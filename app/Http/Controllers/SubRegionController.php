<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Region;
use App\Models\SubRegion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubRegionController extends Controller
{
    /**
     * Display a listing of sub regions (Cities & Regencies).
     */
    public function index(Request $request): Response
    {
        $query = SubRegion::query()->with(['region'])
            ->withCount(['companies', 'employees']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_sub_wilayah', 'like', "%{$search}%")
                    ->orWhere('kode_sub_wilayah', 'like', "%{$search}%")
                    ->orWhereHas('region', function ($rq) use ($search) {
                        $rq->where('nama_wilayah', 'like', "%{$search}%");
                    });
            });
        }

        if ($regionId = $request->input('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $subRegions = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $regions = Region::select(['id', 'nama_wilayah', 'kode_wilayah'])->orderBy('nama_wilayah')->get();

        return Inertia::render('sub-regions/Index', [
            'subRegions' => $subRegions,
            'regions' => $regions,
            'filters' => $request->only(['search', 'region_id', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new sub region.
     */
    public function create(Request $request): Response
    {
        $regions = Region::with('company')->select(['id', 'company_id', 'nama_wilayah', 'kode_wilayah'])->orderBy('nama_wilayah')->get();
        $selectedRegionId = $request->input('region_id');
        $selectedCompanyId = $request->input('company_id');

        return Inertia::render('sub-regions/Create', [
            'regions' => $regions,
            'selectedRegionId' => $selectedRegionId ? (int) $selectedRegionId : null,
            'selectedCompanyId' => $selectedCompanyId ? (int) $selectedCompanyId : null,
            'redirectCompany' => $request->input('redirect_company'),
            'redirectRegion' => $request->input('redirect_region'),
        ]);
    }

    /**
     * Store a newly created sub region in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'nama_sub_wilayah' => 'required|string|max:255',
            'kode_sub_wilayah' => 'nullable|string|max:255|unique:sub_regions,kode_sub_wilayah',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $region = Region::with('company')->findOrFail($validated['region_id']);
        $validated['company_id'] = $region->company_id;

        if (empty($validated['kode_sub_wilayah'])) {
            $validated['kode_sub_wilayah'] = \App\Services\SubRegionCodeGenerator::generateUnique($validated['nama_sub_wilayah']);
        } else {
            $validated['kode_sub_wilayah'] = strtoupper(trim($validated['kode_sub_wilayah']));
        }

        $subRegion = SubRegion::create($validated);

        if ($companySlug = $request->input('redirect_company')) {
            return redirect()->route('companies.show', $companySlug)->with('success', "Sub Wilayah {$subRegion->nama_sub_wilayah} berhasil ditambahkan ke Cabang {$region->nama_wilayah}.");
        }

        if ($redirectRegion = $request->input('redirect_region')) {
            return redirect()->route('regions.show', $redirectRegion)->with('success', "Sub Wilayah {$subRegion->nama_sub_wilayah} berhasil ditambahkan ke Cabang {$region->nama_wilayah}.");
        }

        if ($region->company?->slug) {
            return redirect()->route('companies.show', $region->company->slug)->with('success', "Sub Wilayah {$subRegion->nama_sub_wilayah} berhasil ditambahkan ke Cabang {$region->nama_wilayah}.");
        }

        $regionSlug = $region->slug;
        if ($regionSlug) {
            return redirect()->route('regions.show', $regionSlug)->with('success', "Sub Wilayah {$subRegion->nama_sub_wilayah} berhasil ditambahkan.");
        }

        return redirect()->route('regions.index')->with('success', "Sub Wilayah {$subRegion->nama_sub_wilayah} berhasil ditambahkan.");
    }

    /**
     * Display the specified sub region details and connected companies & employees.
     */
    public function show(SubRegion $subRegion): Response
    {
        $subRegion->load([
            'region.company',
            'company',
            'companies' => function ($q) {
                $q->latest()->take(10);
            },
            'employees' => function ($q) {
                $q->latest()->take(15);
            },
        ]);

        return Inertia::render('sub-regions/Show', [
            'subRegion' => array_merge($subRegion->toArray(), [
                'total_companies' => $subRegion->companies()->count(),
                'total_employees' => $subRegion->employees()->count(),
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified sub region.
     */
    public function edit(SubRegion $subRegion): Response
    {
        $regions = Region::with('company')->select(['id', 'company_id', 'nama_wilayah', 'kode_wilayah'])->orderBy('nama_wilayah')->get();

        return Inertia::render('sub-regions/Edit', [
            'subRegion' => $subRegion->load('region.company'),
            'regions' => $regions,
        ]);
    }

    /**
     * Update the specified sub region in storage.
     */
    public function update(Request $request, SubRegion $subRegion): RedirectResponse
    {
        $validated = $request->validate([
            'region_id' => 'required|exists:regions,id',
            'nama_sub_wilayah' => 'required|string|max:255',
            'kode_sub_wilayah' => "nullable|string|max:255|unique:sub_regions,kode_sub_wilayah,{$subRegion->id}",
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $region = Region::with('company')->findOrFail($validated['region_id']);
        $validated['company_id'] = $region->company_id;

        if (empty($validated['kode_sub_wilayah'])) {
            $validated['kode_sub_wilayah'] = \App\Services\SubRegionCodeGenerator::generateUnique($validated['nama_sub_wilayah'], $subRegion->id);
        } else {
            $validated['kode_sub_wilayah'] = strtoupper(trim($validated['kode_sub_wilayah']));
        }

        $oldName = $subRegion->nama_sub_wilayah;
        $subRegion->update($validated);

        // Jika nama berubah, update cache sub_wilayah di companies & employees terkait
        if ($oldName !== $subRegion->nama_sub_wilayah) {
            Company::where('sub_region_id', $subRegion->id)->update([
                'sub_wilayah' => $subRegion->nama_sub_wilayah,
            ]);
            Employee::where('sub_region_id', $subRegion->id)->update([
                'sub_wilayah' => $subRegion->nama_sub_wilayah,
            ]);
        }

        if ($companySlug = $request->input('redirect_company')) {
            return redirect()->route('companies.show', $companySlug)->with('success', 'Sub Wilayah berhasil diperbarui.');
        }

        if ($redirectRegion = $request->input('redirect_region')) {
            return redirect()->route('regions.show', $redirectRegion)->with('success', 'Sub Wilayah berhasil diperbarui.');
        }

        if ($region->company?->slug) {
            return redirect()->route('companies.show', $region->company->slug)->with('success', 'Sub Wilayah berhasil diperbarui.');
        }

        return redirect()->route('sub-regions.show', $subRegion->slug)->with('success', 'Sub Wilayah berhasil diperbarui.');
    }

    /**
     * Remove the specified sub region from storage.
     */
    public function destroy(Request $request, SubRegion $subRegion): RedirectResponse
    {
        $region = $subRegion->region()->with('company')->first();
        $name = $subRegion->nama_sub_wilayah;
        $subRegion->delete();

        if ($companySlug = $request->input('from_company')) {
            return redirect()->route('companies.show', $companySlug)->with('success', "Sub Wilayah {$name} berhasil dihapus.");
        }

        if ($region?->company?->slug) {
            return redirect()->route('companies.show', $region->company->slug)->with('success', "Sub Wilayah {$name} berhasil dihapus.");
        }

        if ($region) {
            return redirect()->route('regions.show', $region->slug)->with('success', "Sub Wilayah {$name} berhasil dihapus.");
        }

        return redirect()->route('sub-regions.index')->with('success', "Sub Wilayah {$name} berhasil dihapus.");
    }
}
