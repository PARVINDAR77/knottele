"use client";

import React, { useState, useEffect, useMemo, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { Filter, X, SlidersHorizontal, ArrowUpDown, Sparkles, RotateCcw } from "lucide-react";
import { Product } from "@/types/product";
import { fetchProducts, fetchCategories, normalizeImageUrl, ApiCategory } from "@/lib/api";
import { ProductCard } from "@/components/ui/ProductCard";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { StarRating } from "@/components/ui/StarRating";
import { useWebsiteMedia } from "@/context/MediaContext";
import { getLocalCache, setLocalCache, setupAdminSyncListener } from "@/lib/cache";

const PRODUCTS_CACHE_KEY = "knotelle_cache_products";
const CATEGORIES_CACHE_KEY = "knotelle_cache_categories";

function ShopContent() {
  const searchParams = useSearchParams();
  const paramSort = searchParams.get("sort");
  const paramCategory = searchParams.get("category") || searchParams.get("filter");

  const { media } = useWebsiteMedia();
  const [productsList, setProductsList] = useState<Product[]>([]);
  const [categoriesList, setCategoriesList] = useState<any[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [selectedCategory, setSelectedCategory] = useState<string>("all");
  const [priceRange, setPriceRange] = useState<number>(6000);
  const [selectedColors, setSelectedColors] = useState<string[]>([]);
  const [minRating, setMinRating] = useState<number>(0);
  const [availabilityOnly, setAvailabilityOnly] = useState<boolean>(false);
  const [sortBy, setSortBy] = useState<string>("featured");
  const [isMobileFiltersOpen, setIsMobileFiltersOpen] = useState<boolean>(false);

  useEffect(() => {
    if (paramSort) {
      setSortBy(paramSort);
    }
    if (paramCategory) {
      setSelectedCategory(paramCategory);
    }
  }, [paramSort, paramCategory]);

  // Directly derive shop banner from media for instant zero-delay rendering
  const banner = media?.shop?.banner;
  const shopMedia = {
    desktop: banner?.desktop || null,
    mobile: banner?.mobile || null,
    title: banner?.title || "Artisanal Handcrafted Creations",
    subtitle: banner?.subtitle || "Discover unique crochet treasures woven with love, patience, and 100% natural cotton fibers.",
    tag_text: banner?.tag_text || "Handmade with Love",
    cta_text: banner?.cta_text || null,
    cta_link: banner?.cta_link || null,
  };

  // Fetch live products & categories on mount + admin sync
  useEffect(() => {
    // Populate from local cache safely after mount to prevent hydration mismatch
    const cachedProds = getLocalCache<Product[]>(PRODUCTS_CACHE_KEY, []);
    if (cachedProds && cachedProds.length > 0) {
      setProductsList(cachedProds);
      setIsLoading(false);
    }
    const cachedCats = media?.categories && media.categories.length > 0
      ? media.categories
      : getLocalCache<any[]>(CATEGORIES_CACHE_KEY, []);
    if (cachedCats && cachedCats.length > 0) {
      setCategoriesList(cachedCats);
    }

    let isMounted = true;

    const loadData = () => {
      fetchProducts({ per_page: 100 })
        .then((res) => {
          if (isMounted && res.products && res.products.length > 0) {
            setProductsList(res.products);
            setLocalCache(PRODUCTS_CACHE_KEY, res.products);
          }
        })
        .catch((err) => console.warn("Live shop products fetch notice:", err))
        .finally(() => {
          if (isMounted) setIsLoading(false);
        });

      fetchCategories()
        .then((cats) => {
          if (isMounted && cats && cats.length > 0) {
            setCategoriesList(cats);
            setLocalCache(CATEGORIES_CACHE_KEY, cats);
          }
        })
        .catch((err) => console.warn("Live categories fetch notice:", err));
    };

    loadData();
    const cleanupListeners = setupAdminSyncListener(loadData);

    return () => {
      isMounted = false;
      cleanupListeners();
    };
  }, []);

  useEffect(() => {
    if (media?.categories && media.categories.length > 0) {
      setCategoriesList(media.categories);
    }
  }, [media?.categories]);

  // Available unique color swatches from all products
  const allColorSwatches = useMemo(() => {
    const map = new Map<string, string>();
    productsList.forEach((p) => {
      if (p.colors && Array.isArray(p.colors)) {
        p.colors.forEach((c) => {
          if (!map.has(c.name)) {
            map.set(c.name, c.hex);
          }
        });
      }
    });
    return Array.from(map.entries()).map(([name, hex]) => ({ name, hex }));
  }, [productsList]);

  const toggleColor = (colorName: string) => {
    setSelectedColors((prev) =>
      prev.includes(colorName)
        ? prev.filter((c) => c !== colorName)
        : [...prev, colorName]
    );
  };

  const resetAllFilters = () => {
    setSelectedCategory("all");
    setPriceRange(6000);
    setSelectedColors([]);
    setMinRating(0);
    setAvailabilityOnly(false);
    setSortBy("featured");
  };

  const hasActiveFilters =
    selectedCategory !== "all" ||
    priceRange < 6000 ||
    selectedColors.length > 0 ||
    minRating > 0 ||
    availabilityOnly;

  // Filter and sort products
  const filteredProducts = useMemo(() => {
    return productsList.filter((product) => {
      if (selectedCategory !== "all" && product.categorySlug !== selectedCategory) {
        return false;
      }
      if (product.price > priceRange) {
        return false;
      }
      if (
        selectedColors.length > 0 &&
        !product.colors.some((c) => selectedColors.includes(c.name))
      ) {
        return false;
      }
      if (minRating > 0 && product.rating < minRating) {
        return false;
      }
      if (availabilityOnly && product.stock <= 0) {
        return false;
      }
      return true;
    }).sort((a, b) => {
      if (sortBy === "price-low") return a.price - b.price;
      if (sortBy === "price-high") return b.price - a.price;
      if (sortBy === "rating") return b.rating - a.rating;
      if (sortBy === "newest") return (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0);
      if (sortBy === "bestseller") return (b.isBestSeller ? 1 : 0) - (a.isBestSeller ? 1 : 0);
      return 0; // featured
    });
  }, [selectedCategory, priceRange, selectedColors, minRating, availabilityOnly, sortBy, productsList]);

  const filterSidebarContent = (
    <div className="space-y-6">
      {/* Categories Filter */}
      <div className="space-y-2.5">
        <h4 className="font-serif-luxury text-base font-bold text-[#3A211D]">
          Categories
        </h4>
        <div className="space-y-1">
          <button
            onClick={() => setSelectedCategory("all")}
            className={`w-full text-left px-3 py-1.5 rounded-xl text-xs font-medium transition-colors flex items-center justify-between ${
              selectedCategory === "all"
                ? "bg-[#8F3032] text-white font-semibold"
                : "text-[#78635E] hover:bg-[#FDE9E5] hover:text-[#3A211D]"
            }`}
          >
            <span>All Creations</span>
            <span>({productsList.length})</span>
          </button>
          {categoriesList.map((cat) => {
            const isSelected = selectedCategory === cat.slug;
            const count = productsList.filter((p) => p.categorySlug === cat.slug).length;
            return (
              <button
                key={cat.id || cat.slug}
                onClick={() => setSelectedCategory(cat.slug)}
                className={`w-full text-left px-3 py-1.5 rounded-xl text-xs font-medium transition-colors flex items-center justify-between ${
                  isSelected
                    ? "bg-[#8F3032] text-white font-semibold"
                    : "text-[#78635E] hover:bg-[#FDE9E5] hover:text-[#3A211D]"
                }`}
              >
                <span>{cat.name}</span>
                <span>
                  ({count > 0 ? count : (cat.itemCount || 0)})
                </span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Price Range Slider */}
      <div className="space-y-3 pt-4 border-t border-[#E8D4CF]">
        <div className="flex items-center justify-between">
          <h4 className="font-serif-luxury text-base font-bold text-[#3A211D]">
            Max Price
          </h4>
          <span className="text-xs font-bold text-[#8F3032]">
            Up to ₹{priceRange.toLocaleString("en-IN")}
          </span>
        </div>
        <input
          type="range"
          min="150"
          max="6000"
          step="50"
          value={priceRange}
          onChange={(e) => setPriceRange(Number(e.target.value))}
          className="w-full accent-[#8F3032] cursor-pointer"
        />
        <div className="flex justify-between text-[10px] text-[#78635E]">
          <span>₹150</span>
          <span>₹3,000</span>
          <span>₹6,000</span>
        </div>
      </div>

      {/* Color Filter */}
      <div className="space-y-3 pt-4 border-t border-[#E8D4CF]">
        <h4 className="font-serif-luxury text-base font-bold text-[#3A211D]">
          Yarn Color
        </h4>
        <div className="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
          {allColorSwatches.slice(0, 10).map((col) => {
            const isChecked = selectedColors.includes(col.name);
            return (
              <button
                key={col.name}
                onClick={() => toggleColor(col.name)}
                className={`flex items-center gap-2 p-1.5 rounded-lg text-[11px] text-left transition-colors border ${
                  isChecked
                    ? "border-[#8F3032] bg-[#FDE9E5] font-semibold text-[#8F3032]"
                    : "border-[#E8D4CF] hover:bg-[#FFF8F5] text-[#3A211D]"
                }`}
              >
                <span
                  className="w-3.5 h-3.5 rounded-full border border-black/10 shrink-0"
                  style={{ backgroundColor: col.hex }}
                />
                <span className="truncate">{col.name}</span>
              </button>
            );
          })}
        </div>
      </div>

      {/* Minimum Rating */}
      <div className="space-y-2.5 pt-4 border-t border-[#E8D4CF]">
        <h4 className="font-serif-luxury text-base font-bold text-[#3A211D]">
          Customer Rating
        </h4>
        <div className="space-y-1">
          {[4.9, 4.8, 4.5].map((star) => (
            <button
              key={star}
              onClick={() => setMinRating(minRating === star ? 0 : star)}
              className={`w-full flex items-center justify-between p-2 rounded-xl text-xs transition-colors ${
                minRating === star
                  ? "bg-[#FDE9E5] text-[#8F3032] font-semibold border border-[#E8D4CF]"
                  : "text-[#78635E] hover:bg-[#FFF8F5]"
              }`}
            >
              <div className="flex items-center gap-1.5">
                <StarRating rating={star} size="sm" />
                <span>{star}+ Stars</span>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Availability */}
      <div className="pt-4 border-t border-[#E8D4CF]">
        <label className="flex items-center gap-2 text-xs font-medium text-[#3A211D] cursor-pointer">
          <input
            type="checkbox"
            checked={availabilityOnly}
            onChange={(e) => setAvailabilityOnly(e.target.checked)}
            className="rounded border-[#E8D4CF] text-[#8F3032] focus:ring-[#8F3032]"
          />
          <span>In Stock Ready to Ship</span>
        </label>
      </div>

      {/* Reset Button */}
      {hasActiveFilters && (
        <button
          onClick={resetAllFilters}
          className="w-full py-2.5 px-4 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:bg-[#FCE9E5] hover:text-[#913638] hover:border-[#EFB8B0] text-xs font-semibold active:scale-[0.98] transition-all flex items-center justify-center gap-1.5 shadow-xs cursor-pointer"
        >
          <RotateCcw className="w-3.5 h-3.5" />
          <span>Reset All Filters</span>
        </button>
      )}
    </div>
  );

  return (
    <div className="bg-[#FFF8F5] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumb Navigation */}
        <Breadcrumbs items={[{ label: "Shop All Products" }]} className="mb-6" />

        {/* Controls Bar: Count & Sort & Mobile Filter Trigger */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-[#E8D4CF] mb-8 shadow-xs">
          {/* Result Count & Active Badges */}
          <div className="flex items-center gap-3">
            <button
              onClick={() => setIsMobileFiltersOpen(true)}
              className="lg:hidden inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] text-xs font-semibold border border-[#E7D1CC] shadow-xs active:scale-[0.98] cursor-pointer"
            >
              <SlidersHorizontal className="w-4 h-4 text-[#913638]" />
              <span>Filters {hasActiveFilters && "•"}</span>
            </button>
            <span className="text-xs text-[#78635E]">
              Showing <strong className="text-[#3A211D] font-bold">{filteredProducts.length}</strong> of {productsList.length} handmade creations
            </span>
          </div>

          {/* Sort Dropdown */}
          <div className="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-end">
            <label htmlFor="sort-select" className="text-xs font-medium text-[#78635E] shrink-0">
              Sort by:
            </label>
            <div className="relative">
              <select
                id="sort-select"
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value)}
                className="appearance-none bg-[#FFF8F5] border border-[#E8D4CF] rounded-full px-4 py-2 pr-8 text-xs font-semibold text-[#3A211D] focus:outline-none focus:border-[#8F3032] cursor-pointer"
              >
                <option value="featured">Featured Items</option>
                <option value="bestseller">Best Selling</option>
                <option value="newest">Newest Arrivals</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="rating">Highest Rated</option>
              </select>
              <ArrowUpDown className="w-3.5 h-3.5 text-[#78635E] absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            </div>
          </div>
        </div>

        {/* Main Content Layout: Desktop Sidebar + Product Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Desktop Filter Sidebar (Spans 3 cols) */}
          <aside className="hidden lg:block lg:col-span-3 bg-white p-6 rounded-3xl border border-[#E8D4CF] shadow-boutique sticky top-28">
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-[#E8D4CF]">
              <div className="flex items-center gap-2">
                <SlidersHorizontal className="w-4 h-4 text-[#8F3032]" />
                <h3 className="font-serif-luxury text-lg font-bold text-[#3A211D]">
                  Refine Collection
                </h3>
              </div>
              {hasActiveFilters && (
                <button
                  onClick={resetAllFilters}
                  className="text-[11px] font-semibold text-[#8F3032] hover:underline"
                >
                  Clear All
                </button>
              )}
            </div>
            {filterSidebarContent}
          </aside>

          {/* Product Grid Area (Spans 9 cols) */}
          <main className="lg:col-span-9">
            {filteredProducts.length > 0 ? (
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-3 gap-4 sm:gap-6">
                {filteredProducts.map((product) => (
                  <ProductCard key={product.id} product={product} />
                ))}
              </div>
            ) : (
              <div className="bg-white rounded-3xl border border-[#E8D4CF] p-12 text-center shadow-boutique">
                <div className="w-16 h-16 rounded-full bg-[#FFF8F5] border border-[#E8D4CF] flex items-center justify-center text-[#8F3032] mx-auto mb-4">
                  <Sparkles className="w-8 h-8" />
                </div>
                <h3 className="font-serif-luxury text-xl font-bold text-[#3A211D] mb-2">
                  No little stitches match your filters
                </h3>
                <p className="text-xs sm:text-sm text-[#78635E] max-w-sm mx-auto mb-6">
                  Try adjusting your price range or clearing selected filters to see all handcrafted creations.
                </p>
                <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
                  <button
                    onClick={resetAllFilters}
                    className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                  >
                    Reset Filters
                  </button>
                </div>
              </div>
            )}
          </main>

        </div>

      </div>

      {/* Mobile Filter Drawer */}
      {isMobileFiltersOpen && (
        <div className="fixed inset-0 z-50 overflow-hidden lg:hidden">
          <div
            className="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity animate-in fade-in"
            onClick={() => setIsMobileFiltersOpen(false)}
          />
          <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
            <div className="w-screen max-w-xs bg-white border-l border-[#E8D4CF] shadow-2xl flex flex-col p-6 overflow-y-auto animate-in slide-in-from-right">
              <div className="flex items-center justify-between pb-4 border-b border-[#E8D4CF] mb-4">
                <h3 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
                  Filters
                </h3>
                <button
                  onClick={() => setIsMobileFiltersOpen(false)}
                  className="p-1.5 text-[#786864] hover:text-[#2E211E] rounded-full hover:bg-[#FCE9E5] transition-colors"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              {filterSidebarContent}

              <div className="mt-8 pt-4 border-t border-[#E8D4CF]">
                <button
                  onClick={() => setIsMobileFiltersOpen(false)}
                  className="w-full py-3 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                >
                  Apply Filters ({filteredProducts.length} Results)
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default function ShopPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-[#FFF8F5] flex items-center justify-center">
          <div className="w-8 h-8 border-2 border-[#8F3032] border-t-transparent rounded-full animate-spin" />
        </div>
      }
    >
      <ShopContent />
    </Suspense>
  );
}
