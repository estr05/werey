<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Habilitar PostGIS en la base de datos de Supabase
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');

        // 2. Modificar la tabla 'devices'
        Schema::table('devices', function (Blueprint $table) {
            // Columna espacial PostGIS para reemplazar lat/lng numéricos
            $table->geography('location', subtype: 'point', srid: 4326)->nullable();
            
            // Tiempos exactos para distinguir "conectado" vs "moverse"
            $table->timestamp('last_location_at')->nullable()->after('last_seen');
            
            // Precisión del último GPS recibido
            $table->float('last_accuracy')->nullable();
        });

        // 3. Modificar la tabla 'location_histories'
        Schema::table('location_histories', function (Blueprint $table) {
            // Columna geoespacial para la bitácora
            $table->geography('location', subtype: 'point', srid: 4326)->nullable();
            
            // Timestamp físico del teléfono (vital para offline sync)
            $table->timestamp('captured_at')->nullable();
            
            // Precisión (accuracy) para descartar GPS drift
            $table->float('accuracy')->nullable();
        });

        // 4. Crear índices espaciales (GIST) y temporales (BRIN)
        DB::statement('CREATE INDEX devices_location_gist ON devices USING GIST (location);');
        DB::statement('CREATE INDEX location_histories_location_gist ON location_histories USING GIST (location);');
        
        // Índice BRIN: Mucho más eficiente que B-Tree para series de tiempo
        DB::statement('CREATE INDEX location_histories_captured_at_brin ON location_histories USING BRIN (captured_at);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS devices_location_gist;');
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['location', 'last_location_at', 'last_accuracy']);
        });

        DB::statement('DROP INDEX IF EXISTS location_histories_location_gist;');
        DB::statement('DROP INDEX IF EXISTS location_histories_captured_at_brin;');
        Schema::table('location_histories', function (Blueprint $table) {
            $table->dropColumn(['location', 'captured_at', 'accuracy']);
        });
    }
};
