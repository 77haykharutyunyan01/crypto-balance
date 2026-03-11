<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const DIRECTION_CREDIT = 'credit';
    public const DIRECTION_DEBIT = 'debit';

    protected $fillable = [
        'crypto_account_id',
        'type',
        'direction',
        'amount',
        'status',
        'risk_level',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'meta' => 'array',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(CryptoAccount::class, 'crypto_account_id');
    }
}
