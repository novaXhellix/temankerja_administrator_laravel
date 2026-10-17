<?php

namespace App\Services;

use App\Models\SubRegion;
use Illuminate\Support\Str;

class SubRegionCodeGenerator
{
    /**
     * Map of common Indonesian cities to their standard recognized codes.
     */
    protected static array $knownCityCodes = [
        'JAKARTA PUSAT' => 'JKT-PST',
        'JAKARTA SELATAN' => 'JKT-SEL',
        'JAKARTA BARAT' => 'JKT-BAR',
        'JAKARTA UTARA' => 'JKT-UTR',
        'JAKARTA TIMUR' => 'JKT-TIM',
        'JAKARTA' => 'JKT',
        'SURABAYA' => 'SBY',
        'BANDUNG' => 'BDG',
        'SEMARANG' => 'SMG',
        'MEDAN' => 'MDN',
        'MAKASSAR' => 'MKS',
        'PALEMBANG' => 'PLB',
        'TANGERANG' => 'TGR',
        'TANGERANG SELATAN' => 'TGR-SEL',
        'BEKASI' => 'BKS',
        'BOGOR' => 'BGR',
        'DEPOK' => 'DPK',
        'MALANG' => 'MLG',
        'YOGYAKARTA' => 'YOGYA',
        'JOGJA' => 'JOG',
        'DENPASAR' => 'DPS',
        'SURAKARTA' => 'SOLO',
        'SOLO' => 'SOLO',
        'BALIKPAPAN' => 'BPN',
        'SAMARINDA' => 'SMD',
        'PONTIANAK' => 'PTK',
        'BANJARMASIN' => 'BJM',
        'PEKANBARU' => 'PKU',
        'PADANG' => 'PDG',
        'BATAM' => 'BTM',
        'CIREBON' => 'CRB',
        'SUKABUMI' => 'SKB',
        'TASIKMALAYA' => 'TSM',
        'SERANG' => 'SRG',
        'CILEGON' => 'CLG',
        'MAGELANG' => 'MGL',
        'PEKALONGAN' => 'PKL',
        'TEGAL' => 'TGL',
        'KEDIRI' => 'KDR',
        'BLITAR' => 'BLT',
        'MADIUN' => 'MDN',
        'PROBOLINGGO' => 'PBG',
        'PASURUAN' => 'PSR',
        'BANYUWANGI' => 'BWI',
        'SIDOARJO' => 'SDA',
        'GRESIK' => 'GRS',
        'KUDUS' => 'KDS',
        'JEMBER' => 'JBR',
        'BANDA ACEH' => 'BNA',
        'MATARAM' => 'MTR',
        'KUPANG' => 'KPG',
        'AMBON' => 'AMB',
        'JAYAPURA' => 'DJJ',
        'MANADO' => 'MND',
        'PALU' => 'PLU',
        'KENDARI' => 'KDI',
        'GORONTALO' => 'GTO',
    ];

    /**
     * Generate code purely from city name without Kota/Kabupaten prefixes.
     */
    public static function generate(string $cityName): string
    {
        $cleaned = trim(preg_replace('/^(kota|kabupaten|kab\.)\s+/i', '', trim($cityName)));
        if (empty($cleaned)) {
            return '';
        }

        $upper = strtoupper($cleaned);

        // Check exact match in dictionary
        if (isset(self::$knownCityCodes[$upper])) {
            return self::$knownCityCodes[$upper];
        }

        // Clean punctuation and split into words
        $words = preg_split('/[\s\-_]+/', $upper);
        $words = array_filter($words, fn ($w) => !empty($w));

        if (count($words) === 1) {
            $word = reset($words);
            // If single word <= 6 chars, return as-is
            if (strlen($word) <= 5) {
                return $word;
            }
            // Strip vowels if long (consonants acronym), keeping first letter
            $first = substr($word, 0, 1);
            $restWithoutVowels = preg_replace('/[AEIOU]/', '', substr($word, 1));
            $consonants = $first . $restWithoutVowels;

            if (strlen($consonants) >= 3 && strlen($consonants) <= 5) {
                return $consonants;
            }

            return substr($word, 0, 4);
        }

        // Multiple words: e.g. "Bandung Barat" -> "BDG-BAR" or initials
        $parts = [];
        foreach ($words as $w) {
            if (isset(self::$knownCityCodes[$w])) {
                $parts[] = self::$knownCityCodes[$w];
            } elseif (strlen($w) <= 4) {
                $parts[] = $w;
            } else {
                $parts[] = substr($w, 0, 3);
            }
        }

        return implode('-', $parts);
    }

    /**
     * Generate unique sub-region code in database.
     */
    public static function generateUnique(string $cityName, ?int $ignoreId = null): string
    {
        $base = self::generate($cityName);
        if (empty($base)) {
            $base = 'SUB-' . strtoupper(Str::random(4));
        }

        $code = $base;
        $counter = 1;

        while (SubRegion::where('kode_sub_wilayah', $code)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $counter++;
            $code = sprintf('%s-%02d', $base, $counter);
        }

        return $code;
    }
}
