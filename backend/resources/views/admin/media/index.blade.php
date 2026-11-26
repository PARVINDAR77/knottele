{{-- resources/views/admin/media/index.blade.php --}}
@extends('admin.layouts.master')

@section('title', 'Knotelle Media Manager')

@section('content')
    <style>
        /* Mobile layout & modal form responsiveness helpers */
        @media (max-width: 640px) {
            .footer-col1-row,
            .footer-col2-row,
            .navbar-link-row {
                width: 100% !important;
                min-width: 0 !important;
            }
            .footer-col1-row input,
            .footer-col2-row input,
            .navbar-link-row input {
                min-width: 0 !important;
                width: 100% !important;
            }
        }
    </style>

    <div class="mb-4 sm:mb-8">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 sm:gap-4">
            <div>
                <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                    <h2 class="text-xl sm:text-2xl font-bold text-stone-800">Media Manager</h2>
                    <span class="hidden sm:inline-block px-2.5 py-0.5 bg-red-50 text-red-700 text-[11px] font-bold rounded-full border border-red-200 uppercase tracking-wider">
                        Structured Taxonomy
                    </span>
                </div>
                <p class="text-xs text-stone-500 font-medium mt-0.5 hidden sm:block">Manage and replace website visual assets by Page, Section, and Device.</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openGenericUploadModal()" class="btn-primary text-xs px-3.5 py-2 sm:px-4 sm:py-2.5 flex items-center gap-1.5 shadow-2xs cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-xs sm:text-sm"></i>
                    <span>Upload Media</span>
                </button>
                <button onclick="loadManagerData()" class="btn-secondary text-xs px-3.5 py-2 sm:px-4 sm:py-2.5 flex items-center gap-1.5 cursor-pointer">
                    <i class="fas fa-sync-alt text-xs"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter & View Controls Toolbar -->
    <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-stone-100 p-3.5 sm:p-6 mb-4 sm:mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 sm:gap-4">
            
            <!-- Filters Group -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3 flex-1">
                <!-- Page Filter (Strictly 7 options) -->
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase tracking-wider mb-1">Page</label>
                    <select id="filterPage" onchange="onPageFilterChange()" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="all">All Pages</option>
                        <option value="homepage" selected>Homepage</option>
                        <option value="shop">Shop Categories ↗</option>
                        <option value="custom_order">Custom Order Page</option>
                        <option value="about">About Page</option>
                        <option value="contact">Contact Page</option>
                        <option value="footer">Footer</option>
                    </select>
                </div>

                <!-- Section Filter (Dynamically populated based on Page) -->
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase tracking-wider mb-1">Section</label>
                    <select id="filterSection" onchange="applyFilters()" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="all">All Sections</option>
                        <option value="hero">Hero Banner</option>
                        <option value="categories">Shop by Category</option>
                        <option value="custom_crochet">Custom Crochet Banner</option>
                        <option value="brand_story">Brand Story</option>
                        <option value="bestsellers">Best Sellers</option>
                        <option value="trust_benefits">Trust & Benefits</option>
                        <option value="custom_order">Custom Order CTA</option>
                        <option value="newsletter">Newsletter</option>
                        <option value="blog_reels">Blog / Videos & Reels</option>
                    </select>
                </div>

                <!-- Device Target (Desktop Only) -->
                <div>
                    <label class="block text-[11px] font-bold text-stone-500 uppercase tracking-wider mb-1">Device Target</label>
                    <select id="filterDevice" onchange="applyFilters()" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        <option value="desktop" selected>Desktop / All Desktop</option>
                    </select>
                </div>
            </div>

        </div>
    </div>

    <!-- MAIN VIEW CONTAINER 1: STRUCTURED SECTION CARDS -->
    <div id="sectionsViewContainer" class="space-y-8">
        
        <!-- Loading Spinner -->
        <div id="managerLoading" class="bg-white rounded-3xl p-16 text-center border border-stone-100 shadow-sm">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-3 border-red-600 border-t-transparent mb-4"></div>
            <p class="text-stone-700 font-bold text-base">Loading Knotelle Media Manager...</p>
            <p class="text-stone-400 text-xs mt-1">Fetching structured slots and website assets</p>
        </div>

        <!-- Rendered Sections Content will be inserted dynamically here -->
        <div id="sectionsList" class="space-y-8 hidden"></div>

    </div>

    <!-- MAIN VIEW CONTAINER 2: ALL MEDIA LIBRARY TABLE & UPLOAD -->
    <div id="libraryViewContainer" class="hidden space-y-8">
        
        <!-- Drag & Drop Upload Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-stone-100 p-8">
            <div class="flex flex-col items-center justify-center border-2 border-dashed border-stone-200 rounded-3xl p-10 hover:border-red-400 hover:bg-red-50/30 transition-all duration-300 group cursor-pointer"
                id="libraryDropzone" onclick="document.getElementById('libraryFileInput').click()">
                <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-cloud-upload-alt text-3xl text-red-600"></i>
                </div>
                <p class="text-lg font-bold text-stone-800 mb-1">Drag & drop files here</p>
                <p class="text-stone-500 mb-4 text-xs font-medium">or click to browse your desktop / mobile gallery</p>
                <div class="flex space-x-2 items-center text-xs text-stone-400 font-bold uppercase tracking-wider">
                    <span>JPG</span> • <span>PNG</span> • <span>WEBP</span> • <span>SVG</span>
                </div>
                <input type="file" id="libraryFileInput" multiple class="hidden" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
            </div>
        </div>

        <!-- Tabulator Table Card -->
        <div class="bg-white rounded-3xl shadow-sm border border-stone-100 overflow-hidden p-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <div class="relative w-72">
                    <input type="text" id="librarySearchInput" placeholder="Search media by name/slot..."
                        class="pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500 w-full text-sm">
                    <i class="fas fa-search absolute left-3.5 top-3.5 text-stone-400"></i>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="refreshLibraryData()" class="btn-secondary text-xs py-2 px-3">
                        <i class="fas fa-sync-alt mr-1"></i>Refresh Table
                    </button>
                </div>
            </div>
            <div id="mediaTable"></div>
        </div>

    </div>

    <!-- MODAL 1: DIRECT SLOT REPLACE / UPLOAD MODAL -->
    <div id="slotUploadModal" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;" onclick="if(event.target === this) closeSlotUploadModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn">
            
            <!-- Sticky / Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="min-w-0 pr-2">
                    <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="slotModalTitle">Replace Image</h3>
                    <p class="hidden sm:block text-xs text-stone-500 font-medium truncate" id="slotModalSubtitle">Assign a new image to this website slot</p>
                </div>
                <button type="button" onclick="closeSlotUploadModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="slotUploadForm" novalidate onsubmit="handleSlotUploadSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="targetPage" name="page">
                <input type="hidden" id="targetSection" name="section">
                <input type="hidden" id="targetSlot" name="slot">
                <input type="hidden" id="targetDevice" name="device">
                <input type="hidden" id="selectedMediaId" name="media_id">

                <!-- Scrollable Body Content -->
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Recommended Dimensions Info Box -->
                    <div class="bg-amber-50 border border-amber-200/80 rounded-2xl p-3 sm:p-3.5 flex items-center gap-2.5 sm:gap-3 text-amber-800">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                            <i class="fas fa-ruler-combined text-amber-700 text-xs sm:text-sm"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="text-[10px] sm:text-xs font-bold block uppercase tracking-wider">Recommended Aspect Ratio & Dimensions</span>
                            <span class="text-xs sm:text-sm font-bold truncate block" id="slotModalDimensions">1920 × 700 px</span>
                        </div>
                    </div>

                    <!-- Dropzone / File Selector -->
                    <div class="border-2 border-dashed border-stone-200 rounded-2xl p-4 sm:p-5 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer"
                         onclick="document.getElementById('slotFileInput').click()">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 bg-red-50 rounded-xl flex items-center justify-center mx-auto mb-2 text-red-600">
                            <i class="fas fa-image text-base sm:text-lg"></i>
                        </div>
                        <p class="text-xs sm:text-sm font-bold text-stone-800" id="slotFileLabel">Choose a file from Phone / Desktop</p>
                        <p class="text-[10px] sm:text-xs text-stone-400 mt-0.5">Supports JPG, PNG, WEBP (Max 20MB)</p>
                        <input type="file" id="slotFileInput" name="file" class="hidden" accept=".jpg,.jpeg,.png,.webp,.svg" onchange="handleSlotFileChange(this)">
                    </div>

                    <!-- Live Preview of chosen file -->
                    <div id="slotFilePreviewContainer" class="hidden bg-stone-50 rounded-2xl p-2.5 sm:p-3 border border-stone-200 flex items-center gap-2.5 sm:gap-3">
                        <img id="slotFilePreviewImg" src="" class="w-12 h-12 sm:w-16 sm:h-16 rounded-xl object-cover border border-stone-200 shrink-0">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-stone-800 truncate" id="slotFileName"></p>
                            <p class="text-[10px] sm:text-[11px] text-stone-500" id="slotFileSize"></p>
                        </div>
                        <button type="button" onclick="clearSlotFileInput()" class="text-stone-400 hover:text-red-600 p-2 shrink-0">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Metadata Inputs -->
                    <div class="space-y-3 pt-1">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Title / Headline</label>
                                <input type="text" id="slotTitleInput" name="title" placeholder="e.g. Custom Crochet" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Subtitle / Highlight</label>
                                <input type="text" id="slotSubtitleInput" name="subtitle" placeholder="e.g. Just for You" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Description Paragraph</label>
                            <textarea id="slotDescInput" name="description" rows="2" placeholder="e.g. Your imagination, our yarn. Let's create something special together." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Label (CTA)</label>
                                <input type="text" id="slotCtaTextInput" name="cta_text" placeholder="e.g. Request Your Custom Order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Link URL</label>
                                <input type="text" id="slotCtaLinkInput" name="cta_link" placeholder="e.g. /custom-order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Hanging Tag / Badge Text</label>
                                <input type="text" id="slotTagTextInput" name="tag_text" placeholder="e.g. Turn Your Ideas Into Handmade Reality" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text (SEO & Accessibility)</label>
                                <input type="text" id="slotAltInput" name="alt_text" placeholder="e.g. Handmade crochet creations boutique" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeSlotUploadModal()" class="btn-secondary text-xs px-3.5 sm:px-4 py-2 sm:py-2.5">
                        Cancel
                    </button>
                    <button type="submit" id="slotUploadSubmitBtn" class="btn-primary text-xs px-4 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm">
                        <i class="fas fa-save"></i>
                        <span>Save & Apply</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL 2: RESPONSIVE DEVICE PREVIEW MODAL -->
    <div id="devicePreviewModal" class="fixed inset-0 bg-stone-900/70 backdrop-blur-sm hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;" onclick="if(event.target === this) closeDevicePreviewModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-4xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100">
            
            <!-- Header with device switcher -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex flex-wrap items-center justify-between gap-2.5 bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold shrink-0">
                        <i class="fas fa-eye text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="previewModalSlotTitle">Hero Banner Preview</h3>
                        <span class="hidden sm:block text-xs text-stone-400 font-medium truncate" id="previewModalSlotPath">Homepage → Hero Banner</span>
                    </div>
                </div>

                <!-- Device Preview Tabs -->
                <div class="flex items-center gap-2 ml-auto">
                    <div class="bg-stone-200/80 p-0.5 sm:p-1 rounded-xl sm:rounded-2xl flex items-center gap-0.5 sm:gap-1">
                        <button onclick="setPreviewDevice('desktop')" id="previewTabDesktop" class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-bold transition-all bg-white text-stone-800 shadow-xs flex items-center gap-1 sm:gap-1.5">
                            <i class="fas fa-desktop text-xs"></i>
                            <span class="hidden xs:inline">Desktop</span>
                        </button>
                        <button onclick="setPreviewDevice('tablet')" id="previewTabTablet" class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-bold transition-all text-stone-500 hover:text-stone-800 flex items-center gap-1 sm:gap-1.5">
                            <i class="fas fa-tablet-alt text-xs"></i>
                            <span class="hidden xs:inline">Tablet</span>
                        </button>
                        <button onclick="setPreviewDevice('mobile')" id="previewTabMobile" class="px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg sm:rounded-xl text-[11px] sm:text-xs font-bold transition-all text-stone-500 hover:text-stone-800 flex items-center gap-1 sm:gap-1.5">
                            <i class="fas fa-mobile-alt text-xs"></i>
                            <span class="hidden xs:inline">Mobile</span>
                        </button>
                    </div>

                    <button onclick="closeDevicePreviewModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors shrink-0">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Preview Canvas -->
            <div class="p-3 sm:p-8 flex-1 bg-stone-100 overflow-y-auto overflow-x-hidden flex items-center justify-center min-h-[280px] sm:min-h-[360px]">
                <div id="previewFrame" class="bg-white rounded-2xl shadow-xl overflow-hidden border border-stone-300 transition-all duration-300 w-full max-w-full">
                    <div class="bg-stone-200 px-3 sm:px-4 py-1.5 sm:py-2 border-b border-stone-300 flex items-center gap-2">
                        <span class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-red-400"></span>
                        <span class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-amber-400"></span>
                        <span class="w-2 sm:w-2.5 h-2 sm:h-2.5 rounded-full bg-emerald-400"></span>
                        <span class="text-[10px] font-mono text-stone-500 ml-2" id="previewFrameResolution">1920 × 700</span>
                    </div>
                    <div class="p-2 sm:p-4 flex items-center justify-center bg-[#FFF9F6]">
                        <img id="previewModalImg" src="" alt="" class="max-h-[45vh] sm:max-h-[50vh] w-auto object-contain rounded-xl shadow-xs">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL 3: EDIT SECTION OPTIONS & METADATA MODAL -->
    <div id="editMetadataModal" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;" onclick="if(event.target === this) closeEditMetadataModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="min-w-0 pr-2">
                    <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="editModalHeaderTitle">Edit Section Content & Options</h3>
                    <p class="hidden sm:block text-xs text-stone-500 font-medium truncate" id="editModalHeaderSubtitle">Customize headings, button text, and visibility</p>
                </div>
                <button type="button" onclick="closeEditMetadataModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="editMetadataForm" novalidate onsubmit="handleMetadataSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="editMediaId">
                <input type="hidden" id="editMediaPage">
                <input type="hidden" id="editMediaSection">
                <input type="hidden" id="editMediaSlot">
                
                <!-- Scrollable Body Content -->
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Title / Headline</label>
                            <input type="text" id="editMediaTitle" placeholder="e.g. Custom Crochet" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Subtitle / Highlight</label>
                            <input type="text" id="editMediaSubtitle" placeholder="e.g. Just for You" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Description Paragraph</label>
                        <textarea id="editMediaDesc" rows="2" placeholder="e.g. Your imagination, our yarn. Let's create something special together." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Label (CTA)</label>
                            <input type="text" id="editMediaCtaText" placeholder="e.g. Request Your Custom Order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Link URL</label>
                            <input type="text" id="editMediaCtaLink" placeholder="e.g. /custom-order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Hanging Tag / Badge Text</label>
                            <input type="text" id="editMediaTagText" placeholder="e.g. Turn Your Ideas Into Handmade Reality" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text (SEO & Accessibility)</label>
                            <input type="text" id="editMediaAlt" placeholder="e.g. Handmade crochet creations" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 sm:gap-3">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Device</label>
                            <select id="editMediaDevice" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800">
                                <option value="all">All Devices</option>
                                <option value="desktop">Desktop</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order</label>
                            <input type="number" id="editMediaSort" min="0" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="editMediaActive" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="editMediaActive" class="text-xs font-bold text-stone-700">Active (Visible on Storefront Homepage)</label>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeEditMetadataModal()" class="btn-secondary text-xs px-3.5 sm:px-4 py-2">Cancel</button>
                    <button type="submit" class="btn-primary text-xs px-4 sm:px-5 py-2 flex items-center gap-2 shadow-sm">
                        <i class="fas fa-save"></i>
                        <span>Save Options</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 4: HERO SLIDE MODAL (CREATE & EDIT) -->
    <div id="heroSlideModal" onclick="if(event.target === this) closeHeroSlideModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-2xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0" id="heroModalIcon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="heroModalTitle">Add Hero Slide</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate" id="heroModalSubtitle">Configure homepage panoramic visual slide & call-to-actions</p>
                    </div>
                </div>
                <button type="button" onclick="closeHeroSlideModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="heroSlideForm" novalidate onsubmit="handleHeroSlideSubmit(event); return false;" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="heroSlideId" name="id" value="">
                <input type="hidden" id="heroSlideDesktopMediaId" name="desktop_media_id" value="">
                <input type="hidden" id="heroSlideMobileMediaId" name="mobile_media_id" value="">
                <input type="hidden" id="heroSlideDesktopImagePath" name="desktop_image_path" value="">
                <input type="hidden" id="heroSlideMobileImagePath" name="mobile_image_path" value="">

                <!-- Scrollable Body Content -->
                <div class="p-3.5 sm:p-6 space-y-3.5 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    
                    <!-- Section: Content Details -->
                    <div class="space-y-2.5 sm:space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Slide Content & Typography</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Slide Title / Headline <span class="text-red-500">*</span></label>
                                <input type="text" id="heroSlideTitle" name="title" placeholder="e.g. Everyday Elegance" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Slide Tagline / Accent Badge</label>
                                <input type="text" id="heroSlideTagline" name="tagline" placeholder="e.g. Handcrafted Bags" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Slide Description</label>
                            <textarea id="heroSlideDesc" name="description" rows="2" placeholder="e.g. Artisanal crochet bags & wearable creations woven with love." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                    </div>

                    <!-- Section: Call-To-Action Buttons -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1 sm:pt-2">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Call-to-Action Buttons</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Primary Button Text</label>
                                <input type="text" id="heroSlidePrimaryBtnText" name="primary_button_text" value="Shop Now" placeholder="e.g. Shop Now" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Primary Button Link URL</label>
                                <input type="text" id="heroSlidePrimaryBtnLink" name="primary_button_link" value="/shop" placeholder="e.g. /shop or /category/bags" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Secondary Button Text</label>
                                <input type="text" id="heroSlideSecondaryBtnText" name="secondary_button_text" value="Explore Collections" placeholder="e.g. Explore Collections" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Secondary Button Link URL</label>
                                <input type="text" id="heroSlideSecondaryBtnLink" name="secondary_button_link" value="/shop" placeholder="e.g. /shop" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- Section: Media Imagery -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1 sm:pt-2">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-image text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Slide Imagery (Desktop & Mobile)</span>
                        </div>

                        <!-- Desktop Image Box -->
                        <div class="bg-stone-50/70 border border-stone-200/80 rounded-2xl p-3 sm:p-4 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-desktop text-stone-600 text-xs"></i>
                                    <span class="text-xs font-bold text-stone-800">Desktop Image <span class="text-red-500">*</span></span>
                                    <span class="text-[10px] font-bold text-stone-400 bg-stone-200/70 px-2 py-0.5 rounded-md">1920 × 700 px</span>
                                </div>
                                <button type="button" onclick="openMediaPicker('desktop')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-xs text-red-500"></i>
                                    <span>Select Library</span>
                                </button>
                            </div>

                            <!-- Desktop Dropzone / File Picker -->
                            <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 sm:p-3.5 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer"
                                 onclick="document.getElementById('heroSlideDesktopFileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-red-500 text-base mb-1"></i>
                                <p class="text-xs font-bold text-stone-700" id="heroDesktopFileLabel">Click to upload Desktop Image</p>
                                <p class="text-[10px] text-stone-400">JPG, PNG, WEBP (Max 20MB)</p>
                                <input type="file" id="heroSlideDesktopFileInput" name="desktop_image" class="hidden" accept=".jpg,.jpeg,.png,.webp,.svg" onchange="handleHeroDesktopFileChange(this)">
                            </div>

                            <!-- Desktop Live Preview -->
                            <div id="heroDesktopPreviewContainer" class="hidden bg-white rounded-xl p-2.5 border border-stone-200 flex items-center gap-3">
                                <img id="heroDesktopPreviewImg" src="" class="w-16 h-12 rounded-lg object-cover border border-stone-200 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate" id="heroDesktopFileName">Desktop Image Selected</p>
                                    <p class="text-[11px] text-emerald-600 font-semibold" id="heroDesktopFileSize">Ready to save</p>
                                </div>
                                <button type="button" onclick="clearHeroDesktopFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Mobile Image Box -->
                        <div class="bg-stone-50/70 border border-stone-200/80 rounded-2xl p-3 sm:p-4 space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-mobile-alt text-stone-600 text-xs"></i>
                                    <span class="text-xs font-bold text-stone-800">Mobile Image <span class="text-stone-400 text-[10px] font-normal">(Optional fallback)</span></span>
                                    <span class="text-[10px] font-bold text-stone-400 bg-stone-200/70 px-2 py-0.5 rounded-md">768 × 1000 px</span>
                                </div>
                                <button type="button" onclick="openMediaPicker('mobile')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-xs text-red-500"></i>
                                    <span>Select Library</span>
                                </button>
                            </div>

                            <!-- Mobile Dropzone / File Picker -->
                            <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 sm:p-3.5 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer"
                                 onclick="document.getElementById('heroSlideMobileFileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-red-500 text-base mb-1"></i>
                                <p class="text-xs font-bold text-stone-700" id="heroMobileFileLabel">Click to upload Mobile Image</p>
                                <p class="text-[10px] text-stone-400">JPG, PNG, WEBP (Max 20MB)</p>
                                <input type="file" id="heroSlideMobileFileInput" name="mobile_image" class="hidden" accept=".jpg,.jpeg,.png,.webp,.svg" onchange="handleHeroMobileFileChange(this)">
                            </div>

                            <!-- Mobile Live Preview -->
                            <div id="heroMobilePreviewContainer" class="hidden bg-white rounded-xl p-2.5 border border-stone-200 flex items-center gap-3">
                                <img id="heroMobilePreviewImg" src="" class="w-12 h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate" id="heroMobileFileName">Mobile Image Selected</p>
                                    <p class="text-[11px] text-emerald-600 font-semibold" id="heroMobileFileSize">Ready to save</p>
                                </div>
                                <button type="button" onclick="clearHeroMobileFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <!-- Section: Slide Settings & Order -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1 sm:pt-2">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-sliders-h text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Status & Order</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                                <select id="heroSlideStatus" name="status" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order</label>
                                <input type="number" id="heroSlideSortOrder" name="sort_order" min="1" placeholder="e.g. 1" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text (SEO)</label>
                                <input type="text" id="heroSlideAlt" name="alt_text" placeholder="e.g. Handcrafted Crochet" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeHeroSlideModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" id="heroSlideSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="heroSlideSubmitBtnText">Save Slide</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL 5: MEDIA LIBRARY PICKER MODAL -->
    <div id="mediaPickerModal" onclick="if(event.target === this) closeMediaPicker()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[10001] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[92vh] sm:max-h-[85vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold shrink-0">
                        <i class="fas fa-photo-video text-xs sm:text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Select from Media Library</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Choose an existing media file from your catalogue</p>
                    </div>
                </div>
                <button type="button" onclick="closeMediaPicker()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="p-3 sm:p-4 border-b border-stone-100 bg-white">
                <div class="relative">
                    <input type="text" id="mediaPickerSearch" oninput="filterMediaPicker(this.value)" placeholder="Search media library..." class="w-full pl-9 sm:pl-10 pr-4 py-2 bg-stone-50 border border-stone-200 rounded-xl text-xs font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    <i class="fas fa-search absolute left-3 top-2.5 text-stone-400 text-xs"></i>
                </div>
            </div>

            <!-- Media Grid -->
            <div class="p-3.5 sm:p-6 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                <div id="mediaPickerGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5 sm:gap-4">
                    <div class="col-span-full py-8 text-center text-stone-400">Loading media library...</div>
                </div>
            </div>

            <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 flex justify-end">
                <button type="button" onclick="closeMediaPicker()" class="btn-secondary text-xs px-4 py-2 cursor-pointer">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL 6: CUSTOMER REVIEW (TESTIMONIAL) MODAL -->
    <div id="testimonialModal" onclick="if(event.target === this) closeTestimonialModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="testimonialModalTitle">Add Customer Review</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Customer reviews rotate in the homepage reviews carousel</p>
                    </div>
                </div>
                <button type="button" onclick="closeTestimonialModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="testimonialForm" onsubmit="handleTestimonialSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="testimonialId" name="id" value="">
                
                <!-- Scrollable Body Content -->
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Customer Name <span class="text-red-500">*</span></label>
                            <input type="text" id="testimonialName" name="name" required placeholder="e.g. Priya Sharma" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Location / Tagline</label>
                            <input type="text" id="testimonialDesignation" name="designation" placeholder="e.g. Bengaluru, India" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Star Rating <span class="text-red-500">*</span></label>
                            <select id="testimonialRating" name="rating" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                <option value="5">★★★★★ (5 Stars)</option>
                                <option value="4">★★★★☆ (4 Stars)</option>
                                <option value="3">★★★☆☆ (3 Stars)</option>
                                <option value="2">★★☆☆☆ (2 Stars)</option>
                                <option value="1">★☆☆☆☆ (1 Star)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                            <div class="flex items-center gap-2 pt-2 sm:pt-2.5">
                                <input type="checkbox" id="testimonialActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="testimonialActive" class="text-xs font-bold text-stone-700">Show on Website</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Customer Review Message <span class="text-red-500">*</span></label>
                        <textarea id="testimonialMessage" name="message" rows="3" required placeholder="Write customer feedback or quote..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeTestimonialModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="testimonialSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="testimonialSubmitBtnText">Save Review</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- MODAL 7: ADD / EDIT VIDEO & REEL MODAL -->
    <div id="videoReelModal" onclick="if(event.target === this) closeVideoReelModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-5xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/90 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-sm sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-play-circle text-base sm:text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="videoReelModalTitle">Add Video / Reel</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate" id="videoReelModalSubtitle">Manage website video reels, studio journals, tutorials, and social content</p>
                    </div>
                </div>
                <button type="button" onclick="closeVideoReelModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="videoReelForm" novalidate onsubmit="handleVideoReelSubmit(event); return false;" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                @csrf
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" id="videoReelId" name="id" value="">
                <input type="hidden" id="videoReelThumbnailMediaId" name="thumbnail_media_id" value="">
                <input type="hidden" id="videoReelThumbnailUrl" name="thumbnail_url" value="">

                <!-- Scrollable Body Content (2-Column Grid on Desktop) -->
                <div id="videoReelScrollContainer" class="p-3.5 sm:p-6 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-start">
                        
                        <!-- LEFT COLUMN (col-span-7): Primary Video Info, Descriptions & Settings -->
                        <div class="lg:col-span-7 space-y-3.5 sm:space-y-4">
                            
                            <!-- 1. Title / Headline (Most Prominent - Always Visible) -->
                            <div class="bg-red-50/40 border-2 border-red-200/80 rounded-2xl p-3.5 sm:p-4 shadow-2xs">
                                <label class="block text-xs font-bold text-stone-800 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5">
                                        <i class="fas fa-heading text-red-600 text-xs"></i>
                                        <span>Title / Headline <span class="text-red-500">*</span></span>
                                    </span>
                                    <span class="text-[10px] text-stone-400 font-normal">Primary video title</span>
                                </label>
                                <input type="text" id="videoReelTitle" name="title" placeholder="e.g. Crafting the Everlasting Sunflower" class="w-full bg-white border border-stone-200 rounded-xl px-3.5 sm:px-4 py-2 sm:py-2.5 text-xs sm:text-sm font-bold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all shadow-2xs">
                                <p class="text-[10px] text-stone-400 mt-1">If empty, file name will be used.</p>
                            </div>

                            <!-- 2. Content Type & Category -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5 sm:space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Content Type <span class="text-red-500">*</span></label>
                                        <select id="videoReelContentType" name="content_type" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            <option value="reel">Reel (Vertical 9:16)</option>
                                            <option value="video">Video (Standard / Adaptive)</option>
                                            <option value="blog_video">Blog / Video Story</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Category / Label <span class="text-red-500">*</span></label>
                                        <input type="text" id="videoReelCategory" name="category_name" placeholder="e.g. Studio ASMR" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1.5 pt-0.5">
                                    <button type="button" onclick="setReelCategory('Studio ASMR')" class="text-[10px] bg-white border border-stone-200 hover:bg-red-50 hover:text-red-700 px-2.5 py-1 rounded-lg font-semibold text-stone-600 transition-colors cursor-pointer">Studio ASMR</button>
                                    <button type="button" onclick="setReelCategory('Style Guide')" class="text-[10px] bg-white border border-stone-200 hover:bg-red-50 hover:text-red-700 px-2.5 py-1 rounded-lg font-semibold text-stone-600 transition-colors cursor-pointer">Style Guide</button>
                                    <button type="button" onclick="setReelCategory('Behind the Scenes')" class="text-[10px] bg-white border border-stone-200 hover:bg-red-50 hover:text-red-700 px-2.5 py-1 rounded-lg font-semibold text-stone-600 transition-colors cursor-pointer">Behind the Scenes</button>
                                    <button type="button" onclick="setReelCategory('Masterclass')" class="text-[10px] bg-white border border-stone-200 hover:bg-red-50 hover:text-red-700 px-2.5 py-1 rounded-lg font-semibold text-stone-600 transition-colors cursor-pointer">Masterclass</button>
                                </div>
                            </div>

                            <!-- 3. Tagline & Story -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5 sm:space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Tagline / Subtitle</label>
                                    <input type="text" id="videoReelTagline" name="tagline" placeholder="e.g. Watch the petal-by-petal stitch technique" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Story / Description</label>
                                    <textarea id="videoReelDesc" name="description" rows="2" placeholder="Story or description of this video..." class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                                </div>
                            </div>

                            <!-- 4. Track Details & Settings -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5 sm:space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Duration <span class="text-stone-400 font-normal">(e.g. 00:48)</span></label>
                                        <input type="text" id="videoReelDuration" name="duration" value="00:48" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Audio / Music Name</label>
                                        <input type="text" id="videoReelAudioName" name="audio_name" placeholder="Original Audio • Knotelle" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-3 gap-2 sm:gap-2.5 pt-1">
                                    <div>
                                        <label class="block text-[11px] font-bold text-stone-700 uppercase tracking-wider mb-1">Status</label>
                                        <select id="videoReelStatus" name="status" class="w-full bg-white border border-stone-200 rounded-xl px-2 sm:px-2.5 py-1.5 sm:py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            <option value="1">Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-stone-700 uppercase tracking-wider mb-1">Featured</label>
                                        <select id="videoReelFeatured" name="is_featured" class="w-full bg-white border border-stone-200 rounded-xl px-2 sm:px-2.5 py-1.5 sm:py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-stone-700 uppercase tracking-wider mb-1">Sort Order</label>
                                        <input type="number" id="videoReelSortOrder" name="sort_order" value="1" min="1" class="w-full bg-white border border-stone-200 rounded-xl px-2 sm:px-2.5 py-1.5 sm:py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT COLUMN (col-span-5): Video Upload, Live Player & Cover Thumbnail -->
                        <div class="lg:col-span-5 space-y-3.5 sm:space-y-4">
                            
                            <!-- Video Source Box -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5 sm:space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-video text-red-600 text-xs"></i>
                                        <span class="text-xs font-bold text-stone-800 uppercase tracking-wider">Video Source</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-stone-500 bg-stone-200/70 px-2 py-0.5 rounded-md">Max 40MB</span>
                                </div>

                                <!-- File dropzone -->
                                <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 sm:p-3.5 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer bg-white"
                                     onclick="document.getElementById('videoReelFileInput').click()">
                                    <i class="fas fa-file-video text-red-500 text-lg sm:text-xl mb-1"></i>
                                    <p class="text-xs font-bold text-stone-700" id="videoReelFileLabel">Click or drop MP4 / WEBM</p>
                                    <p class="text-[10px] text-stone-400">Uploads and streams on website</p>
                                    <input type="file" id="videoReelFileInput" name="video_file" class="hidden" accept="video/mp4,video/webm,video/quicktime,video/ogg,video/x-matroska,.mp4,.webm,.mov" onchange="handleVideoFileInputChange(this)">
                                </div>

                                <div class="relative flex py-0.5 items-center">
                                    <div class="flex-grow border-t border-stone-200"></div>
                                    <span class="flex-shrink mx-2 text-stone-400 text-[9px] font-bold uppercase tracking-wider">OR DIRECT URL</span>
                                    <div class="flex-grow border-t border-stone-200"></div>
                                </div>

                                <div>
                                    <input type="text" id="videoReelUrlInput" name="video_url" oninput="handleVideoUrlInputChange(this.value)" placeholder="https://assets... or /videos/..." class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                </div>

                                <!-- Live Video Player Preview -->
                                <div id="videoPlayerPreviewContainer" class="bg-black rounded-2xl overflow-hidden border border-stone-800 p-2 text-center space-y-2 shadow-sm">
                                    <div class="flex items-center justify-between px-2 text-stone-400 text-[10px] font-bold uppercase">
                                        <span class="flex items-center gap-1.5"><i class="fas fa-play text-red-500 text-xs"></i> Video Preview</span>
                                        <span id="videoPlayerStatusBadge" class="text-stone-400 font-normal">No video</span>
                                    </div>
                                    <div class="relative w-full aspect-video max-h-40 sm:max-h-48 bg-stone-900 rounded-xl overflow-hidden flex items-center justify-center">
                                        <video id="videoReelPreviewPlayer" controls playsinline loop autoplay muted class="w-full h-full object-contain"
                                               oncanplay="handleVideoPlayerCanPlay()"
                                               onerror="handleVideoPlayerError()"></video>
                                        <div id="videoPlayerEmptyState" class="absolute inset-0 flex flex-col items-center justify-center text-stone-500 bg-stone-900/90 pointer-events-none">
                                            <i class="fas fa-film text-xl sm:text-2xl mb-1 text-stone-600"></i>
                                            <p class="text-[10px] sm:text-[11px] font-medium">Select a video file or URL</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Cover Image / Thumbnail Box -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3.5 sm:p-4 space-y-2.5 sm:space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-image text-red-600 text-xs"></i>
                                        <span class="text-xs font-bold text-stone-800 uppercase tracking-wider">Cover Thumbnail</span>
                                    </div>
                                    <button type="button" onclick="openMediaPicker('video_thumbnail')" class="px-2 py-0.5 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer shadow-2xs">
                                        <i class="fas fa-photo-video text-xs text-red-500"></i>
                                        <span>Media Library</span>
                                    </button>
                                </div>

                                <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer bg-white"
                                     onclick="document.getElementById('videoReelThumbnailInput').click()">
                                    <i class="fas fa-cloud-upload-alt text-red-500 text-base sm:text-lg mb-1"></i>
                                    <p class="text-xs font-bold text-stone-700" id="videoReelThumbnailLabel">Upload Cover Image</p>
                                    <p class="text-[10px] text-stone-400">JPG, PNG, WEBP (Optional)</p>
                                    <input type="file" id="videoReelThumbnailInput" name="thumbnail_file" class="hidden" accept=".jpg,.jpeg,.png,.webp,.svg" onchange="handleReelCoverInputChange(this)">
                                </div>

                                <div id="videoReelThumbnailPreviewContainer" class="hidden bg-white rounded-xl p-2 border border-stone-200 flex items-center gap-3">
                                    <img id="videoReelThumbnailPreviewImg" src="" class="w-12 h-16 rounded-lg object-cover border border-stone-200 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-stone-800 truncate" id="videoReelThumbnailName">Thumbnail Selected</p>
                                        <p class="text-[11px] text-emerald-600 font-semibold" id="videoReelThumbnailSize">Ready to save</p>
                                    </div>
                                    <button type="button" onclick="clearReelCoverInput()" class="text-stone-400 hover:text-red-600 p-1 cursor-pointer shrink-0">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Social Counters (Compact Row) -->
                            <div class="bg-stone-50/80 border border-stone-200/80 rounded-2xl p-3 sm:p-3.5">
                                <span class="block text-[11px] font-bold text-stone-700 uppercase tracking-wider mb-2">Display Counters</span>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-stone-500 font-semibold mb-0.5">Likes</label>
                                        <input type="number" id="videoReelLikes" name="likes" value="0" min="0" class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-stone-500 font-semibold mb-0.5">Comments</label>
                                        <input type="number" id="videoReelComments" name="comments" value="0" min="0" class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-stone-500 font-semibold mb-0.5">Views</label>
                                        <input type="number" id="videoReelViews" name="views" value="0" min="0" class="w-full bg-white border border-stone-200 rounded-lg px-2 py-1 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-1 focus:ring-red-500">
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-between gap-2 sm:gap-3">
                    <span class="hidden sm:inline text-xs text-stone-400 font-medium">All changes sync to website</span>
                    <div class="flex items-center gap-2 sm:gap-3 ml-auto">
                        <button type="button" onclick="closeVideoReelModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" id="videoReelSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                            <i class="fas fa-save"></i>
                            <span id="videoReelSubmitBtnText">Save Video</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL 7B: VIDEO WATCH & AUTO-REPLAY MODAL -->
    <div id="videoWatchModal" onclick="if(event.target === this) closeVideoWatchModal()" class="fixed inset-0 bg-black/80 backdrop-blur-sm hidden items-center justify-center z-[9999] p-2 sm:p-4" style="display: none;">
        <div class="bg-stone-950 rounded-2xl sm:rounded-3xl max-w-md w-full max-h-[94vh] overflow-hidden shadow-2xl border border-stone-800 animate-fadeIn flex flex-col" onclick="event.stopPropagation()">
            <!-- Top bar -->
            <div class="p-3.5 sm:p-4 px-4 sm:px-5 border-b border-stone-800/80 flex items-center justify-between bg-stone-900/60">
                <div class="flex-1 min-w-0 mr-3">
                    <span id="videoWatchCategory" class="text-[10px] font-bold text-red-400 uppercase tracking-wider block truncate">Studio Reel</span>
                    <h3 id="videoWatchTitle" class="text-sm font-bold text-white truncate">Reel Player</h3>
                </div>
                <button type="button" onclick="closeVideoWatchModal()" class="w-8 h-8 rounded-full bg-stone-800 text-stone-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
            <!-- Player container with auto-replay loop -->
            <div class="relative w-full aspect-[9/16] max-h-[70vh] bg-black flex items-center justify-center overflow-hidden">
                <video id="videoWatchPlayer" controls playsinline loop autoplay class="w-full h-full object-contain"></video>
            </div>
            <!-- Bottom bar -->
            <div class="p-3 px-4 sm:px-5 border-t border-stone-800/80 flex items-center justify-between bg-stone-900/60 text-xs text-stone-400">
                <span class="flex items-center gap-1.5 text-emerald-400 text-[11px] font-semibold">
                    <i class="fas fa-redo text-[10px]"></i> Auto-replay
                </span>
                <button type="button" id="videoWatchEditBtn" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs flex items-center gap-1 cursor-pointer">
                    <i class="fas fa-edit text-xs"></i> Edit
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 8: BLOG / VIDEOS SECTION SETTINGS MODAL -->
    <div id="blogReelsSettingsModal" onclick="if(event.target === this) closeBlogReelsSettingsModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Behind the Stitches Settings</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Customize headline, subtitle, and Instagram CTA</p>
                    </div>
                </div>
                <button type="button" onclick="closeBlogReelsSettingsModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="blogReelsSettingsForm" novalidate onsubmit="handleBlogReelsSettingsSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Title <span class="text-red-500">*</span></label>
                        <input type="text" id="blogReelsSettingsTitle" name="title" value="Behind the Stitches" placeholder="e.g. Behind the Stitches" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Tagline / Badge Text</label>
                        <input type="text" id="blogReelsSettingsTagText" name="tag_text" value="Studio Journal & Video Reels" placeholder="e.g. Studio Journal & Video Reels" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Subtitle Paragraph</label>
                        <textarea id="blogReelsSettingsSubtitle" name="subtitle" rows="3" placeholder="Description of what this section showcases..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.</textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Social CTA Text</label>
                            <input type="text" id="blogReelsSettingsCtaText" name="cta_text" value="Follow @knotelleindia" placeholder="e.g. Follow @knotelleindia" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Social CTA Link URL</label>
                            <input type="text" id="blogReelsSettingsCtaLink" name="cta_link" value="https://instagram.com/knotelleindia" placeholder="e.g. https://instagram.com/knotelleindia" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="blogReelsSettingsActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="blogReelsSettingsActive" class="text-xs font-bold text-stone-700">Section Active (Visible on Public Website)</label>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeBlogReelsSettingsModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="blogReelsSettingsSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 9: ABOUT STORY MODAL -->
    <div id="aboutStoryModal" onclick="if(event.target === this) closeAboutStoryModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Edit The KNOTELLE Story</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Manage story headings, descriptive paragraphs, visuals, floating badge, and CTA</p>
                    </div>
                </div>
                <button type="button" onclick="closeAboutStoryModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="aboutStoryForm" novalidate onsubmit="handleAboutStorySubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="aboutStoryDesktopUrl" name="desktop_image_url" value="">
                <input type="hidden" id="aboutStoryMobileUrl" name="mobile_image_url" value="">

                <div class="p-3.5 sm:p-6 space-y-3.5 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Headings Group -->
                    <div class="space-y-2.5 sm:space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Story Headings & Tagline</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Eyebrow Badge <span class="text-red-500">*</span></label>
                                <input type="text" id="aboutStoryTagText" name="tag_text" placeholder="e.g. The KNOTELLE Story" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Main Headline Title <span class="text-red-500">*</span></label>
                                <input type="text" id="aboutStoryTitle" name="title" placeholder="e.g. Every Loop Tells a Story" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Subtitle / Catchphrase</label>
                            <input type="text" id="aboutStorySubtitle" name="subtitle" placeholder="e.g. Handcrafted slow-made warmth from Bengaluru" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <!-- Story Paragraphs -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-paragraph text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Story Narrative (2 Paragraphs)</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Paragraph 1 (Primary Narrative) <span class="text-red-500">*</span></label>
                            <textarea id="aboutStoryDescription" name="description" rows="3" placeholder="In a world flooded with disposable factory goods..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Paragraph 2 (Secondary Narrative)</label>
                            <textarea id="aboutStoryParagraph2" name="paragraph_2" rows="3" placeholder="When you order a bouquet of crochet roses, a customized bunny keychain..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                    </div>

                    <!-- Story Imagery -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-image text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Artisan Crafting Visual (Desktop & Mobile)</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                            <!-- Desktop Box -->
                            <div class="bg-stone-50/80 border border-stone-200/90 rounded-2xl p-3 sm:p-4 space-y-2 sm:space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-stone-800">Desktop Photo</span>
                                    <button type="button" onclick="openMediaPicker('about_desktop')" class="px-2 py-0.5 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                        <i class="fas fa-photo-video text-red-500"></i> Library
                                    </button>
                                </div>
                                <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer" onclick="document.getElementById('aboutStoryDesktopFileInput').click()">
                                    <i class="fas fa-cloud-upload-alt text-red-500 text-base mb-1"></i>
                                    <p class="text-xs font-bold text-stone-700" id="aboutDesktopFileLabel">Upload Desktop Photo</p>
                                    <p class="text-[10px] text-stone-400">1000 × 1100 px</p>
                                    <input type="file" id="aboutStoryDesktopFileInput" name="image" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleAboutDesktopFileChange(this)">
                                </div>
                                <div id="aboutDesktopPreviewContainer" class="bg-white rounded-xl p-2 border border-stone-200 flex items-center gap-3">
                                    <img id="aboutDesktopPreviewImg" src="" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-stone-800 truncate" id="aboutDesktopFileName">Main Photo Active</p>
                                        <p class="text-[10px] text-emerald-600 font-semibold" id="aboutDesktopFileSize">Ready</p>
                                    </div>
                                    <button type="button" onclick="clearAboutDesktopFileInput()" class="text-stone-400 hover:text-red-600 p-1 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                                </div>
                            </div>

                            <!-- Mobile Box -->
                            <div class="bg-stone-50/80 border border-stone-200/90 rounded-2xl p-3 sm:p-4 space-y-2 sm:space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-stone-800">Mobile Photo <span class="text-stone-400 text-[10px]">(Optional)</span></span>
                                    <button type="button" onclick="openMediaPicker('about_mobile')" class="px-2 py-0.5 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                        <i class="fas fa-photo-video text-red-500"></i> Library
                                    </button>
                                </div>
                                <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer" onclick="document.getElementById('aboutStoryMobileFileInput').click()">
                                    <i class="fas fa-cloud-upload-alt text-red-500 text-base mb-1"></i>
                                    <p class="text-xs font-bold text-stone-700" id="aboutMobileFileLabel">Upload Mobile Photo</p>
                                    <p class="text-[10px] text-stone-400">768 × 800 px (Optional)</p>
                                    <input type="file" id="aboutStoryMobileFileInput" name="mobile_image" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleAboutMobileFileChange(this)">
                                </div>
                                <div id="aboutMobilePreviewContainer" class="hidden bg-white rounded-xl p-2 border border-stone-200 flex items-center gap-3">
                                    <img id="aboutMobilePreviewImg" src="" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-stone-800 truncate" id="aboutMobileFileName">Mobile Photo Active</p>
                                        <p class="text-[10px] text-emerald-600 font-semibold" id="aboutMobileFileSize">Ready</p>
                                    </div>
                                    <button type="button" onclick="clearAboutMobileFileInput()" class="text-stone-400 hover:text-red-600 p-1 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text (SEO & Accessibility)</label>
                            <input type="text" id="aboutStoryAlt" name="alt_text" placeholder="e.g. Artisan stitching crochet with wooden hook" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <!-- Floating Badge Settings -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-certificate text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Floating Handcrafted Badge</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Badge Title</label>
                                <input type="text" id="aboutStoryFloatingTitle" name="floating_badge_title" placeholder="e.g. 100% Handcrafted" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Badge Subtitle</label>
                                <input type="text" id="aboutStoryFloatingSubtitle" name="floating_badge_subtitle" placeholder="e.g. Never mass produced" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Badge Icon</label>
                                <select id="aboutStoryFloatingIcon" name="floating_badge_icon" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <option value="Heart">Heart (♡)</option>
                                    <option value="Sparkles">Sparkles (✨)</option>
                                    <option value="Leaf">Leaf (🌿)</option>
                                    <option value="Flower2">Flower (🌸)</option>
                                    <option value="ShieldCheck">Shield / Quality (🛡️)</option>
                                    <option value="Star">Star (⭐)</option>
                                    <option value="Sun">Sun (☀️)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="aboutStoryFloatingActive" name="floating_badge_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                            <label for="aboutStoryFloatingActive" class="text-xs font-bold text-stone-700">Display Floating Badge on Photo</label>
                        </div>
                    </div>

                    <!-- Call To Action Button -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Custom Creation Call-To-Action</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">CTA Button Text</label>
                                <input type="text" id="aboutStoryCtaText" name="cta_text" placeholder="e.g. Request a Custom Creation" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">CTA Button Link</label>
                                <input type="text" id="aboutStoryCtaLink" name="cta_link" placeholder="e.g. /custom-order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="aboutStoryCtaVisible" name="cta_visible" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="aboutStoryCtaVisible" class="text-xs font-bold text-stone-700">Display CTA Button</label>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="aboutStoryActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="aboutStoryActive" class="text-xs font-bold text-stone-700">Section Active on /about page</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeAboutStoryModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="aboutStorySubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Story</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 10: CRAFT PILLARS HEADER SETTINGS MODAL -->
    <div id="craftPillarsHeaderModal" onclick="if(event.target === this) closeCraftPillarsHeaderModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Craft Pillars Header</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Customize section title, subtitle, and badge</p>
                    </div>
                </div>
                <button type="button" onclick="closeCraftPillarsHeaderModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="craftPillarsHeaderForm" novalidate onsubmit="handleCraftPillarsHeaderSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Title <span class="text-red-500">*</span></label>
                        <input type="text" id="craftPillarsHeaderTitle" name="title" value="Our Craft Pillars" placeholder="e.g. Our Craft Pillars" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Eyebrow Badge Text</label>
                        <input type="text" id="craftPillarsHeaderTagText" name="tag_text" value="Artisan Standards" placeholder="e.g. Artisan Standards" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Subtitle / Description</label>
                        <textarea id="craftPillarsHeaderSubtitle" name="subtitle" rows="3" placeholder="Guiding principles behind every stitch we make..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">Guiding principles behind every stitch we make.</textarea>
                    </div>
                    <div class="flex items-center gap-2 pt-1 sm:pt-2">
                        <input type="checkbox" id="craftPillarsHeaderActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="craftPillarsHeaderActive" class="text-xs font-bold text-stone-700">Section Active on About Page</label>
                    </div>
                </div>
                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeCraftPillarsHeaderModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="craftPillarsHeaderSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Header</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 11: ADD / EDIT CRAFT PILLAR MODAL -->
    <div id="craftPillarModal" onclick="if(event.target === this) closeCraftPillarModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="craftPillarModalTitle">Add Craft Pillar</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Add or edit craftsmanship pillars displayed on the About page</p>
                    </div>
                </div>
                <button type="button" onclick="closeCraftPillarModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="craftPillarForm" novalidate onsubmit="handleCraftPillarSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="craftPillarId" name="id" value="">
                <input type="hidden" id="craftPillarIconName" name="icon_name" value="Leaf">
                <input type="hidden" id="craftPillarIconType" name="icon_type" value="preset">

                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Pillar Title <span class="text-red-500">*</span></label>
                        <input type="text" id="craftPillarTitle" name="title" placeholder="e.g. Natural Materials" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Pillar Description <span class="text-red-500">*</span></label>
                        <textarea id="craftPillarDescription" name="description" rows="3" placeholder="We use 100% pure milk cotton and mercerized organic fibers..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <!-- Icon Selector -->
                    <div class="space-y-2 pt-1">
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider">Choose Botanical / Luxury Icon</label>
                        
                        <!-- Visual Icon Preset Grid -->
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 p-2.5 sm:p-3 bg-stone-50 rounded-2xl border border-stone-200" id="pillarIconPickerGrid">
                            <button type="button" onclick="selectPillarIcon('Leaf')" data-icon="Leaf" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-leaf text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Leaf</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Sparkles')" data-icon="Sparkles" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-magic text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Sparkles</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Heart')" data-icon="Heart" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-heart text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Heart</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Flower2')" data-icon="Flower2" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-spa text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Flower</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('ShieldCheck')" data-icon="ShieldCheck" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-shield-alt text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Shield</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Sun')" data-icon="Sun" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-sun text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Sun</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Star')" data-icon="Star" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-star text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Star</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Award')" data-icon="Award" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-award text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Award</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Gem')" data-icon="Gem" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-gem text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Gem</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Feather')" data-icon="Feather" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-feather-alt text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Feather</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Gift')" data-icon="Gift" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-gift text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Gift</span>
                            </button>
                            <button type="button" onclick="selectPillarIcon('Smile')" data-icon="Smile" class="pillar-icon-btn p-2 sm:p-2.5 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-smile text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Smile</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order (Sequence)</label>
                            <input type="number" id="craftPillarSortOrder" name="sort_order" min="1" placeholder="e.g. 1" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                            <div class="flex items-center gap-2 pt-2 sm:pt-2.5">
                                <input type="checkbox" id="craftPillarActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="craftPillarActive" class="text-xs font-bold text-stone-700">Active on About Page</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeCraftPillarModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="craftPillarSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="craftPillarSubmitBtnText">Save Pillar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: CUSTOM ORDER ITEM / CATEGORY MODAL -->
    <div id="customOrderItemModal" onclick="if(event.target === this) closeCustomOrderItemModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="customOrderItemModalTitle">Add Custom Order Category</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Bespoke creation product type for Step 1</p>
                    </div>
                </div>
                <button type="button" onclick="closeCustomOrderItemModal()" class="w-8 h-8 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-500 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="customOrderItemForm" onsubmit="handleCustomOrderItemSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="customOrderItemId" name="id">

                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Item Name / Category <span class="text-red-500">*</span></label>
                        <input type="text" id="customOrderItemTitle" name="title" required placeholder="e.g. Bouquet, Soft Toys, Keychain" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Subtitle / Note</label>
                        <input type="text" id="customOrderItemSubtitle" name="subtitle" placeholder="e.g. Custom pattern, Hand-tied florals" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider">Category Favicon / Icon</label>
                            <span id="selectedIconLabel" class="text-xs font-bold text-red-600">Flower</span>
                        </div>
                        <input type="hidden" id="customOrderItemIcon" name="icon" value="flower">
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 p-2 sm:p-2.5 bg-stone-50 border border-stone-200 rounded-2xl max-h-40 sm:max-h-48 overflow-y-auto" id="customOrderItemIconGrid">
                            <!-- Populated by renderCustomOrderItemIconGrid() -->
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-stone-400 mt-1">Select an icon to display for this item type on the website Custom Order page.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order (Sequence)</label>
                            <input type="number" id="customOrderItemSortOrder" name="sort_order" min="1" placeholder="e.g. 1" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                            <div class="flex items-center gap-2 pt-2 sm:pt-2.5">
                                <input type="checkbox" id="customOrderItemActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="customOrderItemActive" class="text-xs font-bold text-stone-700">Active (Visible)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeCustomOrderItemModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="customOrderItemSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="customOrderItemSubmitBtnText">Save Category</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 12: CONTACT INTRO MODAL -->
    <div id="contactIntroModal" onclick="if(event.target === this) closeContactIntroModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Edit Contact Intro</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Manage main title, badge, tagline, introductory text, and banner artwork</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactIntroModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactIntroForm" onsubmit="handleContactIntroSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="contactIntroImageUrl" name="image_url" value="">

                <div class="p-3.5 sm:p-6 space-y-3.5 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Headings Group -->
                    <div class="space-y-2.5 sm:space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Headings & Badge</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Eyebrow Badge <span class="text-red-500">*</span></label>
                                <input type="text" id="contactIntroBadge" name="badge" required placeholder="e.g. Let's Connect" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Main Headline Title <span class="text-red-500">*</span></label>
                                <input type="text" id="contactIntroTitle" name="title" required placeholder="e.g. Let's Connect" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Tagline / Subtitle <span class="text-red-500">*</span></label>
                            <input type="text" id="contactIntroSubtitle" name="subtitle" required placeholder="e.g. Have a question about a product, custom order, or collaboration? We'd love to hear from you." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Introductory Narrative / Description</label>
                            <textarea id="contactIntroDescription" name="description" rows="2" placeholder="e.g. We're here to help bring your handcrafted crochet dreams to life..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                    </div>

                    <!-- Visual Imagery -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-image text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Contact Banner Artwork</span>
                        </div>

                        <div class="bg-stone-50/80 border border-stone-200/90 rounded-2xl p-3 sm:p-4 space-y-2.5 sm:space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-stone-800">Banner Photo Visual</span>
                                <button type="button" onclick="openMediaPicker('contact_intro')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-red-500"></i> Media Library
                                </button>
                            </div>

                            <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer" onclick="document.getElementById('contactIntroFileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-red-500 text-base sm:text-lg mb-1"></i>
                                <p class="text-xs font-bold text-stone-700" id="contactIntroFileLabel">Upload Banner Image</p>
                                <p class="text-[10px] text-stone-400">1200 × 800 px (JPG/PNG/WEBP)</p>
                                <input type="file" id="contactIntroFileInput" name="image_file" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleContactIntroFileChange(this)">
                            </div>

                            <div id="contactIntroPreviewContainer" class="bg-white rounded-xl p-2.5 border border-stone-200 flex items-center gap-3">
                                <img id="contactIntroPreviewImg" src="" class="w-12 h-12 sm:w-14 sm:h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate" id="contactIntroFileName">Contact Banner Active</p>
                                    <p class="text-[10px] text-emerald-600 font-semibold" id="contactIntroFileSize">Ready</p>
                                </div>
                                <button type="button" onclick="clearContactIntroFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text (SEO & Accessibility)</label>
                                <input type="text" id="contactIntroAlt" name="alt_text" placeholder="e.g. KNOTELLE Artisan Studio Contact" class="w-full bg-white border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- Call To Action & Status -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-sliders-h text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">CTA & Status</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">CTA Button Text</label>
                                <input type="text" id="contactIntroCtaText" name="cta_text" placeholder="e.g. Send Us a Message" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">CTA Button Link</label>
                                <input type="text" id="contactIntroCtaLink" name="cta_link" placeholder="e.g. #contact-form" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1 sm:pt-2">
                            <input type="checkbox" id="contactIntroActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                            <label for="contactIntroActive" class="text-xs font-bold text-stone-700">Intro Section Active on Contact Page</label>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactIntroModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactIntroSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Intro</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 13: CONTACT INFO HEADER & CUSTOM ORDER BOX MODAL -->
    <div id="contactInfoHeaderModal" onclick="if(event.target === this) closeContactInfoHeaderModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Studio Header & Box</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Customize studio title, badge, subtitle, and custom order callout box</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactInfoHeaderModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactInfoHeaderForm" novalidate onsubmit="handleContactInfoHeaderSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Studio Eyebrow Badge</label>
                        <input type="text" id="contactInfoBadgeInput" name="badge" value="Atelier Studio" placeholder="e.g. Atelier Studio" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Studio Name / Headline <span class="text-red-500">*</span></label>
                        <input type="text" id="contactInfoTitleInput" name="title" value="KNOTELLE Studio" placeholder="e.g. KNOTELLE Studio" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Studio Subtitle</label>
                        <input type="text" id="contactInfoSubtitleInput" name="subtitle" value="Handmade with love in Bengaluru, India" placeholder="e.g. Handmade with love in Bengaluru, India" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <!-- Custom Order Note Box -->
                    <div class="p-3 sm:p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-2.5 sm:space-y-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-gift text-amber-700 text-xs"></i>
                            <span class="text-xs font-bold text-amber-900 uppercase tracking-wider">Custom Order Helper Box</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-700 mb-1">Box Title</label>
                            <input type="text" id="contactInfoCustomBoxTitle" name="custom_order_box_title" value="Looking for Custom Orders?" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-700 mb-1">Box Description Text</label>
                            <textarea id="contactInfoCustomBoxText" name="custom_order_box_text" rows="2" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div>
                                <label class="block text-xs font-bold text-stone-700 mb-1">Box Link URL</label>
                                <input type="text" id="contactInfoCustomBoxLink" name="custom_order_box_link" value="/custom-order" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="flex items-center pt-2 sm:pt-5">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="contactInfoCustomBoxActive" name="custom_order_box_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span class="text-xs font-bold text-stone-700">Display Helper Box</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="contactInfoHeaderActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="contactInfoHeaderActive" class="text-xs font-bold text-stone-700">Section Active on Contact Page</label>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactInfoHeaderModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactInfoHeaderSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Header</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 14: ADD / EDIT CONTACT DETAIL ITEM MODAL -->
    <div id="contactInfoItemModal" onclick="if(event.target === this) closeContactInfoItemModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-address-card"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="contactInfoItemModalTitle">Add Contact Detail</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Add or edit phone, email, studio address, hours, WhatsApp, or socials</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactInfoItemModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactInfoItemForm" novalidate onsubmit="handleContactInfoItemSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="contactInfoItemId" name="id" value="">
                <input type="hidden" id="contactInfoItemIcon" name="icon" value="MapPin">

                <div class="p-3.5 sm:p-6 space-y-3 sm:space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Title / Label <span class="text-red-500">*</span></label>
                        <input type="text" id="contactInfoItemTitle" name="title" placeholder="e.g. Visit Our Studio, Call / WhatsApp Us, Write to Us" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Primary Value / Line 1 <span class="text-red-500">*</span></label>
                        <input type="text" id="contactInfoItemValue" name="value" placeholder="e.g. +91 98765 43210, hello@knotelle.com" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Secondary Address / Hours / Note Line</label>
                        <input type="text" id="contactInfoItemAddressLine2" name="address_line_2" placeholder="e.g. Bengaluru, Karnataka 560038, India" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Clickable Action Link (tel:, mailto:, URL)</label>
                        <input type="text" id="contactInfoItemLink" name="link" placeholder="e.g. tel:+919876543210, mailto:hello@knotelle.com" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <!-- Icon Preset Selector -->
                    <div class="space-y-2 pt-1">
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider">Choose Contact Detail Icon</label>
                        
                        <div class="grid grid-cols-5 sm:grid-cols-5 gap-2 p-2.5 sm:p-3 bg-stone-50 rounded-2xl border border-stone-200" id="contactInfoIconGrid">
                            <button type="button" onclick="selectContactInfoIcon('MapPin')" data-icon="MapPin" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-map-marker-alt text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">MapPin</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Phone')" data-icon="Phone" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-phone text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Phone</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Mail')" data-icon="Mail" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-envelope text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Mail</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Clock')" data-icon="Clock" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-clock text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Clock</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('MessageCircle')" data-icon="MessageCircle" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fab fa-whatsapp text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">WhatsApp</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Instagram')" data-icon="Instagram" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fab fa-instagram text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Instagram</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Facebook')" data-icon="Facebook" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fab fa-facebook-f text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Facebook</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Globe')" data-icon="Globe" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-globe text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Globe</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Sparkles')" data-icon="Sparkles" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-magic text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Sparkles</span>
                            </button>
                            <button type="button" onclick="selectContactInfoIcon('Heart')" data-icon="Heart" class="contact-info-icon-btn p-2 rounded-xl border border-stone-200 bg-white hover:border-red-400 flex flex-col items-center gap-1 transition-all cursor-pointer">
                                <i class="fas fa-heart text-sm sm:text-base text-red-600"></i>
                                <span class="text-[10px] font-bold text-stone-700">Heart</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order (Sequence)</label>
                            <input type="number" id="contactInfoItemSortOrder" name="sort_order" min="1" placeholder="e.g. 1" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                            <div class="flex items-center gap-2 pt-2 sm:pt-2.5">
                                <input type="checkbox" id="contactInfoItemActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="contactInfoItemActive" class="text-xs font-bold text-stone-700">Active on Contact Page</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactInfoItemModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactInfoItemSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="contactInfoItemSubmitBtnText">Save Detail</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 15: SEND US A MESSAGE / CONTACT FORM SETTINGS MODAL -->
    <div id="contactFormModal" onclick="if(event.target === this) closeContactFormModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-2xl w-full max-h-[94vh] sm:max-h-[92vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0 pr-2">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs sm:text-base shadow-2xs shrink-0">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Edit Contact Form</h3>
                        <p class="hidden sm:block text-xs text-stone-500 font-medium truncate">Configure form headings, button label, success message, and input field labels</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactFormModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactFormSettingsForm" novalidate onsubmit="handleContactFormSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-3.5 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Headings Group -->
                    <div class="space-y-2.5 sm:space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Form Headings & Button</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Eyebrow Badge</label>
                                <input type="text" id="contactFormBadge" name="badge" value="Get In Touch" placeholder="e.g. Get In Touch" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Title <span class="text-red-500">*</span></label>
                                <input type="text" id="contactFormTitle" name="title" value="Send Us a Message" placeholder="e.g. Send Us a Message" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Subtitle / Help Text</label>
                                <input type="text" id="contactFormSubtitle" name="subtitle" value="Fill in your details and our team will get back to you promptly." placeholder="e.g. Fill in your details..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Submit Button Text <span class="text-red-500">*</span></label>
                                <input type="text" id="contactFormCtaText" name="cta_text" value="Send Message" placeholder="e.g. Send Message" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Messages Group -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-check-circle text-emerald-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Success & Error Notification Messages</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Success Title</label>
                                <input type="text" id="contactFormSuccessTitle" name="success_title" value="Message Sent!" placeholder="e.g. Message Sent!" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Success Message Description</label>
                                <input type="text" id="contactFormSuccessMessage" name="success_message" value="Thank you! Your message has been sent successfully. We will get back to you shortly." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Error Message (Submission Failed)</label>
                            <input type="text" id="contactFormErrorMessage" name="error_message" value="Something went wrong while sending your message. Please check the form and try again." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 sm:px-3.5 py-2 text-xs sm:text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>

                    <!-- Form Fields Configuration -->
                    <div class="space-y-2.5 sm:space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-list text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Input Field Labels & Placeholders</span>
                        </div>

                        <div class="space-y-2.5 sm:space-y-3" id="contactFormFieldsContainer">
                            <!-- Name Field Config -->
                            <div class="bg-stone-50 p-2.5 sm:p-3.5 rounded-2xl border border-stone-200 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center">
                                <div class="sm:col-span-3 font-bold text-xs text-stone-800 flex items-center gap-1.5">
                                    <i class="fas fa-user text-stone-400"></i> Name Field
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text" id="field_name_label" name="fields[0][label]" value="Your Name" placeholder="Label" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[0][key]" value="name">
                                </div>
                                <div class="sm:col-span-5">
                                    <input type="text" id="field_name_placeholder" name="fields[0][placeholder]" value="Enter your full name" placeholder="Placeholder" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[0][required]" value="1">
                                    <input type="hidden" name="fields[0][is_active]" value="1">
                                </div>
                            </div>

                            <!-- Email Field Config -->
                            <div class="bg-stone-50 p-2.5 sm:p-3.5 rounded-2xl border border-stone-200 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center">
                                <div class="sm:col-span-3 font-bold text-xs text-stone-800 flex items-center gap-1.5">
                                    <i class="fas fa-envelope text-stone-400"></i> Email Field
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text" id="field_email_label" name="fields[1][label]" value="Email Address" placeholder="Label" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[1][key]" value="email">
                                </div>
                                <div class="sm:col-span-5">
                                    <input type="text" id="field_email_placeholder" name="fields[1][placeholder]" value="Enter your email address" placeholder="Placeholder" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[1][required]" value="1">
                                    <input type="hidden" name="fields[1][is_active]" value="1">
                                </div>
                            </div>

                            <!-- Phone Field Config -->
                            <div class="bg-stone-50 p-2.5 sm:p-3.5 rounded-2xl border border-stone-200 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center">
                                <div class="sm:col-span-3 font-bold text-xs text-stone-800 flex items-center gap-1.5">
                                    <i class="fas fa-phone text-stone-400"></i> Phone Field
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text" id="field_phone_label" name="fields[2][label]" value="Phone Number" placeholder="Label" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[2][key]" value="phone">
                                </div>
                                <div class="sm:col-span-5">
                                    <input type="text" id="field_phone_placeholder" name="fields[2][placeholder]" value="Enter your 10-digit phone number (optional)" placeholder="Placeholder" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[2][required]" value="0">
                                    <input type="hidden" name="fields[2][is_active]" value="1">
                                </div>
                            </div>

                            <!-- Subject Field Config -->
                            <div class="bg-stone-50 p-2.5 sm:p-3.5 rounded-2xl border border-stone-200 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center">
                                <div class="sm:col-span-3 font-bold text-xs text-stone-800 flex items-center gap-1.5">
                                    <i class="fas fa-tag text-stone-400"></i> Subject Field
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text" id="field_subject_label" name="fields[3][label]" value="Subject" placeholder="Label" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[3][key]" value="subject">
                                </div>
                                <div class="sm:col-span-5">
                                    <input type="text" id="field_subject_placeholder" name="fields[3][placeholder]" value="What is this regarding?" placeholder="Placeholder" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[3][required]" value="0">
                                    <input type="hidden" name="fields[3][is_active]" value="1">
                                </div>
                            </div>

                            <!-- Message Field Config -->
                            <div class="bg-stone-50 p-2.5 sm:p-3.5 rounded-2xl border border-stone-200 grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 items-center">
                                <div class="sm:col-span-3 font-bold text-xs text-stone-800 flex items-center gap-1.5">
                                    <i class="fas fa-comment-alt text-stone-400"></i> Message Field
                                </div>
                                <div class="sm:col-span-4">
                                    <input type="text" id="field_message_label" name="fields[4][label]" value="Your Message" placeholder="Label" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[4][key]" value="message">
                                </div>
                                <div class="sm:col-span-5">
                                    <input type="text" id="field_message_placeholder" name="fields[4][placeholder]" value="Tell us how we can help you..." placeholder="Placeholder" class="w-full bg-white border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold">
                                    <input type="hidden" name="fields[4][required]" value="1">
                                    <input type="hidden" name="fields[4][is_active]" value="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="contactFormActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="contactFormActive" class="text-xs font-bold text-stone-700">Contact Form Active on Website</label>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex flex-wrap items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactFormModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactFormSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Config</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 16: FAQ SECTION HEADER SETTINGS MODAL -->
    <div id="contactFaqsHeaderModal" onclick="if(event.target === this) closeContactFaqsHeaderModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm sm:text-base">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">FAQ Section Header</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block truncate">Customize FAQ section title, subtitle, and badge</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactFaqsHeaderModal()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-xs sm:text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactFaqsHeaderForm" novalidate onsubmit="handleContactFaqsHeaderSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Eyebrow Badge</label>
                        <input type="text" id="contactFaqsHeaderTagText" name="badge" value="Help & Support" placeholder="e.g. Help & Support" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Title <span class="text-red-500">*</span></label>
                        <input type="text" id="contactFaqsHeaderTitle" name="title" value="Frequently Asked Questions" placeholder="e.g. Frequently Asked Questions" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Section Subtitle / Help Text</label>
                        <textarea id="contactFaqsHeaderSubtitle" name="subtitle" rows="3" placeholder="Quick answers about our handmade creations, custom orders, and delivery..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">Quick answers about our handmade creations, custom orders, and delivery.</textarea>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="contactFaqsHeaderActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="contactFaqsHeaderActive" class="text-xs font-bold text-stone-700">FAQ Section Active (Visible on Contact Page)</label>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3.5 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactFaqsHeaderModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactFaqsHeaderSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save FAQ Header</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 17: ADD / EDIT FAQ ITEM MODAL -->
    <div id="contactFaqModal" onclick="if(event.target === this) closeContactFaqModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm sm:text-base">
                        <i class="fas fa-question"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate" id="contactFaqModalTitle">Add FAQ Item</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block truncate">Add or edit customer questions and helpful answers</p>
                    </div>
                </div>
                <button type="button" onclick="closeContactFaqModal()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-xs sm:text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="contactFaqForm" novalidate onsubmit="handleContactFaqSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="contactFaqId" name="id" value="">

                <div class="p-3.5 sm:p-6 space-y-4 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Question <span class="text-red-500">*</span></label>
                        <input type="text" id="contactFaqQuestion" name="question" placeholder="e.g. How long does a custom order take?" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Answer <span class="text-red-500">*</span></label>
                        <textarea id="contactFaqAnswer" name="answer" rows="4" placeholder="e.g. Custom orders usually take 7–14 working days depending on complexity..." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Sort Order (Sequence)</label>
                            <input type="number" id="contactFaqSortOrder" name="sort_order" min="1" placeholder="e.g. 1" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Status</label>
                            <div class="flex items-center gap-2 pt-2 sm:pt-2.5">
                                <input type="checkbox" id="contactFaqActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="contactFaqActive" class="text-xs font-bold text-stone-700">Active (Visible on Website)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3.5 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeContactFaqModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="contactFaqSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span id="contactFaqSubmitBtnText">Save FAQ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 18: CUSTOM CROCHET BANNER & HANGING TAG MODAL -->
    <div id="customCrochetModal" onclick="if(event.target === this) closeCustomCrochetModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm sm:text-base">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Custom Crochet Banner & Note</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block truncate">Headline, italic highlight, narrative, button, artwork, and hanging note</p>
                    </div>
                </div>
                <button type="button" onclick="closeCustomCrochetModal()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-xs sm:text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="customCrochetForm" novalidate onsubmit="handleCustomCrochetSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="customCrochetImageUrl" name="image_url" value="">

                <div class="p-3.5 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Headings Group -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Headings & Description</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Main Headline <span class="text-red-500">*</span></label>
                                <input type="text" id="customCrochetTitle" name="title" placeholder="e.g. Custom Crochet" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 sm:py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Italic Highlight Subtitle</label>
                                <input type="text" id="customCrochetSubtitle" name="subtitle" placeholder="e.g. Just for You" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 sm:py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Description Narrative <span class="text-red-500">*</span></label>
                            <textarea id="customCrochetDescription" name="description" rows="2" placeholder="e.g. Your imagination, our yarn. Let's create something special together." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                    </div>

                    <!-- Hanging Note / Tag Configuration (Turn Your Ideas Section) -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-sticky-note text-amber-600 text-xs"></i>
                            <span class="text-xs font-bold text-amber-900 uppercase tracking-wider">Hanging Paper Note</span>
                        </div>

                        <div class="p-3 sm:p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-stone-700 mb-1">Hanging Note Tag Text</label>
                                <input type="text" id="customCrochetTagText" name="tag_text" placeholder="e.g. Turn Your Ideas Into Handmade Reality" class="w-full bg-white border border-stone-200 rounded-xl px-3.5 py-2 sm:py-2.5 text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                                <p class="text-[11px] text-stone-500 mt-1">This appears as the handcrafted paper card badge pinned over the right-side banner artwork.</p>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" id="customCrochetTagActive" name="tag_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <label for="customCrochetTagActive" class="text-xs font-bold text-stone-700">Display Hanging Note on Banner</label>
                            </div>
                        </div>
                    </div>

                    <!-- Button & Artwork -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-image text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Button CTA & Banner Artwork</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Label</label>
                                <input type="text" id="customCrochetCtaText" name="cta_text" placeholder="e.g. Request Your Custom Order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Link URL</label>
                                <input type="text" id="customCrochetCtaLink" name="cta_link" placeholder="e.g. /custom-order" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="bg-stone-50/80 border border-stone-200/90 rounded-2xl p-3 sm:p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-stone-800">Banner Photo Visual</span>
                                <button type="button" onclick="openMediaPicker('custom_crochet')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-red-500"></i> Media Library
                                </button>
                            </div>

                            <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer" onclick="document.getElementById('customCrochetFileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-red-500 text-lg mb-1"></i>
                                <p class="text-xs font-bold text-stone-700" id="customCrochetFileLabel">Upload Banner Image</p>
                                <p class="text-[10px] text-stone-400">1200 × 800 px (JPG/PNG/WEBP)</p>
                                <input type="file" id="customCrochetFileInput" name="image_file" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleCustomCrochetFileChange(this)">
                            </div>

                            <div id="customCrochetPreviewContainer" class="bg-white rounded-xl p-2.5 border border-stone-200 flex items-center gap-3">
                                <img id="customCrochetPreviewImg" src="" class="w-14 h-14 rounded-lg object-cover border border-stone-200 shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate" id="customCrochetFileName">Banner Active</p>
                                    <p class="text-[10px] text-emerald-600 font-semibold">Ready</p>
                                </div>
                                <button type="button" onclick="clearCustomCrochetFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Alt Text</label>
                                <input type="text" id="customCrochetAlt" name="alt_text" placeholder="e.g. Custom Crochet Banner Visual" class="w-full bg-white border border-stone-200 rounded-xl px-3.5 py-2 text-sm font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="customCrochetActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                            <label for="customCrochetActive" class="text-xs font-bold text-stone-700">Banner Section Active on Homepage</label>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3.5 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeCustomCrochetModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="customCrochetSubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Custom Crochet Banner</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 18B: BRAND STORY SECTION MODAL -->
    <div id="brandStoryModal" onclick="if(event.target === this) closeBrandStoryModal()" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] sm:max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Pinned Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm sm:text-base">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Brand Story Section</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block truncate">Headline, italic highlight, narrative, button, artwork, and 4 trust features</p>
                    </div>
                </div>
                <button type="button" onclick="closeBrandStoryModal()" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-xs sm:text-sm"></i>
                </button>
            </div>

            <!-- Form Wrapper -->
            <form id="brandStoryForm" novalidate onsubmit="handleBrandStorySubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <input type="hidden" id="brandStoryImageUrl" name="image_url" value="">

                <div class="p-3.5 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    <!-- Branding & Headings Group -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-heading text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Branding & Headings</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Top Watermark Tagline / Badge</label>
                            <input type="text" id="brandStoryBadge" name="badge" placeholder="e.g. KNOTELLE Artisanal Crochet Craftsmanship" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Main Headline <span class="text-red-500">*</span></label>
                                <input type="text" id="brandStoryTitle" name="title" placeholder="e.g. Every Stitch" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 sm:py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Italic Highlight Accent</label>
                                <input type="text" id="brandStorySubtitle" name="subtitle" placeholder="e.g. Has a Story" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 sm:py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Description Narrative <span class="text-red-500">*</span></label>
                            <textarea id="brandStoryDescription" name="description" rows="2" placeholder="e.g. More than just crochet, we create memories, happiness and a little bit of magic." class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        </div>
                    </div>

                    <!-- Button CTA Configuration -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Button CTA Link</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Label</label>
                                <input type="text" id="brandStoryCtaText" name="cta_text" placeholder="e.g. Read Our Story" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-600 uppercase tracking-wider mb-1">Button Link URL</label>
                                <input type="text" id="brandStoryCtaLink" name="cta_link" placeholder="e.g. /about" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2 text-sm font-semibold text-stone-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- The 4 Features Group -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-check-circle text-emerald-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">4 Trust & Quality Features</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="bg-stone-50 rounded-2xl p-3 border border-stone-200/80">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-heart"></i></span>
                                    <label class="text-xs font-bold text-stone-700">Feature 1</label>
                                </div>
                                <input type="text" id="brandStoryFeature1" name="feature_1_title" placeholder="Handmade with Love" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>

                            <div class="bg-stone-50 rounded-2xl p-3 border border-stone-200/80">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-wand-magic-sparkles"></i></span>
                                    <label class="text-xs font-bold text-stone-700">Feature 2</label>
                                </div>
                                <input type="text" id="brandStoryFeature2" name="feature_2_title" placeholder="Premium Yarn Quality" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>

                            <div class="bg-stone-50 rounded-2xl p-3 border border-stone-200/80">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-leaf"></i></span>
                                    <label class="text-xs font-bold text-stone-700">Feature 3</label>
                                </div>
                                <input type="text" id="brandStoryFeature3" name="feature_3_title" placeholder="100% Pure Natural Cotton" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>

                            <div class="bg-stone-50 rounded-2xl p-3 border border-stone-200/80">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="w-6 h-6 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-xs shrink-0"><i class="far fa-smile"></i></span>
                                    <label class="text-xs font-bold text-stone-700">Feature 4</label>
                                </div>
                                <input type="text" id="brandStoryFeature4" name="feature_4_title" placeholder="Happiness Guaranteed" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-semibold text-stone-800 focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- Background Artwork -->
                    <div class="space-y-3 pt-1">
                        <div class="flex items-center gap-2 border-b border-stone-100 pb-1.5">
                            <i class="fas fa-image text-red-600 text-xs"></i>
                            <span class="text-xs font-bold text-stone-700 uppercase tracking-wider">Panoramic Background Image</span>
                        </div>

                        <div class="bg-stone-50/80 border border-stone-200/90 rounded-2xl p-3 sm:p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-stone-800">Background Artwork</span>
                                <button type="button" onclick="openMediaPicker('brand_story')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-red-500"></i> Media Library
                                </button>
                            </div>

                            <div class="border-2 border-dashed border-stone-200 rounded-xl p-3 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer" onclick="document.getElementById('brandStoryFileInput').click()">
                                <i class="fas fa-cloud-upload-alt text-red-500 text-lg mb-1"></i>
                                <p class="text-xs font-bold text-stone-700" id="brandStoryFileLabel">Upload Background Image</p>
                                <p class="text-[10px] text-stone-400">Recommended: 1920 × 700 px (JPG/PNG/WEBP)</p>
                                <input type="file" id="brandStoryFileInput" name="image_file" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleBrandStoryFileChange(this)">
                            </div>

                            <div id="brandStoryPreviewContainer" class="bg-white rounded-xl p-2.5 border border-stone-200 flex items-center gap-3">
                                <img id="brandStoryPreviewImg" src="" class="w-14 h-14 rounded-lg object-cover border border-stone-200 shrink-0" onerror="this.src='/images/homepage/middleimg.png';">
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-stone-800 truncate" id="brandStoryFileName">Background Visual</p>
                                    <p class="text-[10px] text-emerald-600 font-semibold">Active</p>
                                </div>
                                <button type="button" onclick="clearBrandStoryFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input type="checkbox" id="brandStoryActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                            <label for="brandStoryActive" class="text-xs font-bold text-stone-700">Brand Story Section Active on Homepage</label>
                        </div>
                    </div>
                </div>

                <!-- Sticky Footer with Action Buttons -->
                <div class="p-3.5 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="closeBrandStoryModal()" class="btn-secondary text-xs px-4 sm:px-5 py-2 sm:py-2.5 cursor-pointer">Cancel</button>
                    <button type="submit" id="brandStorySubmitBtn" class="btn-primary text-xs px-5 sm:px-6 py-2 sm:py-2.5 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Brand Story</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 19: FOOTER SETTINGS & NAVIGATION MODAL -->
    <div id="footerSettingsModal" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;" onclick="if(event.target === this) closeFooterSettingsModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm">
                        <i class="fas fa-shoe-prints"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Footer Settings & Navigation</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block">Manage background image, column titles, link items, contact details, socials & copyright</p>
                    </div>
                </div>
                <button type="button" onclick="closeFooterSettingsModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="footerSettingsForm" onsubmit="handleFooterSettingsSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-4 sm:space-y-6 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    
                    <!-- 1. Panoramic Background Artwork -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider truncate">
                                <i class="fas fa-image mr-1 text-red-600"></i>Panoramic Footer Artwork
                            </label>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-[10px] text-stone-400 font-bold hidden sm:inline">1920 × 600 px</span>
                                <button type="button" onclick="openMediaPicker('footer_bg')" class="px-2.5 py-1 rounded-lg bg-white hover:bg-stone-100 border border-stone-200 text-stone-700 text-[11px] font-bold transition-all flex items-center gap-1 cursor-pointer">
                                    <i class="fas fa-photo-video text-red-500"></i> Media Library
                                </button>
                            </div>
                        </div>

                        <div class="border-2 border-dashed border-stone-200 rounded-xl p-3.5 sm:p-4 text-center hover:border-red-400 hover:bg-red-50/20 transition-all cursor-pointer"
                             onclick="document.getElementById('footerBgFileInput').click()"
                             ondragover="event.preventDefault(); this.classList.add('border-red-500', 'bg-red-50/30');"
                             ondragleave="this.classList.remove('border-red-500', 'bg-red-50/30');"
                             ondrop="event.preventDefault(); this.classList.remove('border-red-500', 'bg-red-50/30'); if(event.dataTransfer.files.length) { document.getElementById('footerBgFileInput').files = event.dataTransfer.files; handleFooterBgFileChange(document.getElementById('footerBgFileInput')); }">
                            <i class="fas fa-cloud-upload-alt text-red-500 text-lg mb-1"></i>
                            <p class="text-xs font-bold text-stone-700">Upload Panoramic Background Image</p>
                            <p class="text-[10px] text-stone-400">Click or drag & drop a new background image</p>
                            <input type="file" id="footerBgFileInput" name="bg_image_file" class="hidden" accept=".jpg,.jpeg,.png,.webp" onchange="handleFooterBgFileChange(this)">
                        </div>

                        <div id="footerBgPreviewContainer" class="bg-white rounded-xl p-2 sm:p-2.5 border border-stone-200 flex items-center gap-2.5 sm:gap-3">
                            <img id="footerBgPreviewImg" src="" class="w-16 sm:w-20 h-9 sm:h-10 rounded-lg object-cover border border-stone-200 shrink-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-stone-800 truncate" id="footerBgFileName">Current Background</p>
                                <p class="text-[10px] text-emerald-600 font-semibold truncate" id="footerBgStatusLabel">Active Background</p>
                            </div>
                            <button type="button" onclick="clearFooterBgFileInput()" class="text-stone-400 hover:text-red-600 p-1.5 cursor-pointer shrink-0"><i class="fas fa-times"></i></button>
                        </div>
                        <input type="hidden" id="footerBgImageUrl" name="bg_image_url">
                    </div>

                    <!-- 2. Column 1: Quick Links Repeater -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <div class="flex items-end justify-between gap-2.5">
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Column 1 Title</label>
                                <input type="text" id="footerCol1Title" name="col1_title" placeholder="Quick Links" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-1.5 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <button type="button" onclick="addFooterCol1Link()" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer">
                                <i class="fas fa-plus-circle text-xs"></i>
                                <span>Add Link</span>
                            </button>
                        </div>

                        <div id="footerCol1LinksContainer" class="space-y-2">
                            <!-- Injected dynamically by JS -->
                        </div>
                    </div>

                    <!-- 3. Column 2: Help Links Repeater -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <div class="flex items-end justify-between gap-2.5">
                            <div class="flex-1 min-w-0">
                                <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider mb-1">Column 2 Title</label>
                                <input type="text" id="footerCol2Title" name="col2_title" placeholder="Help" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-1.5 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <button type="button" onclick="addFooterCol2Link()" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 shrink-0 cursor-pointer">
                                <i class="fas fa-plus-circle text-xs"></i>
                                <span>Add Link</span>
                            </button>
                        </div>

                        <div id="footerCol2LinksContainer" class="space-y-2">
                            <!-- Injected dynamically by JS -->
                        </div>
                    </div>

                    <!-- 4. Column 3: Contact Info & Support Details -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            <i class="fas fa-address-card mr-1 text-red-600"></i>Column 3: Contact Information
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Column 3 Title</label>
                                <input type="text" id="footerCol3Title" name="col3_title" placeholder="Contact" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Store Location / Country</label>
                                <input type="text" id="footerContactAddress" name="contact_address" placeholder="India" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Phone Number</label>
                                <input type="text" id="footerContactPhone" name="contact_phone" placeholder="+91 97730 39243" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Phone Link</label>
                                <input type="text" id="footerContactPhoneLink" name="contact_phone_link" placeholder="tel:+919773039243" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Email Address</label>
                                <input type="email" id="footerContactEmail" name="contact_email" placeholder="support@knotelle.in" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Email Link</label>
                                <input type="text" id="footerContactEmailLink" name="contact_email_link" placeholder="mailto:support@knotelle.in" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Social Media Channels -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            <i class="fas fa-share-alt mr-1 text-red-600"></i>Social Media Channels & Visibility
                        </label>
                        <div class="space-y-2">
                            <!-- Instagram -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-pink-50 text-pink-600 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-instagram"></i>
                                </span>
                                <input type="text" id="footerInstagramUrl" name="instagram_url" placeholder="https://instagram.com/knotelleindia" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerInstagramActive" name="instagram_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>

                            <!-- Facebook -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-facebook-f"></i>
                                </span>
                                <input type="text" id="footerFacebookUrl" name="facebook_url" placeholder="https://facebook.com/knotelleindia" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerFacebookActive" name="facebook_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>

                            <!-- Pinterest -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-pinterest-p"></i>
                                </span>
                                <input type="text" id="footerPinterestUrl" name="pinterest_url" placeholder="https://pinterest.com/knotelleindia" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerPinterestActive" name="pinterest_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>

                            <!-- YouTube -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-youtube"></i>
                                </span>
                                <input type="text" id="footerYouTubeUrl" name="youtube_url" placeholder="https://youtube.com/@knotelleindia" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerYouTubeActive" name="youtube_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>

                            <!-- Twitter / X -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-stone-100 text-stone-700 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-x-twitter"></i>
                                </span>
                                <input type="text" id="footerTwitterUrl" name="twitter_url" placeholder="https://twitter.com/knotelleindia" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerTwitterActive" name="twitter_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>

                            <!-- LinkedIn -->
                            <div class="flex items-center gap-2.5 sm:gap-3 bg-white p-2 sm:p-2.5 rounded-xl border border-stone-200 min-w-0">
                                <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center text-xs sm:text-sm font-bold shrink-0">
                                    <i class="fab fa-linkedin-in"></i>
                                </span>
                                <input type="text" id="footerLinkedinUrl" name="linkedin_url" placeholder="https://linkedin.com/company/knotelle" class="flex-1 min-w-0 bg-transparent border-0 text-xs font-semibold text-stone-800 focus:ring-0">
                                <label class="flex items-center gap-1.5 text-xs text-stone-600 font-bold shrink-0 cursor-pointer">
                                    <input type="checkbox" id="footerLinkedinActive" name="linkedin_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                    <span>Active</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 6. Bottom Bar Copyright & Heart Tagline -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            <i class="fas fa-copyright mr-1 text-red-600"></i>Bottom Bar & Heart Tagline
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Copyright Text (supports {year})</label>
                                <input type="text" id="footerCopyrightText" name="copyright_text" placeholder="© {year} Knotelle. All rights reserved." class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Heart Motto / Tagline</label>
                                <input type="text" id="footerHeartTagline" name="heart_tagline" placeholder="Made with ♡ for a kinder, cozier world." class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="footerSectionActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="footerSectionActive" class="text-xs font-bold text-stone-700">Footer Section Active on Website</label>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeFooterSettingsModal()" class="btn-secondary text-xs px-4 py-2 cursor-pointer">Cancel</button>
                    <button type="submit" id="footerSettingsSubmitBtn" class="btn-primary text-xs px-5 py-2 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Footer Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 20: NAVBAR SETTINGS & NAVIGATION MODAL -->
    <div id="navbarSettingsModal" class="fixed inset-0 bg-stone-900/60 backdrop-blur-xs hidden items-center justify-center z-[9999] p-2 sm:p-4 overflow-y-auto" style="display: none;" onclick="if(event.target === this) closeNavbarSettingsModal()">
        <div class="bg-white rounded-2xl sm:rounded-3xl max-w-3xl w-full max-h-[94vh] flex flex-col overflow-hidden shadow-2xl border border-stone-100 animate-fadeIn" onclick="event.stopPropagation()">
            <!-- Header -->
            <div class="p-3.5 sm:p-5 px-4 sm:px-6 border-b border-stone-100 flex items-center justify-between bg-stone-50/80 shrink-0">
                <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold shadow-2xs shrink-0 text-sm">
                        <i class="fas fa-compass"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-stone-800 truncate">Navbar & Navigation Settings</h3>
                        <p class="text-[11px] sm:text-xs text-stone-500 font-medium hidden sm:block">Manage announcement bar banner, header links, sparkle highlight pill, and action buttons</p>
                    </div>
                </div>
                <button type="button" onclick="closeNavbarSettingsModal()" class="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer shrink-0">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Form -->
            <form id="navbarSettingsForm" onsubmit="handleNavbarSettingsSubmit(event)" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                <div class="p-3.5 sm:p-6 space-y-4 sm:space-y-6 overflow-y-auto overflow-x-hidden flex-1 overscroll-contain">
                    
                    <!-- 1. Announcement Bar Banner -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider truncate">
                                <i class="fas fa-bullhorn mr-1 text-red-600"></i>Announcement Bar Banner
                            </label>
                            <label class="flex items-center gap-1.5 text-xs text-stone-700 font-bold cursor-pointer shrink-0">
                                <input type="checkbox" id="navbarAnnouncementActive" name="announcement_active" class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <span>Enable Banner</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Banner Text</label>
                                <input type="text" id="navbarAnnouncementText" name="announcement_text" placeholder="✨ Free Pan-India Delivery on all Orders above ₹999" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                            <div class="min-w-0">
                                <label class="block text-[11px] font-bold text-stone-600 mb-1">Banner Link URL</label>
                                <input type="text" id="navbarAnnouncementLink" name="announcement_link" placeholder="/shop" class="w-full bg-white border border-stone-200 rounded-xl px-3 py-2 text-xs font-bold text-stone-800 focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Navigation Links Repeater -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                                    <i class="fas fa-link mr-1 text-red-600"></i>Navigation Links
                                </label>
                                <p class="text-[11px] text-stone-500 hidden sm:block">Customize labels, URLs, sparkle highlight style, and visibility</p>
                            </div>
                            <button type="button" onclick="addNavbarLinkRow()" class="px-3 py-1.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shrink-0">
                                <i class="fas fa-plus-circle text-xs"></i>
                                <span>Add Nav Link</span>
                            </button>
                        </div>

                        <div id="navbarLinksContainer" class="space-y-2">
                            <!-- Injected dynamically by JS -->
                        </div>
                    </div>

                    <!-- 3. Header Action Icons Visibility -->
                    <div class="bg-stone-50/80 rounded-2xl p-3.5 sm:p-4 border border-stone-200/80 space-y-3">
                        <label class="block text-xs font-bold text-stone-700 uppercase tracking-wider">
                            <i class="fas fa-toggle-on mr-1 text-red-600"></i>Header Action Buttons Visibility
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3">
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 bg-white rounded-xl border border-stone-200 text-xs font-bold text-stone-700 cursor-pointer hover:border-red-300">
                                <input type="checkbox" id="navbarShowSearch" name="show_search" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <i class="fas fa-search text-stone-500"></i>
                                <span class="truncate">Search</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 bg-white rounded-xl border border-stone-200 text-xs font-bold text-stone-700 cursor-pointer hover:border-red-300">
                                <input type="checkbox" id="navbarShowWishlist" name="show_wishlist" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <i class="fas fa-heart text-stone-500"></i>
                                <span class="truncate">Wishlist</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 bg-white rounded-xl border border-stone-200 text-xs font-bold text-stone-700 cursor-pointer hover:border-red-300">
                                <input type="checkbox" id="navbarShowAccount" name="show_account" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <i class="fas fa-user text-stone-500"></i>
                                <span class="truncate">Account</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 sm:p-3 bg-white rounded-xl border border-stone-200 text-xs font-bold text-stone-700 cursor-pointer hover:border-red-300">
                                <input type="checkbox" id="navbarShowCart" name="show_cart" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                                <i class="fas fa-shopping-bag text-stone-500"></i>
                                <span class="truncate">Cart Bag</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="checkbox" id="navbarSectionActive" name="is_active" checked class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                        <label for="navbarSectionActive" class="text-xs font-bold text-stone-700">Navbar Section Active</label>
                    </div>

                </div>

                <!-- Footer Actions -->
                <div class="p-3 sm:p-4 px-4 sm:px-6 bg-stone-50 border-t border-stone-100 shrink-0 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="closeNavbarSettingsModal()" class="btn-secondary text-xs px-4 py-2 cursor-pointer">Cancel</button>
                    <button type="submit" id="navbarSettingsSubmitBtn" class="btn-primary text-xs px-5 py-2 flex items-center gap-2 shadow-sm cursor-pointer">
                        <i class="fas fa-save"></i>
                        <span>Save Navbar Settings</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const adminMediaBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin/media' : '/admin/media';
    let managerData = null;
    let currentView = 'sections';
    let currentPreviewUrl = '';

    document.addEventListener('DOMContentLoaded', function() {
        loadManagerData();
    });

    // 1. Fetch and render structured manager data with sticky scroll retention
    async function loadManagerData(preserveScroll = true, targetSectionId = null) {
        const loading = document.getElementById('managerLoading');
        const list = document.getElementById('sectionsList');
        
        // Save current scroll position before any DOM updates
        const savedScrollY = (preserveScroll && typeof window !== 'undefined')
            ? (window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0)
            : null;

        // Only show full loading block on cold initial page load when no sections exist yet
        const isInitial = !list || list.children.length === 0;
        if (isInitial && loading) loading.classList.remove('hidden');
        if (isInitial && list) list.classList.add('hidden');

        try {
            const res = await axios.get(`${adminMediaBase}/manager-data`);
            if (res.data && res.data.success) {
                managerData = res.data.data;
                renderSections(managerData);

                // Instantly restore scroll position or stick to target section without jumping to top
                requestAnimationFrame(() => {
                    if (targetSectionId) {
                        const targetEl = document.getElementById(targetSectionId);
                        if (targetEl) {
                            targetEl.scrollIntoView({ behavior: 'instant', block: 'nearest' });
                            return;
                        }
                    }
                    if (savedScrollY !== null) {
                        window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                    }
                });
            }
        } catch (err) {
            console.error('Failed to load manager data', err);
            toastr.error('Failed to load media manager data.');
        } finally {
            if (loading) loading.classList.add('hidden');
            if (list) list.classList.remove('hidden');
            if (savedScrollY !== null && !targetSectionId) {
                requestAnimationFrame(() => {
                    window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                });
            }
        }
    }

    // 2. Render all section accordions & cards
    function renderSections(data) {
        const container = document.getElementById('sectionsList');
        container.innerHTML = '';

        const selectedPage = document.getElementById('filterPage').value;
        const selectedSection = document.getElementById('filterSection').value;
        const selectedDevice = document.getElementById('filterDevice').value;

        data.sections.forEach(section => {
            // Page filter check
            if (selectedPage !== 'all' && section.page !== selectedPage) {
                return;
            }
            // Section filter check
            if (selectedSection !== 'all' && section.id !== selectedSection) {
                return;
            }

            // Create Section Wrapper Card
            const secCard = document.createElement('div');
            secCard.className = 'bg-white rounded-2xl sm:rounded-3xl shadow-xs border border-stone-100 overflow-hidden';
            secCard.id = `sec-card-${section.id}`;

            // Section Header
            const isHero = section.id === 'hero';
            const isBlogReels = section.id === 'blog_reels' || section.is_blog_reels_section;

            // Generate header action buttons per section
            let headerActionBtn = '';
            if (isHero) {
                headerActionBtn = `
                    <button type="button" onclick="openAddHeroSlideModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-plus-circle text-xs"></i>
                        <span>+ Add Slide</span>
                    </button>
                `;
            } else if (isBlogReels) {
                headerActionBtn = `
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <button type="button" onclick="openBlogReelsSettingsModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-2xs transition-all flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-cog text-xs text-stone-500"></i>
                            <span class="hidden sm:inline">Settings</span>
                        </button>
                        <button type="button" onclick="window.openAddVideoReelModal ? window.openAddVideoReelModal() : openAddVideoReelModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-plus-circle text-xs"></i>
                            <span>+ Add Video</span>
                        </button>
                    </div>
                `;
            } else if (section.is_custom_crochet_section) {
                headerActionBtn = `
                    <button type="button" onclick="window.openCustomCrochetModal ? window.openCustomCrochetModal() : openCustomCrochetModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-edit text-xs"></i>
                        <span>Edit Banner</span>
                    </button>
                `;
            } else if (section.is_brand_story_section) {
                headerActionBtn = `
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <button type="button" onclick="openMediaPicker('brand_story')" class="px-3 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-2xs transition-all flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-images text-red-500 text-xs"></i>
                            <span class="hidden sm:inline">Background</span>
                        </button>
                        <button type="button" onclick="window.openBrandStoryModal ? window.openBrandStoryModal() : openBrandStoryModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-edit text-xs"></i>
                            <span>Edit Story</span>
                        </button>
                    </div>
                `;
            } else if (section.is_footer_section) {
                headerActionBtn = `
                    <button type="button" onclick="openFooterSettingsModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-sliders-h text-xs"></i>
                        <span>Edit Footer</span>
                    </button>
                `;
            } else if (section.is_navbar_section) {
                headerActionBtn = `
                    <button type="button" onclick="openNavbarSettingsModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-sliders-h text-xs"></i>
                        <span>Edit Navbar</span>
                    </button>
                `;
            } else if (section.is_product_section) {
                headerActionBtn = `
                    <a href="${window.location.pathname.startsWith('/knottele') ? '/knottele/admin/products' : '/admin/products'}" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-boxes text-xs"></i>
                        <span>Manage Products</span>
                    </a>
                `;
            } else if (section.is_text_only_section) {
                headerActionBtn = `
                    <button type="button" onclick="openTextSectionEditModal('${section.page}', '${section.id}')" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-sliders-h text-xs"></i>
                        <span>Edit Content</span>
                    </button>
                `;
            } else if (section.is_testimonial_section) {
                headerActionBtn = `
                    <button type="button" onclick="openAddTestimonialModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-plus-circle text-xs"></i>
                        <span>+ Add Review</span>
                    </button>
                `;
            } else if (section.is_about_story_section) {
                headerActionBtn = `
                    <button type="button" onclick="openAboutStoryModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-edit text-xs"></i>
                        <span>Edit Story</span>
                    </button>
                `;
            } else if (section.is_custom_order_items_section) {
                headerActionBtn = `
                    <button type="button" onclick="openAddCustomOrderItemModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-plus-circle text-xs"></i>
                        <span>+ Add Item</span>
                    </button>
                `;
            } else if (section.is_craft_pillars_section) {
                headerActionBtn = `
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <button type="button" onclick="openCraftPillarsHeaderModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-2xs transition-all flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-cog text-xs text-stone-500"></i>
                            <span class="hidden sm:inline">Header</span>
                        </button>
                        <button type="button" onclick="openAddCraftPillarModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-plus-circle text-xs"></i>
                            <span>+ Add Pillar</span>
                        </button>
                    </div>
                `;
            } else if (section.is_contact_intro_section) {
                headerActionBtn = `
                    <button type="button" onclick="openContactIntroModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-edit text-xs"></i>
                        <span>Edit Intro</span>
                    </button>
                `;
            } else if (section.is_contact_info_section) {
                headerActionBtn = `
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <button type="button" onclick="openContactInfoHeaderModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-stone-200 shadow-2xs transition-all flex items-center gap-1 cursor-pointer">
                            <i class="fas fa-cog text-xs text-stone-500"></i>
                            <span class="hidden sm:inline">Header</span>
                        </button>
                        <button type="button" onclick="openAddContactInfoItemModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-plus-circle text-xs"></i>
                            <span>+ Add Detail</span>
                        </button>
                    </div>
                `;
            } else if (section.is_contact_form_section) {
                headerActionBtn = `
                    <button type="button" onclick="openContactFormModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer shrink-0">
                        <i class="fas fa-edit text-xs"></i>
                        <span>Edit Form</span>
                    </button>
                `;
            } else if (section.is_contact_faqs_section) {
                headerActionBtn = `
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <button type="button" onclick="openContactFaqsHeaderModal()" class="px-3 py-2 rounded-xl bg-white hover:bg-stone-50 text-stone-700 text-xs font-bold border border-[#E7D1CC] transition-all flex items-center gap-1 shadow-2xs cursor-pointer">
                            <i class="fas fa-cog text-xs text-stone-500"></i>
                            <span class="hidden sm:inline">Header</span>
                        </button>
                        <button type="button" onclick="openAddContactFaqModal()" class="px-3.5 sm:px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-xs transition-all flex items-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-plus-circle text-xs"></i>
                            <span>+ Add FAQ</span>
                        </button>
                    </div>
                `;
            }

            const header = `
                <div class="px-3.5 sm:px-6 md:px-8 py-3.5 sm:py-4 border-b border-stone-100 bg-stone-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-red-50 border border-red-100 flex items-center justify-center text-red-600 font-bold text-sm sm:text-base shadow-2xs shrink-0">
                            <i class="${getSectionIcon(section.id)}"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm sm:text-base md:text-lg font-bold text-stone-800 truncate">${section.title}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold bg-stone-200/80 text-stone-700 shrink-0">${section.badge}</span>
                            </div>
                            <p class="text-xs text-stone-500 font-medium hidden sm:block truncate">${section.description}</p>
                        </div>
                    </div>
                    ${headerActionBtn}
                </div>
            `;

            // Section Body (Slots / Categories / Product Info)
            let bodyContent = '';

            if (section.is_category_section) {
                // Shop by Category Dynamic Grid
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                            ${data.categories.map(cat => renderCategoryCard(cat)).join('')}
                        </div>
                    </div>
                `;
            } else if (section.is_blog_reels_section) {
                // Dynamic Video & Reels Section (Behind the Stitches)
                const reels = section.items || section.reels || [];
                const secSettings = section.section_settings || section.metadata || {};
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl p-3.5 sm:p-5 mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold text-sm shadow-2xs shrink-0">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-stone-800 text-xs sm:text-sm truncate">${escapeHtml(secSettings.title || 'Behind the Stitches')}</h4>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 shrink-0">${escapeHtml(secSettings.badge || 'Watch & Learn')}</span>
                                    </div>
                                    <p class="text-xs text-stone-500 hidden sm:block">${escapeHtml(secSettings.description || 'Step inside our atelier. Watch the craft, hear the rhythmic click of hooks, and learn styling tips from our master crocheters.')}</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            ${reels.length > 0 ? reels.map(r => renderVideoReelCard(r)).join('') : `
                                <div class="col-span-full py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <i class="fas fa-film text-2xl sm:text-3xl mb-2 text-stone-300"></i>
                                    <p class="text-sm font-bold text-stone-600">No videos or reels added yet</p>
                                    <p class="text-xs text-stone-400">Click "+ Add Video" above to publish your first video.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_product_section) {
                // Best Sellers Product-Driven Info
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl p-4 sm:p-5 mb-5 flex items-center gap-3.5">
                            <div class="w-9 h-9 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold text-sm shrink-0">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-stone-800 text-xs sm:text-sm">Product-Driven Best Sellers</h4>
                                <p class="text-xs text-stone-500">Automatically syncs with products flagged as "Bestseller" or "Featured" in your store catalog.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                            ${data.best_sellers.map(p => `
                                <div class="bg-white border border-stone-200 rounded-2xl p-2.5 sm:p-3 text-center shadow-2xs hover:shadow-xs transition-all">
                                    <img src="${p.image}" class="w-full aspect-square rounded-xl object-cover mb-2 border border-stone-100" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                                    <h5 class="text-xs font-bold text-stone-800 truncate" title="${p.name}">${p.name}</h5>
                                    <span class="text-[11px] font-bold text-red-600 block">₹${p.price}</span>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[9px] font-bold ${p.is_bestseller ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'}">
                                        ${p.is_bestseller ? 'Bestseller' : 'Featured'}
                                    </span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            } else if (section.is_text_only_section) {
                // Clean Text & Option Manager (Custom Order, Newsletter)
                const m = section.metadata || {};
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        <i class="fas fa-tag mr-1 text-[10px]"></i>${escapeHtml(m.badge || m.tag_text || 'Active Section')}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                        ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                    </span>
                                </div>

                                <div>
                                    <h3 class="text-lg sm:text-2xl font-bold text-stone-800 tracking-tight">
                                        ${escapeHtml(m.title || m.title_line1 || 'Headline Title')} 
                                        ${(m.subtitle || m.title_line2) ? `<span class="text-[#913638] italic font-serif font-normal block sm:inline sm:ml-1">${escapeHtml(m.subtitle || m.title_line2)}</span>` : ''}
                                    </h3>
                                </div>

                                <p class="text-xs sm:text-sm text-stone-600 leading-relaxed max-w-2xl">${escapeHtml(m.description || 'No description text set.')}</p>

                                ${m.cta_text ? `
                                    <div class="pt-1">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                            <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                                            <span>Button: "<strong>${escapeHtml(m.cta_text)}</strong>" ${m.cta_link ? `→ ${escapeHtml(m.cta_link)}` : ''}</span>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_testimonial_section) {
                // Customer Reviews & Testimonials Dynamic Cards
                const testimonials = section.testimonials || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                            ${testimonials.length > 0 ? testimonials.map(t => `
                                <div class="bg-white border border-stone-200 rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <div class="flex items-center text-amber-400 text-xs gap-0.5">
                                                ${Array.from({length: t.rating || 5}).map(() => '<i class="fas fa-star"></i>').join('')}
                                            </div>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${t.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                                ${t.is_active ? 'Active' : 'Hidden'}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-2xl bg-gradient-to-br from-red-50 to-rose-100 border border-red-200/80 text-red-700 flex items-center justify-center font-bold text-sm shadow-2xs shrink-0">
                                                ${escapeHtml((t.name || 'K').charAt(0).toUpperCase())}
                                            </div>
                                            <div class="truncate">
                                                <h4 class="font-bold text-stone-800 text-sm truncate">${escapeHtml(t.name)}</h4>
                                                <p class="text-[11px] text-stone-400 truncate">${escapeHtml(t.designation || 'Verified Patron')}</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-stone-600 italic leading-relaxed line-clamp-4 mb-4">"${escapeHtml(t.message)}"</p>
                                    </div>
                                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                        <button type="button" onclick="openEditTestimonialModal(${t.id})" class="flex-1 py-1.5 px-3 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" onclick="deleteTestimonial(${t.id})" class="py-1.5 px-3 rounded-xl bg-stone-50 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all cursor-pointer" title="Delete Review">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="col-span-full py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <i class="fas fa-star text-2xl sm:text-3xl mb-2 text-stone-300"></i>
                                    <p class="text-sm font-bold text-stone-600">No customer reviews yet</p>
                                    <p class="text-xs text-stone-400">Click "+ Add Review" above to add one.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_custom_crochet_section) {
                // Custom Crochet Banner & Hanging Tag Section Manager
                const m = section.metadata || {};
                const img = m.desktop_image || m.image_url || '/images/homepage/middleimg.png';
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
                                <!-- Details & Content -->
                                <div class="lg:col-span-7 space-y-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="fas fa-cut mr-1 text-[10px]"></i>Promotional Middle Banner
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                            ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="text-xl sm:text-3xl font-bold text-stone-800 tracking-tight mb-1">
                                            ${escapeHtml(m.title || "Custom Crochet")}
                                        </h3>
                                        ${m.subtitle ? `<p class="text-sm sm:text-base font-serif italic text-red-600">${escapeHtml(m.subtitle)}</p>` : ''}
                                    </div>

                                    <div class="space-y-1.5 bg-white/70 rounded-2xl p-3.5 border border-[#E7D1CC]/70">
                                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-wider block">Banner Narrative</span>
                                        <p class="text-xs text-stone-700 leading-relaxed">${escapeHtml(m.description || "Your imagination, our yarn. Let's create something special together.")}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        ${m.cta_text ? `
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                                <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                                                <span>Button: "<strong>${escapeHtml(m.cta_text)}</strong>" ${m.cta_link ? `→ ${escapeHtml(m.cta_link)}` : ''}</span>
                                            </div>
                                        ` : ''}
                                        ${m.tag_text ? `
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-900 shadow-2xs">
                                                <i class="fas fa-sticky-note text-amber-600 text-xs"></i>
                                                <span>Hanging Note: "<strong>${escapeHtml(m.tag_text)}</strong>" ${m.tag_active ? '(Active)' : '(Hidden)'}</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>

                                <!-- Image Preview Card with Pinned Hanging Tag -->
                                <div class="lg:col-span-5">
                                    <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-stone-100 border-2 border-white shadow-md group">
                                        <img src="${img}" alt="${escapeHtml(m.alt_text || m.title || 'Custom Crochet')}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">

                                        <!-- Hanging Paper Note Over Banner Image -->
                                        ${m.tag_active && m.tag_text ? `
                                            <div class="absolute bottom-3 right-3 bg-[#FFF9F6] border border-[#E7D1CC] rounded-xl p-2.5 shadow-lg text-center max-w-[140px] rotate-2 select-none">
                                                <div class="absolute -top-2 left-1/2 -translate-x-1/2 flex flex-col items-center">
                                                    <div class="w-1.5 h-2 bg-[#C89B61]/80 rounded-t"></div>
                                                    <div class="w-2 h-2 rounded-full bg-white border border-[#E7D1CC]"></div>
                                                </div>
                                                <p class="font-serif italic font-bold text-xs text-[#2E211E] leading-tight pt-1">
                                                    ${escapeHtml(m.tag_text)}
                                                </p>
                                                <span class="text-[10px] text-red-600 block mt-0.5">♡</span>
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_brand_story_section) {
                // Full-Width Storefront Replica Brand Story Section Manager
                const m = section.metadata || {};
                let img = m.desktop_image || m.image_url || '/images/homepage/middleimg.png';
                const knottelePrefix = window.location.pathname.startsWith('/knottele') ? '/knottele' : '';
                if (img.startsWith('/') && !img.startsWith(knottelePrefix) && knottelePrefix) {
                    img = knottelePrefix + img;
                }
                const features = (m.features && Array.isArray(m.features) && m.features.length > 0) ? m.features : [
                    { title: 'Handmade with Love', icon: 'heart' },
                    { title: 'Premium Yarn Quality', icon: 'sparkles' },
                    { title: '100% Pure Natural Cotton', icon: 'leaf' },
                    { title: 'Happiness Guaranteed', icon: 'smile' }
                ];
                
                const iconClassMap = {
                    heart: 'fas fa-heart',
                    sparkles: 'fas fa-wand-magic-sparkles',
                    leaf: 'fas fa-leaf',
                    smile: 'far fa-smile'
                };

                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8 space-y-4 sm:space-y-6">
                        <!-- Top Toolbar / Meta Bar -->
                        <div class="flex items-center gap-2 pb-2 border-b border-stone-100">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200 flex items-center gap-1.5">
                                <i class="fas fa-book-open text-[10px]"></i>
                                <span>Storefront Brand Story Narrative</span>
                            </span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                            </span>
                        </div>

                        <!-- FULL-WIDTH LIVE STOREFRONT PREVIEW BANNER -->
                        <div class="relative w-full rounded-2xl sm:rounded-3xl overflow-hidden bg-[#FCE9E5] border border-[#E7D1CC]/80 shadow-sm py-8 sm:py-12 lg:py-16 px-4 sm:px-8 lg:px-14 group">
                            <!-- Background Image Layer -->
                            <div class="absolute inset-0 z-0 w-full h-full pointer-events-none">
                                <img src="${escapeHtml(img)}" alt="Brand Story Background" class="w-full h-full object-cover object-center lg:object-right" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/homepage/middleimg.png';">
                                <div class="absolute inset-0 bg-gradient-to-r from-[#FFF5F2]/95 via-[#FFF5F2]/85 to-transparent w-full md:w-[60%]"></div>
                                <div class="absolute inset-0 bg-gradient-to-l from-[#FFF5F2]/90 via-[#FFF5F2]/60 to-transparent w-full md:w-[45%] ml-auto hidden lg:block"></div>
                            </div>

                            <!-- Live Content Layer -->
                            <div class="relative z-10 w-full">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-12 items-center justify-between">
                                    <!-- Left Column -->
                                    <div class="lg:col-span-7 space-y-3 sm:space-y-4 max-w-xl text-left">
                                        <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-[#A38F8B] font-sans block">
                                            ${escapeHtml(m.badge || "KNOTELLE Artisanal Crochet Craftsmanship")}
                                        </span>

                                        <h2 class="font-serif text-2xl sm:text-4xl lg:text-5xl font-bold text-[#2E211E] leading-[1.15] tracking-tight">
                                            ${escapeHtml(m.title || "Every Stitch")} <br>
                                            <span class="text-[#913638] italic font-normal font-serif">${escapeHtml(m.subtitle || "Has a Story")}</span>
                                        </h2>

                                        <p class="text-xs sm:text-sm md:text-base text-[#786864] leading-relaxed whitespace-pre-line">
                                            ${escapeHtml(m.description || "More than just crochet, we create memories, happiness and a little bit of magic.")}
                                        </p>

                                        <div class="pt-2 flex items-center gap-3">
                                            <div class="inline-flex items-center gap-2 px-5 sm:px-7 py-2.5 sm:py-3 rounded-full bg-[#913638] text-white text-xs sm:text-sm font-semibold shadow-xs">
                                                <span>${escapeHtml(m.cta_text || "Read Our Story")}</span>
                                                <i class="fas fa-arrow-right text-xs"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Right Column: 4 Feature Items Stacked -->
                                    <div class="lg:col-span-5 space-y-2.5 sm:space-y-3.5">
                                        ${features.map((feat, idx) => {
                                            const iconClass = iconClassMap[feat.icon] || 'fas fa-heart';
                                            return `
                                                <div class="flex items-center gap-3 px-3.5 sm:px-5 py-2.5 sm:py-3.5 rounded-2xl bg-white/95 backdrop-blur-md border border-[#E7D1CC]/80 shadow-xs">
                                                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] flex items-center justify-center text-[#913638] shrink-0 shadow-2xs">
                                                        <i class="${iconClass} text-xs sm:text-sm"></i>
                                                    </div>
                                                    <span class="text-xs sm:text-sm font-semibold text-[#2E211E] flex-1">
                                                        ${escapeHtml(feat.title || '')}
                                                    </span>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Info Footer with Quick Details -->
                        <div class="bg-stone-50/80 rounded-2xl p-3 sm:p-4 border border-stone-100 flex flex-wrap items-center justify-between gap-2 text-xs text-stone-500">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fas fa-link text-stone-400"></i>
                                    <span>Link: <strong class="text-stone-700">${escapeHtml(m.cta_link || '/about')}</strong></span>
                                </span>
                                <span class="text-stone-300 hidden sm:inline">|</span>
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <i class="fas fa-image text-stone-400"></i>
                                    <span>Image: <strong class="text-stone-700">${escapeHtml(String(img).split('/').pop() || 'middleimg.png')}</strong></span>
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_about_story_section) {
                // The KNOTELLE Story & Atelier Section Manager
                const m = section.metadata || {};
                const img = m.desktop_image || '/images/logo/Logo_1.png';
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <!-- Image Preview Card -->
                                <div class="lg:col-span-4">
                                    <div class="relative w-full aspect-[4/5] rounded-2xl overflow-hidden bg-stone-100 border border-[#E7D1CC] shadow-xs group">
                                        <img src="${img}" alt="${escapeHtml(m.alt_text || m.title || 'About Story')}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                                        
                                        <!-- Floating Badge Preview on Image -->
                                        ${m.floating_badge_active ? `
                                            <div class="absolute bottom-3 left-3 right-3 bg-white/95 backdrop-blur-md p-2.5 sm:p-3 rounded-xl border border-stone-200/80 shadow-md flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <i class="${getPillarIconFa(m.floating_badge_icon || 'Heart')}"></i>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-stone-800 truncate">${escapeHtml(m.floating_badge_title || '100% Handcrafted')}</p>
                                                    <p class="text-[10px] text-stone-500 truncate">${escapeHtml(m.floating_badge_subtitle || 'Never mass machine produced')}</p>
                                                </div>
                                            </div>
                                        ` : ''}

                                        ${m.mobile_image ? `
                                            <div class="absolute top-2 right-2 bg-stone-900/75 text-white text-[9px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                                                <i class="fas fa-mobile-alt"></i> Mobile Set
                                            </div>
                                        ` : ''}
                                    </div>
                                </div>

                                <!-- Text Details & Actions -->
                                <div class="lg:col-span-8 space-y-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="fas fa-tag mr-1 text-[10px]"></i>${escapeHtml(m.badge || m.tag_text || 'The KNOTELLE Story')}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                            ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="text-xl sm:text-2xl font-bold text-stone-800 tracking-tight mb-1">
                                            ${escapeHtml(m.title || 'Every Loop Tells a Story')}
                                        </h3>
                                        ${m.subtitle ? `<p class="text-xs font-serif italic text-red-600">${escapeHtml(m.subtitle)}</p>` : ''}
                                    </div>

                                    <div class="space-y-2 bg-white/70 rounded-2xl p-3.5 border border-[#E7D1CC]/70">
                                        <div>
                                            <span class="text-[10px] font-bold text-stone-400 uppercase tracking-wider block mb-0.5">Paragraph 1 (Primary Narrative)</span>
                                            <p class="text-xs text-stone-700 leading-relaxed">${escapeHtml(m.paragraph_1 || m.description || 'No description text set.')}</p>
                                        </div>
                                        ${m.paragraph_2 ? `
                                            <div class="pt-2 border-t border-stone-100">
                                                <span class="text-[10px] font-bold text-stone-400 uppercase tracking-wider block mb-0.5">Paragraph 2 (Secondary Narrative)</span>
                                                <p class="text-xs text-stone-700 leading-relaxed">${escapeHtml(m.paragraph_2)}</p>
                                            </div>
                                        ` : ''}
                                    </div>

                                    <!-- CTA & Floating Info Pills -->
                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        ${m.cta_text && m.cta_visible ? `
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                                <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                                                <span>CTA: "<strong>${escapeHtml(m.cta_text)}</strong>" ${m.cta_link ? `→ ${escapeHtml(m.cta_link)}` : ''}</span>
                                            </div>
                                        ` : ''}
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                            <i class="fas fa-image text-stone-400 text-xs"></i>
                                            <span>Alt: "${escapeHtml(m.alt_text || 'Artisan stitching crochet')}"</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_custom_order_items_section) {
                // Custom Order Items / Categories Dynamic Manager
                const items = section.items || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="mb-5 bg-[#FFF9F6] border border-[#E7D1CC] p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-800">Custom Order Form</span>
                                    <h4 class="font-bold text-stone-800 text-sm sm:text-base">Step 1: Item Types</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">${items.length} Active Options</span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">Categories and items available for customers to choose when requesting bespoke creations.</p>
                            </div>
                        </div>

                        <!-- Items Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
                            ${items.length > 0 ? items.map(item => `
                                <div class="bg-white border border-stone-200/90 rounded-2xl p-3.5 sm:p-4 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2.5">
                                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center justify-center font-bold text-sm sm:text-base shadow-2xs shrink-0">
                                                <i class="${getCustomOrderItemFaIcon(item.icon || item.tag_text || item.title)}"></i>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                                    #${item.sort_order || 1}
                                                </span>
                                                <button type="button" onclick="toggleCustomOrderItem(${item.id})" class="px-2 py-0.5 rounded-full text-[10px] font-bold transition-colors cursor-pointer ${item.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-stone-100 text-stone-500 hover:bg-stone-200 border border-stone-200'}" title="Click to toggle status">
                                                    ${item.is_active ? '● Active' : '○ Hidden'}
                                                </button>
                                            </div>
                                        </div>
                                        <h5 class="font-bold text-stone-800 text-sm mb-0.5 truncate">${escapeHtml(item.title || item.name)}</h5>
                                        <p class="text-xs text-stone-500 line-clamp-1 mb-2.5">${escapeHtml(item.subtitle || 'Custom pattern')}</p>
                                    </div>
                                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-stone-100">
                                        <button type="button" onclick="openEditCustomOrderItemModal(${item.id})" class="text-stone-600 hover:text-stone-900 text-xs font-bold px-2 py-1 rounded-lg hover:bg-stone-100 transition-colors flex items-center gap-1 cursor-pointer">
                                            <i class="fas fa-edit text-xs text-stone-400"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" onclick="deleteCustomOrderItem(${item.id})" class="text-rose-600 hover:text-rose-700 text-xs font-bold px-2 py-1 rounded-lg hover:bg-rose-50 transition-colors flex items-center gap-1 cursor-pointer">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="col-span-full py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <p class="text-sm font-bold text-stone-600">No custom order categories found</p>
                                    <p class="text-xs text-stone-400">Click "+ Add Item" above to add your first category.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_craft_pillars_section) {
                // Our Craft Pillars Dynamic Manager
                const meta = section.metadata || {};
                const items = section.items || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="mb-5 bg-[#FFF9F6] border border-[#E7D1CC] p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-800">${escapeHtml(meta.tag_text || 'Artisan Standards')}</span>
                                    <h4 class="font-bold text-stone-800 text-sm sm:text-base">${escapeHtml(meta.title || 'Our Craft Pillars')}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${meta.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">${meta.is_active ? 'Active' : 'Hidden'}</span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">${escapeHtml(meta.subtitle || 'Guiding principles behind every stitch we make.')}</p>
                            </div>
                        </div>

                        <!-- Pillars Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            ${items.length > 0 ? items.map(p => `
                                <div class="bg-white border border-stone-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-red-50 to-rose-100 border border-red-200 text-red-700 flex items-center justify-center font-bold text-base sm:text-lg shadow-2xs shrink-0">
                                                <i class="${getPillarIconFa(p.icon_name || p.icon || 'Leaf')}"></i>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                                    Order: #${p.sort_order || 1}
                                                </span>
                                                <button type="button" onclick="toggleCraftPillar(${p.id})" class="px-2 py-0.5 rounded-full text-[10px] font-bold transition-colors cursor-pointer ${p.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-stone-100 text-stone-500 hover:bg-stone-200 border border-stone-200'}" title="Click to toggle status">
                                                    ${p.is_active ? '● Active' : '○ Hidden'}
                                                </button>
                                            </div>
                                        </div>

                                        <h4 class="font-bold text-stone-800 text-sm sm:text-base mb-1 truncate">${escapeHtml(p.title)}</h4>
                                        <p class="text-xs text-stone-600 leading-relaxed line-clamp-3 mb-3">${escapeHtml(p.description)}</p>
                                    </div>

                                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                        <button type="button" onclick="openEditCraftPillarModal(${p.id})" class="flex-1 py-1.5 sm:py-2 px-3 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span>Edit Pillar</span>
                                        </button>
                                        <button type="button" onclick="deleteCraftPillar(${p.id})" class="py-1.5 sm:py-2 px-3 rounded-xl bg-stone-50 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all cursor-pointer" title="Delete Pillar">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="col-span-full py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <i class="fas fa-cubes text-2xl sm:text-3xl mb-2 text-stone-300"></i>
                                    <p class="text-sm font-bold text-stone-600">No craft pillars added yet</p>
                                    <p class="text-xs text-stone-400">Click "+ Add Pillar" above to define your craft pillars.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_contact_intro_section) {
                // Contact Introduction / Hero Section Manager
                const m = section.metadata || {};
                const img = m.desktop_image || m.image || '/images/logo/Logo_1.png';
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
                                <!-- Image Preview Card -->
                                <div class="lg:col-span-4">
                                    <div class="relative w-full aspect-[4/3] rounded-2xl overflow-hidden bg-stone-100 border border-[#E7D1CC] shadow-xs group">
                                        <img src="${img}" alt="${escapeHtml(m.alt_text || m.title || 'Contact Hero')}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                                        <div class="absolute bottom-3 left-3 right-3 bg-white/95 backdrop-blur-md p-2.5 rounded-xl border border-stone-200/80 shadow-md flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fas fa-handshake"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-bold text-stone-800 truncate">${escapeHtml(m.badge || "Let's Connect")}</p>
                                                <p class="text-[10px] text-stone-500 truncate">${escapeHtml(m.title || "Let's Connect")}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Details & Actions -->
                                <div class="lg:col-span-8 space-y-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                            <i class="fas fa-tag mr-1 text-[10px]"></i>${escapeHtml(m.badge || m.tag_text || "Let's Connect")}
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                            ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                        </span>
                                    </div>

                                    <div>
                                        <h3 class="text-xl sm:text-2xl font-bold text-stone-800 tracking-tight mb-1">
                                            ${escapeHtml(m.title || "Let's Connect")}
                                        </h3>
                                        ${m.subtitle ? `<p class="text-xs font-serif italic text-red-600">${escapeHtml(m.subtitle)}</p>` : ''}
                                    </div>

                                    <div class="space-y-1.5 bg-white/70 rounded-2xl p-3.5 border border-[#E7D1CC]/70">
                                        <span class="text-[10px] font-bold text-stone-400 uppercase tracking-wider block">Introductory Narrative</span>
                                        <p class="text-xs text-stone-700 leading-relaxed">${escapeHtml(m.description || "We're here to help bring your handcrafted crochet dreams to life.")}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                        ${m.cta_text ? `
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                                <i class="fas fa-mouse-pointer text-red-600 text-xs"></i>
                                                <span>Button: "<strong>${escapeHtml(m.cta_text)}</strong>" ${m.cta_link ? `→ ${escapeHtml(m.cta_link)}` : ''}</span>
                                            </div>
                                        ` : ''}
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-stone-700 shadow-2xs">
                                            <i class="fas fa-image text-stone-400 text-xs"></i>
                                            <span>Alt: "${escapeHtml(m.alt_text || 'Contact Banner')}"</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_contact_info_section) {
                // Contact Information & Atelier Section Manager
                const meta = section.metadata || {};
                const items = section.items || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="mb-5 bg-[#FFF9F6] border border-[#E7D1CC] p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-800">${escapeHtml(meta.tag_text || meta.badge || 'Atelier Studio')}</span>
                                    <h4 class="font-bold text-stone-800 text-sm sm:text-base">${escapeHtml(meta.title || 'KNOTELLE Studio')}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${meta.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">${meta.is_active ? 'Active' : 'Hidden'}</span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">${escapeHtml(meta.subtitle || 'Handmade with love in Bengaluru, India')}</p>
                                ${meta.custom_order_box_active ? `
                                    <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 bg-amber-50 border border-amber-200 rounded-lg text-amber-900 text-[11px] font-semibold">
                                        <i class="fas fa-gift text-amber-600 text-xs"></i>
                                        <span>Helper Box: "<strong>${escapeHtml(meta.custom_order_box_title || 'Looking for Custom Orders?')}</strong>"</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Contact Details Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                            ${items.length > 0 ? items.map(d => `
                                <div class="bg-white border border-stone-200/90 rounded-2xl p-4 sm:p-5 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-3">
                                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-gradient-to-br from-red-50 to-rose-100 border border-red-200 text-red-700 flex items-center justify-center font-bold text-base sm:text-lg shadow-2xs shrink-0">
                                                <i class="${getContactIconFa(d.icon || d.icon_name || 'MapPin')}"></i>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                                    Order: #${d.sort_order || 1}
                                                </span>
                                                <button type="button" onclick="toggleContactInfoItem(${d.id})" class="px-2 py-0.5 rounded-full text-[10px] font-bold transition-colors cursor-pointer ${d.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-stone-100 text-stone-500 hover:bg-stone-200 border border-stone-200'}" title="Click to toggle status">
                                                    ${d.is_active ? '● Active' : '○ Hidden'}
                                                </button>
                                            </div>
                                        </div>

                                        <h4 class="font-bold text-stone-800 text-sm sm:text-base mb-1 truncate">${escapeHtml(d.title)}</h4>
                                        <p class="text-xs sm:text-sm font-semibold text-stone-900 mb-0.5">${escapeHtml(d.value || d.description || '')}</p>
                                        ${d.address_line_2 ? `<p class="text-xs text-stone-500">${escapeHtml(d.address_line_2)}</p>` : ''}
                                        ${d.link || d.cta_link ? `<p class="text-[11px] font-mono text-red-600 truncate mt-2 bg-red-50/50 p-1.5 rounded-lg border border-red-100">Link: ${escapeHtml(d.link || d.cta_link)}</p>` : ''}
                                    </div>

                                    <div class="pt-3 mt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                                        <button type="button" onclick="openEditContactInfoItemModal(${d.id})" class="flex-1 py-1.5 sm:py-2 px-3 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span>Edit Detail</span>
                                        </button>
                                        <button type="button" onclick="deleteContactInfoItem(${d.id})" class="py-1.5 sm:py-2 px-3 rounded-xl bg-stone-50 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all cursor-pointer" title="Delete Detail">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="col-span-full py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <i class="fas fa-address-book text-2xl sm:text-3xl mb-2 text-stone-300"></i>
                                    <p class="text-sm font-bold text-stone-600">No contact details added yet</p>
                                    <p class="text-xs text-stone-400">Click "+ Add Detail" above to add phone, email, address, or hours.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_contact_form_section) {
                // Send Us a Message (Contact Form) Section Manager
                const m = section.metadata || {};
                const fields = m.fields || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="mb-5 border-b border-[#E7D1CC] pb-3.5">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        <i class="fas fa-tag mr-1 text-[10px]"></i>${escapeHtml(m.badge || m.tag_text || 'Get In Touch')}
                                    </span>
                                    <h3 class="text-base sm:text-xl font-bold text-stone-800">${escapeHtml(m.title || 'Send Us a Message')}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                        ${m.is_active ? '● Active' : '○ Hidden'}
                                    </span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">${escapeHtml(m.subtitle || 'Fill in your details and our team will get back to you promptly.')}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <!-- Button & Feedback Preview -->
                                <div class="bg-white/80 rounded-2xl p-3.5 sm:p-4 border border-[#E7D1CC] space-y-2.5">
                                    <div class="text-[10px] sm:text-[11px] font-bold text-stone-400 uppercase tracking-wider">Button & Notifications</div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-stone-700">Submit Button:</span>
                                        <span class="px-2.5 py-1 bg-red-600 text-white font-bold text-xs rounded-xl">${escapeHtml(m.cta_text || m.submit_btn_text || 'Send Message')}</span>
                                    </div>
                                    <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl space-y-0.5">
                                        <div class="text-xs font-bold text-emerald-900 flex items-center gap-1.5"><i class="fas fa-check-circle"></i> ${escapeHtml(m.success_title || 'Message Sent!')}</div>
                                        <p class="text-[11px] text-emerald-700">${escapeHtml(m.success_message || 'Thank you! Your message has been sent successfully.')}</p>
                                    </div>
                                </div>

                                <!-- Fields Configuration Summary -->
                                <div class="bg-white/80 rounded-2xl p-3.5 sm:p-4 border border-[#E7D1CC] space-y-2">
                                    <div class="text-[10px] sm:text-[11px] font-bold text-stone-400 uppercase tracking-wider mb-1">Configured Form Fields</div>
                                    <div class="space-y-1.5">
                                        ${fields.map(f => `
                                            <div class="flex items-center justify-between p-2 bg-stone-50 rounded-xl border border-stone-200 text-xs">
                                                <div>
                                                    <span class="font-bold text-stone-800">${escapeHtml(f.label || f.key)}</span>
                                                    <span class="text-[10px] text-stone-400 ml-1">("${escapeHtml(f.placeholder || '')}")</span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${f.required ? 'bg-amber-100 text-amber-800' : 'bg-stone-200 text-stone-600'}">
                                                    ${f.required ? '✓ Req' : '○ Opt'}
                                                </span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_contact_faqs_section) {
                // Frequently Asked Questions Section Manager
                const meta = section.metadata || {};
                const items = section.items || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="mb-5 bg-[#FFF9F6] border border-[#E7D1CC] p-3.5 sm:p-4 rounded-2xl flex items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-red-100 text-red-800">${escapeHtml(meta.tag_text || meta.badge || 'Help & Support')}</span>
                                    <h4 class="font-bold text-stone-800 text-sm sm:text-base">${escapeHtml(meta.title || 'Frequently Asked Questions')}</h4>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${meta.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">${meta.is_active ? 'Active' : 'Hidden'}</span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">${escapeHtml(meta.subtitle || 'Quick answers about our handmade creations, custom orders, and delivery.')}</p>
                            </div>
                        </div>

                        <!-- FAQ Items List -->
                        <div class="space-y-3 sm:space-y-4">
                            ${items.length > 0 ? items.map((q, idx) => `
                                <div class="bg-white border border-stone-200/90 rounded-2xl p-3.5 sm:p-5 shadow-2xs hover:shadow-boutique transition-all flex flex-col md:flex-row md:items-start justify-between gap-3 sm:gap-4 group">
                                    <div class="flex-1 space-y-1.5 sm:space-y-2">
                                        <div class="flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-red-50 text-red-700 font-bold text-xs flex items-center justify-center shrink-0">
                                                ${idx + 1}
                                            </span>
                                            <h4 class="font-bold text-stone-800 text-sm sm:text-base">${escapeHtml(q.question || q.title || '')}</h4>
                                        </div>
                                        <p class="text-xs text-stone-600 leading-relaxed pl-8 bg-stone-50/50 p-2.5 sm:p-3 rounded-xl border border-stone-100">${escapeHtml(q.answer || q.description || '')}</p>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0 self-end md:self-start pt-1 md:pt-0">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                            #${q.sort_order || (idx + 1)}
                                        </span>
                                        <button type="button" onclick="toggleContactFaq(${q.id})" class="px-2 py-0.5 rounded-full text-[10px] font-bold transition-colors cursor-pointer ${q.is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200' : 'bg-stone-100 text-stone-500 hover:bg-stone-200 border border-stone-200'}" title="Click to toggle status">
                                            ${q.is_active ? '● Active' : '○ Hidden'}
                                        </button>
                                        <button type="button" onclick="openEditContactFaqModal(${q.id})" class="py-1 px-2.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-600 hover:text-white text-xs font-bold transition-all flex items-center gap-1 cursor-pointer">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" onclick="deleteContactFaq(${q.id})" class="py-1 px-2.5 rounded-xl bg-stone-50 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all cursor-pointer" title="Delete FAQ">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            `).join('') : `
                                <div class="py-10 text-center text-stone-400 bg-stone-50 rounded-2xl border border-dashed border-stone-200">
                                    <i class="fas fa-question-circle text-2xl sm:text-3xl mb-2 text-stone-300"></i>
                                    <p class="text-sm font-bold text-stone-600">No FAQ questions added yet</p>
                                    <p class="text-xs text-stone-400">Click "+ Add FAQ" above to add customer questions and answers.</p>
                                </div>
                            `}
                        </div>
                    </div>
                `;
            } else if (section.is_footer_section) {
                // Footer Artwork, Columns & Navigation Dynamic Manager
                const m = section.metadata || {};
                const bgImg = m.bg_image || m.desktop_image || '/images/categories/footer.png';
                const col1Links = m.col1_links || [];
                const col2Links = m.col2_links || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="mb-5 border-b border-[#E7D1CC] pb-3.5">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        <i class="fas fa-shoe-prints mr-1 text-[10px]"></i>Footer & Navigation
                                    </span>
                                    <h3 class="text-base sm:text-2xl font-bold text-stone-800">${escapeHtml(m.title || 'KNOTELLE Boutique Footer')}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                        ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                    </span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">${escapeHtml(m.heart_tagline || m.subtitle || 'Made with ♡ for a kinder, cozier world.')}</p>
                            </div>

                            <!-- Panoramic Background Artwork Preview -->
                            <div class="mb-5">
                                <div class="text-xs font-bold text-stone-500 uppercase tracking-wider mb-2 flex items-center justify-between">
                                    <span>Panoramic Background Artwork</span>
                                    <span class="text-[10px] text-stone-400 font-normal hidden sm:inline">Recommended: 1920 × 600 px</span>
                                </div>
                                <div class="relative w-full h-28 sm:h-40 rounded-2xl overflow-hidden bg-stone-100 border border-[#E7D1CC] shadow-inner group">
                                    <img src="${bgImg}" alt="Footer Background" class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                                    <div class="absolute inset-0 bg-[#FFF9F6]/40 pointer-events-none"></div>
                                    <div class="absolute bottom-2.5 left-3 bg-white/90 backdrop-blur-xs px-2.5 py-1 rounded-xl text-[11px] font-bold text-stone-800 shadow-xs border border-stone-200">
                                        Panoramic Background Visual
                                    </div>
                                </div>
                            </div>

                            <!-- 3 Columns Preview Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mb-5">
                                <!-- Column 1: Quick Links -->
                                <div class="bg-white/85 rounded-2xl p-4 sm:p-5 border border-[#E7D1CC] space-y-2.5">
                                    <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                                        <h4 class="font-bold text-xs sm:text-sm text-stone-800 border-b-2 border-red-700 pb-0.5 inline-block">
                                            ${escapeHtml(m.col1_title || 'Quick Links')}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                            ${col1Links.length} links
                                        </span>
                                    </div>
                                    <ul class="space-y-1 text-xs text-stone-600">
                                        ${col1Links.map(l => `
                                            <li class="flex items-center justify-between py-1 px-2 rounded-lg bg-stone-50/60 text-[11px]">
                                                <span class="font-medium text-stone-800 truncate">${escapeHtml(l.label || l.name || '')}</span>
                                                <span class="text-stone-400 font-mono text-[10px] truncate max-w-[100px] ml-2">${escapeHtml(l.url || l.href || '')}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>

                                <!-- Column 2: Help -->
                                <div class="bg-white/85 rounded-2xl p-4 sm:p-5 border border-[#E7D1CC] space-y-2.5">
                                    <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                                        <h4 class="font-bold text-xs sm:text-sm text-stone-800 border-b-2 border-red-700 pb-0.5 inline-block">
                                            ${escapeHtml(m.col2_title || 'Help')}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">
                                            ${col2Links.length} links
                                        </span>
                                    </div>
                                    <ul class="space-y-1 text-xs text-stone-600">
                                        ${col2Links.map(l => `
                                            <li class="flex items-center justify-between py-1 px-2 rounded-lg bg-stone-50/60 text-[11px]">
                                                <span class="font-medium text-stone-800 truncate">${escapeHtml(l.label || l.name || '')}</span>
                                                <span class="text-stone-400 font-mono text-[10px] truncate max-w-[100px] ml-2">${escapeHtml(l.url || l.href || '')}</span>
                                            </li>
                                        `).join('')}
                                    </ul>
                                </div>

                                <!-- Column 3: Contact Details -->
                                <div class="bg-white/85 rounded-2xl p-4 sm:p-5 border border-[#E7D1CC] space-y-2.5">
                                    <div class="flex items-center justify-between border-b border-stone-100 pb-2">
                                        <h4 class="font-bold text-xs sm:text-sm text-stone-800 border-b-2 border-red-700 pb-0.5 inline-block">
                                            ${escapeHtml(m.col3_title || 'Contact')}
                                        </h4>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                            Store Contact
                                        </span>
                                    </div>
                                    <div class="space-y-1.5 text-xs text-stone-700">
                                        <p class="flex items-center gap-2"><i class="fas fa-phone-alt text-red-600 text-xs w-4"></i> <span class="font-bold">${escapeHtml(m.contact_phone || '+91 97730 39243')}</span></p>
                                        <p class="flex items-center gap-2"><i class="fas fa-envelope text-red-600 text-xs w-4"></i> <span class="font-bold truncate">${escapeHtml(m.contact_email || 'support@knotelle.in')}</span></p>
                                        <p class="flex items-center gap-2"><i class="fas fa-map-marker-alt text-red-600 text-xs w-4"></i> <span class="font-bold">${escapeHtml(m.contact_address || 'India')}</span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Channels & Copyright Row -->
                            <div class="p-3 sm:p-4 bg-white/70 rounded-2xl border border-[#E7D1CC] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-stone-500 font-bold text-[11px]">Social:</span>
                                    <span class="px-2 py-0.5 rounded-lg border text-[10px] font-semibold flex items-center gap-1 ${m.instagram_active !== false ? 'bg-pink-50 text-pink-700 border-pink-200' : 'bg-stone-50 text-stone-400 border-stone-200'}">
                                        <i class="fab fa-instagram"></i> IG
                                    </span>
                                    <span class="px-2 py-0.5 rounded-lg border text-[10px] font-semibold flex items-center gap-1 ${m.facebook_active !== false ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-stone-50 text-stone-400 border-stone-200'}">
                                        <i class="fab fa-facebook-f"></i> FB
                                    </span>
                                    <span class="px-2 py-0.5 rounded-lg border text-[10px] font-semibold flex items-center gap-1 ${m.pinterest_active !== false ? 'bg-red-50 text-red-700 border-red-200' : 'bg-stone-50 text-stone-400 border-stone-200'}">
                                        <i class="fab fa-pinterest-p"></i> Pin
                                    </span>
                                    <span class="px-2 py-0.5 rounded-lg border text-[10px] font-semibold flex items-center gap-1 ${m.youtube_active !== false ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-stone-50 text-stone-400 border-stone-200'}">
                                        <i class="fab fa-youtube"></i> YT
                                    </span>
                                </div>

                                <div class="text-stone-500 text-center sm:text-right">
                                    <p class="font-bold text-stone-700 text-[11px]">${escapeHtml((m.copyright_text || '© {year} Knotelle. All rights reserved.').replace('{year}', new Date().getFullYear()))}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.is_navbar_section) {
                // Navbar & Header Navigation Dynamic Manager
                const m = section.metadata || {};
                const navLinks = m.nav_links || [];
                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl sm:rounded-3xl p-4 sm:p-6 md:p-8 shadow-2xs">
                            <div class="mb-5 border-b border-[#E7D1CC] pb-3.5">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                        <i class="fas fa-compass mr-1 text-[10px]"></i>Header Navigation
                                    </span>
                                    <h3 class="text-base sm:text-2xl font-bold text-stone-800">${escapeHtml(m.title || 'Navbar & Header Navigation')}</h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-bold ${m.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                        ${m.is_active ? '● Live on Storefront' : '○ Hidden'}
                                    </span>
                                </div>
                                <p class="text-xs text-stone-600 font-medium hidden sm:block">Manage top announcement banner, navigation links, highlight sparkle pills, and action button visibility.</p>
                            </div>

                            <!-- Announcement Bar Preview -->
                            <div class="mb-5 p-3.5 rounded-2xl border ${m.announcement_active ? 'bg-amber-50/80 border-amber-200' : 'bg-stone-50 border-stone-200'} flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-7 h-7 rounded-xl flex items-center justify-center font-bold text-xs ${m.announcement_active ? 'bg-amber-100 text-amber-800' : 'bg-stone-200 text-stone-500'}">
                                        <i class="fas fa-bullhorn text-xs"></i>
                                    </span>
                                    <div>
                                        <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider block ${m.announcement_active ? 'text-amber-800' : 'text-stone-400'}">
                                            Announcement (${m.announcement_active ? 'Active' : 'Off'})
                                        </span>
                                        <p class="text-xs font-bold text-stone-800 line-clamp-1">${escapeHtml(m.announcement_text || '✨ Free Pan-India Delivery on all Orders above ₹999')}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Links Preview Pills -->
                            <div class="bg-white/85 rounded-2xl p-4 sm:p-5 border border-[#E7D1CC] mb-5">
                                <div class="flex items-center justify-between mb-2.5 border-b border-stone-100 pb-2">
                                    <span class="text-xs font-bold text-stone-500 uppercase tracking-wider">Navigation Links</span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-stone-100 text-stone-600">${navLinks.length} Items</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    ${navLinks.map((l, idx) => `
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold ${l.is_highlighted ? 'bg-[#FCE9E5] text-[#913638] border-[#E7D1CC]' : 'bg-white text-stone-800 border-stone-200'}">
                                            <span class="text-[10px] text-stone-400 font-mono">#${idx + 1}</span>
                                            ${l.is_highlighted ? '<i class="fas fa-sparkles text-amber-500 text-[10px]"></i>' : ''}
                                            <span class="font-bold">${escapeHtml(l.name)}</span>
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold ${l.is_active !== false ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-600'}">
                                                ${l.is_active !== false ? 'On' : 'Off'}
                                            </span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Header Action Icons Visibility Preview -->
                            <div class="p-3.5 bg-white/70 rounded-2xl border border-[#E7D1CC] flex flex-wrap items-center justify-between gap-3 text-xs">
                                <span class="text-stone-500 font-bold text-[11px]">Header Quick Actions:</span>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-xl border text-[11px] font-bold flex items-center gap-1 ${m.show_search !== false ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-stone-100 text-stone-400 border-stone-200'}">
                                        <i class="fas fa-search text-[10px]"></i> Search
                                    </span>
                                    <span class="px-2.5 py-1 rounded-xl border text-[11px] font-bold flex items-center gap-1 ${m.show_wishlist !== false ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-stone-100 text-stone-400 border-stone-200'}">
                                        <i class="fas fa-heart text-[10px]"></i> Wishlist
                                    </span>
                                    <span class="px-2.5 py-1 rounded-xl border text-[11px] font-bold flex items-center gap-1 ${m.show_account !== false ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-stone-100 text-stone-400 border-stone-200'}">
                                        <i class="fas fa-user text-[10px]"></i> Account
                                    </span>
                                    <span class="px-2.5 py-1 rounded-xl border text-[11px] font-bold flex items-center gap-1 ${m.show_cart !== false ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-stone-100 text-stone-400 border-stone-200'}">
                                        <i class="fas fa-shopping-bag text-[10px]"></i> Cart
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (section.slots && section.slots.length > 0) {
                // Regular Slot Cards
                const filteredSlots = section.slots.filter(s => {
                    if (selectedDevice !== 'all' && s.device !== 'all' && s.device !== selectedDevice) {
                        return false;
                    }
                    return true;
                });

                bodyContent = `
                    <div class="p-3.5 sm:p-6 md:p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            ${filteredSlots.map(s => renderSlotCard(s)).join('')}
                        </div>
                    </div>
                `;
            }

            secCard.innerHTML = header + bodyContent;
            container.appendChild(secCard);
        });
    }

    // 3. Render individual Slot Card
    function renderSlotCard(slot) {
        const hasMedia = slot.media && slot.media.url;
        const imgUrl = hasMedia ? slot.media.url : '/images/logo/Logo_1.png';
        const isActive = slot.media ? slot.media.is_active : true;
        const m = slot.media || {};
        const isHero = slot.section === 'hero';
        const isCustomHero = isHero && (slot.is_custom_slide || m.id);
        const slideId = m.id || slot.media_id;

        if (isHero) {
            return `
                <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                    <div>
                        <!-- Hero Slide Header Badges -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <div class="flex items-center gap-1.5">
                                <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                    Slide #${m.sort_order || slot.sort_order || 1}
                                </span>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider ${getDeviceBadgeClass(slot.device || m.device || 'desktop')}">
                                    <i class="${getDeviceIcon(slot.device || m.device || 'desktop')} mr-1"></i>${slot.device || m.device || 'desktop'}
                                </span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold ${isActive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                                ${isActive ? '● Active' : '○ Inactive'}
                            </span>
                        </div>

                        <!-- Image Preview Container -->
                        <div class="relative w-full aspect-[16/9] rounded-xl overflow-hidden bg-[#FFF9F6] border border-stone-200/70 mb-3.5 flex items-center justify-center cursor-pointer group-hover:border-red-300 transition-colors"
                             onclick="handleSlotPreviewClick('${slot.page}', '${slot.section}', '${slot.slot}')">
                            <img src="${m.desktop_image || imgUrl}" alt="${escapeHtml(m.title || slot.title)}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                            
                            <div class="absolute inset-0 bg-stone-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <span class="px-3 py-1.5 rounded-full bg-white text-stone-800 text-xs font-bold shadow-md flex items-center gap-1">
                                    <i class="fas fa-search-plus text-xs"></i> Preview
                                </span>
                            </div>

                            ${m.mobile_image && m.mobile_image !== m.desktop_image ? `
                                <div class="absolute bottom-2 right-2 bg-stone-900/75 backdrop-blur-xs text-white text-[9px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                                    <i class="fas fa-mobile-alt"></i> Mobile Visual Set
                                </div>
                            ` : ''}
                        </div>

                        <!-- Title & Tagline -->
                        <div class="mb-2">
                            <h4 class="font-bold text-stone-800 text-sm mb-0.5 line-clamp-1">${escapeHtml(m.title || slot.title)}</h4>
                            ${(m.tagline || m.tag_text) ? `<p class="text-[11px] font-semibold text-red-600 italic line-clamp-1">${escapeHtml(m.tagline || m.tag_text)}</p>` : ''}
                        </div>

                        <!-- Description Snippet -->
                        <p class="text-[11px] text-stone-500 mb-3 leading-relaxed line-clamp-2">${escapeHtml(m.description || slot.description || 'No description provided.')}</p>

                        <!-- CTA Buttons Info Pills -->
                        <div class="space-y-1.5 mb-3 bg-stone-50 p-2.5 rounded-xl border border-stone-100 text-[10px] font-semibold text-stone-600">
                            <div class="flex items-center justify-between">
                                <span class="text-stone-400">Primary CTA:</span>
                                <span class="font-bold text-stone-800 truncate max-w-[140px]">${escapeHtml(m.primary_button_text || m.cta_text || 'Shop Now')} → ${escapeHtml(m.primary_button_link || m.cta_link || '/shop')}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-stone-400">Secondary CTA:</span>
                                <span class="font-bold text-stone-800 truncate max-w-[140px]">${escapeHtml(m.secondary_button_text || m.secondary_cta_text || 'Explore Collections')} → ${escapeHtml(m.secondary_button_link || m.secondary_cta_link || '/shop')}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Card Actions Toolbar -->
                    <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                        <button type="button" onclick="openEditHeroSlideModal(${slideId || 0}, '${slot.slot}')"
                                class="flex-1 py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
                            <i class="fas fa-edit text-xs"></i>
                            <span>Edit Slide</span>
                        </button>

                        <button type="button" onclick="deleteHeroSlide(${slideId || 0})"
                                class="py-2 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold transition-all flex items-center gap-1 cursor-pointer" title="Delete Hero Slide">
                            <i class="fas fa-trash-alt text-xs"></i>
                            <span>Delete</span>
                        </button>
                    </div>

                </div>
            `;
        }

        return `
            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                
                <div>
                    <!-- Slot Header & Badges -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider ${getDeviceBadgeClass(slot.device)}">
                            <i class="${getDeviceIcon(slot.device)} mr-1"></i>${slot.device}
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold ${isActive && hasMedia ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500'}">
                            ${hasMedia ? (isActive ? 'Active' : 'Inactive') : 'Default Fallback'}
                        </span>
                    </div>

                    <!-- Image Preview Container -->
                    <div class="relative w-full aspect-[16/9] rounded-xl overflow-hidden bg-[#FFF9F6] border border-stone-200/70 mb-3.5 flex items-center justify-center cursor-pointer group-hover:border-red-300 transition-colors"
                         onclick="handleSlotPreviewClick('${slot.page}', '${slot.section}', '${slot.slot}')">
                        <img src="${imgUrl}" alt="${escapeHtml(m.title || slot.title)}" class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                        
                        <div class="absolute inset-0 bg-stone-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <span class="px-3 py-1.5 rounded-full bg-white text-stone-800 text-xs font-bold shadow-md flex items-center gap-1">
                                <i class="fas fa-search-plus text-xs"></i> Preview
                            </span>
                        </div>
                    </div>

                    <!-- Title & Slot Info -->
                    <h4 class="font-bold text-stone-800 text-sm mb-0.5">${escapeHtml(m.title || slot.title)}</h4>
                    ${m.subtitle ? `<p class="text-[11px] font-semibold text-red-600 italic mb-1">${escapeHtml(m.subtitle)}</p>` : ''}
                    <p class="text-[11px] text-stone-500 mb-2 leading-relaxed line-clamp-2">${escapeHtml(m.description || slot.description)}</p>
                    
                    ${m.cta_text ? `
                        <div class="mb-2 text-[10px] font-bold text-stone-600 bg-stone-50 px-2.5 py-1.5 rounded-lg flex items-center gap-1">
                            <i class="fas fa-link text-red-500 text-[10px]"></i>
                            <span>Button: "${escapeHtml(m.cta_text)}"</span>
                        </div>
                    ` : ''}

                    <div class="flex items-center justify-between text-[11px] text-stone-400 font-semibold bg-stone-50 px-2.5 py-1.5 rounded-lg mb-3">
                        <span>Recommended:</span>
                        <span class="font-bold text-stone-700">${slot.recommended_dimensions}</span>
                    </div>
                </div>

                <!-- Card Actions Toolbar -->
                <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-2">
                    <button onclick="handleSlotReplaceClick('${slot.page}', '${slot.section}', '${slot.slot}')"
                            class="flex-1 py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-2xs hover:shadow-xs transition-all flex items-center justify-center gap-1.5 active:scale-95">
                        <i class="fas fa-camera text-xs"></i>
                        <span>Replace Image</span>
                    </button>

                    <button onclick="handleSlotOptionsClick('${slot.page}', '${slot.section}', '${slot.slot}')"
                            class="py-2 px-3 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold transition-all flex items-center gap-1" title="Edit Options & Content">
                        <i class="fas fa-sliders-h text-xs"></i>
                        <span>Options</span>
                    </button>

                    ${hasMedia ? `
                        <button onclick="detachSlot('${slot.page}', '${slot.section}', '${slot.slot}')"
                                class="p-2 rounded-xl bg-stone-100 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all" title="Detach Slot">
                            <i class="fas fa-unlink"></i>
                        </button>
                    ` : ''}
                </div>

            </div>
        `;
    }

    // 4. Render Category Card
    function renderCategoryCard(cat) {
        const adminBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin' : '/admin';
        return `
            <div class="bg-white border border-stone-200 rounded-2xl p-4 shadow-2xs hover:shadow-boutique transition-all flex flex-col items-center text-center group">
                <div class="relative w-24 h-24 rounded-full overflow-hidden border-2 border-[#E7D1CC] shadow-xs mb-3 bg-[#FFF9F6]">
                    <img src="${cat.image_url}" alt="${escapeHtml(cat.name)}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                </div>
                
                <h4 class="font-bold text-stone-800 text-sm mb-0.5">${escapeHtml(cat.name)}</h4>
                <span class="text-[11px] text-stone-500 font-semibold mb-3">${cat.product_count} Active Products</span>

                <div class="w-full pt-3 border-t border-stone-100 flex items-center justify-center gap-2">
                    <a href="${adminBase}/categories/${cat.id}/edit"
                       class="flex-1 py-2 px-3 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                        <i class="fas fa-edit text-[10px]"></i>
                        <span>Edit Category</span>
                    </a>
                    <button type="button" onclick="openCategoryUploadModal(${cat.id})"
                            class="py-2 px-2.5 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold transition-all flex items-center justify-center cursor-pointer" title="Manage Category Image">
                        <i class="fas fa-image text-xs"></i>
                    </button>
                </div>
            </div>
        `;
    }

    // Helpers
    function findSlot(page, section, slotKey) {
        if (!managerData || !managerData.sections) return null;
        const sec = managerData.sections.find(s => s.id === section);
        if (!sec || !sec.slots) return null;
        return sec.slots.find(s => s.slot === slotKey) || null;
    }

    function getSectionIcon(id) {
        switch (id) {
            case 'hero': return 'fas fa-images';
            case 'blog_reels': return 'fas fa-video';
            case 'categories': return 'fas fa-th-large';
            case 'custom_crochet': return 'fas fa-cut';
            case 'brand_story': return 'fas fa-book-open';
            case 'bestsellers': return 'fas fa-fire';
            case 'trust_benefits': return 'fas fa-shield-alt';
            case 'custom_order': return 'fas fa-magic';
            case 'newsletter': return 'fas fa-envelope-open-text';
            case 'footer': return 'fas fa-shoe-prints';
            case 'about_story': return 'fas fa-heart';
            case 'about_craft_pillars': return 'fas fa-feather-alt';
            case 'contact_intro': return 'fas fa-handshake';
            case 'contact_info': return 'fas fa-address-card';
            case 'contact_form': return 'fas fa-envelope-open-text';
            case 'contact_faqs': return 'fas fa-question-circle';
            case 'contact_header': return 'fas fa-envelope';
            case 'global_assets': return 'fas fa-globe';
            default: return 'fas fa-folder';
        }
    }

    function getDeviceBadgeClass(device) {
        switch (device) {
            case 'desktop': return 'bg-blue-50 text-blue-700 border border-blue-200';
            case 'mobile': return 'bg-purple-50 text-purple-700 border border-purple-200';
            default: return 'bg-stone-100 text-stone-700 border border-stone-200';
        }
    }

    function getDeviceIcon(device) {
        switch (device) {
            case 'desktop': return 'fas fa-desktop';
            case 'mobile': return 'fas fa-mobile-alt';
            default: return 'fas fa-globe';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    const PAGE_SECTIONS_MAP = {
        all: [
            { id: 'all', name: 'All Sections' }
        ],
        homepage: [
            { id: 'all', name: 'All Homepage Sections' },
            { id: 'hero', name: 'Hero Banner' },
            { id: 'categories', name: 'Shop by Category' },
            { id: 'custom_crochet', name: 'Custom Crochet Banner' },
            { id: 'brand_story', name: 'Brand Story' },
            { id: 'bestsellers', name: 'Best Sellers' },
            { id: 'trust_benefits', name: 'Trust & Benefits' },
            { id: 'custom_order', name: 'Custom Order CTA' },
            { id: 'newsletter', name: 'Newsletter' },
            { id: 'blog_reels', name: 'Blog / Videos & Reels' },
        ],
        shop: [
            { id: 'all', name: 'Redirect to Category Manager ↗' }
        ],
        custom_order: [
            { id: 'all', name: 'All Custom Order Sections' },
            { id: 'custom_order_items', name: 'Step 1: Item Types & Categories' }
        ],
        about: [
            { id: 'all', name: 'All About Page Sections' },
            { id: 'about_story', name: 'The KNOTELLE Story' },
            { id: 'craft_pillars', name: 'Our Craft Pillars' }
        ],
        contact: [
            { id: 'all', name: 'All Contact Page Sections' },
            { id: 'contact_intro', name: 'Contact Introduction / Hero' },
            { id: 'contact_info', name: 'Contact Information & Atelier' },
            { id: 'contact_form', name: 'Send Us a Message / Form' },
            { id: 'contact_faqs', name: 'Frequently Asked Questions' }
        ],
        footer: [
            { id: 'all', name: 'All Footer Sections' },
            { id: 'footer', name: 'Footer Artwork & Navigation' }
        ]
    };

    function onPageFilterChange() {
        const page = document.getElementById('filterPage').value;
        if (page === 'shop') {
            const baseUrl = window.location.pathname.startsWith('/knottele') ? '/knottele' : '';
            window.location.href = baseUrl + '/admin/categories';
            return;
        }
        const sectionSelect = document.getElementById('filterSection');
        const sections = PAGE_SECTIONS_MAP[page] || [{ id: 'all', name: 'All Sections' }];

        sectionSelect.innerHTML = sections.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
        sectionSelect.value = 'all';

        applyFilters();
    }

    function applyFilters() {
        if (managerData) {
            renderSections(managerData);
        }
    }

    const CUSTOM_ORDER_ICONS = [
        { id: 'keychain', name: 'Keychain', fa: 'fas fa-key' },
        { id: 'flower', name: 'Flower', fa: 'fas fa-seedling' },
        { id: 'bouquet', name: 'Bouquet', fa: 'fas fa-spa' },
        { id: 'soft_toys', name: 'Soft Toys', fa: 'fas fa-paw' },
        { id: 'bag', name: 'Bags', fa: 'fas fa-shopping-bag' },
        { id: 'coin_purse', name: 'Coin Purse', fa: 'fas fa-wallet' },
        { id: 'phone_cover', name: 'Phone Cover', fa: 'fas fa-mobile-alt' },
        { id: 'cup', name: 'Cup', fa: 'fas fa-mug-hot' },
        { id: 'bookmark', name: 'Bookmark', fa: 'fas fa-bookmark' },
        { id: 'heart', name: 'Love / Heart', fa: 'fas fa-heart' },
        { id: 'leaf', name: 'Leaf', fa: 'fas fa-leaf' },
        { id: 'tree', name: 'Tree', fa: 'fas fa-tree' },
        { id: 'scissors', name: 'Hair Accessories', fa: 'fas fa-cut' },
        { id: 'clothing', name: 'Clothing', fa: 'fas fa-tshirt' },
        { id: 'gift', name: 'Gift Box', fa: 'fas fa-gift' },
        { id: 'sparkles', name: 'Sparkles', fa: 'fas fa-wand-magic-sparkles' },
        { id: 'palette', name: 'Palette / Art', fa: 'fas fa-palette' },
        { id: 'star', name: 'Star', fa: 'fas fa-star' }
    ];

    function getCustomOrderItemFaIcon(iconOrTitle) {
        if (!iconOrTitle) return 'fas fa-seedling';
        const key = String(iconOrTitle).toLowerCase().trim();
        if (key.includes('keychain') || key === 'key') return 'fas fa-key';
        if (key.includes('bouquet')) return 'fas fa-spa';
        if (key.includes('toy') || key.includes('bear') || key.includes('plush') || key.includes('rabbit')) return 'fas fa-paw';
        if (key.includes('bag') || key.includes('tote')) return 'fas fa-shopping-bag';
        if (key.includes('purse') || key.includes('wallet')) return 'fas fa-wallet';
        if (key.includes('phone')) return 'fas fa-mobile-alt';
        if (key.includes('cup') || key.includes('mug') || key.includes('coffee') || key.includes('tea')) return 'fas fa-mug-hot';
        if (key.includes('bookmark')) return 'fas fa-bookmark';
        if (key.includes('heart') || key.includes('love')) return 'fas fa-heart';
        if (key.includes('leaf') || key.includes('plant')) return 'fas fa-leaf';
        if (key.includes('tree')) return 'fas fa-tree';
        if (key.includes('hair') || key.includes('scrunch') || key.includes('clip') || key.includes('scissors')) return 'fas fa-cut';
        if (key.includes('cloth') || key.includes('wear') || key.includes('cardigan') || key.includes('top')) return 'fas fa-tshirt';
        if (key.includes('gift')) return 'fas fa-gift';
        if (key.includes('sparkle')) return 'fas fa-wand-magic-sparkles';
        if (key.includes('palette') || key.includes('art') || key.includes('concept')) return 'fas fa-palette';
        if (key.includes('star')) return 'fas fa-star';
        if (key.includes('flower') || key.includes('pot') || key.includes('flower2')) return 'fas fa-seedling';

        const found = CUSTOM_ORDER_ICONS.find(i => i.id === key);
        return found ? found.fa : 'fas fa-seedling';
    }

    function renderCustomOrderItemIconGrid(selectedId = 'flower') {
        const grid = document.getElementById('customOrderItemIconGrid');
        if (!grid) return;
        grid.innerHTML = CUSTOM_ORDER_ICONS.map(i => {
            const isSelected = (i.id.toLowerCase() === (selectedId || '').toLowerCase()) ||
                               (i.name.toLowerCase() === (selectedId || '').toLowerCase());
            return `
                <button type="button" onclick="selectCustomOrderItemIcon('${i.id}', '${i.name}')" class="flex flex-col items-center justify-center p-2 rounded-xl border text-center transition-all cursor-pointer ${isSelected ? 'bg-red-50 border-red-500 text-red-700 ring-2 ring-red-300 font-bold' : 'bg-white border-stone-200 text-stone-600 hover:border-stone-300 hover:bg-stone-100'}">
                    <i class="${i.fa} text-sm mb-1"></i>
                    <span class="text-[9px] leading-tight line-clamp-1">${i.name}</span>
                </button>
            `;
        }).join('');
    }

    function selectCustomOrderItemIcon(id, name) {
        if (document.getElementById('customOrderItemIcon')) {
            document.getElementById('customOrderItemIcon').value = id;
        }
        if (document.getElementById('selectedIconLabel')) {
            const found = CUSTOM_ORDER_ICONS.find(i => i.id.toLowerCase() === (id || '').toLowerCase());
            document.getElementById('selectedIconLabel').innerText = found ? found.name : (name || id);
        }
        renderCustomOrderItemIconGrid(id);
    }

    function openAddCustomOrderItemModal() {
        const form = document.getElementById('customOrderItemForm');
        if (form) form.reset();
        if (document.getElementById('customOrderItemId')) document.getElementById('customOrderItemId').value = '';
        if (document.getElementById('customOrderItemModalTitle')) document.getElementById('customOrderItemModalTitle').innerText = 'Add Custom Order Category';
        if (document.getElementById('customOrderItemSubmitBtnText')) document.getElementById('customOrderItemSubmitBtnText').innerText = 'Save Category';
        if (document.getElementById('customOrderItemActive')) document.getElementById('customOrderItemActive').checked = true;
        selectCustomOrderItemIcon('flower', 'Flower');

        const modal = document.getElementById('customOrderItemModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    async function openEditCustomOrderItemModal(id) {
        const modal = document.getElementById('customOrderItemModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Immediately pre-populate from local managerData so user gets instant modal response
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'custom_order_items' || s.is_custom_order_items_section);
            if (sec && sec.items) {
                const cached = sec.items.find(i => i.id == id);
                if (cached) {
                    if (document.getElementById('customOrderItemId')) document.getElementById('customOrderItemId').value = cached.id;
                    if (document.getElementById('customOrderItemTitle')) document.getElementById('customOrderItemTitle').value = cached.title || cached.name || '';
                    if (document.getElementById('customOrderItemSubtitle')) document.getElementById('customOrderItemSubtitle').value = cached.subtitle || '';
                    if (document.getElementById('customOrderItemSortOrder')) document.getElementById('customOrderItemSortOrder').value = cached.sort_order || 1;
                    if (document.getElementById('customOrderItemActive')) document.getElementById('customOrderItemActive').checked = cached.is_active !== false;
                    if (document.getElementById('customOrderItemModalTitle')) document.getElementById('customOrderItemModalTitle').innerText = `Edit: ${cached.title || cached.name}`;
                    if (document.getElementById('customOrderItemSubmitBtnText')) document.getElementById('customOrderItemSubmitBtnText').innerText = 'Update Category';
                    const iconVal = cached.icon || cached.tag_text || 'flower';
                    selectCustomOrderItemIcon(iconVal, iconVal);
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/custom-order/items/${id}`);
            if (res.data && res.data.success && (res.data.item || res.data.data)) {
                const item = res.data.item || res.data.data;
                if (document.getElementById('customOrderItemId')) document.getElementById('customOrderItemId').value = item.id;
                if (document.getElementById('customOrderItemTitle')) document.getElementById('customOrderItemTitle').value = item.title || item.name || '';
                if (document.getElementById('customOrderItemSubtitle')) document.getElementById('customOrderItemSubtitle').value = item.subtitle || '';
                if (document.getElementById('customOrderItemSortOrder')) document.getElementById('customOrderItemSortOrder').value = item.sort_order || 1;
                if (document.getElementById('customOrderItemActive')) document.getElementById('customOrderItemActive').checked = item.is_active !== false;

                if (document.getElementById('customOrderItemModalTitle')) document.getElementById('customOrderItemModalTitle').innerText = `Edit: ${item.title || item.name}`;
                if (document.getElementById('customOrderItemSubmitBtnText')) document.getElementById('customOrderItemSubmitBtnText').innerText = 'Update Category';
                const iconVal = item.icon || item.tag_text || 'flower';
                selectCustomOrderItemIcon(iconVal, iconVal);
            }
        } catch (err) {
            console.error('Failed to load category details', err);
        }
    }

    function closeCustomOrderItemModal() {
        const modal = document.getElementById('customOrderItemModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleCustomOrderItemSubmit(event) {
        event.preventDefault();
        const id = document.getElementById('customOrderItemId')?.value;
        const title = document.getElementById('customOrderItemTitle')?.value?.trim();
        const subtitle = document.getElementById('customOrderItemSubtitle')?.value?.trim() || '';
        const icon = document.getElementById('customOrderItemIcon')?.value || 'flower';
        const sortOrder = document.getElementById('customOrderItemSortOrder')?.value || 1;
        const isActive = document.getElementById('customOrderItemActive')?.checked ? 1 : 0;

        if (!title) {
            toastr.error('Item name is required.');
            return;
        }

        const submitBtn = document.getElementById('customOrderItemSubmitBtn');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const url = id ? `${adminMediaBase}/custom-order/items/${id}` : `${adminMediaBase}/custom-order/items`;
            const res = await axios.post(url, {
                _token: csrfToken || '',
                title: title,
                subtitle: subtitle,
                icon: icon,
                tag_text: icon,
                sort_order: sortOrder,
                is_active: isActive
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Saved successfully!');
                closeCustomOrderItemModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save category.');
            }
        } catch (err) {
            console.error('Failed to save custom order item', err);
            toastr.error(err.response?.data?.message || 'Error saving category.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    async function deleteCustomOrderItem(id) {
        if (!confirm('Are you sure you want to delete this custom order category?')) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let res;
            try {
                res = await axios.delete(`${adminMediaBase}/custom-order/items/${id}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            } catch (delErr) {
                res = await axios.post(`${adminMediaBase}/custom-order/items/${id}/delete`, {
                    _token: csrfToken || '',
                    _method: 'DELETE'
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            }
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Category deleted successfully.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to delete category.');
            }
        } catch (err) {
            console.error('Failed to delete category', err);
            toastr.error('Error deleting category.');
        }
    }

    async function toggleCustomOrderItem(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post(`${adminMediaBase}/custom-order/items/${id}/toggle`, {
                _token: csrfToken || ''
            }, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Status updated.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to update status.');
            }
        } catch (err) {
            console.error('Failed to toggle status', err);
            toastr.error('Error updating status.');
        }
    }

    function switchView(view) {
        currentView = view;
        const secContainer = document.getElementById('sectionsViewContainer');
        const libContainer = document.getElementById('libraryViewContainer');
        const secBtn = document.getElementById('viewSectionsBtn');
        const libBtn = document.getElementById('viewLibraryBtn');

        if (view === 'sections') {
            secContainer.classList.remove('hidden');
            libContainer.classList.add('hidden');
            secBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white text-red-700 shadow-xs flex items-center gap-1.5';
            libBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition-all text-stone-500 hover:text-stone-800 flex items-center gap-1.5';
        } else {
            secContainer.classList.add('hidden');
            libContainer.classList.remove('hidden');
            libBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition-all bg-white text-red-700 shadow-xs flex items-center gap-1.5';
            secBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold transition-all text-stone-500 hover:text-stone-800 flex items-center gap-1.5';
            initLibraryTable();
        }
    }

    // Slot Modal Handlers
    function handleSlotReplaceClick(page, section, slotKey) {
        const slotDef = findSlot(page, section, slotKey);
        if (!slotDef) {
            toastr.error('Slot definition not found.');
            return;
        }

        const m = slotDef.media || {};
        document.getElementById('targetPage').value = slotDef.page;
        document.getElementById('targetSection').value = slotDef.section;
        document.getElementById('targetSlot').value = slotDef.slot;
        document.getElementById('targetDevice').value = slotDef.device;
        document.getElementById('selectedMediaId').value = m.id || '';

        document.getElementById('slotModalTitle').innerText = `Replace / Configure ${m.title || slotDef.title}`;
        document.getElementById('slotModalSubtitle').innerText = `Target: ${slotDef.page} → ${slotDef.section} → ${slotDef.slot} (${slotDef.device})`;
        document.getElementById('slotModalDimensions').innerText = slotDef.recommended_dimensions || 'Any aspect ratio';
        
        document.getElementById('slotTitleInput').value = m.title || slotDef.title || '';
        document.getElementById('slotSubtitleInput').value = m.subtitle || '';
        document.getElementById('slotDescInput').value = m.description || '';
        document.getElementById('slotCtaTextInput').value = m.cta_text || '';
        document.getElementById('slotCtaLinkInput').value = m.cta_link || '';
        document.getElementById('slotTagTextInput').value = m.tag_text || '';
        document.getElementById('slotAltInput').value = m.alt_text || `${slotDef.title} Knotelle Boutique`;
        clearSlotFileInput();

        const modal = document.getElementById('slotUploadModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function handleSlotOptionsClick(page, section, slotKey) {
        const slotDef = findSlot(page, section, slotKey);
        if (!slotDef) return;

        const m = slotDef.media || {};
        document.getElementById('editMediaId').value = m.id || '';
        document.getElementById('editMediaPage').value = slotDef.page;
        document.getElementById('editMediaSection').value = slotDef.section;
        document.getElementById('editMediaSlot').value = slotDef.slot;

        document.getElementById('editMediaTitle').value = m.title || slotDef.title || '';
        document.getElementById('editMediaSubtitle').value = m.subtitle || '';
        document.getElementById('editMediaDesc').value = m.description || '';
        document.getElementById('editMediaCtaText').value = m.cta_text || '';
        document.getElementById('editMediaCtaLink').value = m.cta_link || '';
        document.getElementById('editMediaTagText').value = m.tag_text || '';
        document.getElementById('editMediaAlt').value = m.alt_text || '';
        document.getElementById('editMediaDevice').value = m.device || slotDef.device || 'all';
        document.getElementById('editMediaSort').value = m.sort_order || slotDef.sort_order || 1;
        document.getElementById('editMediaActive').checked = m.is_active !== false;

        const modal = document.getElementById('editMetadataModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function handleSlotPreviewClick(page, section, slotKey) {
        const slotDef = findSlot(page, section, slotKey);
        if (!slotDef) return;

        const hasMedia = slotDef.media && slotDef.media.url;
        const imgUrl = hasMedia ? slotDef.media.url : '/images/logo/Logo_1.png';
        const title = (slotDef.media && slotDef.media.title) || slotDef.title;
        const path = `${slotDef.page} → ${slotDef.section}`;

        openDevicePreview(imgUrl, title, path);
    }

    function closeSlotUploadModal() {
        const modal = document.getElementById('slotUploadModal');
        modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function handleSlotFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('slotFilePreviewImg').src = e.target.result;
                document.getElementById('slotFileName').innerText = file.name;
                document.getElementById('slotFileSize').innerText = (file.size / 1024).toFixed(1) + ' KB';
                document.getElementById('slotFilePreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearSlotFileInput() {
        document.getElementById('slotFileInput').value = '';
        document.getElementById('slotFilePreviewContainer').classList.add('hidden');
    }

    function broadcastMediaUpdate() {
        const timestamp = Date.now();
        try {
            localStorage.setItem('knotelle_media_updated', timestamp.toString());
            localStorage.setItem('knotelle_media_sync', timestamp.toString());
            localStorage.setItem('knotelle_contact_updated', timestamp.toString());
            localStorage.setItem('knotelle_about_updated', timestamp.toString());
        } catch(e){}
        try {
            if ('BroadcastChannel' in window) {
                const channel = new BroadcastChannel('knotelle_media_sync');
                channel.postMessage({ type: 'MEDIA_UPDATED', timestamp: timestamp });
                setTimeout(() => {
                    try { channel.close(); } catch(e) {}
                }, 200);
            }
        } catch(e){}
    }

    function openTextSectionEditModal(page, sectionId) {
        const sec = managerData?.sections?.find(s => s.id === sectionId);
        const m = sec?.metadata || {};

        document.getElementById('editMediaId').value = m.id || '';
        document.getElementById('editMediaPage').value = page || 'homepage';
        document.getElementById('editMediaSection').value = sectionId;
        document.getElementById('editMediaSlot').value = m.slot || 'default';

        document.getElementById('editModalHeaderTitle').innerText = `Edit ${sec?.title || 'Section Content'}`;
        document.getElementById('editModalHeaderSubtitle').innerText = 'Update headlines, taglines, description, and button links';

        document.getElementById('editMediaTitle').value = m.title || m.title_line1 || '';
        document.getElementById('editMediaSubtitle').value = m.subtitle || m.title_line2 || '';
        document.getElementById('editMediaDesc').value = m.description || '';
        document.getElementById('editMediaCtaText').value = m.cta_text || '';
        document.getElementById('editMediaCtaLink').value = m.cta_link || '';
        document.getElementById('editMediaTagText').value = m.badge || m.tag_text || '';
        document.getElementById('editMediaAlt').value = '';
        document.getElementById('editMediaDevice').value = 'all';
        document.getElementById('editMediaSort').value = 1;
        document.getElementById('editMediaActive').checked = m.is_active !== false;

        const modal = document.getElementById('editMetadataModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditMetadataModal() {
        const modal = document.getElementById('editMetadataModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleMetadataSubmit(e) {
        if (e) e.preventDefault();
        const mediaId = document.getElementById('editMediaId').value;
        const page = document.getElementById('editMediaPage').value;
        const section = document.getElementById('editMediaSection').value;
        const slot = document.getElementById('editMediaSlot').value;

        const payload = {
            id: mediaId,
            page: page,
            section: section,
            slot: slot,
            title: document.getElementById('editMediaTitle').value,
            subtitle: document.getElementById('editMediaSubtitle').value,
            description: document.getElementById('editMediaDesc').value,
            cta_text: document.getElementById('editMediaCtaText').value,
            cta_link: document.getElementById('editMediaCtaLink').value,
            tag_text: document.getElementById('editMediaTagText').value,
            alt_text: document.getElementById('editMediaAlt').value,
            device: document.getElementById('editMediaDevice').value,
            sort_order: document.getElementById('editMediaSort').value,
            is_active: document.getElementById('editMediaActive').checked ? 1 : 0,
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const endpoint = `${adminMediaBase}/update-metadata` + (mediaId ? `/${mediaId}` : '');
            const res = await axios.post(endpoint, payload, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Section options updated successfully!');
                broadcastMediaUpdate();
                closeEditMetadataModal();
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                toastr.error(res.data.message || 'Failed to update section options.');
            }
        } catch (err) {
            console.error('Update metadata error', err);
            toastr.error(err.response?.data?.message || 'Failed to update section options.');
        }
    }

    async function detachSlot(page, section, slotKey) {
        if (!confirm(`Are you sure you want to detach the image from slot "${slotKey}"? It will revert to the default fallback.`)) {
            return;
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await axios.post(`${adminMediaBase}/detach-slot`, {
                page: page,
                section: section,
                slot: slotKey
            }, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Slot detached successfully.');
                broadcastMediaUpdate();
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                toastr.error(res.data.message || 'Failed to detach slot.');
            }
        } catch (err) {
            console.error('Detach slot error', err);
            toastr.error(err.response?.data?.message || 'Failed to detach slot.');
        }
    }

    function openCategoryUploadModal(catId) {
        const adminBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin' : '/admin';
        window.location.href = `${adminBase}/categories/${catId}/edit`;
    }

    async function handleSlotUploadSubmit(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('slotUploadSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Uploading & Applying...';

        const form = document.getElementById('slotUploadForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/assign-slot`, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (res.data.success) {
                toastr.success(res.data.message || 'Slot updated successfully.');
                broadcastMediaUpdate();
                closeSlotUploadModal();
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                toastr.error(res.data.message || 'Failed to update slot.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save & Apply to Website';
            }
        } catch (err) {
            console.error('Slot upload error', err);
            toastr.error(err.response?.data?.message || 'Upload failed. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Save & Apply to Website';
        }
    }

    // ==========================================
    // HERO SLIDE MODAL & CRUD HANDLERS
    // ==========================================

    function openAddHeroSlideModal() {
        const form = document.getElementById('heroSlideForm');
        if (form) form.reset();
        document.getElementById('heroSlideId').value = '';
        document.getElementById('heroSlideDesktopMediaId').value = '';
        document.getElementById('heroSlideMobileMediaId').value = '';
        document.getElementById('heroSlideDesktopImagePath').value = '';
        document.getElementById('heroSlideMobileImagePath').value = '';

        document.getElementById('heroModalTitle').innerText = 'Add Hero Slide';
        document.getElementById('heroModalSubtitle').innerText = 'Configure homepage panoramic visual slide & call-to-actions';
        document.getElementById('heroSlideSubmitBtnText').innerText = 'Save Slide';

        clearHeroDesktopFileInput();
        clearHeroMobileFileInput();

        // Calculate next sort order
        let nextSort = 1;
        if (managerData && managerData.sections) {
            const heroSec = managerData.sections.find(s => s.id === 'hero');
            if (heroSec && heroSec.slots) {
                nextSort = heroSec.slots.length + 1;
            }
        }

        document.getElementById('heroSlideTitle').value = '';
        document.getElementById('heroSlideTagline').value = 'Good Things Are Handmade';
        document.getElementById('heroSlideDesc').value = '';
        document.getElementById('heroSlidePrimaryBtnText').value = 'Shop Now';
        document.getElementById('heroSlidePrimaryBtnLink').value = '/shop';
        document.getElementById('heroSlideSecondaryBtnText').value = 'Explore Collections';
        document.getElementById('heroSlideSecondaryBtnLink').value = '/shop';
        document.getElementById('heroSlideStatus').value = '1';
        document.getElementById('heroSlideSortOrder').value = nextSort;
        document.getElementById('heroSlideAlt').value = '';

        const modal = document.getElementById('heroSlideModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const titleInput = document.getElementById('heroSlideTitle');
                if (titleInput) titleInput.focus();
            }, 100);
        }
    }

    async function openEditHeroSlideModal(slideId, slotKey) {
        const form = document.getElementById('heroSlideForm');
        if (form) form.reset();

        // 1. First look up slot or media in memory for instantaneous modal opening
        let slideData = null;
        let foundSlot = null;

        if (managerData && managerData.sections) {
            const heroSec = managerData.sections.find(s => s.id === 'hero');
            if (heroSec && heroSec.slots) {
                foundSlot = heroSec.slots.find(s => 
                    (slideId && ((s.media && s.media.id == slideId) || s.media_id == slideId)) || 
                    (slotKey && s.slot === slotKey)
                );
                if (foundSlot) {
                    slideData = foundSlot.media || {};
                }
            }
        }

        const effectiveId = slideId || (slideData && slideData.id) || (foundSlot && foundSlot.media_id) || '';

        document.getElementById('heroSlideId').value = effectiveId;
        document.getElementById('heroSlideDesktopMediaId').value = '';
        document.getElementById('heroSlideMobileMediaId').value = '';
        document.getElementById('heroSlideDesktopImagePath').value = (slideData && (slideData.desktop_image_path || slideData.file_path)) || '';
        document.getElementById('heroSlideMobileImagePath').value = (slideData && slideData.mobile_image_path) || '';

        document.getElementById('heroModalTitle').innerText = effectiveId ? `Edit Hero Slide (#${effectiveId})` : 'Edit Hero Slide';
        document.getElementById('heroModalSubtitle').innerText = 'Update slide content, call-to-action buttons, images, and sequence';
        document.getElementById('heroSlideSubmitBtnText').innerText = 'Update Hero Slide';

        clearHeroDesktopFileInput();
        clearHeroMobileFileInput();

        // Populate fields immediately from in-memory data
        if (slideData || foundSlot) {
            document.getElementById('heroSlideTitle').value = (slideData && slideData.title) || (foundSlot && foundSlot.title) || '';
            document.getElementById('heroSlideTagline').value = (slideData && (slideData.tagline || slideData.tag_text)) || '';
            document.getElementById('heroSlideDesc').value = (slideData && (slideData.description || slideData.subtitle)) || (foundSlot && foundSlot.description) || '';
            document.getElementById('heroSlidePrimaryBtnText').value = (slideData && (slideData.primary_button_text || slideData.cta_text)) || 'Shop Now';
            document.getElementById('heroSlidePrimaryBtnLink').value = (slideData && (slideData.primary_button_link || slideData.cta_link)) || '/shop';
            document.getElementById('heroSlideSecondaryBtnText').value = (slideData && (slideData.secondary_button_text || slideData.secondary_cta_text)) || 'Explore Collections';
            document.getElementById('heroSlideSecondaryBtnLink').value = (slideData && (slideData.secondary_button_link || slideData.secondary_cta_link)) || '/shop';
            document.getElementById('heroSlideStatus').value = (slideData && slideData.is_active === false) ? '0' : '1';
            document.getElementById('heroSlideSortOrder').value = (slideData && slideData.sort_order) || (foundSlot && foundSlot.sort_order) || 1;
            document.getElementById('heroSlideAlt').value = (slideData && slideData.alt_text) || '';

            const desktopImg = (slideData && (slideData.desktop_image || slideData.url));
            if (desktopImg) {
                document.getElementById('heroDesktopPreviewImg').src = desktopImg;
                document.getElementById('heroDesktopFileName').innerText = desktopImg.split('/').pop() || 'Current Desktop Image';
                document.getElementById('heroDesktopFileSize').innerText = 'Existing Slide Image';
                document.getElementById('heroDesktopPreviewContainer').classList.remove('hidden');
            }

            const mobileImg = (slideData && slideData.mobile_image);
            if (mobileImg && mobileImg !== desktopImg) {
                document.getElementById('heroMobilePreviewImg').src = mobileImg;
                document.getElementById('heroMobileFileName').innerText = mobileImg.split('/').pop() || 'Current Mobile Image';
                document.getElementById('heroMobileFileSize').innerText = 'Existing Mobile Image';
                document.getElementById('heroMobilePreviewContainer').classList.remove('hidden');
            }
        }

        // Show modal immediately so the user sees it instantly
        const modal = document.getElementById('heroSlideModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                const titleInput = document.getElementById('heroSlideTitle');
                if (titleInput) titleInput.focus();
            }, 100);
        }

        // Sync with server in background if effectiveId exists
        if (effectiveId) {
            try {
                const adminMediaBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin/media' : '/admin/media';
                const res = await axios.get(`${adminMediaBase}/hero-slide/${effectiveId}`);
                if (res.data && res.data.success) {
                    const s = res.data.data;
                    // Only update if current modal is still for this slide
                    if (document.getElementById('heroSlideId').value == effectiveId) {
                        if (s.title !== undefined) document.getElementById('heroSlideTitle').value = s.title || '';
                        if (s.tagline !== undefined || s.tag_text !== undefined) document.getElementById('heroSlideTagline').value = s.tagline || s.tag_text || '';
                        if (s.description !== undefined || s.subtitle !== undefined) document.getElementById('heroSlideDesc').value = s.description || s.subtitle || '';
                        if (s.primary_button_text !== undefined || s.cta_text !== undefined) document.getElementById('heroSlidePrimaryBtnText').value = s.primary_button_text || s.cta_text || 'Shop Now';
                        if (s.primary_button_link !== undefined || s.cta_link !== undefined) document.getElementById('heroSlidePrimaryBtnLink').value = s.primary_button_link || s.cta_link || '/shop';
                        if (s.secondary_button_text !== undefined || s.secondary_cta_text !== undefined) document.getElementById('heroSlideSecondaryBtnText').value = s.secondary_button_text || s.secondary_cta_text || 'Explore Collections';
                        if (s.secondary_button_link !== undefined || s.secondary_cta_link !== undefined) document.getElementById('heroSlideSecondaryBtnLink').value = s.secondary_button_link || s.secondary_cta_link || '/shop';
                        if (s.is_active !== undefined) document.getElementById('heroSlideStatus').value = s.is_active ? '1' : '0';
                        if (s.sort_order !== undefined) document.getElementById('heroSlideSortOrder').value = s.sort_order || 1;
                        if (s.alt_text !== undefined) document.getElementById('heroSlideAlt').value = s.alt_text || '';
                        
                        if (s.desktop_image_path) document.getElementById('heroSlideDesktopImagePath').value = s.desktop_image_path;
                        if (s.mobile_image_path) document.getElementById('heroSlideMobileImagePath').value = s.mobile_image_path;

                        if (s.desktop_image) {
                            document.getElementById('heroDesktopPreviewImg').src = s.desktop_image;
                            document.getElementById('heroDesktopFileName').innerText = s.desktop_image.split('/').pop() || 'Current Desktop Image';
                            document.getElementById('heroDesktopFileSize').innerText = 'Existing Slide Image';
                            document.getElementById('heroDesktopPreviewContainer').classList.remove('hidden');
                        }

                        if (s.mobile_image && s.mobile_image !== s.desktop_image) {
                            document.getElementById('heroMobilePreviewImg').src = s.mobile_image;
                            document.getElementById('heroMobileFileName').innerText = s.mobile_image.split('/').pop() || 'Current Mobile Image';
                            document.getElementById('heroMobileFileSize').innerText = 'Existing Mobile Image';
                            document.getElementById('heroMobilePreviewContainer').classList.remove('hidden');
                        }
                    }
                }
            } catch (err) {
                console.warn('Hero slide background sync error', err);
            }
        }
    }

    function closeHeroSlideModal() {
        const modal = document.getElementById('heroSlideModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleHeroDesktopFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('heroDesktopPreviewImg').src = e.target.result;
                document.getElementById('heroDesktopFileName').innerText = file.name;
                document.getElementById('heroDesktopFileSize').innerText = (file.size / 1024).toFixed(1) + ' KB (Ready to upload)';
                document.getElementById('heroDesktopPreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearHeroDesktopFileInput() {
        const fileInput = document.getElementById('heroSlideDesktopFileInput');
        if (fileInput) fileInput.value = '';
        document.getElementById('heroSlideDesktopMediaId').value = '';
        document.getElementById('heroSlideDesktopImagePath').value = '';
        const preview = document.getElementById('heroDesktopPreviewContainer');
        if (preview) preview.classList.add('hidden');
    }

    function handleHeroMobileFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('heroMobilePreviewImg').src = e.target.result;
                document.getElementById('heroMobileFileName').innerText = file.name;
                document.getElementById('heroMobileFileSize').innerText = (file.size / 1024).toFixed(1) + ' KB (Ready to upload)';
                document.getElementById('heroMobilePreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearHeroMobileFileInput() {
        const fileInput = document.getElementById('heroSlideMobileFileInput');
        if (fileInput) fileInput.value = '';
        document.getElementById('heroSlideMobileMediaId').value = '';
        document.getElementById('heroSlideMobileImagePath').value = '';
        const preview = document.getElementById('heroMobilePreviewContainer');
        if (preview) preview.classList.add('hidden');
    }

    async function handleHeroSlideSubmit(e) {
        if (e) e.preventDefault();

        const slideId = document.getElementById('heroSlideId').value;
        const isEdit = Boolean(slideId && parseInt(slideId) > 0);

        const titleInput = document.getElementById('heroSlideTitle');
        if (!titleInput.value.trim()) {
            toastr.warning('Please enter a Slide Title / Headline.');
            titleInput.focus();
            return;
        }

        const desktopFileInput = document.getElementById('heroSlideDesktopFileInput');
        const desktopMediaId = document.getElementById('heroSlideDesktopMediaId').value;
        const desktopImagePath = document.getElementById('heroSlideDesktopImagePath').value;

        if (!isEdit && (!desktopFileInput.files || desktopFileInput.files.length === 0) && !desktopMediaId && !desktopImagePath) {
            toastr.warning('Please upload or select a Desktop Image for the new hero slide.');
            return;
        }

        const submitBtn = document.getElementById('heroSlideSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Hero Slide...';

        const form = document.getElementById('heroSlideForm');
        const formData = new FormData(form);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        const adminMediaBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin/media' : '/admin/media';
        const endpoint = isEdit ? `${adminMediaBase}/hero-slide/${slideId}` : `${adminMediaBase}/hero-slide`;

        try {
            const res = await axios.post(endpoint, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });

            if (res.data.success) {
                toastr.success(res.data.message || (isEdit ? 'Hero slide updated successfully!' : 'Hero slide created successfully!'));
                broadcastMediaUpdate();
                closeHeroSlideModal();
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                toastr.error(res.data.message || 'Failed to save hero slide.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save mr-1.5"></i><span>' + (isEdit ? 'Update Hero Slide' : 'Save Slide') + '</span>';
            }
        } catch (err) {
            console.error('Hero slide save error', err);
            const errMsg = err.response?.data?.message || 
                (err.response?.data?.errors ? Object.values(err.response.data.errors).flat().join('<br>') : 'Failed to save hero slide. Please check inputs and file format.');
            toastr.error(errMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save mr-1.5"></i><span>' + (isEdit ? 'Update Hero Slide' : 'Save Slide') + '</span>';
        }
    }

    async function deleteHeroSlide(id) {
        if (!confirm('Are you sure you want to delete this Hero Slide? This will remove it from the storefront carousel.')) {
            return;
        }

        try {
            const adminMediaBase = window.location.pathname.startsWith('/knottele') ? '/knottele/admin/media' : '/admin/media';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.delete(`${adminMediaBase}/hero-slide/${id}`, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Hero slide deleted successfully.');
                broadcastMediaUpdate();
                setTimeout(() => {
                    window.location.reload();
                }, 400);
            } else {
                toastr.error(res.data.message || 'Failed to delete hero slide.');
            }
        } catch (err) {
            console.error('Delete hero slide error', err);
            toastr.error(err.response?.data?.message || 'Failed to delete hero slide.');
        }
    }

    // ==========================================
    // ==========================================
    // CUSTOMER REVIEWS (TESTIMONIALS) HANDLERS
    // ==========================================
    function openAddTestimonialModal() {
        const form = document.getElementById('testimonialForm');
        if (form) form.reset();
        document.getElementById('testimonialId').value = '';
        document.getElementById('testimonialModalTitle').innerText = 'Add Customer Review';
        document.getElementById('testimonialSubmitBtnText').innerText = 'Save Review';
        document.getElementById('testimonialName').value = '';
        document.getElementById('testimonialDesignation').value = '';
        document.getElementById('testimonialRating').value = '5';
        document.getElementById('testimonialMessage').value = '';
        document.getElementById('testimonialActive').checked = true;
        
        const modal = document.getElementById('testimonialModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    async function openEditTestimonialModal(id) {
        const form = document.getElementById('testimonialForm');
        if (form) form.reset();
        document.getElementById('testimonialId').value = id;
        document.getElementById('testimonialModalTitle').innerText = `Edit Customer Review (#${id})`;
        document.getElementById('testimonialSubmitBtnText').innerText = 'Update Review';

        const modal = document.getElementById('testimonialModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await axios.get(`${adminMediaBase}/testimonial/${id}`);
            if (res.data && res.data.success) {
                const t = res.data.data;
                document.getElementById('testimonialName').value = t.name || '';
                document.getElementById('testimonialDesignation').value = t.designation || '';
                document.getElementById('testimonialRating').value = t.rating || 5;
                document.getElementById('testimonialMessage').value = t.message || '';
                document.getElementById('testimonialActive').checked = Boolean(t.is_active);
            }
        } catch (err) {
            console.error('Failed to load testimonial', err);
            toastr.error('Failed to load review details.');
        }
    }

    function closeTestimonialModal() {
        const modal = document.getElementById('testimonialModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleTestimonialSubmit(e) {
        if (e) e.preventDefault();
        
        const name = document.getElementById('testimonialName').value.trim();
        const message = document.getElementById('testimonialMessage').value.trim();
        
        if (!name) {
            toastr.error('Please enter customer name.');
            return;
        }
        if (!message) {
            toastr.error('Please enter review message.');
            return;
        }

        const submitBtn = document.getElementById('testimonialSubmitBtn');
        const submitBtnText = document.getElementById('testimonialSubmitBtnText');
        const originalText = submitBtnText ? submitBtnText.innerText : 'Save Review';

        submitBtn.disabled = true;
        if (submitBtnText) submitBtnText.innerText = 'Saving...';

        const form = document.getElementById('testimonialForm');
        const formData = new FormData(form);
        formData.set('is_active', document.getElementById('testimonialActive').checked ? '1' : '0');

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        try {
            const res = await axios.post(`${adminMediaBase}/testimonial`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Review saved successfully!');
                broadcastMediaUpdate();
                closeTestimonialModal();
                loadManagerData();
            } else {
                toastr.error(res.data.message || 'Failed to save review.');
            }
        } catch (err) {
            console.error('Testimonial save error', err);
            toastr.error(err.response?.data?.message || 'Failed to save review.');
        } finally {
            submitBtn.disabled = false;
            if (submitBtnText) submitBtnText.innerText = originalText;
        }
    }

    async function deleteTestimonial(id) {
        if (!confirm('Are you sure you want to delete this customer review?')) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
            const res = await axios.delete(`${adminMediaBase}/testimonial/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Review deleted successfully.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data.message || 'Failed to delete review.');
            }
        } catch (err) {
            console.error('Delete review error', err);
            toastr.error(err.response?.data?.message || 'Failed to delete review.');
        }
    }

    // ==========================================
    // MEDIA LIBRARY PICKER MODAL
    // ==========================================
    let pickerTarget = 'desktop';
    let pickerMediaCache = [];

    async function openMediaPicker(target) {
        pickerTarget = target;
        const modal = document.getElementById('mediaPickerModal');
        const grid = document.getElementById('mediaPickerGrid');
        grid.innerHTML = '<div class="col-span-full py-8 text-center text-stone-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading media library items...</div>';
        
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            const res = await axios.get(`${adminMediaBase}/data?per_page=48`);
            pickerMediaCache = res.data.data || [];
            renderMediaPickerGrid(pickerMediaCache);
        } catch (err) {
            console.error('Picker load error', err);
            grid.innerHTML = '<div class="col-span-full py-8 text-center text-rose-500 font-bold">Failed to load media files.</div>';
        }
    }

    function closeMediaPicker() {
        const modal = document.getElementById('mediaPickerModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function renderMediaPickerGrid(items) {
        const grid = document.getElementById('mediaPickerGrid');
        if (!items || items.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-8 text-center text-stone-400">No media files found.</div>';
            return;
        }

        grid.innerHTML = items.map(item => `
            <div onclick="selectMediaFromPicker(${item.id}, '${item.url}', '${escapeHtml(item.name || item.file_name)}')"
                 class="bg-stone-50 hover:bg-red-50/40 border border-stone-200 hover:border-red-400 rounded-2xl p-2.5 flex flex-col items-center cursor-pointer transition-all duration-200 group">
                <div class="relative w-full aspect-square rounded-xl overflow-hidden bg-white border border-stone-100 mb-2">
                    <img src="${item.url}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover group-hover:scale-105 transition-transform" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                </div>
                <p class="text-[11px] font-bold text-stone-700 truncate w-full text-center" title="${escapeHtml(item.name || item.file_name)}">${escapeHtml(item.name || item.file_name)}</p>
                <span class="text-[10px] text-stone-400">${item.size_formatted || ''}</span>
            </div>
        `).join('');
    }

    function filterMediaPicker(query) {
        if (!pickerMediaCache) return;
        const q = (query || '').toLowerCase().trim();
        if (!q) {
            renderMediaPickerGrid(pickerMediaCache);
            return;
        }
        const filtered = pickerMediaCache.filter(m => 
            (m.name && m.name.toLowerCase().includes(q)) ||
            (m.file_name && m.file_name.toLowerCase().includes(q)) ||
            (m.slot && m.slot.toLowerCase().includes(q))
        );
        renderMediaPickerGrid(filtered);
    }

    function selectMediaFromPicker(id, url, name) {
        if (pickerTarget === 'desktop') {
            document.getElementById('heroSlideDesktopMediaId').value = id;
            document.getElementById('heroSlideDesktopImagePath').value = url;
            document.getElementById('heroDesktopPreviewImg').src = url;
            document.getElementById('heroDesktopFileName').innerText = name || 'Media Library Image';
            document.getElementById('heroDesktopFileSize').innerText = 'Selected from Media Library';
            document.getElementById('heroDesktopPreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'video_thumbnail') {
            document.getElementById('videoReelThumbnailMediaId').value = id;
            document.getElementById('videoReelThumbnailUrl').value = url;
            document.getElementById('videoReelThumbnailPreviewImg').src = url;
            document.getElementById('videoReelThumbnailName').innerText = name || 'Media Library Image';
            document.getElementById('videoReelThumbnailSize').innerText = 'Selected from Media Library';
            document.getElementById('videoReelThumbnailPreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'about_desktop') {
            document.getElementById('aboutStoryDesktopUrl').value = url;
            document.getElementById('aboutDesktopPreviewImg').src = url;
            document.getElementById('aboutDesktopFileName').innerText = name || 'Media Library Image';
            document.getElementById('aboutDesktopFileSize').innerText = 'Selected from Media Library';
            document.getElementById('aboutDesktopPreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'about_mobile') {
            document.getElementById('aboutStoryMobileUrl').value = url;
            document.getElementById('aboutMobilePreviewImg').src = url;
            document.getElementById('aboutMobileFileName').innerText = name || 'Media Library Image';
            document.getElementById('aboutMobileFileSize').innerText = 'Selected from Media Library';
            document.getElementById('aboutMobilePreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'contact_intro') {
            document.getElementById('contactIntroImageUrl').value = url;
            document.getElementById('contactIntroPreviewImg').src = url;
            document.getElementById('contactIntroFileName').innerText = name || 'Media Library Image';
            document.getElementById('contactIntroFileSize').innerText = 'Selected from Media Library';
            document.getElementById('contactIntroPreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'custom_crochet') {
            document.getElementById('customCrochetImageUrl').value = url;
            document.getElementById('customCrochetPreviewImg').src = url;
            document.getElementById('customCrochetFileName').innerText = name || 'Media Library Image';
            document.getElementById('customCrochetPreviewContainer').classList.remove('hidden');
        } else if (pickerTarget === 'brand_story') {
            const input = document.getElementById('brandStoryImageUrl');
            if (input) input.value = url;
            const previewImg = document.getElementById('brandStoryPreviewImg');
            if (previewImg) previewImg.src = url;
            const fileName = document.getElementById('brandStoryFileName');
            if (fileName) fileName.innerText = name || 'Media Library Image';
            const previewContainer = document.getElementById('brandStoryPreviewContainer');
            if (previewContainer) {
                previewContainer.classList.remove('hidden');
                previewContainer.style.display = 'flex';
            }
            const modal = document.getElementById('brandStoryModal');
            if (modal && (modal.style.display === 'none' || modal.classList.contains('hidden'))) {
                openBrandStoryModal();
            }
        } else if (pickerTarget === 'footer_bg') {
            const input = document.getElementById('footerBgImageUrl');
            if (input) input.value = url;
            const fileInput = document.getElementById('footerBgFileInput');
            if (fileInput) fileInput.value = '';
            const previewImg = document.getElementById('footerBgPreviewImg');
            if (previewImg) previewImg.src = url;
            const fileName = document.getElementById('footerBgFileName');
            if (fileName) fileName.innerText = name || url.split('/').pop() || 'Media Library Image';
            const statusLabel = document.getElementById('footerBgStatusLabel');
            if (statusLabel) statusLabel.innerText = 'Selected from Media Library';
            const previewContainer = document.getElementById('footerBgPreviewContainer');
            if (previewContainer) {
                previewContainer.classList.remove('hidden');
                previewContainer.style.display = 'flex';
            }
            const modal = document.getElementById('footerSettingsModal');
            if (modal && (modal.style.display === 'none' || modal.classList.contains('hidden'))) {
                openFooterSettingsModal();
            }
        } else {
            document.getElementById('heroSlideMobileMediaId').value = id;
            document.getElementById('heroSlideMobileImagePath').value = url;
            document.getElementById('heroMobilePreviewImg').src = url;
            document.getElementById('heroMobileFileName').innerText = name || 'Media Library Image';
            document.getElementById('heroMobileFileSize').innerText = 'Selected from Media Library';
            document.getElementById('heroMobilePreviewContainer').classList.remove('hidden');
        }
        closeMediaPicker();
        toastr.info(`Selected "${name}" for ${pickerTarget} image.`);
    }

    // ==========================================
    // BLOG / VIDEOS & REELS MANAGEMENT
    // ==========================================
    function renderVideoReelCard(reel) {
        const thumbUrl = reel.thumbnail_url || reel.url || '/images/logo/Logo_1.png';
        const isActive = reel.is_active !== false && reel.is_active !== 0;
        const isFeatured = !!reel.is_featured;

        return `
            <div class="bg-white border border-stone-200/90 rounded-2xl p-4 shadow-2xs hover:shadow-boutique transition-all flex flex-col justify-between group">
                <div>
                    <!-- Header Badges -->
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-red-50 text-red-700 border border-red-200 uppercase tracking-wider">
                                ${escapeHtml(reel.category_name || 'Reel')}
                            </span>
                            <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase bg-stone-100 text-stone-600">
                                ${reel.content_type || 'reel'}
                            </span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${isActive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-stone-100 text-stone-500 border border-stone-200'}">
                            ${isActive ? '● Active' : '○ Inactive'}
                        </span>
                    </div>

                    <!-- Thumbnail with Play Overlay -->
                    <div class="relative w-full aspect-[4/5] rounded-xl overflow-hidden bg-stone-900 border border-stone-200/70 mb-3 group/thumb flex items-center justify-center cursor-pointer"
                         title="Click to watch in auto-replay loop"
                         onclick="openVideoWatchModal('${escapeHtml(reel.video_url || '')}', '${escapeHtml(reel.title)}', '${escapeHtml(reel.category_name || 'Reel')}', ${reel.id})">
                        <img src="${thumbUrl}" alt="${escapeHtml(reel.title)}" class="w-full h-full object-cover group-hover/thumb:scale-105 transition-transform duration-300" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">
                        
                        <div class="absolute inset-0 bg-stone-900/30 group-hover/thumb:bg-stone-900/50 transition-colors flex items-center justify-center">
                            <div class="w-11 h-11 rounded-full bg-white/90 group-hover/thumb:bg-white text-red-600 flex items-center justify-center shadow-lg transition-transform group-hover/thumb:scale-110">
                                <i class="fas fa-play text-sm ml-0.5"></i>
                            </div>
                        </div>

                        ${reel.duration ? `
                            <div class="absolute bottom-2 right-2 bg-stone-900/80 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1">
                                <i class="far fa-clock text-[9px]"></i>
                                <span>${escapeHtml(reel.duration)}</span>
                            </div>
                        ` : ''}

                        ${isFeatured ? `
                            <div class="absolute top-2 left-2 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1 shadow-xs">
                                <i class="fas fa-star text-[8px]"></i>
                                <span>Featured</span>
                            </div>
                        ` : ''}
                    </div>

                    <!-- Title & Tagline -->
                    <div class="mb-2">
                        <h4 class="font-bold text-stone-800 text-sm mb-0.5 line-clamp-1" title="${escapeHtml(reel.title)}">${escapeHtml(reel.title)}</h4>
                        ${reel.tagline ? `<p class="text-[11px] font-semibold text-red-600 italic line-clamp-1">${escapeHtml(reel.tagline)}</p>` : ''}
                    </div>

                    <!-- Description -->
                    <p class="text-[11px] text-stone-500 mb-2.5 leading-relaxed line-clamp-2">${escapeHtml(reel.description || 'No description provided.')}</p>

                    <!-- Social Stats & Audio Track -->
                    <div class="bg-stone-50 rounded-xl p-2 border border-stone-100 space-y-1 mb-3 text-[10px] font-semibold text-stone-600">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1 text-stone-500"><i class="fas fa-eye text-red-500"></i> Views:</span>
                            <span class="font-bold text-stone-800">${(reel.views_count || 0).toLocaleString()}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="flex items-center gap-1 text-stone-500"><i class="fas fa-heart text-red-500"></i> Likes:</span>
                            <span class="font-bold text-stone-800">${(reel.likes_count || 0).toLocaleString()}</span>
                        </div>
                        ${reel.audio_name ? `
                            <div class="flex items-center gap-1 text-stone-600 truncate pt-0.5 border-t border-stone-200/60">
                                <i class="fas fa-music text-red-500 text-[9px] shrink-0"></i>
                                <span class="truncate" title="${escapeHtml(reel.audio_name)}">${escapeHtml(reel.audio_name)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Actions Toolbar -->
                <div class="pt-3 border-t border-stone-100 flex items-center justify-between gap-1.5">
                    <button type="button" onclick="openEditVideoReelModal(${reel.id})"
                            class="flex-1 py-1.5 px-2.5 rounded-xl bg-red-50 hover:bg-red-600 text-red-700 hover:text-white text-xs font-bold transition-all flex items-center justify-center gap-1 cursor-pointer">
                        <i class="fas fa-edit text-xs"></i>
                        <span>Edit</span>
                    </button>
                    <button type="button" onclick="toggleVideoReelStatus(${reel.id})"
                            class="py-1.5 px-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-stone-600 text-xs font-bold transition-all cursor-pointer" title="Toggle Active Status">
                        <i class="fas ${isActive ? 'fa-eye-slash' : 'fa-eye'} text-xs"></i>
                    </button>
                    <button type="button" onclick="deleteVideoReel(${reel.id})"
                            class="py-1.5 px-2 rounded-xl bg-stone-50 hover:bg-rose-50 text-stone-400 hover:text-rose-600 text-xs font-bold transition-all cursor-pointer" title="Delete Video / Reel">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
            </div>
        `;
    }

    function setReelCategory(catName) {
        document.getElementById('videoReelCategory').value = catName;
    }

    function openAddVideoReelModal() {
        const form = document.getElementById('videoReelForm');
        if (form) form.reset();

        document.getElementById('videoReelId').value = '';
        document.getElementById('videoReelThumbnailMediaId').value = '';
        document.getElementById('videoReelThumbnailUrl').value = '';
        document.getElementById('videoReelModalTitle').innerText = 'Add Video / Reel';
        document.getElementById('videoReelModalSubtitle').innerText = 'Manage website video reels, studio journals, tutorials, and social content';
        document.getElementById('videoReelSubmitBtnText').innerText = 'Save Video / Reel';
        
        document.getElementById('videoReelContentType').value = 'reel';
        document.getElementById('videoReelCategory').value = 'Studio ASMR';
        document.getElementById('videoReelDuration').value = '00:48';
        document.getElementById('videoReelAudioName').value = 'Original Audio • Knotelle Studio';
        document.getElementById('videoReelLikes').value = '120';
        document.getElementById('videoReelComments').value = '18';
        document.getElementById('videoReelViews').value = '1500';
        document.getElementById('videoReelStatus').value = '1';
        document.getElementById('videoReelFeatured').value = '0';
        document.getElementById('videoReelSortOrder').value = '1';

        clearReelCoverInput();
        clearVideoPreviewPlayer();

        const scrollContainer = document.getElementById('videoReelScrollContainer');
        if (scrollContainer) {
            scrollContainer.scrollTop = 0;
        }

        const modal = document.getElementById('videoReelModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        setTimeout(() => {
            if (scrollContainer) scrollContainer.scrollTop = 0;
            const titleInput = document.getElementById('videoReelTitle');
            if (titleInput) {
                titleInput.focus({ preventScroll: true });
            }
        }, 60);
    }

    async function openEditVideoReelModal(id) {
        try {
            const res = await axios.get(`${adminMediaBase}/video-reel/${id}`);
            if (!res.data.success || (!res.data.reel && !res.data.data)) {
                toastr.error('Video reel not found');
                return;
            }

            const reel = res.data.reel || res.data.data;
            const form = document.getElementById('videoReelForm');
            if (form) form.reset();

            document.getElementById('videoReelId').value = reel.id;
            document.getElementById('videoReelThumbnailMediaId').value = '';
            document.getElementById('videoReelThumbnailUrl').value = reel.thumbnail_url || reel.thumbnail || '';

            document.getElementById('videoReelModalTitle').innerText = `Edit: ${reel.title}`;
            document.getElementById('videoReelModalSubtitle').innerText = `ID #${reel.id} — ${reel.category_name || reel.category || 'Reel'}`;
            document.getElementById('videoReelSubmitBtnText').innerText = 'Update Video / Reel';

            document.getElementById('videoReelContentType').value = reel.content_type || 'reel';
            document.getElementById('videoReelCategory').value = reel.category_name || reel.category || '';
            document.getElementById('videoReelTitle').value = reel.title || '';
            document.getElementById('videoReelTagline').value = reel.tagline || reel.subtitle || '';
            document.getElementById('videoReelDesc').value = reel.description || '';
            document.getElementById('videoReelUrlInput').value = reel.video_url || '';
            document.getElementById('videoReelDuration').value = reel.duration || '00:48';
            document.getElementById('videoReelAudioName').value = reel.audio_name || 'Original Audio';
            document.getElementById('videoReelLikes').value = reel.likes_count ?? (reel.likes ?? 0);
            document.getElementById('videoReelComments').value = reel.comments_count ?? (reel.comments ?? 0);
            document.getElementById('videoReelViews').value = reel.views_count ?? (reel.views ?? 0);
            document.getElementById('videoReelStatus').value = reel.is_active ? '1' : '0';
            document.getElementById('videoReelFeatured').value = reel.is_featured ? '1' : '0';
            document.getElementById('videoReelSortOrder').value = reel.sort_order || 1;

            const thumbUrl = reel.thumbnail_url || reel.thumbnail || reel.url;
            if (thumbUrl) {
                document.getElementById('videoReelThumbnailPreviewImg').src = thumbUrl;
                document.getElementById('videoReelThumbnailName').innerText = reel.title || 'Current Cover Image';
                document.getElementById('videoReelThumbnailSize').innerText = 'Current thumbnail';
                document.getElementById('videoReelThumbnailPreviewContainer').classList.remove('hidden');
            } else {
                clearReelCoverInput();
            }

            const videoSrc = reel.video_url || reel.video_stream_url || (reel.file_path ? `/storage/${reel.file_path}` : '');
            if (videoSrc) {
                loadVideoIntoPreviewPlayer(videoSrc);
            } else {
                clearVideoPreviewPlayer();
            }

            const scrollContainer = document.getElementById('videoReelScrollContainer');
            if (scrollContainer) {
                scrollContainer.scrollTop = 0;
            }

            const modal = document.getElementById('videoReelModal');
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            setTimeout(() => {
                if (scrollContainer) scrollContainer.scrollTop = 0;
                const titleInput = document.getElementById('videoReelTitle');
                if (titleInput) {
                    titleInput.focus({ preventScroll: true });
                }
            }, 60);
        } catch (err) {
            console.error('Failed to load video reel for editing', err);
            toastr.error('Failed to load video reel details.');
        }
    }

    function closeVideoReelModal() {
        clearVideoPreviewPlayer();
        const modal = document.getElementById('videoReelModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    // Video Watch & Auto-Replay Modal
    function openVideoWatchModal(videoSrc, title, category, id) {
        if (!videoSrc) {
            toastr.info('No video file or URL is attached to this reel. Click Edit to attach one.');
            return;
        }

        let cleanSrc = videoSrc.trim();
        if (!cleanSrc.startsWith('http://') && !cleanSrc.startsWith('https://') && !cleanSrc.startsWith('blob:') && !cleanSrc.startsWith('data:')) {
            cleanSrc = cleanSrc.startsWith('/') ? cleanSrc : '/' + cleanSrc;
            if (window.location.pathname.startsWith('/knottele') && !cleanSrc.startsWith('/knottele')) {
                cleanSrc = '/knottele' + cleanSrc;
            }
        }

        const modal = document.getElementById('videoWatchModal');
        const player = document.getElementById('videoWatchPlayer');
        const titleEl = document.getElementById('videoWatchTitle');
        const catEl = document.getElementById('videoWatchCategory');
        const editBtn = document.getElementById('videoWatchEditBtn');

        if (titleEl) titleEl.innerText = title || 'Video / Reel';
        if (catEl) catEl.innerText = category || 'STUDIO REEL';
        if (editBtn) {
            editBtn.onclick = function() {
                closeVideoWatchModal();
                if (id) openEditVideoReelModal(id);
            };
        }

        if (player) {
            player.loop = true;
            player.autoplay = true;
            player.src = cleanSrc;
            player.load();
            player.play().catch(e => console.log('Reel player autoplay notice:', e));
        }

        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeVideoWatchModal() {
        const modal = document.getElementById('videoWatchModal');
        const player = document.getElementById('videoWatchPlayer');
        if (player) {
            player.pause();
            player.removeAttribute('src');
            player.load();
        }
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleVideoPlayerCanPlay() {
        const player = document.getElementById('videoReelPreviewPlayer');
        if (player) {
            player.loop = true;
            player.play().catch(e => console.log('Preview loop play notice:', e));
        }
        const statusBadge = document.getElementById('videoPlayerStatusBadge');
        if (statusBadge) {
            statusBadge.innerText = '✓ Video loaded & ready to play (Auto-loops)';
            statusBadge.className = 'text-emerald-400 font-semibold';
        }
    }

    function handleVideoPlayerError() {
        const player = document.getElementById('videoReelPreviewPlayer');
        const statusBadge = document.getElementById('videoPlayerStatusBadge');
        if (player && player.src) {
            console.warn('Video preview stream notice:', player.src);
            if (statusBadge) {
                statusBadge.innerText = 'Video link/file attached (Ready for saving)';
                statusBadge.className = 'text-amber-400 font-medium';
            }
        }
    }

    function handleVideoFileInputChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            if (file.size > 150 * 1024 * 1024) {
                toastr.warning(`Selected video is ${sizeMb} MB. Maximum recommended file size is 150 MB.`);
            }
            document.getElementById('videoReelFileLabel').innerText = `Selected: ${file.name} (${sizeMb} MB)`;
            
            // Auto-populate Title if empty so the user is never blocked
            const titleInput = document.getElementById('videoReelTitle');
            if (titleInput && !titleInput.value.trim()) {
                let clean = file.name.replace(/\.[^/.]+$/, "").replace(/\d{4}-\d{2}-\d{2}.*$/, "").replace(/[-_]+/g, " ").trim();
                titleInput.value = clean.length > 2 ? clean : 'Studio Video Reel';
            }

            const fileUrl = URL.createObjectURL(file);
            loadVideoIntoPreviewPlayer(fileUrl);
        }
    }

    function handleVideoUrlInputChange(val) {
        if (val && val.trim().length > 3) {
            loadVideoIntoPreviewPlayer(val.trim());
        } else {
            const fileInput = document.getElementById('videoReelFileInput');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                clearVideoPreviewPlayer();
            }
        }
    }

    function loadVideoIntoPreviewPlayer(src) {
        if (!src) {
            clearVideoPreviewPlayer();
            return;
        }

        const player = document.getElementById('videoReelPreviewPlayer');
        const emptyState = document.getElementById('videoPlayerEmptyState');
        const statusBadge = document.getElementById('videoPlayerStatusBadge');
        
        let cleanSrc = src.trim();
        if (!cleanSrc.startsWith('http://') && !cleanSrc.startsWith('https://') && !cleanSrc.startsWith('blob:') && !cleanSrc.startsWith('data:')) {
            cleanSrc = cleanSrc.startsWith('/') ? cleanSrc : '/' + cleanSrc;
            if (window.location.pathname.startsWith('/knottele') && !cleanSrc.startsWith('/knottele')) {
                cleanSrc = '/knottele' + cleanSrc;
            }
        }

        if (player && emptyState) {
            player.loop = true;
            player.autoplay = true;
            player.muted = true;
            player.src = cleanSrc;
            player.load();
            player.play().catch(e => console.log('Preview player autoplay notice:', e));
            emptyState.classList.add('hidden');
            if (statusBadge) {
                statusBadge.innerText = 'Loading preview player (Auto-replay loop)...';
                statusBadge.className = 'text-amber-300 font-normal';
            }
        }
    }

    function clearVideoPreviewPlayer() {
        const player = document.getElementById('videoReelPreviewPlayer');
        const emptyState = document.getElementById('videoPlayerEmptyState');
        const statusBadge = document.getElementById('videoPlayerStatusBadge');
        const fileLabel = document.getElementById('videoReelFileLabel');
        const fileInput = document.getElementById('videoReelFileInput');
        
        if (player) {
            player.pause();
            player.removeAttribute('src');
            player.load();
        }
        if (emptyState) emptyState.classList.remove('hidden');
        if (statusBadge) {
            statusBadge.innerText = 'No video selected';
            statusBadge.className = 'text-stone-400 font-normal';
        }
        if (fileLabel) fileLabel.innerText = 'Click or drop MP4 / WEBM / MOV';
        if (fileInput) fileInput.value = '';
    }

    function handleReelCoverInputChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('videoReelThumbnailPreviewImg').src = e.target.result;
                document.getElementById('videoReelThumbnailName').innerText = file.name;
                document.getElementById('videoReelThumbnailSize').innerText = (file.size / 1024).toFixed(1) + ' KB';
                document.getElementById('videoReelThumbnailPreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearReelCoverInput() {
        document.getElementById('videoReelThumbnailInput').value = '';
        document.getElementById('videoReelThumbnailMediaId').value = '';
        document.getElementById('videoReelThumbnailUrl').value = '';
        document.getElementById('videoReelThumbnailPreviewContainer').classList.add('hidden');
    }

    async function handleVideoReelSubmit(event) {
        if (event) event.preventDefault();
        
        const form = document.getElementById('videoReelForm');
        const id = document.getElementById('videoReelId').value;
        const titleInput = document.getElementById('videoReelTitle');
        const categoryInput = document.getElementById('videoReelCategory');
        
        // Auto-resolve title if empty so save is never blocked
        let title = titleInput.value.trim();
        if (!title) {
            const fileInput = document.getElementById('videoReelFileInput');
            if (fileInput && fileInput.files && fileInput.files[0]) {
                let clean = fileInput.files[0].name.replace(/\.[^/.]+$/, "").replace(/\d{4}-\d{2}-\d{2}.*$/, "").replace(/[-_]+/g, " ").trim();
                title = clean.length > 2 ? clean : 'Handcrafted Video Reel';
                titleInput.value = title;
            } else if (id) {
                title = 'Studio Reel #' + id;
                titleInput.value = title;
            } else {
                title = 'Handcrafted Video Story';
                titleInput.value = title;
            }
        }

        // Auto-resolve category if empty
        let category = categoryInput.value.trim();
        if (!category) {
            category = 'Studio ASMR';
            categoryInput.value = category;
        }

        const formData = new FormData(form);
        const submitBtn = document.getElementById('videoReelSubmitBtn');
        const submitBtnText = document.getElementById('videoReelSubmitBtnText');
        const originalText = submitBtnText.innerText;

        try {
            submitBtn.disabled = true;
            submitBtnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';

            let url = `${adminMediaBase}/video-reel/add`;
            if (id) {
                url = `${adminMediaBase}/video-reel/update/${id}`;
            }

            const res = await axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            if (res.data.success) {
                toastr.success(res.data.message || 'Video / reel saved successfully.');
                closeVideoReelModal();
                broadcastMediaUpdate();
                loadManagerData(true, 'sec-card-blog_reels');
            } else {
                toastr.error(res.data.message || 'Failed to save video / reel.');
            }
        } catch (err) {
            console.error('Video reel save error', err);
            let errMsg = 'Error saving video / reel.';
            if (err.response?.data?.errors) {
                const errList = Object.values(err.response.data.errors).flat();
                errMsg = errList.join(' • ');
            } else if (err.response?.data?.message) {
                errMsg = err.response.data.message;
            }
            toastr.error(errMsg);
        } finally {
            submitBtn.disabled = false;
            submitBtnText.innerText = originalText;
        }
    }

    async function deleteVideoReel(id) {
        if (!confirm('Are you sure you want to delete this Video / Reel? This action cannot be undone.')) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.delete(`${adminMediaBase}/video-reel/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Video / reel deleted successfully.');
                broadcastMediaUpdate();
                loadManagerData(true, 'sec-card-blog_reels');
            } else {
                toastr.error(res.data.message || 'Failed to delete video / reel.');
            }
        } catch (err) {
            console.error('Failed to delete video reel', err);
            toastr.error(err.response?.data?.message || 'Failed to delete video reel.');
        }
    }

    async function toggleVideoReelStatus(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post(`${adminMediaBase}/video-reel/toggle-status/${id}`, {
                _token: csrfToken || ''
            }, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Status updated.');
                broadcastMediaUpdate();
                loadManagerData(true, 'sec-card-blog_reels');
            } else {
                toastr.error(res.data.message || 'Failed to toggle status.');
            }
        } catch (err) {
            console.error('Toggle status error', err);
            toastr.error(err.response?.data?.message || 'Failed to update status.');
        }
    }

    // BLOG / REELS SECTION SETTINGS MODAL
    function openBlogReelsSettingsModal() {
        const blogSec = (managerData && managerData.sections) 
            ? managerData.sections.find(s => s.id === 'blog_reels' || s.is_blog_reels_section)
            : null;
        const settings = (blogSec && (blogSec.section_settings || blogSec.metadata)) || {};

        if (document.getElementById('blogReelsSettingsTitle')) {
            document.getElementById('blogReelsSettingsTitle').value = settings.title || 'Behind the Stitches';
        }
        if (document.getElementById('blogReelsSettingsTagText')) {
            document.getElementById('blogReelsSettingsTagText').value = settings.badge || settings.tag_text || 'Studio Journal & Video Reels';
        }
        if (document.getElementById('blogReelsSettingsSubtitle')) {
            document.getElementById('blogReelsSettingsSubtitle').value = settings.description || settings.subtitle || 'Watch our artisans hand-craft each creation, styling guides, and cozy studio ASMR unboxings.';
        }
        if (document.getElementById('blogReelsSettingsCtaText')) {
            document.getElementById('blogReelsSettingsCtaText').value = settings.cta_text || 'Follow @knotelleindia';
        }
        if (document.getElementById('blogReelsSettingsCtaLink')) {
            document.getElementById('blogReelsSettingsCtaLink').value = settings.cta_link || 'https://instagram.com/knotelleindia';
        }

        const modal = document.getElementById('blogReelsSettingsModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeBlogReelsSettingsModal() {
        const modal = document.getElementById('blogReelsSettingsModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleBlogReelsSettingsSubmit(event) {
        if (event) event.preventDefault();
        const form = document.getElementById('blogReelsSettingsForm');
        const formData = new FormData(form);

        try {
            const res = await axios.post(`${adminMediaBase}/blog-reels/settings`, formData);
            if (res.data.success) {
                toastr.success(res.data.message || 'Section settings updated.');
                closeBlogReelsSettingsModal();
                broadcastMediaUpdate();
                loadManagerData(true, 'sec-card-blog_reels');
            } else {
                toastr.error(res.data.message || 'Failed to save section settings.');
            }
        } catch (err) {
            console.error('Settings save error', err);
            toastr.error(err.response?.data?.message || 'Failed to save section settings.');
        }
    }

    // Responsive Device Preview
    function openDevicePreview(imgUrl, title, path) {
        currentPreviewUrl = imgUrl;
        document.getElementById('previewModalImg').src = imgUrl;
        document.getElementById('previewModalSlotTitle').innerText = title;
        document.getElementById('previewModalSlotPath').innerText = path;
        setPreviewDevice('desktop');

        const modal = document.getElementById('devicePreviewModal');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDevicePreviewModal() {
        const modal = document.getElementById('devicePreviewModal');
        modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function setPreviewDevice(device) {
        const frame = document.getElementById('previewFrame');
        const resLabel = document.getElementById('previewFrameResolution');
        const tabDesktop = document.getElementById('previewTabDesktop');
        const tabTablet = document.getElementById('previewTabTablet');
        const tabMobile = document.getElementById('previewTabMobile');

        // Reset tab classes
        [tabDesktop, tabTablet, tabMobile].forEach(t => {
            t.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all text-stone-500 hover:text-stone-800 flex items-center gap-1.5';
        });

        if (device === 'desktop') {
            frame.style.maxWidth = '100%';
            resLabel.innerText = 'Desktop Viewport (1920 × 1080)';
            tabDesktop.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-stone-800 shadow-xs flex items-center gap-1.5';
        } else if (device === 'tablet') {
            frame.style.maxWidth = '640px';
            resLabel.innerText = 'Tablet Viewport (768 × 1024)';
            tabTablet.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-stone-800 shadow-xs flex items-center gap-1.5';
        } else {
            frame.style.maxWidth = '375px';
            resLabel.innerText = 'Mobile Viewport (375 × 667)';
            tabMobile.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-stone-800 shadow-xs flex items-center gap-1.5';
        }
    }

    function openGenericUploadModal() {
        switchView('library');
    }

    // Library Tabulator Table Initialization
    let libraryTable = null;
    function initLibraryTable() {
        if (libraryTable) return;

        libraryTable = new Tabulator("#mediaTable", {
            ajaxURL: adminMediaBase + "/data",
            layout: "fitColumns",
            pagination: true,
            paginationMode: "remote",
            paginationSize: 15,
            columns: [
                {
                    title: "Preview",
                    field: "url",
                    width: 70,
                    hozAlign: "center",
                    formatter: function(cell) {
                        return `<img src="${cell.getValue()}" class="w-10 h-10 rounded-lg object-cover mx-auto" onerror="this.onerror=null; this.src=(window.location.pathname.startsWith('/knottele') ? '/knottele' : '') + '/images/logo/Logo_1.png';">`;
                    }
                },
                { title: "Name / Title", field: "name", widthGrow: 2 },
                { title: "Page", field: "page", width: 100 },
                { title: "Section", field: "section", width: 120 },
                { title: "Slot", field: "slot", width: 130 },
                { title: "Device", field: "device", width: 90 },
                { title: "Size", field: "size_formatted", width: 90, hozAlign: "right" },
                {
                    title: "Status",
                    field: "is_active",
                    width: 90,
                    hozAlign: "center",
                    formatter: function(cell) {
                        return cell.getValue() ? '<span class="text-emerald-600 font-bold text-xs">Active</span>' : '<span class="text-stone-400 text-xs">Inactive</span>';
                    }
                },
                {
                    title: "Actions",
                    field: "id",
                    width: 100,
                    hozAlign: "center",
                    formatter: function(cell) {
                        const id = cell.getValue();
                        return `
                            <button onclick="deleteMedia(${id})" class="p-1.5 text-rose-600 hover:text-rose-900" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                    }
                }
            ]
        });

        // Search in library table
        document.getElementById('librarySearchInput').addEventListener('keyup', function(e) {
            libraryTable.setData(adminMediaBase + '/data?search=' + encodeURIComponent(this.value));
        });

        // Library file dropzone input
        document.getElementById('libraryFileInput').addEventListener('change', async function(e) {
            if (this.files && this.files.length > 0) {
                const formData = new FormData();
                for (let i = 0; i < this.files.length; i++) {
                    formData.append('files[]', this.files[i]);
                }
                formData.append('page', 'homepage');
                formData.append('section', 'unassigned');
                formData.append('slot', 'default');

                try {
                    toastr.info('Uploading media files...');
                    const res = await axios.post(`${adminMediaBase}/upload`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (res.data.success) {
                        toastr.success(res.data.message);
                        libraryTable.setData();
                        loadManagerData();
                    }
                } catch (err) {
                    toastr.error('Upload failed');
                }
            }
        });
    }

    function refreshLibraryData() {
        if (libraryTable) {
            libraryTable.setData(adminMediaBase + '/data');
            toastr.info('Media library refreshed');
        }
    }

    async function deleteMedia(id) {
        if (!confirm('Are you sure you want to delete this media file?')) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.delete(`${adminMediaBase}/${id}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data.success) {
                toastr.success(res.data.message);
                broadcastMediaUpdate();
                if (libraryTable) libraryTable.setData();
                loadManagerData();
            } else {
                toastr.error(res.data.message);
            }
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to delete media.');
        }
    }

    function getPillarIconFa(iconName) {
        const map = {
            'Leaf': 'fas fa-leaf',
            'Sparkles': 'fas fa-magic',
            'Heart': 'fas fa-heart',
            'Flower2': 'fas fa-spa',
            'ShieldCheck': 'fas fa-shield-alt',
            'Sun': 'fas fa-sun',
            'Star': 'fas fa-star',
            'Award': 'fas fa-award',
            'Gem': 'fas fa-gem',
            'Feather': 'fas fa-feather-alt',
            'Gift': 'fas fa-gift',
            'Smile': 'fas fa-smile',
        };
        return map[iconName] || 'fas fa-leaf';
    }

    function getContactIconFa(iconName) {
        const map = {
            'MapPin': 'fas fa-map-marker-alt',
            'Phone': 'fas fa-phone',
            'Mail': 'fas fa-envelope',
            'Clock': 'fas fa-clock',
            'MessageCircle': 'fab fa-whatsapp',
            'Instagram': 'fab fa-instagram',
            'Facebook': 'fab fa-facebook-f',
            'Globe': 'fas fa-globe',
            'Sparkles': 'fas fa-magic',
            'Heart': 'fas fa-heart'
        };
        return map[iconName] || 'fas fa-map-marker-alt';
    }

    // ==========================================
    // ABOUT STORY HANDLERS
    // ==========================================
    async function openAboutStoryModal() {
        const modal = document.getElementById('aboutStoryModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Try populating from cache first for instantaneous opening
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'about_story' || s.is_about_story_section);
            if (sec && sec.metadata) {
                const meta = sec.metadata;
                if (document.getElementById('aboutStoryTagText')) document.getElementById('aboutStoryTagText').value = sec.tag_text || meta.tag_text || 'The KNOTELLE Story';
                if (document.getElementById('aboutStoryTitle')) document.getElementById('aboutStoryTitle').value = sec.title || meta.title || 'Every Loop Tells a Story';
                if (document.getElementById('aboutStorySubtitle')) document.getElementById('aboutStorySubtitle').value = sec.subtitle || meta.subtitle || '';
                if (document.getElementById('aboutStoryDescription')) document.getElementById('aboutStoryDescription').value = sec.description || meta.description || '';
                if (document.getElementById('aboutStoryParagraph2')) document.getElementById('aboutStoryParagraph2').value = meta.paragraph_2 || '';
                if (document.getElementById('aboutStoryAlt')) document.getElementById('aboutStoryAlt').value = sec.alt_text || meta.alt_text || '';
                if (document.getElementById('aboutStoryFloatingTitle')) document.getElementById('aboutStoryFloatingTitle').value = meta.floating_badge_title || '100% Handcrafted';
                if (document.getElementById('aboutStoryFloatingSubtitle')) document.getElementById('aboutStoryFloatingSubtitle').value = meta.floating_badge_subtitle || 'Never mass machine produced';
                if (document.getElementById('aboutStoryFloatingIcon')) document.getElementById('aboutStoryFloatingIcon').value = meta.floating_badge_icon || 'Heart';
                if (document.getElementById('aboutStoryFloatingActive')) document.getElementById('aboutStoryFloatingActive').checked = meta.floating_badge_active !== false;
                if (document.getElementById('aboutStoryCtaText')) document.getElementById('aboutStoryCtaText').value = sec.cta_text || meta.cta_text || 'Request a Custom Creation';
                if (document.getElementById('aboutStoryCtaLink')) document.getElementById('aboutStoryCtaLink').value = sec.cta_link || meta.cta_link || '/custom-order';
                if (document.getElementById('aboutStoryCtaVisible')) document.getElementById('aboutStoryCtaVisible').checked = meta.cta_visible !== false;
                if (document.getElementById('aboutStoryActive')) document.getElementById('aboutStoryActive').checked = sec.is_active !== false;
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/about/story`);
            if (res.data && res.data.success) {
                const s = res.data.data;
                if (document.getElementById('aboutStoryTagText')) document.getElementById('aboutStoryTagText').value = s.tag_text || 'The KNOTELLE Story';
                if (document.getElementById('aboutStoryTitle')) document.getElementById('aboutStoryTitle').value = s.title || 'Every Loop Tells a Story';
                if (document.getElementById('aboutStorySubtitle')) document.getElementById('aboutStorySubtitle').value = s.subtitle || '';
                if (document.getElementById('aboutStoryDescription')) document.getElementById('aboutStoryDescription').value = s.description || '';
                if (document.getElementById('aboutStoryParagraph2')) document.getElementById('aboutStoryParagraph2').value = s.paragraph_2 || '';
                if (document.getElementById('aboutStoryAlt')) document.getElementById('aboutStoryAlt').value = s.alt_text || '';
                
                if (document.getElementById('aboutStoryFloatingTitle')) document.getElementById('aboutStoryFloatingTitle').value = s.floating_badge_title || '100% Handcrafted';
                if (document.getElementById('aboutStoryFloatingSubtitle')) document.getElementById('aboutStoryFloatingSubtitle').value = s.floating_badge_subtitle || 'Never mass machine produced';
                if (document.getElementById('aboutStoryFloatingIcon')) document.getElementById('aboutStoryFloatingIcon').value = s.floating_badge_icon || 'Heart';
                if (document.getElementById('aboutStoryFloatingActive')) document.getElementById('aboutStoryFloatingActive').checked = s.floating_badge_active !== false;

                if (document.getElementById('aboutStoryCtaText')) document.getElementById('aboutStoryCtaText').value = s.cta_text || 'Request a Custom Creation';
                if (document.getElementById('aboutStoryCtaLink')) document.getElementById('aboutStoryCtaLink').value = s.cta_link || '/custom-order';
                if (document.getElementById('aboutStoryCtaVisible')) document.getElementById('aboutStoryCtaVisible').checked = s.cta_visible !== false;
                if (document.getElementById('aboutStoryActive')) document.getElementById('aboutStoryActive').checked = s.is_active !== false;

                if (document.getElementById('aboutStoryDesktopUrl')) document.getElementById('aboutStoryDesktopUrl').value = s.desktop_image || '';
                if (document.getElementById('aboutStoryMobileUrl')) document.getElementById('aboutStoryMobileUrl').value = s.mobile_image || '';

                if (s.desktop_image && document.getElementById('aboutDesktopPreviewImg')) {
                    document.getElementById('aboutDesktopPreviewImg').src = s.desktop_image;
                    document.getElementById('aboutDesktopPreviewContainer')?.classList.remove('hidden');
                }
                if (s.mobile_image && document.getElementById('aboutMobilePreviewImg')) {
                    document.getElementById('aboutMobilePreviewImg').src = s.mobile_image;
                    document.getElementById('aboutMobilePreviewContainer')?.classList.remove('hidden');
                } else if (document.getElementById('aboutMobilePreviewContainer')) {
                    document.getElementById('aboutMobilePreviewContainer').classList.add('hidden');
                }
            }
        } catch (err) {
            console.error('Failed to load fresh About Story data', err);
        }
    }

    function closeAboutStoryModal() {
        const modal = document.getElementById('aboutStoryModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleAboutDesktopFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('aboutDesktopPreviewImg').src = e.target.result;
                document.getElementById('aboutDesktopFileName').textContent = file.name;
                document.getElementById('aboutDesktopFileSize').textContent = formatBytes(file.size);
                document.getElementById('aboutDesktopPreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearAboutDesktopFileInput() {
        document.getElementById('aboutStoryDesktopFileInput').value = '';
        document.getElementById('aboutDesktopPreviewContainer').classList.add('hidden');
    }

    function handleAboutMobileFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('aboutMobilePreviewImg').src = e.target.result;
                document.getElementById('aboutMobileFileName').textContent = file.name;
                document.getElementById('aboutMobileFileSize').textContent = formatBytes(file.size);
                document.getElementById('aboutMobilePreviewContainer').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearAboutMobileFileInput() {
        document.getElementById('aboutStoryMobileFileInput').value = '';
        document.getElementById('aboutMobilePreviewContainer').classList.add('hidden');
    }

    async function handleAboutStorySubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('aboutStorySubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('aboutStoryForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/about/story`, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'About Story saved successfully!');
                closeAboutStoryModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save About Story.');
            }
        } catch (err) {
            console.error('Failed to save About Story', err);
            toastr.error(err.response?.data?.message || 'Failed to save About Story.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Story & Visuals</span>';
            }
        }
    }

    // ==========================================
    // CRAFT PILLARS HEADER HANDLERS
    // ==========================================
    function openCraftPillarsHeaderModal() {
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'craft_pillars');
            if (sec && sec.metadata) {
                if (document.getElementById('craftPillarsHeaderTitle')) document.getElementById('craftPillarsHeaderTitle').value = sec.metadata.title || 'Our Craft Pillars';
                if (document.getElementById('craftPillarsHeaderTagText')) document.getElementById('craftPillarsHeaderTagText').value = sec.metadata.tag_text || 'Artisan Standards';
                if (document.getElementById('craftPillarsHeaderSubtitle')) document.getElementById('craftPillarsHeaderSubtitle').value = sec.metadata.subtitle || 'Guiding principles behind every stitch we make.';
                if (document.getElementById('craftPillarsHeaderActive')) document.getElementById('craftPillarsHeaderActive').checked = sec.metadata.is_active !== false;
            }
        }
        const modal = document.getElementById('craftPillarsHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeCraftPillarsHeaderModal() {
        const modal = document.getElementById('craftPillarsHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleCraftPillarsHeaderSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('craftPillarsHeaderSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('craftPillarsHeaderForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/about/craft-pillars/header`, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Header updated successfully!');
                closeCraftPillarsHeaderModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to update header.');
            }
        } catch (err) {
            console.error('Craft pillars header error', err);
            toastr.error(err.response?.data?.message || 'Failed to update Craft Pillars header.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Section Header</span>';
            }
        }
    }

    // ==========================================
    // CRAFT PILLARS ITEM CRUD HANDLERS
    // ==========================================
    function selectPillarIcon(iconName) {
        const iconEl = document.getElementById('craftPillarIconName');
        if (iconEl) iconEl.value = iconName;
        const typeEl = document.getElementById('craftPillarIconType');
        if (typeEl) typeEl.value = 'preset';
        document.querySelectorAll('.pillar-icon-btn').forEach(btn => {
            if (btn.getAttribute('data-icon') === iconName) {
                btn.classList.add('border-red-600', 'bg-red-50', 'ring-2', 'ring-red-500/20');
                btn.classList.remove('border-stone-200', 'bg-white');
            } else {
                btn.classList.remove('border-red-600', 'bg-red-50', 'ring-2', 'ring-red-500/20');
                btn.classList.add('border-stone-200', 'bg-white');
            }
        });
    }

    function openAddCraftPillarModal() {
        const form = document.getElementById('craftPillarForm');
        if (form) form.reset();
        if (document.getElementById('craftPillarId')) document.getElementById('craftPillarId').value = '';
        if (document.getElementById('craftPillarModalTitle')) document.getElementById('craftPillarModalTitle').textContent = 'Add Craft Pillar';
        if (document.getElementById('craftPillarSubmitBtnText')) document.getElementById('craftPillarSubmitBtnText').textContent = 'Save Craft Pillar';
        if (document.getElementById('craftPillarTitle')) document.getElementById('craftPillarTitle').value = '';
        if (document.getElementById('craftPillarDescription')) document.getElementById('craftPillarDescription').value = '';
        if (document.getElementById('craftPillarSortOrder')) document.getElementById('craftPillarSortOrder').value = 1;
        if (document.getElementById('craftPillarActive')) document.getElementById('craftPillarActive').checked = true;
        selectPillarIcon('Leaf');

        const modal = document.getElementById('craftPillarModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    async function openEditCraftPillarModal(id) {
        const modal = document.getElementById('craftPillarModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Pre-fill from managerData immediately
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'craft_pillars' || s.is_craft_pillars_section);
            if (sec && sec.items) {
                const cached = sec.items.find(p => p.id == id);
                if (cached) {
                    if (document.getElementById('craftPillarId')) document.getElementById('craftPillarId').value = cached.id;
                    if (document.getElementById('craftPillarModalTitle')) document.getElementById('craftPillarModalTitle').textContent = 'Edit Craft Pillar';
                    if (document.getElementById('craftPillarSubmitBtnText')) document.getElementById('craftPillarSubmitBtnText').textContent = 'Update Craft Pillar';
                    if (document.getElementById('craftPillarTitle')) document.getElementById('craftPillarTitle').value = cached.title || '';
                    if (document.getElementById('craftPillarDescription')) document.getElementById('craftPillarDescription').value = cached.description || '';
                    if (document.getElementById('craftPillarSortOrder')) document.getElementById('craftPillarSortOrder').value = cached.sort_order || 1;
                    if (document.getElementById('craftPillarActive')) document.getElementById('craftPillarActive').checked = cached.is_active !== false;
                    selectPillarIcon(cached.icon_name || cached.icon || 'Leaf');
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/about/craft-pillars/${id}`);
            if (res.data && res.data.success) {
                const p = res.data.data;
                if (document.getElementById('craftPillarId')) document.getElementById('craftPillarId').value = p.id;
                if (document.getElementById('craftPillarModalTitle')) document.getElementById('craftPillarModalTitle').textContent = 'Edit Craft Pillar';
                if (document.getElementById('craftPillarSubmitBtnText')) document.getElementById('craftPillarSubmitBtnText').textContent = 'Update Craft Pillar';
                if (document.getElementById('craftPillarTitle')) document.getElementById('craftPillarTitle').value = p.title || '';
                if (document.getElementById('craftPillarDescription')) document.getElementById('craftPillarDescription').value = p.description || '';
                if (document.getElementById('craftPillarSortOrder')) document.getElementById('craftPillarSortOrder').value = p.sort_order || 1;
                if (document.getElementById('craftPillarActive')) document.getElementById('craftPillarActive').checked = p.is_active !== false;
                
                selectPillarIcon(p.icon_name || p.icon || 'Leaf');
            }
        } catch (err) {
            console.error('Failed to load Craft Pillar details', err);
        }
    }

    function closeCraftPillarModal() {
        const modal = document.getElementById('craftPillarModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleCraftPillarSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('craftPillarSubmitBtn');
        const id = document.getElementById('craftPillarId')?.value;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('craftPillarForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const url = id ? `${adminMediaBase}/about/craft-pillars/${id}` : `${adminMediaBase}/about/craft-pillars`;

        try {
            const res = await axios.post(url, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Pillar saved successfully!');
                closeCraftPillarModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save pillar.');
            }
        } catch (err) {
            console.error('Failed to save Craft Pillar', err);
            toastr.error(err.response?.data?.message || 'Failed to save Craft Pillar.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span id="craftPillarSubmitBtnText">Save Craft Pillar</span>';
            }
        }
    }

    async function deleteCraftPillar(id) {
        if (!confirm('Are you sure you want to delete this Craft Pillar?')) return;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let res;
            try {
                res = await axios.delete(`${adminMediaBase}/about/craft-pillars/${id}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            } catch (delErr) {
                res = await axios.post(`${adminMediaBase}/about/craft-pillars/${id}/delete`, {
                    _token: csrfToken || '',
                    _method: 'DELETE'
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            }
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Pillar deleted.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to delete.');
            }
        } catch (err) {
            console.error('Delete pillar error', err);
            toastr.error(err.response?.data?.message || 'Failed to delete Craft Pillar.');
        }
    }

    async function toggleCraftPillar(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post(`${adminMediaBase}/about/craft-pillars/${id}/toggle`, {
                _token: csrfToken || ''
            }, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Status updated.');
                broadcastMediaUpdate();
                loadManagerData();
            }
        } catch (err) {
            toastr.error('Failed to toggle status.');
        }
    }

    // ==========================================
    // ==========================================
    // 12. CONTACT INTRO / HERO HANDLERS
    // ==========================================
    async function openContactIntroModal() {
        const modal = document.getElementById('contactIntroModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'contact_intro' || s.is_contact_intro_section);
            if (sec && sec.metadata) {
                const s = sec.metadata;
                if (document.getElementById('contactIntroBadge')) document.getElementById('contactIntroBadge').value = s.badge || s.tag_text || "Let's Connect";
                if (document.getElementById('contactIntroTitle')) document.getElementById('contactIntroTitle').value = s.title || "Let's Connect";
                if (document.getElementById('contactIntroSubtitle')) document.getElementById('contactIntroSubtitle').value = s.subtitle || "Have a question about a product, custom order, or collaboration? We'd love to hear from you.";
                if (document.getElementById('contactIntroDescription')) document.getElementById('contactIntroDescription').value = s.description || "We're here to help bring your handcrafted crochet dreams to life.";
                if (document.getElementById('contactIntroAlt')) document.getElementById('contactIntroAlt').value = s.alt_text || "KNOTELLE Artisan Studio Contact";
                if (document.getElementById('contactIntroCtaText')) document.getElementById('contactIntroCtaText').value = s.cta_text || "";
                if (document.getElementById('contactIntroCtaLink')) document.getElementById('contactIntroCtaLink').value = s.cta_link || "";
                if (document.getElementById('contactIntroActive')) document.getElementById('contactIntroActive').checked = s.is_active !== false;

                const img = s.image_url || s.desktop_image || s.image || '';
                if (document.getElementById('contactIntroImageUrl')) document.getElementById('contactIntroImageUrl').value = img;
                if (img && document.getElementById('contactIntroPreviewImg')) {
                    document.getElementById('contactIntroPreviewImg').src = img;
                    if (document.getElementById('contactIntroFileName')) document.getElementById('contactIntroFileName').textContent = img.split('/').pop() || 'Contact Banner Active';
                    if (document.getElementById('contactIntroFileSize')) document.getElementById('contactIntroFileSize').textContent = 'Ready';
                    document.getElementById('contactIntroPreviewContainer')?.classList.remove('hidden');
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/intro`);
            if (res.data && res.data.success) {
                const s = res.data.data || {};
                if (document.getElementById('contactIntroBadge')) document.getElementById('contactIntroBadge').value = s.badge || s.tag_text || "Let's Connect";
                if (document.getElementById('contactIntroTitle')) document.getElementById('contactIntroTitle').value = s.title || "Let's Connect";
                if (document.getElementById('contactIntroSubtitle')) document.getElementById('contactIntroSubtitle').value = s.subtitle || "Have a question about a product, custom order, or collaboration? We'd love to hear from you.";
                if (document.getElementById('contactIntroDescription')) document.getElementById('contactIntroDescription').value = s.description || "We're here to help bring your handcrafted crochet dreams to life.";
                if (document.getElementById('contactIntroAlt')) document.getElementById('contactIntroAlt').value = s.alt_text || "KNOTELLE Artisan Studio Contact";
                if (document.getElementById('contactIntroCtaText')) document.getElementById('contactIntroCtaText').value = s.cta_text || "";
                if (document.getElementById('contactIntroCtaLink')) document.getElementById('contactIntroCtaLink').value = s.cta_link || "";
                if (document.getElementById('contactIntroActive')) document.getElementById('contactIntroActive').checked = s.is_active !== false;

                const img = s.image_url || s.desktop_image || s.image || '';
                if (document.getElementById('contactIntroImageUrl')) document.getElementById('contactIntroImageUrl').value = img;
                if (img && document.getElementById('contactIntroPreviewImg')) {
                    document.getElementById('contactIntroPreviewImg').src = img;
                    if (document.getElementById('contactIntroFileName')) document.getElementById('contactIntroFileName').textContent = img.split('/').pop() || 'Contact Banner Active';
                    if (document.getElementById('contactIntroFileSize')) document.getElementById('contactIntroFileSize').textContent = 'Ready';
                    document.getElementById('contactIntroPreviewContainer')?.classList.remove('hidden');
                } else if (!img) {
                    clearContactIntroFileInput();
                }
            }
        } catch (err) {
            console.error('Failed to load Contact Intro details', err);
        }
    }

    function closeContactIntroModal() {
        const modal = document.getElementById('contactIntroModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleContactIntroFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            if (document.getElementById('contactIntroFileLabel')) document.getElementById('contactIntroFileLabel').textContent = file.name;
            const reader = new FileReader();
            reader.onload = function(e) {
                if (document.getElementById('contactIntroPreviewImg')) document.getElementById('contactIntroPreviewImg').src = e.target.result;
                if (document.getElementById('contactIntroFileName')) document.getElementById('contactIntroFileName').textContent = file.name;
                if (document.getElementById('contactIntroFileSize')) document.getElementById('contactIntroFileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
                document.getElementById('contactIntroPreviewContainer')?.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    function clearContactIntroFileInput() {
        const fileInput = document.getElementById('contactIntroFileInput');
        if (fileInput) fileInput.value = '';
        if (document.getElementById('contactIntroFileLabel')) document.getElementById('contactIntroFileLabel').textContent = 'Upload Banner Image';
        if (document.getElementById('contactIntroPreviewImg')) document.getElementById('contactIntroPreviewImg').src = '';
        if (document.getElementById('contactIntroImageUrl')) document.getElementById('contactIntroImageUrl').value = '';
        if (document.getElementById('contactIntroPreviewContainer')) document.getElementById('contactIntroPreviewContainer').classList.add('hidden');
    }

    async function handleContactIntroSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactIntroSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactIntroForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/contact/intro`, formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Contact Intro saved successfully.');
                closeContactIntroModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save Contact Intro.');
            }
        } catch (err) {
            console.error('Failed to save Contact Intro', err);
            toastr.error(err.response?.data?.message || 'Failed to save Contact Intro.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Contact Intro</span>';
            }
        }
    }

    // ==========================================
    // 13. CONTACT INFO HEADER & HELPER BOX
    // ==========================================
    async function openContactInfoHeaderModal() {
        const modal = document.getElementById('contactInfoHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'contact_info' || s.is_contact_info_section);
            if (sec && sec.metadata) {
                const h = sec.metadata;
                if (document.getElementById('contactInfoBadgeInput')) document.getElementById('contactInfoBadgeInput').value = h.badge || h.tag_text || 'Atelier Studio';
                if (document.getElementById('contactInfoTitleInput')) document.getElementById('contactInfoTitleInput').value = h.title || 'KNOTELLE Studio';
                if (document.getElementById('contactInfoSubtitleInput')) document.getElementById('contactInfoSubtitleInput').value = h.subtitle || 'Handmade with love in Bengaluru, India';
                if (document.getElementById('contactInfoCustomBoxTitle')) document.getElementById('contactInfoCustomBoxTitle').value = h.custom_order_box_title || 'Looking for Custom Orders?';
                if (document.getElementById('contactInfoCustomBoxText')) document.getElementById('contactInfoCustomBoxText').value = h.custom_order_box_text || 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.';
                if (document.getElementById('contactInfoCustomBoxLink')) document.getElementById('contactInfoCustomBoxLink').value = h.custom_order_box_link || '/custom-order';
                if (document.getElementById('contactInfoCustomBoxActive')) document.getElementById('contactInfoCustomBoxActive').checked = h.custom_order_box_active !== false;
                if (document.getElementById('contactInfoHeaderActive')) document.getElementById('contactInfoHeaderActive').checked = h.is_active !== false;
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/info/header`);
            if (res.data && res.data.success) {
                const h = res.data.data || {};
                if (document.getElementById('contactInfoBadgeInput')) document.getElementById('contactInfoBadgeInput').value = h.badge || h.tag_text || 'Atelier Studio';
                if (document.getElementById('contactInfoTitleInput')) document.getElementById('contactInfoTitleInput').value = h.title || 'KNOTELLE Studio';
                if (document.getElementById('contactInfoSubtitleInput')) document.getElementById('contactInfoSubtitleInput').value = h.subtitle || 'Handmade with love in Bengaluru, India';
                if (document.getElementById('contactInfoCustomBoxTitle')) document.getElementById('contactInfoCustomBoxTitle').value = h.custom_order_box_title || 'Looking for Custom Orders?';
                if (document.getElementById('contactInfoCustomBoxText')) document.getElementById('contactInfoCustomBoxText').value = h.custom_order_box_text || 'Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.';
                if (document.getElementById('contactInfoCustomBoxLink')) document.getElementById('contactInfoCustomBoxLink').value = h.custom_order_box_link || '/custom-order';
                if (document.getElementById('contactInfoCustomBoxActive')) document.getElementById('contactInfoCustomBoxActive').checked = h.custom_order_box_active !== false;
                if (document.getElementById('contactInfoHeaderActive')) document.getElementById('contactInfoHeaderActive').checked = h.is_active !== false;
            }
        } catch (err) {
            console.error('Failed to load Contact Studio Header settings', err);
        }
    }

    function closeContactInfoHeaderModal() {
        const modal = document.getElementById('contactInfoHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleContactInfoHeaderSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactInfoHeaderSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactInfoHeaderForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/contact/info/header`, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Studio Header updated successfully.');
                closeContactInfoHeaderModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to update Studio Header.');
            }
        } catch (err) {
            console.error('Failed to update Studio Header', err);
            toastr.error(err.response?.data?.message || 'Failed to update Studio Header.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Studio Header</span>';
            }
        }
    }

    // ==========================================
    // 14. CONTACT INFO ITEMS (CRUD + TOGGLE)
    // ==========================================
    function openAddContactInfoItemModal() {
        const form = document.getElementById('contactInfoItemForm');
        if (form) form.reset();
        if (document.getElementById('contactInfoItemId')) document.getElementById('contactInfoItemId').value = '';
        if (document.getElementById('contactInfoItemModalTitle')) document.getElementById('contactInfoItemModalTitle').textContent = 'Add Contact Detail';
        if (document.getElementById('contactInfoItemSubmitBtnText')) document.getElementById('contactInfoItemSubmitBtnText').textContent = 'Save Contact Detail';
        if (document.getElementById('contactInfoItemTitle')) document.getElementById('contactInfoItemTitle').value = '';
        if (document.getElementById('contactInfoItemValue')) document.getElementById('contactInfoItemValue').value = '';
        if (document.getElementById('contactInfoItemAddressLine2')) document.getElementById('contactInfoItemAddressLine2').value = '';
        if (document.getElementById('contactInfoItemLink')) document.getElementById('contactInfoItemLink').value = '';
        if (document.getElementById('contactInfoItemSortOrder')) document.getElementById('contactInfoItemSortOrder').value = 1;
        if (document.getElementById('contactInfoItemActive')) document.getElementById('contactInfoItemActive').checked = true;

        selectContactInfoIcon('MapPin');

        const modal = document.getElementById('contactInfoItemModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    async function openEditContactInfoItemModal(id) {
        const modal = document.getElementById('contactInfoItemModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'contact_info' || s.is_contact_info_section);
            if (sec && sec.items) {
                const cached = sec.items.find(i => i.id == id);
                if (cached) {
                    if (document.getElementById('contactInfoItemId')) document.getElementById('contactInfoItemId').value = cached.id;
                    if (document.getElementById('contactInfoItemModalTitle')) document.getElementById('contactInfoItemModalTitle').textContent = 'Edit Contact Detail';
                    if (document.getElementById('contactInfoItemSubmitBtnText')) document.getElementById('contactInfoItemSubmitBtnText').textContent = 'Update Contact Detail';
                    if (document.getElementById('contactInfoItemTitle')) document.getElementById('contactInfoItemTitle').value = cached.title || '';
                    if (document.getElementById('contactInfoItemValue')) document.getElementById('contactInfoItemValue').value = cached.value || cached.description || '';
                    if (document.getElementById('contactInfoItemAddressLine2')) document.getElementById('contactInfoItemAddressLine2').value = cached.address_line_2 || '';
                    if (document.getElementById('contactInfoItemLink')) document.getElementById('contactInfoItemLink').value = cached.link || cached.cta_link || '';
                    if (document.getElementById('contactInfoItemSortOrder')) document.getElementById('contactInfoItemSortOrder').value = cached.sort_order || 1;
                    if (document.getElementById('contactInfoItemActive')) document.getElementById('contactInfoItemActive').checked = cached.is_active !== false;
                    selectContactInfoIcon(cached.icon || cached.icon_name || 'MapPin');
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/info/items/${id}`);
            if (res.data && res.data.success) {
                const item = res.data.data;
                if (document.getElementById('contactInfoItemId')) document.getElementById('contactInfoItemId').value = item.id;
                if (document.getElementById('contactInfoItemModalTitle')) document.getElementById('contactInfoItemModalTitle').textContent = 'Edit Contact Detail';
                if (document.getElementById('contactInfoItemSubmitBtnText')) document.getElementById('contactInfoItemSubmitBtnText').textContent = 'Update Contact Detail';
                if (document.getElementById('contactInfoItemTitle')) document.getElementById('contactInfoItemTitle').value = item.title || '';
                if (document.getElementById('contactInfoItemValue')) document.getElementById('contactInfoItemValue').value = item.value || '';
                if (document.getElementById('contactInfoItemAddressLine2')) document.getElementById('contactInfoItemAddressLine2').value = item.address_line_2 || '';
                if (document.getElementById('contactInfoItemLink')) document.getElementById('contactInfoItemLink').value = item.link || item.cta_link || '';
                if (document.getElementById('contactInfoItemSortOrder')) document.getElementById('contactInfoItemSortOrder').value = item.sort_order || 1;
                if (document.getElementById('contactInfoItemActive')) document.getElementById('contactInfoItemActive').checked = item.is_active !== false;

                selectContactInfoIcon(item.icon || item.icon_name || 'MapPin');
            }
        } catch (err) {
            console.error('Failed to load Contact Detail', err);
        }
    }

    function closeContactInfoItemModal() {
        const modal = document.getElementById('contactInfoItemModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function selectContactInfoIcon(iconName) {
        const iconEl = document.getElementById('contactInfoItemIcon');
        if (iconEl) iconEl.value = iconName;
        document.querySelectorAll('.contact-info-icon-btn').forEach(btn => {
            if (btn.getAttribute('data-icon') === iconName) {
                btn.classList.add('border-red-500', 'bg-red-50/50', 'ring-2', 'ring-red-300');
                btn.classList.remove('border-stone-200', 'bg-white');
            } else {
                btn.classList.remove('border-red-500', 'bg-red-50/50', 'ring-2', 'ring-red-300');
                btn.classList.add('border-stone-200', 'bg-white');
            }
        });
    }

    async function handleContactInfoItemSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactInfoItemSubmitBtn');
        const id = document.getElementById('contactInfoItemId')?.value;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactInfoItemForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = id ? `${adminMediaBase}/contact/info/items/${id}` : `${adminMediaBase}/contact/info/items`;

        try {
            const res = await axios.post(url, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Contact detail saved successfully.');
                closeContactInfoItemModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save detail.');
            }
        } catch (err) {
            console.error('Failed to save detail', err);
            toastr.error(err.response?.data?.message || 'Failed to save detail.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span id="contactInfoItemSubmitBtnText">Save Contact Detail</span>';
            }
        }
    }

    async function deleteContactInfoItem(id) {
        if (!confirm('Are you sure you want to delete this contact detail?')) return;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let res;
            try {
                res = await axios.delete(`${adminMediaBase}/contact/info/items/${id}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            } catch (delErr) {
                res = await axios.post(`${adminMediaBase}/contact/info/items/${id}/delete`, {
                    _token: csrfToken || '',
                    _method: 'DELETE'
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            }
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Contact detail deleted.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to delete detail.');
            }
        } catch (err) {
            console.error('Delete detail error', err);
            toastr.error(err.response?.data?.message || 'Failed to delete detail.');
        }
    }

    async function toggleContactInfoItem(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post(`${adminMediaBase}/contact/info/items/${id}/toggle`, {
                _token: csrfToken || ''
            }, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Status updated.');
                broadcastMediaUpdate();
                loadManagerData();
            }
        } catch (err) {
            toastr.error('Failed to toggle status.');
        }
    }

    // ==========================================
    // 15. CONTACT FORM SETTINGS
    // ==========================================
    async function openContactFormModal() {
        const modal = document.getElementById('contactFormModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'contact_form' || s.is_contact_form_section);
            if (sec && sec.metadata) {
                const f = sec.metadata;
                if (document.getElementById('contactFormBadge')) document.getElementById('contactFormBadge').value = f.badge || f.tag_text || 'Get In Touch';
                if (document.getElementById('contactFormTitle')) document.getElementById('contactFormTitle').value = f.title || 'Send Us a Message';
                if (document.getElementById('contactFormSubtitle')) document.getElementById('contactFormSubtitle').value = f.subtitle || 'Fill in your details and our team will get back to you promptly.';
                if (document.getElementById('contactFormCtaText')) document.getElementById('contactFormCtaText').value = f.cta_text || f.submit_btn_text || 'Send Message';
                if (document.getElementById('contactFormSuccessTitle')) document.getElementById('contactFormSuccessTitle').value = f.success_title || 'Message Sent!';
                if (document.getElementById('contactFormSuccessMessage')) document.getElementById('contactFormSuccessMessage').value = f.success_message || 'Thank you! Your message has been sent successfully. We will get back to you shortly.';
                if (document.getElementById('contactFormErrorMessage')) document.getElementById('contactFormErrorMessage').value = f.error_message || 'Something went wrong while sending your message. Please check the form and try again.';
                if (document.getElementById('contactFormActive')) document.getElementById('contactFormActive').checked = f.is_active !== false;

                if (f.fields && Array.isArray(f.fields)) {
                    f.fields.forEach(field => {
                        const labelEl = document.getElementById(`field_${field.key}_label`);
                        const placeholderEl = document.getElementById(`field_${field.key}_placeholder`);
                        if (labelEl) labelEl.value = field.label || '';
                        if (placeholderEl) placeholderEl.value = field.placeholder || '';
                    });
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/form`);
            if (res.data && res.data.success) {
                const f = res.data.data || {};
                if (document.getElementById('contactFormBadge')) document.getElementById('contactFormBadge').value = f.badge || f.tag_text || 'Get In Touch';
                if (document.getElementById('contactFormTitle')) document.getElementById('contactFormTitle').value = f.title || 'Send Us a Message';
                if (document.getElementById('contactFormSubtitle')) document.getElementById('contactFormSubtitle').value = f.subtitle || 'Fill in your details and our team will get back to you promptly.';
                if (document.getElementById('contactFormCtaText')) document.getElementById('contactFormCtaText').value = f.cta_text || f.submit_btn_text || 'Send Message';
                if (document.getElementById('contactFormSuccessTitle')) document.getElementById('contactFormSuccessTitle').value = f.success_title || 'Message Sent!';
                if (document.getElementById('contactFormSuccessMessage')) document.getElementById('contactFormSuccessMessage').value = f.success_message || 'Thank you! Your message has been sent successfully. We will get back to you shortly.';
                if (document.getElementById('contactFormErrorMessage')) document.getElementById('contactFormErrorMessage').value = f.error_message || 'Something went wrong while sending your message. Please check the form and try again.';
                if (document.getElementById('contactFormActive')) document.getElementById('contactFormActive').checked = f.is_active !== false;

                if (f.fields && Array.isArray(f.fields)) {
                    f.fields.forEach(field => {
                        const labelEl = document.getElementById(`field_${field.key}_label`);
                        const placeholderEl = document.getElementById(`field_${field.key}_placeholder`);
                        if (labelEl) labelEl.value = field.label || '';
                        if (placeholderEl) placeholderEl.value = field.placeholder || '';
                    });
                }
            }
        } catch (err) {
            console.error('Failed to load Contact Form settings', err);
        }
    }

    function closeContactFormModal() {
        const modal = document.getElementById('contactFormModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleContactFormSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactFormSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactFormSettingsForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/contact/form`, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Form configuration saved successfully.');
                closeContactFormModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save form settings.');
            }
        } catch (err) {
            console.error('Contact form error', err);
            toastr.error(err.response?.data?.message || 'Failed to save form settings.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Form Configuration</span>';
            }
        }
    }

    // ==========================================
    // 16. CONTACT FAQS (HEADER + CRUD + TOGGLE)
    // ==========================================
    async function openContactFaqsHeaderModal() {
        const modal = document.getElementById('contactFaqsHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'faqs' || s.is_contact_faqs_section);
            if (sec && sec.metadata) {
                const h = sec.metadata;
                if (document.getElementById('contactFaqsHeaderTagText')) document.getElementById('contactFaqsHeaderTagText').value = h.badge || h.tag_text || 'Help & Support';
                if (document.getElementById('contactFaqsHeaderTitle')) document.getElementById('contactFaqsHeaderTitle').value = h.title || 'Frequently Asked Questions';
                if (document.getElementById('contactFaqsHeaderSubtitle')) document.getElementById('contactFaqsHeaderSubtitle').value = h.subtitle || 'Quick answers about our handmade creations, custom orders, and delivery.';
                if (document.getElementById('contactFaqsHeaderActive')) document.getElementById('contactFaqsHeaderActive').checked = h.is_active !== false;
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/faqs/header`);
            if (res.data && res.data.success) {
                const h = res.data.data || {};
                if (document.getElementById('contactFaqsHeaderTagText')) document.getElementById('contactFaqsHeaderTagText').value = h.badge || h.tag_text || 'Help & Support';
                if (document.getElementById('contactFaqsHeaderTitle')) document.getElementById('contactFaqsHeaderTitle').value = h.title || 'Frequently Asked Questions';
                if (document.getElementById('contactFaqsHeaderSubtitle')) document.getElementById('contactFaqsHeaderSubtitle').value = h.subtitle || 'Quick answers about our handmade creations, custom orders, and delivery.';
                if (document.getElementById('contactFaqsHeaderActive')) document.getElementById('contactFaqsHeaderActive').checked = h.is_active !== false;
            }
        } catch (err) {
            console.error('Failed to load FAQ Header settings', err);
        }
    }

    function closeContactFaqsHeaderModal() {
        const modal = document.getElementById('contactFaqsHeaderModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleContactFaqsHeaderSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactFaqsHeaderSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactFaqsHeaderForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        try {
            const res = await axios.post(`${adminMediaBase}/contact/faqs/header`, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'FAQ Header saved successfully.');
                closeContactFaqsHeaderModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save FAQ header.');
            }
        } catch (err) {
            console.error('FAQ header error', err);
            toastr.error(err.response?.data?.message || 'Failed to save FAQ header.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save FAQ Header</span>';
            }
        }
    }

    function openAddContactFaqModal() {
        const form = document.getElementById('contactFaqForm');
        if (form) form.reset();
        if (document.getElementById('contactFaqId')) document.getElementById('contactFaqId').value = '';
        if (document.getElementById('contactFaqModalTitle')) document.getElementById('contactFaqModalTitle').textContent = 'Add FAQ Item';
        if (document.getElementById('contactFaqSubmitBtnText')) document.getElementById('contactFaqSubmitBtnText').textContent = 'Save FAQ';
        if (document.getElementById('contactFaqQuestion')) document.getElementById('contactFaqQuestion').value = '';
        if (document.getElementById('contactFaqAnswer')) document.getElementById('contactFaqAnswer').value = '';
        if (document.getElementById('contactFaqSortOrder')) document.getElementById('contactFaqSortOrder').value = 1;
        if (document.getElementById('contactFaqActive')) document.getElementById('contactFaqActive').checked = true;

        const modal = document.getElementById('contactFaqModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    async function openEditContactFaqModal(id) {
        const modal = document.getElementById('contactFaqModal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Instant pre-population from managerData
        if (managerData && managerData.sections) {
            const sec = managerData.sections.find(s => s.id === 'faqs' || s.is_contact_faqs_section);
            if (sec && sec.items) {
                const cached = sec.items.find(q => q.id == id);
                if (cached) {
                    if (document.getElementById('contactFaqId')) document.getElementById('contactFaqId').value = cached.id;
                    if (document.getElementById('contactFaqModalTitle')) document.getElementById('contactFaqModalTitle').textContent = 'Edit FAQ Item';
                    if (document.getElementById('contactFaqSubmitBtnText')) document.getElementById('contactFaqSubmitBtnText').textContent = 'Update FAQ';
                    if (document.getElementById('contactFaqQuestion')) document.getElementById('contactFaqQuestion').value = cached.question || cached.title || '';
                    if (document.getElementById('contactFaqAnswer')) document.getElementById('contactFaqAnswer').value = cached.answer || cached.description || '';
                    if (document.getElementById('contactFaqSortOrder')) document.getElementById('contactFaqSortOrder').value = cached.sort_order || 1;
                    if (document.getElementById('contactFaqActive')) document.getElementById('contactFaqActive').checked = cached.is_active !== false;
                }
            }
        }

        try {
            const res = await axios.get(`${adminMediaBase}/contact/faqs/items/${id}`);
            if (res.data && res.data.success) {
                const faq = res.data.data;
                if (document.getElementById('contactFaqId')) document.getElementById('contactFaqId').value = faq.id;
                if (document.getElementById('contactFaqModalTitle')) document.getElementById('contactFaqModalTitle').textContent = 'Edit FAQ Item';
                if (document.getElementById('contactFaqSubmitBtnText')) document.getElementById('contactFaqSubmitBtnText').textContent = 'Update FAQ';
                if (document.getElementById('contactFaqQuestion')) document.getElementById('contactFaqQuestion').value = faq.question || faq.title || '';
                if (document.getElementById('contactFaqAnswer')) document.getElementById('contactFaqAnswer').value = faq.answer || faq.description || '';
                if (document.getElementById('contactFaqSortOrder')) document.getElementById('contactFaqSortOrder').value = faq.sort_order || 1;
                if (document.getElementById('contactFaqActive')) document.getElementById('contactFaqActive').checked = faq.is_active !== false;
            }
        } catch (err) {
            console.error('Failed to load FAQ details', err);
        }
    }

    function closeContactFaqModal() {
        const modal = document.getElementById('contactFaqModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    async function handleContactFaqSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('contactFaqSubmitBtn');
        const id = document.getElementById('contactFaqId')?.value;
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('contactFaqForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const url = id ? `${adminMediaBase}/contact/faqs/items/${id}` : `${adminMediaBase}/contact/faqs/items`;

        try {
            const res = await axios.post(url, formData, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'FAQ item saved successfully.');
                closeContactFaqModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save FAQ.');
            }
        } catch (err) {
            console.error('Failed to save FAQ', err);
            toastr.error(err.response?.data?.message || 'Failed to save FAQ.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span id="contactFaqSubmitBtnText">Save FAQ</span>';
            }
        }
    }

    async function deleteContactFaq(id) {
        if (!confirm('Are you sure you want to delete this FAQ?')) return;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let res;
            try {
                res = await axios.delete(`${adminMediaBase}/contact/faqs/items/${id}`, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            } catch (delErr) {
                res = await axios.post(`${adminMediaBase}/contact/faqs/items/${id}/delete`, {
                    _token: csrfToken || '',
                    _method: 'DELETE'
                }, {
                    headers: { 'X-CSRF-TOKEN': csrfToken || '' }
                });
            }
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'FAQ deleted.');
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to delete FAQ.');
            }
        } catch (err) {
            console.error('Delete FAQ error', err);
            toastr.error(err.response?.data?.message || 'Failed to delete FAQ.');
        }
    }

    async function toggleContactFaq(id) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await axios.post(`${adminMediaBase}/contact/faqs/items/${id}/toggle`, {
                _token: csrfToken || ''
            }, {
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Status updated.');
                broadcastMediaUpdate();
                loadManagerData();
            }
        } catch (err) {
            toastr.error('Failed to toggle status.');
        }
    }

    // ==========================================
    // CUSTOM CROCHET BANNER & HANGING TAG HANDLERS
    // ==========================================
    function populateCustomCrochetForm(c) {
        if (!c) return;
        try {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };
            const setCheck = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.checked = !!val;
            };

            setVal('customCrochetTitle', c.title || 'Custom Crochet');
            setVal('customCrochetSubtitle', c.subtitle || 'Just for You');
            setVal('customCrochetDescription', c.description || "Your imagination, our yarn. Let's create something special together.");
            setVal('customCrochetTagText', c.tag_text || 'Turn Your Ideas Into Handmade Reality');
            setCheck('customCrochetTagActive', c.tag_active !== false);
            setVal('customCrochetCtaText', c.cta_text || 'Request Your Custom Order');
            setVal('customCrochetCtaLink', c.cta_link || '/custom-order');
            setVal('customCrochetAlt', c.alt_text || 'Custom Crochet Banner');
            setCheck('customCrochetActive', c.is_active !== false);

            const imgUrl = c.image_url || c.desktop_image || c.image || '';
            setVal('customCrochetImageUrl', imgUrl);

            if (imgUrl && document.getElementById('customCrochetPreviewImg')) {
                document.getElementById('customCrochetPreviewImg').src = imgUrl;
                if (document.getElementById('customCrochetFileName')) {
                    document.getElementById('customCrochetFileName').textContent = String(imgUrl).split('/').pop() || 'Banner Visual';
                }
                if (document.getElementById('customCrochetPreviewContainer')) {
                    document.getElementById('customCrochetPreviewContainer').classList.remove('hidden');
                    document.getElementById('customCrochetPreviewContainer').style.display = 'flex';
                }
            }
        } catch (err) {
            console.error('populateCustomCrochetForm error', err);
        }
    }

    async function openCustomCrochetModal() {
        const modal = document.getElementById('customCrochetModal');
        if (!modal) {
            console.error('customCrochetModal element not found');
            return;
        }

        // Force modal visible immediately
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Immediately populate from cached managerData if available
        try {
            let cData = null;
            if (managerData && managerData.sections) {
                const sec = managerData.sections.find(s => s.id === 'custom_crochet' || s.is_custom_crochet_section);
                if (sec && sec.metadata) cData = sec.metadata;
            }
            if (cData) {
                populateCustomCrochetForm(cData);
            }
        } catch (e) {
            console.warn('Pre-population from managerData failed:', e);
        }

        try {
            const res = await axios.get(`${adminMediaBase}/homepage/custom-crochet`);
            if (res.data && res.data.success) {
                const c = res.data.data || {};
                populateCustomCrochetForm(c);
            }
        } catch (err) {
            console.warn('Background sync of Custom Crochet settings failed', err);
        }
    }

    function closeCustomCrochetModal() {
        const modal = document.getElementById('customCrochetModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleCustomCrochetFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('customCrochetPreviewImg');
                if (previewImg) previewImg.src = e.target.result;
                const fileName = document.getElementById('customCrochetFileName');
                if (fileName) fileName.textContent = file.name;
                const container = document.getElementById('customCrochetPreviewContainer');
                if (container) {
                    container.classList.remove('hidden');
                    container.style.display = 'flex';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    function clearCustomCrochetFileInput() {
        const fileInput = document.getElementById('customCrochetFileInput');
        if (fileInput) fileInput.value = '';
        const imgUrl = document.getElementById('customCrochetImageUrl');
        if (imgUrl) imgUrl.value = '';
        const container = document.getElementById('customCrochetPreviewContainer');
        if (container) {
            container.classList.add('hidden');
            container.style.display = 'none';
        }
    }

    async function handleCustomCrochetSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('customCrochetSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('customCrochetForm');
        const formData = new FormData(form);
        const isActiveCheck = document.getElementById('customCrochetActive');
        if (isActiveCheck) {
            formData.set('is_active', isActiveCheck.checked ? '1' : '0');
        }
        const isTagActiveCheck = document.getElementById('customCrochetTagActive');
        if (isTagActiveCheck) {
            formData.set('tag_active', isTagActiveCheck.checked ? '1' : '0');
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        try {
            const res = await axios.post(`${adminMediaBase}/homepage/custom-crochet`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Custom Crochet Banner updated successfully!');
                closeCustomCrochetModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data.message || 'Failed to save Custom Crochet Banner.');
            }
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to save Custom Crochet Banner.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Custom Crochet Banner</span>';
            }
        }
    }

    // Expose all Custom Crochet functions directly on window
    window.openCustomCrochetModal = openCustomCrochetModal;
    window.closeCustomCrochetModal = closeCustomCrochetModal;
    window.populateCustomCrochetForm = populateCustomCrochetForm;
    window.handleCustomCrochetFileChange = handleCustomCrochetFileChange;
    window.clearCustomCrochetFileInput = clearCustomCrochetFileInput;
    window.handleCustomCrochetSubmit = handleCustomCrochetSubmit;

    // ==========================================
    // BRAND STORY SECTION HANDLERS ("Every Stitch Has a Story")
    // ==========================================
    function populateBrandStoryForm(data) {
        if (!data) return;
        try {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = (val !== null && val !== undefined) ? val : '';
            };
            const setCheck = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.checked = !!val;
            };

            setVal('brandStoryBadge', data.badge || data.tag_text || 'KNOTELLE Artisanal Crochet Craftsmanship');
            setVal('brandStoryTitle', data.title || 'Every Stitch');
            setVal('brandStorySubtitle', data.subtitle || 'Has a Story');
            setVal('brandStoryDescription', data.description || 'More than just crochet, we create memories, happiness and a little bit of magic.');
            setVal('brandStoryCtaText', data.cta_text || 'Read Our Story');
            setVal('brandStoryCtaLink', data.cta_link || '/about');
            setCheck('brandStoryActive', data.is_active !== false && data.is_active !== 0 && data.is_active !== '0');

            // Populate features
            const features = Array.isArray(data.features) ? data.features : [];
            const defaultFeatures = [
                'Handmade with Love',
                'Premium Yarn Quality',
                '100% Pure Natural Cotton',
                'Happiness Guaranteed'
            ];
            for (let i = 1; i <= 4; i++) {
                const feat = features[i - 1];
                const title = (feat && (feat.title || feat.text)) ? (feat.title || feat.text) : defaultFeatures[i - 1];
                setVal(`brandStoryFeature${i}`, title);
            }

            // Background Image URL & preview
            let imgUrl = data.image_url || data.desktop_image || data.image || '';
            const knottelePrefix = window.location.pathname.startsWith('/knottele') ? '/knottele' : '';
            if (imgUrl && imgUrl.startsWith('/') && !imgUrl.startsWith(knottelePrefix) && knottelePrefix) {
                imgUrl = knottelePrefix + imgUrl;
            }
            setVal('brandStoryImageUrl', imgUrl);

            const previewImg = document.getElementById('brandStoryPreviewImg');
            if (previewImg && imgUrl) {
                previewImg.src = imgUrl;
            }
            const fileName = document.getElementById('brandStoryFileName');
            if (fileName && imgUrl) {
                fileName.textContent = String(imgUrl).split('/').pop() || 'Background Visual';
            }
            const previewContainer = document.getElementById('brandStoryPreviewContainer');
            if (previewContainer) {
                if (imgUrl) {
                    previewContainer.classList.remove('hidden');
                    previewContainer.style.display = 'flex';
                }
            }
        } catch (err) {
            console.error('populateBrandStoryForm error', err);
        }
    }

    async function openBrandStoryModal() {
        const modal = document.getElementById('brandStoryModal');
        if (!modal) {
            console.error('brandStoryModal element not found');
            return;
        }

        // Force modal visible immediately
        modal.style.setProperty('display', 'flex', 'important');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Immediately populate from cached managerData if available
        try {
            let bsData = null;
            if (managerData && managerData.sections) {
                const sec = managerData.sections.find(s => s.id === 'brand_story' || s.is_brand_story_section);
                if (sec && sec.metadata) bsData = sec.metadata;
            }
            if (bsData) {
                populateBrandStoryForm(bsData);
            }
        } catch (e) {
            console.warn('Pre-population from managerData failed:', e);
        }

        // Background sync with API
        try {
            const res = await axios.get(`${adminMediaBase}/homepage/brand-story`);
            if (res.data && res.data.success) {
                populateBrandStoryForm(res.data.data || {});
            }
        } catch (err) {
            console.warn('Background sync of Brand Story settings failed', err);
        }
    }

    function closeBrandStoryModal() {
        const modal = document.getElementById('brandStoryModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function handleBrandStoryFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('brandStoryPreviewImg');
                if (previewImg) previewImg.src = e.target.result;
                const fileName = document.getElementById('brandStoryFileName');
                if (fileName) fileName.textContent = file.name;
                const container = document.getElementById('brandStoryPreviewContainer');
                if (container) {
                    container.classList.remove('hidden');
                    container.style.display = 'flex';
                }
                const label = document.getElementById('brandStoryFileLabel');
                if (label) label.textContent = 'Selected: ' + file.name;
            };
            reader.readAsDataURL(file);
        }
    }

    function clearBrandStoryFileInput() {
        const fileInput = document.getElementById('brandStoryFileInput');
        if (fileInput) fileInput.value = '';
        const imgUrl = document.getElementById('brandStoryImageUrl');
        if (imgUrl) imgUrl.value = '';
        const label = document.getElementById('brandStoryFileLabel');
        if (label) label.textContent = 'Upload Background Image';
        const container = document.getElementById('brandStoryPreviewContainer');
        if (container) {
            container.classList.add('hidden');
            container.style.display = 'none';
        }
    }

    async function handleBrandStorySubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('brandStorySubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('brandStoryForm');
        const formData = new FormData(form);
        const isActiveCheck = document.getElementById('brandStoryActive');
        if (isActiveCheck) {
            formData.set('is_active', isActiveCheck.checked ? '1' : '0');
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        try {
            const res = await axios.post(`${adminMediaBase}/homepage/brand-story`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data.success) {
                toastr.success(res.data.message || 'Brand Story section updated successfully!');
                closeBrandStoryModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data.message || 'Failed to save Brand Story section.');
            }
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to save Brand Story section.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Brand Story</span>';
            }
        }
    }

    // Expose all Brand Story functions directly on window
    window.openBrandStoryModal = openBrandStoryModal;
    window.closeBrandStoryModal = closeBrandStoryModal;
    window.populateBrandStoryForm = populateBrandStoryForm;
    window.handleBrandStoryFileChange = handleBrandStoryFileChange;
    window.clearBrandStoryFileInput = clearBrandStoryFileInput;
    window.handleBrandStorySubmit = handleBrandStorySubmit;

    // ==========================================
    // ==========================================
    // 17. FOOTER SETTINGS & NAVIGATION HANDLERS
    // ==========================================
    let footerCol1LinkIndex = 0;
    let footerCol2LinkIndex = 0;

    function populateFooterSettingsForm(f) {
        if (!f) return;
        try {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };
            const setCheck = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.checked = !!val;
            };

            setVal('footerCol1Title', f.col1_title || 'Quick Links');
            setVal('footerCol2Title', f.col2_title || 'Help');
            setVal('footerCol3Title', f.col3_title || 'Contact');

            setVal('footerContactPhone', f.contact_phone || '+91 97730 39243');
            setVal('footerContactPhoneLink', f.contact_phone_link || 'tel:+919773039243');
            setVal('footerContactEmail', f.contact_email || 'support@knotelle.in');
            setVal('footerContactEmailLink', f.contact_email_link || 'mailto:support@knotelle.in');
            setVal('footerContactAddress', f.contact_address || 'India');

            setVal('footerInstagramUrl', f.instagram_url || 'https://instagram.com/knotelleindia');
            setCheck('footerInstagramActive', f.instagram_active !== false);
            setVal('footerFacebookUrl', f.facebook_url || 'https://facebook.com/knotelleindia');
            setCheck('footerFacebookActive', f.facebook_active !== false);
            setVal('footerPinterestUrl', f.pinterest_url || 'https://pinterest.com/knotelleindia');
            setCheck('footerPinterestActive', f.pinterest_active !== false);
            setVal('footerYouTubeUrl', f.youtube_url || 'https://youtube.com/@knotelleindia');
            setCheck('footerYouTubeActive', f.youtube_active !== false);
            setVal('footerTwitterUrl', f.twitter_url || '');
            setCheck('footerTwitterActive', f.twitter_active === true || (f.twitter_url && f.twitter_url.length > 0));
            setVal('footerLinkedinUrl', f.linkedin_url || '');
            setCheck('footerLinkedinActive', f.linkedin_active === true || (f.linkedin_url && f.linkedin_url.length > 0));

            setVal('footerCopyrightText', f.copyright_text || '© {year} Knotelle. All rights reserved.');
            setVal('footerHeartTagline', f.heart_tagline || 'Made with ♡ for a kinder, cozier world.');
            setCheck('footerSectionActive', f.is_active !== false);

            // Background image
            const bgImg = f.bg_image_url || f.bg_image || f.desktop_image || '';
            setVal('footerBgImageUrl', bgImg);
            const previewImg = document.getElementById('footerBgPreviewImg');
            const previewContainer = document.getElementById('footerBgPreviewContainer');
            const fileName = document.getElementById('footerBgFileName');

            if (bgImg && previewImg) {
                previewImg.src = bgImg;
                if (fileName) fileName.textContent = String(bgImg).split('/').pop() || 'Background Active';
                if (previewContainer) {
                    previewContainer.classList.remove('hidden');
                    previewContainer.style.display = 'flex';
                }
            } else if (previewContainer) {
                previewContainer.classList.add('hidden');
                previewContainer.style.display = 'none';
            }

            // Render Column 1 links
            const col1Container = document.getElementById('footerCol1LinksContainer');
            if (col1Container) {
                col1Container.innerHTML = '';
                footerCol1LinkIndex = 0;
                const col1Links = Array.isArray(f.col1_links) && f.col1_links.length > 0 ? f.col1_links.filter(l => !l.url?.includes('custom') && !l.label?.toLowerCase()?.includes('custom')) : [
                    { label: 'Home', url: '/', is_active: true },
                    { label: 'Shop', url: '/shop', is_active: true },
                    { label: 'About', url: '/about', is_active: true },
                    { label: 'Contact', url: '/contact', is_active: true },
                ];
                col1Links.forEach(link => addFooterCol1Link(link));
            }

            // Render Column 2 links
            const col2Container = document.getElementById('footerCol2LinksContainer');
            if (col2Container) {
                col2Container.innerHTML = '';
                footerCol2LinkIndex = 0;
                const col2Links = Array.isArray(f.col2_links) && f.col2_links.length > 0 ? f.col2_links : [
                    { label: 'Shipping Policy', url: '/contact', is_active: true },
                    { label: 'Return & Refund', url: '/contact', is_active: true },
                    { label: 'FAQ', url: '/contact', is_active: true },
                    { label: 'Track Order', url: '/account/orders', is_active: true },
                ];
                col2Links.forEach(link => addFooterCol2Link(link));
            }
        } catch (err) {
            console.error('populateFooterSettingsForm error', err);
        }
    }

    async function openFooterSettingsModal() {
        const modal = document.getElementById('footerSettingsModal');
        if (!modal) {
            console.error('footerSettingsModal element not found');
            return;
        }

        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Immediately populate from cached managerData if available
        try {
            let fData = managerData?.footer_settings;
            if (!fData && managerData?.sections) {
                const sec = managerData.sections.find(s => s.id === 'footer' || s.is_footer_section);
                if (sec && sec.metadata) fData = sec.metadata;
            }
            if (fData) {
                populateFooterSettingsForm(fData);
            }
        } catch (e) {
            console.warn('Pre-population from managerData failed:', e);
        }

        try {
            const res = await axios.get(`${adminMediaBase}/footer/settings`);
            if (res.data && res.data.success) {
                const f = res.data.data || {};
                populateFooterSettingsForm(f);
            }
        } catch (err) {
            console.warn('Background sync of footer settings failed', err);
        }
    }

    function closeFooterSettingsModal() {
        const modal = document.getElementById('footerSettingsModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function addFooterCol1Link(data = { label: '', url: '', is_active: true }) {
        const container = document.getElementById('footerCol1LinksContainer');
        if (!container) return;
        const idx = footerCol1LinkIndex++;
        const row = document.createElement('div');
        row.className = 'p-2 sm:p-2.5 bg-white rounded-xl border border-stone-200 text-xs w-full min-w-0 space-y-2 footer-col1-row';
        row.id = `footer_col1_row_${idx}`;
        row.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-2 w-full min-w-0">
                <input type="text" name="col1_links[${idx}][label]" value="${escapeHtml(data.label || data.name || '')}" placeholder="Link Label (e.g. Shop)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-stone-800 focus:ring-1 focus:ring-red-500">
                <input type="text" name="col1_links[${idx}][url]" value="${escapeHtml(data.url || data.href || '')}" placeholder="URL (e.g. /shop)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-mono text-stone-700 focus:ring-1 focus:ring-red-500">
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-3 pt-1 border-t sm:border-t-0 border-stone-100">
                <label class="flex items-center gap-1.5 text-xs font-bold text-stone-600 cursor-pointer">
                    <input type="checkbox" name="col1_links[${idx}][is_active]" value="1" ${data.is_active !== false ? 'checked' : ''} class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                    <span>Active</span>
                </label>
                <button type="button" onclick="document.getElementById('footer_col1_row_${idx}')?.remove()" class="text-stone-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded-lg flex items-center gap-1 text-xs transition-colors cursor-pointer">
                    <i class="fas fa-trash-alt text-xs"></i> <span>Remove</span>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function addFooterCol2Link(data = { label: '', url: '', is_active: true }) {
        const container = document.getElementById('footerCol2LinksContainer');
        if (!container) return;
        const idx = footerCol2LinkIndex++;
        const row = document.createElement('div');
        row.className = 'p-2 sm:p-2.5 bg-white rounded-xl border border-stone-200 text-xs w-full min-w-0 space-y-2 footer-col2-row';
        row.id = `footer_col2_row_${idx}`;
        row.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-2 w-full min-w-0">
                <input type="text" name="col2_links[${idx}][label]" value="${escapeHtml(data.label || data.name || '')}" placeholder="Link Label (e.g. FAQ)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-stone-800 focus:ring-1 focus:ring-red-500">
                <input type="text" name="col2_links[${idx}][url]" value="${escapeHtml(data.url || data.href || '')}" placeholder="URL (e.g. /contact)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-mono text-stone-700 focus:ring-1 focus:ring-red-500">
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-3 pt-1 border-t sm:border-t-0 border-stone-100">
                <label class="flex items-center gap-1.5 text-xs font-bold text-stone-600 cursor-pointer">
                    <input type="checkbox" name="col2_links[${idx}][is_active]" value="1" ${data.is_active !== false ? 'checked' : ''} class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                    <span>Active</span>
                </label>
                <button type="button" onclick="document.getElementById('footer_col2_row_${idx}')?.remove()" class="text-stone-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded-lg flex items-center gap-1 text-xs transition-colors cursor-pointer">
                    <i class="fas fa-trash-alt text-xs"></i> <span>Remove</span>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    function handleFooterBgFileChange(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const imgUrlInput = document.getElementById('footerBgImageUrl');
            if (imgUrlInput) imgUrlInput.value = '';
            const statusLabel = document.getElementById('footerBgStatusLabel');
            if (statusLabel) statusLabel.textContent = 'Selected new upload (Click Save to apply)';
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('footerBgPreviewImg');
                if (previewImg) previewImg.src = e.target.result;
                const fileName = document.getElementById('footerBgFileName');
                if (fileName) fileName.textContent = file.name;
                const previewContainer = document.getElementById('footerBgPreviewContainer');
                if (previewContainer) {
                    previewContainer.classList.remove('hidden');
                    previewContainer.style.display = 'flex';
                }
            };
            reader.readAsDataURL(file);
        }
    }

    function clearFooterBgFileInput() {
        const fileInput = document.getElementById('footerBgFileInput');
        if (fileInput) fileInput.value = '';
        const imgUrl = document.getElementById('footerBgImageUrl');
        if (imgUrl) imgUrl.value = '';
        const previewImg = document.getElementById('footerBgPreviewImg');
        if (previewImg) previewImg.src = '';
        const previewContainer = document.getElementById('footerBgPreviewContainer');
        if (previewContainer) {
            previewContainer.classList.add('hidden');
            previewContainer.style.display = 'none';
        }
    }

    async function handleFooterSettingsSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('footerSettingsSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Saving...';
        }

        const form = document.getElementById('footerSettingsForm');
        const formData = new FormData(form);
        const fileInput = document.getElementById('footerBgFileInput');
        if (fileInput && (!fileInput.files || fileInput.files.length === 0)) {
            formData.delete('bg_image_file');
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        try {
            // Note: Do not specify 'Content-Type': 'multipart/form-data'; axios will automatically
            // set multipart/form-data with the browser-generated boundary
            const res = await axios.post(`${adminMediaBase}/footer/settings`, formData, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Footer settings updated successfully!');
                closeFooterSettingsModal();
                broadcastMediaUpdate();
                if (typeof loadManagerData === 'function') {
                    loadManagerData();
                }
            } else {
                toastr.error(res.data?.message || 'Failed to save footer settings.');
            }
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to save footer settings.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-1.5"></i><span>Save Footer Settings</span>';
            }
        }
    }

    // Expose all Footer functions directly on window
    window.openFooterSettingsModal = openFooterSettingsModal;
    window.closeFooterSettingsModal = closeFooterSettingsModal;
    window.populateFooterSettingsForm = populateFooterSettingsForm;
    window.addFooterCol1Link = addFooterCol1Link;
    window.addFooterCol2Link = addFooterCol2Link;
    window.handleFooterBgFileChange = handleFooterBgFileChange;
    window.clearFooterBgFileInput = clearFooterBgFileInput;
    window.handleFooterSettingsSubmit = handleFooterSettingsSubmit;

    // ==========================================
    // 18. NAVBAR & HEADER SETTINGS HANDLERS
    // ==========================================
    let navbarLinkIndex = 0;

    function populateNavbarSettingsForm(n) {
        if (!n) return;
        try {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = val;
            };
            const setCheck = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.checked = !!val;
            };

            setVal('navbarAnnouncementText', n.announcement_text || '✨ Free Pan-India Delivery on all Orders above ₹999');
            setVal('navbarAnnouncementLink', n.announcement_link || '/shop');
            setCheck('navbarAnnouncementActive', n.announcement_active === true);

            setCheck('navbarShowSearch', n.show_search !== false);
            setCheck('navbarShowWishlist', n.show_wishlist !== false);
            setCheck('navbarShowAccount', n.show_account !== false);
            setCheck('navbarShowCart', n.show_cart !== false);
            setCheck('navbarSectionActive', n.is_active !== false);

            // Render Nav links
            const container = document.getElementById('navbarLinksContainer');
            if (container) {
                container.innerHTML = '';
                navbarLinkIndex = 0;
                const links = Array.isArray(n.nav_links) && n.nav_links.length > 0 ? n.nav_links.filter(l => !l.href?.includes('custom') && !l.name?.toLowerCase()?.includes('custom')) : [
                    { name: 'Home', href: '/', is_highlighted: false, is_active: true },
                    { name: 'Shop', href: '/shop', is_highlighted: false, is_active: true },
                    { name: 'About', href: '/about', is_highlighted: false, is_active: true },
                    { name: 'Contact', href: '/contact', is_highlighted: false, is_active: true },
                ];
                links.forEach(link => addNavbarLinkRow(link));
            }
        } catch (err) {
            console.error('populateNavbarSettingsForm error', err);
        }
    }

    async function openNavbarSettingsModal() {
        const modal = document.getElementById('navbarSettingsModal');
        if (!modal) {
            console.error('navbarSettingsModal element not found');
            return;
        }

        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
        modal.classList.add('flex');

        try {
            let nData = managerData?.navbar_settings;
            if (!nData && managerData?.sections) {
                const sec = managerData.sections.find(s => s.id === 'navbar_settings');
                if (sec && sec.metadata) nData = sec.metadata;
            }
            if (nData) {
                populateNavbarSettingsForm(nData);
            }
        } catch (e) {
            console.warn('Pre-population from managerData failed:', e);
        }

        try {
            const res = await axios.get(`${adminMediaBase}/navbar/settings`);
            if (res.data && res.data.success) {
                const n = res.data.data || {};
                populateNavbarSettingsForm(n);
            }
        } catch (err) {
            console.warn('Background sync of navbar settings failed', err);
        }
    }

    function closeNavbarSettingsModal() {
        const modal = document.getElementById('navbarSettingsModal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function addNavbarLinkRow(data = { name: '', href: '', is_highlighted: false, is_active: true }) {
        const container = document.getElementById('navbarLinksContainer');
        if (!container) return;
        const idx = navbarLinkIndex++;
        const row = document.createElement('div');
        row.className = 'p-2 sm:p-2.5 bg-white rounded-xl border border-stone-200 text-xs w-full min-w-0 space-y-2 navbar-link-row';
        row.id = `navbar_link_row_${idx}`;
        row.innerHTML = `
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 sm:gap-2 w-full min-w-0">
                <input type="text" name="nav_links[${idx}][name]" value="${escapeHtml(data.name || data.label || '')}" placeholder="Nav Name (e.g. Shop)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-bold text-stone-800 focus:ring-1 focus:ring-red-500">
                <input type="text" name="nav_links[${idx}][href]" value="${escapeHtml(data.href || data.url || '')}" placeholder="URL Href (e.g. /shop)" class="w-full min-w-0 bg-stone-50 border border-stone-200 rounded-lg px-2.5 py-1.5 text-xs font-mono text-stone-700 focus:ring-1 focus:ring-red-500">
            </div>
            <div class="flex items-center justify-between sm:justify-end gap-2.5 pt-1 border-t sm:border-t-0 border-stone-100 flex-wrap">
                <label class="flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200 cursor-pointer" title="Highlight with Sparkle Pill Style">
                    <input type="checkbox" name="nav_links[${idx}][is_highlighted]" value="1" ${data.is_highlighted ? 'checked' : ''} class="w-3.5 h-3.5 rounded text-amber-600 focus:ring-amber-500">
                    <span>Sparkle Pill</span>
                </label>
                <label class="flex items-center gap-1.5 text-xs font-bold text-stone-600 cursor-pointer">
                    <input type="checkbox" name="nav_links[${idx}][is_active]" value="1" ${data.is_active !== false ? 'checked' : ''} class="w-4 h-4 rounded text-red-600 focus:ring-red-500">
                    <span>Active</span>
                </label>
                <button type="button" onclick="document.getElementById('navbar_link_row_${idx}')?.remove()" class="text-stone-400 hover:text-red-600 hover:bg-red-50 px-2 py-1 rounded-lg flex items-center gap-1 text-xs transition-colors cursor-pointer">
                    <i class="fas fa-trash-alt text-xs"></i> <span>Remove</span>
                </button>
            </div>
        `;
        container.appendChild(row);
    }

    async function handleNavbarSettingsSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('navbarSettingsSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        }

        const form = document.getElementById('navbarSettingsForm');
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken && !formData.has('_token')) {
            formData.append('_token', csrfToken);
        }

        try {
            const res = await axios.post(`${adminMediaBase}/navbar/settings`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            if (res.data && res.data.success) {
                toastr.success(res.data.message || 'Navbar settings updated successfully!');
                closeNavbarSettingsModal();
                broadcastMediaUpdate();
                loadManagerData();
            } else {
                toastr.error(res.data?.message || 'Failed to save navbar settings.');
            }
        } catch (err) {
            toastr.error(err.response?.data?.message || 'Failed to save navbar settings.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i><span>Save Navbar Settings</span>';
            }
        }
    }

    // Expose all Navbar functions directly on window
    window.openNavbarSettingsModal = openNavbarSettingsModal;
    window.closeNavbarSettingsModal = closeNavbarSettingsModal;
    window.populateNavbarSettingsForm = populateNavbarSettingsForm;
    window.addNavbarLinkRow = addNavbarLinkRow;
    window.handleNavbarSettingsSubmit = handleNavbarSettingsSubmit;


    // =========================================================================
    // EXPOSE ALL MEDIA MANAGER FUNCTIONS TO GLOBAL WINDOW SCOPE
    // =========================================================================
    window.adminMediaBase = adminMediaBase;
    window.loadManagerData = loadManagerData;
    window.refreshLibraryData = refreshLibraryData;
    window.switchView = switchView;
    window.onPageFilterChange = onPageFilterChange;
    window.openGenericUploadModal = openGenericUploadModal;

    // Slot Controls
    window.handleSlotPreviewClick = handleSlotPreviewClick;
    window.handleSlotReplaceClick = handleSlotReplaceClick;
    window.handleSlotOptionsClick = handleSlotOptionsClick;
    window.handleSlotUploadSubmit = handleSlotUploadSubmit;
    window.handleSlotFileChange = handleSlotFileChange;
    window.clearSlotFileInput = clearSlotFileInput;
    window.closeSlotUploadModal = closeSlotUploadModal;
    window.detachSlot = detachSlot;

    // Metadata Modal (Edit Options / Text Sections)
    window.openTextSectionEditModal = openTextSectionEditModal;
    window.closeEditMetadataModal = closeEditMetadataModal;
    window.handleMetadataSubmit = handleMetadataSubmit;

    // Hero Slides
    window.openAddHeroSlideModal = openAddHeroSlideModal;
    window.openEditHeroSlideModal = openEditHeroSlideModal;
    window.closeHeroSlideModal = closeHeroSlideModal;
    window.handleHeroSlideSubmit = handleHeroSlideSubmit;
    window.deleteHeroSlide = deleteHeroSlide;
    window.handleHeroDesktopFileChange = handleHeroDesktopFileChange;
    window.handleHeroMobileFileChange = handleHeroMobileFileChange;
    window.clearHeroDesktopFileInput = clearHeroDesktopFileInput;
    window.clearHeroMobileFileInput = clearHeroMobileFileInput;

    // Categories
    window.openCategoryUploadModal = openCategoryUploadModal;
    window.renderCategoryCard = renderCategoryCard;

    // Video Reels & Blog
    window.openBlogReelsSettingsModal = openBlogReelsSettingsModal;
    window.closeBlogReelsSettingsModal = closeBlogReelsSettingsModal;
    window.handleBlogReelsSettingsSubmit = handleBlogReelsSettingsSubmit;
    window.openAddVideoReelModal = openAddVideoReelModal;
    window.openEditVideoReelModal = openEditVideoReelModal;
    window.closeVideoReelModal = closeVideoReelModal;
    window.openVideoWatchModal = openVideoWatchModal;
    window.closeVideoWatchModal = closeVideoWatchModal;
    window.handleVideoReelSubmit = handleVideoReelSubmit;
    window.deleteVideoReel = deleteVideoReel;
    window.toggleVideoReelStatus = toggleVideoReelStatus;
    window.setReelCategory = setReelCategory;
    window.clearReelCoverInput = clearReelCoverInput;
    window.handleVideoFileInputChange = handleVideoFileInputChange;
    window.handleVideoUrlInputChange = handleVideoUrlInputChange;
    window.handleReelCoverInputChange = handleReelCoverInputChange;
    window.handleVideoPlayerCanPlay = handleVideoPlayerCanPlay;
    window.handleVideoPlayerError = handleVideoPlayerError;
    window.renderVideoReelCard = renderVideoReelCard;

    // Testimonials
    window.openAddTestimonialModal = openAddTestimonialModal;
    window.openEditTestimonialModal = openEditTestimonialModal;
    window.closeTestimonialModal = closeTestimonialModal;
    window.handleTestimonialSubmit = handleTestimonialSubmit;
    window.deleteTestimonial = deleteTestimonial;

    // Custom Crochet
    window.openCustomCrochetModal = openCustomCrochetModal;
    window.closeCustomCrochetModal = closeCustomCrochetModal;
    window.handleCustomCrochetSubmit = handleCustomCrochetSubmit;
    window.handleCustomCrochetFileChange = handleCustomCrochetFileChange;
    window.clearCustomCrochetFileInput = clearCustomCrochetFileInput;

    // Homepage Brand Story ("Every Stitch Has a Story")
    window.openBrandStoryModal = openBrandStoryModal;
    window.closeBrandStoryModal = closeBrandStoryModal;
    window.populateBrandStoryForm = populateBrandStoryForm;
    window.handleBrandStorySubmit = handleBrandStorySubmit;
    window.handleBrandStoryFileChange = handleBrandStoryFileChange;
    window.clearBrandStoryFileInput = clearBrandStoryFileInput;

    // About Us - Brand Story
    window.openAboutStoryModal = openAboutStoryModal;
    window.closeAboutStoryModal = closeAboutStoryModal;
    window.handleAboutStorySubmit = handleAboutStorySubmit;
    window.handleAboutDesktopFileChange = handleAboutDesktopFileChange;
    window.handleAboutMobileFileChange = handleAboutMobileFileChange;
    window.clearAboutDesktopFileInput = clearAboutDesktopFileInput;
    window.clearAboutMobileFileInput = clearAboutMobileFileInput;

    // About Us - Craft Pillars
    window.openCraftPillarsHeaderModal = openCraftPillarsHeaderModal;
    window.closeCraftPillarsHeaderModal = closeCraftPillarsHeaderModal;
    window.handleCraftPillarsHeaderSubmit = handleCraftPillarsHeaderSubmit;
    window.openAddCraftPillarModal = openAddCraftPillarModal;
    window.openEditCraftPillarModal = openEditCraftPillarModal;
    window.closeCraftPillarModal = closeCraftPillarModal;
    window.handleCraftPillarSubmit = handleCraftPillarSubmit;
    window.deleteCraftPillar = deleteCraftPillar;
    window.toggleCraftPillar = toggleCraftPillar;
    window.selectPillarIcon = selectPillarIcon;

    // Custom Order Steps / Items
    window.openAddCustomOrderItemModal = openAddCustomOrderItemModal;
    window.openEditCustomOrderItemModal = openEditCustomOrderItemModal;
    window.closeCustomOrderItemModal = closeCustomOrderItemModal;
    window.handleCustomOrderItemSubmit = handleCustomOrderItemSubmit;
    window.deleteCustomOrderItem = deleteCustomOrderItem;
    window.toggleCustomOrderItem = toggleCustomOrderItem;

    // Contact Us - Intro
    window.openContactIntroModal = openContactIntroModal;
    window.closeContactIntroModal = closeContactIntroModal;
    window.handleContactIntroSubmit = handleContactIntroSubmit;
    window.handleContactIntroFileChange = handleContactIntroFileChange;
    window.clearContactIntroFileInput = clearContactIntroFileInput;

    // Contact Us - Info Channels
    window.openContactInfoHeaderModal = openContactInfoHeaderModal;
    window.closeContactInfoHeaderModal = closeContactInfoHeaderModal;
    window.handleContactInfoHeaderSubmit = handleContactInfoHeaderSubmit;
    window.openAddContactInfoItemModal = openAddContactInfoItemModal;
    window.openEditContactInfoItemModal = openEditContactInfoItemModal;
    window.closeContactInfoItemModal = closeContactInfoItemModal;
    window.handleContactInfoItemSubmit = handleContactInfoItemSubmit;
    window.deleteContactInfoItem = deleteContactInfoItem;
    window.toggleContactInfoItem = toggleContactInfoItem;
    window.selectContactInfoIcon = selectContactInfoIcon;

    // Contact Us - Form Settings
    window.openContactFormModal = openContactFormModal;
    window.closeContactFormModal = closeContactFormModal;
    window.handleContactFormSubmit = handleContactFormSubmit;

    // Contact Us - FAQs
    window.openContactFaqsHeaderModal = openContactFaqsHeaderModal;
    window.closeContactFaqsHeaderModal = closeContactFaqsHeaderModal;
    window.handleContactFaqsHeaderSubmit = handleContactFaqsHeaderSubmit;
    window.openAddContactFaqModal = openAddContactFaqModal;
    window.openEditContactFaqModal = openEditContactFaqModal;
    window.closeContactFaqModal = closeContactFaqModal;
    window.handleContactFaqSubmit = handleContactFaqSubmit;
    window.deleteContactFaq = deleteContactFaq;
    window.toggleContactFaq = toggleContactFaq;

    // Footer Settings
    window.openFooterSettingsModal = openFooterSettingsModal;
    window.closeFooterSettingsModal = closeFooterSettingsModal;
    window.handleFooterSettingsSubmit = handleFooterSettingsSubmit;
    window.handleFooterBgFileChange = handleFooterBgFileChange;
    window.clearFooterBgFileInput = clearFooterBgFileInput;
    window.addFooterCol1Link = addFooterCol1Link;
    window.addFooterCol2Link = addFooterCol2Link;

    // Navbar Settings
    window.openNavbarSettingsModal = openNavbarSettingsModal;
    window.closeNavbarSettingsModal = closeNavbarSettingsModal;
    window.handleNavbarSettingsSubmit = handleNavbarSettingsSubmit;
    window.addNavbarLinkRow = addNavbarLinkRow;

    // Device Preview & Media Picker
    window.openDevicePreview = openDevicePreview;
    window.setPreviewDevice = setPreviewDevice;
    window.closeDevicePreviewModal = closeDevicePreviewModal;
    window.openMediaPicker = openMediaPicker;
    window.closeMediaPicker = closeMediaPicker;
    window.selectMediaFromPicker = selectMediaFromPicker;
    window.deleteMedia = deleteMedia;

</script>
@endpush
