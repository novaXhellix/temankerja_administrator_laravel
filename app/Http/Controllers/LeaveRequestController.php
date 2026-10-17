<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    protected LeaveRequestService $leaveService;

    public function __construct(LeaveRequestService $leaveService)
    {
        $this->leaveService = $leaveService;
    }

    /**
     * Halaman Utama Pengajuan & Approval Cuti
     */
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $companyId = $request->query('company_id');
        $search = $request->query('search');

        $query = LeaveRequest::with(['employee.company', 'employee.region', 'leaveType', 'approver'])
            ->latest();

        if ($status && in_array($status, ['Pending', 'Approved', 'Rejected'])) {
            $query->where('status_persetujuan', $status);
        }

        if ($companyId) {
            $query->whereHas('employee', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_induk_karyawan', 'like', "%{$search}%");
            });
        }

        $leaveRequests = $query->paginate(12)->withQueryString();

        // Summary Counts
        $stats = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status_persetujuan', 'Pending')->count(),
            'approved' => LeaveRequest::where('status_persetujuan', 'Approved')->count(),
            'rejected' => LeaveRequest::where('status_persetujuan', 'Rejected')->count(),
        ];

        $companies = Company::orderBy('nama_perusahaan')->get(['id', 'nama_perusahaan']);
        $leaveTypes = LeaveType::where('status', 'Aktif')->orderBy('nama_tipe')->get();

        return Inertia::render('leaves/Index', [
            'leaveRequests' => $leaveRequests,
            'stats' => $stats,
            'companies' => $companies,
            'leaveTypes' => $leaveTypes,
            'filters' => [
                'status' => $status,
                'company_id' => $companyId,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Form Buat Pengajuan Cuti Baru (Dedicated Page)
     */
    public function create(): Response
    {
        // Siapkan master data jika belum ada LeaveType
        if (LeaveType::count() === 0) {
            $this->seedDefaultLeaveTypes();
        }

        $employees = Employee::with(['company', 'region'])
            ->where('status_karyawan', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();

        $leaveTypes = LeaveType::where('status', 'Aktif')->orderBy('nama_tipe')->get();

        return Inertia::render('leaves/Create', [
            'employees' => $employees,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    /**
     * Simpan Pengajuan Cuti Baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jumlah_hari' => 'nullable|integer|min:1',
            'alasan' => 'required|string|max:1000',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $this->leaveService->createLeaveRequest(
            $validated,
            $request->file('lampiran')
        );

        return redirect()->route('leave-requests.index')
            ->with('success', 'Pengajuan cuti berhasil dibuat dan sedang menunggu persetujuan.');
    }

    /**
     * Detail & Approval Action Page (Dedicated Page)
     */
    public function show(string $uuid): Response
    {
        $leaveRequest = LeaveRequest::with(['employee.company', 'employee.region', 'leaveType', 'approver'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $year = (int)substr($leaveRequest->tanggal_mulai, 0, 4);
        $leaveBalance = $this->leaveService->getEmployeeLeaveBalance($leaveRequest->employee_id, $year);

        return Inertia::render('leaves/Show', [
            'leaveRequest' => $leaveRequest,
            'leaveBalance' => $leaveBalance,
        ]);
    }

    /**
     * Aksi Persetujuan / Penolakan Pengajuan Cuti
     */
    public function approve(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'catatan_persetujuan' => 'nullable|string|max:500',
        ]);

        $leaveRequest = LeaveRequest::where('uuid', $uuid)->firstOrFail();

        $this->leaveService->processApproval(
            $leaveRequest,
            $request->action,
            $request->user() ? $request->user()->id : null,
            $request->catatan_persetujuan
        );

        $actionText = $request->action === 'approve' ? 'disetujui' : 'ditolak';

        return redirect()->back()
            ->with('success', "Pengajuan cuti berhasil {$actionText}.");
    }

    /**
     * Default Leave Types Seeder Helper
     */
    protected function seedDefaultLeaveTypes(): void
    {
        $defaults = [
            ['nama_tipe' => 'Cuti Tahunan', 'kategori' => 'Cuti', 'kuota_hari_pertahun' => 12, 'memerlukan_lampiran' => false, 'potong_cuti_tahunan' => true],
            ['nama_tipe' => 'Izin Sakit (Surat Dokter)', 'kategori' => 'Sakit', 'kuota_hari_pertahun' => 14, 'memerlukan_lampiran' => true, 'potong_cuti_tahunan' => false],
            ['nama_tipe' => 'Izin Keperluan Mendesak', 'kategori' => 'Izin', 'kuota_hari_pertahun' => 5, 'memerlukan_lampiran' => false, 'potong_cuti_tahunan' => false],
            ['nama_tipe' => 'Cuti Melahirkan (Maternity)', 'kategori' => 'Cuti', 'kuota_hari_pertahun' => 90, 'memerlukan_lampiran' => true, 'potong_cuti_tahunan' => false],
            ['nama_tipe' => 'Tugas Dinas Luar Kota', 'kategori' => 'Dinas', 'kuota_hari_pertahun' => 30, 'memerlukan_lampiran' => true, 'potong_cuti_tahunan' => false],
        ];

        foreach ($defaults as $item) {
            LeaveType::firstOrCreate(['nama_tipe' => $item['nama_tipe']], $item);
        }
    }
}
