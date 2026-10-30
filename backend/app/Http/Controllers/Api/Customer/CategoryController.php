<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Get a list of all active categories with dynamic counts and starting prices.
     * Cached for 30 minutes for sub-10ms response times.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $priceFromFallback = [
            'keychain' => 'From ₹100 →',
            'flower' => 'From ₹200 →',
            'bouquet' => 'From ₹700 →',
            'soft-toys' => 'From ₹1,000 →',
            'bags' => 'From ₹4,800 →',
            'coin-purse' => 'From ₹250 →',
            'phone-cover' => 'From ₹1,000 →',
            'cup-tea-coffee' => 'From ₹500 →',
            'bookmark' => 'From ₹300 →',
            'hair-accessories' => 'From ₹200 →',
            'clothing' => 'From ₹2,800 →',
        ];

        $formattedCategories = Cache::remember('knotelle_customer_categories_v2', 1800, function () use ($priceFromFallback) {
            $categories = Category::where('status', 1)
                ->whereNull('parent_id') // Get main categories
                ->orderBy('sort_order', 'asc')
                ->with(['image', 'products.variants'])
                ->get();

            return $categories->map(function ($category) use ($priceFromFallback) {
                $imageUrl = null;
                if ($category->image && $category->image->file_path) {
                    $fp = $category->image->file_path;
                    if (str_starts_with($fp, 'http://') || str_starts_with($fp, 'https://')) {
                        $imageUrl = $fp;
                    } elseif (str_starts_with($fp, 'images/')) {
                        $imageUrl = asset($fp);
                    } else {
                        $imageUrl = asset('storage/' . ltrim($fp, '/'));
                    }
                } elseif ($category->image && $category->image->file_name) {
                    $fn = $category->image->file_name;
                    if (str_starts_with($fn, 'http://') || str_starts_with($fn, 'https://')) {
                        $imageUrl = $fn;
                    } elseif (str_starts_with($fn, 'images/')) {
                        $imageUrl = asset($fn);
                    } else {
                        $imageUrl = asset('storage/' . ltrim($fn, '/'));
                    }
                } else {
                    $imageUrl = asset('images/categories/' . $category->slug . '.jpg');
                }

                $activeProducts = $category->products->where('status', 'active');
                $productCount = $activeProducts->count();

                // Calculate min price from active products
                $minPrice = null;
                foreach ($activeProducts as $p) {
                    foreach ($p->variants as $v) {
                        if ($v->price > 0 && ($minPrice === null || $v->price < $minPrice)) {
                            $minPrice = (float) $v->price;
                        }
                    }
                }

                $priceFromStr = $minPrice !== null
                    ? ('From ₹' . number_format($minPrice, 0) . ' →')
                    : ($priceFromFallback[$category->slug] ?? 'From ₹100 →');

                return [
                    'id' => 'cat-' . $category->id,
                    'db_id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $imageUrl,
                    'featured' => (bool) $category->featured,
                    'priceFrom' => $priceFromStr,
                    'itemCount' => $productCount > 0 ? $productCount : (!empty($priceFromFallback[$category->slug]) ? 1 : 0),
                ];
            })->all();
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedCategories
        ]);
    }
}
