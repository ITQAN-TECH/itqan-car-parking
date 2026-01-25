<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLocation extends Model
{
    protected $fillable = [
        'employee_id',
        'location_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    
    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
