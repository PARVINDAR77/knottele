<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperCategory
 */
class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'status',
        'featured',
        'show_in_nav',
        'sort_order',
        'image_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'status' => 'boolean',
        'featured' => 'boolean',
        'show_in_nav' => 'boolean',
    ];

    /**
     * Automatic Cache Invalidation on Category Changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_categories_v2');
        });
        static::deleted(function () {
            \Illuminate\Support\Facades\Cache::forget('knotelle_customer_categories_v2');
        });
    }

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps();
    }

    public function primaryProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->wherePivot('is_primary', true);
    }



    public function ancestors()
    {
        return $this->belongsToMany(self::class, 'category_hierarchies', 'descendant_id', 'ancestor_id')
            ->withPivot('depth');
    }

    public function descendants()
    {
        return $this->belongsToMany(self::class, 'category_hierarchies', 'ancestor_id', 'descendant_id')
            ->withPivot('depth');
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'offer_categories');
    }

    public function seoMetadata(): HasOne
    {
        return $this->hasOne(SeoMetadata::class, 'entity_id')
            ->where('entity_type', self::class);
    }
}
