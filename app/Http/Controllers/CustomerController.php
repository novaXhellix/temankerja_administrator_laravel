<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): Response
    {
        $query = Customer::query()->withCount('routes');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_customer', 'like', "%{$search}%")
                    ->orWhere('kode_customer', 'like', "%{$search}%")
                    ->orWhere('pic_nama', 'like', "%{$search}%")
                    ->orWhere('telepon', 'like', "%{$search}%")
                    ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_customers' => Customer::count(),
            'total_routes' => CustomerRoute::count(),
            'total_km' => round((float) CustomerRoute::sum('km'), 1),
        ];

        return Inertia::render('customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): Response
    {
        return Inertia::render('customers/Create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'kode_customer' => 'required|string|max:255|unique:customers,kode_customer',
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'pic_nama' => 'nullable|string|max:255',
            'pic_telepon' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create($validated);

        return redirect()->route('customers.show', $customer->slug)
            ->with('success', "Customer {$customer->nama_customer} berhasil ditambahkan.");
    }

    /**
     * Display the specified customer details and connected routes.
     */
    public function show(Customer $customer): Response
    {
        $customer->load(['routes' => function ($q) {
            $q->latest();
        }]);

        return Inertia::render('customers/Show', [
            'customer' => array_merge($customer->toArray(), [
                'total_routes' => $customer->routes()->count(),
                'total_km' => round((float) $customer->routes()->sum('km'), 1),
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): Response
    {
        return Inertia::render('customers/Edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'nama_customer' => 'required|string|max:255',
            'kode_customer' => "required|string|max:255|unique:customers,kode_customer,{$customer->id}",
            'email' => 'nullable|email|max:255',
            'telepon' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'pic_nama' => 'nullable|string|max:255',
            'pic_telepon' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.show', $customer->slug)
            ->with('success', "Data customer {$customer->nama_customer} berhasil diperbarui.");
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $nama = $customer->nama_customer;
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', "Customer {$nama} berhasil dihapus.");
    }
}
