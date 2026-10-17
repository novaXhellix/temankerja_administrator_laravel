<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case HADIR = 'H';
    case HADIR_EARLY_OUT = 'H/EO';
    case HADIR_LATE_IN = 'H/LI';
    case HADIR_NO_CHECK_OUT = 'H/NCO';
    case HADIR_NO_CHECK_IN = 'H/NCI';
    case SAKIT_SURAT_DOKTER = 'SSD';
    case HADIR_IZIN_SETENGAH_HARI = 'H/ISH';
    case CUTI_DILUAR_TANGGUNGAN = 'CDT';
    case CUTI_TAHUNAN = 'CT';
    case CUTI_MENIKAH = 'CM';
    case CUTI_KELUARGA_MENINGGAL = 'CKM';
    case ALFA = 'A';
    case DINAS_LUAR = 'DL';

    /**
     * Dapatkan label deskripsi resmi sesuai tabel master HRIS.
     */
    public function label(): string
    {
        return match ($this) {
            self::HADIR => 'Hadir',
            self::HADIR_EARLY_OUT => 'Hadir / Clock Out Lebih Awal',
            self::HADIR_LATE_IN => 'Hadir / Late In (Terlambat)',
            self::HADIR_NO_CHECK_OUT => 'Hadir / No Check Out (Tidak Absen Clock Out)',
            self::HADIR_NO_CHECK_IN => 'Hadir / No Check In (Tidak Absen Clock In)',
            self::SAKIT_SURAT_DOKTER => 'Sakit dengan Surat Dokter',
            self::HADIR_IZIN_SETENGAH_HARI => 'Hadir / Izin Set hari',
            self::CUTI_DILUAR_TANGGUNGAN => 'Cuti Diluar Tanggungan (1 Hari), Pengganti Izin',
            self::CUTI_TAHUNAN => 'Cuti Tahunan',
            self::CUTI_MENIKAH => 'Cuti Menikah',
            self::CUTI_KELUARGA_MENINGGAL => 'Cuti Keluarga Meninggal',
            self::ALFA => 'Alfa',
            self::DINAS_LUAR => 'Dinas Luar',
        };
    }

    /**
     * Pengelompokan kategori kehadiran.
     */
    public function category(): string
    {
        return match ($this) {
            self::HADIR, self::HADIR_EARLY_OUT, self::HADIR_NO_CHECK_OUT, self::HADIR_NO_CHECK_IN => 'Hadir',
            self::HADIR_LATE_IN => 'Terlambat',
            self::SAKIT_SURAT_DOKTER => 'Sakit',
            self::HADIR_IZIN_SETENGAH_HARI, self::CUTI_DILUAR_TANGGUNGAN => 'Izin',
            self::CUTI_TAHUNAN, self::CUTI_MENIKAH, self::CUTI_KELUARGA_MENINGGAL => 'Cuti',
            self::ALFA => 'Alpa',
            self::DINAS_LUAR => 'Dinas',
        };
    }

    /**
     * Kelas styling warna badge Tailwind CSS.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::HADIR => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            self::HADIR_EARLY_OUT => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            self::HADIR_LATE_IN => 'bg-orange-500/10 text-orange-600 dark:text-orange-400 border-orange-500/20',
            self::HADIR_NO_CHECK_OUT, self::HADIR_NO_CHECK_IN => 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
            self::SAKIT_SURAT_DOKTER => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
            self::HADIR_IZIN_SETENGAH_HARI => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
            self::CUTI_DILUAR_TANGGUNGAN, self::CUTI_TAHUNAN, self::CUTI_MENIKAH, self::CUTI_KELUARGA_MENINGGAL => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
            self::ALFA => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            self::DINAS_LUAR => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
        };
    }

    /**
     * Seluruh kamus kode status absensi untuk kebutuhan UI Legend & dropdown.
     */
    public static function allWithLabels(): array
    {
        return array_map(fn (self $case) => [
            'code' => $case->value,
            'label' => $case->label(),
            'category' => $case->category(),
            'badge' => $case->badgeClasses(),
        ], self::cases());
    }
}
