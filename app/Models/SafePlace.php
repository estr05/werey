<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafePlace extends Model
{
    use HasFactory;

    protected $table = 'safe_places';

    protected $fillable = [
        'device_id',
        'name',
        'latitude',
        'longitude',
        'radius_meters'
    ];

    protected $casts = [
        'latitude'      => 'double',
        'longitude'     => 'double',
        'radius_meters' => 'integer'
    ];

    /**
     * Relación inversa: Un lugar seguro pertenece a un dispositivo.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
