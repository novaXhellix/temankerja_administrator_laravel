<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'attendance_location_id' => ['required', 'exists:attendance_locations,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'foto' => ['nullable', 'image', 'max:5120'], // Max 5MB
            'catatan' => ['nullable', 'string', 'max:500'],
            'bypass_geofence' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_location_id.required' => 'Titik lokasi absensi wajib dipilih.',
            'attendance_location_id.exists' => 'Titik lokasi absensi tidak valid.',
            'latitude.required' => 'Koordinat Latitude GPS perangkat wajib disertakan.',
            'longitude.required' => 'Koordinat Longitude GPS perangkat wajib disertakan.',
            'foto.image' => 'File bukti presensi harus berupa gambar (JPG/PNG/WEBP).',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
