<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name',
        'type',
        'price',
        'duration_of_receiving_the_car',
    ];

    protected $casts = [
        'price' => 'float',
        'duration_of_receiving_the_car' => 'integer',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_locations');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}
