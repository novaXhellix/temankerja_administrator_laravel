<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeAttendanceController extends Controller
{
    /**
     * Tier 1: Perusahaan Selection List
     */
    public function index(): Response
    {
        $companies = Company::withCount(['employees', 'attendances'])->get()->map(function ($comp) {
            $comp->total_attendances = $comp->attendances_count;
            return $comp;
        });

        return Inertia::render('attendances/CompanyIndex', [
            'companies' => $companies,
        ]);
    }

    /**
     * Tier 2: List Karyawan & Skema Periode Cut-Off Perusahaan
     */
    public function showCompany(Request $request, string $slug): Response
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $startDay = (int) $request->input('start_day', $company->cutoff_start_day ?? 26);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        // Rentang kalender bulan berjalan (1 s/d akhir bulan) dan periode cut-off
        $calStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $calEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $endDate = Carbon::createFromDate($year, $month, $startDay - 1)->endOfDay();
        $startDate = Carbon::createFromDate($year, $month, $startDay)->subMonth()->startOfDay();

        $queryStart = $calStart->copy()->min($startDate);
        $queryEnd = $calEnd->copy()->max($endDate);

        $activeEmployees = Employee::with('user')
            ->where('company_id', $company->id)
            ->where('status_karyawan', 'Aktif')
            ->get();

        $allAttendances = Attendance::where('company_id', $company->id)
            ->whereBetween('tanggal', [$queryStart->format('Y-m-d'), $queryEnd->format('Y-m-d')])
            ->get();

        $employees = $activeEmployees->map(function ($emp) use ($startDate, $endDate, $allAttendances) {
            $attendances = $allAttendances->where('employee_id', $emp->id);
            $cutOffAttendances = $attendances->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

            $emp->hadir_count = $cutOffAttendances->whereIn('status_kehadiran', ['H', 'Hadir', 'H/EO', 'H/NCO', 'H/NCI', 'DL'])->count();
            $emp->terlambat_count = $cutOffAttendances->whereIn('status_kehadiran', ['H/LI', 'Terlambat'])->count();
            $emp->izin_count = $cutOffAttendances->whereIn('status_kehadiran', ['SSD', 'H/ISH', 'CDT', 'Izin', 'Sakit'])->count();
            $emp->cuti_count = $cutOffAttendances->whereIn('status_kehadiran', ['CT', 'CM', 'CKM', 'Cuti'])->count();
            $emp->alpha_count = $cutOffAttendances->whereIn('status_kehadiran', ['A', 'Alpa'])->count();

            // Peta presensi harian per tanggal untuk Matriks Kalender Timesheet
            $dailyMap = [];
            foreach ($attendances as $att) {
                $dateKey = is_string($att->tanggal) ? substr($att->tanggal, 0, 10) : $att->tanggal->format('Y-m-d');
                $dailyMap[$dateKey] = [
                    'status' => $att->status_kehadiran,
                    'in' => $att->jam_masuk ? substr($att->jam_masuk, 0, 5) : null,
                    'out' => $att->jam_keluar ? substr($att->jam_keluar, 0, 5) : null,
                ];
            }
            $emp->daily_attendance = $dailyMap;

            return $emp;
        });

        return Inertia::render('attendances/CompanyEmployees', [
            'company' => $company,
            'employees' => $employees,
            'statusLegends' => \App\Enums\AttendanceStatus::allWithLabels(),
            'period' => [
                'start_day' => $startDay,
                'month' => $month,
                'year' => $year,
                'start_date_formatted' => $startDate->translatedFormat('d M Y'),
                'end_date_formatted' => $endDate->translatedFormat('d M Y'),
                'cal_start_date' => $calStart->format('Y-m-d'),
                'cal_end_date' => $calEnd->format('Y-m-d'),
                'month_name' => $calStart->translatedFormat('F Y'),
                'days_in_month' => $calStart->daysInMonth,
            ],
        ]);
    }

    /**
     * Tier 3: Detail Presensi Bulanan Per Karyawan (Slug Routing & Gaya Kalender)
     */
    public function showEmployee(Request $request, string $companySlug, string $employeeSlug): Response
    {
        $company = Company::where('slug', $companySlug)->firstOrFail();
        $employee = Employee::with('user')->where('slug', $employeeSlug)->where('company_id', $company->id)->firstOrFail();

        $startDay = (int) $request->input('start_day', $company->cutoff_start_day ?? 26);
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $calStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $calEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $endDate = Carbon::createFromDate($year, $month, $startDay - 1)->endOfDay();
        $startDate = Carbon::createFromDate($year, $month, $startDay)->subMonth()->startOfDay();

        $queryStart = $calStart->copy()->min($startDate);
        $queryEnd = $calEnd->copy()->max($endDate);

        $attendances = Attendance::with('location')
            ->where('employee_id', $employee->id)
            ->whereBetween('tanggal', [$queryStart->format('Y-m-d'), $queryEnd->format('Y-m-d')])
            ->orderBy('tanggal', 'asc')
            ->get();

        return Inertia::render('attendances/EmployeeDetail', [
            'company' => $company,
            'employee' => $employee,
            'attendances' => $attendances,
            'statusLegends' => \App\Enums\AttendanceStatus::allWithLabels(),
            'period' => [
                'start_day' => $startDay,
                'month' => $month,
                'year' => $year,
                'start_date_formatted' => $startDate->translatedFormat('d M Y'),
                'end_date_formatted' => $endDate->translatedFormat('d M Y'),
                'cal_start_date' => $calStart->format('Y-m-d'),
                'cal_end_date' => $calEnd->format('Y-m-d'),
                'month_name' => $calStart->translatedFormat('F Y'),
                'days_in_month' => $calStart->daysInMonth,
            ],
        ]);
    }
}
