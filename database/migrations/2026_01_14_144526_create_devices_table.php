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
    Schema::create('devices', function (Blueprint $table) {
        $table->id();
        $table->string('alias'); // Ejemplo: "Celular de mi pareja"
        $table->string('identifier')->unique(); // Un ID único del teléfono
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->integer('battery_level')->nullable();
        $table->boolean('is_charging')->default(false);
        $table->string('connection_type')->nullable(); // wifi / mobile
        $table->string('activity')->default('still'); // En movimiento, quieto, etc.
        $table->boolean('screen_active')->default(false); // El punto • o °
        $table->timestamp('last_seen')->nullable(); // Heartbeat
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
