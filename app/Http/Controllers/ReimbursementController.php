<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementType;
use App\Services\ReimbursementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReimbursementController extends Controller
{
    protected ReimbursementService $reimbursementService;

    public function __construct(ReimbursementService $reimbursementService)
    {
        $this->reimbursementService = $reimbursementService;
    }

    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $companyId = $request->query('company_id');
        $search = $request->query('search');

        $query = Reimbursement::with(['employee.company', 'reimbursementType', 'approver'])
            ->latest();

        if ($status && in_array($status, ['Pending', 'Approved', 'Rejected', 'Paid'])) {
            $query->where('status', $status);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                  ->orWhere('nomor_induk_karyawan', 'like', "%{$search}%")
                  ->orWhere('nomor_klaim', 'like', "%{$search}%");
            });
        }

        $reimbursements = $query->paginate(12)->withQueryString();

        $stats = [
            'total_nominal' => (float) Reimbursement::where('status', '!=', 'Rejected')->sum('nominal'),
            'pending' => Reimbursement::where('status', 'Pending')->count(),
            'approved' => Reimbursement::where('status', 'Approved')->count(),
            'paid' => Reimbursement::where('status', 'Paid')->count(),
        ];

        $companies = Company::orderBy('nama_perusahaan')->get(['id', 'nama_perusahaan']);
        $types = ReimbursementType::where('status', 'Aktif')->get();

        return Inertia::render('reimbursements/Index', [
            'reimbursements' => $reimbursements,
            'stats' => $stats,
            'companies' => $companies,
            'types' => $types,
            'filters' => [
                'status' => $status,
                'company_id' => $companyId,
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        if (ReimbursementType::count() === 0) {
            $this->seedDefaultTypes();
        }

        $employees = Employee::with(['company'])
            ->where('status_karyawan', 'Aktif')
            ->orderBy('nama_lengkap')
            ->get();

        $types = ReimbursementType::where('status', 'Aktif')->get();

        return Inertia::render('reimbursements/Create', [
            'employees' => $employees,
            'types' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'reimbursement_type_id' => 'required|exists:reimbursement_types,id',
            'tanggal_klaim' => 'required|date',
            'nominal' => 'required|numeric|min:1000',
            'keterangan' => 'required|string|max:1000',
            'bukti_nota' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $this->reimbursementService->createReimbursement($validated, $request->file('bukti_nota'));

        return redirect()->route('reimbursements.index')
            ->with('success', 'Pengajuan reimbursement berhasil dibuat.');
    }

    public function show(string $uuid): Response
    {
        $reimbursement = Reimbursement::with(['employee.company', 'reimbursementType', 'approver'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return Inertia::render('reimbursements/Show', [
            'reimbursement' => $reimbursement,
        ]);
    }

    public function action(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected,Paid',
            'catatan_approval' => 'nullable|string|max:500',
            'metode_pembayaran' => 'nullable|string|max:100',
        ]);

        $reimbursement = Reimbursement::where('uuid', $uuid)->firstOrFail();

        $this->reimbursementService->processStatus(
            $reimbursement,
            $request->status,
            $request->user() ? $request->user()->id : null,
            $request->catatan_approval,
            $request->metode_pembayaran
        );

        return redirect()->back()->with('success', "Status klaim berhasil diubah menjadi {$request->status}.");
    }

    protected function seedDefaultTypes(): void
    {
        $defaults = [
            ['nama_tipe' => 'Biaya Medis & Rawat Jalan', 'kode_tipe' => 'MED', 'maksimal_klaim' => 2000000, 'memerlukan_struk' => true],
            ['nama_tipe' => 'Transportasi & Bensin', 'kode_tipe' => 'TRN', 'maksimal_klaim' => 500000, 'memerlukan_struk' => true],
            ['nama_tipe' => 'Makan Lembur', 'kode_tipe' => 'MLM', 'maksimal_klaim' => 100000, 'memerlukan_struk' => true],
            ['nama_tipe' => 'Perjalanan Dinas & Hotel', 'kode_tipe' => 'DNS', 'maksimal_klaim' => 5000000, 'memerlukan_struk' => true],
            ['nama_tipe' => 'Internet & Komunikasi', 'kode_tipe' => 'INT', 'maksimal_klaim' => 300000, 'memerlukan_struk' => true],
        ];

        foreach ($defaults as $item) {
            ReimbursementType::firstOrCreate(['kode_tipe' => $item['kode_tipe']], $item);
        }
    }
}
