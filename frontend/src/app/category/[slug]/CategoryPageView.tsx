"use client";

import React, { useState, useEffect, useMemo } from "react";
import Image from "next/image";
import Link from "next/link";
import { useParams, usePathname } from "next/navigation";
import { Product } from "@/types/product";
import { fetchProducts, fetchCategories } from "@/lib/api";
import { CATEGORIES } from "@/data/categories";
import { ProductCard } from "@/components/ui/ProductCard";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { BotanicalFlourish } from "@/components/ui/BotanicalDecorations";
import { Sparkles, ArrowRight } from "lucide-react";
import { useWebsiteMedia } from "@/context/MediaContext";
import { getLocalCache, setLocalCache, setupAdminSyncListener } from "@/lib/cache";

const PRODUCTS_CACHE_KEY = "knotelle_cache_products";
const CATEGORIES_CACHE_KEY = "knotelle_cache_categories";

interface CategoryPageViewProps {
  slug: string;
}

export default function CategoryPageView({ slug: propSlug }: CategoryPageViewProps) {
  const pathname = usePathname();
  const params = useParams();

  // 1. Authoritative client pathname slug:
  // When static HTML template fallback is served (e.g. bags/index.html),
  // propSlug was baked in at build time. On the client, window.location.pathname
  // is the authoritative source of the actual category the user requested.
  const clientPathnameSlug = typeof window !== "undefined"
    ? window.location.pathname.replace(/^.*\/category\//, "").replace(/\/.*$/, "").trim()
    : (pathname ? pathname.replace(/^.*\/category\//, "").replace(/\/.*$/, "").trim() : "");

  const rawParamSlug = typeof params?.slug === "string" ? params.slug : Array.isArray(params?.slug) ? params.slug[0] : "";
  const decodedPathSlug = clientPathnameSlug ? decodeURIComponent(clientPathnameSlug) : "";

  // Dynamic slug: decoded client path overrides pre-baked SSG template slug
  const slug = (decodedPathSlug && decodedPathSlug !== "index")
    ? decodedPathSlug
    : (rawParamSlug || propSlug);

  const normalizedSlug = (slug || "").toLowerCase().trim();

  const { media } = useWebsiteMedia();
  const [categoriesList, setCategoriesList] = useState<any[]>([]);
  const [productsList, setProductsList] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const cachedCats = media?.categories && media.categories.length > 0
      ? media.categories
      : getLocalCache<any[]>(CATEGORIES_CACHE_KEY, []);
    if (cachedCats && cachedCats.length > 0) {
      setCategoriesList(cachedCats);
    }
    const cachedProds = getLocalCache<Product[]>(PRODUCTS_CACHE_KEY, []);
    if (cachedProds && cachedProds.length > 0) {
      setProductsList(cachedProds);
      setIsLoading(false);
    }

    let isMounted = true;

    const loadData = () => {
      fetchCategories()
        .then((cats) => {
          if (isMounted && cats && cats.length > 0) {
            setCategoriesList(cats);
            setLocalCache(CATEGORIES_CACHE_KEY, cats);
          }
        })
        .catch((err) => console.warn("Live categories fetch notice:", err));

      fetchProducts({ per_page: 100 })
        .then((res) => {
          if (isMounted && res.products && res.products.length > 0) {
            setProductsList(res.products);
            setLocalCache(PRODUCTS_CACHE_KEY, res.products);
            setIsLoading(false);
          }
        })
        .catch((err) => {
          console.warn("Live products fetch notice:", err);
          if (isMounted) setIsLoading(false);
        });
    };

    loadData();
    const cleanupListeners = setupAdminSyncListener(loadData);

    return () => {
      isMounted = false;
      cleanupListeners();
    };
  }, [media?.categories]);

  useEffect(() => {
    if (media?.categories && media.categories.length > 0) {
      setCategoriesList(media.categories);
    }
  }, [media?.categories]);

  const category = categoriesList.find(
    (c) => (c.slug || "").toLowerCase() === normalizedSlug || c.slug === slug
  );

  const fallbackCategory = CATEGORIES.find(
    (c) => (c.slug || "").toLowerCase() === normalizedSlug || c.slug === slug
  );

  const matchedCat = category || fallbackCategory;

  const currentCat = matchedCat || {
    name: slug
      ? slug
          .split("-")
          .map((w: string) => w.charAt(0).toUpperCase() + w.slice(1))
          .join(" ")
      : "Category",
    slug: slug,
    description: `Discover our artisanal handcrafted ${slug.replace(/-/g, " ")} creations.`,
    image: "/images/logo/Logo_1.png",
  };

  const categoryProducts = useMemo(() => {
    const targetSlug = normalizedSlug;
    const targetName = (currentCat.name || "").toLowerCase();
    return productsList.filter((p) => {
      const pCatSlug = (p.categorySlug || "").toLowerCase();
      const pCat = (p.category || "").toLowerCase();
      return (
        pCatSlug === targetSlug ||
        pCat === targetName ||
        pCat.includes(targetSlug.replace(/-/g, " ")) ||
        (targetSlug === "combo-packs" && (pCatSlug.includes("combo") || pCat.includes("combo")))
      );
    });
  }, [productsList, normalizedSlug, currentCat.name]);

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumb */}
        <Breadcrumbs
          items={[
            { label: "Shop", href: "/shop" },
            { label: currentCat.name },
          ]}
          className="mb-6"
        />

        {/* Category Hero Banner */}
        <div className="relative rounded-[2.5rem] bg-gradient-to-r from-[#FCE9E5] via-white to-[#FCE9E5] border border-[#E7D1CC] p-6 sm:p-12 mb-10 overflow-hidden shadow-boutique">
          <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
            
            <div className="md:col-span-8 space-y-3 text-center md:text-left">
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white border border-[#E7D1CC] text-xs font-semibold text-[#913638]">
                <Sparkles className="w-3.5 h-3.5 text-[#C69A5A]" />
                <span>Handmade Boutique Collection</span>
              </div>

              <h1 className="font-serif-luxury text-3xl sm:text-4xl lg:text-5xl font-bold text-[#2E211E]">
                Crochet {currentCat.name}
              </h1>

              <p className="text-sm sm:text-base text-[#786864] max-w-xl leading-relaxed">
                {currentCat.description} Each piece is hand-stitched with 100% pure cotton yarn.
              </p>

              <div className="pt-2 flex flex-wrap gap-2 justify-center md:justify-start">
                <span className="text-xs text-[#786864] bg-white px-3 py-1 rounded-full border border-[#E7D1CC]">
                  {categoryProducts.length} unique handcrafted items
                </span>
              </div>
            </div>

            <div className="md:col-span-4 flex justify-center">
              <div className="relative w-36 h-36 sm:w-44 sm:h-44 rounded-full overflow-hidden border-4 border-white shadow-lg bg-[#FFF9F6]">
                <Image
                  src={currentCat.image || "/images/logo/Logo_1.png"}
                  alt={currentCat.name}
                  fill
                  sizes="180px"
                  className="object-cover"
                />
              </div>
            </div>

          </div>
        </div>

        {/* Other Category Quick Selector */}
        <div className="flex items-center gap-2 overflow-x-auto pb-4 mb-8 no-scrollbar">
          <Link
            href="/shop"
            className="px-4 py-1.5 rounded-full text-xs font-semibold bg-white text-[#786864] hover:text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] whitespace-nowrap active:scale-[0.98] transition-all"
          >
            All Collections
          </Link>
          {categoriesList.map((cat) => (
            <Link
              key={cat.id || cat.slug}
              href={`/category/${cat.slug}`}
              prefetch={false}
              className={`px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap active:scale-[0.98] transition-all ${
                cat.slug === currentCat.slug
                  ? "bg-[#913638] text-white shadow-xs"
                  : "bg-white text-[#786864] hover:text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] hover:text-[#913638]"
              }`}
            >
              {cat.name}
            </Link>
          ))}
        </div>

        {/* Product Grid */}
        {categoryProducts.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
            {categoryProducts.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </div>
        ) : isLoading ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-6">
            {[...Array(4)].map((_, i) => (
              <div key={i} className="bg-white rounded-3xl border border-[#E7D1CC] p-4 h-64 animate-pulse" />
            ))}
          </div>
        ) : (
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-12 text-center shadow-boutique">
            <h3 className="font-serif-luxury text-xl font-bold text-[#2E211E] mb-2">
              New Designs In Crafting!
            </h3>
            <p className="text-xs sm:text-sm text-[#786864] max-w-sm mx-auto mb-6">
              Our artisans are currently handcrafting more {currentCat.name} designs. Explore our full catalog!
            </p>
            <Link
              href="/shop"
              className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all"
            >
              <span>Explore Boutique Shop</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          </div>
        )}

        {/* Bottom Flourish */}
        <div className="flex justify-center mt-12">
          <BotanicalFlourish className="w-28 h-5 text-[#EFB8B0]" />
        </div>

      </div>
    </div>
  );
}
