<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerOtp extends Model
{
    protected $fillable = [
        'mobile',
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * Check if the OTP is currently expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if max attempts reached.
     */
    public function isMaxAttemptsReached(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }
}
