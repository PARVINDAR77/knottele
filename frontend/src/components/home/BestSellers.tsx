"use client";

import React, { useState, useEffect, useMemo } from "react";
import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { Product } from "@/types/product";
import { PRODUCTS } from "@/data/products";
import { fetchProducts } from "@/lib/api";
import { ProductCard } from "@/components/ui/ProductCard";
import { useWebsiteMedia } from "@/context/MediaContext";
import { getLocalCache, setLocalCache, setupAdminSyncListener } from "@/lib/cache";

const PRODUCTS_CACHE_KEY = "knotelle_cache_products";

export function BestSellers() {
  const { media } = useWebsiteMedia();
  const [productsList, setProductsList] = useState<Product[]>(PRODUCTS);
  const [activeTab, setActiveTab] = useState<string>("all");

  useEffect(() => {
    // Safely populate from local cache after mount
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
        .catch((err) => console.warn("Live bestsellers fetch notice:", err));
    };

    loadProducts();
    const cleanupListeners = setupAdminSyncListener(loadProducts);

    return () => {
      isMounted = false;
      cleanupListeners();
    };
  }, []);

  const bestSellers = useMemo(() => {
    const featured = productsList.filter((p) => p.isBestSeller || p.isFeatured);
    if (featured.length >= 12) return featured;
    const featuredIds = new Set(featured.map((p) => p.id));
    const others = productsList.filter((p) => !featuredIds.has(p.id));
    return [...featured, ...others];
  }, [productsList]);

  const categories = useMemo(() => {
    const defaultTabs = [{ id: "all", label: "All" }];
    if (media?.categories && media.categories.length > 0) {
      const dbTabs = media.categories.slice(0, 5).map((c) => ({
        id: c.slug,
        label: c.name,
      }));
      return [...defaultTabs, ...dbTabs];
    }
    return defaultTabs;
  }, [media?.categories]);

  const filteredProducts = useMemo(() => {
    if (activeTab === "all") return bestSellers;
    return bestSellers.filter(
      (p) => p.categorySlug === activeTab || p.category.toLowerCase().includes(activeTab.toLowerCase())
    );
  }, [activeTab, bestSellers]);

  const handleTabChange = (tabId: string) => {
    setActiveTab(tabId);
  };

  // Up to 12 products: 6 products per row across 2 rows on desktop (6 by 6 two lines)
  const visibleProducts = useMemo(() => {
    return filteredProducts.slice(0, 12);
  }, [filteredProducts]);

  return (
    <section className="py-12 sm:py-16 bg-[#FFF9F6] relative">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Header & Tabs */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
          <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
            Best Sellers
          </h2>

          <div className="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 no-scrollbar">
            {categories.map((cat) => (
              <button
                key={cat.id}
                onClick={() => handleTabChange(cat.id)}
                className={`px-3.5 sm:px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all cursor-pointer ${
                  activeTab === cat.id
                    ? "bg-[#913638] text-white shadow-xs"
                    : "bg-white text-[#2E211E] border border-[#E7D1CC] hover:bg-[#FCE9E5] hover:text-[#913638]"
                }`}
              >
                {cat.label}
              </button>
            ))}

            <Link
              href="/shop"
              className="text-xs font-semibold text-[#786864] hover:text-[#913638] flex items-center gap-1 pl-2 whitespace-nowrap"
            >
              <span>View All</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          </div>
        </div>

        {/* 6 by 6 Two Lines Product Grid (Up to 12 items: 6 per row on desktop) */}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
          {visibleProducts.map((product, idx) => (
            <ProductCard key={`${product.id}-${idx}`} product={product} priority={idx < 6} />
          ))}
        </div>

      </div>
    </section>
  );
}

