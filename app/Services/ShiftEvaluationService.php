<?php

namespace App\Services;

use App\Models\WorkShift;
use Carbon\Carbon;

class ShiftEvaluationService
{
    /**
     * Map hari Inggris ke Indonesia.
     */
    protected array $dayMap = [
        'Monday' => 'monday',
        'Tuesday' => 'tuesday',
        'Wednesday' => 'wednesday',
        'Thursday' => 'thursday',
        'Friday' => 'friday',
        'Saturday' => 'saturday',
        'Sunday' => 'sunday',
    ];

    /**
     * Evaluasi absensi terhadap jadwal WorkShift pada waktu tertentu.
     *
     * @param WorkShift|null $workShift
     * @param Carbon|null $clockInTime
     * @return array
     */
    public function evaluateClockIn(?WorkShift $workShift, ?Carbon $clockInTime = null): array
    {
        $now = $clockInTime ?: Carbon::now();
        $todayEnglish = $now->format('l');
        $dayKey = $this->dayMap[$todayEnglish] ?? strtolower($todayEnglish);

        // Jika tidak memiliki shift kerja khusus
        if (!$workShift) {
            return [
                'has_shift' => false,
                'is_work_day' => true,
                'is_late' => false,
                'lateness_minutes' => 0,
                'status_kehadiran' => 'Hadir',
                'expected_jam_masuk' => '08:00',
                'expected_jam_keluar' => '17:00',
                'tolerance_minutes' => 15,
                'message' => 'Absensi normal tanpa shift terdaftar.',
            ];
        }

        // Cek konfigurasi schedule_days jika ada
        $isWorkDay = true;
        $scheduledJamMasuk = $workShift->jam_masuk;
        $scheduledJamKeluar = $workShift->jam_keluar;
        $tolerance = (int) ($workShift->toleransi_keterlambatan_menit ?? 0);

        if (!empty($workShift->schedule_days) && is_array($workShift->schedule_days)) {
            foreach ($workShift->schedule_days as $dayConfig) {
                if (($dayConfig['day'] ?? '') === $dayKey) {
                    $isWorkDay = (bool) ($dayConfig['is_work'] ?? true);
                    if (!empty($dayConfig['jam_masuk'])) {
                        $scheduledJamMasuk = substr($dayConfig['jam_masuk'], 0, 5);
                    }
                    if (!empty($dayConfig['jam_keluar'])) {
                        $scheduledJamKeluar = substr($dayConfig['jam_keluar'], 0, 5);
                    }
                    break;
                }
            }
        }

        if (!$isWorkDay) {
            return [
                'has_shift' => true,
                'is_work_day' => false,
                'is_late' => false,
                'lateness_minutes' => 0,
                'status_kehadiran' => 'Hadir', // Lembur / Masuk di hari libur
                'expected_jam_masuk' => $scheduledJamMasuk,
                'expected_jam_keluar' => $scheduledJamKeluar,
                'tolerance_minutes' => $tolerance,
                'message' => 'Hari ini merupakan hari libur (Day Off) pada jadwal shift Anda.',
            ];
        }

        // Hitung Keterlambatan
        $shiftStart = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . substr($scheduledJamMasuk, 0, 5));
        $shiftStartWithTolerance = $shiftStart->copy()->addMinutes($tolerance);

        $isLate = $now->greaterThan($shiftStartWithTolerance);
        $latenessMinutes = 0;

        if ($isLate) {
            // Dihitung dari jam_masuk shift asli
            $latenessMinutes = $now->diffInMinutes($shiftStart);
        }

        return [
            'has_shift' => true,
            'is_work_day' => true,
            'is_late' => $isLate,
            'lateness_minutes' => $latenessMinutes,
            'status_kehadiran' => $isLate ? 'H/LI' : 'H',
            'status_label' => $isLate ? 'Hadir / Late In (Terlambat)' : 'Hadir',
            'expected_jam_masuk' => $scheduledJamMasuk,
            'expected_jam_keluar' => $scheduledJamKeluar,
            'tolerance_minutes' => $tolerance,
            'is_night_shift' => (bool) $workShift->is_night_shift,
            'shift_name' => $workShift->nama_shift,
            'message' => $isLate 
                ? "Terlambat {$latenessMinutes} menit dari jadwal masuk ({$scheduledJamMasuk}, toleransi {$tolerance} menit)."
                : "Tepat waktu (Jadwal: {$scheduledJamMasuk}).",
        ];
    }

    /**
     * Evaluasi status pulang cepat pada Clock Out.
     */
    public function evaluateClockOut(?WorkShift $workShift, ?Carbon $clockOutTime = null): array
    {
        $now = $clockOutTime ?: Carbon::now();
        $todayEnglish = $now->format('l');
        $dayKey = $this->dayMap[$todayEnglish] ?? strtolower($todayEnglish);

        if (!$workShift) {
            return [
                'is_early_leave' => false,
                'early_minutes' => 0,
                'expected_jam_keluar' => '17:00',
            ];
        }

        $scheduledJamKeluar = $workShift->jam_keluar;
        if (!empty($workShift->schedule_days) && is_array($workShift->schedule_days)) {
            foreach ($workShift->schedule_days as $dayConfig) {
                if (($dayConfig['day'] ?? '') === $dayKey && !empty($dayConfig['jam_keluar'])) {
                    $scheduledJamKeluar = substr($dayConfig['jam_keluar'], 0, 5);
                    break;
                }
            }
        }

        $shiftEnd = Carbon::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . substr($scheduledJamKeluar, 0, 5));
        $isEarlyLeave = $now->lessThan($shiftEnd);
        $earlyMinutes = $isEarlyLeave ? $shiftEnd->diffInMinutes($now) : 0;

        return [
            'is_early_leave' => $isEarlyLeave,
            'early_minutes' => $earlyMinutes,
            'expected_jam_keluar' => $scheduledJamKeluar,
            'message' => $isEarlyLeave 
                ? "Pulang lebih awal {$earlyMinutes} menit sebelum jadwal ({$scheduledJamKeluar})."
                : "Pulang sesuai/melebihi jam kerja ({$scheduledJamKeluar}).",
        ];
    }
}
