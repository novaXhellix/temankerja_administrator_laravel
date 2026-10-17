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
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('nama_shift'); // e.g. "Shift Pagi (Office)", "Shift Malam (Ops)"
            $table->string('kode_shift', 30)->nullable(); // e.g. "SH-PAGI", "SH-MLM"
            $table->time('jam_masuk'); // e.g. 08:00
            $table->time('jam_keluar'); // e.g. 17:00
            $table->integer('toleransi_keterlambatan_menit')->default(15); // Toleransi keterlambatan dalam menit
            $table->boolean('is_night_shift')->default(false); // True jika melewati tengah malam (misal 22:00 - 06:00)
            $table->string('status', 20)->default('Aktif'); // Aktif, Non-Aktif
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        // Add optional work_shift_id column to employees table if not present
        if (!Schema::hasColumn('employees', 'work_shift_id')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->foreignId('work_shift_id')->nullable()->after('company_id')->constrained('work_shifts')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['work_shift_id']);
            $table->dropColumn('work_shift_id');
        });

        Schema::dropIfExists('work_shifts');
    }
};
