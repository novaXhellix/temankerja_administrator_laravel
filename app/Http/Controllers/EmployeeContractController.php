<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Services\EmployeeContractService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeContractController extends Controller
{
    protected EmployeeContractService $contractService;

    public function __construct(EmployeeContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function index(Request $request): Response
    {
        $jenis = $request->query('jenis_kontrak');
        $status = $request->query('status');
        $search = $request->query('search');

        $query = EmployeeContract::with(['employee.company', 'employee.region'])->latest();

        if ($jenis) {
            $query->where('jenis_kontrak', $jenis);
        }

        if ($status) {
            $query->where('status_kontrak', $status);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_induk_karyawan', 'like', "%{$search}%")
                  ->orWhere('nomor_kontrak', 'like', "%{$search}%");
            });
        }

        $contracts = $query->paginate(12)->withQueryString();
        $alerts = $this->contractService->getExpiringContracts(30);

        $stats = [
            'total_active' => EmployeeContract::where('status_kontrak', 'Aktif')->count(),
            'pkwtt_tetap' => EmployeeContract::where('jenis_kontrak', 'PKWTT')->where('status_kontrak', 'Aktif')->count(),
            'pkwt_kontrak' => EmployeeContract::where('jenis_kontrak', 'like', '%PKWT%')->where('status_kontrak', 'Aktif')->count(),
            'expiring_soon' => count($alerts['expiring_soon']),
        ];

        return Inertia::render('contracts/Index', [
            'contracts' => $contracts,
            'alerts' => $alerts,
            'stats' => $stats,
            'filters' => [
                'jenis_kontrak' => $jenis,
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $employeeId = $request->query('employee_id');
        $employees = Employee::with(['company', 'region'])->where('status_karyawan', 'Aktif')->orderBy('nama_lengkap')->get();

        return Inertia::render('contracts/Create', [
            'employees' => $employees,
            'selectedEmployeeId' => $employeeId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'nomor_kontrak' => 'nullable|string|max:100',
            'jenis_kontrak' => 'required|string|max:50',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'jabatan' => 'required|string|max:100',
            'divisi' => 'nullable|string|max:100',
            'gaji_pokok' => 'nullable|numeric|min:0',
            'catatan_khusus' => 'nullable|string|max:1000',
            'dokumen_kontrak' => 'nullable|file|mimes:pdf,docx,jpg,png|max:5120',
        ]);

        $this->contractService->createOrRenewContract($validated, $request->file('dokumen_kontrak'));

        return redirect()->route('contracts.index')
            ->with('success', 'Kontrak kerja karyawan berhasil diterbitkan / diperpanjang.');
    }

    public function show(string $uuid): Response
    {
        $contract = EmployeeContract::with(['employee.company', 'employee.region'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Riwayat kontrak karyawan yang sama
        $history = EmployeeContract::where('employee_id', $contract->employee_id)
            ->latest('tanggal_mulai')
            ->get();

        return Inertia::render('contracts/Show', [
            'contract' => $contract,
            'history' => $history,
        ]);
    }
}
