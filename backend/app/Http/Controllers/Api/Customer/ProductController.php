<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * API Response Helper
     */
    private function apiResponse($success = true, $data = null, $message = '', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'data' => $data,
            'message' => $message,
        ], $statusCode);
    }

    private function formatImageUrl($filePath): string
    {
        if (!$filePath) {
            return asset('images/logo/Logo_1.png');
        }
        if (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://')) {
            return $filePath;
        }
        if (str_starts_with($filePath, 'images/')) {
            return asset($filePath);
        }
        if (str_starts_with($filePath, 'storage/')) {
            return asset($filePath);
        }
        return asset('images/' . ltrim($filePath, '/'));
    }

    /**
     * Get all products with filters (for customer listing page)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            $search = $request->get('search', '');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');
            $categoryId = $request->get('category_id');
            $brandId = $request->get('brand_id');
            $minPrice = $request->get('min_price');
            $maxPrice = $request->get('max_price');
            $material = $request->get('material');
            $isFeatured = $request->get('is_featured');
            $isNew = $request->get('is_new');
            $isBestseller = $request->get('is_bestseller');
            $inStockOnly = $request->get('in_stock', false);

            $query = Product::with([
                'brand:id,name',
                'mainCategory:id,name,slug',
                'taxClass.rates',
                'defaultVariant.images',
                'variants' => function ($query) {
                    $query->select('id', 'product_id', 'price', 'compare_price', 'stock_quantity', 'is_default', 'sku')
                          ->with('images');
                }
            ])->where('products.status', 'active'); // Specify table name

            // Search
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('products.name', 'LIKE', "%{$search}%")
                      ->orWhere('products.description', 'LIKE', "%{$search}%")
                      ->orWhere('products.product_code', 'LIKE', "%{$search}%")
                      ->orWhereHas('brand', function ($q) use ($search) {
                          $q->where('name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('mainCategory', function ($q) use ($search) {
                          $q->where('name', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Filters
            if ($categoryId) {
                $query->where('products.main_category_id', $categoryId);
            }

            // Brand filter
            if ($brandId) {
                $query->where('products.brand_id', $brandId);
            }

            if ($isFeatured !== null) {
                $query->where('products.is_featured', filter_var($isFeatured, FILTER_VALIDATE_BOOLEAN));
            }

            if ($isNew !== null) {
                $query->where('products.is_new', filter_var($isNew, FILTER_VALIDATE_BOOLEAN));
            }

            if ($isBestseller !== null) {
                $query->where('products.is_bestseller', filter_var($isBestseller, FILTER_VALIDATE_BOOLEAN));
            }

            // Price range filter
            if ($minPrice || $maxPrice) {
                $query->whereHas('defaultVariant', function ($q) use ($minPrice, $maxPrice) {
                    if ($minPrice) {
                        $q->where('price', '>=', $minPrice);
                    }
                    if ($maxPrice) {
                        $q->where('price', '<=', $maxPrice);
                    }
                });
            }

            // In stock filter
            if ($inStockOnly) {
                $query->whereHas('defaultVariant', function ($q) {
                    $q->where('stock_quantity', '>', 0);
                });
            }

            // Sorting
            $sortMapping = [
                'price_asc' => ['defaultVariant.price', 'asc'],
                'price_desc' => ['defaultVariant.price', 'desc'],
                'newest' => ['created_at', 'desc'],
                'featured' => ['is_featured', 'desc'],
                'popular' => ['is_bestseller', 'desc'],
                'name_asc' => ['name', 'asc'],
                'name_desc' => ['name', 'desc'],
                'default' => ['created_at', 'desc']
            ];

            if (isset($sortMapping[$sortBy])) {
                list($sortColumn, $sortDirection) = $sortMapping[$sortBy];
                if ($sortColumn === 'defaultVariant.price') {
                    // Join with product_variants table (not variants)
                    $query->join('product_variants', function ($join) {
                        $join->on('products.id', '=', 'product_variants.product_id')
                             ->where('product_variants.is_default', true);
                    })->orderBy('product_variants.price', $sortDirection)
                      ->select('products.*');
                } else {
                    $query->orderBy($sortColumn, $sortDirection);
                }
            } else {
                $query->orderBy($sortBy, $sortDir);
            }

            // Paginate
            $products = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform data for customer view
            $transformedData = $products->getCollection()->map(function ($product) {
                $defaultVariant = $product->defaultVariant;
                $mainImage = null;

                // Get main image from default variant
                if ($defaultVariant && $defaultVariant->images && $defaultVariant->images->isNotEmpty()) {
                    $primaryImage = $defaultVariant->images->where('pivot.is_primary', true)->first();
                    if (!$primaryImage) {
                        $primaryImage = $defaultVariant->images->sortBy('pivot.sort_order')->first();
                    }
                    if ($primaryImage && $primaryImage->file_path) {
                        $mainImage = $this->formatImageUrl($primaryImage->file_path);
                    }
                }

                // Fallback to product's default variant images if available
                if (!$mainImage && $product->variants->isNotEmpty()) {
                    foreach ($product->variants as $variant) {
                        if ($variant->images && $variant->images->isNotEmpty()) {
                            $variantImage = $variant->images->where('pivot.is_primary', true)->first();
                            if (!$variantImage) {
                                $variantImage = $variant->images->sortBy('pivot.sort_order')->first();
                            }
                            if ($variantImage && $variantImage->file_path) {
                                $mainImage = $this->formatImageUrl($variantImage->file_path);
                                break;
                            }
                        }
                    }
                }

                // Fallback to placeholder or category image
                if (!$mainImage) {
                    $mainImage = asset('images/logo/Logo_1.png');
                }

                // Calculate discount percentage
                $discountPercent = 0;
                if ($defaultVariant && $defaultVariant->compare_price && $defaultVariant->compare_price > $defaultVariant->price) {
                    $discountPercent = round((($defaultVariant->compare_price - $defaultVariant->price) / $defaultVariant->compare_price) * 100);
                }

                // Determine if product is in stock
                $isInStock = false;
                $totalStock = 0;

                foreach ($product->variants as $variant) {
                    $totalStock += $variant->stock_quantity;
                }
                $isInStock = $totalStock > 0;

                // Collect all images for product card (e.g. hover image and angles)
                $allProductImages = [];
                if ($defaultVariant && $defaultVariant->images && $defaultVariant->images->isNotEmpty()) {
                    $allProductImages = $defaultVariant->images->sortBy('pivot.sort_order')->map(function($img) {
                        return $img->url ?? $this->formatImageUrl($img->file_path);
                    })->values()->toArray();
                }
                if (empty($allProductImages)) {
                    foreach ($product->variants as $variant) {
                        if ($variant->images && $variant->images->isNotEmpty()) {
                            $allProductImages = $variant->images->sortBy('pivot.sort_order')->map(function($img) {
                                return $img->url ?? $this->formatImageUrl($img->file_path);
                            })->values()->toArray();
                            break;
                        }
                    }
                }
                if (empty($allProductImages)) {
                    $allProductImages = [$mainImage];
                }

                return [
                    'id' => $product->id,
                    'slug' => $product->slug,
                    'name' => $product->name,
                    'short_description' => $product->short_description,
                    'main_image' => $mainImage,
                    'price' => $defaultVariant ? (float) $defaultVariant->price : 0,
                    'compare_price' => $defaultVariant ? (float) $defaultVariant->compare_price : null,
                    'tax_rate' => $product->taxClass ? (float) $product->taxClass->total_rate : 0,
                    'discount_percent' => $discountPercent,
                    'is_in_stock' => $isInStock,
                    'stock_quantity' => $totalStock,
                    'stock' => $totalStock,
                    'sku' => $defaultVariant ? $defaultVariant->sku : 'N/A',
                    'brand' => $product->brand ? $product->brand->name : null,
                    'brand_id' => $product->brand_id,
                    'category' => $product->mainCategory ? $product->mainCategory->name : null,
                    'category_id' => $product->main_category_id,
                    'category_slug' => $product->mainCategory ? $product->mainCategory->slug : null,
                    'is_featured' => (bool) $product->is_featured,
                    'is_new' => (bool) $product->is_new,
                    'is_bestseller' => (bool) $product->is_bestseller,
                    'rating' => (float) $product->rating ?? 0,
                    'review_count' => (int) $product->review_count ?? 0,
                    'has_variants' => $product->product_type === 'configurable',
                    'variants_count' => $product->variants->count(),
                    'variants' => $product->variants->map(function($v) {
                        $size = 'Standard';
                        // Infer size from SKU if attributes are missing
                        if (str_ends_with($v->sku, '-08')) $size = '8 Sachets';
                        elseif (str_ends_with($v->sku, '-16')) $size = '16 Sachets';
                        elseif (str_ends_with($v->sku, '-32')) $size = '32 Sachets';
                        elseif (str_ends_with($v->sku, '-250')) $size = '250g';
                        elseif (str_ends_with($v->sku, '-500')) $size = '500g';
                        elseif (str_ends_with($v->sku, '-1KG')) $size = '1 Kg';

                        return [
                            'id' => $v->id,
                            'sku' => $v->sku,
                            'price' => (float)$v->price,
                            'size' => $size,
                        ];
                    })->toArray(),
                    'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $product->created_at ? $product->created_at->format('M d, Y') : null,
                    'images' => $allProductImages, // for ProductCard.jsx and carousels
                    'featured' => (bool) $product->is_featured,
                    'bestseller' => (bool) $product->is_bestseller,
                ];
            });

            // Get available filters for the current result set
            $availableFilters = $this->getAvailableFilters($query);

            return $this->apiResponse(true, [
                'products' => $transformedData,
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'filters' => $availableFilters,
                'sort_options' => [
                    ['value' => 'newest', 'label' => 'Newest First'],
                    ['value' => 'featured', 'label' => 'Featured'],
                    ['value' => 'price_asc', 'label' => 'Price: Low to High'],
                    ['value' => 'price_desc', 'label' => 'Price: High to Low'],
                ]
            ], 'Products retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Customer Product API Error: ' . $e->getMessage());
            return $this->apiResponse(false, null, 'Failed to fetch products', 500);
        }
    }

    /**
     * Get available filter options based on the current product set
     */
    private function getAvailableFilters($query): array
    {
        $filters = [];

        // Categories with counts
        $categories = Category::where('status', 1)
            ->whereNull('parent_id')
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get(['id', 'name', 'slug']);

        $filters['categories'] = $categories;

        // Brands with counts
        $brands = Brand::where('status', 1)
            ->withCount(['products' => function ($q) {
                $q->where('status', 'active');
            }])
            ->get(['id', 'name']);

        $filters['brands'] = $brands;

        // Price range
        $priceRange = DB::table('products')
            ->where('products.status', 'active')
            ->join('product_variants', function ($join) {
                $join->on('products.id', '=', 'product_variants.product_id')
                     ->where('product_variants.is_default', true);
            })
            ->selectRaw('MIN(product_variants.price) as min_price, MAX(product_variants.price) as max_price')
            ->first();

        $filters['price_range'] = [
            'min' => $priceRange->min_price ?? 0,
            'max' => $priceRange->max_price ?? 100000,
        ];

        $filters['materials'] = [];

        return $filters;
    }

    /**
     * Get featured collections
     */
    public function featuredCollections(): JsonResponse
    {
        try {
            $collections = [
                [
                    'id' => 1,
                    'name' => 'Wedding Collection',
                    'slug' => 'wedding-collection',
                    'image' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=400&h=300&fit=crop&auto=format',
                    'description' => 'Elegant pieces for your special day'
                ],
                [
                    'id' => 2,
                    'name' => 'Christmas Special',
                    'slug' => 'christmas-special',
                    'image' => 'https://images.unsplash.com/photo-1581235720845-4c6c41b07c0d?w=400&h=300&fit=crop&auto=format',
                    'description' => 'Festive jewelry for the holiday season'
                ],
                [
                    'id' => 3,
                    'name' => 'Statement Pieces',
                    'slug' => 'statement-pieces',
                    'image' => 'https://images.unsplash.com/photo-1594576722512-582d5577dc56?w=400&h=300&fit=crop&auto=format',
                    'description' => 'Bold and beautiful jewelry pieces'
                ],
            ];

            return $this->apiResponse(true, $collections, 'Featured collections retrieved');
        } catch (\Exception $e) {
            Log::error('Featured collections error: ' . $e->getMessage());
            return $this->apiResponse(false, null, 'Failed to retrieve collections', 500);
        }
    }

