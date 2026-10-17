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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('wilayah');
            $table->string('nama_karyawan');
            $table->string('nik')->unique();
            $table->string('nip')->unique();
            $table->string('status_kontrak'); // PKWT, PKWTT, Probation, dll.
            $table->string('status_karyawan')->default('Aktif'); // Aktif, Resign, Non-Aktif
            $table->string('jabatan');
            
            $table->string('lama_kontrak')->nullable(); // e.g. "12 Bulan"
            $table->date('periode_kontrak_mulai')->nullable();
            $table->date('periode_kontrak_selesai')->nullable();
            
            $table->date('tgl_lahir');
            $table->date('mulai_bekerja');
            
            $table->string('bpjs_kes')->nullable();
            $table->string('bpjs_tk')->nullable();
            
            $table->date('tgl_resign')->nullable();
            $table->text('alasan_resign')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
