<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $c1 = Company::where('kode_perusahaan', 'TKI-001')->first()?->id;
        $c2 = Company::where('kode_perusahaan', 'SMU-002')->first()?->id;
        $c3 = Company::where('kode_perusahaan', 'NDP-003')->first()?->id;

        $departmentsData = [
            [
                'kode_departemen' => 'DEPT-HRGA',
                'nama_departemen' => 'Human Resource & General Affair',
                'company_id' => $c1,
                'deskripsi' => 'Pengelolaan SDM, rekrutmen, absensi, payroll, dan operasional umum kantor.',
                'status' => 'Aktif',
            ],
            [
                'kode_departemen' => 'DEPT-ITDEV',
                'nama_departemen' => 'Information Technology & Engineering',
                'company_id' => $c1,
                'deskripsi' => 'Pengembangan arsitektur perangkat lunak, infrastruktur server, dan sistem informasi.',
                'status' => 'Aktif',
            ],
            [
                'kode_departemen' => 'DEPT-FINACC',
                'nama_departemen' => 'Finance & Accounting',
                'company_id' => $c1,
                'deskripsi' => 'Manajemen keuangan, audit, pembukuan arus kas, dan perpajakan perusahaan.',
                'status' => 'Aktif',
            ],
            [
                'kode_departemen' => 'DEPT-OPSLOG',
                'nama_departemen' => 'Operations & Field Logistic',
                'company_id' => $c2,
                'deskripsi' => 'Operasional lapangan, rantai pasok (supply chain), distribusi dan layanan armada.',
                'status' => 'Aktif',
            ],
            [
                'kode_departemen' => 'DEPT-PRODUX',
                'nama_departemen' => 'Product Management & UI/UX',
                'company_id' => $c3,
                'deskripsi' => 'Riset produk digital, perancangan antarmuka pengguna, dan pengalaman pengguna interaktif.',
                'status' => 'Aktif',
            ],
            [
                'kode_departemen' => 'DEPT-CSM',
                'nama_departemen' => 'Customer Success & Client Relations',
                'company_id' => $c1,
                'deskripsi' => 'Dukungan kepuasan pelanggan, after-sales service, dan komunikasi mitra klien.',
                'status' => 'Aktif',
            ],
        ];

        $deptMap = [];
        foreach ($departmentsData as $data) {
            $dept = Department::firstOrCreate(
                ['kode_departemen' => $data['kode_departemen']],
                [
                    'uuid' => (string) Str::uuid(),
                    'slug' => Str::slug($data['nama_departemen']) . '-' . strtolower(Str::random(5)),
                    'nama_departemen' => $data['nama_departemen'],
                    'company_id' => $data['company_id'],
                    'deskripsi' => $data['deskripsi'],
                    'status' => $data['status'],
                ]
            );
            $deptMap[$data['kode_departemen']] = $dept;
        }

        // Map existing employees to departments based on NIP / Jabatan
        $employeeDeptMapping = [
            'STF-005' => 'DEPT-ITDEV', // Agung Pratama - Staf Fullstack Developer
            'SPV-003' => 'DEPT-ITDEV', // Hendra Wijaya - Supervisor Engineering
            'STF-006' => 'DEPT-FINACC', // Siti Aminah - Staf Admin Keuangan
            'SPV-004' => 'DEPT-FINACC', // Rina Marlina - Supervisor Finance
            'STF-007' => 'DEPT-OPSLOG', // Ahmad Fauzi - Staf Field Operational
            'STF-008' => 'DEPT-PRODUX', // Nadia Putri - Staf UI/UX Designer
            'STF-009' => 'DEPT-CSM',    // Budi Prasetyo - Staf Customer Success
            'STF-010' => 'DEPT-HRGA',   // Linda Kusuma - Staf HR
            'DIR-002' => 'DEPT-HRGA',   // Dewi Lestari - Direktur HR
            'DIR-001' => 'DEPT-ITDEV',  // Bambang Soetjipto - Owner / Chief
        ];

        foreach ($employeeDeptMapping as $nip => $deptCode) {
            if (isset($deptMap[$deptCode])) {
                Employee::where('nip', $nip)->update([
                    'department_id' => $deptMap[$deptCode]->id,
                ]);
            }
        }
    }
}
