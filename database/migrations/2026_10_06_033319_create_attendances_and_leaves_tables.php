<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Lokasi Absensi Karyawan (Geofencing & Office Location Radius)
        Schema::create('attendance_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('nama_lokasi'); // e.g. "Kantor Pusat Sudirman", "Site Cikarang"
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius_meter')->default(100); // Batas radius geofencing dalam meter
            $table->string('alamat_lengkap')->nullable();
            $table->string('status', 20)->default('Aktif'); // Aktif, Non-Aktif
            $table->timestamps();
        });

        // 2. Tipe & Jenis Perizinan / Cuti (Master Leave Types)
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('nama_tipe'); // e.g. "Cuti Tahunan", "Izin Sakit", "Cuti Melahirkan", "Dinas Luar"
            $table->string('kategori', 30); // Cuti, Izin, Sakit, Dinas
            $table->integer('kuota_hari_pertahun')->default(12);
            $table->boolean('memerlukan_lampiran')->default(false); // Surat Dokter / Bukti Perjalanan
            $table->boolean('potong_cuti_tahunan')->default(true);
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();
        });

        // 3. List & Log Absensi Karyawan (Attendance Logs / Clock-In Clock-Out)
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->constrained('attendance_locations')->nullOnDelete();
            
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_keluar')->nullable();
            
            // Geofencing Coordinates
            $table->decimal('latitude_masuk', 10, 8)->nullable();
            $table->decimal('longitude_masuk', 11, 8)->nullable();
            $table->decimal('latitude_keluar', 10, 8)->nullable();
            $table->decimal('longitude_keluar', 11, 8)->nullable();
            
            $table->string('foto_masuk_path')->nullable();
            $table->string('foto_keluar_path')->nullable();
            
            $table->string('status_kehadiran', 30)->default('Hadir'); // Hadir, Terlambat, Pulang Cepat, Izin, Cuti, Alpa
            $table->integer('menit_keterlambatan')->default(0);
            $table->text('catatan')->nullable();
            
            $table->timestamps();
            
            $table->unique(['employee_id', 'tanggal']);
        });

        // 4. Pengajuan Cuti & Perizinan (Leave & Permission Requests)
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->integer('jumlah_hari');
            $table->text('alasan');
            $table->string('lampiran_path')->nullable();
            
            // Workflow Persetujuan Hirarki (Pending, Approved, Rejected)
            $table->string('status_persetujuan', 30)->default('Pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_persetujuan')->nullable();
            
            $table->timestamps();
        });

        // 5. Rekapitulasi & Pelaporan Absensi Bulanan (Monthly Attendance Summary Reports)
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            
            $table->integer('bulan'); // 1-12
            $table->integer('tahun'); // e.g. 2026
            
            $table->integer('total_hari_kerja')->default(0);
            $table->integer('total_hadir')->default(0);
            $table->integer('total_terlambat')->default(0);
            $table->integer('total_izin')->default(0);
            $table->integer('total_cuti')->default(0);
            $table->integer('total_sakit')->default(0);
            $table->integer('total_alpa')->default(0);
            $table->integer('total_menit_keterlambatan')->default(0);
            
            $table->timestamps();
            
            $table->unique(['employee_id', 'bulan', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reports');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendance_locations');
    }
};
