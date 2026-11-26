"use client";

import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import {
  Trash2,
  Plus,
  Minus,
  ShoppingBag,
  ArrowRight,
  Sparkles,
  Heart,
  Truck,
  Tag,
  ShieldCheck,
  X,
} from "lucide-react";
import { useCart } from "@/context/CartContext";
import { useWishlist } from "@/context/WishlistContext";
import { useToast } from "@/context/ToastContext";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { normalizeImageUrl } from "@/lib/api";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { CartItem } from "@/types/cart";

export default function CartPage() {
  const {
    items,
    removeItem,
    updateQuantity,
    subtotal,
    shipping,
    discount,
    total,
    promoCode,
    applyPromoCode,
    removePromoCode,
    amountToFreeShipping,
    freeShippingThreshold,
  } = useCart();

  const { toggleWishlist, addToWishlist, isInWishlist } = useWishlist();
  const { showToast } = useToast();
  const [couponInput, setCouponInput] = useState("");
  const [giftNote, setGiftNote] = useState("");
  const [includeGiftWrap, setIncludeGiftWrap] = useState(false);
  const [itemToConfirm, setItemToConfirm] = useState<CartItem | null>(null);

  const handleMoveToWishlist = async (item: CartItem) => {
    await addToWishlist(item.product);
    removeItem(item.id, true);
    showToast("Moved to Wishlist 💕", `${item.product.name} moved to your wishlist.`, "wishlist");
    setItemToConfirm(null);
  };

  const handleRemoveFromCart = (item: CartItem) => {
    removeItem(item.id, false);
    setItemToConfirm(null);
  };

  const handleApplyCoupon = (e: React.FormEvent) => {
    e.preventDefault();
    if (couponInput.trim()) {
      applyPromoCode(couponInput);
      setCouponInput("");
    }
  };

  const freeShippingProgress = Math.min(
    100,
    Math.round(((freeShippingThreshold - amountToFreeShipping) / freeShippingThreshold) * 100)
  );

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumbs */}
        <Breadcrumbs items={[{ label: "Shopping Cart" }]} className="mb-6" />

        {/* Page Title */}
        <div className="flex items-center justify-between mb-8 pb-4 border-b border-[#E7D1CC]">
          <div>
            <h1 className="font-serif-luxury text-2xl sm:text-3xl lg:text-4xl font-bold text-[#2E211E]">
              Your Cart
            </h1>
            <p className="text-xs sm:text-sm text-[#786864] mt-1">
              Review your handcrafted items before we prepare our yarn.
            </p>
          </div>
          <Link
            href="/shop"
            className="text-xs font-semibold text-[#913638] hover:underline flex items-center gap-1"
          >
            <span>Continue Shopping</span>
            <ArrowRight className="w-3 h-3" />
          </Link>
        </div>

        {items.length === 0 ? (
          /* Empty Cart State */
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-16 text-center max-w-xl mx-auto shadow-boutique">
            <div className="w-20 h-20 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] flex items-center justify-center text-[#913638] mx-auto mb-6">
              <ShoppingBag className="w-10 h-10 stroke-1" />
            </div>

            <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E] mb-2">
              Your cart is waiting for a little handmade happiness.
            </h2>

            <p className="text-sm text-[#786864] mb-8 leading-relaxed">
              Explore our bestselling crochet bouquets, lovable amigurumi toys, and custom creations made with love.
            </p>

            <Link
              href="/shop"
              className="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all cursor-pointer"
            >
              <span>Explore Boutique Collections</span>
              <ArrowRight className="w-4 h-4" />
            </Link>
          </div>
        ) : (
          /* Cart Content Layout: Items on Left, Order Summary on Right */
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {/* Left Items Column (Spans 8 cols on lg) */}
            <div className="lg:col-span-8 space-y-6">
              
              {/* Free Shipping Progress Ribbon */}
              <div className="p-4 rounded-2xl bg-white border border-[#E7D1CC] shadow-xs">
                <div className="flex items-center justify-between text-xs font-medium text-[#2E211E] mb-2">
                  <span className="flex items-center gap-1.5 text-emerald-800 font-semibold">
                    <Truck className="w-4 h-4 text-[#913638]" />
                    <span>🎉 FREE Pan-India Shipping on All Orders!</span>
                  </span>
                  <span className="text-xs text-emerald-800 font-bold">100%</span>
                </div>
                <div className="w-full h-2 bg-[#FCE9E5] rounded-full overflow-hidden">
                  <div
                    className="h-full bg-[#913638] transition-all duration-500 rounded-full"
                    style={{ width: `${freeShippingProgress}%` }}
                  />
                </div>
              </div>

              {/* Items List */}
              <div className="bg-white rounded-3xl border border-[#E7D1CC] p-4 sm:p-6 shadow-boutique divide-y divide-[#E7D1CC]/70">
                {items.map((item) => (
                  <div key={item.id} className="py-5 first:pt-0 last:pb-0 flex flex-col sm:flex-row gap-4 sm:gap-5 items-start sm:items-center justify-between">
                    
                    {/* Item Image + Details */}
                    <div className="flex items-start gap-4 flex-1 min-w-0">
                      <Link
                        href={`/product/${item.product.slug}`}
                        className="relative w-20 h-20 sm:w-28 sm:h-28 rounded-2xl overflow-hidden bg-[#FFF9F6] shrink-0 border border-[#E7D1CC]"
                      >
                        <Image
                          src={normalizeImageUrl(item.product.main_image || item.product.images?.[0], "/images/products/bunny-keychain.jpg")}
                          alt={item.product.name}
                          fill
                          sizes="112px"
                          className="object-cover"
                        />
                      </Link>

                      <div className="space-y-1 min-w-0">
                        <span className="text-[10px] uppercase tracking-wider text-[#786864] font-medium">
                          {item.product.category}
                        </span>
                        <Link
                          href={`/product/${item.product.slug}`}
                          className="font-serif-luxury text-base sm:text-lg font-bold text-[#2E211E] hover:text-[#913638] transition-colors line-clamp-1 block"
                        >
                          {item.product.name}
                        </Link>

                        {/* Customization Details */}
                        {item.customization && (
                          <div className="text-xs text-[#786864] space-y-0.5 pt-0.5">
                            {item.customization.color && (
                              <div className="flex items-center gap-1.5">
                                <span
                                  className="w-2.5 h-2.5 rounded-full border border-black/10 shrink-0"
                                  style={{ backgroundColor: item.customization.color.hex }}
                                />
                                <span>Color: {item.customization.color.name}</span>
                              </div>
                            )}
                            {item.customization.size && (
                              <p>Size: {item.customization.size}</p>
                            )}
                            {item.customization.customText && (
                              <p className="text-[#913638] font-medium">
                                Tag Note: &ldquo;{item.customization.customText}&rdquo;
                              </p>
                            )}
                          </div>
                        )}

                        <div className="pt-2 flex items-center gap-4 text-xs">
                          <button
                            onClick={() => toggleWishlist(item.product)}
                            className="text-[#786864] hover:text-[#913638] flex items-center gap-1 transition-colors cursor-pointer"
                          >
                            <Heart className={`w-3.5 h-3.5 ${isInWishlist(item.product.id) ? "fill-[#913638] text-[#913638]" : ""}`} />
                            <span>Save to Wishlist</span>
                          </button>
                          <span className="text-[#E7D1CC]">|</span>
                          <button
                            onClick={() => setItemToConfirm(item)}
                            className="text-[#786864] hover:text-[#913638] flex items-center gap-1 transition-colors cursor-pointer"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                            <span>Remove</span>
                          </button>
                        </div>
                      </div>
                    </div>

                    {/* Quantity Selector & Price */}
                    <div className="flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto gap-4 pt-3 sm:pt-0 border-t sm:border-t-0 border-[#E7D1CC]/60">
                      <div className="flex items-center border border-[#E7D1CC] rounded-full bg-white overflow-hidden shadow-xs">
                        <button
                          onClick={() => {
                            if (item.quantity > 1) {
                              updateQuantity(item.id, item.quantity - 1);
                            } else {
                              setItemToConfirm(item);
                            }
                          }}
                          className="px-2.5 py-1 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer"
                        >
                          <Minus className="w-3 h-3" />
                        </button>
                        <span className="px-3 text-xs font-bold text-[#2E211E] min-w-[24px] text-center">
                          {item.quantity}
                        </span>
                        <button
                          onClick={() => {
                            const maxStock = typeof item.product?.stock === "number" ? item.product.stock : 99;
                            if (item.quantity >= maxStock) {
                              showToast("Stock Limit Reached", `Only ${maxStock} items available in stock.`, "warning");
                              return;
                            }
                            updateQuantity(item.id, item.quantity + 1);
                          }}
                          disabled={item.quantity >= (typeof item.product?.stock === "number" ? item.product.stock : 99)}
                          className="px-2.5 py-1 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                          <Plus className="w-3 h-3" />
                        </button>
                      </div>

                      <div className="text-right">
                        <span className="font-bold text-base sm:text-lg text-[#913638]">
                          ₹{(item.price * item.quantity).toLocaleString("en-IN")}
                        </span>
                        <p className="text-[10px] text-[#786864]">
                          (₹{item.price.toLocaleString("en-IN")} each)
                        </p>
                        {typeof item.product?.stock === "number" && item.product.stock <= 5 && item.product.stock > 0 && (
                          <p className="text-[10px] text-amber-700 font-medium">Only {item.product.stock} in stock</p>
                        )}
                        {(item.product?.stock === 0 || item.product?.inStock === false) && (
                          <p className="text-[10px] text-rose-600 font-medium">Out of stock</p>
                        )}
                      </div>
                    </div>

                  </div>
                ))}
              </div>

              {/* Gift Wrap & Note Accordion */}
              <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-4">
                <div className="flex items-center justify-between">
                  <label className="flex items-center gap-2.5 text-xs font-bold text-[#2E211E] cursor-pointer">
                    <input
                      type="checkbox"
                      checked={includeGiftWrap}
                      onChange={(e) => setIncludeGiftWrap(e.target.checked)}
                      className="rounded border-[#E7D1CC] text-[#913638] focus:ring-[#913638]"
                    />
                    <Sparkles className="w-4 h-4 text-[#C69A5A]" />
                    <span>Include Luxury Handmade Gift Wrap (+₹99)</span>
                  </label>
                </div>

                {includeGiftWrap && (
                  <div className="space-y-2 pt-2 animate-in fade-in">
                    <label className="text-xs font-medium text-[#786864] block">
                      Handwritten Letter / Card Message:
                    </label>
                    <textarea
                      rows={2}
                      value={giftNote}
                      onChange={(e) => setGiftNote(e.target.value)}
                      placeholder="Write your personal message. We will handwrite this with gold ink on pressed handmade paper."
                      className="w-full px-3.5 py-2 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] placeholder-[#786864]/60 focus:outline-none focus:border-[#913638]"
                    />
                  </div>
                )}
              </div>

            </div>

            {/* Right Order Summary Column (Spans 4 cols on lg) */}
            <div className="lg:col-span-4 space-y-6 sticky top-28">
              
              <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6">
                <h3 className="font-serif-luxury text-xl font-bold text-[#2E211E] pb-3 border-b border-[#E7D1CC]">
                  Order Summary
                </h3>

                {/* Promo Code Input */}
                <form onSubmit={handleApplyCoupon} className="space-y-2">
                  <label className="text-xs font-semibold text-[#2E211E] flex items-center gap-1.5">
                    <Tag className="w-3.5 h-3.5 text-[#913638]" />
                    <span>Promo or Gift Voucher:</span>
                  </label>
                  {promoCode ? (
                    <div className="flex items-center justify-between p-2.5 rounded-xl bg-[#FCE9E5] border border-[#E7D1CC] text-xs">
                      <span className="font-bold text-[#913638]">{promoCode} Applied!</span>
                      <button
                        type="button"
                        onClick={removePromoCode}
                        className="text-xs font-semibold text-[#786864] hover:text-[#913638] cursor-pointer"
                      >
                        Remove
                      </button>
                    </div>
                  ) : (
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value)}
                        placeholder="Try KNOTELLE10"
                        className="flex-1 px-3.5 py-2 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-xs uppercase text-[#2E211E] focus:outline-none focus:border-[#913638]"
                      />
                      <button
                        type="submit"
                        className="px-5 py-2 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                      >
                        Apply
                      </button>
                    </div>
                  )}
                </form>

                {/* Price Breakdown */}
                <div className="space-y-3 text-xs text-[#786864] pt-2 border-t border-[#E7D1CC]">
                  <div className="flex justify-between">
                    <span>Subtotal</span>
                    <span className="font-semibold text-[#2E211E]">
                      ₹{subtotal.toLocaleString("en-IN")}
                    </span>
                  </div>

                  {discount > 0 && (
                    <div className="flex justify-between text-[#913638]">
                      <span>Discount ({promoCode})</span>
                      <span className="font-bold">
                        -₹{discount.toLocaleString("en-IN")}
                      </span>
                    </div>
                  )}

                  {includeGiftWrap && (
                    <div className="flex justify-between">
                      <span>Luxury Gift Wrap</span>
                      <span className="font-semibold text-[#2E211E]">₹99</span>
                    </div>
                  )}

                  <div className="flex justify-between">
                    <span>Shipping</span>
                    <span>
                      <strong className="text-emerald-700 font-bold">FREE</strong>
                    </span>
                  </div>

                  <div className="flex justify-between text-base font-bold text-[#2E211E] pt-3 border-t border-[#E7D1CC]">
                    <span>Total Amount</span>
                    <span className="text-[#913638] text-xl">
                      ₹{(total + (includeGiftWrap ? 99 : 0)).toLocaleString("en-IN")}
                    </span>
                  </div>
                </div>

                {/* Checkout Button */}
                <Link
                  href="/checkout"
                  className="w-full py-4 px-6 rounded-full bg-[#913638] text-white text-xs sm:text-sm font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>Proceed to Checkout</span>
                  <ArrowRight className="w-4 h-4" />
                </Link>

                <div className="pt-2 flex items-center justify-center gap-2 text-[11px] text-[#786864]">
                  <ShieldCheck className="w-4 h-4 text-emerald-700" />
                  <span>Guaranteed safe & secure checkout</span>
                </div>
              </div>

            </div>

          </div>
        )}

      </div>

      {/* Move to Wishlist or Remove Confirmation Modal */}
      {itemToConfirm && (
        <div
          className="fixed inset-0 z-50 bg-stone-900/50 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-200"
          onClick={(e) => {
            if (e.target === e.currentTarget) setItemToConfirm(null);
          }}
        >
          <div className="bg-white rounded-3xl p-6 w-full max-w-sm border border-[#E7D1CC] shadow-2xl space-y-4 animate-in zoom-in-95 duration-200">
            <div className="flex items-start justify-between gap-3">
              <div className="flex items-center gap-3 min-w-0">
                <div className="relative w-14 h-14 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] overflow-hidden shrink-0">
                  <Image
                    src={normalizeImageUrl(itemToConfirm.product.main_image || itemToConfirm.product.images?.[0], "/images/products/bunny-keychain.jpg")}
                    alt={itemToConfirm.product.name}
                    fill
                    sizes="56px"
                    className="object-cover"
                  />
                </div>
                <div className="min-w-0">
                  <h4 className="font-serif-luxury text-sm font-bold text-[#2E211E] truncate">
                    {itemToConfirm.product.name}
                  </h4>
                  <p className="text-xs text-[#913638] font-bold mt-0.5">
                    ₹{(itemToConfirm.price * itemToConfirm.quantity).toLocaleString("en-IN")}
                  </p>
                </div>
              </div>
              <button
                onClick={() => setItemToConfirm(null)}
                className="p-1.5 rounded-full text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer shrink-0"
                aria-label="Close"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            <div className="bg-[#FFF9F6] rounded-2xl p-3.5 border border-[#E7D1CC]/70 text-xs text-[#786864] leading-relaxed">
              Would you like to move this handcrafted item to your <strong className="text-[#913638] font-semibold">Wishlist</strong> to save for later, or <strong className="text-[#2E211E] font-semibold">remove</strong> it from your cart?
            </div>

            <div className="grid grid-cols-2 gap-2.5 pt-1">
              <button
                type="button"
                onClick={() => handleMoveToWishlist(itemToConfirm)}
                className="flex items-center justify-center gap-1.5 py-3 px-3 rounded-2xl border-2 border-[#913638] text-[#913638] bg-[#FFF9F6] hover:bg-[#FCE9E5] active:scale-[0.98] text-xs font-bold transition-all shadow-xs cursor-pointer text-center"
              >
                <Heart className="w-4 h-4 fill-[#913638] shrink-0" />
                <span className="truncate">Move to Wishlist</span>
              </button>
              <button
                type="button"
                onClick={() => handleRemoveFromCart(itemToConfirm)}
                className="flex items-center justify-center gap-1.5 py-3 px-3 rounded-2xl bg-[#913638] hover:bg-[#74292B] active:scale-[0.98] text-white text-xs font-bold transition-all shadow-sm hover:shadow-md cursor-pointer text-center"
              >
                <Trash2 className="w-4 h-4 shrink-0" />
                <span className="truncate">Remove from Cart</span>
              </button>
            </div>

            <button
              type="button"
              onClick={() => setItemToConfirm(null)}
              className="w-full py-1 text-center text-xs font-medium text-[#786864] hover:text-[#2E211E] transition-colors cursor-pointer"
            >
              Cancel and keep in cart
            </button>
          </div>
        </div>
      )}

    </div>
  );
}
