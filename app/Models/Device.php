<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    // Campos que permitiremos llenar desde la API y el controlador
    protected $fillable = [
        'alias',
        'identifier',
        'latitude',
        'longitude',
        'battery_level',
        'is_charging',
        'connection_type',
        'activity',
        'screen_active',
        'last_seen'
    ];

    /**
     * Casts de atributos.
     * Esto asegura que Laravel trate los datos correctamente al convertirlos a JSON para Flutter.
     */
    protected $casts = [
        'is_charging'   => 'boolean', // Convierte 0/1 de la DB a true/false
        'screen_active' => 'boolean',
        'latitude'      => 'double',
        'longitude'     => 'double',
        'last_seen'     => 'datetime', // Permite usar funciones de fecha como diffForHumans()
    ];

    /**
     * Relación: Un dispositivo tiene muchos registros de historial.
     * Esta relación permitirá al Dashboard graficar las rutas y el consumo de batería.
     */
    public function locationHistories(): HasMany
    {
        return $this->hasMany(LocationHistory::class);
    }
}