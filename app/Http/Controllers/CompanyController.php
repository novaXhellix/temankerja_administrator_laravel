<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies.
     */
    public function index(Request $request): Response
    {
        $query = Company::query()
            ->with([
                'regions' => function ($q) {
                    $q->select(['id', 'company_id', 'nama_wilayah', 'kode_wilayah'])->withCount('subRegions');
                },
            ])
            ->withCount(['employees', 'departments', 'regions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_perusahaan', 'like', "%{$search}%")
                    ->orWhere('kode_perusahaan', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('telepon', 'like', "%{$search}%")
                    ->orWhereHas('regions', function ($rq) use ($search) {
                        $rq->where('nama_wilayah', 'like', "%{$search}%")
                            ->orWhere('kode_wilayah', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $companies = $query->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('companies/Index', [
            'companies' => $companies,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create(): Response
    {
        return Inertia::render('companies/Create');
    }

    /**
     * Store a newly created company in storage with logo upload support.
     */
    public function store(Request $request): RedirectResponse
    {
        if (isset($_FILES['logo']['error']) && $_FILES['logo']['error'] !== UPLOAD_ERR_OK && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorCode = $_FILES['logo']['error'];
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload PHP tidak ditemukan (upload_tmp_dir).',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk penyimpanan.',
                UPLOAD_ERR_EXTENSION => 'Upload file dihentikan oleh ekstensi PHP.',
            ];
            $errorMsg = $messages[$errorCode] ?? 'Gagal mengunggah logo (Kode Error PHP: ' . $errorCode . ').';
            throw \Illuminate\Validation\ValidationException::withMessages([
                'logo' => $errorMsg,
            ]);
        }

        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'kode_perusahaan' => 'required|string|max:255|unique:companies,kode_perusahaan',
            'logo' => 'nullable|image|max:5120',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Non-Aktif',
            'cutoff_start_day' => 'nullable|integer|min:1|max:31',
        ], [
            'logo.image' => 'File logo harus berupa gambar yang valid (JPG, PNG, WEBP, SVG, GIF).',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 5 MB.',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Extract primary & secondary colors from uploaded logo
            $palette = (new \App\Services\ColorExtractorService())->extractPalette($file->getRealPath());
            $validated['primary_color'] = $palette['primary'];
            $validated['secondary_color'] = $palette['secondary'];

            $path = $file->storeAs('companies/logos', $filename, config('filesystems.default'));
            $validated['logo_path'] = $path;
        }

        unset($validated['logo']);

        Company::create($validated);

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil ditambahkan.');
    }

    /**
     * Display the specified company details with its branches (regions) and sub-regions.
     */
    public function show(Company $company): Response
    {
        $company->load([
            'regions' => function ($q) {
                $q->with([
                    'subRegions' => function ($sq) {
                        $sq->withCount('employees')->orderBy('nama_sub_wilayah');
                    },
                ])->withCount('employees')->latest();
            },
            'departments' => function ($q) {
                $q->withCount('employees')->latest();
            },
        ])->loadCount(['employees', 'departments', 'regions']);

        return Inertia::render('companies/Show', [
            'company' => $company,
        ]);
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company): Response
    {
        return Inertia::render('companies/Edit', [
            'company' => $company,
        ]);
    }

    /**
     * Update the specified company in storage with logo replacement.
     */
    public function update(Request $request, Company $company): RedirectResponse
    {
        if (isset($_FILES['logo']['error']) && $_FILES['logo']['error'] !== UPLOAD_ERR_OK && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorCode = $_FILES['logo']['error'];
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas upload server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
                UPLOAD_ERR_PARTIAL => 'File hanya terunggah sebagian.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary upload PHP tidak ditemukan (upload_tmp_dir).',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk penyimpanan.',
                UPLOAD_ERR_EXTENSION => 'Upload file dihentikan oleh ekstensi PHP.',
            ];
            $errorMsg = $messages[$errorCode] ?? 'Gagal mengunggah logo (Kode Error PHP: ' . $errorCode . ').';
            throw \Illuminate\Validation\ValidationException::withMessages([
                'logo' => $errorMsg,
            ]);
        }

        $validated = $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'kode_perusahaan' => "required|string|max:255|unique:companies,kode_perusahaan,{$company->id}",
            'logo' => 'nullable|image|max:5120',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'website' => 'nullable|string|max:255',
            'status' => 'required|string|in:Aktif,Non-Aktif',
            'cutoff_start_day' => 'nullable|integer|min:1|max:31',
        ], [
            'logo.image' => 'File logo harus berupa gambar yang valid (JPG, PNG, WEBP, SVG, GIF).',
            'logo.max' => 'Ukuran logo tidak boleh lebih dari 5 MB.',
        ]);

        if ($request->hasFile('logo')) {
            if (! empty($company->logo_path)) {
                try {
                    Storage::disk(config('filesystems.default'))->delete($company->logo_path);
                } catch (\Throwable) {}
                try {
                    if (Storage::disk('public')->exists($company->logo_path)) {
                        Storage::disk('public')->delete($company->logo_path);
                    }
                } catch (\Throwable) {}
            }
            $file = $request->file('logo');
            $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Extract primary & secondary colors from uploaded logo
            $palette = (new \App\Services\ColorExtractorService())->extractPalette($file->getRealPath());
            $validated['primary_color'] = $palette['primary'];
            $validated['secondary_color'] = $palette['secondary'];

            $path = $file->storeAs('companies/logos', $filename, config('filesystems.default'));
            $validated['logo_path'] = $path;
        }

        unset($validated['logo']);

        $company->update($validated);

        return redirect()->route('companies.show', $company->slug)->with('success', 'Data perusahaan berhasil diperbarui.');
    }

    /**
     * Remove the specified company from storage and delete logo file.
     */
    public function destroy(Company $company): RedirectResponse
    {
        if (! empty($company->logo_path)) {
            try {
                Storage::disk(config('filesystems.default'))->delete($company->logo_path);
            } catch (\Throwable) {}
            try {
                if (Storage::disk('public')->exists($company->logo_path)) {
                    Storage::disk('public')->delete($company->logo_path);
                }
            } catch (\Throwable) {}
        }

        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dihapus.');
    }
}
