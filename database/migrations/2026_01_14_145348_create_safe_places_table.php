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
        Schema::create('safe_places', function (Blueprint $table) {
            $table->id();
            // Relacionamos el lugar con un dispositivo
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ej: "Casa de los abuelos"
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('radius_meters')->default(150); // El círculo de confianza
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_places');
    }
};
