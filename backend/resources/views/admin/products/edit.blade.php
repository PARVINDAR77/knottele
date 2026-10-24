@extends('admin.layouts.master')

@section('title', 'Edit Product')

@section('content')
<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-stone-800 mb-2">Edit Product: {{ $product->name }}</h2>
            <nav class="text-sm text-stone-500 font-medium">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-red-600 transition-colors">Dashboard</a>
                <span class="mx-2 text-stone-300">/</span>
                <a href="{{ route('admin.products.index') }}" class="hover:text-red-600 transition-colors">Products</a>
                <span class="mx-2 text-stone-300">/</span>
                <span class="text-stone-800 font-semibold">Edit</span>
            </nav>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>

<form action="{{ route('admin.products.update', $product->id) }}" method="POST" id="product-form" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 pb-2 border-b text-stone-800">Basic Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-700 mb-1">Product Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                        @error('name') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="slug" class="block text-sm font-medium text-stone-700 mb-1">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug', $product->slug) }}"
                                class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition bg-stone-50 text-stone-500">
                            @error('slug') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="product_code" class="block text-sm font-medium text-gray-700 mb-1">Product Code (Art. No.)</label>
                            <input type="text" name="product_code" id="product_code" value="{{ old('product_code', $product->product_code) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                            @error('product_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 pb-2 border-b">Description</h3>
                
                <div class="space-y-4">
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-stone-700 mb-1">Short Description</label>
                        <textarea name="short_description" id="short_description" rows="3"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">{{ old('short_description', $product->short_description) }}</textarea>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-stone-700 mb-1">Full Description</label>
                        <textarea name="description" id="description" rows="6"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Media (5 Photo Slots) -->
            @php
                $productMediaList = collect();
                if ($product->defaultVariant && $product->defaultVariant->images && $product->defaultVariant->images->isNotEmpty()) {
                    $productMediaList = $product->defaultVariant->images->sortBy('pivot.sort_order')->values();
                }
                if ($productMediaList->isEmpty()) {
                    foreach ($product->variants as $v) {
                        if ($v->images && $v->images->isNotEmpty()) {
                            $productMediaList = $v->images->sortBy('pivot.sort_order')->values();
                            break;
                        }
                    }
                }
            @endphp
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-stone-100" id="media-section">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-6 border-b border-stone-100">
                    <div>
                        <h3 class="text-lg font-bold text-stone-900 flex items-center gap-2">
                            <i class="fas fa-camera-retro text-red-500"></i>
                            <span>Product Images</span>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">5 Photo Slots</span>
                        </h3>
                        <p class="text-xs text-stone-500 mt-0.5">Upload 4 to 5 high-quality photos. Slot 1 is the primary cover photo, and Slots 2–5 show other angles and details on the website.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="cursor-pointer bg-red-600 hover:bg-red-700 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-cloud-upload-alt text-sm"></i>
                            <span>Upload Multiple Photos</span>
                            <input type="file" accept="image/*" multiple class="hidden" onchange="uploadMultipleSlots(event)">
                        </label>
                    </div>
                </div>

                <!-- 5 Image Slots Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    @for($i = 0; $i < 5; $i++)
                        @php
                            $slotMedia = $productMediaList->get($i);
                            $slotMediaId = $slotMedia ? $slotMedia->id : '';
                            $slotMediaUrl = $slotMedia ? ($slotMedia->url ?? asset($slotMedia->file_path)) : '';
                            $isPrimary = $i === 0;
                        @endphp
                        <div class="relative group flex flex-col bg-stone-50/80 rounded-2xl border-2 {{ $isPrimary ? 'border-red-300 bg-red-50/20 ring-1 ring-red-100' : 'border-dashed border-stone-300 hover:border-stone-400' }} p-3 transition duration-200" id="slot-card-{{ $i }}">
                            
                            <!-- Slot Header / Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider {{ $isPrimary ? 'text-red-600 bg-red-100/70 px-2 py-0.5 rounded-md' : 'text-stone-500 bg-stone-200/60 px-2 py-0.5 rounded-md' }}">
                                    {{ $isPrimary ? '★ Primary (Cover)' : 'Slot #' . ($i + 1) }}
                                </span>
                                <button type="button" onclick="clearImageSlot({{ $i }})" id="slot-clear-btn-{{ $i }}" class="{{ $slotMediaId ? '' : 'hidden' }} text-stone-400 hover:text-red-600 text-xs p-1 rounded-md transition" title="Remove photo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Preview Area -->
                            <div class="relative aspect-square w-full rounded-xl overflow-hidden bg-white border border-stone-200/80 flex items-center justify-center shadow-inner group/thumb mb-3">
                                <img id="slot-img-{{ $i }}" src="{{ $slotMediaUrl ?: '/images/logo/Logo_1.png' }}" class="{{ $slotMediaUrl ? '' : 'hidden' }} w-full h-full object-cover">
                                
                                <div id="slot-empty-{{ $i }}" class="{{ $slotMediaUrl ? 'hidden' : 'flex' }} flex-col items-center justify-center p-3 text-center cursor-pointer group/slot-empty w-full h-full" onclick="document.getElementById('slot-file-input-{{ $i }}').click()">
                                    <div class="w-10 h-10 rounded-full bg-stone-100 flex items-center justify-center text-stone-400 mb-1 group-hover/slot-empty:bg-red-50 group-hover/slot-empty:text-red-500 transition">
                                        <i class="fas fa-image text-lg"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-stone-600">Empty Slot</span>
                                    <span class="text-[10px] text-stone-400">Click to add</span>
                                </div>

                                <div id="slot-loading-{{ $i }}" class="hidden absolute inset-0 bg-white/90 backdrop-blur-xs flex flex-col items-center justify-center p-2 z-10">
                                    <i class="fas fa-spinner fa-spin text-red-500 text-lg mb-1"></i>
                                    <span class="text-[11px] font-semibold text-stone-700">Uploading...</span>
                                </div>
                            </div>

                            <!-- Hidden Inputs -->
                            <input type="hidden" name="product_images[{{ $i }}]" id="slot-input-{{ $i }}" value="{{ $slotMediaId }}">
                            @if($i === 0)
                                <input type="hidden" name="main_image_id" id="main_image_id" value="{{ $slotMediaId }}">
                            @endif

                            <!-- Slot Action Buttons -->
                            <div class="grid grid-cols-2 gap-1.5 mt-auto">
                                <label class="cursor-pointer flex items-center justify-center gap-1 text-[11px] font-bold text-stone-700 bg-white hover:bg-stone-100 border border-stone-200/90 py-1.5 px-2 rounded-lg shadow-2xs transition active:scale-95">
                                    <i class="fas fa-upload text-stone-500 text-[10px]"></i>
                                    <span>Upload</span>
                                    <input type="file" id="slot-file-input-{{ $i }}" name="slot_files[{{ $i }}]" accept="image/*" class="hidden" onchange="uploadSingleSlot(event, {{ $i }})">
                                </label>

                                <button type="button" onclick="openMediaModalForSlot({{ $i }})" class="flex items-center justify-center gap-1 text-[11px] font-bold text-stone-700 bg-white hover:bg-stone-100 border border-stone-200/90 py-1.5 px-2 rounded-lg shadow-2xs transition active:scale-95">
                                    <i class="fas fa-folder-open text-stone-500 text-[10px]"></i>
                                    <span>Library</span>
                                </button>
                            </div>

                        </div>
                    @endfor
                </div>
            </div>

            <!-- Simple Product Fields -->
            @if($product->product_type === 'simple')
            <div id="simple-product-fields" class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 pb-2 border-b">Pricing & Inventory</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-stone-700 mb-1">Price <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-stone-500 font-bold">₹</span>
                            <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" step="0.01" min="0" class="w-full pl-8 pr-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500 font-bold text-stone-800">
                        </div>
                        @error('price') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sku" class="block text-sm font-medium text-stone-700 mb-1">SKU <span class="text-rose-500">*</span></label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500 font-medium">
                        @error('sku') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-stone-700 mb-1">Stock Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                         @error('stock_quantity') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>
                     <div>
                        <label for="compare_price" class="block text-sm font-medium text-stone-700 mb-1">Compare at Price</label>
                        <input type="number" name="compare_price" value="{{ old('compare_price', $product->compare_price) }}" step="0.01" class="w-full px-4 py-2 border border-stone-300 rounded-lg">
                    </div>
                </div>
            </div>
            @endif

            <!-- CONFIGURABLE VARIANTS SECTION -->
            @if($product->product_type === 'configurable')
            <div id="configurable-product-fields" class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <div class="flex items-center justify-between mb-4 pb-2 border-b">
                    <h3 class="text-lg font-semibold text-stone-800">Product Variants</h3>
                    <span class="text-xs text-stone-500">Manage or remove variants below</span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 border rounded-xl overflow-hidden">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Variant</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-32">SKU</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-24">Price</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-24">Stock</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-40">Images</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-stone-500 uppercase tracking-wider w-16">Default</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-stone-500 uppercase tracking-wider w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-stone-100" id="variants-container">
                            <!-- PHP RENDERED VARIANTS -->
                            @foreach($product->variants as $idx => $variant)
                                <tr id="variant-row-{{ $idx }}">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">
                                        <div class="font-medium text-stone-800">{{ 'Variant #' . ($idx + 1) }}</div>
                                        <input type="hidden" name="variants[{{ $idx }}][id]" value="{{ $variant->id }}">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="variants[{{ $idx }}][sku]" value="{{ $variant->sku }}" class="w-full px-2 py-1 border rounded text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="variants[{{ $idx }}][price]" value="{{ $variant->price }}" step="0.01" class="w-full px-2 py-1 border rounded text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" name="variants[{{ $idx }}][stock_quantity]" value="{{ $variant->stock_quantity }}" class="w-full px-2 py-1 border rounded text-sm">
                                    </td>
                                    <td class="px-3 py-2">
                                        <div id="variant-images-{{ $idx }}" class="flex gap-1 flex-wrap">
                                            @if($variant->primaryImage && $variant->primaryImage->media)
                                                <div class="relative w-9 h-9 variant-main-thumb border-2 border-red-500 rounded overflow-hidden">
                                                    <img src="{{ $variant->primaryImage->media->url }}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='/images/logo/Logo_1.png'">
                                                </div>
                                            @endif
                                            @foreach($variant->images->where('pivot.is_primary', 0) as $vImg)
                                                <div class="relative w-9 h-9 border border-stone-200 rounded overflow-hidden">
                                                    <img src="{{ $vImg->url }}" class="w-full h-full object-cover" onerror="this.onerror=null;this.src='/images/logo/Logo_1.png'">
                                                </div>
                                            @endforeach
                                        </div>
                                        <button type="button" onclick="openVariantMediaModal({{ $idx }})" class="text-[11px] text-red-600 hover:text-red-800 font-bold mt-1">Manage Images</button>
                                        
                                        <input type="hidden" name="variants[{{ $idx }}][main_image_id]" id="variant-main-input-{{ $idx }}" value="{{ $variant->primaryImage ? $variant->primaryImage->media_id : '' }}">
                                        <div id="variant-gallery-inputs-{{ $idx }}">
                                            @foreach($variant->images->where('pivot.is_primary', 0) as $vImg)
                                                <input type="hidden" name="variants[{{ $idx }}][gallery_image_ids][]" value="{{ $vImg->id }}">
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                       <input type="radio" name="default_variant_index" value="{{ $idx }}" {{ $variant->is_default ? 'checked' : '' }} onclick="document.querySelectorAll('.is-default-input').forEach(el => el.value=0); document.getElementById('is-default-{{ $idx }}').value=1;">
                                       <input type="hidden" id="is-default-{{ $idx }}" name="variants[{{ $idx }}][is_default]" value="{{ $variant->is_default ? '1' : '0' }}" class="is-default-input">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick="removeVariantRow({{ $idx }})" class="text-stone-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition" title="Delete Variant">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif



        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Publish Status (Same as above) -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 border-b pb-2">Publish</h3>
                <div class="space-y-4">
                     <div>
                        <label for="status" class="block text-sm font-medium text-stone-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="draft" {{ old('status', $product->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ old('status', $product->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_featured" class="text-sm text-stone-700">Featured Product</label>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_new" class="text-sm text-stone-700">New Arrival</label>
                    </div>

                     <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller', $product->is_bestseller) ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_bestseller" class="text-sm text-stone-700">Bestseller</label>
                    </div>

                    <div class="pt-4 border-t border-stone-100">
                        <button type="submit" class="w-full btn-primary py-3 justify-center shadow-lg shadow-red-100 uppercase tracking-widest text-xs font-bold">
                            <i class="fas fa-save mr-2"></i>Update Product
                        </button>
                    </div>
                </div>
            </div>

            <!-- Organization -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 border-b pb-2">Organization</h3>
                
                <div class="space-y-4">
                     <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Product Type</label>
                        <input type="text" value="{{ ucfirst($product->product_type) }}" disabled class="w-full px-4 py-2 border border-stone-200 bg-stone-50 rounded-lg text-stone-500 cursor-not-allowed font-medium">
                        <input type="hidden" name="product_type" value="{{ $product->product_type }}">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-stone-700 mb-1">Main Category</label>
                         <input type="text" value="{{ $product->mainCategory ? $product->mainCategory->name : 'None' }}" disabled class="w-full px-4 py-2 border border-stone-200 bg-stone-50 rounded-lg text-stone-500 cursor-not-allowed font-medium">
                         <input type="hidden" name="main_category_id" id="main_category_id" value="{{ $product->main_category_id }}">
                    </div>

                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-stone-700 mb-1">Brand</label>
                        <select name="brand_id" id="brand_id" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>



                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-stone-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $product->sort_order) }}"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-[10px] text-stone-400 mt-1 uppercase tracking-wider font-bold">Lower numbers will display first</p>
                    </div>
                </div>
            </div>

            <!-- Shipping -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 border-b pb-2">Shipping</h3>
                <div class="space-y-4">
                    <div>
                        <label for="weight" class="block text-sm font-medium text-stone-700 mb-1">Weight (kg)</label>
                        <input type="number" name="weight" id="weight" value="{{ old('weight', $product->weight) }}" step="0.001"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-xs text-stone-500 mt-1">Note: Enter weight in KG. (e.g., 1 KG = 1.000, 100 GM = 0.100)</p>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Length</label>
                             <input type="number" name="length" value="{{ old('length', $product->length) }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Width</label>
                             <input type="number" name="width" value="{{ old('width', $product->width) }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Height</label>
                             <input type="number" name="height" value="{{ old('height', $product->height) }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                    </div>

                     <div>
                        <label for="tax_class_id" class="block text-sm font-medium text-stone-700 mb-1">Tax Class</label>
                        <select name="tax_class_id" id="tax_class_id" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                             <option value="">None</option>
                             @foreach($taxClasses as $tax)
                                 <option value="{{ $tax->id }}" {{ old('tax_class_id', $product->tax_class_id) == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->total_rate, 2) }}%)</option>
                             @endforeach
                        </select>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" name="cod_available" id="cod_available" value="1" {{ old('cod_available', $product->cod_available) ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="cod_available" class="text-sm text-stone-700">COD Available</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Media Modal (Same as Create) -->
