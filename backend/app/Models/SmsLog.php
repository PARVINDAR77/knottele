<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSmsLog
 */
class SmsLog extends Model
{
    protected $fillable = [
        'to',
        'from',
        'message',
        'otp_code',
        'purpose',
        'status',
        'message_id',
        'http_code',
        'duration_ms',
        'ip_address',
        'error_message',
        'response_payload',
        'metadata',
        'sent_at',
        'delivered_at',
        'verified_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'duration_ms' => 'float',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Mobile attribute alias for 'to'
     */
    public function getMobileAttribute()
    {
        return $this->to;
    }

    public function setMobileAttribute($value)
    {
        $this->attributes['to'] = $value;
    }

    /**
     * Gateway message id alias
     */
    public function getGatewayMessageIdAttribute()
    {
        return $this->message_id;
    }

    public function setGatewayMessageIdAttribute($value)
    {
        $this->attributes['message_id'] = $value;
    }
}
