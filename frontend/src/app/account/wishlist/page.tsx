"use client";

import React from "react";
import Image from "next/image";
import Link from "next/link";
import { Heart, ShoppingBag, Trash2 } from "lucide-react";
import { useWishlist } from "@/context/WishlistContext";
import { useCart } from "@/context/CartContext";
import { normalizeImageUrl } from "@/lib/api";

export default function WishlistPage() {
  const { wishlistItems, removeFromWishlist } = useWishlist();
  const { addItem, setIsCartOpen } = useCart();

  const handleAddToCart = (product: any) => {
    addItem(product, 1, {
      color: product.colors?.[0],
      size: product.sizes?.[0],
    });
    setIsCartOpen(true);
  };

  return (
    <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6 animate-in fade-in">
      <div className="flex items-center justify-between pb-4 border-b border-[#E7D1CC]">
        <div>
          <h1 className="font-serif-luxury text-2xl font-bold text-[#2E211E]">
            My Wishlist
          </h1>
          <p className="text-xs text-[#786864] mt-0.5">
            Your saved handmade favorites and dream creations.
          </p>
        </div>
        <span className="text-xs font-semibold bg-[#FCE9E5] text-[#913638] px-3.5 py-1 rounded-full border border-[#E7D1CC]">
          {wishlistItems.length} {wishlistItems.length === 1 ? "Item" : "Items"} Saved
        </span>
      </div>

      {wishlistItems.length === 0 ? (
        <div className="py-12 text-center">
          <div className="w-16 h-16 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] flex items-center justify-center text-[#913638] mx-auto mb-3">
            <Heart className="w-8 h-8 text-[#913638]" />
          </div>
          <p className="text-base font-bold text-[#2E211E]">Your wishlist is currently empty</p>
          <p className="text-xs text-[#786864] mt-1 mb-6 max-w-sm mx-auto">
            Tap the little heart on any crochet bouquet, bag, or plushie to save it here.
          </p>
          <Link
            href="/shop"
            className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-block shadow-xs"
          >
            Explore Creations →
          </Link>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
          {wishlistItems.map((product) => {
            const isStock = product.inStock !== false && (product.stock ?? 10) > 0;
            const rawImg = product.main_image || (product.images && product.images[0]);
            const imgSrc = normalizeImageUrl(rawImg, "/images/products/bunny-keychain.jpg");

            return (
              <div
                key={product.id}
                className="group relative flex flex-col bg-white rounded-3xl border border-[#E7D1CC] overflow-hidden shadow-boutique hover:shadow-boutique-hover transition-all duration-300"
              >
                {/* Image */}
                <div className="relative aspect-[4/4] w-full bg-[#FFF9F6] overflow-hidden">
                  <Link href={`/product/${(product.slug || "").trim().toLowerCase().replace(/[\s_]+/g, "-")}`} className="block w-full h-full relative">
                    <Image
                      src={imgSrc}
                      alt={product.name}
                      fill
                      sizes="(max-width: 640px) 100vw, 33vw"
                      className="object-cover object-center group-hover:scale-105 transition-transform duration-500"
                    />
                  </Link>

                  {/* Stock Status Tag */}
                  <div className="absolute top-3 left-3 z-10">
                    <span
                      className={`text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider shadow-xs ${
                        isStock
                          ? "bg-emerald-50 text-emerald-700 border border-emerald-200"
                          : "bg-rose-50 text-rose-700 border border-rose-200"
                      }`}
                    >
                      {isStock ? "In Stock" : "Out of Stock"}
                    </span>
                  </div>

                  {/* Remove Button Top-Right */}
                  <button
                    onClick={() => removeFromWishlist(String(product.db_id || product.id))}
                    aria-label="Remove from Wishlist"
                    className="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white/90 backdrop-blur-md border border-[#E7D1CC] flex items-center justify-center text-[#913638] hover:bg-[#913638] hover:text-white transition-all shadow-xs active:scale-90 cursor-pointer"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>

                {/* Details */}
                <div className="p-4 flex flex-col flex-1 bg-white">
                  <span className="text-[11px] font-semibold text-[#786864] uppercase tracking-wider mb-0.5">
                    {product.category || "Handcrafted"}
                  </span>

                  <Link
                    href={`/product/${(product.slug || "").trim().toLowerCase().replace(/[\s_]+/g, "-")}`}
                    className="font-serif-luxury text-base font-bold text-[#2E211E] hover:text-[#913638] transition-colors line-clamp-1 mb-2"
                  >
                    {product.name}
                  </Link>

                  <div className="flex items-baseline gap-2 mb-4">
                    <span className="text-base font-bold text-[#913638]">
                      ₹{product.price.toLocaleString("en-IN")}
                    </span>
                    {(product.comparePrice || product.compareAtPrice) && (
                      <span className="text-xs text-[#786864] line-through">
                        ₹{(product.comparePrice || product.compareAtPrice)?.toLocaleString("en-IN")}
                      </span>
                    )}
                  </div>

                  {/* Action Buttons */}
                  <div className="mt-auto grid grid-cols-2 gap-2 pt-2 border-t border-[#E7D1CC]/60">
                    <button
                      onClick={() => handleAddToCart(product)}
                      disabled={!isStock}
                      className={`py-2 px-3 rounded-full text-xs font-semibold flex items-center justify-center gap-1.5 transition-all active:scale-[0.98] shadow-xs cursor-pointer ${
                        isStock
                          ? "bg-[#913638] text-white hover:bg-[#74292B]"
                          : "bg-gray-100 text-gray-400 cursor-not-allowed"
                      }`}
                    >
                      <ShoppingBag className="w-3.5 h-3.5" />
                      <span>Add to Cart</span>
                    </button>

                    <button
                      onClick={() => removeFromWishlist(String(product.db_id || product.id))}
                      className="py-2 px-3 rounded-full bg-white text-[#913638] border border-[#E7D1CC] hover:bg-[#FCE9E5] text-xs font-semibold flex items-center justify-center gap-1.5 transition-all active:scale-[0.98] cursor-pointer"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                      <span>Remove</span>
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
