<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'employee_id',
        'location_id',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    protected $appends = [
        'duration',
    ];

    public function getDurationAttribute()
    {
        return $this->end_time ? round($this->start_time->diffInSeconds($this->end_time)) : round($this->start_time->diffInSeconds(now()));
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketsOpened()
    {
        return $this->hasMany(Ticket::class, 'shift_id');
    }

}
