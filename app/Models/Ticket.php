<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'shift_id',
        'car_id',
        'location_id',
        'employee_opened_id',
        'employee_closed_id',
        'start_time',
        'end_time',
        'type',
        'status',
        'is_requested',
        'requested_time',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'requested_time' => 'datetime',
        'is_requested' => 'boolean',
    ];

    public static function booted()
    {
        static::creating(function ($model) {
            if(Ticket::first()){
                $model->ticket_number = str_pad(max(self::pluck('id')->toArray()) + 1, 10, '0', STR_PAD_LEFT);
            }else{
                $model->ticket_number = '0000000001';
            }
        });
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function employeeOpened()
    {
        return $this->belongsTo(Employee::class, 'employee_opened_id');
    }

    public function employeeClosed()
    {
        return $this->belongsTo(Employee::class, 'employee_closed_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
