<?php
/**
 * ExposureSoftware
 */

namespace ExposureSoftware\LaravelWave\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    public $fillable = [
        'device_id',
        'probe_title',
        'scale_title',
        'level',
        'icon',
        'title',
    ];
    protected $table = 'zway_metrics';

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }
}
