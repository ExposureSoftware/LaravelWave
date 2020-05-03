<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    public $incrementing = false;

    protected $table = 'zway_devices';
    protected $keyType = 'string';
    protected $dates = [
        'update_time',
        'creation_time',
    ];
    protected $fillable = [
        'id',
        'device_type',
        'update_time',
        'creation_time',
        'creator_id',
        'has_history',
        'permanently_hidden',
        'probeType',
        'visibility',
        'node_id',
        'location',
    ];

    public function metrics(): HasOne
    {
        return $this->hasOne(Metric::class, 'device_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location');
    }
}
