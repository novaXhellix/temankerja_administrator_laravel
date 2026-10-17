<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceReport;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceReportApiController extends Controller
{
    public function __construct(
        protected AttendanceReportService $reportService,
    ) {}

    /**
     * Dapatkan Laporan Ringkasan Absensi Perusahaan (Dashboard / Executive Summary).
     */
    public function companySummary(Request $request): JsonResponse
    {
        $companyId = (int) $request->input('company_id');
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        if (!$companyId && $request->user()) {
            $emp = Employee::where('user_id', $request->user()->id)->first();
            $companyId = $emp?->company_id ?: Company::first()?->id;
        }

        if (!$companyId) {
            $companyId = Company::first()?->id;
        }

        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $summary = $this->reportService->getCompanyAttendanceSummary($companyId, $month, $year);

        return response()->json([
            'success' => true,
            'message' => 'Laporan ringkasan presensi perusahaan berhasil dimuat.',
            'company' => [
                'id' => $company->id,
                'nama_perusahaan' => $company->nama_perusahaan,
                'kode_perusahaan' => $company->kode_perusahaan,
            ],
            'data' => $summary,
        ]);
    }

    /**
     * Dapatkan Laporan Rekap Bulanan Semua Karyawan dalam Perusahaan (Monthly Cut-Off & Table View).
     */
    public function monthlyRecap(Request $request): JsonResponse
    {
        $companyId = (int) $request->input('company_id');
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        if (!$companyId && $request->user()) {
            $emp = Employee::where('user_id', $request->user()->id)->first();
            $companyId = $emp?->company_id ?: Company::first()?->id;
        }

        if (!$companyId) {
            $companyId = Company::first()?->id;
        }

        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $employees = Employee::with(['workShift', 'region'])
            ->where('company_id', $companyId)
            ->where('status_karyawan', 'Aktif')
            ->get();

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $recap = $employees->map(function ($emp) use ($month, $year, $startDate, $endDate) {
            // Hitung & sinkronisasi data rekap
            $report = $this->reportService->syncMonthlyReport($emp, $month, $year);

            return [
                'employee_id' => $emp->id,
                'nama_karyawan' => $emp->nama_karyawan,
                'nip' => $emp->nip,
                'jabatan' => $emp->jabatan,
                'wilayah' => $emp->region?->nama_wilayah ?? $emp->wilayah ?? '-',
                'shift' => $emp->workShift?->nama_shift ?? 'Default',
                'rekap' => [
                    'total_hari_kerja' => $report->total_hari_kerja,
                    'total_hadir' => $report->total_hadir,
                    'total_terlambat' => $report->total_terlambat,
                    'total_izin' => $report->total_izin,
                    'total_cuti' => $report->total_cuti,
                    'total_sakit' => $report->total_sakit,
                    'total_alpa' => $report->total_alpa,
                    'total_menit_keterlambatan' => $report->total_menit_keterlambatan,
                    'persentase_kehadiran' => $report->total_hari_kerja > 0 
                        ? round((($report->total_hadir + $report->total_terlambat) / $report->total_hari_kerja) * 100, 1) . '%'
                        : '0%',
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Rekapitulasi absensi bulanan berhasil dimuat.',
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $startDate->translatedFormat('F Y'),
            ],
            'company' => [
                'id' => $company->id,
                'nama' => $company->nama_perusahaan,
            ],
            'total_employees' => $employees->count(),
            'data' => $recap,
        ]);
    }

    /**
     * Dapatkan Laporan Detail Harian Absensi Karyawan Tertentu (Daily Timesheet).
     */
    public function employeeDailyTimesheet(Request $request, int $employeeId): JsonResponse
    {
        $employee = Employee::with(['company', 'workShift', 'region'])->find($employeeId);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan.',
            ], 404);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $attendances = Attendance::with('location')
            ->where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($item) => $item->tanggal instanceof Carbon ? $item->tanggal->format('Y-m-d') : substr($item->tanggal, 0, 10));

        // Generate full daily matrix for the entire month
        $daysList = [];
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            $dateStr = $current->format('Y-m-d');
            $att = $attendances->get($dateStr);

            $daysList[] = [
                'date' => $dateStr,
                'day_name' => $current->translatedFormat('l'),
                'is_weekend' => $current->isWeekend(),
                'attendance' => $att ? [
                    'id' => $att->id,
                    'jam_masuk' => $att->jam_masuk ? substr($att->jam_masuk, 0, 5) : null,
                    'jam_keluar' => $att->jam_keluar ? substr($att->jam_keluar, 0, 5) : null,
                    'status' => $att->status_kehadiran,
                    'menit_terlambat' => $att->menit_keterlambatan,
                    'lokasi' => $att->location?->nama_lokasi ?? 'Lokasi Luar Kantor',
                    'in_geofence' => (bool) $att->is_in_geofence_masuk,
                    'jarak_meter' => $att->jarak_meter_masuk,
                    'foto_masuk_url' => $att->foto_masuk_path ? asset('storage/' . $att->foto_masuk_path) : null,
                    'foto_keluar_url' => $att->foto_keluar_path ? asset('storage/' . $att->foto_keluar_path) : null,
                ] : null,
            ];

            $current->addDay();
        }

        $report = $this->reportService->syncMonthlyReport($employee, $month, $year);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet harian karyawan berhasil dimuat.',
            'employee' => [
                'id' => $employee->id,
                'nama' => $employee->nama_karyawan,
                'nip' => $employee->nip,
                'jabatan' => $employee->jabatan,
                'perusahaan' => $employee->company?->nama_perusahaan,
                'shift' => $employee->workShift?->nama_shift ?? 'Default 08:00 - 17:00',
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $startDate->translatedFormat('F Y'),
            ],
            'monthly_summary' => $report,
            'days' => $daysList,
        ]);
    }
}
