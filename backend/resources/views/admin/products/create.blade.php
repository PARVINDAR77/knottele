@extends('admin.layouts.master')

@section('title', 'Create Product')

@section('content')
<div class="mb-8">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-stone-800 mb-2">Create Product</h2>
            <nav class="text-sm text-stone-500 font-medium">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-red-600 transition-colors">Dashboard</a>
                <span class="mx-2 text-stone-300">/</span>
                <a href="{{ route('admin.products.index') }}" class="hover:text-red-600 transition-colors">Products</a>
                <span class="mx-2 text-stone-300">/</span>
                <span class="text-stone-800 font-semibold">Create</span>
            </nav>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
</div>

<form action="{{ route('admin.products.store') }}" method="POST" id="product-form" class="space-y-6" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Basic Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 pb-2 border-b">Basic Information</h3>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-stone-700 mb-1">Product Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition"
                            placeholder="Enter product name">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="slug" class="block text-sm font-medium text-stone-700 mb-1">Slug</label>
                            <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                                class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition bg-stone-50 text-stone-500">
                            @error('slug') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label for="product_code" class="block text-sm font-medium text-stone-700">Product Code (Art. No.)</label>
                                <button type="button" onclick="generateProductCode()" class="text-xs text-red-600 hover:text-red-700 font-semibold flex items-center gap-1 transition">
                                    <i class="fas fa-magic text-[10px]"></i> Auto
                                </button>
                            </div>
                            <input type="text" name="product_code" id="product_code" value="{{ old('product_code', $nextProductCode ?? '') }}"
                                placeholder="{{ $nextProductCode ?? 'prod-13' }}"
                                class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                            @error('product_code') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
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
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">{{ old('short_description') }}</textarea>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-stone-700 mb-1">Full Description</label>
                        <textarea name="description" id="description" rows="6"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Media (5 Photo Slots) -->
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
                            $slotOldId = old('product_images.' . $i, ($i === 0 ? old('main_image_id') : (old('gallery_image_ids.' . ($i - 1)) ?? '')));
                            $slotOldUrl = old('product_image_urls.' . $i, ($i === 0 ? old('main_image_url') : (old('gallery_image_urls.' . ($i - 1)) ?? '')));
                            $isPrimary = $i === 0;
                        @endphp
                        <div class="relative group flex flex-col bg-stone-50/80 rounded-2xl border-2 {{ $isPrimary ? 'border-red-300 bg-red-50/20 ring-1 ring-red-100' : 'border-dashed border-stone-300 hover:border-stone-400' }} p-3 transition duration-200" id="slot-card-{{ $i }}">
                            
                            <!-- Slot Header / Badge -->
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-bold uppercase tracking-wider {{ $isPrimary ? 'text-red-600 bg-red-100/70 px-2 py-0.5 rounded-md' : 'text-stone-500 bg-stone-200/60 px-2 py-0.5 rounded-md' }}">
                                    {{ $isPrimary ? '★ Primary (Cover)' : 'Slot #' . ($i + 1) }}
                                </span>
                                <button type="button" onclick="clearImageSlot({{ $i }})" id="slot-clear-btn-{{ $i }}" class="{{ $slotOldId ? '' : 'hidden' }} text-stone-400 hover:text-red-600 text-xs p-1 rounded-md transition" title="Remove photo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <!-- Preview Area -->
                            <div class="relative aspect-square w-full rounded-xl overflow-hidden bg-white border border-stone-200/80 flex items-center justify-center shadow-inner group/thumb mb-3">
                                <img id="slot-img-{{ $i }}" src="{{ $slotOldUrl ?: '/images/logo/Logo_1.png' }}" class="{{ $slotOldUrl ? '' : 'hidden' }} w-full h-full object-cover">
                                
                                <div id="slot-empty-{{ $i }}" class="{{ $slotOldUrl ? 'hidden' : 'flex' }} flex-col items-center justify-center p-3 text-center cursor-pointer group/slot-empty w-full h-full" onclick="document.getElementById('slot-file-input-{{ $i }}').click()">
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
                            <input type="hidden" name="product_images[{{ $i }}]" id="slot-input-{{ $i }}" value="{{ $slotOldId }}">
                            @if($i === 0)
                                <input type="hidden" name="main_image_id" id="main_image_id" value="{{ $slotOldId }}">
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
            <div id="simple-product-fields" class="bg-white rounded-xl shadow-sm p-6 border border-stone-100 {{ old('product_type', 'simple') === 'simple' ? '' : 'hidden' }}">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 pb-2 border-b">Pricing & Inventory</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="price" class="block text-sm font-medium text-stone-700 mb-1">Price <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-stone-500 font-bold">₹</span>
                            <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0"
                                class="w-full pl-8 pr-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition font-bold text-stone-800">
                        </div>
                        @error('price') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="compare_price" class="block text-sm font-medium text-stone-700 mb-1">Compare at Price</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-stone-500">₹</span>
                            <input type="number" name="compare_price" id="compare_price" value="{{ old('compare_price') }}" step="0.01" min="0"
                                class="w-full pl-8 pr-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label for="cost_price" class="block text-sm font-medium text-stone-700 mb-1">Cost Price</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-stone-500">₹</span>
                            <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                                class="w-full pl-8 pr-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="sku" class="block text-sm font-medium text-stone-700">SKU <span class="text-rose-500">*</span></label>
                            <button type="button" onclick="generateSku()" class="text-xs text-red-600 hover:text-red-700 font-semibold flex items-center gap-1 transition">
                                <i class="fas fa-magic text-[10px]"></i> Auto
                            </button>
                        </div>
                        <input type="text" name="sku" id="sku" value="{{ old('sku') }}" placeholder="e.g. KNT-ITEM-101"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                        @error('sku') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-stone-700 mb-1">Stock Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity') }}"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none transition">
                        @error('stock_quantity') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>
                </div>
                      <!-- Configurable Product Fields -->
            <div id="configurable-product-fields" class="bg-white rounded-xl shadow-sm p-6 border border-stone-100 {{ old('product_type') === 'configurable' ? '' : 'hidden' }}">
                <div class="flex items-center justify-between mb-4 pb-2 border-b">
                    <h3 class="text-lg font-semibold text-stone-800">Product Variants</h3>
                    <button type="button" onclick="addVariantRow()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-bold shadow transition">
                        <i class="fas fa-plus mr-1"></i> Add Variant
                    </button>
                </div>

                <!-- Variants Table -->
                <div id="variants-container" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-200 border rounded-xl overflow-hidden" id="variants-table">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider">Variant</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-32">SKU</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-24">Price</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-24">Stock</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-stone-500 uppercase tracking-wider w-48">Images</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-stone-500 uppercase tracking-wider w-16">Default</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-stone-500 uppercase tracking-wider w-16">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="variants-tbody">
                            @if(old('variants'))
                                @foreach(old('variants') as $idx => $variant)
                                    <tr id="variant-row-{{ $idx }}">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">
                                            Variant {{ $idx + 1 }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text" name="variants[{{ $idx }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm {{ $errors->has('variants.'.$idx.'.sku') ? 'border-red-500' : '' }}">
                                            @error('variants.'.$idx.'.sku') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" name="variants[{{ $idx }}][price]" value="{{ $variant['price'] ?? '' }}" step="0.01" class="w-full px-2 py-1 border rounded text-sm {{ $errors->has('variants.'.$idx.'.price') ? 'border-red-500' : '' }}">
                                            @error('variants.'.$idx.'.price') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="number" name="variants[{{ $idx }}][stock_quantity]" value="{{ $variant['stock_quantity'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm {{ $errors->has('variants.'.$idx.'.stock_quantity') ? 'border-red-500' : '' }}">
                                            @error('variants.'.$idx.'.stock_quantity') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="px-3 py-2">
                                            <div id="variant-images-{{ $idx }}" class="flex gap-1 flex-wrap">
                                                @if(!empty($variant['main_image_id']))
                                                    <div class="text-xs text-green-600 font-bold">Main Img Set</div>
                                                @endif
                                            </div>
                                            <button type="button" onclick="openVariantMediaModal({{ $idx }})" class="text-xs text-blue-600 hover:text-blue-800 mt-1">Manage Images</button>
                                            <input type="hidden" name="variants[{{ $idx }}][main_image_id]" id="variant-main-input-{{ $idx }}" value="{{ $variant['main_image_id'] ?? '' }}">
                                            
                                            <div id="variant-gallery-inputs-{{ $idx }}">
                                                @if(isset($variant['gallery_image_ids']))
                                                    @foreach($variant['gallery_image_ids'] as $gid)
                                                        <input type="hidden" name="variants[{{ $idx }}][gallery_image_ids][]" value="{{ $gid }}">
                                                    @endforeach
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="radio" name="default_variant_index" value="{{ $idx }}" {{ (old('default_variant_index') == $idx) ? 'checked' : '' }}
                                                onchange="document.querySelectorAll('.is-default-input').forEach((el, i) => el.value = (i === {{ $idx }} ? '1' : '0'))">
                                            <input type="hidden" id="is-default-{{ $idx }}" name="variants[{{ $idx }}][is_default]" value="{{ (old('variants.'.$idx.'.is_default') ?? '0') }}" class="is-default-input">
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" onclick="removeVariant({{ $idx }})" class="text-red-500 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Publish Status -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 border-b pb-2">Publish</h3>
                <div class="space-y-4">
                     <div>
                        <label for="status" class="block text-sm font-medium text-stone-700 mb-1">Status</label>
                        <select name="status" id="status" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_featured" id="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_featured" class="text-sm text-stone-700">Featured Product</label>
                    </div>

                    <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_new" id="is_new" value="1" {{ old('is_new') ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_new" class="text-sm text-stone-700">New Arrival</label>
                    </div>

                     <div class="flex items-center space-x-2">
                        <input type="checkbox" name="is_bestseller" id="is_bestseller" value="1" {{ old('is_bestseller') ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="is_bestseller" class="text-sm text-stone-700">Bestseller</label>
                    </div>

                    <div class="pt-4 border-t border-stone-100">
                        <button type="submit" class="w-full btn-primary py-3 justify-center shadow-lg shadow-red-100 uppercase tracking-widest text-xs font-bold">
                            <i class="fas fa-save mr-2"></i>Create Product
                        </button>
                    </div>
                </div>
            </div>

            <!-- Organization -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-stone-100">
                <h3 class="text-lg font-semibold text-stone-800 mb-4 border-b pb-2">Organization</h3>

                <div class="space-y-4">
                     <div>
                        <label for="product_type" class="block text-sm font-medium text-stone-700 mb-1">Product Type</label>
                        <select name="product_type" id="product_type" onchange="toggleProductType()"
                            class="w-full px-4 py-2 border border-red-200 bg-red-50 rounded-lg outline-none focus:ring-2 focus:ring-red-500 font-bold text-red-700">
                            <option value="simple" {{ old('product_type') == 'simple' ? 'selected' : '' }}>Simple Product</option>
                            <option value="configurable" {{ old('product_type') == 'configurable' ? 'selected' : '' }}>Configurable Product</option>
                        </select>
                    </div>

                    <div>
                        <label for="main_category_id" class="block text-sm font-medium text-stone-700 mb-1">Main Category <span class="text-rose-500">*</span></label>
                        <select name="main_category_id" id="main_category_id" required onchange="handleCategoryChange(this.value)"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('main_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                @if($category->children)
                                    @foreach($category->children as $child)
                                        <option value="{{ $child->id }}" {{ old('main_category_id') == $child->id ? 'selected' : '' }}> -- {{ $child->name }}</option>
                                    @endforeach
                                @endif
                            @endforeach
                        </select>
                        @error('main_category_id') <p class="text-rose-500 text-xs mt-1 italic">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand_id" class="block text-sm font-medium text-stone-700 mb-1">Brand</label>
                        <select name="brand_id" id="brand_id" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-stone-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}"
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
                        <input type="number" name="weight" id="weight" value="{{ old('weight') }}" step="0.001"
                            class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-xs text-stone-500 mt-1">Note: Enter weight in KG <br>(e.g., 1 KG = 1.000, 100 GM = 0.100)</p>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Length</label>
                             <input type="number" name="length" value="{{ old('length') }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Width</label>
                             <input type="number" name="width" value="{{ old('width') }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                        <div>
                             <label class="block text-[10px] text-stone-400 uppercase font-bold mb-1">Height</label>
                             <input type="number" name="height" value="{{ old('height') }}" placeholder="cm" class="w-full px-2 py-2 border border-stone-200 rounded-lg focus:ring-1 focus:ring-red-500 outline-none">
                        </div>
                    </div>

                     <div>
                        <label for="tax_class_id" class="block text-sm font-medium text-stone-700 mb-1">Tax Class</label>
                        <select name="tax_class_id" id="tax_class_id" class="w-full px-4 py-2 border border-stone-300 rounded-lg outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">None</option>
                            @foreach($taxClasses as $tax)
                                <option value="{{ $tax->id }}" {{ old('tax_class_id') == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ number_format($tax->total_rate, 2) }}%)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox" name="cod_available" id="cod_available" value="1" {{ old('cod_available') ? 'checked' : '' }}
                            class="rounded text-red-500 focus:ring-red-500 h-4 w-4">
                        <label for="cod_available" class="text-sm text-stone-700">COD Available</label>
                    </div>
                 </div>
            </div>

        </div>
    </div>
</form>

<!-- Media Modal -->
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
                <button type="button" id="media-select-btn" onclick="confirmMediaSelection()" class="btn-primary min-w-[120px] justify-center disabled:opacity-50 disabled:cursor-not-allowed">
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

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.21/lodash.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ asset('js/admin/Sortable.min.js') }}"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>

<script>
    // Global State
    let currentMode = 'slot-0'; // 'slot-0'...'slot-4', 'multi-slot', 'variant-main', 'variant-gallery'
    let activeSlotIndex = 0;
    let currentVariantIndex = null;
    let selectedImages = [];
    let currentMediaData = null;
    let variantCount = {{ count(old('variants', [])) }};

    // Old Input Data
    const oldVariants = @json(old('variants', []));
    const oldCategory = "{{ old('main_category_id') }}";
    const oldProductType = "{{ old('product_type') }}";

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Restore product type if exists, otherwise toggle default
        if(oldProductType) {
            document.getElementById('product_type').value = oldProductType;
        }
        toggleProductType();

        // Set up media upload
        document.getElementById('media-upload').addEventListener('change', handleFileUpload);

        // Initialize CKEditor
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
                    console.error(error);
                });
        }

        // Show validation errors with toastr
        @if($errors->any())
            @foreach($errors->all() as $error)
                toastr.error('{{ $error }}');
            @endforeach
        @endif

        // Initialize Sortable
        const galleryContainer = document.getElementById('gallery-container');
        if (galleryContainer) {
            new Sortable(galleryContainer, {
                animation: 150,
                ghostClass: 'bg-stone-50',
                dragClass: 'opacity-50'
            });
        }
    });

    // =============== PRODUCT TYPE & CATEGORY FUNCTIONS ===============

    function toggleProductType() {
        const type = document.getElementById('product_type').value;
        const simpleFields = document.getElementById('simple-product-fields');
        const configFields = document.getElementById('configurable-product-fields');

        if (type === 'simple') {
            simpleFields.classList.remove('hidden');
            configFields.classList.add('hidden');
        } else {
            simpleFields.classList.add('hidden');
            configFields.classList.remove('hidden');
            const tbody = document.getElementById('variants-tbody');
            if (tbody && tbody.children.length === 0) {
                addVariantRow();
            }
        }
    }

    const defaultNextCode = "{{ $nextProductCode ?? '' }}";

    function generateProductCode() {
        const codeInput = document.getElementById('product_code');
        if (codeInput) {
            if (defaultNextCode) {
                codeInput.value = defaultNextCode;
            } else {
                codeInput.value = 'prod-' + Math.floor(Date.now() / 1000).toString().slice(-4);
            }
            toastr.info('Generated Product Code: ' + codeInput.value);
        }
    }

    function generateSku() {
        const name = document.getElementById('name').value.trim();
        const prefix = name ? name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6) : 'PROD';
        const randomNum = Math.floor(100 + Math.random() * 900);
        const skuInput = document.getElementById('sku');
        if (skuInput) {
            skuInput.value = `KNT-${prefix || 'PROD'}-${randomNum}`;
            skuInput.dataset.autoGenerated = 'false';
            toastr.info('Generated SKU: ' + skuInput.value);
        }
    }

    // Auto-generate slug, SKU, and code on typing name
    document.getElementById('name').addEventListener('input', function() {
        const nameVal = this.value.trim();
        let slug = nameVal.toLowerCase()
            .replace(/[^\w ]+/g, '')
            .replace(/ +/g, '-');
        document.getElementById('slug').value = slug;

        const skuInput = document.getElementById('sku');
        if (skuInput && (!skuInput.value || skuInput.dataset.autoGenerated === 'true')) {
            const prefix = nameVal.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
            if (prefix) {
                skuInput.value = `KNT-${prefix}-${Math.floor(100 + Math.random() * 900)}`;
                skuInput.dataset.autoGenerated = 'true';
            }
        }

        const codeInput = document.getElementById('product_code');
        if (codeInput && !codeInput.value && defaultNextCode) {
            codeInput.value = defaultNextCode;
        }
    });

    document.getElementById('sku')?.addEventListener('input', function() {
        this.dataset.autoGenerated = 'false';
    });

    function handleCategoryChange(categoryId) {
        // Category changed
    }

    // =============== VARIANTS FUNCTIONS ===============

    function addVariantRow() {
        const tbody = document.getElementById('variants-tbody');
        const idx = variantCount++;
        const baseSku = document.getElementById('sku')?.value || 'PRD';
        const basePrice = document.getElementById('price')?.value || '0';

        const tr = document.createElement('tr');
        tr.id = `variant-row-${idx}`;
        tr.innerHTML = `
            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-700">
                Variant ${idx + 1}
            </td>
            <td class="px-3 py-2">
                <input type="text" name="variants[${idx}][sku]" value="${baseSku}-${idx + 1}" class="w-full px-2 py-1 border rounded text-sm" required>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="variants[${idx}][price]" value="${basePrice}" step="0.01" min="0" class="w-full px-2 py-1 border rounded text-sm" required>
            </td>
            <td class="px-3 py-2">
                <input type="number" name="variants[${idx}][stock_quantity]" value="0" min="0" class="w-full px-2 py-1 border rounded text-sm" required>
            </td>
            <td class="px-3 py-2">
                <div id="variant-images-${idx}" class="flex gap-1 flex-wrap"></div>
                <button type="button" onclick="openVariantMediaModal(${idx})" class="text-xs text-blue-600 hover:text-blue-800 mt-1">Manage Images</button>
                <input type="hidden" name="variants[${idx}][main_image_id]" id="variant-main-input-${idx}">
                <div id="variant-gallery-inputs-${idx}"></div>
            </td>
            <td class="px-3 py-2 text-center">
                <input type="radio" name="default_variant_index" value="${idx}" ${idx === 0 ? 'checked' : ''}
                    onchange="document.querySelectorAll('.is-default-input').forEach((el, i) => el.value = (i === ${idx} ? '1' : '0'))">
                <input type="hidden" id="is-default-${idx}" name="variants[${idx}][is_default]" value="${idx === 0 ? '1' : '0'}" class="is-default-input">
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" onclick="removeVariant(${idx})" class="text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function removeVariant(idx) {
        const row = document.getElementById(`variant-row-${idx}`);
        if (row) row.remove();
    }

    // =============== MEDIA MANAGEMENT FUNCTIONS (5 Slots + Variants) ===============

    function openMediaModalForSlot(slotIndex) {
        activeSlotIndex = slotIndex;
        currentMode = 'slot-' + slotIndex;
        selectedImages = [];

        document.getElementById('modal-title').textContent = slotIndex === 0 ? 'Select Primary Cover Photo' : `Select Photo for Slot #${slotIndex + 1}`;
        document.getElementById('media-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loadMedia(1);
    }

    function openMediaModalMulti() {
        currentMode = 'multi-slot';
        selectedImages = [];
        document.getElementById('modal-title').textContent = 'Select Product Photos';
        document.getElementById('media-modal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        loadMedia(1);
    }

    function openMediaModal(mode = 'main') {
        if (mode === 'main') {
            openMediaModalForSlot(0);
        } else {
            currentMode = mode;
            selectedImages = [];
            document.getElementById('modal-title').textContent = 'Select Media';
            document.getElementById('media-modal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            loadMedia(1);
        }
    }

    function closeMediaModal() {
        document.getElementById('media-modal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        selectedImages = [];
        currentMode = 'slot-0';
        currentVariantIndex = null;
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

    async function loadMedia(page = 1, search = '') {
        const grid = document.getElementById('media-grid');
        const pagination = document.getElementById('media-pagination');

        grid.innerHTML = '<div class="col-span-full text-center py-10 text-stone-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading media...</div>';

        try {
            const response = await axios.get(`{{ route('admin.media.data') }}`, {
                params: { page, search }
            });

            currentMediaData = response.data;
            renderMediaGrid(response.data.data);
            renderPagination(response.data);
        } catch (error) {
            console.error('Media load error:', error);
            grid.innerHTML = '<div class="col-span-full text-center py-10 text-red-500">Error loading media.</div>';
            toastr.error('Failed to load media');
        }
    }

    function sanitizeImageUrl(rawUrl) {
        if (!rawUrl) return '/images/logo/Logo_1.png';
        let url = String(rawUrl).trim();
        if (url.startsWith(window.location.origin)) {
            url = url.substring(window.location.origin.length);
        }
        if (/^https?:\/\//i.test(url)) {
            return url;
        }
        if (url.startsWith('/storage/images/')) {
            url = url.replace('/storage/images/', '/images/');
        } else if (url.startsWith('storage/images/')) {
            url = url.replace('storage/images/', '/images/');
        }
        if (!url.startsWith('/')) {
            url = '/' + url;
        }
        return url;
    }

    function renderMediaGrid(media) {
        const grid = document.getElementById('media-grid');

        if (!media || media.length === 0) {
            grid.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500">No media found.</div>';
            return;
        }

        let html = '';
        media.forEach(item => {
            const isSelected = selectedImages.some(img => img.id === item.id);
            const rawUrl = item.thumb_url || item.thumbnail_url || item.url || item.full_url || item.file_path || item.path;
            const url = sanitizeImageUrl(rawUrl);
            const name = item.file_name || item.name || item.filename || 'Image';

            html += `
            <div class="relative border rounded-lg overflow-hidden cursor-pointer group hover:shadow-md transition ${isSelected ? 'ring-2 ring-red-500' : ''}"
                 onclick="toggleImageSelection(${item.id}, '${url}')"
                 ondblclick="toggleImageSelection(${item.id}, '${url}'); confirmMediaSelection();">
                <img src="${url}" class="w-full h-32 object-cover bg-stone-100" onerror="this.onerror=null;this.src='/images/logo/Logo_1.png'">
                <div class="p-2 text-xs truncate font-medium text-stone-700 bg-white border-t">${name}</div>
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition"></div>
                ${isSelected ?
                    '<div class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs shadow">✓</div>'
                    : ''}
            </div>
            `;
        });

        grid.innerHTML = html;
    }

    function renderPagination(data) {
        const pagination = document.getElementById('media-pagination');
        if (!data || !data.links || data.links.length <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '<div class="flex space-x-2">';
        
        data.links.forEach(link => {
            if (link.url) {
                const active = link.active ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700';
                const url = new URL(link.url, window.location.origin);
                const page = url.searchParams.get('page') || link.label.replace('&laquo; Previous', '1').replace('Next &raquo;', data.last_page);
                
                html += `
                <button onclick="loadMedia(${page}, document.getElementById('media-search').value)"
                        class="px-3 py-1 rounded ${active} hover:bg-blue-600 hover:text-white transition">
                    ${link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                </button>
                `;
            }
        });
        
        html += '</div>';
        pagination.innerHTML = html;
    }

    function toggleImageSelection(id, url) {
        if (currentMode.startsWith('slot-') || currentMode === 'main' || currentMode === 'variant-main') {
            selectedImages = [{ id, url }];
        } else {
            const existingIndex = selectedImages.findIndex(img => img.id === id);
            if (existingIndex === -1) {
                selectedImages.push({ id, url });
            } else {
                selectedImages.splice(existingIndex, 1);
            }
        }

        if (currentMediaData && currentMediaData.data) {
            renderMediaGrid(currentMediaData.data);
        }
    }

    function confirmMediaSelection() {
        if (selectedImages.length === 0) {
            toastr.warning('Please select at least one image');
            return;
        }

        if (currentMode === 'main' || currentMode.startsWith('slot-')) {
            const slotIdx = currentMode === 'main' ? 0 : parseInt(currentMode.replace('slot-', ''), 10);
            const { id, url } = selectedImages[0];
            assignImageToSlot(slotIdx, id, url);
            toastr.success(`Photo assigned to Slot #${slotIdx + 1}`);
        } else if (currentMode === 'multi-slot') {
            let uploadIdx = 0;
            for (let s = 0; s < 5 && uploadIdx < selectedImages.length; s++) {
                const curVal = document.getElementById(`slot-input-${s}`)?.value;
                if (!curVal) {
                    assignImageToSlot(s, selectedImages[uploadIdx].id, selectedImages[uploadIdx].url);
                    uploadIdx++;
                }
            }
            for (let s = 0; s < 5 && uploadIdx < selectedImages.length; s++) {
                assignImageToSlot(s, selectedImages[uploadIdx].id, selectedImages[uploadIdx].url);
                uploadIdx++;
            }
            toastr.success(`${selectedImages.length} photo(s) assigned!`);
        } else if (currentMode === 'variant-main') {
            const { id, url } = selectedImages[0];
            setVariantMainImage(currentVariantIndex, id, url);
        } else if (currentMode === 'variant-gallery') {
            selectedImages.forEach(({ id, url }) => {
                addVariantGalleryImage(currentVariantIndex, id, url);
            });
        }

        closeMediaModal();
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

        if(hiddenContainer.querySelector(`input[value="${id}"]`)) {
            toastr.warning('Image already added to variant');
            return;
        }

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

    function openVariantMediaModal(idx) {
        currentVariantIndex = idx;

        Swal.fire({
            title: 'Manage Variant Images',
            html: `
                <div class="text-left">
                    <button onclick="setVariantMainImageModal(${idx})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                        Set Main Image
                    </button>
                    <button onclick="addVariantGalleryImageModal(${idx})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                        Add Gallery Images
                    </button>
                </div>
            `,
            showCancelButton: true,
            cancelButtonText: 'Cancel',
            showConfirmButton: false
        });
    }

    function setVariantMainImageModal(idx) {
        Swal.close();
        openMediaModal('variant-main');
    }

    function addVariantGalleryImageModal(idx) {
        Swal.close();
        openMediaModal('variant-gallery');
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
                    if (currentMode === 'main' || currentMode.startsWith('slot-') || currentMode === 'variant-main') {
                        const first = response.data.all_uploaded[0];
                        selectedImages = [{ id: first.id, url: first.url }];
                    } else {
                        response.data.all_uploaded.forEach(item => {
                            if (!selectedImages.some(img => img.id === item.id)) {
                                selectedImages.push({ id: item.id, url: item.url });
                            }
                        });
                    }
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

    // =============== UTILITY FUNCTIONS ===============
    
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

    // Debounced search
    document.getElementById('media-search').addEventListener('input', debounce(function(e) {
        loadMedia(1, e.target.value);
    }, 500));

    // Handle Enter key in search
    document.getElementById('media-search').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadMedia(1, this.value);
        }
    });

    // Handle form submission
    const form = document.getElementById('product-form');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const productType = document.getElementById('product_type').value;
            if (productType === 'configurable') {
                const variantsExist = document.querySelectorAll('[id^="variant-row-"]').length > 0;
                if (!variantsExist) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Variants',
                        text: 'Please generate at least one variant for configurable product.'
                    });
                    return;
                }
            }

            if (window.productDescriptionEditor) {
                document.getElementById('description').value = window.productDescriptionEditor.getData();
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Product...';
            }

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
                    toastr.success(response.data.message || 'Product created successfully!');
                    setTimeout(() => {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.href = "{{ route('admin.products.index') }}";
                        }
                    }, 800);
                } else {
                    toastr.error(response.data?.message || 'Failed to create product');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }
            } catch (error) {
                console.error('Create product error:', error);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }

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
</script>
@endpush
