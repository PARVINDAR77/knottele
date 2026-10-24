<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ProductService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaxClass;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        $query = Product::with(['mainCategory', 'brand', 'defaultVariant.images', 'taxClass']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%")
                  ->orWhereHas('defaultVariant', function($v) use ($search) {
                      $v->where('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('product_type', $request->type);
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        $brands = Brand::where('status', 1)->get();
        $taxClasses = TaxClass::all();

        $maxId = Product::max('id') ?? 0;
        $nextProductCode = 'prod-' . ($maxId + 1);
        while (Product::where('product_code', $nextProductCode)->withTrashed()->exists()) {
            $maxId++;
            $nextProductCode = 'prod-' . ($maxId + 1);
        }

        return view('admin.products.create', compact('categories', 'brands', 'taxClasses', 'nextProductCode'));
    }

    public function store(Request $request)
    {
        $data = array_merge($request->all(), $request->allFiles());

        // 1. Auto-generate slug if empty
        if (empty($data['slug']) && !empty($data['name'])) {
            $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        }
        if (!empty($data['slug'])) {
            $baseSlug = $data['slug'];
            $slugCount = 1;
            while (Product::where('slug', $data['slug'])->withTrashed()->exists()) {
                $data['slug'] = $baseSlug . '-' . $slugCount;
                $slugCount++;
            }
        }

        // 2. Auto-generate product_code if empty
        if (empty($data['product_code'])) {
            $maxId = Product::max('id') ?? 0;
            $code = 'prod-' . ($maxId + 1);
            while (Product::where('product_code', $code)->withTrashed()->exists()) {
                $maxId++;
                $code = 'prod-' . ($maxId + 1);
            }
            $data['product_code'] = $code;
        }

        // 3. Auto-generate SKU for simple products if empty
        if (($data['product_type'] ?? 'simple') === 'simple') {
            if (empty($data['sku'])) {
                $prefix = strtoupper(\Illuminate\Support\Str::slug($data['name'] ?? 'PROD'));
                $cleanPrefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
                if (empty($cleanPrefix)) {
                    $cleanPrefix = 'PROD';
                }
                $skuCandidate = substr($cleanPrefix, 0, 8) . '-' . rand(100, 999);
                while (ProductVariant::where('sku', $skuCandidate)->withTrashed()->exists()) {
                    $skuCandidate = substr($cleanPrefix, 0, 8) . '-' . rand(1000, 9999);
                }
                $data['sku'] = $skuCandidate;
            }
            if (!isset($data['stock_quantity']) || $data['stock_quantity'] === '') {
                $data['stock_quantity'] = 0;
            }
            if (!isset($data['price']) || $data['price'] === '') {
                $data['price'] = 0;
            }
        }

        $request->merge($data);

        // Basic Validation needed before hitting Service
        $request->validate([
            'name' => 'required|string|max:255',
            'main_category_id' => 'required|exists:categories,id',
            'product_type' => 'required|in:simple,configurable',
            'price' => 'required_if:product_type,simple|nullable|numeric|min:0',
            'product_code' => 'nullable|string|max:255|unique:products,product_code',
            'slug' => 'required|string|max:255|unique:products,slug',
            'sku' => 'required_if:product_type,simple|nullable|string|max:255|unique:product_variants,sku',
            'variants.*.sku' => 'required_if:product_type,configurable|string|max:255',
        ]);

        if ($request->input('product_type') === 'configurable' && empty($request->input('variants'))) {
            if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Configurable products must have at least one variant generated. Please select attributes and generate variants.'
                ], 422);
            }
            return back()->withInput()->with('error', 'Configurable products must have at least one variant generated. Please select attributes and generate variants.');
        }

        $result = $this->productService->createProduct($data);

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product created successfully.',
                    'redirect' => route('admin.products.index')
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to create product.'
            ], 422);
        }

        if ($result['success']) {
            return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
        }

        return back()->withInput()->with('error', $result['error']);
    }

    public function edit($id)
    {
        // Eager load everything needed for the view
        $product = Product::with([
            'categories', 
            'defaultVariant.images',
            'brand',
            'mainCategory',
            'variants.images',
            'variants.primaryImage.media',
        ])->findOrFail($id);
        
        $categories = Category::with('children')->whereNull('parent_id')->get();
        $brands = Brand::where('status', 1)->get();
        $taxClasses = TaxClass::all();
        
        return view('admin.products.edit', compact('product', 'categories', 'brands', 'taxClasses'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'product_code' => 'nullable|string|max:255|unique:products,product_code,' . $id,
            'slug' => 'required|string|max:255|unique:products,slug,' . $id,
            'sku' => 'required_if:product_type,simple|nullable|string|max:255',
            'variants.*.sku' => 'required_if:product_type,configurable|string|max:255',
        ]);
        
        $data = array_merge($request->all(), $request->allFiles());
        if (!isset($data['product_type'])) {
            $data['product_type'] = $product->product_type;
        }
        if (!isset($data['main_category_id'])) {
             $data['main_category_id'] = $product->main_category_id;
        }

        $result = $this->productService->updateProduct($product, $data);

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product updated successfully.',
                    'redirect' => route('admin.products.index')
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to update product.'
            ], 422);
        }

        if ($result['success']) {
            return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
        }

        return back()->withInput()->with('error', $result['error']);
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
             return back()->with('error', 'Cannot delete product. It has associated orders.');
        }

        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function search(Request $request)
    {
        $query = Product::query();

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
        }
        
        // Debug Logging
        \Illuminate\Support\Facades\Log::info('Product Search Query:', [
            'search_term' => $request->q,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        // Increased limit to 100
        $products = $query->with(['defaultVariant.images']) 
                  ->latest()
                  ->limit(100)
                  ->get();

        return response()->json([
            'success' => true,
            'data' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name . ' (' . $product->product_code . ')',
                    'image' => $product->main_image_url,
                ];
            })
        ]);
    }
}
