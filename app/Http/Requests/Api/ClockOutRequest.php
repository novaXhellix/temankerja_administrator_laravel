<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ClockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'attendance_id' => ['nullable', 'exists:attendances,id'],
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
            'latitude.required' => 'Koordinat Latitude GPS perangkat wajib disertakan.',
            'longitude.required' => 'Koordinat Longitude GPS perangkat wajib disertakan.',
            'foto.image' => 'File bukti presensi harus berupa gambar (JPG/PNG/WEBP).',
            'foto.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
