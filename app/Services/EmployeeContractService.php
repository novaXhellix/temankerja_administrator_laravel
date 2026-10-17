<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeContractService
{
    /**
     * Buat atau Perpanjang Kontrak Karyawan Baru
     */
    public function createOrRenewContract(array $data, $uploadedFile = null): EmployeeContract
    {
        return DB::transaction(function () use ($data, $uploadedFile) {
            $employee = Employee::findOrFail($data['employee_id']);

            $dokumenPath = null;
            if ($uploadedFile && $uploadedFile->isValid()) {
                $dokumenPath = $uploadedFile->store('employee_contracts', config('filesystems.default'));
            }

            // Arsipkan / non-aktifkan kontrak aktif sebelumnya jika ada
            EmployeeContract::where('employee_id', $employee->id)
                ->where('status_kontrak', 'Aktif')
                ->update(['status_kontrak' => 'Diperpanjang']);

            $durasiBulan = null;
            $tanggalSelesai = !empty($data['tanggal_selesai']) ? $data['tanggal_selesai'] : null;

            if ($tanggalSelesai && !empty($data['tanggal_mulai'])) {
                $start = Carbon::parse($data['tanggal_mulai']);
                $end = Carbon::parse($tanggalSelesai);
                $durasiBulan = $start->diffInMonths($end) ?: 1;
            }

            $contract = EmployeeContract::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'nomor_kontrak' => $data['nomor_kontrak'] ?? null,
                'jenis_kontrak' => $data['jenis_kontrak'],
                'tanggal_mulai' => $data['tanggal_mulai'],
                'tanggal_selesai' => $tanggalSelesai,
                'durasi_bulan' => $durasiBulan,
                'jabatan' => $data['jabatan'] ?? $employee->jabatan,
                'divisi' => $data['divisi'] ?? $employee->divisi,
                'gaji_pokok' => $data['gaji_pokok'] ?? 0,
                'catatan_khusus' => $data['catatan_khusus'] ?? null,
                'dokumen_kontrak_path' => $dokumenPath,
                'status_kontrak' => 'Aktif',
            ]);

            // Sinkronkan ke master tabel employees
            $employee->update([
                'status_kontrak' => $data['jenis_kontrak'],
                'jabatan' => $data['jabatan'] ?? $employee->jabatan,
                'divisi' => $data['divisi'] ?? $employee->divisi,
                'periode_kontrak_mulai' => $data['tanggal_mulai'],
                'periode_kontrak_selesai' => $tanggalSelesai,
            ]);

            return $contract->load(['employee.company']);
        });
    }

    /**
     * Dapatkan daftar karyawan yang kontraknya akan habis (H-30 atau H-60)
     */
    public function getExpiringContracts(int $daysThreshold = 30): array
    {
        $targetDate = Carbon::today()->addDays($daysThreshold)->format('Y-m-d');
        $today = Carbon::today()->format('Y-m-d');

        $expiring = EmployeeContract::with(['employee.company', 'employee.region'])
            ->where('status_kontrak', 'Aktif')
            ->whereNotNull('tanggal_selesai')
            ->whereBetween('tanggal_selesai', [$today, $targetDate])
            ->orderBy('tanggal_selesai', 'asc')
            ->get();

        $expired = EmployeeContract::with(['employee.company', 'employee.region'])
            ->where('status_kontrak', 'Aktif')
            ->whereNotNull('tanggal_selesai')
            ->where('tanggal_selesai', '<', $today)
            ->orderBy('tanggal_selesai', 'desc')
            ->get();

        return [
            'expiring_soon' => $expiring,
            'already_expired' => $expired,
            'total_alert' => $expiring->count() + $expired->count(),
        ];
    }
}
