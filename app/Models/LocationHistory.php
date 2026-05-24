<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationHistory extends Model
{
    use HasFactory;

    protected $table = 'location_histories';

    protected $fillable = [
        'device_id', 
        'latitude', 
        'longitude', 
        'location',      // PostGIS Geography
        'accuracy',      // Precisión del GPS en metros
        'bearing',       // Dirección/Orientación física (0-359.99)
        'captured_at',   // Hora real generada en el teléfono
        'battery_level', 
        'is_charging',    // Capturado en la migración de detalles
        'connection_type', // Capturado en la migración de detalles
        'activity',
        'movement_type',
        'screen_active',
        'speed_kmh',
        'intervalo_aplicado',
        'motivo'
    ];

    /**
     * Casts de atributos.
     * Crucial para que las gráficas de batería y mapas en la web reciban datos limpios.
     */
    protected $casts = [
        'is_charging' => 'boolean',
        'screen_active' => 'boolean',
        'latitude'    => 'double',
        'longitude'   => 'double',
        'accuracy'    => 'double',
        'bearing'     => 'double',
        'battery_level' => 'integer',
        'speed_kmh'   => 'double',
        'intervalo_aplicado' => 'integer',
        'captured_at' => 'datetime',
        'created_at'  => 'datetime',
    ];

    /**
     * Relación inversa: Cada registro de historial pertenece a un único dispositivo.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * Calcula la dirección cardinal (N, NE, E, etc.) de forma dinámica
     * a partir de los grados del bearing, sin guardarlo en la DB.
     */
    public function getCardinalDirectionAttribute(): ?string
    {
        if ($this->bearing === null) {
            return null;
        }

        // Normalizamos por seguridad a 0-359
        $degrees = fmod($this->bearing, 360);
        if ($degrees < 0) {
            $degrees += 360;
        }

        $val = floor(($degrees / 22.5) + 0.5);
        $arr = ["N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW"];
        
        return $arr[($val % 16)];
    }
}