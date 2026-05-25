<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega los campos faltantes que el móvil envía pero que no se persistían:
     *   - altitude: altitud en metros (del GPS)
     *   - speed: velocidad cruda original en m/s (del GPS)
     *   - smoothed_speed: velocidad suavizada en m/s (del clasificador móvil)
     *   - is_safe_zone: indica si el dispositivo estaba en zona segura al capturar
     */
    public function up(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            $table->double('altitude')->nullable()->after('accuracy')
                ->comment('Altitud en metros sobre el nivel del mar (GPS)');

            $table->double('speed')->nullable()->after('altitude')
                ->comment('Velocidad cruda original en m/s reportada por el GPS');

            $table->double('smoothed_speed')->nullable()->after('speed')
                ->comment('Velocidad suavizada en m/s (promedio ventana deslizante del clasificador móvil)');

            $table->boolean('is_safe_zone')->nullable()->after('zone_name')
                ->comment('true si el dispositivo estaba dentro de una zona segura al momento de la captura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_histories', function (Blueprint $table) {
            $table->dropColumn(['altitude', 'speed', 'smoothed_speed', 'is_safe_zone']);
        });
    }
};
