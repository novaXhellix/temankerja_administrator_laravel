<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerRoute;
use Illuminate\Database\Seeder;

class CustomerRouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();
        $primaryCompany = $companies->first();
        $secondaryCompany = $companies->skip(1)->first() ?: $primaryCompany;

        $customersData = [
            [
                'nama_customer' => 'PT Indofood CBP Sukses Makmur Tbk',
                'kode_customer' => 'CUST-INDF-001',
                'email' => 'logistics@indofood.co.id',
                'telepon' => '021-57958822',
                'alamat' => 'Sudirman Plaza, Indofood Tower, Jl. Jend. Sudirman Kav. 76-78, Jakarta Selatan',
                'pic_nama' => 'Bambang Sudiro',
                'pic_telepon' => '0812-8899-7711',
                'company_id' => $primaryCompany?->id,
                'status' => 'Aktif',
                'routes' => [
                    [
                        'nama_rute' => 'Cikarang Hub → DC Semarang',
                        'tempat_muat' => 'Pabrik Cikarang Barat (Kawasan MM2100)',
                        'tempat_bongkar' => 'Distribution Center Tambakaji Semarang',
                        'kargo' => 'Mie Instan & Bumbu Kemasan',
                        'km' => 425.5,
                        'tarif_standar' => 6500000,
                        'catatan' => 'Muatan kering dengan segel kontainer standar.',
                        'status' => 'Aktif',
                    ],
                    [
                        'nama_rute' => 'Tanjung Priok → DC Bandung',
                        'tempat_muat' => 'Terminal Petikemas Tanjung Priok Jakarta',
                        'tempat_bongkar' => 'Gudang Logistik Cimahi Bandung',
                        'kargo' => 'Tepung Terigu & Bahan Baku',
                        'km' => 155.0,
                        'tarif_standar' => 3200000,
                        'catatan' => 'Pengiriman via Tol Cipularang.',
                        'status' => 'Aktif',
                    ],
                ],
            ],
            [
                'nama_customer' => 'PT Semen Indonesia (Persero) Tbk',
                'kode_customer' => 'CUST-SMGR-002',
                'email' => 'distribusi@sig.id',
                'telepon' => '031-3981732',
                'alamat' => 'South Quarter Tower A, Jl. RA Kartini Kav. 8, Cilandak Barat, Jakarta Selatan',
                'pic_nama' => 'Hendra Setiawan',
                'pic_telepon' => '0813-1122-3344',
                'company_id' => $primaryCompany?->id,
                'status' => 'Aktif',
                'routes' => [
                    [
                        'nama_rute' => 'Tuban Quarry → Pelabuhan Gresik',
                        'tempat_muat' => 'Pabrik Semen Tuban Jawa Timur',
                        'tempat_bongkar' => 'Dermaga Khusus Pelabuhan Gresik',
                        'kargo' => 'Semen Curah & Sak Tonase Tinggi',
                        'km' => 95.0,
                        'tarif_standar' => 2800000,
                        'catatan' => 'Wajib armada dump truck / bulk cement trailer.',
                        'status' => 'Aktif',
                    ],
                    [
                        'nama_rute' => 'Gresik Plant → Proyek Tol Trans Jawa Solo',
                        'tempat_muat' => 'Pabrik Semen Gresik',
                        'tempat_bongkar' => 'Batching Plant Tol Solo-Kertosono',
                        'kargo' => 'Semen Portland Komposit',
                        'km' => 240.0,
                        'tarif_standar' => 4800000,
                        'catatan' => 'Pengiriman tepat waktu untuk pengecoran jalan tol.',
                        'status' => 'Aktif',
                    ],
                ],
            ],
            [
                'nama_customer' => 'PT Unilever Indonesia Tbk',
                'kode_customer' => 'CUST-UNVR-003',
                'email' => 'supplychain@unilever.com',
                'telepon' => '021-80827000',
                'alamat' => 'Grha Unilever, Green Office Park Kav. 3, BSD City, Tangerang',
                'pic_nama' => 'Dewi Lestari',
                'pic_telepon' => '0811-9876-5432',
                'company_id' => $secondaryCompany?->id,
                'status' => 'Aktif',
                'routes' => [
                    [
                        'nama_rute' => 'Cikarang Plant → DC Surabaya Rungkut',
                        'tempat_muat' => 'Pabrik Unilever Jababeka Cikarang',
                        'tempat_bongkar' => 'Mega DC Rungkut Industri Surabaya',
                        'kargo' => 'Consumer Goods & Perawatan Diri (FMCG)',
                        'km' => 765.0,
                        'tarif_standar' => 9800000,
                        'catatan' => 'Truk boks tertutup berpendingin ringan (wingbox).',
                        'status' => 'Aktif',
                    ],
                ],
            ],
            [
                'nama_customer' => 'PT Astra Otoparts Tbk',
                'kode_customer' => 'CUST-AUTO-004',
                'email' => 'procurement@component.astra.co.id',
                'telepon' => '021-4603550',
                'alamat' => 'Jl. Raya Pegangsaan Dua Km. 2.2, Kelapa Gading, Jakarta Utara',
                'pic_nama' => 'Irfan Hakim',
                'pic_telepon' => '0815-5544-3322',
                'company_id' => $secondaryCompany?->id,
                'status' => 'Aktif',
                'routes' => [
                    [
                        'nama_rute' => 'Kelapa Gading → Karawang Assembly Plant',
                        'tempat_muat' => 'Warehouse Pegangsaan Dua Jakarta Utara',
                        'tempat_bongkar' => 'Pabrik Perakitan Otomotif Karawang KIIC',
                        'kargo' => 'Komponen Otomotif & Aki Kendaraan',
                        'km' => 68.0,
                        'tarif_standar' => 2100000,
                        'catatan' => 'Sistem Just-In-Time (JIT) delivery.',
                        'status' => 'Aktif',
                    ],
                ],
            ],
        ];

        foreach ($customersData as $cData) {
            $routes = $cData['routes'] ?? [];
            unset($cData['routes']);

            $customer = Customer::firstOrCreate(
                ['kode_customer' => $cData['kode_customer']],
                $cData
            );

            foreach ($routes as $rData) {
                $rData['customer_id'] = $customer->id;
                $rData['company_id'] = $customer->company_id;

                CustomerRoute::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'tempat_muat' => $rData['tempat_muat'],
                        'tempat_bongkar' => $rData['tempat_bongkar'],
                    ],
                    $rData
                );
            }
        }
    }
}
