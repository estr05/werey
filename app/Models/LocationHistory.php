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
        'battery_level', 
        'is_charging',    // Capturado en la migración de detalles
        'connection_type', // Capturado en la migración de detalles
        'activity',
        'movement_type',
        'screen_active'
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
        'battery_level' => 'integer',
        'created_at'  => 'datetime',
    ];

    /**
     * Relación inversa: Cada registro de historial pertenece a un único dispositivo.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}