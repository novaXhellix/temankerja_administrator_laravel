<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Companies
        $companies = [
            Company::firstOrCreate(
                ['kode_perusahaan' => 'TKI-001'],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => 'pt-teman-kerja-indonesia-' . strtolower(Str::random(5)),
                    'nama_perusahaan' => 'PT Teman Kerja Indonesia',
                    'email' => 'info@temankerja.id',
                    'telepon' => '(021) 555-0101',
                    'alamat' => 'Gedung Teman Kerja Tower Lt. 12, Jl. Jend. Sudirman Kav. 52, Jakarta Selatan',
                    'website' => 'https://temankerja.id',
                    'status' => 'Aktif',
                ]
            ),
            Company::firstOrCreate(
                ['kode_perusahaan' => 'SMU-002'],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => 'pt-sinergi-mitra-utama-' . strtolower(Str::random(5)),
                    'nama_perusahaan' => 'PT Sinergi Mitra Utama',
                    'email' => 'kontak@sinergimitra.co.id',
                    'telepon' => '(022) 420-1122',
                    'alamat' => 'Jl. Asia Afrika No. 88, Bandung, Jawa Barat',
                    'website' => 'https://sinergimitra.co.id',
                    'status' => 'Aktif',
                ]
            ),
            Company::firstOrCreate(
                ['kode_perusahaan' => 'NDP-003'],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => 'pt-nusantara-digital-perkasa-' . strtolower(Str::random(5)),
                    'nama_perusahaan' => 'PT Nusantara Digital Perkasa',
                    'email' => 'support@nusantaradigital.com',
                    'telepon' => '(031) 829-4050',
                    'alamat' => 'Jl. Pemuda No. 12, Surabaya, Jawa Timur',
                    'website' => 'https://nusantaradigital.com',
                    'status' => 'Aktif',
                ]
            ),
        ];

        // 2. Create Operational Regions (Wilayah Operasional) linked to Companies
        $c1 = $companies[0]->id;
        $c2 = $companies[1]->id;
        $c3 = $companies[2]->id;

        $regions = [
            'Jakarta Pusat' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-JKT-PST'],
                ['company_id' => $c1, 'nama_wilayah' => 'Jakarta Pusat', 'alamat_kantor' => 'Jl. MH Thamrin No. 1, Jakarta Pusat', 'telepon' => '(021) 390-1122', 'status' => 'Aktif']
            ),
            'Jakarta Selatan' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-JKT-SLT'],
                ['company_id' => $c1, 'nama_wilayah' => 'Jakarta Selatan', 'alamat_kantor' => 'Jl. Jend. Sudirman Kav. 52, Jakarta Selatan', 'telepon' => '(021) 555-0101', 'status' => 'Aktif']
            ),
            'Bandung' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-BDG'],
                ['company_id' => $c2, 'nama_wilayah' => 'Bandung', 'alamat_kantor' => 'Jl. Asia Afrika No. 88, Bandung', 'telepon' => '(022) 420-1122', 'status' => 'Aktif']
            ),
            'Surabaya' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-SBY'],
                ['company_id' => $c3, 'nama_wilayah' => 'Surabaya', 'alamat_kantor' => 'Jl. Pemuda No. 12, Surabaya', 'telepon' => '(031) 829-4050', 'status' => 'Aktif']
            ),
            'Medan' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-MDN'],
                ['company_id' => $c1, 'nama_wilayah' => 'Medan', 'alamat_kantor' => 'Jl. Gatot Subroto No. 45, Medan', 'telepon' => '(061) 451-9988', 'status' => 'Aktif']
            ),
            'Semarang' => Region::firstOrCreate(
                ['kode_wilayah' => 'WIL-SMG'],
                ['company_id' => $c2, 'nama_wilayah' => 'Semarang', 'alamat_kantor' => 'Jl. Pandanaran No. 30, Semarang', 'telepon' => '(024) 841-3344', 'status' => 'Aktif']
            ),
        ];

        // 3. Sample 10 Employees & Linked Users Data
        $dummyUsers = [
            [
                'name' => 'Bambang Soetjipto',
                'email' => 'owner@temankerja.id',
                'role' => UserRole::TOP_LEVEL,
                'company_name' => 'PT Teman Kerja Indonesia',
                'wilayah' => 'Jakarta Pusat',
                'nik' => '3171011508750001',
                'nip' => 'CEO-001',
                'status_kontrak' => 'PKWTT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Chief Executive Officer (Owner)',
                'lama_kontrak' => 'Permanen',
                'tgl_lahir' => '1975-08-15',
                'mulai_bekerja' => '2018-01-01',
                'bpjs_kes' => '000188992211',
                'bpjs_tk' => '000988776655',
            ],
            [
                'name' => 'Hendra Kurniawan',
                'email' => 'middle.bandung@sinergimitra.co.id',
                'role' => UserRole::MIDDLE_LEVEL,
                'company_name' => 'PT Sinergi Mitra Utama',
                'wilayah' => 'Bandung',
                'nik' => '3273021004820002',
                'nip' => 'MGR-002',
                'status_kontrak' => 'PKWTT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Regional General Manager',
                'lama_kontrak' => 'Permanen',
                'tgl_lahir' => '1982-04-10',
                'mulai_bekerja' => '2019-06-01',
                'bpjs_kes' => '000188992212',
                'bpjs_tk' => '000988776656',
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'spv.jakarta@temankerja.id',
                'role' => UserRole::FIRST_LEVEL,
                'company_name' => 'PT Teman Kerja Indonesia',
                'wilayah' => 'Jakarta Selatan',
                'nik' => '3174032211880003',
                'nip' => 'SPV-003',
                'status_kontrak' => 'PKWTT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Supervisor Operational',
                'lama_kontrak' => 'Permanen',
                'tgl_lahir' => '1988-11-22',
                'mulai_bekerja' => '2020-02-15',
                'bpjs_kes' => '000188992213',
                'bpjs_tk' => '000988776657',
            ],
            [
                'name' => 'Rahmat Hidayat',
                'email' => 'spv.surabaya@nusantaradigital.com',
                'role' => UserRole::FIRST_LEVEL,
                'company_name' => 'PT Nusantara Digital Perkasa',
                'wilayah' => 'Surabaya',
                'nik' => '3578040507900004',
                'nip' => 'SPV-004',
                'status_kontrak' => 'PKWTT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Team Leader Support',
                'lama_kontrak' => 'Permanen',
                'tgl_lahir' => '1990-07-05',
                'mulai_bekerja' => '2021-03-01',
                'bpjs_kes' => '000188992214',
                'bpjs_tk' => '000988776658',
            ],
            [
                'name' => 'Raihan Rivana',
                'email' => 'raihanrivana08@gmail.com',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Teman Kerja Indonesia',
                'wilayah' => 'Jakarta Selatan',
                'nik' => '3174051203980005',
                'nip' => 'STF-005',
                'status_kontrak' => 'PKWT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Staf Fullstack Developer',
                'lama_kontrak' => '12 Bulan',
                'tgl_lahir' => '1998-03-12',
                'mulai_bekerja' => '2023-01-10',
                'bpjs_kes' => '000188992215',
                'bpjs_tk' => '000988776659',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@temankerja.id',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Teman Kerja Indonesia',
                'wilayah' => 'Jakarta Selatan',
                'nik' => '3174062509970006',
                'nip' => 'STF-006',
                'status_kontrak' => 'PKWT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Staf Admin Keuangan',
                'lama_kontrak' => '12 Bulan',
                'tgl_lahir' => '1997-09-25',
                'mulai_bekerja' => '2023-05-01',
                'bpjs_kes' => '000188992216',
                'bpjs_tk' => '000988776660',
            ],
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'fauzi@sinergimitra.co.id',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Sinergi Mitra Utama',
                'wilayah' => 'Bandung',
                'nik' => '3273071802990007',
                'nip' => 'STF-007',
                'status_kontrak' => 'Probation',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Staf Field Operational',
                'lama_kontrak' => '3 Bulan',
                'tgl_lahir' => '1999-02-18',
                'mulai_bekerja' => '2024-02-01',
                'bpjs_kes' => '000188992217',
                'bpjs_tk' => '000988776661',
            ],
            [
                'name' => 'Nadia Putri',
                'email' => 'nadia@nusantaradigital.com',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Nusantara Digital Perkasa',
                'wilayah' => 'Surabaya',
                'nik' => '3578081411000008',
                'nip' => 'STF-008',
                'status_kontrak' => 'PKWT',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Staf UI/UX Designer',
                'lama_kontrak' => '12 Bulan',
                'tgl_lahir' => '2000-11-14',
                'mulai_bekerja' => '2023-08-15',
                'bpjs_kes' => '000188992218',
                'bpjs_tk' => '000988776662',
            ],
            [
                'name' => 'Budi Prasetyo',
                'email' => 'budi.prasetyo@temankerja.id',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Teman Kerja Indonesia',
                'wilayah' => 'Medan',
                'nik' => '1271090306960009',
                'nip' => 'STF-009',
                'status_kontrak' => 'Outsourcing',
                'status_karyawan' => 'Aktif',
                'jabatan' => 'Staf Customer Success',
                'lama_kontrak' => '6 Bulan',
                'tgl_lahir' => '1996-06-03',
                'mulai_bekerja' => '2023-11-01',
                'bpjs_kes' => '000188992219',
                'bpjs_tk' => '000988776663',
            ],
            [
                'name' => 'Maya Indah',
                'email' => 'maya.indah@sinergimitra.co.id',
                'role' => UserRole::STAF_OPERASIONAL,
                'company_name' => 'PT Sinergi Mitra Utama',
                'wilayah' => 'Semarang',
                'nik' => '3374102912950010',
                'nip' => 'STF-010',
                'status_kontrak' => 'PKWT',
                'status_karyawan' => 'Resign',
                'jabatan' => 'Ex-Staf HR Support',
                'lama_kontrak' => '12 Bulan',
                'tgl_lahir' => '1995-12-29',
                'mulai_bekerja' => '2022-01-15',
                'bpjs_kes' => '000188992220',
                'bpjs_tk' => '000988776664',
                'tgl_resign' => '2023-12-31',
                'alasan_resign' => 'Pindah domisili mengikuti keluarga.',
            ],
        ];

        // Store reference to Supervisor
        $spvJakarta = null;

        foreach ($dummyUsers as $data) {
            // Create User
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                    'role' => $data['role'],
                    'company_name' => $data['company_name'],
                    'email_verified_at' => now(),
                    'two_factor_secret' => encrypt('dummy-mfa-secret'),
                    'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2', 'code-3', 'code-4'])),
                    'two_factor_confirmed_at' => now(),
                    'is_mfa_bound' => true,
                ]
            );

            if ($data['nip'] === 'SPV-003') {
                $spvJakarta = $user;
            }

            // Assign Manager for staf operasional under SPV Jakarta
            if (in_array($data['nip'], ['STF-005', 'STF-006']) && $spvJakarta) {
                $user->update(['manager_id' => $spvJakarta->id]);
            }

            $regionObj = $regions[$data['wilayah']] ?? null;
            $companyObj = Company::where('nama_perusahaan', $data['company_name'])->first();

            // Create Linked Employee Record
            Employee::firstOrCreate(
                ['nip' => $data['nip']],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => Str::slug($data['name']) . '-' . strtolower(Str::random(5)),
                    'user_id' => $user->id,
                    'foto' => $data['foto'] ?? null,
                    'company_id' => $companyObj?->id,
                    'region_id' => $regionObj?->id,
                    'wilayah' => $data['wilayah'],
                    'nama_karyawan' => $data['name'],
                    'nik' => $data['nik'],
                    'nip' => $data['nip'],
                    'status_kontrak' => $data['status_kontrak'],
                    'status_karyawan' => $data['status_karyawan'],
                    'jabatan' => $data['jabatan'],
                    'lama_kontrak' => $data['lama_kontrak'] ?? null,
                    'periode_kontrak_mulai' => '2024-01-01',
                    'periode_kontrak_selesai' => '2024-12-31',
                    'tgl_lahir' => $data['tgl_lahir'],
                    'mulai_bekerja' => $data['mulai_bekerja'],
                    'bpjs_kes' => $data['bpjs_kes'],
                    'bpjs_tk' => $data['bpjs_tk'],
                    'tgl_resign' => $data['tgl_resign'] ?? null,
                    'alasan_resign' => $data['alasan_resign'] ?? null,
                ]
            );
        }
    }
}
