<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'external_id',
        'payment_id',
        'amount',
        'status',
        'payment_url',
        'expiry_date',
        'paid_at',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expiry_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    const STATUS_PENDING = 'PENDING';

    const STATUS_PAID = 'PAID';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }
}
