<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CryptoAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'available_balance',
        'locked_balance',
    ];

    protected $casts = [
        'available_balance' => 'decimal:8',
        'locked_balance' => 'decimal:8',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CryptoTransaction::class);
    }
}

