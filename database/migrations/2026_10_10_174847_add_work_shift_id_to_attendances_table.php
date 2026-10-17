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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('work_shift_id')->nullable()->after('attendance_location_id')->constrained('work_shifts')->nullOnDelete();
            $table->boolean('is_in_geofence_masuk')->default(true)->after('longitude_masuk');
            $table->boolean('is_in_geofence_keluar')->default(true)->after('longitude_keluar');
            $table->decimal('jarak_meter_masuk', 8, 2)->nullable()->after('is_in_geofence_masuk');
            $table->decimal('jarak_meter_keluar', 8, 2)->nullable()->after('is_in_geofence_keluar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['work_shift_id']);
            $table->dropColumn([
                'work_shift_id',
                'is_in_geofence_masuk',
                'is_in_geofence_keluar',
                'jarak_meter_masuk',
                'jarak_meter_keluar',
            ]);
        });
    }
};
