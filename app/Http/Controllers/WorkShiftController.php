<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkShiftController extends Controller
{
    /**
     * Display a listing of the work shifts.
     */
    public function index(Request $request): Response
    {
        $query = WorkShift::with('company')->withCount('employees');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_shift', 'like', "%{$search}%")
                  ->orWhere('kode_shift', 'like', "%{$search}%");
            });
        }

        $workShifts = $query->latest()->get();
        $companies = Company::select('id', 'nama_perusahaan', 'kode_perusahaan')->get();

        return Inertia::render('work-shifts/Index', [
            'workShifts' => $workShifts,
            'companies' => $companies,
            'filters' => $request->only(['company_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new work shift.
     */
    public function create(): Response
    {
        $companies = Company::select('id', 'nama_perusahaan', 'kode_perusahaan')->get();

        return Inertia::render('work-shifts/Create', [
            'companies' => $companies,
        ]);
    }

    /**
     * Store a newly created work shift in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('jam_masuk')) {
            $request->merge(['jam_masuk' => substr($request->jam_masuk, 0, 5)]);
        }
        if ($request->has('jam_keluar')) {
            $request->merge(['jam_keluar' => substr($request->jam_keluar, 0, 5)]);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'nama_shift' => ['required', 'string', 'max:255'],
            'kode_shift' => ['nullable', 'string', 'max:30'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'schedule_days' => ['nullable', 'array'],
            'toleransi_keterlambatan_menit' => ['required', 'integer', 'min:0', 'max:120'],
            'is_night_shift' => ['boolean'],
            'status' => ['required', 'in:Aktif,Non-Aktif'],
            'catatan' => ['nullable', 'string'],
        ]);

        // Hitung ringkasan hari kerja
        if (!empty($validated['schedule_days'])) {
            $activeDays = [];
            foreach ($validated['schedule_days'] as $day) {
                if (!empty($day['is_work'])) {
                    $activeDays[] = $day['day_name'] ?? $day['day'];
                }
            }
            $validated['work_days'] = !empty($activeDays) ? implode(', ', $activeDays) : 'Tidak ada hari kerja';
        }

        WorkShift::create($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'Jam Kerja / Shift berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified work shift.
     */
    public function edit(WorkShift $workShift): Response
    {
        $workShift->load('company');
        $companies = Company::select('id', 'nama_perusahaan', 'kode_perusahaan')->get();

        return Inertia::render('work-shifts/Edit', [
            'workShift' => $workShift,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified work shift in storage.
     */
    public function update(Request $request, WorkShift $workShift)
    {
        if ($request->has('jam_masuk')) {
            $request->merge(['jam_masuk' => substr($request->jam_masuk, 0, 5)]);
        }
        if ($request->has('jam_keluar')) {
            $request->merge(['jam_keluar' => substr($request->jam_keluar, 0, 5)]);
        }

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'nama_shift' => ['required', 'string', 'max:255'],
            'kode_shift' => ['nullable', 'string', 'max:30'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'schedule_days' => ['nullable', 'array'],
            'toleransi_keterlambatan_menit' => ['required', 'integer', 'min:0', 'max:120'],
            'is_night_shift' => ['boolean'],
            'status' => ['required', 'in:Aktif,Non-Aktif'],
            'catatan' => ['nullable', 'string'],
        ]);

        // Hitung ringkasan hari kerja
        if (!empty($validated['schedule_days'])) {
            $activeDays = [];
            foreach ($validated['schedule_days'] as $day) {
                if (!empty($day['is_work'])) {
                    $activeDays[] = $day['day_name'] ?? $day['day'];
                }
            }
            $validated['work_days'] = !empty($activeDays) ? implode(', ', $activeDays) : 'Tidak ada hari kerja';
        }

        $workShift->update($validated);

        return redirect()->route('work-shifts.index')
            ->with('success', 'Jam Kerja / Shift berhasil diperbarui.');
    }

    /**
     * Remove the specified work shift from storage.
     */
    public function destroy(WorkShift $workShift)
    {
        $workShift->delete();

        return redirect()->route('work-shifts.index')
            ->with('success', 'Jam Kerja / Shift berhasil dihapus.');
    }
}
