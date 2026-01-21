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
        Schema::create('location_histories', function (Blueprint $table) {
            $table->id();
            // Relacionamos con la tabla de dispositivos
            $table->foreignId('device_id')->constrained()->onDelete('cascade');

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('battery_level')->nullable();
            $table->string('activity')->nullable();

            $table->timestamps(); // Esto nos dara la fecha y hora exacta del punto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_histories');
    }
};
