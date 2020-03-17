<?php

namespace ExposureSoftware\LaravelWave\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $query->with('devices')->whereHas('devices');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class, 'location');
    }
}
