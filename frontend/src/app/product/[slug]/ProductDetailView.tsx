"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useParams, usePathname } from "next/navigation";
import {
  Heart,
  ShoppingBag,
  Sparkles,
  Truck,
  ShieldCheck,
  RotateCcw,
  Plus,
  Minus,
  ArrowRight,
  Share2,
  Ruler,
} from "lucide-react";
import { PRODUCTS } from "@/data/products";
import { useCart } from "@/context/CartContext";
import { useWishlist } from "@/context/WishlistContext";
import { useToast } from "@/context/ToastContext";
import { StarRating } from "@/components/ui/StarRating";
import { Badge } from "@/components/ui/Badge";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { ProductCard } from "@/components/ui/ProductCard";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { fetchProductBySlug, fetchRelatedProducts } from "@/lib/api";
import { getLocalCache } from "@/lib/cache";
import { Product } from "@/types/product";

interface ProductDetailViewProps {
  slug: string;
}

function FormattedHtmlText({ content, className = "" }: { content?: string; className?: string }) {
  if (!content) return null;

  // Sanitize content to strip executable tags, inline event handlers, and javascript protocols
  const sanitizedContent = content
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, "")
    .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, "")
    .replace(/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/gi, "")
    .replace(/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/gi, "")
    .replace(/\bon\w+\s*=\s*(?:'[^']*'|"[^"]*"|[^\s>]+)/gi, "")
    .replace(/javascript:/gi, "");

  // Check if content contains HTML tags (e.g. <p>, <br>, <strong>, etc.)
  const hasHtml = /<[a-z][\s\S]*>/i.test(sanitizedContent);

  if (hasHtml) {
    return (
      <div
        className={className}
        dangerouslySetInnerHTML={{ __html: sanitizedContent }}
      />
    );
  }

  return (
    <div className={className}>
      {sanitizedContent.split("\n\n").map((para, i) => (
        <p key={i} className="mb-2 last:mb-0">
          {para}
        </p>
      ))}
    </div>
  );
}

