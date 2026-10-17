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
        Schema::table('work_shifts', function (Blueprint $table) {
            $table->json('schedule_days')->nullable()->after('jam_keluar'); // Detail config per hari (senin-minggu: is_work, jam_masuk, jam_keluar)
            $table->string('work_days')->nullable()->after('schedule_days'); // e.g. "Senin, Selasa, Rabu, Kamis, Jumat"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_shifts', function (Blueprint $table) {
            $table->dropColumn(['schedule_days', 'work_days']);
        });
    }
};
