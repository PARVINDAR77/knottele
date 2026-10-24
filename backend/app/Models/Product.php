<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @mixin IdeHelperProduct
 */
class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'product_type',
        'brand_id',
        'main_category_id',
        'tax_class_id',
        'short_description',
        'description',
        'status',
        'is_featured',
        'is_new',
        'is_bestseller',
        'weight',
        'length',
        'width',
        'height',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'cod_available',
        'product_code',
        'sort_order',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'is_bestseller' => 'boolean',
        'cod_available' => 'boolean',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
    ];

    // Relationships
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function getRatingAttribute()
    {
        // Use loaded reviews if available relative to performance, or simple avg
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->avg('rating') ?? 0;
        }
        // Fallback or optimized query could go here, but for now simple 0.
        // Ideally we might want to store avg_rating in product table and update it on review save,
        // but dynamic is fine for now.
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute()
    {
        if ($this->relationLoaded('reviews')) {
            return $this->reviews->count();
        }
        return $this->reviews()->count();
    
}

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps();
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'related_products', 'product_id', 'related_product_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function crossSellProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'cross_sell_products', 'product_id', 'cross_sell_product_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function upsellProducts(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'upsell_products', 'product_id', 'upsell_product_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }



    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 1);
    }

    public function seoMetadata(): HasOne
    {
        return $this->hasOne(SeoMetadata::class, 'entity_id')
            ->where('entity_type', self::class);
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class);
    }

    public function wishlistItems(): HasManyThrough
    {
        return $this->hasManyThrough(WishlistItem::class, ProductVariant::class);
    }

    public function cartItems(): HasManyThrough
    {
        return $this->hasManyThrough(CartItem::class, ProductVariant::class);
    }

    // Accessors for simple product compatibility
    public function getPriceAttribute()
    {
        return $this->defaultVariant->price ?? 0;
    }

    public function getSkuAttribute()
    {
        return $this->defaultVariant->sku ?? null;
    }

    public function getComparePriceAttribute()
    {
        return $this->defaultVariant->compare_price ?? null;
    }

    public function getStockQuantityAttribute()
    {
        return $this->defaultVariant->stock_quantity ?? 0;
    }

    public function getStockStatusAttribute()
    {
        return $this->defaultVariant->stock_status ?? 'out_of_stock';
    }
    
    // Helper to get Main Image from Default Variant's Primary Image
    public function getMainImageAttribute()
    {
        $variant = $this->defaultVariant ?: $this->variants()->first();
        if (!$variant) return null;

        $images = $variant->images;
        if ($images->isEmpty()) return null;

        $primaryImage = $images->where('pivot.is_primary', 1)->first() ?: $images->first();
        return $primaryImage ? $primaryImage->url : null;
    }

    public function getMainImageUrlAttribute()
    {
        return $this->getMainImageAttribute() ?: '/images/logo/Logo_1.png';
    }

    public function getMainImageIdAttribute()
    {
        $variant = $this->defaultVariant ?: $this->variants()->first();
        if (!$variant) return null;
        
        $primaryImage = $variant->images->where('pivot.is_primary', 1)->first();
        if ($primaryImage) {
            return $primaryImage->id;
        }
        
        return $variant->images->first()?->id ?? null;
    }
    
    public function getManageStockAttribute()
    {
        return true; // Default or column on product? Migration didn't show manage_stock on products table.
        // Assuming true for now as migration didn't have it.
    }
}