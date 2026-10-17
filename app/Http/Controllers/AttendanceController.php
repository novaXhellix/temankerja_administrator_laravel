<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AttendanceReportService;
use App\Services\GeofenceService;
use App\Services\ShiftEvaluationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected ShiftEvaluationService $shiftEvaluationService,
        protected AttendanceReportService $reportService,
    ) {}

    /**
     * Halaman Utama Absensi: Live Presensi (Clock In / Clock Out) & Monitoring Hari Ini.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Cari karyawan yang terhubung dengan user login, atau karyawan pertama untuk preview/admin
        $employee = Employee::with(['company', 'workShift', 'region'])
            ->where('user_id', $user->id)
            ->first();

        if (!$employee && $request->filled('employee_id')) {
            $employee = Employee::with(['company', 'workShift', 'region'])->find($request->employee_id);
        }

        if (!$employee) {
            $employee = Employee::with(['company', 'workShift', 'region'])->where('status_karyawan', 'Aktif')->first();
        }

        $today = Carbon::today()->format('Y-m-d');
        $attendanceToday = null;
        $shiftEvaluation = null;

        if ($employee) {
            $attendanceToday = Attendance::with(['location', 'workShift', 'company'])
                ->where('employee_id', $employee->id)
                ->where('tanggal', $today)
                ->first();

            $shiftEvaluation = $this->shiftEvaluationService->evaluateClockIn($employee->workShift, Carbon::now());
        }

        // Lokasi Geofence Kantor yang aktif
        $locations = AttendanceLocation::where('status', 'Aktif')
            ->when($employee, function ($q) use ($employee) {
                $q->where('company_id', $employee->company_id);
            })
            ->get();

        // Riwayat 10 absensi terakhir
        $recentLogs = Attendance::with(['employee', 'location', 'company'])
            ->when($employee, function ($q) use ($employee) {
                $q->where('company_id', $employee->company_id);
            })
            ->latest('tanggal')
            ->latest('jam_masuk')
            ->take(10)
            ->get();

        // Statistik hari ini untuk perusahaan
        $todayStats = [
            'total_hadir' => Attendance::where('tanggal', $today)->whereIn('status_kehadiran', ['H', 'H/EO', 'H/NCO', 'H/NCI', 'DL', 'Hadir'])->count(),
            'total_terlambat' => Attendance::where('tanggal', $today)->whereIn('status_kehadiran', ['H/LI', 'Terlambat'])->count(),
            'total_keluar' => Attendance::where('tanggal', $today)->whereNotNull('jam_keluar')->count(),
        ];

        $allEmployees = Employee::select('id', 'nama_karyawan', 'nip', 'jabatan', 'company_id')->where('status_karyawan', 'Aktif')->get();
        $companies = Company::select('id', 'nama_perusahaan', 'kode_perusahaan')->get();

        return Inertia::render('attendances/Index', [
            'employee' => $employee,
            'attendanceToday' => $attendanceToday,
            'shiftEvaluation' => $shiftEvaluation,
            'locations' => $locations,
            'recentLogs' => $recentLogs,
            'todayStats' => $todayStats,
            'allEmployees' => $allEmployees,
            'companies' => $companies,
            'serverTime' => Carbon::now()->toIso8601String(),
        ]);
    }

    /**
     * Web Action: Submit Clock-In langsung dari browser / web dashboard.
     */
    public function webClockIn(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_location_id' => 'required|exists:attendance_locations,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'catatan' => 'nullable|string|max:500',
            'foto' => 'nullable|image|max:5120',
        ]);

        $employee = Employee::with(['company', 'workShift'])->findOrFail($request->employee_id);
        $location = AttendanceLocation::findOrFail($request->attendance_location_id);
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $existing = Attendance::where('employee_id', $employee->id)->where('tanggal', $today)->first();
        if ($existing) {
            return back()->withErrors(['attendance' => 'Anda sudah Clock In hari ini pada ' . substr($existing->jam_masuk, 0, 5)]);
        }

        // Evaluasi Geofencing
        $geoCheck = $this->geofenceService->validateGeofence($location, (float) $request->latitude, (float) $request->longitude);
        $bypass = $request->boolean('bypass_geofence', false);

        if (!$geoCheck['is_in_geofence'] && !$bypass) {
            return back()->withErrors(['geofence' => "Posisi Anda berada di luar radius kantor ({$geoCheck['distance_meter']} m dari maksimal {$location->radius_meter} m)."]);
        }

        // Evaluasi Shift
        $shiftEval = $this->shiftEvaluationService->evaluateClockIn($employee->workShift, $now);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->storeAs(
                'attendances/clock_in',
                'in_' . $employee->id . '_' . time() . '.' . $request->file('foto')->getClientOriginalExtension(),
                config('filesystems.default')
            );
        }

        DB::beginTransaction();
        try {
            Attendance::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'attendance_location_id' => $location->id,
                'work_shift_id' => $employee->work_shift_id,
                'tanggal' => $today,
                'jam_masuk' => $now->format('H:i:s'),
                'latitude_masuk' => $request->latitude,
                'longitude_masuk' => $request->longitude,
                'is_in_geofence_masuk' => $geoCheck['is_in_geofence'],
                'jarak_meter_masuk' => $geoCheck['distance_meter'],
                'foto_masuk_path' => $fotoPath,
                'status_kehadiran' => $shiftEval['status_kehadiran'],
                'menit_keterlambatan' => $shiftEval['lateness_minutes'],
                'catatan' => $request->catatan,
            ]);

            $this->reportService->syncMonthlyReport($employee, (int) $now->month, (int) $now->year);

            DB::commit();

            return back()->with('success', 'Clock In berhasil dicatat. ' . $shiftEval['message']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['attendance' => 'Gagal menyimpan Clock In: ' . $e->getMessage()]);
        }
    }

    /**
     * Web Action: Submit Clock-Out langsung dari browser / web dashboard.
     */
    public function webClockOut(Request $request): RedirectResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'catatan' => 'nullable|string|max:500',
            'foto' => 'nullable|image|max:5120',
        ]);

        $employee = Employee::with(['company', 'workShift'])->findOrFail($request->employee_id);
        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        $attendance = Attendance::where('employee_id', $employee->id)->where('tanggal', $today)->first();
        if (!$attendance) {
            return back()->withErrors(['attendance' => 'Anda belum melakukan Clock In hari ini.']);
        }

        if ($attendance->jam_keluar) {
            return back()->withErrors(['attendance' => 'Anda sudah melakukan Clock Out sebelumnya pada ' . substr($attendance->jam_keluar, 0, 5)]);
        }

        $location = $attendance->location ?: AttendanceLocation::where('company_id', $employee->company_id)->where('status', 'Aktif')->first();
        $geoCheck = ['is_in_geofence' => true, 'distance_meter' => 0];

        if ($location) {
            $geoCheck = $this->geofenceService->validateGeofence($location, (float) $request->latitude, (float) $request->longitude);
            $bypass = $request->boolean('bypass_geofence', false);
            if (!$geoCheck['is_in_geofence'] && !$bypass) {
                return back()->withErrors(['geofence' => "Posisi Anda ({$geoCheck['distance_meter']} m) berada di luar batas geofence kantor."]);
            }
        }

        $outEval = $this->shiftEvaluationService->evaluateClockOut($employee->workShift, $now);

        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->storeAs(
                'attendances/clock_out',
                'out_' . $employee->id . '_' . time() . '.' . $request->file('foto')->getClientOriginalExtension(),
                config('filesystems.default')
            );
        }

        DB::beginTransaction();
        try {
            $newStatus = $attendance->status_kehadiran;
            if ($outEval['is_early_leave'] && in_array($attendance->status_kehadiran, ['H', 'Hadir'])) {
                $newStatus = 'H/EO';
            }

            $attendance->update([
                'jam_keluar' => $now->format('H:i:s'),
                'latitude_keluar' => $request->latitude,
                'longitude_keluar' => $request->longitude,
                'is_in_geofence_keluar' => $geoCheck['is_in_geofence'],
                'jarak_meter_keluar' => $geoCheck['distance_meter'],
                'foto_keluar_path' => $fotoPath,
                'status_kehadiran' => $newStatus,
                'catatan' => $request->filled('catatan') 
                    ? ($attendance->catatan ? $attendance->catatan . ' | Out: ' . $request->catatan : $request->catatan)
                    : $attendance->catatan,
            ]);

            $this->reportService->syncMonthlyReport($employee, (int) $now->month, (int) $now->year);

            DB::commit();

            return back()->with('success', 'Clock Out berhasil dicatat. ' . ($outEval['message'] ?? 'Selamat beristirahat!'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['attendance' => 'Gagal mencatat Clock Out: ' . $e->getMessage()]);
        }
    }
}
