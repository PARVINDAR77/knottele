<?php

namespace App\Services\Admin;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class ProductService
{
    /**
     * Create a new product with all related data
     */
    public function createProduct(array $data): array
    {
        DB::beginTransaction();

        try {
            Log::info('Starting product creation', ['data' => $data]);

            // 1. Create product
            $status = $this->normalizeStatus($data['status'] ?? 'active');

            $product = Product::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'product_type' => $data['product_type'],
                'brand_id' => $data['brand_id'] ?? null,
                'main_category_id' => $data['main_category_id'],
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? '',
                'status' => $status,
                'is_featured' => !empty($data['is_featured']),
                'is_new' => !empty($data['is_new']),
                'is_bestseller' => !empty($data['is_bestseller']),
                'cod_available' => !empty($data['cod_available']),
                'weight' => $data['weight'] ?? 0,
                'length' => $data['length'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'product_code' => $data['product_code'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            Log::info('Product created', ['product_id' => $product->id]);

            // 2. Sync categories
            $this->syncCategories($product, $data);

            // 3. Handle product variants
            if ($product->product_type === 'simple') {
                $this->createSimpleProductVariant($product, $data);
            } else {
                $this->createConfigurableProductVariants($product, $data);
            }

            DB::commit();
            Log::info('Product creation completed successfully', ['product_id' => $product->id]);

            return [
                'success' => true,
                'product' => $product,
                'message' => 'Product created successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sync categories for product
     */
    private function syncCategories(Product $product, array $data): void
    {
        $categoryIds = $data['category_ids'] ?? [];
        if (!in_array($data['main_category_id'], $categoryIds)) {
            $categoryIds[] = $data['main_category_id'];
        }

        if (!empty($categoryIds)) {
            $syncData = [];
            foreach ($categoryIds as $categoryId) {
                $syncData[$categoryId] = [
                    'is_primary' => $categoryId == $data['main_category_id'] ? 1 : 0,
                    'sort_order' => 0
                ];
            }
            $product->categories()->sync($syncData);
            Log::info('Categories synced', ['product_id' => $product->id, 'category_ids' => $categoryIds]);
        }
    }

    /**
     * Create simple product variant
     */
    private function createSimpleProductVariant(Product $product, array $data): void
    {
        $sku = $data['sku'] ?? '';
        if (empty($sku)) {
            $prefix = strtoupper(\Illuminate\Support\Str::slug($product->name ?? 'PROD'));
            $cleanPrefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
            if (empty($cleanPrefix)) {
                $cleanPrefix = 'PROD';
            }
            $sku = substr($cleanPrefix, 0, 8) . '-' . rand(100, 999);
            while (ProductVariant::where('sku', $sku)->withTrashed()->exists()) {
                $sku = substr($cleanPrefix, 0, 8) . '-' . rand(1000, 9999);
            }
        }

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $sku,
            'price' => isset($data['price']) && is_numeric($data['price']) ? $data['price'] : 0,
            'compare_price' => $data['compare_price'] ?? null,
            'cost_price' => $data['cost_price'] ?? null,
            'stock_quantity' => isset($data['stock_quantity']) && is_numeric($data['stock_quantity']) ? $data['stock_quantity'] : 0,
            'reserved_quantity' => 0,
            'stock_status' => (isset($data['stock_quantity']) && $data['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock',
            'is_default' => true,
            'status' => isset($data['status']) && $data['status'] === 'active' ? 1 : 0,
            'weight' => $data['weight'] ?? $product->weight,
            'length' => $data['length'] ?? $product->length,
            'width' => $data['width'] ?? $product->width,
            'height' => $data['height'] ?? $product->height,
        ]);

        Log::info('Simple variant created', ['variant_id' => $variant->id]);

        // Handle images for simple product variant
        $topLevelImageIds = $this->extractTopLevelImageIds($data);
        $this->syncVariantImages($variant, $data, $topLevelImageIds);
    }

    /**
     * Create configurable product variants
     */
    private function createConfigurableProductVariants(Product $product, array $data): void
    {
        Log::info('Creating configurable variants', ['product_id' => $product->id, 'variant_count' => count($data['variants'] ?? [])]);

        $topLevelImageIds = $this->extractTopLevelImageIds($data);

        if (isset($data['variants']) && is_array($data['variants'])) {
            $defaultVariantSet = false;

            foreach ($data['variants'] as $index => $variantData) {
                try {
                    $isDefault = ($index === 0 && !$defaultVariantSet) || !empty($variantData['is_default']);

                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'compare_price' => $variantData['compare_price'] ?? null,
                        'cost_price' => $variantData['cost_price'] ?? null,
                        'stock_quantity' => $variantData['stock_quantity'] ?? 0,
                        'reserved_quantity' => 0,
                        'stock_status' => ($variantData['stock_quantity'] ?? 0) > 0 ? 'in_stock' : 'out_of_stock',
                        'is_default' => $isDefault,
                        'status' => isset($variantData['status'])
                            ? ($variantData['status'] === 'active' ? 1 : 0)
                            : 1,
                        'weight' => $variantData['weight'] ?? $product->weight,
                        'length' => $variantData['length'] ?? $product->length,
                        'width' => $variantData['width'] ?? $product->width,
                        'height' => $variantData['height'] ?? $product->height,
                    ]);

                    if ($variant->is_default) {
                        $defaultVariantSet = true;
                    }

                    Log::info('Variant created', [
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'is_default' => $variant->is_default
                    ]);

                    // Handle variant images
                    if ($isDefault && !empty($topLevelImageIds)) {
                        $this->syncVariantImages($variant, ['product_images' => $topLevelImageIds]);
                    } else {
                        $this->syncVariantImages($variant, $variantData, $topLevelImageIds);
                    }

                } catch (\Exception $e) {
                    Log::error('Failed to create variant', [
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'variant_data' => $variantData
                    ]);
                    throw $e;
                }
            }
        }
    }

    public function updateProduct(Product $product, array $data): array
    {
        DB::beginTransaction();

        try {
            Log::info('Starting product update', ['product_id' => $product->id, 'data' => $data]);

            // 1. Update product basic information
            $status = $this->normalizeStatus($data['status'] ?? ($product->status ?? 'active'));

            $product->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'product_type' => $data['product_type'],
                'brand_id' => $data['brand_id'] ?? null,
                'main_category_id' => $data['main_category_id'],
                'tax_class_id' => $data['tax_class_id'] ?? null,
                'short_description' => $data['short_description'] ?? null,
                'description' => $data['description'] ?? '',
                'status' => $status,
                'is_featured' => !empty($data['is_featured']),
                'is_new' => !empty($data['is_new']),
                'is_bestseller' => !empty($data['is_bestseller']),
                'cod_available' => !empty($data['cod_available']),
                'weight' => $data['weight'] ?? 0,
                'length' => $data['length'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'meta_title' => $data['meta_title'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'meta_keywords' => $data['meta_keywords'] ?? null,
                'canonical_url' => $data['canonical_url'] ?? null,
                'product_code' => $data['product_code'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            Log::info('Product basic info updated', ['product_id' => $product->id]);

            // 2. Sync categories
            $this->syncCategories($product, $data);

            // 3. Handle variants
            $this->handleVariantsUpdate($product, $data);

            DB::commit();
            Log::info('Product update completed successfully', ['product_id' => $product->id]);

            return [
                'success' => true,
                'product' => $product,
                'message' => 'Product updated successfully'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed', [
                'product_id' => $product->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get product data for edit form
     */
    public function getProductForEdit(Product $product): array
    {
        $product->load([
            'brand:id,name',
            'mainCategory:id,name',
            'categories:id,name',
            'taxClass:id,name,rate',
            'variants' => function ($query) {
                $query->with([
                   'images:id,path,full_url,thumb_url'
                ])->orderBy('is_default', 'desc');
            }
        ]);

        $defaultVariant = $product->defaultVariant;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'product_type' => $product->product_type,
            'product_code' => $product->product_code,
            'brand_id' => $product->brand_id,
            'main_category_id' => $product->main_category_id,
            'tax_class_id' => $product->tax_class_id,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'status' => $product->status,
            'is_featured' => (bool) $product->is_featured,
            'is_new' => (bool) $product->is_new,
            'is_bestseller' => (bool) $product->is_bestseller,
            'weight' => (float) $product->weight,
            'length' => (float) $product->length,
            'width' => (float) $product->width,
            'height' => (float) $product->height,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'canonical_url' => $product->canonical_url,
            'brand' => $product->brand,
            'main_category' => $product->mainCategory,
            'categories' => $product->categories,
            'tax_class' => $product->taxClass,
            'variants' => $this->formatVariants($product->variants),
            'default_variant' => $defaultVariant ? $this->formatDefaultVariant($defaultVariant) : null,
            'main_image' => $this->getMainProductImage($product),
            'gallery_images' => $this->getGalleryImages($product),
        ];
    }

    /**
     * Format variants for edit form
     */
    private function formatVariants($variants): array
    {
        $formatted = [];
        foreach ($variants as $variant) {
            $formatted[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => (float) $variant->price,
                'compare_price' => $variant->compare_price ? (float) $variant->compare_price : null,
                'cost_price' => $variant->cost_price ? (float) $variant->cost_price : null,
                'stock_quantity' => $variant->stock_quantity,
                'stock_status' => $variant->stock_status,
                'status' => $variant->status,
                'is_default' => (bool) $variant->is_default,
                'images' => $variant->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'media_id' => $image->media_id,
                        'url' => $image->full_url ?? $image->path,
                        'is_primary' => (bool) $image->is_primary,
                    ];
                })->toArray(),
            ];
        }
        return $formatted;
    }

    /**
     * Format default variant
     */
    private function formatDefaultVariant(ProductVariant $variant): array
    {
        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'price' => (float) $variant->price,
            'compare_price' => $variant->compare_price ? (float) $variant->compare_price : null,
            'cost_price' => $variant->cost_price ? (float) $variant->cost_price : null,
            'stock_quantity' => $variant->stock_quantity,
            'stock_status' => $variant->stock_status,
            'status' => $variant->status,
            'images' => $variant->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'media_id' => $image->media_id,
                    'url' => $image->full_url ?? $image->path,
                    'is_primary' => (bool) $image->is_primary,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get main product image
     */
    private function getMainProductImage(Product $product): ?array
    {
        $defaultVariant = $product->defaultVariant;
        if (!$defaultVariant) {
            return null;
        }
        $mainImage = $defaultVariant->images()
            ->where('variant_images.is_primary', true)
            ->first();

        if ($mainImage) {
            return [
                'id' => $mainImage->id,
                'url' => $mainImage->full_url ?? $mainImage->path,
            ];
        }

        return null;
    }

    /**
     * Get gallery images
     */
    private function getGalleryImages(Product $product): array
    {
        $defaultVariant = $product->defaultVariant;
        if (!$defaultVariant) {
            return [];
        }

        $galleryImages = $defaultVariant->images()->where('is_primary', false)->get();

        return $galleryImages->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->full_url ?? $image->path,
            ];
        })->values()->toArray();
    }

    /**
     * Extract top-level product image IDs from request data (up to 5 slots)
     */
    private function extractTopLevelImageIds(array $data): array
    {
        $imageIds = [];

        // 1. Check product_images array (5 slots)
        if (!empty($data['product_images']) && is_array($data['product_images'])) {
            foreach ($data['product_images'] as $imgId) {
                if (!empty($imgId) && is_numeric($imgId)) {
                    $imageIds[] = (int) $imgId;
                }
            }
        }

        // 2. Check main_image_id
        if (!empty($data['main_image_id']) && is_numeric($data['main_image_id'])) {
            if (!in_array((int)$data['main_image_id'], $imageIds)) {
                array_unshift($imageIds, (int)$data['main_image_id']);
            }
        }

        // 3. Check gallery_image_ids
        if (!empty($data['gallery_image_ids']) && is_array($data['gallery_image_ids'])) {
            foreach ($data['gallery_image_ids'] as $imgId) {
                if (!empty($imgId) && is_numeric($imgId)) {
                    $imageIds[] = (int) $imgId;
                }
            }
        }

        // 4. Check for direct file uploads (fallback)
        $uploadedFiles = [];
        if (!empty($data['slot_files']) && is_array($data['slot_files'])) {
            $uploadedFiles = $data['slot_files'];
        } elseif (request()->hasFile('slot_files')) {
            $uploadedFiles = request()->file('slot_files');
        } elseif (request()->hasFile('images')) {
            $uploadedFiles = request()->file('images');
        }

        if (!empty($uploadedFiles) && is_array($uploadedFiles)) {
            $targetFolder = 'images/products';
            $targetDirectory = public_path($targetFolder);
            if (!File::isDirectory($targetDirectory)) {
                File::makeDirectory($targetDirectory, 0755, true, true);
            }

            foreach ($uploadedFiles as $slotIdx => $file) {
                if ($file instanceof UploadedFile && $file->isValid()) {
                    try {
                        $originalName = $file->getClientOriginalName();
                        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
                        $rawName = pathinfo($originalName, PATHINFO_FILENAME);
                        $cleanSlug = Str::slug($rawName);
                        $uniqueName = ($cleanSlug ?: 'product') . '_' . time() . '_' . Str::random(4) . '.' . $extension;

                        $file->move($targetDirectory, $uniqueName);
                        $relPath = $targetFolder . '/' . $uniqueName;
                        $fullDiskPath = $targetDirectory . DIRECTORY_SEPARATOR . $uniqueName;
                        $fileSize = file_exists($fullDiskPath) ? filesize($fullDiskPath) : 0;

                        $media = Media::create([
                            'file_name' => $originalName,
                            'file_path' => $relPath,
                            'disk' => 'local',
                            'mime_type' => 'image/' . ($extension === 'png' ? 'png' : ($extension === 'webp' ? 'webp' : 'jpeg')),
                            'file_type' => 'image',
                            'file_size' => $fileSize,
                            'alt_text' => $rawName,
                            'page' => 'products',
                            'section' => 'products',
                            'slot' => 'slot-' . $slotIdx,
                            'title' => $rawName,
                            'is_active' => true,
                            'uploaded_by' => auth('admin')->id() ?? 1,
                            'uploader_type' => 'admin',
                        ]);

                        if ($slotIdx === 0 || $slotIdx === '0') {
                            array_unshift($imageIds, (int)$media->id);
                        } else {
                            $imageIds[] = (int)$media->id;
                        }
                    } catch (\Exception $e) {
                        Log::error('Direct product file upload failed: ' . $e->getMessage());
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($imageIds)));
    }

    /**
     * Sync variant images
     */
    private function syncVariantImages(ProductVariant $variant, array $variantData, array $fallbackImageIds = []): void
    {
        $imagesData = [];

        // Collect unique image IDs
        $imageIds = [];

        // Check for slot-based product_images array
        if (!empty($variantData['product_images']) && is_array($variantData['product_images'])) {
            foreach ($variantData['product_images'] as $imgId) {
                if (!empty($imgId) && is_numeric($imgId)) {
                    $imageIds[] = (int) $imgId;
                }
            }
        }

        if (!empty($variantData['main_image_id']) && is_numeric($variantData['main_image_id'])) {
            $imageIds[] = (int) $variantData['main_image_id'];
        }

        if (!empty($variantData['gallery_image_ids']) && is_array($variantData['gallery_image_ids'])) {
            foreach ($variantData['gallery_image_ids'] as $imgId) {
                if (!empty($imgId) && is_numeric($imgId)) {
                    $imageIds[] = (int) $imgId;
                }
            }
        }

        // Remove duplicates
        $imageIds = array_values(array_unique(array_filter($imageIds)));

        // If no images set specifically for this variant, use fallback top-level product images
        if (empty($imageIds) && !empty($fallbackImageIds)) {
            $imageIds = $fallbackImageIds;
        }

        // Clear existing images first
        DB::table('variant_images')->where('variant_id', $variant->id)->delete();

        $mainImageId = $variantData['main_image_id'] ?? ($imageIds[0] ?? null);

        $primaryChosen = false;
        foreach ($imageIds as $index => $imageId) {
            $isPrimary = false;
            if ($mainImageId && $imageId == $mainImageId && !$primaryChosen) {
                $isPrimary = true;
                $primaryChosen = true;
            } elseif (!$mainImageId && $index === 0 && !$primaryChosen) {
                $isPrimary = true;
                $primaryChosen = true;
            }
            $imagesData[] = [
                'variant_id' => $variant->id,
                'media_id' => $imageId,
                'is_primary' => $isPrimary ? 1 : 0,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!$primaryChosen && !empty($imagesData)) {
            $imagesData[0]['is_primary'] = 1;
        }

        if (!empty($imagesData)) {
            DB::table('variant_images')->insert($imagesData);
        }

        Log::info('Variant images synced', [
            'variant_id' => $variant->id,
            'image_count' => count($imagesData)
        ]);
    }

    /**
     * Handle variants update (Create/Update/Delete)
     */
    private function handleVariantsUpdate(Product $product, array $data): void
    {
        $topLevelImageIds = $this->extractTopLevelImageIds($data);

        // 1. Handle Simple Product
        if ($product->product_type === 'simple') {
            Log::info('Updating simple product variant', ['product_id' => $product->id]);
            $variant = $product->defaultVariant;
            
            if (!$variant) {
                Log::warning('No default variant found for simple product, creating one', ['product_id' => $product->id]);
                $this->createSimpleProductVariant($product, $data);
                return;
            }

            $variant->update([
                'sku' => $data['sku'] ?? $variant->sku,
                'price' => $data['price'] ?? $variant->price,
                'compare_price' => $data['compare_price'] ?? $variant->compare_price,
                'stock_quantity' => $data['stock_quantity'] ?? $variant->stock_quantity,
                'stock_status' => (isset($data['stock_quantity']) && $data['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock',
                'status' => 1,
                'weight' => $data['weight'] ?? $product->weight,
                'length' => $data['length'] ?? $product->length,
                'width' => $data['width'] ?? $product->width,
                'height' => $data['height'] ?? $product->height,
            ]);

            // Sync images for simple product using top level images
            $this->syncVariantImages($variant, $data, $topLevelImageIds);
            Log::info('Simple product variant updated successfully', ['variant_id' => $variant->id]);
            return;
        }

        // 2. Handle Configurable Product
        if (!isset($data['variants']) || !is_array($data['variants'])) {
            // If no variants array sent, at least sync top level images to default variant
            if ($product->defaultVariant && !empty($topLevelImageIds)) {
                $this->syncVariantImages($product->defaultVariant, ['product_images' => $topLevelImageIds]);
            }
            Log::info('No variants data provided for configurable product update', ['product_id' => $product->id]);
            return;
        }

        Log::info('Updating configurable product variants', ['product_id' => $product->id, 'count' => count($data['variants'])]);
        $submittedVariantIds = [];

        foreach ($data['variants'] as $index => $variantData) {
            $variant = null;

            // Check if updating existing variant
            if (!empty($variantData['id'])) {
                $variant = ProductVariant::where('id', $variantData['id'])
                    ->where('product_id', $product->id)
                    ->first();
                if ($variant) {
                    $submittedVariantIds[] = $variant->id;
                }
            }

            $isDefault = (isset($data['default_variant_index']) && $data['default_variant_index'] == $index) 
                || !empty($variantData['is_default']) 
                || ($index === 0 && empty($data['default_variant_index']));

            if ($variant) {
                // UPDATE existing
                $variant->update([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'compare_price' => $variantData['compare_price'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'],
                    'stock_status' => ($variantData['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock',
                    'is_default' => $isDefault,
                    'status' => 1
                ]);
                Log::debug('Variant updated', ['variant_id' => $variant->id, 'sku' => $variant->sku]);
            } else {
                // CREATE new
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'compare_price' => $variantData['compare_price'] ?? null,
                    'stock_quantity' => $variantData['stock_quantity'],
                    'stock_status' => ($variantData['stock_quantity'] > 0) ? 'in_stock' : 'out_of_stock',
                    'is_default' => $isDefault,
                    'status' => 1
                ]);
                $submittedVariantIds[] = $variant->id;
                Log::debug('New variant created during update', ['variant_id' => $variant->id, 'sku' => $variant->sku]);
            }

            // Sync images:
            // If this is default variant and top level images were provided, ensure it receives the top level product images!
            if ($isDefault && !empty($topLevelImageIds)) {
                $this->syncVariantImages($variant, ['product_images' => $topLevelImageIds]);
            } else {
                $this->syncVariantImages($variant, $variantData, $topLevelImageIds);
            }
        }

        // 3. Remove variants not in submission (if any deletions were intended)
        if (!empty($submittedVariantIds)) {
            $deletedCount = $product->variants()
                ->whereNotIn('id', $submittedVariantIds)
                ->delete();
            if ($deletedCount > 0) {
                Log::info('Deleted variants not in submission', ['product_id' => $product->id, 'count' => $deletedCount]);
            }
        }
    }

    /**
     * Normalize status value to match database enum
     */
    private function normalizeStatus($status): string
    {
        if ($status === '1' || $status === 1 || $status === true || $status === 'active') {
            return 'active';
        }
        if ($status === '0' || $status === 0 || $status === false || $status === 'inactive') {
            return 'inactive';
        }
        if (in_array($status, ['draft', 'active', 'inactive', 'out_of_stock'])) {
            return $status;
        }
        return 'draft';
    }

}
