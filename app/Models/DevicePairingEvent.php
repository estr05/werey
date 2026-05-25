<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevicePairingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'event_type',
        'previous_device_uuid',
        'new_device_uuid',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Relación: Un evento pertenece a un dispositivo.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
