<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Car extends Model
{
    protected $fillable = [
        'owner_name',
        'owner_phone',
        'car_name',
        'car_number',
        'car_letter',
        'car_image',
        'car_description',
    ];

    public static function booted()
    {
        static::deleting(function ($model) {
            if ($model->car_image) {
                Storage::delete('public/media/' . $model->car_image);
            }
        });
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('start_date', '<=', now())->where('end_date', '>=', now());
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
