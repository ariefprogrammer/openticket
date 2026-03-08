<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'ticket_id',
        'external_id',
        'payment_channel',
        'amount',
        'status',
        'checkout_url',
        'doku_payment_datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
