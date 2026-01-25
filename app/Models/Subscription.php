<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'car_id',
        'location_id',
        'start_date',
        'end_date',
        'price',
        'type',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function getIsActiveAttribute()
    {
        return ($this->end_date >= now() && $this->start_date <= now()) ? true : false;
    }

    protected static function booted()
    {
        static::retrieved(function (self $model) {
            $model->update([
                'is_active' => ($model->end_date >= now() && $model->start_date <= now()) ? true : false,
            ]);
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function isActive()
    {
        return $this->is_active;
    }
}
