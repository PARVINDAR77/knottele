"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound, useRouter, useParams, usePathname } from "next/navigation";
import {
  Heart,
  ShoppingBag,
  Sparkles,
  Truck,
  ShieldCheck,
  RotateCcw,
  Check,
  Plus,
  Minus,
  ArrowRight,
  Share2,
  Ruler,
} from "lucide-react";
import { PRODUCTS } from "@/data/products";
import { ProductColor } from "@/types/product";
import { useCart } from "@/context/CartContext";
import { useWishlist } from "@/context/WishlistContext";
import { useToast } from "@/context/ToastContext";
import { StarRating } from "@/components/ui/StarRating";
import { Badge } from "@/components/ui/Badge";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { ProductCard } from "@/components/ui/ProductCard";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { fetchProductBySlug, fetchRelatedProducts } from "@/lib/api";
import { Product } from "@/types/product";

interface ProductDetailViewProps {
  slug: string;
}

function FormattedHtmlText({ content, className = "" }: { content?: string; className?: string }) {
  if (!content) return null;

  // Check if content contains HTML tags (e.g. <p>, <br>, <strong>, etc.)
  const hasHtml = /<[a-z][\s\S]*>/i.test(content);

  if (hasHtml) {
    return (
      <div
        className={className}
        dangerouslySetInnerHTML={{ __html: content }}
      />
    );
  }

  return (
    <div className={className}>
      {content.split("\n\n").map((para, i) => (
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

  // 2. Authoritative slug: On client, pathname represents the actual product the user clicked.
  // In Next.js SSG fallback template serving, propSlug was baked into the static HTML template (e.g. "botanical-sprout-bookmark").
  // So if decodedPathSlug exists, it overrides propSlug whenever they differ.
  const slug = (decodedPathSlug && decodedPathSlug !== "index")
    ? decodedPathSlug
    : (rawParamSlug || propSlug);

  // Detect if this page was loaded as a fallback HTML template for a different dynamic database product
  const isFallbackTemplateMismatch = Boolean(
    decodedPathSlug && propSlug && decodedPathSlug !== propSlug
  );

  const fallbackProduct = isFallbackTemplateMismatch
    ? PRODUCTS.find((p) => p.slug === slug)
    : PRODUCTS.find(
        (p) =>
          p.slug === slug ||
          (slug === "cute-bunny-amigurumi-keychain" && p.slug === "cute-bunny-keychain") ||
          (slug === "cute-bunny-keychain" && p.slug === "cute-bunny-amigurumi-keychain")
      );
  const [liveProduct, setLiveProduct] = useState<Product | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(!fallbackProduct);
  const [fetchAttempted, setFetchAttempted] = useState<boolean>(false);
  const product = liveProduct || fallbackProduct;
  const [relatedProductsList, setRelatedProductsList] = useState<Product[]>([]);

  const { addItem, setIsCartOpen } = useCart();
  const { toggleWishlist, isInWishlist } = useWishlist();
  const { showToast } = useToast();

  const [selectedImageIndex, setSelectedImageIndex] = useState(0);
  const [selectedColor, setSelectedColor] = useState<ProductColor>(
    product?.colors?.[0] || { name: "Default", hex: "#FFF" }
  );
  const [selectedSize, setSelectedSize] = useState<string>(
    product?.sizes?.[0] || "Standard"
  );
  const [customText, setCustomText] = useState("");
  const [quantity, setQuantity] = useState(1);
  const [activeTab, setActiveTab] = useState<"desc" | "specs" | "reviews">("desc");

  useEffect(() => {
    let isMounted = true;
    if (!fallbackProduct || isFallbackTemplateMismatch) {
      setLiveProduct(null);
      setIsLoading(true);
      setFetchAttempted(false);
    }
    fetchProductBySlug(slug)
      .then((apiProd) => {
        if (isMounted) {
          if (apiProd) {
            setLiveProduct(apiProd);
            setSelectedImageIndex(0);
            if (apiProd.name && typeof document !== "undefined") {
              document.title = `${apiProd.name} | Knotelle`;
            }
            if (apiProd.colors && apiProd.colors[0]) {
              setSelectedColor(apiProd.colors[0]);
            }
            if (apiProd.sizes && apiProd.sizes[0]) {
              setSelectedSize(apiProd.sizes[0]);
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
    notFound();
  }

  if (!product) {
    return null;
  }

  const isWishlisted = isInWishlist(product.id);
  const maxStock = typeof product.stock === "number" ? product.stock : 99;
  const isOutOfStock = maxStock <= 0 || product.inStock === false;

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
      color: selectedColor,
      size: selectedSize,
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
      color: selectedColor,
      size: selectedSize,
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
            { label: product.category, href: `/category/${product.categorySlug}` },
            { label: product.name },
          ]}
          className="mb-8"
        />

        {/* Product Showcase: Left Gallery, Right Details */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-10 shadow-boutique mb-16">
          
          {/* Left Gallery (Spans 6 cols on lg) */}
          <div className="lg:col-span-6 space-y-4">
            {/* Main Stage Image with Zoom feel */}
            <div className="relative aspect-[4/4.5] w-full rounded-2xl overflow-hidden bg-[#FFF9F6] border border-[#E7D1CC]">
              <Image
                src={product.images[selectedImageIndex] || product.images[0]}
                alt={product.name}
                fill
                priority
                sizes="(max-width: 1024px) 100vw, 50vw"
                className="object-cover object-center"
              />

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
            {product.images.length > 1 && (
              <div className="flex items-center gap-3 overflow-x-auto pb-2">
                {product.images.map((img, idx) => (
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
                      src={img}
                      alt={`${product.name} preview ${idx + 1}`}
                      fill
                      sizes="80px"
                      className="object-cover"
                    />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Right Product Info (Spans 6 cols on lg) */}
          <div className="lg:col-span-6 flex flex-col justify-between space-y-6">
            <div className="space-y-4">
              
              {/* Category & Rating */}
              <div className="flex items-center justify-between gap-4">
                <Link
                  href={`/category/${product.categorySlug}`}
                  className="text-xs uppercase tracking-widest text-[#913638] font-semibold hover:underline"
                >
                  {product.category}
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
                <StarRating rating={product.rating} size="md" showNumber reviewCount={product.reviewCount} />
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
                  ₹{product.price.toLocaleString("en-IN")}
                </span>
                {product.comparePrice && (
                  <span className="text-base text-[#786864] line-through">
                    ₹{product.comparePrice.toLocaleString("en-IN")}
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

              {/* Color Selection */}
              {product.colors && product.colors.length > 0 && (
                <div className="space-y-2 pt-2 border-t border-[#E7D1CC]">
                  <div className="flex items-center justify-between text-xs">
                    <span className="font-semibold text-[#2E211E]">
                      Yarn Color:{" "}
                      <strong className="text-[#913638]">{selectedColor.name}</strong>
                    </span>
                  </div>
                  <div className="flex flex-wrap gap-2.5">
                    {product.colors.map((color) => (
                      <button
                        key={color.name}
                        onClick={() => setSelectedColor(color)}
                        className={`flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-medium transition-all active:scale-[0.98] cursor-pointer ${
                          selectedColor.name === color.name
                            ? "border-[#913638] bg-[#FCE9E5] text-[#913638] ring-1 ring-[#913638]"
                            : "border-[#E7D1CC] bg-white hover:bg-[#FFF9F6] text-[#2E211E]"
                        }`}
                      >
                        <span
                          className="w-3.5 h-3.5 rounded-full border border-black/10 shrink-0"
                          style={{ backgroundColor: color.hex }}
                        />
                        <span>{color.name}</span>
                        {selectedColor.name === color.name && (
                          <Check className="w-3 h-3 text-[#913638]" />
                        )}
                      </button>
                    ))}
                  </div>
                </div>
              )}

              {/* Size Selection (if available) */}
              {product.sizes && product.sizes.length > 0 && (
                <div className="space-y-2 pt-2">
                  <span className="text-xs font-semibold text-[#2E211E] block">
                    Size / Variant:
                  </span>
                  <div className="flex flex-wrap gap-2">
                    {product.sizes.map((size) => (
                      <button
                        key={size}
                        onClick={() => setSelectedSize(size)}
                        className={`px-3.5 py-1.5 rounded-full border text-xs font-medium transition-all active:scale-[0.98] cursor-pointer ${
                          selectedSize === size
                            ? "border-[#913638] bg-[#913638] text-white shadow-xs"
                            : "border-[#E7D1CC] bg-white hover:bg-[#FCE9E5] hover:text-[#913638] text-[#2E211E]"
                        }`}
                      >
                        {size}
                      </button>
                    ))}
                  </div>
                </div>
              )}



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
                    {product.rating.toFixed(1)}
                  </span>
                  <p className="text-[10px] text-[#786864]">out of 5.0</p>
                </div>
                <div>
                  <StarRating rating={product.rating} size="md" />
                  <p className="text-xs text-[#786864] mt-1">
                    Based on {product.reviewCount} verified purchases
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
