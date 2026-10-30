<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'options',
        'label',
        'description',
        'is_encrypted',
        'is_public',
        'sort_order',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
        'options' => 'array',
    ];

    /**
     * Automatic Cache Invalidation on Setting Changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_public_store_settings');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_homepage_media_v2');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_public_store_settings');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_homepage_media_v2');
        });
    }
}
