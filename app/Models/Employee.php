<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable implements LaratrustUser
{
    use HasFactory, Notifiable, HasRolesAndPermissions, HasApiTokens;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'status',
        'type',
        'image',
        'receive_notifications',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'status' => 'boolean',
        'password' => 'hashed',
    ];

    protected $with = [
        'activeShift',
    ];

    protected $appends = [
        'unread_notifications_count',
    ];

    public function getUnreadNotificationsCountAttribute()
    {
        return $this->notifications()->whereNull('read_at')->count();
    }

    public function fcmTokens()
    {
        return $this->morphMany(FcmToken::class, 'tokenable');
    }
    public function locations()
    {
        return $this->belongsToMany(Location::class, 'employee_locations');
    }
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
    public function ticketsOpened()
    {
        return $this->hasMany(Ticket::class, 'employee_opened_id');
    }
    public function ticketsClosed()
    {
        return $this->hasMany(Ticket::class, 'employee_closed_id');
    }
    public function activeShift()
    {
        return $this->hasOne(Shift::class)->where('status', 'open')->where('end_time', null);
    }
    
}
