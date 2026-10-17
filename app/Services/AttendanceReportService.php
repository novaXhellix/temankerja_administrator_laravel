<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceReport;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    /**
     * Hitung dan perbarui tabel rekap attendance_reports untuk karyawan pada bulan dan tahun tertentu.
     */
    public function syncMonthlyReport(Employee $employee, int $month, int $year): AttendanceReport
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $totalHadir = $attendances->whereIn('status_kehadiran', ['H', 'H/EO', 'H/NCO', 'H/NCI', 'H/ISH', 'DL', 'Hadir'])->count();
        $totalTerlambat = $attendances->whereIn('status_kehadiran', ['H/LI', 'Terlambat'])->count();
        $totalIzin = $attendances->whereIn('status_kehadiran', ['H/ISH', 'CDT', 'Izin'])->count();
        $totalCuti = $attendances->whereIn('status_kehadiran', ['CT', 'CM', 'CKM', 'Cuti'])->count();
        $totalSakit = $attendances->whereIn('status_kehadiran', ['SSD', 'Sakit'])->count();
        $totalAlpa = $attendances->whereIn('status_kehadiran', ['A', 'Alpa'])->count();
        $totalMenitTerlambat = $attendances->sum('menit_keterlambatan');

        // Total hari kerja dalam bulan (default 22 hari atau berdasarkan jumlah weekdays)
        $totalHariKerja = 0;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if (!$current->isWeekend()) {
                $totalHariKerja++;
            }
            $current->addDay();
        }

        return AttendanceReport::updateOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'bulan' => $month,
                'tahun' => $year,
            ],
            [
                'total_hari_kerja' => $totalHariKerja,
                'total_hadir' => $totalHadir,
                'total_terlambat' => $totalTerlambat,
                'total_izin' => $totalIzin,
                'total_cuti' => $totalCuti,
                'total_sakit' => $totalSakit,
                'total_alpa' => $totalAlpa,
                'total_menit_keterlambatan' => $totalMenitTerlambat,
            ]
        );
    }

    /**
     * Dapatkan ringkasan statistik komprehensif kehadiran perusahaan atau divisi.
     */
    public function getCompanyAttendanceSummary(int $companyId, ?int $month = null, ?int $year = null): array
    {
        $month = $month ?: now()->month;
        $year = $year ?: now()->year;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $employeesCount = Employee::where('company_id', $companyId)->where('status_karyawan', 'Aktif')->count();

        $attendances = Attendance::where('company_id', $companyId)
            ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get();

        $today = now()->format('Y-m-d');
        $todayAttendances = Attendance::where('company_id', $companyId)
            ->where('tanggal', $today)
            ->get();

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'month_name' => $startDate->translatedFormat('F Y'),
            ],
            'total_active_employees' => $employeesCount,
            'today_stats' => [
                'clocked_in' => $todayAttendances->count(),
                'on_time' => $todayAttendances->where('status_kehadiran', 'Hadir')->count(),
                'late' => $todayAttendances->where('status_kehadiran', 'Terlambat')->count(),
                'clocked_out' => $todayAttendances->whereNotNull('jam_keluar')->count(),
                'out_of_geofence' => $todayAttendances->where('is_in_geofence_masuk', false)->count(),
            ],
            'monthly_totals' => [
                'total_records' => $attendances->count(),
                'hadir' => $attendances->whereIn('status_kehadiran', ['H', 'H/EO', 'H/NCO', 'H/NCI', 'H/ISH', 'DL', 'Hadir'])->count(),
                'terlambat' => $attendances->whereIn('status_kehadiran', ['H/LI', 'Terlambat'])->count(),
                'izin' => $attendances->whereIn('status_kehadiran', ['H/ISH', 'CDT', 'Izin'])->count(),
                'cuti' => $attendances->whereIn('status_kehadiran', ['CT', 'CM', 'CKM', 'Cuti'])->count(),
                'sakit' => $attendances->whereIn('status_kehadiran', ['SSD', 'Sakit'])->count(),
                'alpa' => $attendances->whereIn('status_kehadiran', ['A', 'Alpa'])->count(),
                'total_lateness_minutes' => (int) $attendances->sum('menit_keterlambatan'),
            ],
        ];
    }
}
