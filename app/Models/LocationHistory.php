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
        'location',           // PostGIS Geography
        'accuracy',           // Precisión del GPS en metros
        'altitude',           // Altitud en metros (GPS)
        'speed',              // Velocidad cruda original en m/s
        'smoothed_speed',     // Velocidad suavizada en m/s (clasificador móvil)
        'bearing',            // Dirección/Orientación física (0-359.99)
        'battery_level',
        'is_charging',
        'connection_type',
        'activity',           // tracking_state mapeado a activity
        'movement_type',
        'screen_active',
        'signal_strength',
        'has_internet',
        'tracking_state',     // Estado de rastreo original enviado por el móvil
        'zone_name',          // Nombre de la zona segura activa
        'is_safe_zone',       // true si estaba en zona segura
        'speed_kmh',
        'intervalo_aplicado',
        'motivo',
        'captured_at',        // Hora real generada en el teléfono
        'raw_latitude',       // Coordenada cruda antes del filtrado espacial
        'raw_longitude',      // Coordenada cruda antes del filtrado espacial
        'confidence_score',   // Confianza del filtro espacial (0-100)
        'is_outlier',         // true si el frame fue clasificado como outlier
    ];

    /**
     * Casts de atributos.
     * Crucial para que las gráficas de batería y mapas en la web reciban datos limpios.
     */
    protected $casts = [
        'is_charging'      => 'boolean',
        'screen_active'    => 'boolean',
        'has_internet'     => 'boolean',
        'is_safe_zone'     => 'boolean',
        'is_outlier'       => 'boolean',
        'latitude'         => 'double',
        'longitude'        => 'double',
        'accuracy'         => 'double',
        'altitude'         => 'double',
        'speed'            => 'double',
        'smoothed_speed'   => 'double',
        'bearing'          => 'double',
        'battery_level'    => 'integer',
        'speed_kmh'        => 'double',
        'intervalo_aplicado' => 'integer',
        'confidence_score' => 'integer',
        'captured_at'      => 'datetime',
        'created_at'       => 'datetime',
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