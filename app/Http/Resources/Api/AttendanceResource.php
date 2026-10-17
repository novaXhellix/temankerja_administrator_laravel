<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'employee' => [
                'id' => $this->employee?->id,
                'nama' => $this->employee?->nama_karyawan,
                'nip' => $this->employee?->nip,
                'jabatan' => $this->employee?->jabatan,
            ],
            'company' => [
                'id' => $this->company?->id,
                'nama' => $this->company?->nama_perusahaan,
                'kode' => $this->company?->kode_perusahaan,
            ],
            'work_shift' => $this->workShift ? [
                'id' => $this->workShift->id,
                'nama' => $this->workShift->nama_shift,
                'kode' => $this->workShift->kode_shift,
                'jam_masuk' => substr($this->workShift->jam_masuk, 0, 5),
                'jam_keluar' => substr($this->workShift->jam_keluar, 0, 5),
                'toleransi_keterlambatan_menit' => $this->workShift->toleransi_keterlambatan_menit,
            ] : null,
            'location' => $this->location ? [
                'id' => $this->location->id,
                'nama_lokasi' => $this->location->nama_lokasi,
                'radius_meter' => $this->location->radius_meter,
                'check_type' => $this->location->check_type,
            ] : null,
            'tanggal' => $this->tanggal instanceof \Carbon\Carbon ? $this->tanggal->format('Y-m-d') : $this->tanggal,
            'jam_masuk' => $this->jam_masuk ? substr($this->jam_masuk, 0, 8) : null,
            'jam_keluar' => $this->jam_keluar ? substr($this->jam_keluar, 0, 8) : null,
            'status_kehadiran' => $this->status_kehadiran,
            'menit_keterlambatan' => (int) $this->menit_keterlambatan,
            'geofence_masuk' => [
                'is_in_geofence' => (bool) $this->is_in_geofence_masuk,
                'jarak_meter' => $this->jarak_meter_masuk,
                'latitude' => $this->latitude_masuk,
                'longitude' => $this->longitude_masuk,
            ],
            'geofence_keluar' => [
                'is_in_geofence' => (bool) $this->is_in_geofence_keluar,
                'jarak_meter' => $this->jarak_meter_keluar,
                'latitude' => $this->latitude_keluar,
                'longitude' => $this->longitude_keluar,
            ],
            'foto_masuk_url' => $this->foto_masuk_path ? asset('storage/' . $this->foto_masuk_path) : null,
            'foto_keluar_url' => $this->foto_keluar_path ? asset('storage/' . $this->foto_keluar_path) : null,
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
