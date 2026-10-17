# System Architecture & API Blueprint: Perizinan Cuti & Approval Workflow

## 1. Executive Summary
Sistem Perizinan Cuti & Pengajuan Cuti Approval dirancang untuk mengelola seluruh siklus izin karyawan:
1. **Pengajuan Cuti / Izin Karyawan**: Karyawan mengajukan cuti dengan jenis cuti, rentang tanggal, jumlah hari, alasan, dan bukti/lampiran pendukung.
2. **Approval Workflow**: Persetujuan bertingkat (Manager / HR / Admin) dengan aksi Approve, Reject, atau Pending, dilengkapi catatan persetujuan dan riwayat audit waktu.
3. **Sinkronisasi Presensi Otomatis**: Saat cuti disetujui (Approved), sistem secara otomatis mencatat / meng-update status di tabel `attendances` harian menjadi `Cuti` atau `Izin` agar rekapitulasi gaji & kehadiran akurat.
4. **Open RESTful API**: Endpoint lengkap berbasis token Sanctum untuk integrasi dengan sistem pihak ketiga (ERP, Aplikasi Mobile, Payroll).

---

## 2. Mock API Contracts & Data Payloads

### A. List & Filter Master Jenis Cuti (`GET /api/leaves/types`)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama_tipe": "Cuti Tahunan",
      "kategori": "Cuti",
      "kuota_hari_pertahun": 12,
      "memerlukan_lampiran": false,
      "potong_cuti_tahunan": true
    },
    {
      "id": 2,
      "nama_tipe": "Izin Sakit",
      "kategori": "Sakit",
      "kuota_hari_pertahun": 14,
      "memerlukan_lampiran": true,
      "potong_cuti_tahunan": false
    }
  ]
}
```

### B. Pengajuan Cuti Baru (`POST /api/leaves/requests`)
- **Request Body**:
```json
{
  "employee_id": 1,
  "leave_type_id": 1,
  "tanggal_mulai": "2026-10-15",
  "tanggal_selesai": "2026-10-17",
  "jumlah_hari": 3,
  "alasan": "Keperluan keluarga di luar kota",
  "lampiran": "(file binary opsional / base64)"
}
```
- **Response**:
```json
{
  "success": true,
  "message": "Pengajuan cuti berhasil dikirim dan menunggu persetujuan.",
  "data": {
    "uuid": "43e2e8da-2be0-449e-ba19-798888bdf1b2",
    "status_persetujuan": "Pending",
    "jumlah_hari": 3,
    "tanggal_mulai": "2026-10-15",
    "tanggal_selesai": "2026-10-17"
  }
}
```

### C. Approval / Rejection Action (`POST /api/leaves/requests/{uuid}/action`)
- **Request Body**:
```json
{
  "action": "approve", // "approve" atau "reject"
  "catatan_persetujuan": "Disetujui. Selamat berlibur."
}
```
- **Response**:
```json
{
  "success": true,
  "message": "Pengajuan cuti berhasil disetujui (Approved).",
  "data": {
    "uuid": "43e2e8da-2be0-449e-ba19-798888bdf1b2",
    "status_persetujuan": "Approved",
    "approved_by": "Admin HR",
    "approved_at": "2026-10-11 08:30:00"
  }
}
```

---

## 3. UI Flow (Inertia + Svelte 5)
- **Index List & Filter Page**: `/leave-requests`
  - Ringkasan KPI: Menunggu Approval (Pending), Disetujui (Approved), Ditolak (Rejected), Total Cuti Terpakai.
  - Tab Filter: Semua, Menunggu Approval, Disetujui, Ditolak.
  - Modal-free Dedicated Actions & Inline Approval Bar.
- **Pengajuan Cuti (Create Form)**: `/leave-requests/create`
  - Pemilihan karyawan & tipe cuti interaktif.
  - Perhitungan hari otomatis dari `tanggal_mulai` s/d `tanggal_selesai`.
  - Drag & drop upload lampiran (Surat Dokter / Dokumen).
- **Detail & Audit Trail (Show / Approval Page)**: `/leave-requests/:uuid`
  - Informasi lengkap pengaju, kuota cuti tersisa, timeline riwayat status, dan tombol aksi Approval (Setujui / Tolak) dengan catatan.

[Faust] 📜 Blueprint & Device Profile selesai. Kontrak API dikunci.
