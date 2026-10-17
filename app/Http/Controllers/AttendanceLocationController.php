<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLocation;
use App\Models\Company;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceLocationController extends Controller
{
    /**
     * Display a listing of attendance geofencing locations.
     */
    public function index(Request $request): Response
    {
        $query = AttendanceLocation::with(['company', 'region']);

        if ($search = $request->input('search')) {
            $query->where('nama_lokasi', 'like', "%{$search}%")
                ->orWhere('alamat_lengkap', 'like', "%{$search}%");
        }

        if ($companyId = $request->input('company_id')) {
            $query->where('company_id', $companyId);
        }

        $locations = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('attendances/locations/Index', [
            'locations' => $locations,
            'companies' => Company::select('id', 'nama_perusahaan')->get(),
            'filters' => $request->only(['search', 'company_id']),
        ]);
    }

    /**
     * Show the form for creating a new geofencing location.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('attendances/locations/Create', [
            'companies' => Company::select('id', 'nama_perusahaan')->get(),
            'regions' => Region::select('id', 'company_id', 'nama_wilayah')->get(),
            'defaultCompanyId' => $request->input('company_id'),
        ]);
    }

    /**
     * Store a newly created geofencing location in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'region_id' => 'nullable|exists:regions,id',
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meter' => 'required|integer|min:10|max:10000',
            'alamat_lengkap' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
            'check_type' => 'nullable|string|in:radius,polygon',
            'polygon_coordinates' => 'nullable|array',
        ]);

        AttendanceLocation::create($validated);

        return redirect()->route('attendance-locations.index')->with('success', 'Lokasi Geofencing Presensi berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified geofencing location.
     */
    public function edit(AttendanceLocation $attendanceLocation): Response
    {
        return Inertia::render('attendances/locations/Edit', [
            'location' => $attendanceLocation->load(['company', 'region']),
            'companies' => Company::select('id', 'nama_perusahaan')->get(),
            'regions' => Region::select('id', 'company_id', 'nama_wilayah')->get(),
        ]);
    }

    /**
     * Update the specified geofencing location in storage.
     */
    public function update(Request $request, AttendanceLocation $attendanceLocation): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'region_id' => 'nullable|exists:regions,id',
            'nama_lokasi' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meter' => 'required|integer|min:10|max:10000',
            'alamat_lengkap' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
            'check_type' => 'nullable|string|in:radius,polygon',
            'polygon_coordinates' => 'nullable|array',
        ]);

        $attendanceLocation->update($validated);

        return redirect()->route('attendance-locations.index')->with('success', 'Lokasi Geofencing Presensi berhasil diperbarui.');
    }

    /**
     * Remove the specified geofencing location from storage.
     */
    public function destroy(AttendanceLocation $attendanceLocation): RedirectResponse
    {
        $attendanceLocation->delete();

        return redirect()->route('attendance-locations.index')->with('success', 'Lokasi Geofencing berhasil dihapus.');
    }
}
