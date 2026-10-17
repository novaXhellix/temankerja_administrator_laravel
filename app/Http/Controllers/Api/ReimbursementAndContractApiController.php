<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Services\EmployeeContractService;
use App\Services\ReimbursementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReimbursementAndContractApiController extends Controller
{
    protected ReimbursementService $reimbursementService;
    protected EmployeeContractService $contractService;

    public function __construct(ReimbursementService $reimbursementService, EmployeeContractService $contractService)
    {
        $this->reimbursementService = $reimbursementService;
        $this->contractService = $contractService;
    }

    /**
     * 1. REIMBURSEMENT: Master Tipe Klaim
     * GET /api/reimbursements/types
     */
    public function getTypes(Request $request): JsonResponse
    {
        $types = ReimbursementType::where('status', 'Aktif')->orderBy('nama_tipe')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jenis reimbursement berhasil diambil.',
            'data' => $types,
        ]);
    }

    /**
     * 2. REIMBURSEMENT: List & Filter Klaim
     * GET /api/reimbursements
     */
    public function listReimbursements(Request $request): JsonResponse
    {
        $query = Reimbursement::with(['employee.company', 'reimbursementType', 'approver:id,name,email']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->latest()->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * 3. REIMBURSEMENT: Pengajuan Baru
     * POST /api/reimbursements
     */
    public function storeReimbursement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'reimbursement_type_id' => 'required|exists:reimbursement_types,id',
            'tanggal_klaim' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'keterangan' => 'required|string|max:1000',
            'bukti_nota' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $reimbursement = $this->reimbursementService->createReimbursement(
                $validator->validated(),
                $request->file('bukti_nota')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan reimbursement berhasil dikirim.',
                'data' => $reimbursement,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan reimbursement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 4. REIMBURSEMENT: Action Approval / Payment
     * POST /api/reimbursements/{uuid}/action
     */
    public function actionReimbursement(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Approved,Rejected,Paid',
            'catatan_approval' => 'nullable|string|max:500',
            'metode_pembayaran' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi status gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $reimbursement = Reimbursement::where('uuid', $uuid)->firstOrFail();
        $approverId = $request->user() ? $request->user()->id : null;

        $updated = $this->reimbursementService->processStatus(
            $reimbursement,
            $request->status,
            $approverId,
            $request->catatan_approval,
            $request->metode_pembayaran
        );

        return response()->json([
            'success' => true,
            'message' => "Status reimbursement berhasil diubah menjadi {$updated->status}.",
            'data' => $updated,
        ]);
    }

    /**
     * 5. KONTRAK: List Kontrak Karyawan
     * GET /api/contracts
     */
    public function listContracts(Request $request): JsonResponse
    {
        $query = EmployeeContract::with(['employee.company', 'employee.region']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('jenis_kontrak')) {
            $query->where('jenis_kontrak', $request->jenis_kontrak);
        }

        $contracts = $query->latest()->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $contracts,
        ]);
    }

    /**
     * 6. KONTRAK: Peringatan Kontrak Habis (Expiring Alerts)
     * GET /api/contracts/expiring-alerts
     */
    public function contractAlerts(Request $request): JsonResponse
    {
        $days = (int)$request->query('days', 30);
        $alerts = $this->contractService->getExpiringContracts($days);

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }
}
