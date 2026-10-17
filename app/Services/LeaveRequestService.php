<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveRequestService
{
    /**
     * Buat Pengajuan Cuti / Izin Baru
     */
    public function createLeaveRequest(array $data, $uploadedFile = null): LeaveRequest
    {
        return DB::transaction(function () use ($data, $uploadedFile) {
            $startDate = Carbon::parse($data['tanggal_mulai']);
            $endDate = Carbon::parse($data['tanggal_selesai']);

            // Validasi urutan tanggal
            if ($endDate->lt($startDate)) {
                throw new \InvalidArgumentException('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
            }

            // Hitung durasi hari jika belum diisi
            $jumlahHari = isset($data['jumlah_hari']) && $data['jumlah_hari'] > 0
                ? (int)$data['jumlah_hari']
                : $startDate->diffInDays($endDate) + 1;

            $lampiranPath = null;
            if ($uploadedFile && $uploadedFile->isValid()) {
                $lampiranPath = $uploadedFile->store('leave_attachments', config('filesystems.default'));
            }

            $leaveRequest = LeaveRequest::create([
                'uuid' => (string) Str::uuid(),
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'tanggal_mulai' => $startDate->format('Y-m-d'),
                'tanggal_selesai' => $endDate->format('Y-m-d'),
                'jumlah_hari' => $jumlahHari,
                'alasan' => $data['alasan'] ?? '-',
                'lampiran_path' => $lampiranPath,
                'status_persetujuan' => 'Pending',
            ]);

            return $leaveRequest->load(['employee.company', 'leaveType']);
        });
    }

    /**
     * Proses Persetujuan (Approve / Reject) Pengajuan Cuti
     */
    public function processApproval(LeaveRequest $leaveRequest, string $action, ?int $approverUserId, ?string $catatan = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $action, $approverUserId, $catatan) {
            $isApproved = strtolower($action) === 'approved' || strtolower($action) === 'approve';
            $status = $isApproved ? 'Approved' : 'Rejected';

            $leaveRequest->update([
                'status_persetujuan' => $status,
                'approved_by_user_id' => $approverUserId,
                'approved_at' => now(),
                'catatan_persetujuan' => $catatan,
            ]);

            // Jika Approved, sinkronkan ke tabel attendances (Presensi Harian)
            if ($isApproved) {
                $this->syncAttendanceRecords($leaveRequest);
            }

            return $leaveRequest->fresh(['employee.company', 'leaveType', 'approver']);
        });
    }

    /**
     * Sinkronkan rentang tanggal cuti ke tabel log absensi harian (attendances)
     */
    protected function syncAttendanceRecords(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        if (!$employee) {
            return;
        }

        $leaveType = $leaveRequest->leaveType;
        $statusKehadiran = 'Cuti';
        if ($leaveType) {
            if ($leaveType->kategori === 'Sakit') {
                $statusKehadiran = 'Izin';
            } elseif ($leaveType->kategori === 'Izin' || $leaveType->kategori === 'Dinas') {
                $statusKehadiran = 'Izin';
            }
        }

        $period = CarbonPeriod::create($leaveRequest->tanggal_mulai, $leaveRequest->tanggal_selesai);

        foreach ($period as $date) {
            $tanggalStr = $date->format('Y-m-d');

            // Simpan / update status kehadiran
            Attendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'tanggal' => $tanggalStr,
                ],
                [
                    'company_id' => $employee->company_id,
                    'status_kehadiran' => $statusKehadiran,
                    'catatan' => 'Perizinan: ' . ($leaveType->nama_tipe ?? 'Cuti') . ' (' . $leaveRequest->alasan . ')',
                ]
            );
        }
    }

    /**
     * Ringkasan kuota dan statistik cuti karyawan
     */
    public function getEmployeeLeaveBalance(int $employeeId, ?int $year = null): array
    {
        $year = $year ?: (int)date('Y');

        $totalApprovedDays = LeaveRequest::where('employee_id', $employeeId)
            ->where('status_persetujuan', 'Approved')
            ->whereYear('tanggal_mulai', $year)
            ->whereHas('leaveType', function ($query) {
                $query->where('potong_cuti_tahunan', true);
            })
            ->sum('jumlah_hari');

        $defaultQuota = 12;
        $remainingQuota = max(0, $defaultQuota - $totalApprovedDays);

        return [
            'year' => $year,
            'default_quota' => $defaultQuota,
            'used_days' => (int)$totalApprovedDays,
            'remaining_quota' => $remainingQuota,
        ];
    }
}
