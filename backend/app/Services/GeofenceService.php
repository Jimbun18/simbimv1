<?php

namespace App\Services;

use App\Models\SchoolProfile;
use App\Models\Setting;

class GeofenceService
{
    /**
     * Radius Bumi dalam meter
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Hitung jarak Haversine antara dua titik koordinat (dalam meter).
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)
        ));

        return (int) round($angle * self::EARTH_RADIUS_METERS);
    }

    /**
     * Validasi apakah koordinat pengguna berada dalam radius geofence sekolah.
     *
     * @param float|null $userLat
     * @param float|null $userLng
     * @return array
     */
    public function validateLocation(?float $userLat, ?float $userLng): array
    {
        $geofenceEnabled = (bool) Setting::get('geofence_enabled', true);
        $profile = SchoolProfile::first();

        // Jika setting geofence dinonaktifkan
        if (!$geofenceEnabled) {
            return [
                'is_valid' => true,
                'geofence_enabled' => false,
                'distance_m' => 0,
                'radius_m' => $profile?->radius_geofence_m ?? 100,
                'school_lat' => $profile?->latitude,
                'school_lng' => $profile?->longitude,
                'message' => 'Validasi geofence dinonaktifkan oleh pengaturan sistem.',
            ];
        }

        // Jika koordinat sekolah belum diset
        if (!$profile || $profile->latitude === null || $profile->longitude === null) {
            return [
                'is_valid' => true,
                'geofence_enabled' => true,
                'distance_m' => 0,
                'radius_m' => $profile?->radius_geofence_m ?? 100,
                'school_lat' => null,
                'school_lng' => null,
                'message' => 'Koordinat lokasi sekolah belum dikonfigurasi. Absensi tetap diizinkan.',
            ];
        }

        // Jika koordinat pengguna tidak disediakan
        if ($userLat === null || $userLng === null) {
            return [
                'is_valid' => false,
                'geofence_enabled' => true,
                'distance_m' => null,
                'radius_m' => $profile->radius_geofence_m ?? 100,
                'school_lat' => $profile->latitude,
                'school_lng' => $profile->longitude,
                'message' => 'Koordinat GPS wajib diaktifkan untuk melakukan presensi.',
            ];
        }

        $distance = $this->calculateDistance($userLat, $userLng, (float) $profile->latitude, (float) $profile->longitude);
        $radius = (int) ($profile->radius_geofence_m ?? 100);
        $isValid = $distance <= $radius;

        return [
            'is_valid' => $isValid,
            'geofence_enabled' => true,
            'distance_m' => $distance,
            'radius_m' => $radius,
            'school_lat' => (float) $profile->latitude,
            'school_lng' => (float) $profile->longitude,
            'message' => $isValid
                ? "Lokasi valid. Anda berada dalam radius sekolah ({$distance} meter)."
                : "Lokasi Anda berada di luar radius sekolah. Jarak: {$distance} meter (maksimal {$radius} meter).",
        ];
    }
}
