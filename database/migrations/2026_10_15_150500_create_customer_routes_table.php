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
        Schema::create('customer_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            $table->string('nama_rute')->nullable();
            $table->string('tempat_muat');
            $table->string('tempat_bongkar');
            $table->string('kargo');
            $table->decimal('km', 8, 2);
            $table->decimal('tarif_standar', 15, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_routes');
    }
};
