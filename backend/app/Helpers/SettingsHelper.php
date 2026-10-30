<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsHelper
{
    /**
     * Get setting value with caching
     */
    public static function get(string $key, $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            if (($value === null || $value === '') && $default !== null) {
                return $default;
            }

            if ($setting->is_encrypted && $value) {
                $value = decrypt($value);
            }

            // Cast based on type
            switch ($setting->type) {
                case 'boolean':
                case 'checkbox':
                    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                case 'number':
                case 'integer':
                    return is_numeric($value) ? (int) $value : 0;
                case 'decimal':
                case 'float':
                    return is_numeric($value) ? (float) $value : 0.0;
                case 'array':
                case 'json':
                    return $value ? json_decode($value, true) : [];
                default:
                    return $value ?? '';
            }
        });
    }

    /**
     * Get multiple settings at once
     */
    public static function getMultiple(array $keys): array
    {
        $settings = [];

        foreach ($keys as $key) {
            $settings[$key] = self::get($key);
        }

        return $settings;
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(string $key = null): void
    {
        if ($key) {
            Cache::forget("setting.{$key}");
        } else {
            // Clear all settings cache
            $settings = Setting::pluck('key')->toArray();
            foreach ($settings as $settingKey) {
                Cache::forget("setting.{$settingKey}");
            }
            Cache::forget('settings.public');
        }
    }

    /**
     * Get all public settings
     */
    public static function getPublicSettings(): array
    {
        return Cache::remember('settings.public', 3600, function () {
            $settings = Setting::where('is_public', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get();

            $result = [];

            foreach ($settings as $setting) {
                $result[$setting->key] = self::getSettingValue($setting);
            }

            return $result;
        });
    }

    /**
     * Get setting value from model
     */
    private static function getSettingValue(Setting $setting): mixed
    {
        $value = $setting->value;

        if ($setting->is_encrypted && $value) {
            $value = decrypt($value);
        }

        // Cast based on type
        switch ($setting->type) {
            case 'boolean':
            case 'checkbox':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
            case 'integer':
                return is_numeric($value) ? (int) $value : 0;
            case 'decimal':
            case 'float':
                return is_numeric($value) ? (float) $value : 0.0;
            case 'array':
            case 'json':
                return $value ? json_decode($value, true) : [];
            default:
                return $value ?? '';
        }
    }

    /**
     * Get currency symbol
     */
    public static function currencySymbol(): string
    {
        $currency = self::get('currency', 'USD');

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'INR' => '₹'
        ];

        return $symbols[$currency] ?? '$';
    }

    /**
     * Get store information
     */
    public static function storeInfo(): array
    {
        return [
            'name' => self::get('store_name', 'Dr. Kinjal'),
            'email' => self::get('store_email', 'DrKinjal.official@gmail.com'),
            'phone' => self::get('store_phone', '9428289077'),
            'phone_alt' => self::get('store_phone_alt', ''),
            'address' => self::get('store_address', "Abha Multi Speciality Clinic\nA-401, Hanuman Madhi, Golden Club,\n402, Nirmala Convent Rd, Opp. Chetna Medical Store,\nRajkot, Gujarat - 360007"),
            'currency' => self::get('currency', 'INR'),
            'currency_symbol' => self::currencySymbol()
        ];
    }

    /**
     * Get SEO settings
     */
    public static function seoSettings(): array
    {
        return [
            'meta_title' => self::get('meta_title', 'Dr. Kinjal - Clinically effective, result oriented products.'),
            'meta_description' => self::get('meta_description', 'Clinically effective, result oriented products by Dr. Kinjal. Quality skincare and beauty solutions.'),
            'meta_keywords' => self::get('meta_keywords', 'skincare, beauty, natural, organic, dr kinjal'),
            'google_analytics' => self::get('google_analytics', '')
        ];
    }

    /**
     * Get payment settings
     */
    public static function paymentSettings(): array
    {
        return [
            'razorpay_enabled' => self::get('razorpay_enabled', true),
            'razorpay_key_id' => self::get('razorpay_key_id', ''),
            'razorpay_key_secret' => self::get('razorpay_key_secret', ''),
            'cod_enabled' => self::get('cod_enabled', true),
            'cod_min_order_value' => self::get('cod_min_order_value', 0.00)
        ];
    }

    /**
     * Get shipping settings
     */
    public static function shippingSettings(): array
    {
        return [
            'default_shipping_rate' => self::get('default_shipping_rate', 99.00),
            'tax_rate' => self::get('tax_rate', 3.0),
            'free_shipping_min' => self::get('free_shipping_min', 999.00),
            'cod_min_order_value' => self::get('cod_min_order_value', 0.00)
        ];
    }
}
