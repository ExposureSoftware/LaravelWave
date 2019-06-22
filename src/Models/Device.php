<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $table = 'zway_devices';
    protected $keyType = 'string';
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
    ];

    protected function metrics(): HasOne
    {
        return $this->hasOne(Metric::class, 'device_id');
    }
}
