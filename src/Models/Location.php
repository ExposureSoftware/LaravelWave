<?php

namespace ExposureSoftware\LaravelWave\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Location
 *
 * @package ExposureSoftware\LaravelWave\Models
 * @method Builder hasDevices(Builder $query)
 */
class Location extends Model
{
    public $incrementing = false;

    protected $table = 'zway_locations';
    protected $fillable = [
        'id',
        'name',
    ];

    public function scopeHasDevices(Builder $query): Builder
    {
        return $query->whereHas('devices')
            ->join('zway_locations', 'locations.id', '=', 'zway_devices.location');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'location');
    }
}