/**
 * Get product details by slug
 */
public function show($slug): JsonResponse
{
    try {
        $product = Product::with([
            'brand:id,name',
            'mainCategory:id,name,slug',
            'taxClass.rates',
            'approvedReviews',
            'variants.images',
        ])->where('slug', $slug)
          ->where('status', 'active')
          ->firstOrFail();

        // Get default variant for pricing
        $defaultVariant = $product->variants->where('is_default', true)->first();

        // Get all images from all variants
        $allImages = collect();
        foreach ($product->variants as $variant) {
            if ($variant->images && $variant->images->isNotEmpty()) {
                $allImages = $allImages->merge($variant->images);
            }
        }

        $mainImage = null;

        // Get main image from default variant
        if ($defaultVariant && $defaultVariant->images && $defaultVariant->images->isNotEmpty()) {
            $primaryImage = $defaultVariant->images->where('pivot.is_primary', true)->first();
            if (!$primaryImage) {
                $primaryImage = $defaultVariant->images->sortBy('pivot.sort_order')->first();
            }
            if ($primaryImage) {
                $mainImage = $primaryImage->url ?? $this->formatImageUrl($primaryImage->file_path);
            }
        }

        // Fallback to any variant image
        if (!$mainImage && $allImages->isNotEmpty()) {
            $firstImage = $allImages->sortBy('pivot.sort_order')->first();
            if ($firstImage) {
                $mainImage = $firstImage->url ?? $this->formatImageUrl($firstImage->file_path);
            }
        }

        // Fallback to placeholder
        if (!$mainImage) {
            $mainImage = asset('images/logo/Logo_1.png');
        }

        // Prepare all product images (unique by media_id)
        $self = $this;
        $productImages = $allImages->unique('id')->map(function ($image) use ($self) {
            return [
                'id' => $image->id,
                'url' => $image->url ?? $self->formatImageUrl($image->file_path),
                'is_primary' => (bool) ($image->pivot->is_primary ?? false),
                'sort_order' => $image->pivot->sort_order ?? 0,
                'alt_text' => $image->alt_text ?? 'KNOTELLE Product'
            ];
        })->sortBy('sort_order')->values();

        // Calculate discount percentage
        $discountPercent = 0;
        if ($defaultVariant && $defaultVariant->compare_price && $defaultVariant->compare_price > $defaultVariant->price) {
            $discountPercent = round((($defaultVariant->compare_price - $defaultVariant->price) / $defaultVariant->compare_price) * 100);
        }

        // Determine if product is in stock
        $isInStock = false;
        $totalStock = 0;

        foreach ($product->variants as $variant) {
            $totalStock += $variant->stock_quantity;
        }
        $isInStock = $totalStock > 0;

        // Transform product data similar to index method
        $transformedProduct = [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'main_image' => $mainImage,
            'images' => $productImages,
            'price' => $defaultVariant ? (float) $defaultVariant->price : 0,
            'compare_price' => $defaultVariant ? (float) $defaultVariant->compare_price : null,
            'tax_rate' => $product->taxClass ? (float) $product->taxClass->total_rate : 0,
            'discount_percent' => $discountPercent,
            'is_in_stock' => $isInStock,
            'stock_quantity' => $totalStock,
            'stock' => $totalStock,
            'sku' => $defaultVariant ? $defaultVariant->sku : 'N/A',
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
            ] : null,
            'category' => $product->mainCategory ? [
                'id' => $product->mainCategory->id,
                'name' => $product->mainCategory->name,
                'slug' => $product->mainCategory->slug,
            ] : null,
            'is_featured' => (bool) $product->is_featured,
            'is_new' => (bool) $product->is_new,
            'is_bestseller' => (bool) $product->is_bestseller,
            'rating' => (float) $product->rating ?? 0,
            'review_count' => (int) $product->review_count ?? 0,
            'has_variants' => $product->product_type === 'configurable',
            'variants_count' => $product->variants->count(),
            'created_at' => $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : null,
            'created_at_formatted' => $product->created_at ? $product->created_at->format('M d, Y') : null,
            // Include additional details for show method
            'variants' => $product->variants->map(function ($variant) use ($self) {
                $images = $variant->images->map(function ($image) use ($self) {
                    return [
                        'id' => $image->id,
                        'url' => $self->formatImageUrl($image->file_path),
                        'is_primary' => (bool) ($image->pivot->is_primary ?? false),
                        'sort_order' => $image->pivot->sort_order ?? 0,
                        'alt_text' => $image->alt_text
                    ];
                })->sortBy('sort_order')->values();

                    $size = 'Standard';
                    if (str_ends_with($variant->sku, '-08')) $size = '8 Sachets';
                    elseif (str_ends_with($variant->sku, '-16')) $size = '16 Sachets';
                    elseif (str_ends_with($variant->sku, '-32')) $size = '32 Sachets';
                    elseif (str_ends_with($variant->sku, '-250')) $size = '250g';
                    elseif (str_ends_with($variant->sku, '-500')) $size = '500g';
                    elseif (str_ends_with($variant->sku, '-1KG')) $size = '1 Kg';

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'compare_price' => $variant->compare_price ? (float) $variant->compare_price : null,
                        'stock_quantity' => $variant->stock_quantity,
                        'stock' => $variant->stock_quantity,
                        'size' => $size,
                        'stock_status' => $variant->stock_status,
                        'is_default' => (bool) $variant->is_default,
                        'images' => $images,
                        'attributes' => []
                ];
            }),
            'reviews_data' => $product->approvedReviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user_name,
                    'user_icon' => $review->user_icon,
                    'rating' => (float) $review->rating,
                    'review' => $review->review,
                    'created_at' => $review->created_at ? $review->created_at->format('Y-m-d') : null
                ];
            })->values(),
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
        ];

        return $this->apiResponse(true, $transformedProduct, 'Product details retrieved');
    } catch (\Exception $e) {
        Log::error('Product show error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return $this->apiResponse(false, null, 'Product not found', 404);
    }
}
    /**
     * Get related products
     */
    public function relatedProducts($productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            $relatedProducts = Product::with(['brand', 'defaultVariant.images'])
                ->where('status', 'active')
                ->where('id', '!=', $productId)
                ->where(function ($query) use ($product) {
                    $query->where('brand_id', $product->brand_id)
                          ->orWhere('main_category_id', $product->main_category_id);
                })
                ->limit(8)
                ->get()
                ->map(function ($relatedProduct) {
                    $defaultVariant = $relatedProduct->defaultVariant;
                    $mainImage = null;

                    // Get main image from default variant
                    if ($defaultVariant && $defaultVariant->images && $defaultVariant->images->isNotEmpty()) {
                        $primaryImage = $defaultVariant->images->where('pivot.is_primary', true)->first();
                        if (!$primaryImage) {
                            $primaryImage = $defaultVariant->images->sortBy('pivot.sort_order')->first();
                        }
                        if ($primaryImage && $primaryImage->file_path) {
                            $mainImage = $this->formatImageUrl($primaryImage->file_path);
                        }
                    }

                    // Fallback to placeholder
                    if (!$mainImage) {
                        $mainImage = asset('images/placeholder-product.jpg');
                    }

                    return [
                        'id' => $relatedProduct->id,
                        'slug' => $relatedProduct->slug,
                        'name' => $relatedProduct->name,
                        'main_image' => $mainImage,
                        'price' => $defaultVariant ? (float) $defaultVariant->price : 0,
                        'compare_price' => $defaultVariant ? (float) $defaultVariant->compare_price : null,
                        'brand' => $relatedProduct->brand ? $relatedProduct->brand->name : null,
                    ];
                });

            return $this->apiResponse(true, $relatedProducts, 'Related products retrieved');
        } catch (\Exception $e) {
            Log::error('Related products error: ' . $e->getMessage());
            return $this->apiResponse(false, null, 'Failed to retrieve related products', 500);
        }
    }

    /**
     * Get product variants for a specific product
     */
    public function getProductVariants($productId): JsonResponse
    {
        try {
            $self = $this;
            $variants = ProductVariant::with(['images'])
                ->where('product_id', $productId)
                ->where('status', 1)
                ->get()
                ->map(function ($variant) use ($self) {
                    $images = $variant->images->map(function ($image) use ($self) {
                        return [
                            'id' => $image->id,
                            'url' => $self->formatImageUrl($image->file_path),
                            'is_primary' => (bool) ($image->pivot->is_primary ?? false),
                            'sort_order' => $image->pivot->sort_order ?? 0
                        ];
                    })->sortBy('sort_order')->values();

                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'compare_price' => $variant->compare_price ? (float) $variant->compare_price : null,
                        'stock_quantity' => $variant->stock_quantity,
                        'stock_status' => $variant->stock_status,
                        'is_default' => (bool) $variant->is_default,
                        'images' => $images,
                        'attributes' => []
                    ];
                });

            return $this->apiResponse(true, $variants, 'Product variants retrieved');
        } catch (\Exception $e) {
            Log::error('Product variants error: ' . $e->getMessage());
            return $this->apiResponse(false, null, 'Failed to retrieve product variants', 500);
        }
    }
}
