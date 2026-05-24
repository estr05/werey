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
        Schema::table('location_histories', function (Blueprint $table) {
            $table->double('speed_kmh')->nullable()->after('movement_type');
            $table->integer('intervalo_aplicado')->nullable()->after('speed_kmh');
            $table->string('motivo')->nullable()->after('intervalo_aplicado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            $table->dropColumn(['speed_kmh', 'intervalo_aplicado', 'motivo']);
        });
    }
};
