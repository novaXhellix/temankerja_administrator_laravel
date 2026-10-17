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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('jenis_kelamin', 20)->nullable()->after('tgl_lahir'); // Laki-laki / Perempuan
            $table->string('tempat_lahir')->nullable()->after('jenis_kelamin');
            $table->string('agama', 30)->nullable()->after('tempat_lahir');
            $table->string('status_pernikahan', 30)->nullable()->after('agama'); // Belum Menikah, Menikah, Cerai
            $table->string('pendidikan_terakhir', 50)->nullable()->after('status_pernikahan'); // SMA, D3, S1, S2, S3
            
            $table->string('email_pribadi')->nullable()->after('nip');
            $table->string('no_telepon')->nullable()->after('email_pribadi');
            $table->text('alamat_ktp')->nullable()->after('no_telepon');
            $table->text('alamat_domisili')->nullable()->after('alamat_ktp');
            
            $table->string('npwp')->nullable()->after('bpjs_tk');
            $table->string('nama_bank', 50)->nullable()->after('npwp');
            $table->string('no_rekening', 50)->nullable()->after('nama_bank');
            $table->string('pemilik_rekening')->nullable()->after('no_rekening');
            
            $table->string('kontak_darurat_nama')->nullable()->after('pemilik_rekening');
            $table->string('kontak_darurat_hubungan')->nullable()->after('kontak_darurat_nama');
            $table->string('kontak_darurat_telepon')->nullable()->after('kontak_darurat_hubungan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_kelamin',
                'tempat_lahir',
                'agama',
                'status_pernikahan',
                'pendidikan_terakhir',
                'email_pribadi',
                'no_telepon',
                'alamat_ktp',
                'alamat_domisili',
                'npwp',
                'nama_bank',
                'no_rekening',
                'pemilik_rekening',
                'kontak_darurat_nama',
                'kontak_darurat_hubungan',
                'kontak_darurat_telepon',
            ]);
        });
    }
};
