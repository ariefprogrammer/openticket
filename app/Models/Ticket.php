<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi (Mass Assignment)
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'priority',
        'status',
        'amount',
        'admin_note',
    ];

    /**
     * Relasi ke User (Klien yang membuat tiket)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Payment (Satu tiket punya satu data pembayaran)
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}