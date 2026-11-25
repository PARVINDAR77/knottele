"use client";

import React, { useState, useEffect, useMemo, Suspense } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { Search, Sparkles, ArrowRight, SlidersHorizontal } from "lucide-react";
import { Product } from "@/types/product";
import { fetchProducts } from "@/lib/api";
import { ProductCard } from "@/components/ui/ProductCard";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { BotanicalFlourish } from "@/components/ui/BotanicalDecorations";
import { useWebsiteMedia } from "@/context/MediaContext";
import { getLocalCache, setLocalCache, setupAdminSyncListener } from "@/lib/cache";

const PRODUCTS_CACHE_KEY = "knotelle_cache_products";

function SearchContent() {
  const { media } = useWebsiteMedia();
  const searchParams = useSearchParams();
  const initialQuery = searchParams.get("q") || "";
  const [query, setQuery] = useState(initialQuery);
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [sortBy, setSortBy] = useState("relevance");
  const [productsList, setProductsList] = useState<Product[]>([]);

  useEffect(() => {
    const cached = getLocalCache<Product[]>(PRODUCTS_CACHE_KEY, []);
    if (cached && cached.length > 0) {
      setProductsList(cached);
    }

    let isMounted = true;
    const loadProducts = () => {
      fetchProducts({ per_page: 100 })
        .then((res) => {
          if (isMounted && res.products && res.products.length > 0) {
            setProductsList(res.products);
            setLocalCache(PRODUCTS_CACHE_KEY, res.products);
          }
        })
        .catch((err) => console.warn("Live search products notice:", err));
    };

    loadProducts();
    const cleanupListeners = setupAdminSyncListener(loadProducts);

    return () => {
      isMounted = false;
      cleanupListeners();
    };
  }, []);

  const categories = media?.categories || [];

  const searchResults = useMemo(() => {
    const q = query.toLowerCase().trim();
    if (!q) return productsList;

    return productsList.filter((product) => {
      const matchName = product.name.toLowerCase().includes(q);
      const matchCategory = product.category.toLowerCase().includes(q);
      const matchTags = product.tags.some((t) => t.toLowerCase().includes(q));
      const matchDesc = product.description.toLowerCase().includes(q);
      const matchMaterial = product.materials?.some((m) => m.toLowerCase().includes(q));

      const matchesQuery = matchName || matchCategory || matchTags || matchDesc || matchMaterial;
      const matchesCat = selectedCategory === "all" || product.categorySlug === selectedCategory;

      return matchesQuery && matchesCat;
    }).sort((a, b) => {
      if (sortBy === "price-low") return a.price - b.price;
      if (sortBy === "price-high") return b.price - a.price;
      if (sortBy === "rating") return b.rating - a.rating;
      return 0;
    });
  }, [query, selectedCategory, sortBy, productsList]);

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumb */}
        <Breadcrumbs
          items={[
            { label: "Search", href: "/search" },
            { label: query ? `"${query}"` : "All Results" },
          ]}
          className="mb-6"
        />

        {/* Search Header Banner */}
        <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-10 mb-8 shadow-boutique text-center max-w-3xl mx-auto">
          <div className="flex justify-center mb-2">
            <BotanicalFlourish className="w-20 h-4 text-[#EFB8B0]" />
          </div>

          <h1 className="font-serif-luxury text-2xl sm:text-4xl font-bold text-[#2E211E] mb-2">
            {query ? (
              <>
                Search Results for{" "}
                <span className="text-[#913638] italic">&ldquo;{query}&rdquo;</span>
              </>
            ) : (
              "Explore All Creations"
            )}
          </h1>

          <p className="text-xs sm:text-sm text-[#786864] mb-6">
            Found {searchResults.length} matching handmade crochet creations
          </p>

          {/* Search Input Bar */}
          <form
            onSubmit={(e) => e.preventDefault()}
            className="relative flex items-center max-w-md mx-auto"
          >
            <input
              type="text"
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search flowers, bouquets, bags, plushies..."
              className="w-full pl-12 pr-4 py-3 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-xs sm:text-sm text-[#2E211E] placeholder-[#786864]/60 focus:outline-none focus:border-[#913638] transition-colors"
            />
            <Search className="w-5 h-5 text-[#913638] absolute left-4 top-1/2 -translate-y-1/2" />
          </form>
        </div>

        {/* Filter & Sort Controls */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-[#E7D1CC] mb-8 shadow-xs">
          <div className="flex items-center gap-2 overflow-x-auto w-full sm:w-auto pb-2 sm:pb-0 no-scrollbar">
            <button
              onClick={() => setSelectedCategory("all")}
              className={`px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap active:scale-[0.98] transition-all cursor-pointer ${
                selectedCategory === "all"
                  ? "bg-[#913638] text-white shadow-xs"
                  : "bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] hover:text-[#913638]"
              }`}
            >
              All Categories
            </button>
            {categories.slice(0, 6).map((cat) => (
              <button
                key={cat.id}
                onClick={() => setSelectedCategory(cat.slug)}
                className={`px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap active:scale-[0.98] transition-all cursor-pointer ${
                  selectedCategory === cat.slug
                    ? "bg-[#913638] text-white shadow-xs"
                    : "bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] hover:text-[#913638]"
                }`}
              >
                {cat.name}
              </button>
            ))}
          </div>

          <div className="flex items-center gap-2 shrink-0 self-end sm:self-auto">
            <label htmlFor="search-sort-select" className="text-xs text-[#786864]">Sort:</label>
            <select
              id="search-sort-select"
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="bg-[#FFF9F6] border border-[#E7D1CC] rounded-full px-3 py-1.5 text-xs font-semibold text-[#2E211E] focus:outline-none cursor-pointer"
            >
              <option value="relevance">Relevance</option>
              <option value="price-low">Price: Low to High</option>
              <option value="price-high">Price: High to Low</option>
              <option value="rating">Top Rated</option>
            </select>
          </div>
        </div>

        {/* Results Grid or Empty State */}
        {searchResults.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
            {searchResults.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : (
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-16 text-center max-w-2xl mx-auto shadow-boutique">
            <div className="w-16 h-16 rounded-full bg-[#FCE9E5] border border-[#E7D1CC] flex items-center justify-center text-[#913638] mx-auto mb-4">
              <Sparkles className="w-8 h-8" />
            </div>

            <h3 className="font-serif-luxury text-2xl font-bold text-[#2E211E] mb-2">
              No little stitch found.
            </h3>

            <p className="text-xs sm:text-sm text-[#786864] mb-6 leading-relaxed">
              Try another search keyword or browse our curated category collections.
            </p>

            <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
              <Link
                href="/shop"
                className="w-full sm:w-auto px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all"
              >
                Explore All Products →
              </Link>
            </div>
          </div>
        )}

      </div>
    </div>
  );
}

export default function SearchPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-[#FFF8F5] flex items-center justify-center">
          <div className="w-8 h-8 border-2 border-[#8F3032] border-t-transparent rounded-full animate-spin" />
        </div>
      }
    >
      <SearchContent />
    </Suspense>
  );
}
