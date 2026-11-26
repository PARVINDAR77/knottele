"use client";

import React, { useEffect, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { X, Trash2, Plus, Minus, ShoppingBag, ArrowRight, Sparkles, Truck, Heart } from "lucide-react";
import { useCart } from "@/context/CartContext";
import { useWishlist } from "@/context/WishlistContext";
import { useToast } from "@/context/ToastContext";
import { normalizeImageUrl } from "@/lib/api";
import { CartItem } from "@/types/cart";

export function CartDrawer() {
  const {
    items,
    isCartOpen,
    setIsCartOpen,
    removeItem,
    updateQuantity,
    subtotal,
    total,
    totalItemsCount,
    amountToFreeShipping,
    freeShippingThreshold,
  } = useCart();
  const { addToWishlist } = useWishlist();
  const { showToast } = useToast();

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

  useEffect(() => {
    if (isCartOpen) {
      document.body.style.overflow = "hidden";
    } else {
      document.body.style.overflow = "unset";
      setItemToConfirm(null);
    }
    return () => {
      document.body.style.overflow = "unset";
    };
  }, [isCartOpen]);

  if (!isCartOpen) return null;

  const freeShippingProgress = Math.min(
    100,
    Math.round(((freeShippingThreshold - amountToFreeShipping) / freeShippingThreshold) * 100)
  );

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/40 backdrop-blur-xs transition-opacity animate-in fade-in duration-300"
        onClick={() => setIsCartOpen(false)}
      />

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div className="w-screen max-w-md bg-white border-l border-[#E7D1CC] shadow-2xl flex flex-col animate-in slide-in-from-right duration-300 relative overflow-hidden">
          {/* Header */}
          <div className="p-5 border-b border-[#E7D1CC] flex items-center justify-between bg-[#FFF9F6]">
            <div className="flex items-center gap-2">
              <ShoppingBag className="w-5 h-5 text-[#913638]" />
              <h2 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
                Your Shopping Basket
              </h2>
              <span className="text-xs bg-[#FCE9E5] text-[#913638] font-semibold px-2 py-0.5 rounded-full">
                {totalItemsCount}
              </span>
            </div>
            <button
              onClick={() => setIsCartOpen(false)}
              className="p-1.5 rounded-full text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer"
              aria-label="Close cart"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Free Shipping Meter */}
          <div className="px-5 py-3 bg-[#FCE9E5]/60 border-b border-[#E7D1CC]">
            <div className="flex items-center justify-between text-xs font-medium text-[#2E211E] mb-1.5">
              <span className="flex items-center gap-1 text-[#913638]">
                <Truck className="w-3.5 h-3.5" />
                <span className="font-semibold text-emerald-800">
                  🎉 FREE Pan-India Shipping on All Orders!
                </span>
              </span>
              <span className="text-[11px] text-emerald-800 font-bold">
                100%
              </span>
            </div>
            <div className="w-full h-1.5 bg-[#E7D1CC] rounded-full overflow-hidden">
              <div
                className="h-full bg-[#913638] transition-all duration-500 rounded-full"
                style={{ width: `${freeShippingProgress}%` }}
              />
            </div>
          </div>

          {/* Items List */}
          <div className="flex-1 overflow-y-auto p-5 space-y-4">
            {items.length === 0 ? (
              <div className="h-full flex flex-col items-center justify-center text-center py-12">
                <div className="w-16 h-16 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] flex items-center justify-center text-[#913638] mb-4">
                  <ShoppingBag className="w-8 h-8 stroke-1" />
                </div>
                <h3 className="font-serif-luxury text-lg font-semibold text-[#2E211E] mb-1">
                  Your basket is empty
                </h3>
                <p className="text-xs text-[#786864] max-w-xs mb-6 leading-relaxed">
                  Your cart is waiting for a little handmade happiness. Explore our bestselling crochet items.
                </p>
                <button
                  onClick={() => setIsCartOpen(false)}
                  className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                >
                  Start Shopping →
                </button>
              </div>
            ) : (
              items.map((item) => (
                <div
                  key={item.id}
                  className="flex gap-4 p-3.5 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC]/80 transition-all hover:border-[#913638]/30"
                >
                  {/* Image */}
                  <Link
                    href={`/product/${item.product.slug}`}
                    onClick={() => setIsCartOpen(false)}
                    className="relative w-20 h-20 rounded-xl overflow-hidden bg-white shrink-0 border border-[#E7D1CC]"
                  >
                    <Image
                      src={normalizeImageUrl(item.product.main_image || item.product.images?.[0], "/images/products/bunny-keychain.jpg")}
                      alt={item.product.name}
                      fill
                      sizes="80px"
                      className="object-cover"
                    />
                  </Link>

                  {/* Details */}
                  <div className="flex-1 min-w-0 flex flex-col justify-between">
                    <div>
                      <div className="flex items-start justify-between gap-2">
                        <Link
                          href={`/product/${item.product.slug}`}
                          onClick={() => setIsCartOpen(false)}
                          className="font-serif-luxury text-sm font-semibold text-[#2E211E] hover:text-[#913638] transition-colors line-clamp-1"
                        >
                          {item.product.name}
                        </Link>
                        <button
                          onClick={() => setItemToConfirm(item)}
                          className="text-[#786864] hover:text-[#913638] p-1 transition-colors cursor-pointer"
                          aria-label="Remove item"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                        </button>
                      </div>

                      {/* Customization Details */}
                      {item.customization && (
                        <div className="mt-1 text-[11px] text-[#786864] space-y-0.5">
                          {item.customization.color && (
                            <div className="flex items-center gap-1.5">
                              <span
                                className="w-2.5 h-2.5 rounded-full border border-black/10 inline-block shrink-0"
                                style={{ backgroundColor: item.customization.color.hex }}
                              />
                              <span className="truncate">{item.customization.color.name}</span>
                            </div>
                          )}
                          {item.customization.size && (
                            <p>Size: {item.customization.size}</p>
                          )}
                          {item.customization.customText && (
                            <p className="text-[#913638] font-medium truncate">
                              &ldquo;{item.customization.customText}&rdquo;
                            </p>
                          )}
                        </div>
                      )}
                    </div>

                    {/* Price & Quantity Controls */}
                    <div className="flex items-center justify-between mt-2 pt-2 border-t border-[#E7D1CC]/60">
                      <div className="flex items-center border border-[#E7D1CC] rounded-full bg-white overflow-hidden shadow-xs">
                        <button
                          onClick={() => {
                            if (item.quantity > 1) {
                              updateQuantity(item.id, item.quantity - 1);
                            } else {
                              setItemToConfirm(item);
                            }
                          }}
                          className="px-2 py-1 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer"
                          aria-label="Decrease quantity"
                        >
                          <Minus className="w-3 h-3" />
                        </button>
                        <span className="px-2 text-xs font-bold text-[#2E211E] min-w-[20px] text-center">
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
                          className="px-2 py-1 text-[#786864] hover:text-[#2E211E] hover:bg-[#FCE9E5] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                          aria-label="Increase quantity"
                        >
                          <Plus className="w-3 h-3" />
                        </button>
                      </div>

                      <div className="text-right">
                        <span className="text-sm font-bold text-[#913638]">
                          ₹{(item.price * item.quantity).toLocaleString("en-IN")}
                        </span>
                        {typeof item.product?.stock === "number" && item.product.stock <= 5 && item.product.stock > 0 && (
                          <p className="text-[10px] text-amber-700 font-medium">Only {item.product.stock} left</p>
                        )}
                        {(item.product?.stock === 0 || item.product?.inStock === false) && (
                          <p className="text-[10px] text-rose-600 font-medium">Out of stock</p>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>

          {/* Footer & Checkout CTA */}
          {items.length > 0 && (
            <div className="p-5 border-t border-[#E7D1CC] bg-[#FFF9F6] space-y-3">
              <div className="space-y-1.5 text-xs text-[#786864]">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold text-[#2E211E]">
                    ₹{subtotal.toLocaleString("en-IN")}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span>
                    <span className="text-emerald-800 font-semibold">FREE</span>
                  </span>
                </div>
                <div className="flex justify-between text-sm font-bold text-[#2E211E] pt-1.5 border-t border-[#E7D1CC]">
                  <span>Estimated Total</span>
                  <span className="text-[#913638]">
                    ₹{subtotal.toLocaleString("en-IN")}
                  </span>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-2 pt-2">
                <Link
                  href="/cart"
                  onClick={() => setIsCartOpen(false)}
                  className="py-3 px-4 rounded-full bg-white text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs font-semibold active:scale-[0.98] shadow-xs transition-all text-center cursor-pointer"
                >
                  View Full Cart
                </Link>
                <Link
                  href="/checkout"
                  onClick={() => setIsCartOpen(false)}
                  className="py-3 px-4 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-1.5 text-center cursor-pointer"
                >
                  <span>Checkout</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </Link>
              </div>

              <div className="flex items-center justify-center gap-1 text-[11px] text-[#786864] text-center pt-1">
                <Sparkles className="w-3 h-3 text-[#C69A5A]" />
                <span>Every item is carefully handcrafted with love</span>
              </div>
            </div>
          )}

          {/* Move to Wishlist or Remove Confirmation Modal / Bottom Sheet */}
          {itemToConfirm && (
            <div
              className="absolute inset-0 z-50 bg-stone-900/40 backdrop-blur-xs flex items-end sm:items-center justify-center p-3 sm:p-4 animate-in fade-in duration-200"
              onClick={(e) => {
                if (e.target === e.currentTarget) setItemToConfirm(null);
              }}
            >
              <div className="bg-white rounded-3xl p-5 w-full border border-[#E7D1CC] shadow-2xl space-y-4 animate-in slide-in-from-bottom-6 duration-200">
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
      </div>
    </div>
  );
}
