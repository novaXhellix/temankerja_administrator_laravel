<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): Response
    {
        $query = Department::query()->with(['company'])->withCount('employees');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_departemen', 'like', "%{$search}%")
                    ->orWhere('kode_departemen', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        $departments = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $companies = Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();

        return Inertia::render('departments/Index', [
            'departments' => $departments,
            'filters' => $request->only(['search', 'status', 'company_id']),
            'companies' => $companies,
        ]);
    }

    /**
     * Show the form for creating a new department.
     */
    public function create(Request $request): Response
    {
        $companies = Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();
        $selectedCompanyId = $request->input('company_id');

        return Inertia::render('departments/Create', [
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId ? (int) $selectedCompanyId : null,
        ]);
    }

    /**
     * Store a newly created department in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'nama_departemen' => 'required|string|max:255',
            'kode_departemen' => 'required|string|max:255|unique:departments,kode_departemen',
            'deskripsi' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $department = Department::create($validated);

        if ($department->company_id) {
            $company = Company::find($department->company_id);
            if ($company && $request->input('from_company')) {
                return redirect()->route('companies.show', $company->slug)->with('success', "Departemen {$department->nama_departemen} berhasil ditambahkan ke perusahaan.");
            }
        }

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan.');
    }

    /**
     * Display the specified department details and connected employees.
     */
    public function show(Department $department): Response
    {
        $department->load(['company', 'employees' => function ($q) {
            $q->with(['region', 'user'])->latest();
        }]);

        return Inertia::render('departments/Show', [
            'department' => array_merge($department->toArray(), [
                'total_karyawan' => $department->employees()->count(),
                'active_karyawan' => $department->employees()->where('status_karyawan', 'Aktif')->count(),
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified department.
     */
    public function edit(Department $department): Response
    {
        $companies = Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();

        return Inertia::render('departments/Edit', [
            'department' => $department->load('company'),
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified department in storage.
     */
    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'nama_departemen' => 'required|string|max:255',
            'kode_departemen' => "required|string|max:255|unique:departments,kode_departemen,{$department->id}",
            'deskripsi' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $department->update($validated);

        if ($redirectCompany = $request->input('redirect_company')) {
            return redirect()->route('companies.show', $redirectCompany)->with('success', 'Data departemen berhasil diperbarui.');
        }

        return redirect()->route('departments.show', $department->slug)->with('success', 'Data departemen berhasil diperbarui.');
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $companySlug = $department->company?->slug;
        $department->delete();

        if ($companySlug && $request->input('from_company')) {
            return redirect()->route('companies.show', $companySlug)->with('success', 'Departemen berhasil dihapus dari perusahaan.');
        }

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus.');
    }
}
