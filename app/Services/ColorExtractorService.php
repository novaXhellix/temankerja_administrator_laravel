<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class ColorExtractorService
{
    /**
     * Extract 2 dominant colors (Primary & Secondary) from an image file path.
     * Returns an array like ['primary' => '#HEX', 'secondary' => '#HEX'].
     *
     * @param string $imagePath Absolute path or storage relative path to image file.
     * @return array{primary: string, secondary: string}
     */
    public function extractPalette(string $imagePath): array
    {
        $defaultPalette = [
            'primary' => '#1E40AF',   // Default Tailwind Blue 800
            'secondary' => '#3B82F6', // Default Tailwind Blue 500
        ];

        if (!file_exists($imagePath)) {
            return $defaultPalette;
        }

        try {
            $info = @getimagesize($imagePath);
            if (!$info) {
                return $defaultPalette;
            }

            $mime = $info['mime'] ?? '';
            $img = null;

            switch ($mime) {
                case 'image/jpeg':
                    $img = @imagecreatefromjpeg($imagePath);
                    break;
                case 'image/png':
                    $img = @imagecreatefrompng($imagePath);
                    break;
                case 'image/webp':
                    $img = @imagecreatefromwebp($imagePath);
                    break;
                case 'image/gif':
                    $img = @imagecreatefromgif($imagePath);
                    break;
            }

            if (!$img) {
                return $defaultPalette;
            }

            $width = imagesx($img);
            $height = imagesy($img);

            // Resample down to max 64x64 for fast computation
            $sampleW = min(64, $width);
            $sampleH = min(64, $height);
            $resized = imagecreatetruecolor($sampleW, $sampleH);
            
            // Preserve transparency if PNG/WEBP
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $sampleW, $sampleH, $width, $height);
            imagedestroy($img);

            $colorCounts = [];

            for ($x = 0; $x < $sampleW; $x++) {
                for ($y = 0; $y < $sampleH; $y++) {
                    $rgba = imagecolorat($resized, $x, $y);
                    $alpha = ($rgba & 0x7F000000) >> 24;

                    // Skip fully or mostly transparent pixels (alpha > 50 in GD where 127 is invisible)
                    if ($alpha > 50) {
                        continue;
                    }

                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8) & 0xFF;
                    $b = $rgba & 0xFF;

                    // Skip pure white / near white backgrounds
                    if ($r > 240 && $g > 240 && $b > 240) {
                        continue;
                    }

                    // Skip pure black / near black background frames
                    if ($r < 20 && $g < 20 && $b < 20) {
                        continue;
                    }

                    // Quantize color to group similar shades (round to nearest multiple of 16)
                    $qr = min(255, (int) (round($r / 16) * 16));
                    $qg = min(255, (int) (round($g / 16) * 16));
                    $qb = min(255, (int) (round($b / 16) * 16));

                    $hex = sprintf('#%02X%02X%02X', $qr, $qg, $qb);

                    if (!isset($colorCounts[$hex])) {
                        $colorCounts[$hex] = 0;
                    }
                    $colorCounts[$hex]++;
                }
            }

            imagedestroy($resized);

            if (empty($colorCounts)) {
                return $defaultPalette;
            }

            // Sort colors by frequency descending
            arsort($colorCounts);

            $hexKeys = array_keys($colorCounts);

            $primary = $hexKeys[0] ?? $defaultPalette['primary'];
            $secondary = null;

            // Find a secondary color that is visually distinct from primary
            foreach (array_slice($hexKeys, 1) as $candidateHex) {
                if ($this->colorDistance($primary, $candidateHex) > 60) {
                    $secondary = $candidateHex;
                    break;
                }
            }

            if (!$secondary) {
                // If no distinct secondary, adjust primary brightness/saturation for secondary
                $secondary = $hexKeys[1] ?? $this->adjustBrightness($primary, -30);
            }

            return [
                'primary' => $primary,
                'secondary' => $secondary,
            ];

        } catch (Exception $e) {
            Log::error('Color extraction failed: ' . $e->getMessage());
            return $defaultPalette;
        }
    }

    /**
     * Calculate Euclidean distance between two HEX colors.
     */
    private function colorDistance(string $hex1, string $hex2): float
    {
        [$r1, $g1, $b1] = sscanf($hex1, "#%02x%02x%02x");
        [$r2, $g2, $b2] = sscanf($hex2, "#%02x%02x%02x");

        return sqrt(pow($r1 - $r2, 2) + pow($g1 - $g2, 2) + pow($b1 - $b2, 2));
    }

    /**
     * Adjust color brightness.
     */
    private function adjustBrightness(string $hex, int $steps): string
    {
        [$r, $g, $b] = sscanf($hex, "#%02x%02x%02x");
        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