<div id="media-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-stone-900/60 backdrop-blur-sm transition-opacity" aria-hidden="true" onclick="closeMediaModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-stone-100">
            <div class="bg-white px-6 pt-6 pb-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-stone-800" id="modal-title">Media Library</h3>
                        <p class="text-sm text-stone-500">Select or upload images for your product</p>
                    </div>
                    <button type="button" onclick="closeMediaModal()" class="text-stone-400 hover:text-stone-600 p-2 hover:bg-stone-50 rounded-full transition-all">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="flex flex-col md:flex-row justify-between mb-6 gap-4">
                    <div class="relative w-full md:w-1/2">
                        <input type="text" id="media-search" placeholder="Search by filename..." 
                            class="w-full pl-10 pr-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl outline-none focus:ring-2 focus:ring-red-500 transition-all">
                        <i class="fas fa-search absolute left-4 top-3.5 text-stone-400"></i>
                    </div>
                     <div class="flex items-center space-x-3">
                        <label class="cursor-pointer bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-lg shadow-red-100 flex items-center transition-all">
                            <i class="fas fa-cloud-upload-alt mr-2"></i>
                            <span id="modal-upload-btn-text">Upload from Device</span>
                            <input type="file" id="media-upload" class="hidden" multiple accept="image/*" onchange="handleFileUpload(event)">
                        </label>
                    </div>
                </div>

                <div id="media-grid" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 max-h-[50vh] overflow-y-auto p-4 bg-stone-50 rounded-2xl border border-stone-100">
                    <div class="col-span-full text-center py-20 text-stone-400">
                        <i class="fas fa-spinner fa-spin text-3xl mb-3 text-red-500"></i>
                        <p class="font-medium">Loading your media library...</p>
                    </div>
                </div>

                <div id="media-pagination" class="mt-6 flex justify-between items-center">
                    <!-- Pagination links via JS -->
                </div>
            </div>
            <div class="bg-stone-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-stone-100">
                <button type="button" id="media-select-btn" class="btn-primary min-w-[120px] justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                    Confirm selection
                </button>
                <button type="button" onclick="closeMediaModal()" class="btn-secondary min-w-[120px] justify-center">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>


    document.getElementById('name').addEventListener('input', function() {
        let slug = this.value.toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-');
        document.getElementById('slug').value = slug;
    });



    // Media Manager (5 Slots + Variants)
    let currentMode = 'slot-0';
    let activeSlotIndex = 0;
    let selectedMediaId = null;
    let currentVariantIndex = null;

    function openMediaModalForSlot(slotIndex) {
        activeSlotIndex = slotIndex;
        currentMode = 'slot-' + slotIndex;
        selectedMediaId = null;
        document.getElementById('media-modal').classList.remove('hidden');
        loadMedia(1);
    }

    function openMediaModalMulti() {
        currentMode = 'multi-slot';
        selectedMediaId = null;
        document.getElementById('media-modal').classList.remove('hidden');
        loadMedia(1);
    }

    function openMediaModal(mode) {
        if (mode === 'main') {
            openMediaModalForSlot(0);
        } else {
            currentMode = mode;
            selectedMediaId = null;
            document.getElementById('media-modal').classList.remove('hidden');
            loadMedia(1);
        }
    }

    function closeMediaModal() {
        document.getElementById('media-modal').classList.add('hidden');
    }

    function assignImageToSlot(slotIndex, id, url) {
        if (slotIndex < 0 || slotIndex >= 5) return;
        const input = document.getElementById(`slot-input-${slotIndex}`);
        const img = document.getElementById(`slot-img-${slotIndex}`);
        const emptyState = document.getElementById(`slot-empty-${slotIndex}`);
        const clearBtn = document.getElementById(`slot-clear-btn-${slotIndex}`);
        
        if (input) input.value = id;
        if (slotIndex === 0) {
            const mainInput = document.getElementById('main_image_id');
            if (mainInput) mainInput.value = id;
        }
        if (img) {
            img.src = url;
            img.classList.remove('hidden');
        }
        if (emptyState) emptyState.classList.add('hidden');
        if (clearBtn) clearBtn.classList.remove('hidden');
    }

    function clearImageSlot(slotIndex) {
        const input = document.getElementById(`slot-input-${slotIndex}`);
        const img = document.getElementById(`slot-img-${slotIndex}`);
        const emptyState = document.getElementById(`slot-empty-${slotIndex}`);
        const clearBtn = document.getElementById(`slot-clear-btn-${slotIndex}`);
        const fileInput = document.getElementById(`slot-file-input-${slotIndex}`);

        if (input) input.value = '';
        if (slotIndex === 0) {
            const mainInput = document.getElementById('main_image_id');
            if (mainInput) mainInput.value = '';
        }
        if (img) {
            img.src = '/images/logo/Logo_1.png';
            img.classList.add('hidden');
        }
        if (emptyState) emptyState.classList.remove('hidden');
        if (clearBtn) clearBtn.classList.add('hidden');
        if (fileInput) fileInput.value = '';
    }

    async function uploadSingleSlot(event, slotIndex) {
        const file = event.target.files[0];
        if (!file) return;

        const loading = document.getElementById(`slot-loading-${slotIndex}`);
        if (loading) loading.classList.remove('hidden');

        const formData = new FormData();
        formData.append('files[]', file);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await axios.post('{{ route("admin.media.upload") }}', formData, {
                headers: { 
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const media = (response.data && response.data.data) ||
                          (response.data && response.data.all_uploaded && response.data.all_uploaded[0]);

            if (response.data && response.data.success && media) {
                assignImageToSlot(slotIndex, media.id, media.url);
                toastr.success(`Slot #${slotIndex + 1} photo uploaded!`);
            } else {
                toastr.error(response.data?.message || 'Failed to upload photo.');
            }
        } catch (error) {
            console.error('Upload error:', error);
            const msg = error.response?.data?.message || 'Failed to upload photo.';
            toastr.error(msg);
        } finally {
            if (loading) loading.classList.add('hidden');
        }
    }

    async function uploadMultipleSlots(event) {
        const files = event.target.files;
        if (!files || !files.length) return;

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        toastr.info(`Uploading ${files.length} photo(s)...`);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await axios.post('{{ route("admin.media.upload") }}', formData, {
                headers: { 
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.data && response.data.success && response.data.all_uploaded) {
                const uploaded = response.data.all_uploaded;
                let uploadIdx = 0;
                for (let s = 0; s < 5 && uploadIdx < uploaded.length; s++) {
                    const curVal = document.getElementById(`slot-input-${s}`)?.value;
                    if (!curVal) {
                        assignImageToSlot(s, uploaded[uploadIdx].id, uploaded[uploadIdx].url);
                        uploadIdx++;
                    }
                }
                for (let s = 0; s < 5 && uploadIdx < uploaded.length; s++) {
                    assignImageToSlot(s, uploaded[uploadIdx].id, uploaded[uploadIdx].url);
                    uploadIdx++;
                }
                toastr.success(`${uploaded.length} photo(s) attached to product slots!`);
            } else {
                toastr.error('Failed to upload photos.');
            }
        } catch (error) {
            console.error('Batch upload error:', error);
            toastr.error('Failed to upload photos.');
        } finally {
            event.target.value = '';
        }
    }
    
    // Variant Modal Intent
    function openVariantMediaModal(idx) {
        currentVariantIndex = idx;
        Swal.fire({
            title: 'Manage Variant Images',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: 'Set Main Image',
            denyButtonText: 'Add Gallery Images',
        }).then((result) => {
            if (result.isConfirmed) {
                currentMode = 'variant-main';
                document.getElementById('media-modal').classList.remove('hidden');
                loadMedia(1);
            } else if (result.isDenied) {
                currentMode = 'variant-gallery';
                document.getElementById('media-modal').classList.remove('hidden');
                loadMedia(1);
            }
        });
    }

    function removeVariantRow(idx) {
        const row = document.getElementById(`variant-row-${idx}`);
        if (!row) return;

        Swal.fire({
            title: 'Remove Variant?',
            text: 'This variant row will be deleted when you update the product.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, remove variant'
        }).then((result) => {
            if (result.isConfirmed) {
                row.remove();
                // Ensure at least one remaining variant is default
                const firstRadio = document.querySelector('#variants-container input[name="default_variant_index"]');
                if (firstRadio && !document.querySelector('#variants-container input[name="default_variant_index"]:checked')) {
                    firstRadio.checked = true;
                    const val = firstRadio.value;
                    const hiddenDef = document.getElementById(`is-default-${val}`);
                    if (hiddenDef) hiddenDef.value = 1;
                }
                toastr.success('Variant removed. Click Update Product to save changes.');
            }
        });
    }

    async function loadMedia(page = 1, search = '') {
        const grid = document.getElementById('media-grid');
        grid.innerHTML = '<div class="col-span-full text-center py-8 text-stone-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading media...</div>';
        
        try {
            const response = await axios.get('{{ route("admin.media.data") }}', {
                params: { page, search }
            });
            renderMediaGrid(response.data);
        } catch (error) {
            console.error(error);
            grid.innerHTML = '<div class="col-span-full text-red-500 py-8 text-center">Error loading media</div>';
        }
    }

    function sanitizeImageUrl(rawUrl) {
        if (!rawUrl) return '/images/logo/Logo_1.png';
        let url = String(rawUrl).trim();
        url = url.replace(/^https?:\/\/[^\/]+/, '');
        if (url.startsWith('/storage/images/')) {
            url = url.replace('/storage/images/', '/images/');
        } else if (url.startsWith('storage/images/')) {
            url = url.replace('storage/images/', '/images/');
        } else if (url.startsWith('/storage/')) {
            url = url.replace('/storage/', '/images/');
        } else if (url.startsWith('storage/')) {
            url = url.replace('storage/', '/images/');
        }
        if (!url.startsWith('http://') && !url.startsWith('https://') && !url.startsWith('/')) {
            url = '/' + url;
        }
        return url;
    }

    function renderMediaGrid(data) {
        const grid = document.getElementById('media-grid');
        grid.innerHTML = '';
        
        data.data.forEach(media => {
             const rawUrl = media.thumb_url || media.thumbnail_url || media.url || media.full_url || media.file_path || media.path;
             const url = sanitizeImageUrl(rawUrl);
             const name = media.file_name || media.name || media.filename || 'Image';
             const div = document.createElement('div');
             div.className = `relative group cursor-pointer border rounded-lg overflow-hidden media-item hover:shadow-md transition ${selectedMediaId === media.id ? 'ring-2 ring-red-500' : ''}`;
             div.setAttribute('data-id', media.id);
             div.onclick = () => selectMedia(media.id, url);
             div.innerHTML = `
                <img src="${url}" class="w-full h-32 object-cover bg-stone-100" onerror="this.onerror=null;this.src='/images/logo/Logo_1.png'">
                <div class="p-2 text-xs truncate font-medium text-stone-700 bg-white border-t">${name}</div>
             `;
             grid.appendChild(div);
        });

         const pag = document.getElementById('media-pagination');
         let pagHtml = `<span class="text-sm">Page ${data.current_page} of ${data.last_page}</span>`;
         pagHtml += `<div class="space-x-1">`;
         if(data.prev_page_url) pagHtml += `<button type="button" onclick="loadMedia(${data.current_page - 1})" class="px-2 py-1 border rounded hover:bg-gray-50">Prev</button>`;
         if(data.next_page_url) pagHtml += `<button type="button" onclick="loadMedia(${data.current_page + 1})" class="px-2 py-1 border rounded hover:bg-gray-50">Next</button>`;
         pagHtml += `</div>`;
         pag.innerHTML = pagHtml;
    }

    function selectMedia(id, url) {
        selectedMediaId = id;
        const items = document.querySelectorAll('.media-item');
        items.forEach(item => {
            item.classList.remove('ring-2', 'ring-red-500');
            if(item.getAttribute('data-id') == id) {
                 item.classList.add('ring-2', 'ring-red-500');
            }
        });
        
        const btn = document.getElementById('media-select-btn');
        btn.onclick = () => confirmSelection(id, url);
    }

    function confirmSelection(id, url) {
        if (currentMode.startsWith('slot-')) {
            const slotIdx = parseInt(currentMode.replace('slot-', ''), 10);
            assignImageToSlot(slotIdx, id, url);
            closeMediaModal();
            toastr.success(`Photo assigned to Slot #${slotIdx + 1}`);
        } else if (currentMode === 'multi-slot') {
            let targetSlot = 0;
            for (let s = 0; s < 5; s++) {
                if (!document.getElementById(`slot-input-${s}`)?.value) {
                    targetSlot = s;
                    break;
                }
            }
            assignImageToSlot(targetSlot, id, url);
            closeMediaModal();
            toastr.success(`Photo assigned to Slot #${targetSlot + 1}`);
        } else if (currentMode === 'variant-main') {
            setVariantMainImage(currentVariantIndex, id, url);
            closeMediaModal();
        } else if (currentMode === 'variant-gallery') {
            addVariantGalleryImage(currentVariantIndex, id, url);
            toastr.success('Image added to variant gallery');
        } else {
            assignImageToSlot(0, id, url);
            closeMediaModal();
        }
    }

    function setVariantMainImage(idx, id, url) {
        const container = document.getElementById(`variant-images-${idx}`);
        const input = document.getElementById(`variant-main-input-${idx}`);
        
        const existing = container.querySelector('.variant-main-thumb');
        if(existing) existing.remove();
        
        input.value = id;
        
        const thumb = document.createElement('div');
        thumb.className = 'relative w-9 h-9 variant-main-thumb border-2 border-red-500 rounded overflow-hidden';
        thumb.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        container.prepend(thumb);
    }

    function addVariantGalleryImage(idx, id, url) {
        const container = document.getElementById(`variant-images-${idx}`);
        const hiddenContainer = document.getElementById(`variant-gallery-inputs-${idx}`);
        
        if(hiddenContainer.querySelector(`input[value="${id}"]`)) return;
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `variants[${idx}][gallery_image_ids][]`;
        input.value = id;
        hiddenContainer.appendChild(input);
        
        const thumb = document.createElement('div');
        thumb.className = 'relative w-9 h-9 border border-stone-200 rounded overflow-hidden';
        thumb.innerHTML = `<img src="${url}" class="w-full h-full object-cover">`;
        container.appendChild(thumb);
    }

    async function handleFileUpload(event) {
        const files = event.target.files;
        if (!files || !files.length) return;

        const uploadBtnText = document.getElementById('modal-upload-btn-text');
        const origBtnText = uploadBtnText ? uploadBtnText.innerHTML : 'Upload from Device';
        if (uploadBtnText) uploadBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...';

        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const response = await axios.post('{{ route("admin.media.upload") }}', formData, {
                headers: { 
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            if (response.data && response.data.success) {
                toastr.success(response.data.message || 'Files uploaded successfully');

                if (response.data.all_uploaded && response.data.all_uploaded.length > 0) {
                    const first = response.data.all_uploaded[0];
                    selectMedia(first.id, first.url);
                }

                await loadMedia(1);
            } else {
                toastr.error(response.data.message || 'Failed to upload files');
            }
        } catch (error) {
            console.error('Upload error:', error);
            const msg = error.response?.data?.message || 'Failed to upload files';
            toastr.error(msg);
        } finally {
            if (uploadBtnText) uploadBtnText.innerHTML = origBtnText;
            event.target.value = '';
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    document.getElementById('media-search').addEventListener('input', debounce((e) => {
        loadMedia(1, e.target.value);
    }, 500));
</script>
<script src="{{ asset('js/admin/Sortable.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const galleryContainer = document.getElementById('gallery-container');
        if (galleryContainer) {
            new Sortable(galleryContainer, {
                animation: 150,
                ghostClass: 'bg-stone-50',
                dragClass: 'opacity-50'
            });
        }
    });
</script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<script>
    window.productDescriptionEditor = null;
    document.addEventListener("DOMContentLoaded", function () {
        if (document.querySelector('#description')) {
            ClassicEditor
                .create(document.querySelector('#description'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote'],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                            { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                            { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' }
                        ]
                    }
                })
                .then(editor => {
                    window.productDescriptionEditor = editor;
                })
                .catch(error => {
                    console.error('CKEditor error:', error);
                });
        }

        // AJAX Form Submission
        const form = document.getElementById('product-form');
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                if (window.productDescriptionEditor) {
                    document.getElementById('description').value = window.productDescriptionEditor.getData();
                }

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating Product...';

                const formData = new FormData(this);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const response = await axios.post(this.action, formData, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    if (response.data && response.data.success) {
                        toastr.success(response.data.message || 'Product updated successfully!');
                        setTimeout(() => {
                            if (response.data.redirect) {
                                window.location.href = response.data.redirect;
                            } else {
                                window.location.reload();
                            }
                        }, 800);
                    } else {
                        toastr.error(response.data?.message || 'Failed to update product');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                } catch (error) {
                    console.error('Update product error:', error);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;

                    if (error.response?.status === 422) {
                        const errors = error.response.data?.errors;
                        if (errors) {
                            Object.values(errors).flat().forEach(msg => toastr.error(msg));
                        } else {
                            toastr.error(error.response.data?.message || 'Validation error');
                        }
                    } else if (error.response?.status === 419) {
                        toastr.error('Session expired. Please refresh the page or log in again.');
                    } else {
                        toastr.error(error.response?.data?.message || 'An error occurred while saving the product.');
                    }
                }
            });
        }
    });
</script>
@endpush
