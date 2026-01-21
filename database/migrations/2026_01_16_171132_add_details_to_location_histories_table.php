<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('location_histories', function (Blueprint $table) {
        // Añadimos las columnas para capturar el estado completo en cada punto
        $table->boolean('is_charging')->default(false)->after('battery_level');
        $table->string('connection_type')->nullable()->after('is_charging');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            //
        });
    }
};
