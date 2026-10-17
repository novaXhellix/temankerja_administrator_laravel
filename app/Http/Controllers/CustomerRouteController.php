<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRouteController extends Controller
{
    /**
     * Display a listing of customer routes.
     */
    public function index(Request $request): Response
    {
        $query = CustomerRoute::query()->with(['customer', 'company']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('tempat_muat', 'like', "%{$search}%")
                    ->orWhere('tempat_bongkar', 'like', "%{$search}%")
                    ->orWhere('kargo', 'like', "%{$search}%")
                    ->orWhere('nama_rute', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('nama_customer', 'like', "%{$search}%")
                            ->orWhere('kode_customer', 'like', "%{$search}%");
                    });
            });
        }

        if ($customerId = $request->input('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $routes = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $customers = Customer::select(['id', 'nama_customer', 'kode_customer'])
            ->orderBy('nama_customer')
            ->get();

        $stats = [
            'total_routes' => CustomerRoute::count(),
            'total_km' => round(CustomerRoute::sum('km'), 1),
            'total_active' => CustomerRoute::where('status', 'Aktif')->count(),
            'total_customers_served' => CustomerRoute::distinct('customer_id')->count('customer_id'),
        ];

        return Inertia::render('customer-routes/Index', [
            'routes' => $routes,
            'filters' => $request->only(['search', 'customer_id', 'status']),
            'customers' => $customers,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new customer route.
     */
    public function create(Request $request): Response
    {
        $customers = Customer::select(['id', 'nama_customer', 'kode_customer'])
            ->orderBy('nama_customer')
            ->get();

        $companies = Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])
            ->orderBy('nama_perusahaan')
            ->get();

        $selectedCustomerId = $request->input('customer_id');

        return Inertia::render('customer-routes/Create', [
            'customers' => $customers,
            'companies' => $companies,
            'selectedCustomerId' => $selectedCustomerId ? (int) $selectedCustomerId : null,
        ]);
    }

    /**
     * Store a newly created customer route in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'company_id' => 'nullable|exists:companies,id',
            'nama_rute' => 'nullable|string|max:255',
            'tempat_muat' => 'required|string|max:255',
            'tempat_bongkar' => 'required|string|max:255',
            'kargo' => 'required|string|max:255',
            'km' => 'required|numeric|min:0.1',
            'tarif_standar' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $route = CustomerRoute::create($validated);

        if ($request->input('redirect_to_customer') && $route->customer) {
            return redirect()->route('customers.show', $route->customer->slug)
                ->with('success', "Rute {$route->tempat_muat} → {$route->tempat_bongkar} berhasil ditambahkan ke customer.");
        }

        return redirect()->route('customer-routes.index')
            ->with('success', "Rute {$route->tempat_muat} → {$route->tempat_bongkar} ({$route->km} KM) berhasil ditambahkan.");
    }

    /**
     * Display the specified customer route.
     */
    public function show(CustomerRoute $customerRoute): Response
    {
        $customerRoute->load(['customer', 'company']);

        // Estimated transit duration at 50 km/h average truck speed
        $estHours = $customerRoute->km > 0 ? round($customerRoute->km / 50, 1) : 0;

        return Inertia::render('customer-routes/Show', [
            'route' => array_merge($customerRoute->toArray(), [
                'customer_nama' => $customerRoute->customer?->nama_customer,
                'customer_kode' => $customerRoute->customer?->kode_customer,
                'customer_slug' => $customerRoute->customer?->slug,
                'customer_telepon' => $customerRoute->customer?->telepon,
                'customer_alamat' => $customerRoute->customer?->alamat,
                'company_nama' => $customerRoute->company?->nama_perusahaan,
                'estimasi_jam' => $estHours,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified customer route.
     */
    public function edit(CustomerRoute $customerRoute): Response
    {
        $customerRoute->load(['customer', 'company']);

        $customers = Customer::select(['id', 'nama_customer', 'kode_customer'])
            ->orderBy('nama_customer')
            ->get();

        $companies = Company::select(['id', 'nama_perusahaan', 'kode_perusahaan'])
            ->orderBy('nama_perusahaan')
            ->get();

        return Inertia::render('customer-routes/Edit', [
            'route' => $customerRoute,
            'customers' => $customers,
            'companies' => $companies,
        ]);
    }

    /**
     * Update the specified customer route in storage.
     */
    public function update(Request $request, CustomerRoute $customerRoute): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'company_id' => 'nullable|exists:companies,id',
            'nama_rute' => 'nullable|string|max:255',
            'tempat_muat' => 'required|string|max:255',
            'tempat_bongkar' => 'required|string|max:255',
            'kargo' => 'required|string|max:255',
            'km' => 'required|numeric|min:0.1',
            'tarif_standar' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'status' => 'required|string|in:Aktif,Non-Aktif',
        ]);

        $customerRoute->update($validated);

        return redirect()->route('customer-routes.show', $customerRoute->slug)
            ->with('success', "Rute {$customerRoute->tempat_muat} → {$customerRoute->tempat_bongkar} berhasil diperbarui.");
    }

    /**
     * Remove the specified customer route from storage.
     */
    public function destroy(CustomerRoute $customerRoute): RedirectResponse
    {
        $info = "{$customerRoute->tempat_muat} → {$customerRoute->tempat_bongkar}";
        $customerRoute->delete();

        return redirect()->route('customer-routes.index')
            ->with('success', "Rute {$info} berhasil dihapus.");
    }
}
