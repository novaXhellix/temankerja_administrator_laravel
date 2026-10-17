<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Region;
use App\Models\SubRegion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TerritorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $territories = [
            [
                'nama' => 'DKI Jakarta',
                'kode' => 'PROV-DKI',
                'alamat' => 'Jl. Medan Merdeka Selatan No. 8-9, Gambir, Jakarta Pusat',
                'telepon' => '021-3822255',
                'sub_regions' => [
                    ['nama' => 'Kota Jakarta Pusat', 'kode' => 'KOTA-JKT-PST', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Jakarta Selatan', 'kode' => 'KOTA-JKT-SEL', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Jakarta Barat', 'kode' => 'KOTA-JKT-BAR', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Jakarta Utara', 'kode' => 'KOTA-JKT-UTR', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Jakarta Timur', 'kode' => 'KOTA-JKT-TIM', 'tipe' => 'Kota'],
                ],
            ],
            [
                'nama' => 'Jawa Barat',
                'kode' => 'PROV-JBR',
                'alamat' => 'Jl. Diponegoro No. 22, Citarum, Kec. Bandung Wetan, Kota Bandung',
                'telepon' => '022-4233347',
                'sub_regions' => [
                    ['nama' => 'Kota Bandung', 'kode' => 'KOTA-BDG-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Bekasi', 'kode' => 'KOTA-BKS-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Bogor', 'kode' => 'KOTA-BGR-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Depok', 'kode' => 'KOTA-DPK-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kabupaten Bekasi', 'kode' => 'KAB-BKS-01', 'tipe' => 'Kabupaten'],
                ],
            ],
            [
                'nama' => 'Jawa Timur',
                'kode' => 'PROV-JTM',
                'alamat' => 'Jl. Pahlawan No. 110, Alun-alun Contong, Kec. Bubutan, Kota Surabaya',
                'telepon' => '031-3524001',
                'sub_regions' => [
                    ['nama' => 'Kota Surabaya', 'kode' => 'KOTA-SBY-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Malang', 'kode' => 'KOTA-MLG-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kabupaten Sidoarjo', 'kode' => 'KAB-SDA-01', 'tipe' => 'Kabupaten'],
                    ['nama' => 'Kabupaten Gresik', 'kode' => 'KAB-GRS-01', 'tipe' => 'Kabupaten'],
                ],
            ],
            [
                'nama' => 'Kalimantan Timur',
                'kode' => 'PROV-KTM',
                'alamat' => 'Jl. Gajah Mada No. 2, Jawa, Kec. Samarinda Ulu, Kota Samarinda',
                'telepon' => '0541-733333',
                'sub_regions' => [
                    ['nama' => 'Kota Balikpapan', 'kode' => 'KOTA-BPP-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Samarinda', 'kode' => 'KOTA-SMD-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Bontang', 'kode' => 'KOTA-BTG-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kabupaten Kutai Kartanegara', 'kode' => 'KAB-KUKAR-01', 'tipe' => 'Kabupaten'],
                ],
            ],
            [
                'nama' => 'Riau',
                'kode' => 'PROV-RIU',
                'alamat' => 'Jl. Jend. Sudirman No. 460, Jadirejo, Kec. Sukajadi, Kota Pekanbaru',
                'telepon' => '0761-31444',
                'sub_regions' => [
                    ['nama' => 'Kota Pekanbaru', 'kode' => 'KOTA-PKU-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Dumai', 'kode' => 'KOTA-DUM-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kabupaten Siak', 'kode' => 'KAB-SIAK-01', 'tipe' => 'Kabupaten'],
                    ['nama' => 'Kabupaten Bengkalis', 'kode' => 'KAB-BKL-01', 'tipe' => 'Kabupaten'],
                ],
            ],
            [
                'nama' => 'Sumatera Utara',
                'kode' => 'PROV-SMU',
                'alamat' => 'Jl. Pangeran Diponegoro No. 30, Madras Hulu, Kota Medan',
                'telepon' => '061-4537111',
                'sub_regions' => [
                    ['nama' => 'Kota Medan', 'kode' => 'KOTA-MDN-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kota Binjai', 'kode' => 'KOTA-BNJ-01', 'tipe' => 'Kota'],
                    ['nama' => 'Kabupaten Deli Serdang', 'kode' => 'KAB-DLS-01', 'tipe' => 'Kabupaten'],
                ],
            ],
        ];

        foreach ($territories as $item) {
            $region = Region::firstOrCreate(
                ['kode_wilayah' => $item['kode']],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => Str::slug($item['nama']) . '-' . strtolower(Str::random(5)),
                    'nama_wilayah' => $item['nama'],
                    'alamat_kantor' => $item['alamat'],
                    'telepon' => $item['telepon'],
                    'status' => 'Aktif',
                ]
            );

            foreach ($item['sub_regions'] as $sub) {
                SubRegion::firstOrCreate(
                    ['kode_sub_wilayah' => $sub['kode']],
                    [
                        'region_id' => $region->id,
                        'uuid' => (string) Str::uuid(),
                        'slug' => Str::slug($sub['nama']) . '-' . strtolower(Str::random(5)),
                        'nama_sub_wilayah' => $sub['nama'],
                        'tipe' => $sub['tipe'],
                        'status' => 'Aktif',
                    ]
                );
            }
        }

        // Tautkan perusahaan yang belum punya wilayah ke wilayah default
        $companies = Company::whereNull('region_id')->get();
        $allSubRegions = SubRegion::with('region')->get();

        if ($allSubRegions->isNotEmpty()) {
            foreach ($companies as $index => $comp) {
                $sub = $allSubRegions[$index % $allSubRegions->count()];
                $comp->update([
                    'region_id' => $sub->region_id,
                    'sub_region_id' => $sub->id,
                    'wilayah_operasional' => $sub->region->nama_wilayah,
                    'sub_wilayah' => $sub->nama_sub_wilayah,
                ]);
            }
        }

        // Sinkronisasi data karyawan yang sudah punya region_id
        $employees = Employee::with('region')->whereNull('sub_region_id')->get();
        foreach ($employees as $emp) {
            if ($emp->region_id) {
                $firstSub = SubRegion::where('region_id', $emp->region_id)->first();
                if ($firstSub) {
                    $emp->update([
                        'sub_region_id' => $firstSub->id,
                        'sub_wilayah' => $firstSub->nama_sub_wilayah,
                        'wilayah' => $emp->region?->nama_wilayah ?? $emp->wilayah,
                    ]);
                }
            }
        }
    }
}