export default function ProductDetailView({ slug: propSlug }: ProductDetailViewProps) {
  const router = useRouter();
  const params = useParams();
  const pathname = usePathname();

  // 1. Extract pathname slug from current URL (e.g. "/product/rose-keychain/" -> "rose-keychain")
  const clientPathnameSlug = typeof window !== "undefined"
    ? window.location.pathname.replace(/^.*\/product\//, "").replace(/\/.*$/, "").trim()
    : (pathname ? pathname.replace(/^.*\/product\//, "").replace(/\/.*$/, "").trim() : "");

  const rawParamSlug = typeof params?.slug === "string" ? params.slug : Array.isArray(params?.slug) ? params.slug[0] : "";
  const decodedPathSlug = clientPathnameSlug ? decodeURIComponent(clientPathnameSlug) : "";

  // 2. Authoritative raw slug
  const rawSlug = (decodedPathSlug && decodedPathSlug !== "index")
    ? decodedPathSlug
    : (rawParamSlug || propSlug);

  // Normalize slug: lowercase, replace spaces/underscores with hyphens, remove special characters
  const slug = (rawSlug || "")
    .toLowerCase()
    .trim()
    .replace(/[\s_]+/g, "-")
    .replace(/[^\w-]/g, "");

  // Seamlessly clean up the browser URL if it had raw spaces (e.g. "/product/crochet gift-box" -> "/product/crochet-gift-box")
  useEffect(() => {
    if (typeof window !== "undefined" && slug && (window.location.pathname.includes(" ") || window.location.pathname.includes("%20"))) {
      try {
        window.history.replaceState(null, "", `/product/${slug}`);
      } catch {}
    }
  }, [slug]);

  // Detect if this page was loaded as a fallback HTML template for a different dynamic database product
  const isFallbackTemplateMismatch = Boolean(
    decodedPathSlug && propSlug && decodedPathSlug.toLowerCase().replace(/[\s_]+/g, "-") !== propSlug.toLowerCase().replace(/[\s_]+/g, "-")
  );

  // Deterministic fallback matching SSR static build to ensure zero hydration mismatch (#418)
  const fallbackProduct = isFallbackTemplateMismatch
    ? PRODUCTS.find((p) => p.slug === slug)
    : (PRODUCTS.find((p) => p.slug === slug || p.slug === rawSlug) ||
       PRODUCTS.find(
         (p) =>
           (slug === "cute-bunny-amigurumi-keychain" && p.slug === "cute-bunny-keychain") ||
           (slug === "cute-bunny-keychain" && p.slug === "cute-bunny-amigurumi-keychain")
       ));

  const [liveProduct, setLiveProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(!fallbackProduct);
  const [fetchAttempted, setFetchAttempted] = useState<boolean>(false);
  const product = liveProduct || fallbackProduct;
  const [relatedProductsList, setRelatedProductsList] = useState<Product[]>([]);

  const { addItem, setIsCartOpen } = useCart();
  const { toggleWishlist, isInWishlist } = useWishlist();
  const { showToast } = useToast();

  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [customText, setCustomText] = useState("");
  const [quantity, setQuantity] = useState(1);
  const [activeTab, setActiveTab] = useState<"desc" | "specs" | "reviews">("desc");

  useEffect(() => {
    let isMounted = true;

    // Check client local cache immediately on mount (runs strictly after initial hydration, eliminating #418)
    if (typeof window !== "undefined") {
      try {
        const cached = getLocalCache<Product[]>("knotelle_cache_products", []).find(
          (p) =>
            p.slug === slug ||
            p.slug === rawSlug ||
            (slug === "cute-bunny-amigurumi-keychain" && p.slug === "cute-bunny-keychain") ||
            (slug === "cute-bunny-keychain" && p.slug === "cute-bunny-amigurumi-keychain")
        );
        if (cached && isMounted) {
          setLiveProduct(cached);
          setIsLoading(false);
        }
      } catch {}
    }

    if (!fallbackProduct || isFallbackTemplateMismatch) {
      setLiveProduct(null);
      setIsLoading(true);
      setFetchAttempted(false);
    }
    fetchProductBySlug(slug)
      .then(async (apiProd) => {

        if (isMounted) {
          if (apiProd) {
            setLiveProduct(apiProd);
            setSelectedImageIndex(0);
            if (apiProd.name && typeof document !== "undefined") {
              document.title = `${apiProd.name} | Knotelle`;
            }
            fetchRelatedProducts(apiProd.id)
              .then((rel) => {
                if (isMounted && rel && rel.length > 0) {
                  setRelatedProductsList(rel);
                }
              })
              .catch(() => {});
          }
          setIsLoading(false);
          setFetchAttempted(true);
        }
      })
      .catch((err) => {
        console.warn("Live product fetch error:", err);
        if (isMounted) {
          setIsLoading(false);
          setFetchAttempted(true);
        }
      });

    return () => {
      isMounted = false;
    };
  }, [slug, fallbackProduct, isFallbackTemplateMismatch]);

  useEffect(() => {
    if (product?.name && typeof document !== "undefined") {
      document.title = `${product.name} | Knotelle`;
    }
  }, [product?.name]);

  // ALL HOOKS MUST RUN AT TOP LEVEL BEFORE ANY CONDITIONAL RETURNS (Fixes React Error #310)
  const isWishlisted = isInWishlist(product?.id || "");
  const maxStock = typeof product?.stock === "number" ? product.stock : 99;
  const isOutOfStock = maxStock <= 0 || product?.inStock === false;

  useEffect(() => {
    if (product) {
      const availStock = typeof product.stock === "number" ? product.stock : 99;
      if (availStock <= 0 || product.inStock === false) {
        setQuantity(0);
      } else if (quantity > availStock) {
        setQuantity(availStock);
      } else if (quantity === 0) {
        setQuantity(1);
      }
    }
  }, [product?.id, product?.stock, product?.inStock]);

  if (isLoading && !product) {
    return (
      <div className="min-h-screen bg-warm-white py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-7xl mx-auto space-y-8 animate-pulse">
          <div className="h-6 w-48 bg-stone-200 rounded-md"></div>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <div className="h-[460px] bg-stone-200 rounded-3xl"></div>
            <div className="space-y-4">
              <div className="h-9 w-3/4 bg-stone-200 rounded-lg"></div>
              <div className="h-6 w-1/3 bg-stone-200 rounded-lg"></div>
              <div className="h-28 bg-stone-200 rounded-xl"></div>
              <div className="h-12 w-full bg-stone-200 rounded-xl"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!product && fetchAttempted) {
    return (
      <div className="min-h-[70vh] bg-[#FFF8F5] flex items-center justify-center px-4 py-16">
        <div className="max-w-md w-full bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-12 text-center shadow-boutique space-y-6">
          <div className="w-20 h-20 mx-auto rounded-full bg-[#FCE9E5] border border-[#E7D1CC] flex items-center justify-center text-[#913638]">
            <FlowerIcon className="w-10 h-10 text-[#913638]" />
          </div>
          <div className="space-y-2">
            <span className="font-serif-luxury text-xs font-bold uppercase tracking-wider text-[#913638] block">
              Boutique Notice
            </span>
            <h1 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
              Artisan Creation
            </h1>
            <p className="text-xs sm:text-sm text-[#786864] leading-relaxed">
              This handcrafted item is currently being prepared in our workshop. Explore our full collection of bespoke crochet pieces.
            </p>
          </div>
          <div className="flex justify-center">
            <BotanicalFlourish className="w-20 h-4 text-[#EFB8B0]" />
          </div>
          <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <Link
              href="/shop"
              className="w-full sm:w-auto px-7 py-3 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] shadow-sm transition-all text-center"
            >
              Explore Collection
            </Link>
            <Link
              href="/"
              className="w-full sm:w-auto px-6 py-3 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:bg-[#FCE9E5] text-xs font-semibold shadow-xs transition-all text-center"
            >
              Return Home
            </Link>
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return null;
  }

  const relatedProducts =
    relatedProductsList.length > 0
      ? relatedProductsList
      : PRODUCTS.filter(
          (p) => p.categorySlug === product.categorySlug && p.id !== product.id
        ).slice(0, 4);

  const handleAddToCart = () => {
    if (isOutOfStock) {
      showToast("Out of Stock", "This item is currently out of stock.", "error");
      return;
    }
    if (quantity > maxStock) {
      showToast("Stock Limit Exceeded", `Only ${maxStock} items available in stock.`, "warning");
      return;
    }
    addItem(product, quantity, {
      customText: customText.trim() || undefined,
    });
    setIsCartOpen(true);
  };

  const handleBuyNow = () => {
    if (isOutOfStock) {
      showToast("Out of Stock", "This item is currently out of stock.", "error");
      return;
    }
    if (quantity > maxStock) {
      showToast("Stock Limit Exceeded", `Only ${maxStock} items available in stock.`, "warning");
      return;
    }
    addItem(product, quantity, {
      customText: customText.trim() || undefined,
    });
    router.push("/checkout");
  };

  const handleShare = () => {
    if (typeof navigator !== "undefined" && navigator.share) {
      navigator.share({
        title: product.name,
        text: product.shortDescription,
        url: window.location.href,
      }).catch(() => {});
    } else if (typeof navigator !== "undefined") {
      navigator.clipboard.writeText(window.location.href);
      showToast("Link Copied 📋", "Product link copied to your clipboard.", "info");
    }
  };

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumbs */}
        <Breadcrumbs
          items={[
            { label: "Shop", href: "/shop" },
            { label: product.category || "Handmade", href: `/category/${product.categorySlug || "all"}` },
            { label: product.name || "Product" },
          ]}
          className="mb-8"
        />

        {/* Product Showcase: Left Gallery, Right Details */}
        <div
          className="flex flex-col lg:flex-row gap-8 lg:gap-12 xl:gap-14 items-start bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 lg:p-10 shadow-boutique mb-16"
          style={{ gap: "3.5rem" }}
        >
          
          {/* Left Gallery (strictly constrained width on desktop, guarantees zero overlap) */}
          <div className="w-full lg:w-[42%] lg:max-w-[460px] shrink-0 space-y-4">
            {/* Main Stage Image with Zoom feel */}
            <div className="relative aspect-[4/4.5] w-full max-w-full rounded-2xl overflow-hidden bg-[#FFF9F6] border border-[#E7D1CC]">
              {(() => {
                const currentImg = product.images?.[selectedImageIndex] || product.images?.[0] || product.main_image || "";
                const isLogo = !currentImg || currentImg.includes("logo") || currentImg.includes("Logo_1.png");
                const safeImg = isLogo ? "/images/products/bunny-keychain.jpg" : currentImg;

                if (isLoading && isLogo) {
                  return (
                    <div className="w-full h-full bg-[#FCE9E5]/50 animate-pulse flex items-center justify-center">
                      <div className="w-8 h-8 border-2 border-[#913638] border-t-transparent rounded-full animate-spin" />
                    </div>
                  );
                }

                return (
                  <Image
                    src={safeImg}
                    alt={product.name || "Handmade Product"}
                    fill
                    priority
                    sizes="(max-width: 1024px) 100vw, 45vw"
                    className="object-cover object-center"
                  />
                );
              })()}

              {/* Wishlist Button */}
              <button
                onClick={() => toggleWishlist(product)}
                aria-label={isWishlisted ? "Remove from wishlist" : "Add to wishlist"}
                className="absolute top-4 right-4 z-10 w-11 h-11 rounded-full bg-white/90 backdrop-blur-md border border-[#E7D1CC] flex items-center justify-center text-[#786864] hover:text-[#913638] hover:bg-white transition-all shadow-md active:scale-95 cursor-pointer"
              >
                <Heart
                  className={`w-5 h-5 transition-all ${
                    isWishlisted ? "fill-[#913638] text-[#913638] scale-110" : ""
                  }`}
                />
              </button>

              {/* Badge */}
              <div className="absolute top-4 left-4 flex flex-col gap-1.5">
                {product.isBestSeller && <Badge variant="rose">Best Seller</Badge>}
                {product.isNew && <Badge variant="pink">New In</Badge>}
              </div>
            </div>

            {/* Thumbnails */}
            {(() => {
              const validThumbnails = (product.images || []).filter(
                (img) => img && !img.includes("logo") && !img.includes("Logo_1.png")
              );
              if (validThumbnails.length <= 1) return null;

              return (
                <div className="flex items-center gap-3 overflow-x-auto pb-2">
                  {validThumbnails.map((img, idx) => (
                    <button
                      key={idx}
                      onClick={() => setSelectedImageIndex(idx)}
                      className={`relative w-20 h-20 rounded-xl overflow-hidden bg-[#FFF9F6] shrink-0 border-2 transition-all cursor-pointer ${
                        selectedImageIndex === idx
                          ? "border-[#913638] ring-2 ring-[#913638]/20"
                          : "border-[#E7D1CC] opacity-70 hover:opacity-100"
                      }`}
                    >
                      <Image
                        src={img || "/images/products/bunny-keychain.jpg"}
                        alt={`${product.name} preview ${idx + 1}`}
                        fill
                        sizes="80px"
                        className="object-cover"
                      />
                    </button>
                  ))}
                </div>
              );
            })()}
          </div>

          {/* Right Product Info (expands cleanly to fill remaining space) */}
          <div className="w-full lg:flex-1 min-w-0 flex flex-col justify-between space-y-6">
            <div className="space-y-4">
              
              {/* Category & Rating */}
              <div className="flex items-center justify-between gap-4">
                <Link
                  href={`/category/${product.categorySlug || "all"}`}
                  className="text-xs uppercase tracking-widest text-[#913638] font-semibold hover:underline"
                >
                  {product.category || "Handmade"}
                </Link>
                <button
                  onClick={handleShare}
                  className="text-[#786864] hover:text-[#913638] flex items-center gap-1 text-xs transition-colors cursor-pointer"
                >
                  <Share2 className="w-4 h-4" />
                  <span>Share</span>
                </button>
              </div>

              {/* Product Title */}
              <h1 className="font-serif-luxury text-2xl sm:text-3xl lg:text-4xl font-bold text-[#2E211E]">
                {product.name}
              </h1>

              {/* Star Rating & Review count */}
              <div className="flex items-center gap-3">
                <StarRating rating={Number(product.rating) || 5} size="md" showNumber reviewCount={product.reviewCount || 0} />
                {isOutOfStock ? (
                  <span className="text-xs text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full font-medium border border-rose-200">
                    Out of Stock
                  </span>
                ) : maxStock <= 5 ? (
                  <span className="text-xs text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full font-semibold border border-amber-200 animate-pulse">
                    Only {maxStock} left in stock!
                  </span>
                ) : (
                  <span className="text-xs text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full font-medium border border-emerald-200">
                    In Stock ({maxStock} available)
                  </span>
                )}
              </div>

              {/* Price */}
              <div className="flex items-baseline gap-3 pt-1">
                <span className="text-2xl sm:text-3xl font-bold text-[#913638]">
                  ₹{Number(product.price || 0).toLocaleString("en-IN")}
                </span>
                {product.comparePrice && (
                  <span className="text-base text-[#786864] line-through">
                    ₹{Number(product.comparePrice).toLocaleString("en-IN")}
                  </span>
                )}
                <span className="text-xs text-[#786864]">
                  (Inclusive of all taxes & handmade fees)
                </span>
              </div>

              {/* Product Overview / Description */}
              <FormattedHtmlText
                content={product.description}
                className="text-sm text-[#786864] leading-relaxed space-y-2 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:text-[#2E211E] [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:my-1"
              />

              {/* Quantity Selector & Action Buttons */}
              <div className="space-y-3 pt-2">
                <div className="flex items-center gap-3">
                  <span className="text-xs font-semibold text-[#2E211E]">Quantity:</span>
                  <div className="flex items-center border border-[#E7D1CC] rounded-full bg-white overflow-hidden shadow-xs">
                    <button
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      disabled={isOutOfStock || quantity <= 1}
                      className="px-3 py-1.5 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                      aria-label="Decrease quantity"
                    >
                      <Minus className="w-3.5 h-3.5" />
                    </button>
                    <span className="px-3 text-xs font-bold text-[#2E211E] min-w-[24px] text-center">
                      {isOutOfStock ? 0 : quantity}
                    </span>
                    <button
                      onClick={() => {
                        if (quantity >= maxStock) {
                          showToast("Stock Limit Reached", `Only ${maxStock} items available in stock.`, "warning");
                          return;
                        }
                        setQuantity(Math.min(maxStock, quantity + 1));
                      }}
                      disabled={isOutOfStock || quantity >= maxStock}
                      className="px-3 py-1.5 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                      aria-label="Increase quantity"
                    >
                      <Plus className="w-3.5 h-3.5" />
                    </button>
                  </div>
                  {!isOutOfStock && maxStock <= 10 && (
                    <span className="text-[11px] text-[#913638] font-medium">
                      Max {maxStock} available
                    </span>
                  )}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                  {isOutOfStock ? (
                    <>
                      <button
                        disabled
                        className="w-full py-3.5 px-6 rounded-full bg-stone-200 text-stone-500 text-xs sm:text-sm font-semibold cursor-not-allowed flex items-center justify-center gap-2"
                      >
                        <ShoppingBag className="w-4 h-4" />
                        <span>Out of Stock</span>
                      </button>
                      <button
                        disabled
                        className="w-full py-3.5 px-6 rounded-full bg-stone-100 text-stone-400 border border-stone-200 text-xs sm:text-sm font-semibold cursor-not-allowed flex items-center justify-center gap-2"
                      >
                        <span>Sold Out</span>
                        <ArrowRight className="w-4 h-4" />
                      </button>
                    </>
                  ) : (
                    <>
                      <button
                        onClick={handleAddToCart}
                        className="w-full py-3.5 px-6 rounded-full bg-[#913638] text-white text-xs sm:text-sm font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <ShoppingBag className="w-4 h-4" />
                        <span>Add to Cart</span>
                      </button>
                      <button
                        onClick={handleBuyNow}
                        className="w-full py-3.5 px-6 rounded-full bg-white text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs sm:text-sm font-semibold active:scale-[0.98] shadow-xs transition-all flex items-center justify-center gap-2 cursor-pointer"
                      >
                        <span>Buy Now</span>
                        <ArrowRight className="w-4 h-4" />
                      </button>
                    </>
                  )}
                </div>
              </div>

              {/* Guarantees */}
              <div className="grid grid-cols-3 gap-2 pt-4 border-t border-[#E7D1CC] text-[11px] text-[#786864]">
                <div className="flex items-center gap-1.5">
                  <Truck className="w-4 h-4 text-[#913638] shrink-0" />
                  <span>Pan India Delivery</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <ShieldCheck className="w-4 h-4 text-[#913638] shrink-0" />
                  <span>Handmade Guarantee</span>
                </div>
                <div className="flex items-center gap-1.5">
                  <RotateCcw className="w-4 h-4 text-[#913638] shrink-0" />
                  <span>Safe Packaging</span>
                </div>
              </div>

            </div>
          </div>

        </div>

        {/* Tabbed Product Details */}
        <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-10 shadow-boutique mb-16">
          <div className="flex items-center gap-4 sm:gap-8 border-b border-[#E7D1CC] pb-4 mb-6 overflow-x-auto no-scrollbar">
            <button
              onClick={() => setActiveTab("desc")}
              className={`font-serif-luxury text-base sm:text-lg font-bold pb-2 transition-all whitespace-nowrap relative cursor-pointer ${
                activeTab === "desc"
                  ? "text-[#913638]"
                  : "text-[#786864] hover:text-[#2E211E]"
              }`}
            >
              <span>Product Description</span>
              {activeTab === "desc" && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#913638] rounded-full" />
              )}
            </button>

            <button
              onClick={() => setActiveTab("specs")}
              className={`font-serif-luxury text-base sm:text-lg font-bold pb-2 transition-all whitespace-nowrap relative cursor-pointer ${
                activeTab === "specs"
                  ? "text-[#913638]"
                  : "text-[#786864] hover:text-[#2E211E]"
              }`}
            >
              <span>Crafting & Specifications</span>
              {activeTab === "specs" && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#913638] rounded-full" />
              )}
            </button>

            <button
              onClick={() => setActiveTab("reviews")}
              className={`font-serif-luxury text-base sm:text-lg font-bold pb-2 transition-all whitespace-nowrap relative cursor-pointer ${
                activeTab === "reviews"
                  ? "text-[#913638]"
                  : "text-[#786864] hover:text-[#2E211E]"
              }`}
            >
              <span>Artisan Reviews ({product.reviewCount})</span>
              {activeTab === "reviews" && (
                <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#913638] rounded-full" />
              )}
            </button>
          </div>

          {/* Tab 1: Description */}
          {activeTab === "desc" && (
            <div className="space-y-4 text-sm text-[#786864] leading-relaxed max-w-3xl">
              <FormattedHtmlText
                content={product.description}
                className="space-y-3 [&_p]:mb-2 [&_p:last-child]:mb-0 [&_strong]:text-[#2E211E] [&_strong]:font-semibold [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_li]:my-1"
              />
              <p>
                Each stitch is tightly looped by hand to ensure longevity, color fastness, and structure retention. Unlike factory machine-made knits, our artisanal crochet possesses unmistakable warmth, depth, and personality.
              </p>
              <div className="p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] flex items-center gap-3 text-xs text-[#2E211E]">
                <FlowerIcon className="w-5 h-5 text-[#913638] shrink-0" />
                <span>
                  Every creation comes nestled in our signature KNOTELLE recycled boutique box, tied with satin ribbon and sprinkled with dried lavender buds.
                </span>
              </div>
            </div>
          )}

          {/* Tab 2: Specifications */}
          {activeTab === "specs" && (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-3xl text-sm">
              <div className="space-y-3">
                <h4 className="font-bold text-[#2E211E] flex items-center gap-2">
                  <FlowerIcon className="w-4 h-4 text-[#913638]" />
                  <span>Materials Used</span>
                </h4>
                <ul className="space-y-1 text-[#786864] text-xs">
                  {product.materials?.map((mat, i) => (
                    <li key={i} className="flex items-center gap-2">
                      <span className="w-1.5 h-1.5 rounded-full bg-[#913638]" />
                      <span>{mat}</span>
                    </li>
                  )) || <li>100% Premium Milk Cotton Yarn</li>}
                </ul>
              </div>

              <div className="space-y-3">
                <h4 className="font-bold text-[#2E211E] flex items-center gap-2">
                  <Ruler className="w-4 h-4 text-[#913638]" />
                  <span>Dimensions & Time</span>
                </h4>
                <div className="space-y-1 text-[#786864] text-xs">
                  <p>Dimensions: {product.dimensions || "Standard Boutique Sizing"}</p>
                  <p>Handcrafting Time: {product.craftTime || "4-6 hours"}</p>
                </div>
              </div>
            </div>
          )}

          {/* Tab 3: Reviews */}
          {activeTab === "reviews" && (
            <div className="space-y-6 max-w-3xl">
              <div className="flex items-center gap-4 p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC]">
                <div className="text-center pr-4 border-r border-[#E7D1CC]">
                  <span className="font-serif-luxury text-3xl font-bold text-[#913638]">
                    {(Number(product.rating) || 5).toFixed(1)}
                  </span>
                  <p className="text-[10px] text-[#786864]">out of 5.0</p>
                </div>
                <div>
                  <StarRating rating={Number(product.rating) || 5} size="md" />
                  <p className="text-xs text-[#786864] mt-1">
                    Based on {product.reviewCount || 0} verified purchases
                  </p>
                </div>
              </div>

              {product.reviews && product.reviews.length > 0 ? (
                <div className="space-y-4">
                  {product.reviews.map((rev) => (
                    <div key={rev.id} className="p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] space-y-2">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <span className="text-xs font-bold text-[#2E211E]">{rev.userName}</span>
                          {rev.verified && (
                            <span className="text-[10px] text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full font-medium">
                              Verified Buyer
                            </span>
                          )}
                        </div>
                        <span className="text-[10px] text-[#786864]">{rev.date}</span>
                      </div>
                      <StarRating rating={rev.rating} size="sm" />
                      <p className="text-xs text-[#786864] leading-relaxed">{rev.comment}</p>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-xs text-[#786864]">
                  Be the first to leave a review after your handmade creation arrives!
                </p>
              )}
            </div>
          )}
        </div>



        {/* Related Products */}
        {relatedProducts.length > 0 && (
          <div>
            <div className="flex items-center justify-between mb-8">
              <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
                You May Also Adore
              </h2>
              <Link
                href={`/category/${product.categorySlug}`}
                className="text-xs font-semibold text-[#913638] hover:underline"
              >
                More in {product.category} →
              </Link>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
              {relatedProducts.map((rel) => (
                <ProductCard key={rel.id} product={rel} />
              ))}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
