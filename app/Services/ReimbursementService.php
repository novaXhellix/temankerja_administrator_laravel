<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use Illuminate\Support\Facades\DB;

class ReimbursementService
{
    /**
     * Buat Pengajuan Reimbursement / Klaim Biaya
     */
    public function createReimbursement(array $data, $uploadedReceipt = null): Reimbursement
    {
        return DB::transaction(function () use ($data, $uploadedReceipt) {
            $employee = Employee::findOrFail($data['employee_id']);

            $buktiPath = null;
            if ($uploadedReceipt && $uploadedReceipt->isValid()) {
                $buktiPath = $uploadedReceipt->store('reimbursement_receipts', config('filesystems.default'));
            }

            $reimbursement = Reimbursement::create([
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'reimbursement_type_id' => $data['reimbursement_type_id'],
                'tanggal_klaim' => $data['tanggal_klaim'],
                'nominal' => $data['nominal'],
                'keterangan' => $data['keterangan'] ?? '-',
                'bukti_nota_path' => $buktiPath,
                'status' => 'Pending',
            ]);

            return $reimbursement->load(['employee.company', 'reimbursementType']);
        });
    }

    /**
     * Approval / Rejection / Pembayaran Klaim
     */
    public function processStatus(Reimbursement $reimbursement, string $status, ?int $approverId, ?string $catatan = null, ?string $metode = null): Reimbursement
    {
        return DB::transaction(function () use ($reimbursement, $status, $approverId, $catatan, $metode) {
            $updateData = [
                'status' => $status,
                'approved_by_user_id' => $approverId,
                'catatan_approval' => $catatan,
            ];

            if ($status === 'Approved') {
                $updateData['approved_at'] = now();
            } elseif ($status === 'Paid') {
                $updateData['tanggal_pencairan'] = now()->format('Y-m-d');
                $updateData['metode_pembayaran'] = $metode ?: 'Transfer Bank';
            }

            $reimbursement->update($updateData);

            return $reimbursement->fresh(['employee.company', 'reimbursementType', 'approver']);
        });
    }
}
