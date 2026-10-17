<?php

namespace App\Services;

use App\Models\AttendanceLocation;

class GeofenceService
{
    /**
     * Hitung jarak dua koordinat GPS menggunakan formula Haversine (dalam meter).
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Algoritma Ray-Casting untuk memeriksa apakah suatu titik berada di dalam Polygon.
     * @param float $lat
     * @param float $lng
     * @param array $polygon Array of coordinates [[lat, lng], [lat, lng], ...]
     */
    public function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $numVertices = count($polygon);
        if ($numVertices < 3) {
            return false;
        }

        $inside = false;
        for ($i = 0, $j = $numVertices - 1; $i < $numVertices; $j = $i++) {
            $xi = (float) ($polygon[$i][0] ?? $polygon[$i]['lat'] ?? 0);
            $yi = (float) ($polygon[$i][1] ?? $polygon[$i]['lng'] ?? 0);
            $xj = (float) ($polygon[$j][0] ?? $polygon[$j]['lat'] ?? 0);
            $yj = (float) ($polygon[$j][1] ?? $polygon[$j]['lng'] ?? 0);

            $intersect = (($yi > $lng) !== ($yj > $lng))
                && ($lat < ($xj - $xi) * ($lng - $yi) / (($yj - $yi) ?: 0.00000001) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Evaluasi posisi koordinat terhadap Lokasi Absensi Kantor (Radius atau Polygon).
     */
    public function validateGeofence(AttendanceLocation $location, float $latitude, float $longitude): array
    {
        $distance = $this->calculateHaversineDistance(
            (float) $location->latitude,
            (float) $location->longitude,
            $latitude,
            $longitude
        );

        $isInGeofence = false;

        if ($location->check_type === 'polygon' && !empty($location->polygon_coordinates) && is_array($location->polygon_coordinates)) {
            $isInGeofence = $this->isPointInPolygon($latitude, $longitude, $location->polygon_coordinates);
        } else {
            // Default: Radius Circle Check
            $isInGeofence = ($distance <= (float) $location->radius_meter);
        }

        return [
            'is_in_geofence' => $isInGeofence,
            'distance_meter' => $distance,
            'max_radius_meter' => (float) $location->radius_meter,
            'check_type' => $location->check_type ?? 'radius',
            'location_name' => $location->nama_lokasi,
        ];
    }
}
