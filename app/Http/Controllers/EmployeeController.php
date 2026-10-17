<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response
    {
        $query = Employee::query()->with(['company', 'region', 'department', 'user.manager']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('wilayah', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status_karyawan', $status);
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($deptId = $request->input('department_id')) {
            $query->where('department_id', $deptId);
        }

        if ($wilayah = $request->input('wilayah')) {
            $query->where('wilayah', $wilayah);
        }

        $employees = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Employee $emp) => [
                'id' => $emp->id,
                'uuid' => $emp->uuid,
                'slug' => $emp->slug,
                'user_id' => $emp->user_id,
                'foto' => $emp->foto,
                'foto_url' => $emp->foto_url,
                'company_id' => $emp->company_id,
                'company_name' => $emp->company?->nama_perusahaan ?: $emp->user?->company_name,
                'company_slug' => $emp->company?->slug,
                'department_id' => $emp->department_id,
                'department_name' => $emp->department?->nama_departemen,
                'department_slug' => $emp->department?->slug,
                'user_email' => $emp->user?->email,
                'user_role' => $emp->user?->role?->label(),
                'user_role_key' => $emp->user?->role?->value,
                'manager_name' => $emp->user?->manager?->name,
                'wilayah' => $emp->region?->nama_wilayah ?: $emp->wilayah,
                'nama_karyawan' => $emp->nama_karyawan,
                'nik' => $emp->nik,
                'nip' => $emp->nip,
                'status_kontrak' => $emp->status_kontrak,
                'status_karyawan' => $emp->status_karyawan,
                'jabatan' => $emp->jabatan,
                'lama_kontrak' => $emp->lama_kontrak,
                'tgl_lahir' => $emp->tgl_lahir ? $emp->tgl_lahir->format('Y-m-d') : null,
                'usia' => $emp->usia,
                'mulai_bekerja' => $emp->mulai_bekerja ? $emp->mulai_bekerja->format('Y-m-d') : null,
                'lama_bekerja' => $emp->lama_bekerja,
            ]);

        $companies = \App\Models\Company::select(['id', 'nama_perusahaan'])->orderBy('nama_perusahaan')->get();
        $regions = \App\Models\Region::select(['id', 'nama_wilayah', 'company_id'])->where('status', 'Aktif')->orderBy('nama_wilayah')->get();
        $subRegions = \App\Models\SubRegion::select(['id', 'region_id', 'nama_sub_wilayah', 'kode_sub_wilayah', 'tipe'])->where('status', 'Aktif')->orderBy('nama_sub_wilayah')->get();
        $departments = \App\Models\Department::select(['id', 'nama_departemen', 'kode_departemen', 'company_id'])->where('status', 'Aktif')->orderBy('nama_departemen')->get();
        $wilayahList = Employee::distinct()->pluck('wilayah')->filter()->values();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'status', 'wilayah', 'company_id', 'department_id']),
            'companies' => $companies,
            'regions' => $regions,
            'subRegions' => $subRegions,
            'departments' => $departments,
            'wilayahList' => $wilayahList,
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create(): Response
    {
        $companies = \App\Models\Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();
        $regions = \App\Models\Region::select(['id', 'nama_wilayah', 'kode_wilayah', 'company_id'])->where('status', 'Aktif')->orderBy('nama_wilayah')->get();
        $subRegions = \App\Models\SubRegion::select(['id', 'region_id', 'nama_sub_wilayah', 'kode_sub_wilayah', 'tipe'])->where('status', 'Aktif')->orderBy('nama_sub_wilayah')->get();
        $departments = \App\Models\Department::select(['id', 'nama_departemen', 'kode_departemen', 'company_id'])->where('status', 'Aktif')->orderBy('nama_departemen')->get();

        $potentialManagers = \App\Models\User::select(['id', 'name', 'email', 'role', 'company_name'])
            ->orderBy('name')
            ->get()
            ->map(fn (\App\Models\User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_key' => $u->role?->value,
                'role_label' => $u->role?->label(),
                'level' => $u->role?->level() ?? 1,
                'company_name' => $u->company_name,
            ]);

        return Inertia::render('employees/Create', [
            'companies' => $companies,
            'regions' => $regions,
            'subRegions' => $subRegions,
            'departments' => $departments,
            'potentialManagers' => $potentialManagers,
        ]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'region_id' => 'nullable|exists:regions,id',
            'sub_region_id' => 'nullable|exists:sub_regions,id',
            'user_role' => 'nullable|string|in:staf_operasional,first_level,middle_level,top_level',
            'manager_id' => 'nullable|exists:users,id',
            'wilayah' => 'nullable|string|max:255',
            'sub_wilayah' => 'nullable|string|max:255',
            'nama_karyawan' => 'required|string|max:255',
            'nik' => 'required|string|max:255|unique:employees,nik',
            'nip' => 'required|string|max:255|unique:employees,nip',
            'status_kontrak' => 'required|string|max:255',
            'status_karyawan' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'lama_kontrak' => 'nullable|string|max:255',
            'periode_kontrak_mulai' => 'nullable|date',
            'periode_kontrak_selesai' => 'nullable|date|after_or_equal:periode_kontrak_mulai',
            'tgl_lahir' => 'required|date',
            'mulai_bekerja' => 'required|date',
            'bpjs_kes' => 'nullable|string|max:255',
            'bpjs_tk' => 'nullable|string|max:255',
            'tgl_resign' => 'nullable|date|after_or_equal:mulai_bekerja',
            'alasan_resign' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('employees/photos', config('filesystems.default'));
        }

        if (! empty($validated['region_id'])) {
            $region = \App\Models\Region::find($validated['region_id']);
            if ($region) {
                $validated['wilayah'] = $region->nama_wilayah;
                if (empty($validated['company_id']) && $region->company_id) {
                    $validated['company_id'] = $region->company_id;
                }
            }
        }

        if (! empty($validated['sub_region_id'])) {
            $sub = \App\Models\SubRegion::with('region')->find($validated['sub_region_id']);
            if ($sub) {
                $validated['sub_wilayah'] = $sub->nama_sub_wilayah;
                if (empty($validated['region_id'])) {
                    $validated['region_id'] = $sub->region_id;
                    $validated['wilayah'] = $sub->region?->nama_wilayah;
                }
            }
        }

        if (empty($validated['wilayah'])) {
            $validated['wilayah'] = 'Pusat';
        }

        if (($validated['status_karyawan'] ?? '') !== 'Resign') {
            $validated['tgl_resign'] = null;
            $validated['alasan_resign'] = null;
        }

        if (($validated['status_kontrak'] ?? '') === 'PKWTT') {
            $validated['lama_kontrak'] = 'Tetap';
            $validated['periode_kontrak_mulai'] = null;
            $validated['periode_kontrak_selesai'] = null;
        }

        $userRole = $validated['user_role'] ?? null;
        $managerId = $userRole === 'top_level' ? null : ($validated['manager_id'] ?? null);

        if (empty($validated['jabatan'])) {
            if ($userRole) {
                $roleEnum = \App\Enums\UserRole::tryFrom($userRole);
                $validated['jabatan'] = $roleEnum ? $roleEnum->label() : ucfirst(str_replace('_', ' ', $userRole));
            } else {
                $validated['jabatan'] = 'Karyawan';
            }
        }

        unset($validated['user_role'], $validated['manager_id']);

        $employee = Employee::create($validated);

        if ($employee->user) {
            $userPayload = [];
            if ($userRole) {
                $userPayload['role'] = $userRole;
            }
            $userPayload['manager_id'] = $managerId;
            $employee->user->update($userPayload);
        }

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    /**
     * Display the specified employee details.
     */
    public function show(Employee $employee): Response
    {
        $employee->load(['company', 'region', 'subRegion', 'department', 'user.manager']);

        return Inertia::render('employees/Show', [
            'employee' => array_merge($employee->toArray(), [
                'user_id' => $employee->user?->id,
                'user_email' => $employee->user?->email,
                'user_role' => $employee->user?->role?->label(),
                'user_role_key' => $employee->user?->role?->value,
                'is_mfa_bound' => $employee->user ? $employee->user->isMfaBound() : false,
                'has_two_factor' => $employee->user ? ! empty($employee->user->two_factor_secret) : false,
                'mfa_confirmed_at' => $employee->user?->two_factor_confirmed_at?->format('d M Y H:i:s'),
                'manager_id' => $employee->user?->manager_id,
                'manager_name' => $employee->user?->manager?->name,
                'company_name' => $employee->company?->nama_perusahaan ?: $employee->user?->company_name,
                'company_slug' => $employee->company?->slug,
                'region_name' => $employee->region?->nama_wilayah ?: $employee->wilayah,
                'region_slug' => $employee->region?->slug,
                'sub_region_name' => $employee->subRegion?->nama_sub_wilayah ?: $employee->sub_wilayah,
                'sub_region_slug' => $employee->subRegion?->slug,
                'department_id' => $employee->department_id,
                'department_name' => $employee->department?->nama_departemen,
                'department_slug' => $employee->department?->slug,
                'department_kode' => $employee->department?->kode_departemen,
                'usia' => $employee->usia,
                'lama_bekerja' => $employee->lama_bekerja,
                'periode_kontrak_mulai' => $employee->periode_kontrak_mulai ? $employee->periode_kontrak_mulai->format('Y-m-d') : null,
                'periode_kontrak_selesai' => $employee->periode_kontrak_selesai ? $employee->periode_kontrak_selesai->format('Y-m-d') : null,
                'tgl_lahir' => $employee->tgl_lahir ? $employee->tgl_lahir->format('Y-m-d') : null,
                'mulai_bekerja' => $employee->mulai_bekerja ? $employee->mulai_bekerja->format('Y-m-d') : null,
                'tgl_resign' => $employee->tgl_resign ? $employee->tgl_resign->format('Y-m-d') : null,
            ]),
            'canManageMfa' => auth()->user()?->canManageMfa() ?? false,
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee): Response
    {
        $employee->load(['user.manager', 'department']);
        $companies = \App\Models\Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();
        $regions = \App\Models\Region::select(['id', 'nama_wilayah', 'kode_wilayah', 'company_id'])->where('status', 'Aktif')->orderBy('nama_wilayah')->get();
        $departments = \App\Models\Department::select(['id', 'nama_departemen', 'kode_departemen', 'company_id'])->where('status', 'Aktif')->orderBy('nama_departemen')->get();

        $potentialManagers = \App\Models\User::select(['id', 'name', 'email', 'role', 'company_name'])
            ->where('id', '!=', $employee->user_id)
            ->orderBy('name')
            ->get()
            ->map(fn (\App\Models\User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role_key' => $u->role?->value,
                'role_label' => $u->role?->label(),
                'level' => $u->role?->level() ?? 1,
                'company_name' => $u->company_name,
            ]);

        return Inertia::render('employees/Edit', [
            'employee' => array_merge($employee->toArray(), [
                'user_role_key' => $employee->user?->role?->value ?: 'staf_operasional',
                'manager_id' => $employee->user?->manager_id,
                'department_id' => $employee->department_id,
                'department_name' => $employee->department?->nama_departemen,
                'periode_kontrak_mulai' => $employee->periode_kontrak_mulai ? $employee->periode_kontrak_mulai->format('Y-m-d') : null,
                'periode_kontrak_selesai' => $employee->periode_kontrak_selesai ? $employee->periode_kontrak_selesai->format('Y-m-d') : null,
                'tgl_lahir' => $employee->tgl_lahir ? $employee->tgl_lahir->format('Y-m-d') : null,
                'mulai_bekerja' => $employee->mulai_bekerja ? $employee->mulai_bekerja->format('Y-m-d') : null,
                'tgl_resign' => $employee->tgl_resign ? $employee->tgl_resign->format('Y-m-d') : null,
            ]),
            'companies' => $companies,
            'regions' => $regions,
            'subRegions' => $subRegions,
            'departments' => $departments,
            'potentialManagers' => $potentialManagers,
        ]);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'department_id' => 'nullable|exists:departments,id',
            'region_id' => 'nullable|exists:regions,id',
            'sub_region_id' => 'nullable|exists:sub_regions,id',
            'user_role' => 'nullable|string|in:staf_operasional,first_level,middle_level,top_level',
            'manager_id' => 'nullable|exists:users,id',
            'wilayah' => 'nullable|string|max:255',
            'sub_wilayah' => 'nullable|string|max:255',
            'nama_karyawan' => 'required|string|max:255',
            'nik' => "required|string|max:255|unique:employees,nik,{$employee->id}",
            'nip' => "required|string|max:255|unique:employees,nip,{$employee->id}",
            'status_kontrak' => 'required|string|max:255',
            'status_karyawan' => 'required|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'lama_kontrak' => 'nullable|string|max:255',
            'periode_kontrak_mulai' => 'nullable|date',
            'periode_kontrak_selesai' => 'nullable|date|after_or_equal:periode_kontrak_mulai',
            'tgl_lahir' => 'required|date',
            'mulai_bekerja' => 'required|date',
            'bpjs_kes' => 'nullable|string|max:255',
            'bpjs_tk' => 'nullable|string|max:255',
            'tgl_resign' => 'nullable|date|after_or_equal:mulai_bekerja',
            'alasan_resign' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('foto')) {
            if (! empty($employee->foto)) {
                try {
                    Storage::disk(config('filesystems.default'))->delete($employee->foto);
                } catch (\Throwable) {}
                try {
                    if (Storage::disk('public')->exists($employee->foto)) {
                        Storage::disk('public')->delete($employee->foto);
                    }
                } catch (\Throwable) {}
            }
            $validated['foto'] = $request->file('foto')->store('employees/photos', config('filesystems.default'));
        }

        if (! empty($validated['region_id'])) {
            $region = \App\Models\Region::find($validated['region_id']);
            if ($region) {
                $validated['wilayah'] = $region->nama_wilayah;
                if (empty($validated['company_id']) && $region->company_id) {
                    $validated['company_id'] = $region->company_id;
                }
            }
        }

        if (! empty($validated['sub_region_id'])) {
            $sub = \App\Models\SubRegion::with('region')->find($validated['sub_region_id']);
            if ($sub) {
                $validated['sub_wilayah'] = $sub->nama_sub_wilayah;
                if (empty($validated['region_id'])) {
                    $validated['region_id'] = $sub->region_id;
                    $validated['wilayah'] = $sub->region?->nama_wilayah;
                }
            }
        } else {
            $validated['sub_wilayah'] = null;
        }

        if (($validated['status_karyawan'] ?? '') !== 'Resign') {
            $validated['tgl_resign'] = null;
            $validated['alasan_resign'] = null;
        }

        if (($validated['status_kontrak'] ?? '') === 'PKWTT') {
            $validated['lama_kontrak'] = 'Tetap';
            $validated['periode_kontrak_mulai'] = null;
            $validated['periode_kontrak_selesai'] = null;
        }

        $userRole = $validated['user_role'] ?? null;
        $managerId = $userRole === 'top_level' ? null : ($validated['manager_id'] ?? null);

        if (empty($validated['jabatan'])) {
            if ($userRole) {
                $roleEnum = \App\Enums\UserRole::tryFrom($userRole);
                $validated['jabatan'] = $roleEnum ? $roleEnum->label() : ucfirst(str_replace('_', ' ', $userRole));
            } else {
                $validated['jabatan'] = $employee->jabatan ?: 'Karyawan';
            }
        }

        unset($validated['user_role'], $validated['manager_id']);

        $employee->update($validated);

        if ($employee->user) {
            $userPayload = [];
            if ($userRole) {
                $userPayload['role'] = $userRole;
            }
            $userPayload['manager_id'] = $managerId;
            $employee->user->update($userPayload);
        }

        return redirect()->route('employees.show', $employee->slug)->with('success', 'Data karyawan berhasil diperbarui.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
