<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkShift;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThreeMonthAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds to populate 3+ months of attendance records
     * from 2026-07-01 to 2026-10-14 (today).
     */
    public function run(): void
    {
        $this->command?->info('Memulai seeding data absensi 3 bulan kebelakang (1 Juli 2026 s/d 14 Oktober 2026)...');

        $company1 = Company::first();
        $company2 = Company::skip(1)->first() ?? $company1;

        // 1. Pastikan Perusahaan 1 & 2 memiliki WorkShift Back Office yang valid
        $shiftC1 = WorkShift::firstOrCreate(
            ['company_id' => $company1->id, 'kode_shift' => 'SH-PG'],
            [
                'nama_shift' => 'Back Office',
                'jam_masuk' => '08:00:00',
                'jam_keluar' => '17:00:00',
                'schedule_days' => [
                    ['day' => 'monday', 'is_work' => true, 'day_name' => 'Senin', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'tuesday', 'is_work' => true, 'day_name' => 'Selasa', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'wednesday', 'is_work' => true, 'day_name' => 'Rabu', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'thursday', 'is_work' => true, 'day_name' => 'Kamis', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'friday', 'is_work' => true, 'day_name' => 'Jumat', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'saturday', 'is_work' => true, 'day_name' => 'Sabtu', 'jam_masuk' => '08:00', 'jam_keluar' => '12:00'],
                    ['day' => 'sunday', 'is_work' => false, 'day_name' => 'Minggu', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                ],
                'work_days' => 'Senin, Selasa, Rabu, Kamis, Jumat, Sabtu',
                'toleransi_keterlambatan_menit' => 0,
                'is_night_shift' => false,
                'status' => 'Aktif',
            ]
        );

        $shiftC2 = WorkShift::firstOrCreate(
            ['company_id' => $company2->id, 'kode_shift' => 'SH-AERO-01'],
            [
                'nama_shift' => 'Back Office Aerobus',
                'jam_masuk' => '08:00:00',
                'jam_keluar' => '17:00:00',
                'schedule_days' => [
                    ['day' => 'monday', 'is_work' => true, 'day_name' => 'Senin', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'tuesday', 'is_work' => true, 'day_name' => 'Selasa', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'wednesday', 'is_work' => true, 'day_name' => 'Rabu', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'thursday', 'is_work' => true, 'day_name' => 'Kamis', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'friday', 'is_work' => true, 'day_name' => 'Jumat', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                    ['day' => 'saturday', 'is_work' => true, 'day_name' => 'Sabtu', 'jam_masuk' => '08:00', 'jam_keluar' => '12:00'],
                    ['day' => 'sunday', 'is_work' => false, 'day_name' => 'Minggu', 'jam_masuk' => '08:00', 'jam_keluar' => '17:00'],
                ],
                'work_days' => 'Senin, Selasa, Rabu, Kamis, Jumat, Sabtu',
                'toleransi_keterlambatan_menit' => 0,
                'is_night_shift' => false,
                'status' => 'Aktif',
            ]
        );

        $regionC1 = \App\Models\Region::where('company_id', $company1->id)->first() ?? \App\Models\Region::first();
        $regionC2 = \App\Models\Region::where('company_id', $company2->id)->first() ?? \App\Models\Region::first();

        // 2. Hubungkan data karyawan aktif yang masih company_id null ke perusahaan & shift yang sesuai
        $activeEmployees = Employee::where('status_karyawan', 'Aktif')->get();
        foreach ($activeEmployees as $idx => $emp) {
            $comp = $emp->company_id ? Company::find($emp->company_id) : (($idx % 2 === 0) ? $company1 : $company2);
            $shift = ($comp->id === $company2->id) ? $shiftC2 : $shiftC1;
            $reg = ($comp->id === $company2->id) ? $regionC2 : $regionC1;

            $emp->update([
                'company_id' => $emp->company_id ?: $comp->id,
                'work_shift_id' => $emp->work_shift_id ?: $shift->id,
                'region_id' => $emp->region_id ?: $reg?->id,
            ]);
        }

        // Ambil data lokasi geofence kantor
        $locationC1 = AttendanceLocation::where('company_id', 1)->first() ?? AttendanceLocation::first();
        $locationC2 = AttendanceLocation::where('company_id', 2)->first() ?? AttendanceLocation::first();

        // 3. Rentang waktu: 1 Juli 2026 s/d 14 Oktober 2026 (Hari ini)
        $startDate = Carbon::create(2026, 7, 1);
        $endDate = Carbon::create(2026, 10, 14); // Hari ini
        $todayStr = '2026-10-14';

        $activeEmployees = Employee::where('status_karyawan', 'Aktif')->get();
        $this->command?->info("Ditemukan {$activeEmployees->count()} karyawan aktif untuk digenerate absensinya.");

        $totalInserted = 0;
        $totalUpdated = 0;

        $catatanTerlambat = [
            'Macet di jalan protokol',
            'Kendala kendaraan mogok',
            'Hujan lebat pagi hari',
            'Antrian panjang pengisian BBM',
            'Menunggu angkutan umum',
            null,
        ];

        $catatanIzin = [
            'Izin keperluan keluarga mendesak',
            'Mengurus perpanjangan SIM / dokumen resmi',
            'Ada acara keluarga di luar kota',
        ];

        $catatanSakit = [
            'Demam dan flu (Istirahat dokter)',
            'Sakit kepala migrain hebat',
            'Pemeriksaan medis rutin di klinik',
        ];

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($activeEmployees as $employee) {
            $companyId = $employee->company_id ?: 1;
            $workShiftId = $employee->work_shift_id ?: ($companyId === 2 ? $shiftC2->id : $shiftC1->id);
            $location = ($companyId === 2) ? $locationC2 : $locationC1;

            $baseLat = $location ? (float) $location->latitude : 0.53255800;
            $baseLng = $location ? (float) $location->longitude : 101.44532200;

            foreach ($period as $date) {
                // Lewati hari Minggu (Libur akhir pekan)
                if ($date->isSunday()) {
                    continue;
                }

                $dateStr = $date->format('Y-m-d');
                $isSaturday = $date->isSaturday();
                $isToday = ($dateStr === $todayStr);

                // Algoritma deterministik pseudo-random berdasarkan Employee ID & Tanggal
                // Menjamin persebaran data realistis dan stabil jika seeder di-rerun
                $hash = abs(crc32($employee->id . '_' . $dateStr));
                $seedScore = $hash % 100;

                // Tentukan status kehadiran berdasarkan 13 kode standar master HRIS (sesuai gambar):
                // H, H/LI, H/EO, H/NCO, H/NCI, SSD, H/ISH, CDT, CT, CM, CKM, A, DL
                $statusKehadiran = 'H';
                $jamMasuk = null;
                $jamKeluar = null;
                $menitTerlambat = 0;
                $catatan = null;

                if ($seedScore < 70) {
                    // H: Hadir tepat waktu (Clock In & Clock Out on-time)
                    $statusKehadiran = 'H';
                    $menitMasuk = 42 + ($hash % 16); // 07:42 s/d 07:58
                    $jamMasuk = sprintf('07:%02d:%02d', $menitMasuk, $hash % 60);

                    if ($isSaturday) {
                        $jamKeluar = sprintf('12:%02d:%02d', 5 + ($hash % 20), ($hash / 3) % 60);
                    } else {
                        $jamKeluar = sprintf('17:%02d:%02d', 2 + ($hash % 38), ($hash / 3) % 60);
                    }
                } elseif ($seedScore < 80) {
                    // H/LI: Hadir / Late In (Terlambat)
                    $statusKehadiran = 'H/LI';
                    $menitTerlambat = 6 + ($hash % 34); // Terlambat 6 - 40 menit
                    $jamMasuk = sprintf('08:%02d:%02d', $menitTerlambat, $hash % 60);

                    if ($isSaturday) {
                        $jamKeluar = sprintf('12:%02d:%02d', 15 + ($hash % 25), ($hash / 3) % 60);
                    } else {
                        $jamKeluar = sprintf('17:%02d:%02d', 15 + ($hash % 40), ($hash / 3) % 60);
                    }
                    $catatan = $catatanTerlambat[$hash % count($catatanTerlambat)];
                } elseif ($seedScore < 85) {
                    // H/EO: Hadir / Clock Out Lebih Awal
                    $statusKehadiran = 'H/EO';
                    $menitMasuk = 45 + ($hash % 13);
                    $jamMasuk = sprintf('07:%02d:%02d', $menitMasuk, $hash % 60);

                    if ($isSaturday) {
                        $jamKeluar = sprintf('11:%02d:%02d', 20 + ($hash % 35), ($hash / 3) % 60);
                    } else {
                        $eoHour = 15 + ($hash % 2); // Jam 15 atau 16
                        $eoMinute = ($hash / 3) % 55;
                        $jamKeluar = sprintf('%02d:%02d:%02d', $eoHour, $eoMinute, ($hash / 5) % 60);
                    }
                    $catatan = 'Pulang lebih awal atas izin supervisor';
                } elseif ($seedScore < 88) {
                    // H/NCO: Hadir / No Check Out (Tidak Absen Clock Out)
                    $statusKehadiran = 'H/NCO';
                    $menitMasuk = 45 + ($hash % 14);
                    $jamMasuk = sprintf('07:%02d:%02d', $menitMasuk, $hash % 60);
                    $jamKeluar = null;
                    $catatan = 'Tidak melakukan clock out pada sistem';
                } elseif ($seedScore < 90) {
                    // H/NCI: Hadir / No Check In (Tidak Absen Clock In)
                    $statusKehadiran = 'H/NCI';
                    $jamMasuk = null;
                    $jamKeluar = $isSaturday ? '12:10:00' : '17:15:00';
                    $catatan = 'Lupa absen clock in pagi, verifikasi manual via supervisi';
                } elseif ($seedScore < 92) {
                    // SSD: Sakit dengan Surat Dokter
                    $statusKehadiran = 'SSD';
                    $catatan = $catatanSakit[$hash % count($catatanSakit)];
                    $jamMasuk = null;
                    $jamKeluar = null;
                } elseif ($seedScore < 94) {
                    // H/ISH: Hadir / Izin Set hari (Izin Setengah Hari)
                    $statusKehadiran = 'H/ISH';
                    $jamMasuk = '12:45:00';
                    $jamKeluar = '17:10:00';
                    $catatan = 'Izin setengah hari (keperluan keluarga mendesak pagi hari)';
                } elseif ($seedScore < 95) {
                    // CDT: Cuti Diluar Tanggungan (1 Hari), Pengganti Izin
                    $statusKehadiran = 'CDT';
                    $catatan = 'Cuti di luar tanggungan (1 hari) pengganti izin';
                    $jamMasuk = null;
                    $jamKeluar = null;
                } elseif ($seedScore < 97) {
                    // CT: Cuti Tahunan
                    $statusKehadiran = 'CT';
                    $catatan = 'Cuti Tahunan terencana';
                    $jamMasuk = null;
                    $jamKeluar = null;
                } elseif ($seedScore === 97) {
                    // CM atau CKM
                    if ($hash % 2 === 0) {
                        $statusKehadiran = 'CM';
                        $catatan = 'Cuti Menikah Karyawan';
                    } else {
                        $statusKehadiran = 'CKM';
                        $catatan = 'Cuti Duka Cita / Keluarga Meninggal';
                    }
                    $jamMasuk = null;
                    $jamKeluar = null;
                } elseif ($seedScore === 98) {
                    // DL: Dinas Luar
                    $statusKehadiran = 'DL';
                    $jamMasuk = '08:00:00';
                    $jamKeluar = $isSaturday ? '12:00:00' : '17:00:00';
                    $catatan = 'Tugas dinas luar kantor / site visit operasional';
                } else {
                    // A: Alfa
                    $statusKehadiran = 'A';
                    $catatan = 'Alfa (Tanpa Keterangan)';
                    $jamMasuk = null;
                    $jamKeluar = null;
                }

                // Kondisi khusus HARI INI (14 Oktober 2026, pk 11:00)
                // Karyawan hadir sudah Clock In pagi ini, namun belum Clock Out (masih jam kantor)
                if ($isToday) {
                    if (in_array($statusKehadiran, ['H', 'H/LI', 'H/EO', 'DL'])) {
                        $jamKeluar = null; // Belum clock-out!
                        // Jika tadinya H/EO, karena belum jam keluar, tetapkan H
                        if ($statusKehadiran === 'H/EO') {
                            $statusKehadiran = 'H';
                        }
                    } elseif ($statusKehadiran === 'H/NCO') {
                        // Hari ini belum selesai, jadi belum NCO
                        $statusKehadiran = 'H';
                        $jamKeluar = null;
                    }
                }

                // Variasi jarak geofence kantor (1 - 25 meter dari pusat lokasi kantor)
                $latJitter = (($hash % 10) - 5) * 0.000015;
                $lngJitter = ((($hash / 10) % 10) - 5) * 0.000015;
                $distanceMasuk = round(3.5 + ($hash % 20), 2);
                $distanceKeluar = $jamKeluar ? round(4.0 + (($hash / 2) % 22), 2) : null;

                $latMasuk = $jamMasuk ? ($baseLat + $latJitter) : null;
                $lngMasuk = $jamMasuk ? ($baseLng + $lngJitter) : null;
                $latKeluar = $jamKeluar ? ($baseLat + $latJitter) : null;
                $lngKeluar = $jamKeluar ? ($baseLng + $lngJitter) : null;

                $existing = Attendance::where('employee_id', $employee->id)->where('tanggal', $dateStr)->first();

                $payload = [
                    'company_id' => $companyId,
                    'attendance_location_id' => $location?->id,
                    'work_shift_id' => $workShiftId,
                    'jam_masuk' => $jamMasuk,
                    'jam_keluar' => $jamKeluar,
                    'latitude_masuk' => $latMasuk,
                    'longitude_masuk' => $lngMasuk,
                    'is_in_geofence_masuk' => $jamMasuk ? true : false,
                    'jarak_meter_masuk' => $jamMasuk ? $distanceMasuk : 0,
                    'latitude_keluar' => $latKeluar,
                    'longitude_keluar' => $lngKeluar,
                    'is_in_geofence_keluar' => $jamKeluar ? true : false,
                    'jarak_meter_keluar' => $distanceKeluar,
                    'status_kehadiran' => $statusKehadiran,
                    'menit_keterlambatan' => $menitTerlambat,
                    'catatan' => $catatan,
                ];

                if ($existing) {
                    $existing->update($payload);
                    $totalUpdated++;
                } else {
                    Attendance::create(array_merge([
                        'uuid' => (string) Str::uuid(),
                        'employee_id' => $employee->id,
                        'tanggal' => $dateStr,
                    ], $payload));
                    $totalInserted++;
                }
            }
        }

        $this->command?->info("Berhasil generate absensi: {$totalInserted} data baru, {$totalUpdated} data diperbarui.");

        // 4. Sinkronisasi rekap bulanan ke tabel attendance_reports (Bulan 7, 8, 9, 10 Tahun 2026)
        $this->command?->info('Menyinkronkan tabel rekap bulanan (attendance_reports)...');
        $reportService = app(AttendanceReportService::class);

        foreach ($activeEmployees as $employee) {
            foreach ([7, 8, 9, 10] as $month) {
                $reportService->syncMonthlyReport($employee, $month, 2026);
            }
        }

        $this->command?->info('Rekapitulasi bulanan attendance_reports berhasil disinkronkan.');
    }
}
