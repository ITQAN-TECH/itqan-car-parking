<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'ticket_id',
        'price',
        'payment_method',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
