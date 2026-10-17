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
        // 1. Tipe & Kategori Reimbursement (Medical, Transport, Travel, Operational, dll.)
        Schema::create('reimbursement_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('nama_tipe'); // e.g. "Klaim Medis / Rawat Jalan", "Bensin & Parkir", "Makan Lembur", "Perjalanan Dinas"
            $table->string('kode_tipe', 50)->unique();
            $table->decimal('maksimal_klaim', 15, 2)->default(0); // 0 jika tidak ada batas limit nominal
            $table->boolean('memerlukan_struk')->default(true);
            $table->string('status', 20)->default('Aktif');
            $table->timestamps();
        });

        // 2. Pengajuan Klaim & Reimbursement
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nomor_klaim')->unique(); // e.g. "CLM/2026/10/0001"
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('reimbursement_type_id')->constrained('reimbursement_types')->cascadeOnDelete();

            $table->date('tanggal_klaim');
            $table->decimal('nominal', 15, 2);
            $table->text('keterangan');
            $table->string('bukti_nota_path')->nullable(); // Foto / PDF struk

            // Workflow Persetujuan & Pencairan (Pending, Approved, Rejected, Paid)
            $table->string('status', 30)->default('Pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_approval')->nullable();

            $table->date('tanggal_pencairan')->nullable();
            $table->string('metode_pembayaran')->nullable(); // Transfer Bank, Payroll, Kas Kecil

            $table->timestamps();
        });

        // 3. Riwayat Perpanjangan & Tata Kelola Kontrak Karyawan (Contract Lifecycle History)
        Schema::create('employee_contracts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('nomor_kontrak')->nullable(); // e.g. "SPK/TKS/2026/001"
            $table->string('jenis_kontrak', 50); // PKWT 1, PKWT 2, Probation, PKWTT (Tetap), Freelance, Intern
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable(); // Null jika PKWTT
            $table->integer('durasi_bulan')->nullable();

            $table->string('jabatan');
            $table->string('divisi')->nullable();
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->text('catatan_khusus')->nullable();
            $table->string('dokumen_kontrak_path')->nullable(); // PDF Surat Kontrak

            // Status Kontrak: Aktif, Berakhir, Diperpanjang, Diputus
            $table->string('status_kontrak', 30)->default('Aktif');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
        Schema::dropIfExists('reimbursements');
        Schema::dropIfExists('reimbursement_types');
    }
};
