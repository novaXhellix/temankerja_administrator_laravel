<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveApiController extends Controller
{
    protected LeaveRequestService $leaveService;

    public function __construct(LeaveRequestService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Master Jenis Cuti & Izin
     * GET /api/leaves/types
     */
    public function getLeaveTypes(Request $request): JsonResponse
    {
        $companyId = $request->query('company_id');

        $query = LeaveType::where('status', 'Aktif');
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }

        $types = $query->orderBy('nama_tipe')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar jenis cuti & perizinan berhasil diambil.',
            'data' => $types,
        ]);
    }

    /**
     * List Pengajuan Cuti (Bisa difilter per employee / status / date)
     * GET /api/leaves/requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::with(['employee.company', 'leaveType', 'approver:id,name,email']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status_persetujuan', $request->status);
        }

        if ($request->filled('year')) {
            $query->whereYear('tanggal_mulai', $request->year);
        }

        $leaves = $query->latest()->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan cuti berhasil dimuat.',
            'data' => $leaves,
        ]);
    }

    /**
     * Pengajuan Cuti / Izin Baru
     * POST /api/leaves/requests
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jumlah_hari' => 'nullable|integer|min:1',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $leave = $this->leaveService->createLeaveRequest(
                $validator->validated(),
                $request->file('lampiran')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan cuti berhasil dibuat dan menunggu persetujuan.',
                'data' => $leave,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengajuan cuti: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detail Pengajuan Cuti
     * GET /api/leaves/requests/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $leave = LeaveRequest::with(['employee.company', 'employee.region', 'leaveType', 'approver:id,name,email'])
            ->where('uuid', $uuid)
            ->first();

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan cuti tidak ditemukan.',
            ], 404);
        }

        $balance = $this->leaveService->getEmployeeLeaveBalance($leave->employee_id, (int)substr($leave->tanggal_mulai, 0, 4));

        return response()->json([
            'success' => true,
            'data' => [
                'leave_request' => $leave,
                'leave_balance' => $balance,
            ],
        ]);
    }

    /**
     * Approval / Rejection Pengajuan Cuti (API Workflow)
     * POST /api/leaves/requests/{uuid}/action
     */
    public function handleApproval(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,approved,reject,rejected',
            'catatan_persetujuan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter aksi tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leave = LeaveRequest::where('uuid', $uuid)->first();
        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan cuti tidak ditemukan.',
            ], 404);
        }

        $approverId = $request->user() ? $request->user()->id : null;

        try {
            $updatedLeave = $this->leaveService->processApproval(
                $leave,
                $request->action,
                $approverId,
                $request->catatan_persetujuan
            );

            return response()->json([
                'success' => true,
                'message' => 'Status pengajuan cuti berhasil diperbarui menjadi ' . $updatedLeave->status_persetujuan . '.',
                'data' => $updatedLeave,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses persetujuan cuti: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cek Saldo & Kuota Cuti Karyawan
     * GET /api/leaves/employee/{employeeId}/balance
     */
    public function getBalance(int $employeeId, Request $request): JsonResponse
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Data karyawan tidak ditemukan.',
            ], 404);
        }

        $year = $request->query('year', (int)date('Y'));
        $balance = $this->leaveService->getEmployeeLeaveBalance($employeeId, (int)$year);

        return response()->json([
            'success' => true,
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'nama_lengkap' => $employee->nama_lengkap,
                    'nomor_induk_karyawan' => $employee->nomor_induk_karyawan,
                    'jabatan' => $employee->jabatan,
                ],
                'balance' => $balance,
            ],
        ]);
    }
}
