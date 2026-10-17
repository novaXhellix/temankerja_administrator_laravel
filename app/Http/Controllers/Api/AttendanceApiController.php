<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ClockInRequest;
use App\Http\Requests\Api\ClockOutRequest;
use App\Http\Resources\Api\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Services\AttendanceReportService;
use App\Services\GeofenceService;
use App\Services\ShiftEvaluationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceApiController extends Controller
{
    public function __construct(
        protected GeofenceService $geofenceService,
        protected ShiftEvaluationService $shiftEvaluationService,
        protected AttendanceReportService $reportService,
    ) {}

    /**
     * Dapatkan Karyawan terkait untuk user yang terotentikasi atau dari employee_id.
     */
    protected function resolveEmployee(Request $request, ?int $employeeId = null): ?Employee
    {
        $user = $request->user();

        if ($employeeId) {
            return Employee::with(['company', 'workShift', 'region'])->find($employeeId);
        }

        if ($user) {
            $employee = Employee::with(['company', 'workShift', 'region'])->where('user_id', $user->id)->first();
            if ($employee) {
                return $employee;
            }
        }

        return null;
    }

    /**
     * Cek status absensi hari ini, jadwal shift, dan geofence lokasi terdekat.
     */
    public function todayStatus(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request, $request->input('employee_id'));

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil karyawan tidak ditemukan. Pastikan akun terhubung dengan data karyawan.',
            ], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::with(['location', 'workShift', 'company'])
            ->where('employee_id', $employee->id)
            ->where('tanggal', $today)
            ->first();

        // Evaluasi shift hari ini
        $shiftEval = $this->shiftEvaluationService->evaluateClockIn($employee->workShift, Carbon::now());

        // Lokasi kantor yang valid untuk perusahaan/region karyawan
        $locationsQuery = AttendanceLocation::where('company_id', $employee->company_id)
            ->where('status', 'Aktif');

        if ($employee->region_id) {
            $locationsQuery->where(function ($q) use ($employee) {
                $q->where('region_id', $employee->region_id)->orWhereNull('region_id');
            });
        }

        $locations = $locationsQuery->get();

        return response()->json([
            'success' => true,
            'message' => 'Status absensi berhasil dimuat.',
            'data' => [
                'server_time' => Carbon::now()->toIso8601String(),
                'employee' => [
                    'id' => $employee->id,
                    'nama_karyawan' => $employee->nama_karyawan,
                    'nip' => $employee->nip,
                    'jabatan' => $employee->jabatan,
                    'perusahaan' => $employee->company?->nama_perusahaan,
                ],
                'shift_info' => $shiftEval,
                'attendance_today' => $attendance ? new AttendanceResource($attendance) : null,
                'can_clock_in' => is_null($attendance),
                'can_clock_out' => $attendance && is_null($attendance->jam_keluar),
                'available_locations' => $locations->map(fn ($loc) => [
                    'id' => $loc->id,
                    'nama_lokasi' => $loc->nama_lokasi,
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                    'radius_meter' => (int) $loc->radius_meter,
                    'check_type' => $loc->check_type ?? 'radius',
                    'alamat_lengkap' => $loc->alamat_lengkap,
                ]),
            ],
        ]);
    }

    /**
     * API Clock-In (Masuk Kerja).
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request, $request->input('employee_id'));

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil karyawan tidak ditemukan.',
            ], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Cek apakah sudah clock in hari ini
        $existing = Attendance::where('employee_id', $employee->id)
            ->where('tanggal', $today)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan clock in untuk hari ini pada pukul ' . substr($existing->jam_masuk, 0, 5) . '.',
                'data' => new AttendanceResource($existing),
            ], 422);
        }

        // 1. Validasi Geofencing
        $location = AttendanceLocation::where('id', $request->attendance_location_id)
            ->where('company_id', $employee->company_id)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi absensi tidak terdaftar pada perusahaan Anda.',
            ], 422);
        }

        $geoCheck = $this->geofenceService->validateGeofence(
            $location,
            (float) $request->latitude,
            (float) $request->longitude
        );

        $bypass = $request->boolean('bypass_geofence', false);
        if (!$geoCheck['is_in_geofence'] && !$bypass) {
            return response()->json([
                'success' => false,
                'message' => "Posisi Anda ({$geoCheck['distance_meter']} m) berada di luar batas radius lokasi absensi '{$location->nama_lokasi}' (Maksimal {$location->radius_meter} m).",
                'geofence_details' => $geoCheck,
            ], 422);
        }

        // 2. Evaluasi Shift & Toleransi Keterlambatan
        $shiftEval = $this->shiftEvaluationService->evaluateClockIn($employee->workShift, $now);

        // 3. Simpan Foto jika ada
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $filename = 'in_' . $employee->id . '_' . time() . '.' . ($foto->getClientOriginalExtension() ?: 'jpg');
            $fotoPath = $foto->storeAs('attendances/clock_in', $filename, config('filesystems.default'));
        }

        // 4. Simpan Log Absensi ke Database
        DB::beginTransaction();
        try {
            $attendance = Attendance::create([
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

            // Auto sync ke tabel rekap report bulanan
            $this->reportService->syncMonthlyReport($employee, (int) $now->month, (int) $now->year);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clock In berhasil dicatat. ' . $shiftEval['message'],
                'data' => new AttendanceResource($attendance->load(['location', 'workShift', 'company', 'employee'])),
                'shift_evaluation' => $shiftEval,
                'geofence_evaluation' => $geoCheck,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($fotoPath) {
                try {
                    Storage::disk(config('filesystems.default'))->delete($fotoPath);
                } catch (\Throwable) {}
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan absensi clock in: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API Clock-Out (Pulang Kerja).
     */
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request, $request->input('employee_id'));

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data profil karyawan tidak ditemukan.',
            ], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        $now = Carbon::now();

        // Cari record absensi hari ini (atau via attendance_id jika shift malam)
        $attendance = null;
        if ($request->filled('attendance_id')) {
            $attendance = Attendance::where('id', $request->attendance_id)
                ->where('employee_id', $employee->id)
                ->first();
        } else {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->where('tanggal', $today)
                ->first();
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum melakukan Clock In hari ini. Silakan Clock In terlebih dahulu.',
            ], 422);
        }

        if (!empty($attendance->jam_keluar)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan Clock Out sebelumnya pada pukul ' . substr($attendance->jam_keluar, 0, 5) . '.',
                'data' => new AttendanceResource($attendance),
            ], 422);
        }

        // 1. Validasi Geofence jika ada lokasi kantor
        $location = $attendance->location ?: AttendanceLocation::where('company_id', $employee->company_id)->where('status', 'Aktif')->first();
        $geoCheck = [
            'is_in_geofence' => true,
            'distance_meter' => 0,
        ];

        if ($location) {
            $geoCheck = $this->geofenceService->validateGeofence(
                $location,
                (float) $request->latitude,
                (float) $request->longitude
            );

            $bypass = $request->boolean('bypass_geofence', false);
            if (!$geoCheck['is_in_geofence'] && !$bypass) {
                return response()->json([
                    'success' => false,
                    'message' => "Posisi Clock Out Anda ({$geoCheck['distance_meter']} m) berada di luar batas geofence '{$location->nama_lokasi}' (Maksimal {$location->radius_meter} m).",
                    'geofence_details' => $geoCheck,
                ], 422);
            }
        }

        // 2. Evaluasi Waktu Pulang Shift
        $outEval = $this->shiftEvaluationService->evaluateClockOut($employee->workShift, $now);

        // 3. Simpan Foto Bukti Pulang jika ada
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $filename = 'out_' . $employee->id . '_' . time() . '.' . ($foto->getClientOriginalExtension() ?: 'jpg');
            $fotoPath = $foto->storeAs('attendances/clock_out', $filename, config('filesystems.default'));
        }

        DB::beginTransaction();
        try {
            $attendance->update([
                'jam_keluar' => $now->format('H:i:s'),
                'latitude_keluar' => $request->latitude,
                'longitude_keluar' => $request->longitude,
                'is_in_geofence_keluar' => $geoCheck['is_in_geofence'],
                'jarak_meter_keluar' => $geoCheck['distance_meter'],
                'foto_keluar_path' => $fotoPath,
                'catatan' => $request->filled('catatan') 
                    ? ($attendance->catatan ? $attendance->catatan . ' | Out: ' . $request->catatan : $request->catatan)
                    : $attendance->catatan,
            ]);

            // Sync ke laporan bulanan
            $this->reportService->syncMonthlyReport($employee, (int) $now->month, (int) $now->year);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Clock Out berhasil dicatat. ' . ($outEval['message'] ?? 'Selamat beristirahat!'),
                'data' => new AttendanceResource($attendance->fresh(['location', 'workShift', 'company', 'employee'])),
                'early_leave_evaluation' => $outEval,
                'geofence_evaluation' => $geoCheck,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($fotoPath) {
                try {
                    Storage::disk(config('filesystems.default'))->delete($fotoPath);
                } catch (\Throwable) {}
            }
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat clock out: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Riwayat Absensi Karyawan (Attendance Logs).
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $this->resolveEmployee($request, $request->input('employee_id'));

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $limit = (int) $request->input('limit', 15);
        $month = $request->input('month');
        $year = $request->input('year');

        $query = Attendance::with(['location', 'workShift', 'company'])
            ->where('employee_id', $employee->id)
            ->orderBy('tanggal', 'desc');

        if ($month && $year) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d');
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        $attendances = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat presensi berhasil dimuat.',
            'data' => AttendanceResource::collection($attendances),
            'pagination' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }
}
