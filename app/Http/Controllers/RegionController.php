<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegionController extends Controller
{
    /**
     * Display a listing of operational regions (Redirected to companies).
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('companies.index');
    }

    /**
     * Show the form for creating a new operational region.
     */
    public function create(Request $request): Response
    {
        $companies = \App\Models\Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();
        $selectedCompanyId = $request->input('company_id');

        return Inertia::render('regions/Create', [
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId ? (int) $selectedCompanyId : null,
        ]);
    }

    /**
     * Store a newly created operational region in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'nama_wilayah' => 'required|string|max:255',
            'kode_wilayah' => 'required|string|max:255|unique:regions,kode_wilayah',
            'alamat_kantor' => 'nullable|string',
            'telepon' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $region = Region::create($validated);

        if ($region->company_id) {
            $company = \App\Models\Company::find($region->company_id);
            if ($company) {
                return redirect()->route('companies.show', $company->slug)->with('success', "Wilayah {$region->nama_wilayah} berhasil ditambahkan ke perusahaan.");
            }
        }

        return redirect()->route('companies.index')->with('success', 'Wilayah operasional berhasil ditambahkan.');
    }

    /**
     * Display the specified operational region details and connected employees.
     */
    public function show(Region $region): Response
    {
        $region->load([
            'company',
            'subRegions' => function ($q) {
                $q->withCount(['companies', 'employees'])->latest();
            },
            'employees' => function ($q) {
                $q->latest()->take(20);
            },
        ]);

        return Inertia::render('regions/Show', [
            'region' => array_merge($region->toArray(), [
                'total_karyawan' => $region->employees()->count(),
                'total_sub_wilayah' => $region->subRegions()->count(),
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified operational region.
     */
    public function edit(Region $region): Response
    {
        $companies = \App\Models\Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])->orderBy('nama_perusahaan')->get();

        return Inertia::render('regions/Edit', [
            'region' => $region->load('company'),
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified operational region in storage.
     */
    public function update(Request $request, Region $region): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'nama_wilayah' => 'required|string|max:255',
            'kode_wilayah' => "required|string|max:255|unique:regions,kode_wilayah,{$region->id}",
            'alamat_kantor' => 'nullable|string',
            'telepon' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $oldName = $region->nama_wilayah;
        $region->update($validated);

        if ($oldName !== $region->nama_wilayah) {
            \App\Models\Company::where('region_id', $region->id)->update([
                'wilayah_operasional' => $region->nama_wilayah,
            ]);
            \App\Models\Employee::where('region_id', $region->id)->update([
                'wilayah' => $region->nama_wilayah,
            ]);
        }

        if ($redirectCompany = $request->input('redirect_company')) {
            return redirect()->route('companies.show', $redirectCompany)->with('success', 'Data wilayah operasional berhasil diperbarui.');
        }

        return redirect()->route('regions.show', $region->slug)->with('success', 'Data wilayah operasional berhasil diperbarui.');
    }

    /**
     * Remove the specified operational region from storage.
     */
    public function destroy(Request $request, Region $region): RedirectResponse
    {
        $companySlug = $region->company?->slug;
        $region->delete();

        if ($companySlug) {
            return redirect()->route('companies.show', $companySlug)->with('success', 'Wilayah operasional berhasil dihapus.');
        }

        return redirect()->route('companies.index')->with('success', 'Wilayah operasional berhasil dihapus.');
    }
}
