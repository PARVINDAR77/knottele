<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperMedia
 */
class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_name',
        'file_path',
        'mobile_image_path',
        'video_url',
        'disk',
        'mime_type',
        'file_type',
        'content_type',
        'category_name',
        'file_size',
        'duration',
        'audio_name',
        'likes_count',
        'comments_count',
        'views_count',
        'thumbnails',
        'metadata',
        'alt_text',
        'page',
        'section',
        'slot',
        'device',
        'title',
        'subtitle',
        'description',
        'cta_text',
        'cta_link',
        'secondary_cta_text',
        'secondary_cta_link',
        'tag_text',
        'sort_order',
        'is_active',
        'is_featured',
        'uploaded_by',
        'uploader_type',
    ];

    protected $casts = [
        'thumbnails' => 'array',
        'metadata' => 'array',
        'file_size' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'views_count' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    /**
     * Automatic Cache Invalidation on Media Changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_homepage_media_v2');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_about_data');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_contact_data');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_homepage_media_v2');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_about_data');
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_contact_data');
        });
    }

    protected $appends = [
        'url',
        'thumb_url',
        'thumbnail_url',
        'full_url',
        'desktop_image_url',
        'mobile_image_url',
        'video_stream_url',
        'formatted_views',
        'formatted_likes'
    ];

    public function getUrlAttribute(): string
    {
        if (empty($this->file_path)) {
            return asset('images/logo/Logo_1.png');
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        $cleanPath = ltrim($this->file_path, '/');
        $resolvedPath = str_starts_with($cleanPath, 'images/') || str_starts_with($cleanPath, 'storage/')
            ? $cleanPath
            : 'images/' . $cleanPath;

        // If local file exists, serve it directly
        if (file_exists(public_path($resolvedPath))) {
            return asset($resolvedPath);
        }

        // Resilient fallback for missing image files to prevent storefront 404s
        if ($this->section === 'brand_story' || str_contains($cleanPath, 'brand_story')) {
            if (file_exists(public_path('images/homepage/middleimg.png'))) {
                return asset('images/homepage/middleimg.png');
            }
        } elseif ($this->section === 'hero' || str_contains($cleanPath, 'hero')) {
            if (file_exists(public_path('images/hero/hero-enhanced.jpg'))) {
                return asset('images/hero/hero-enhanced.jpg');
            }
        } elseif ($this->section === 'custom_crochet') {
            if (file_exists(public_path('images/homepage/middleimg.png'))) {
                return asset('images/homepage/middleimg.png');
            }
        }

        return asset($resolvedPath);
    }

    public function getDesktopImageUrlAttribute(): string
    {
        return $this->getUrlAttribute();
    }

    public function getMobileImageUrlAttribute(): string
    {
        if (empty($this->mobile_image_path)) {
            return $this->getUrlAttribute();
        }

        if (str_starts_with($this->mobile_image_path, 'http://') || str_starts_with($this->mobile_image_path, 'https://')) {
            return $this->mobile_image_path;
        }

        $cleanPath = ltrim($this->mobile_image_path, '/');
        $resolvedPath = str_starts_with($cleanPath, 'images/') || str_starts_with($cleanPath, 'storage/')
            ? $cleanPath
            : 'images/' . $cleanPath;

        if (file_exists(public_path($resolvedPath))) {
            return asset($resolvedPath);
        }

        return $this->getUrlAttribute();
    }

    public function getThumbUrlAttribute(): string
    {
        return $this->getUrlAttribute();
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->getUrlAttribute();
    }

    public function getFullUrlAttribute(): string
    {
        return $this->getUrlAttribute();
    }

    public function getVideoStreamUrlAttribute(): ?string
    {
        if (empty($this->video_url)) {
            return null;
        }

        if (str_starts_with($this->video_url, 'http://') || str_starts_with($this->video_url, 'https://')) {
            return $this->video_url;
        }

        $cleanPath = ltrim($this->video_url, '/');

        if (str_starts_with($cleanPath, 'images/') || str_starts_with($cleanPath, 'storage/') || str_starts_with($cleanPath, 'videos/')) {
            return asset($cleanPath);
        }

        return asset('storage/' . $cleanPath);
    }

    public function getFormattedViewsAttribute(): string
    {
        $views = $this->views_count ?: 0;
        if ($views >= 1000000) {
            return round($views / 1000000, 1) . 'M';
        }
        if ($views >= 1000) {
            return round($views / 1000, 1) . 'K';
        }
        return (string) $views;
    }

    public function getFormattedLikesAttribute(): string
    {
        $likes = $this->likes_count ?: 0;
        if ($likes >= 1000000) {
            return round($likes / 1000000, 1) . 'M';
        }
        if ($likes >= 1000) {
            return round($likes / 1000, 1) . 'K';
        }
        return (string) $likes;
    }

    // Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSlot($query, string $page, string $section, string $slot)
    {
        return $query->where('page', $page)
                     ->where('section', $section)
                     ->where('slot', $slot);
    }

    public function scopeForDevice($query, string $device)
    {
        if ($device === 'all') {
            return $query;
        }
        return $query->whereIn('device', [$device, 'all']);
    }

    // Relationships
    public function uploader(): MorphTo
    {
        return $this->morphTo('uploader');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'image_id');
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class, 'logo_id');
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'image_id');
    }

    public function variantImages(): HasMany
    {
        return $this->hasMany(VariantImage::class);
    }

    public function reviewImages(): HasMany
    {
        return $this->hasMany(ReviewImage::class);
    }
}