"use client";

import React, { useState, useEffect, useMemo } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  CheckCircle2,
  ShieldCheck,
  QrCode,
  Truck,
  ArrowRight,
  ArrowLeft,
  ChevronDown,
  ChevronUp,
  Sparkles,
  Heart,
  Plus,
  MapPin,
  Tag,
} from "lucide-react";
import confetti from "canvas-confetti";
import { useCart } from "@/context/CartContext";
import { useAuth } from "@/context/AuthContext";
import { useWishlist } from "@/context/WishlistContext";
import { useToast } from "@/context/ToastContext";
import { Order, OrderStatus } from "@/types/order";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { FlowerIcon } from "@/components/ui/BotanicalDecorations";
import {
  submitOrder,
  saveCustomerAddress,
  normalizeImageUrl,
  fetchActiveOffers,
  createRazorpayOrder,
  verifyRazorpayPayment,
  getFullPath,
} from "@/lib/api";
import { KNOTELLE_LOGO_BASE64 } from "@/lib/brandLogo";

const loadRazorpayScript = (): Promise<boolean> => {
  return new Promise((resolve) => {
    if (typeof window === "undefined") return resolve(false);
    if ((window as any).Razorpay) return resolve(true);

    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
};

export default function CheckoutPage() {
  const router = useRouter();
  const { items, subtotal, shipping, discount, total, promoCode, applyPromoCode, removePromoCode, clearCart } = useCart();
  const { user, isAuthLoading, addOrder, refetchOrders, addAddress } = useAuth();
  const { toggleWishlist, isInWishlist } = useWishlist();
  const { showToast } = useToast();

  const [activeStep, setActiveStep] = useState(1);
  const [isSummaryCollapsedMobile, setIsSummaryCollapsedMobile] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Dynamic Offers / Coupon state
  const [couponInput, setCouponInput] = useState("");
  const [availableOffers, setAvailableOffers] = useState<any[]>([]);
  const [isApplyingCoupon, setIsApplyingCoupon] = useState(false);

  // Fetch active offers from backend database (filtered by subtotal on server)
  useEffect(() => {
    fetchActiveOffers(subtotal).then((offers) => {
      if (offers && Array.isArray(offers)) {
        setAvailableOffers(offers);
      }
    });
  }, [subtotal]);

  // Client-side verification: Strictly filter offers valid for current subtotal & unexpired
  const validOffers = useMemo(() => {
    if (!availableOffers || availableOffers.length === 0) return [];
    const now = Date.now();

    return availableOffers.filter((off) => {
      if (!off || !off.code) return false;

      // 1. Check expiration
      if (off.ends_at) {
        const expiry = new Date(off.ends_at).getTime();
        if (!isNaN(expiry) && expiry < now) return false;
      }

      // 2. Check starts at
      if (off.starts_at) {
        const start = new Date(off.starts_at).getTime();
        if (!isNaN(start) && start > now) return false;
      }

      // 3. Check minimum cart amount against subtotal
      const minAmount = off.min_cart_amount != null ? Number(off.min_cart_amount) : 0;
      if (!isNaN(minAmount) && minAmount > 0 && subtotal < minAmount) {
        return false;
      }

      // 4. Check maximum cart amount against subtotal
      const maxAmount = off.max_cart_amount != null ? Number(off.max_cart_amount) : 0;
      if (!isNaN(maxAmount) && maxAmount > 0 && subtotal > maxAmount) {
        return false;
      }

      return true;
    });
  }, [availableOffers, subtotal]);

  // Auto-remove promo code if subtotal drops below required minimum amount
  useEffect(() => {
    if (promoCode && availableOffers.length > 0) {
      const currentAppliedOffer = availableOffers.find(
        (o) => o.code?.toUpperCase() === promoCode.toUpperCase()
      );
      if (currentAppliedOffer) {
        const minAmt = currentAppliedOffer.min_cart_amount != null ? Number(currentAppliedOffer.min_cart_amount) : 0;
        if (!isNaN(minAmt) && minAmt > 0 && subtotal < minAmt) {
          removePromoCode();
          showToast(
            "Coupon Removed",
            `Coupon ${promoCode} requires a minimum cart amount of ₹${minAmt}.`,
            "info"
          );
        }
      }
    }
  }, [subtotal, promoCode, availableOffers, removePromoCode, showToast]);

  const handleApplyCouponSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!couponInput.trim()) return;
    setIsApplyingCoupon(true);
    await applyPromoCode(couponInput);
    setIsApplyingCoupon(false);
  };

  // Form State: 01 Information
  const [fullName, setFullName] = useState(user?.name || "");
  const [email, setEmail] = useState(user?.email && user.email.includes("@") ? user.email : "");
  const [phone, setPhone] = useState(user?.phone ? user.phone.replace(/\D/g, "").slice(-10) : "");

  // Form State: 02 Delivery
  const defaultSavedAddr = user?.addresses?.find((a) => a.isDefault) || user?.addresses?.[0];
  const [selectedAddressId, setSelectedAddressId] = useState<string>(defaultSavedAddr ? defaultSavedAddr.id : "new");
  const [addressLine1, setAddressLine1] = useState(defaultSavedAddr?.addressLine1 || "");
  const [addressLine2, setAddressLine2] = useState(defaultSavedAddr?.addressLine2 || "");
  const [city, setCity] = useState(defaultSavedAddr?.city || "");
  const [state, setState] = useState(defaultSavedAddr?.state || "");
  const [pincode, setPincode] = useState(defaultSavedAddr?.pincode || "");

  // Form State: 03 Payment
  const [paymentMethod, setPaymentMethod] = useState<"Razorpay" | "Cash on Delivery">("Razorpay");

  // Redirect guest users to login when accessing checkout ONLY after auth initialization finishes
  useEffect(() => {
    if (!isAuthLoading && !user) {
      router.replace(`/login?redirectTo=${encodeURIComponent("/checkout")}`);
    }
  }, [isAuthLoading, user, router]);

  // Sync state when user profile or saved addresses load dynamically from DB
  useEffect(() => {
    if (user) {
      if (user.name) setFullName(user.name);
      if (user.email && user.email.includes("@")) {
        setEmail(user.email);
      }
      if (user.phone) {
        const cleanDigits = user.phone.replace(/\D/g, "").slice(-10);
        setPhone(cleanDigits);
      }

      // If user has saved addresses in DB, auto-populate default address; otherwise reset to empty form for new users
      if (user.addresses && user.addresses.length > 0) {
        const defaultAddr = user.addresses.find((a) => a.isDefault) || user.addresses[0];
        if (defaultAddr) {
          setSelectedAddressId(defaultAddr.id);
          setAddressLine1(defaultAddr.addressLine1 || "");
          setAddressLine2(defaultAddr.addressLine2 || "");
          setCity(defaultAddr.city || "");
          setState(defaultAddr.state || "");
          setPincode(defaultAddr.pincode || "");
        }
      } else {
        setSelectedAddressId("new");
        setAddressLine1("");
        setAddressLine2("");
        setCity("");
        setState("");
        setPincode("");
      }
    }
  }, [user]);

  if (isAuthLoading) {
    return (
      <div className="bg-[#FFF9F6] min-h-screen py-24 flex flex-col items-center justify-center text-center">
        <div className="w-10 h-10 border-3 border-[#913638] border-t-transparent rounded-full animate-spin mb-4" />
        <p className="font-serif-luxury text-base font-bold text-[#2E211E]">Loading KNOTELLE Checkout...</p>
        <p className="text-xs text-[#786864] mt-1">Verifying secure artisan session</p>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="bg-[#FFF9F6] min-h-screen py-16 flex items-center justify-center text-center">
        <p className="text-xs text-[#786864]">Redirecting to Login for Checkout...</p>
      </div>
    );
  }

  if (items.length === 0 && !isSubmitting) {
    return (
      <div className="bg-[#FFF9F6] min-h-[75vh] flex items-center justify-center py-16 px-4">
        <div className="max-w-md w-full bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-12 text-center shadow-boutique space-y-6">
          <div className="w-16 h-16 rounded-full bg-[#FCE9E5] border border-[#E7D1CC] flex items-center justify-center mx-auto text-[#913638]">
            <FlowerIcon className="w-8 h-8 text-[#913638]" />
          </div>
          <div className="space-y-2">
            <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
              Your Cart is Empty
            </h2>
            <p className="text-xs sm:text-sm text-[#786864] leading-relaxed">
              Explore our boutique collection of handmade crochet creations and add your favorites to checkout.
            </p>
          </div>
          <Link
            href="/shop"
            className="inline-flex items-center gap-2 px-7 py-3 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-sm transition-all"
          >
            <span>Explore Boutique</span>
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>
      </div>
    );
  }

  const handlePlaceOrder = async () => {
    // Client-side validation
    const cleanName = fullName.trim();
    const cleanEmail = email.trim();
    const cleanPhone = phone.replace(/\D/g, "").slice(-10);
    const cleanAddr1 = addressLine1.trim();
    const cleanCity = city.trim();
    const cleanPin = pincode.trim();

    if (!cleanName) {
      showToast("Missing Full Name", "Please enter your full name for shipping.", "error");
      return;
    }
    if (!cleanEmail || !cleanEmail.includes("@") || !cleanEmail.includes(".")) {
      showToast("Invalid Email", "Please enter a valid email address (e.g., user@gmail.com).", "error");
      return;
    }
    if (cleanPhone.length !== 10) {
      showToast("Invalid Phone", "Please enter a valid 10-digit mobile number.", "error");
      return;
    }
    if (!cleanAddr1) {
      showToast("Missing Address", "Please enter street address line 1.", "error");
      return;
    }
    if (!cleanCity) {
      showToast("Missing City", "Please enter your city.", "error");
      return;
    }
    if (!cleanPin || cleanPin.length !== 6 || !/^\d{6}$/.test(cleanPin)) {
      showToast("Invalid Pincode", "Please enter a valid 6-digit postal pincode (digits only).", "error");
      return;
    }

    setIsSubmitting(true);

    const orderItemsPayload = items.map((item) => {
      let variantId = 1;
      const numMatch = String(item.product?.id || "").match(/\d+/);
      if (numMatch) {
        const parsed = parseInt(numMatch[0], 10);
        if (parsed >= 1 && parsed <= 50) {
          variantId = parsed;
        }
      }
      return {
        variant_id: variantId,
        product_id: item.product?.id || item.productId,
        product_code: item.product?.id || item.productId,
        name: item.product?.name || "Handmade Product",
        unit_price: item.price,
        quantity: item.quantity || 1,
      };
    });

    // RAZORPAY ONLINE PAYMENT FLOW
    if (paymentMethod === "Razorpay") {
      try {
        const isLoaded = await loadRazorpayScript();
        if (!isLoaded) {
          showToast("SDK Load Failed", "Unable to load Razorpay Checkout JS SDK. Check your internet connection.", "error");
          setIsSubmitting(false);
          return;
        }

        const razorpayOrder = await createRazorpayOrder(total);
        if (!razorpayOrder || !razorpayOrder.success || !razorpayOrder.order_id) {
          showToast("Payment Initiation Failed", razorpayOrder?.message || "Could not create Razorpay order.", "error");
          setIsSubmitting(false);
          return;
        }

        const razorpayKeyId = razorpayOrder.key_id || process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID || "rzp_test_1DP5A3y5551234";

        const options = {
          key: razorpayKeyId,
          amount: razorpayOrder.amount,
          currency: razorpayOrder.currency || "INR",
          name: "KNOTELLE Boutique",
          description: "Artisanal Handmade Order",
          image: KNOTELLE_LOGO_BASE64,
          order_id: razorpayOrder.order_id,
          prefill: {
            name: cleanName,
            email: cleanEmail,
            contact: cleanPhone,
          },
          theme: {
            color: "#913638",
          },
          handler: async function (response: any) {
            try {
              // 1. Verify Razorpay signature on backend FIRST
              const verifyRes = await verifyRazorpayPayment({
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_order_id: response.razorpay_order_id,
                razorpay_signature: response.razorpay_signature,
              });

              if (!verifyRes.success) {
                showToast("Payment Verification Failed", verifyRes.message || "Invalid Razorpay payment signature.", "error");
                setIsSubmitting(false);
                return;
              }

              // 2. Signature verified! Create/mark order as Paid
              const res = await submitOrder({
                items: orderItemsPayload.length > 0 ? orderItemsPayload : [{ variant_id: 1, quantity: 1, unit_price: subtotal }],
                shipping_address: {
                  name: cleanName,
                  email: cleanEmail,
                  phone: cleanPhone,
                  address_line_1: `${cleanAddr1}${addressLine2 ? ', ' + addressLine2.trim() : ''}`,
                  city: cleanCity,
                  pin_code: cleanPin,
                },
                payment_method: "razorpay",
                payment_status: "paid",
                offer_code: promoCode || undefined,
                discount_total: discount || 0,
                shipping_total: shipping || 0,
                grand_total: total,
              });

              if (!res || !res.success) {
                showToast("Order Submission Failed", res?.message || "Payment verified, but failed to record order.", "error");
                setIsSubmitting(false);
                return;
              }

              if (typeof window !== "undefined") {
                localStorage.setItem("knotelle_guest_email", cleanEmail);
              }

              const realOrderId = String(res.orderId || `ord-${Date.now()}`);
              const realOrderNumber = res.orderNumber || `KNT-${Math.floor(1000 + Math.random() * 9000)}-IN`;

              const newOrder: Order = {
                id: realOrderId,
                orderNumber: realOrderNumber,
                orderDate: new Date().toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }),
                estimatedDelivery: new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }),
                items: items.length > 0 ? [...items] : [],
                shippingAddress: {
                  fullName: cleanName,
                  email: cleanEmail,
                  phone: cleanPhone,
                  addressLine1: cleanAddr1,
                  addressLine2: addressLine2 || "",
                  city: cleanCity,
                  state: state || "Gujarat",
                  pincode: cleanPin,
                  country: "India",
                },
                paymentMethod: "Razorpay Online Payment",
                paymentStatus: "Paid",
                subtotal,
                shipping,
                discount,
                total,
                status: "Order Placed",
                timeline: [
                  { status: "Order Placed", date: "Just now", description: "Order placed and payment signature verified successfully.", completed: true, current: true },
                  { status: "Order Confirmed", date: "Upcoming", description: "Natural cotton yarns prepared for crafting.", completed: false },
                  { status: "Crafting Your Order", date: "Upcoming", description: "Handcrafting your bespoke crochet items.", completed: false },
                  { status: "Shipped", date: "Upcoming", description: "Handed to premium courier express.", completed: false },
                  { status: "Delivered", date: "Upcoming", description: "Handmade joy delivered with love.", completed: false },
                ],
              };

              try {
                confetti({
                  particleCount: 120,
                  spread: 80,
                  origin: { y: 0.6 },
                  colors: ["#8F3032", "#F4C7C1", "#C69A5A", "#FDE9E5"],
                });
              } catch (e) {}

              addOrder(newOrder);
              try {
                await refetchOrders();
              } catch {}
              clearCart();

              window.location.href = getFullPath(`/order-confirmation?orderNumber=${encodeURIComponent(realOrderNumber)}&orderId=${encodeURIComponent(realOrderId)}`);
            } catch (err: any) {
              console.error("Razorpay verification callback exception:", err);
              showToast("Order Submission Error", err?.message || "Payment verified, but failed to save order.", "error");
            } finally {
              setIsSubmitting(false);
            }
          },
          modal: {
            ondismiss: function () {
              setIsSubmitting(false);
              showToast("Payment Cancelled", "Razorpay checkout was cancelled.", "info");
            },
          },
        };

        const rzp = new (window as any).Razorpay(options);
        rzp.on("payment.failed", function (response: any) {
          setIsSubmitting(false);
          showToast("Payment Failed", response.error?.description || "Razorpay payment failed. Please try again.", "error");
        });
        rzp.open();

      } catch (err: any) {
        console.error("Razorpay payment error:", err);
        showToast("Payment Initiation Error", err?.message || "An unexpected error occurred launching Razorpay.", "error");
        setIsSubmitting(false);
      }
      return;
    }

    // CASH ON DELIVERY FLOW
    try {
      const res = await submitOrder({
        items: orderItemsPayload.length > 0 ? orderItemsPayload : [{ variant_id: 1, quantity: 1, unit_price: subtotal }],
        shipping_address: {
          name: cleanName,
          email: cleanEmail,
          phone: cleanPhone,
          address_line_1: `${cleanAddr1}${addressLine2 ? ', ' + addressLine2.trim() : ''}`,
          city: cleanCity,
          pin_code: cleanPin,
        },
        payment_method: "cod",
        offer_code: promoCode || undefined,
        discount_total: discount || 0,
        shipping_total: shipping || 0,
        grand_total: total,
      });

      if (!res || !res.success) {
        showToast("Order Placement Failed", res?.message || "Failed to place order. Please try again.", "error");
        setIsSubmitting(false);
        return;
      }

      if (typeof window !== "undefined") {
        localStorage.setItem("knotelle_guest_email", cleanEmail);
      }

      const realOrderId = String(res.orderId || `ord-${Date.now()}`);
      const realOrderNumber = res.orderNumber || `KNT-${Math.floor(1000 + Math.random() * 9000)}-IN`;

      const newOrder: Order = {
        id: realOrderId,
        orderNumber: realOrderNumber,
        orderDate: new Date().toLocaleDateString("en-GB", {
          day: "2-digit",
          month: "short",
          year: "numeric",
        }),
        estimatedDelivery: new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toLocaleDateString(
          "en-GB",
          { day: "2-digit", month: "short", year: "numeric" }
        ),
        items: items.length > 0 ? [...items] : [],
        shippingAddress: {
          fullName: cleanName,
          email: cleanEmail,
          phone: cleanPhone,
          addressLine1: cleanAddr1,
          addressLine2: addressLine2 || "",
          city: cleanCity,
          state: state || "Gujarat",
          pincode: cleanPin,
          country: "India",
        },
        paymentMethod,
        paymentStatus: "Pending",
        subtotal,
        shipping,
        discount,
        total,
        status: "Order Placed",
        timeline: [
          {
            status: "Order Placed",
            date: "Just now",
            description: "Order placed successfully. Assigned to artisan studio.",
            completed: true,
            current: true,
          },
          {
            status: "Order Confirmed",
            date: "Upcoming",
            description: "Natural cotton yarns prepared for crafting.",
            completed: false,
          },
          {
            status: "Crafting Your Order",
            date: "Upcoming",
            description: "Handcrafting your bespoke crochet items.",
            completed: false,
          },
          {
            status: "Shipped",
            date: "Upcoming",
            description: "Handed to premium courier express.",
            completed: false,
          },
          {
            status: "Delivered",
            date: "Upcoming",
            description: "Handmade joy delivered with love.",
            completed: false,
          },
        ],
      };

      try {
        confetti({
          particleCount: 100,
          spread: 70,
          origin: { y: 0.6 },
          colors: ["#8F3032", "#F4C7C1", "#C69A5A", "#FDE9E5"],
        });
      } catch (e) {}

      addOrder(newOrder);
      try {
        await refetchOrders();
      } catch {}
      clearCart();

      window.location.href = getFullPath(`/order-confirmation?orderNumber=${encodeURIComponent(realOrderNumber)}&orderId=${encodeURIComponent(realOrderId)}`);
    } catch (err: any) {
      console.error("Order submission exception:", err);
      showToast("Order Submission Error", err?.message || "An unexpected error occurred.", "error");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumb */}
        <Breadcrumbs
          items={[
            { label: "Cart", href: "/cart" },
            { label: "Distraction-Free Checkout" },
          ]}
          className="mb-6"
        />

        {/* Header */}
        <div className="flex items-center justify-between mb-8 pb-4 border-b border-[#E7D1CC]">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-[#FCE9E5] flex items-center justify-center text-[#913638]">
              <FlowerIcon className="w-5 h-5 text-[#913638]" />
            </div>
            <div>
              <h1 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
                Complete Your Order
              </h1>
              <p className="text-xs text-[#786864]">
                Safe 256-Bit Encrypted Boutique Checkout
              </p>
            </div>
          </div>
          <div className="hidden sm:flex items-center gap-2 text-xs text-[#786864]">
            <ShieldCheck className="w-4 h-4 text-emerald-800" />
            <span>100% Guaranteed Handmade</span>
          </div>
        </div>

        {/* Mobile Collapsible Order Summary Trigger */}
        <div className="lg:hidden mb-6 bg-white rounded-2xl border border-[#E7D1CC] p-4 shadow-xs">
          <button
            onClick={() => setIsSummaryCollapsedMobile(!isSummaryCollapsedMobile)}
            className="w-full flex items-center justify-between text-xs font-semibold text-[#2E211E] cursor-pointer"
          >
            <span className="flex items-center gap-1.5 text-[#913638]">
              <Sparkles className="w-4 h-4" />
              <span>
                {isSummaryCollapsedMobile ? "Show" : "Hide"} Order Summary (₹{total.toLocaleString("en-IN")})
              </span>
            </span>
            {isSummaryCollapsedMobile ? <ChevronDown className="w-4 h-4" /> : <ChevronUp className="w-4 h-4" />}
          </button>

          {!isSummaryCollapsedMobile && (
            <div className="mt-4 pt-4 border-t border-[#E7D1CC] space-y-3 text-xs animate-in fade-in">
              {items.map((item) => {
                const name = item.product?.name || (item as any)?.name || "Handmade Product";
                return (
                  <div key={item.id} className="flex justify-between items-center">
                    <span>{item.quantity}x {name}</span>
                    <span className="font-bold text-[#2E211E]">₹{((item.price || 0) * (item.quantity || 1)).toLocaleString("en-IN")}</span>
                  </div>
                );
              })}

              {/* Offer / Promo Code Apply Section (Mobile) */}
              <div className="pt-3 border-t border-[#E7D1CC] space-y-2">
                <label className="text-xs font-semibold text-[#2E211E] flex items-center gap-1.5">
                  <Tag className="w-3.5 h-3.5 text-[#913638]" />
                  <span>Apply Offer or Promo Code:</span>
                </label>
                {promoCode ? (
                  <div className="flex items-center justify-between p-2 rounded-xl bg-[#FCE9E5] border border-[#E7D1CC] text-xs">
                    <span className="font-bold text-[#913638] flex items-center gap-1">
                      <Sparkles className="w-3 h-3 text-[#913638]" />
                      {promoCode} Applied!
                    </span>
                    <button
                      type="button"
                      onClick={removePromoCode}
                      className="text-xs font-semibold text-[#786864] hover:text-[#913638] cursor-pointer"
                    >
                      Remove
                    </button>
                  </div>
                ) : (
                  <div className="space-y-2">
                    <form onSubmit={handleApplyCouponSubmit} className="flex gap-2">
                      <input
                        type="text"
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value)}
                        placeholder="Try KNOTELLE10"
                        className="flex-1 px-3 py-1.5 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-xs uppercase text-[#2E211E] focus:outline-none focus:border-[#913638]"
                      />
                      <button
                        type="submit"
                        disabled={isApplyingCoupon || !couponInput.trim()}
                        className="px-4 py-1.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all cursor-pointer disabled:opacity-50"
                      >
                        {isApplyingCoupon ? "..." : "Apply"}
                      </button>
                    </form>

                    {validOffers.length > 0 && (
                      <div className="space-y-1.5 pt-1">
                        <span className="text-[10px] font-semibold text-[#786864] block">Available Active Offers:</span>
                        <div className="flex flex-wrap gap-1.5">
                          {validOffers.map((off) => (
                            <button
                              key={off.id || off.code}
                              type="button"
                              disabled={isApplyingCoupon}
                              onClick={async () => {
                                setCouponInput(off.code);
                                await applyPromoCode(off.code);
                              }}
                              className="px-2 py-0.5 rounded-lg bg-[#FCE9E5] hover:bg-[#EFB8B0] text-[#913638] text-[10px] font-bold border border-[#E7D1CC] transition-all cursor-pointer disabled:opacity-50"
                            >
                              <span>🏷️ {off.code}</span>
                              {off.discount_value && (
                                <span className="text-[9px] text-[#786864] font-semibold">({off.discount_value}% OFF)</span>
                              )}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>

              {discount > 0 && (
                <div className="flex justify-between text-[#913638] font-semibold pt-1">
                  <span>Discount ({promoCode}):</span>
                  <span>-₹{discount.toLocaleString("en-IN")}</span>
                </div>
              )}

              <div className="flex justify-between font-bold text-sm text-[#913638] pt-2 border-t border-[#E7D1CC]">
                <span>Total:</span>
                <span>₹{total.toLocaleString("en-IN")}</span>
              </div>
            </div>
          )}
        </div>

        {/* Main 2-Column Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Left Steps Form (Spans 7 cols on lg) */}
          <div className="lg:col-span-7 space-y-6">
            
            {/* STEP 01: Information */}
            <div className={`bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique transition-all ${activeStep === 1 ? "ring-2 ring-[#913638]/20" : ""}`}>
              <div className="flex items-center justify-between pb-3 border-b border-[#E7D1CC]">
                <div className="flex items-center gap-2.5">
                  <span className={`w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center ${activeStep > 1 ? "bg-[#FCE9E5] text-[#913638]" : "bg-[#913638] text-white"}`}>
                    {activeStep > 1 ? "✓" : "01"}
                  </span>
                  <h3 className="font-serif-luxury text-base font-bold text-[#2E211E]">
                    Customer Information
                  </h3>
                </div>
                {activeStep > 1 && (
                  <button
                    onClick={() => setActiveStep(1)}
                    className="text-xs font-semibold text-[#913638] hover:underline cursor-pointer"
                  >
                    Edit
                  </button>
                )}
              </div>

              {activeStep === 1 ? (
                <div className="pt-4 space-y-4 animate-in fade-in">
                  <div className="space-y-1">
                    <label className="text-xs font-semibold text-[#2E211E] block">
                      Full Name *
                    </label>
                    <input
                      type="text"
                      value={fullName}
                      onChange={(e) => setFullName(e.target.value)}
                      placeholder="e.g. Avira Sharma"
                      className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                    />
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        Email Address *
                      </label>
                      <input
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="you@gmail.com"
                        className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        Phone Number *
                      </label>
                      <div className="relative flex items-center rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] focus-within:border-[#913638] focus-within:ring-1 focus-within:ring-[#913638] transition-all overflow-hidden">
                        <div className="flex items-center gap-1.5 px-3 py-2.5 bg-[#FCE9E5] border-r border-[#E7D1CC] text-xs font-bold text-[#913638] shrink-0 select-none">
                          <span className="text-sm leading-none">🇮🇳</span>
                          <span>+91</span>
                        </div>
                        <input
                          type="tel"
                          required
                          inputMode="numeric"
                          pattern="[0-9]{10}"
                          maxLength={10}
                          value={phone.replace(/^\+91\s*/, "").replace(/\D/g, "").slice(0, 10)}
                          onChange={(e) => setPhone(e.target.value.replace(/\D/g, "").slice(0, 10))}
                          placeholder="98765 43210"
                          className="w-full px-3.5 py-2.5 bg-transparent text-xs text-[#2E211E] placeholder-[#786864]/50 focus:outline-none"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="pt-2 flex justify-end">
                    <button
                      type="button"
                      onClick={() => {
                        const cleanName = fullName.trim();
                        const cleanEmail = email.trim();
                        const cleanPhone = phone.replace(/\D/g, "").slice(-10);

                        if (!cleanName) {
                          showToast("Missing Full Name", "Please enter your full name for shipping.", "error");
                          return;
                        }
                        if (!cleanEmail || !cleanEmail.includes("@") || !cleanEmail.includes(".")) {
                          showToast("Invalid Email Address", "Please enter a valid email address (e.g. user@gmail.com).", "error");
                          return;
                        }
                        if (cleanPhone.length !== 10) {
                          showToast("Invalid Phone Number", "Please enter a valid 10-digit mobile number.", "error");
                          return;
                        }
                        setActiveStep(2);
                      }}
                      className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>Continue to Delivery</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              ) : (
                <div className="pt-3 text-xs text-[#786864]">
                  <p className="font-semibold text-[#2E211E]">{fullName}</p>
                  <p>{email} &bull; +91 {phone.replace(/^\+91\s*/, "").replace(/\D/g, "").slice(-10)}</p>
                </div>
              )}
            </div>

            {/* STEP 02: Delivery Address */}
            <div className={`bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique transition-all ${activeStep === 2 ? "ring-2 ring-[#913638]/20" : ""}`}>
              <div className="flex items-center justify-between pb-3 border-b border-[#E7D1CC]">
                <div className="flex items-center gap-2.5">
                  <span className={`w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center ${activeStep > 2 ? "bg-[#FCE9E5] text-[#913638]" : activeStep === 2 ? "bg-[#913638] text-white" : "bg-[#FFF9F6] text-[#786864] border border-[#E7D1CC]"}`}>
                    {activeStep > 2 ? "✓" : "02"}
                  </span>
                  <h3 className="font-serif-luxury text-base font-bold text-[#2E211E]">
                    Shipping & Delivery Address
                  </h3>
                </div>
                {activeStep > 2 && (
                  <button
                    onClick={() => setActiveStep(2)}
                    className="text-xs font-semibold text-[#913638] hover:underline cursor-pointer"
                  >
                    Edit
                  </button>
                )}
              </div>

              {activeStep === 2 ? (
                <div className="pt-4 space-y-4 animate-in fade-in">
                  
                  {/* Saved Addresses List (If user has saved addresses) */}
                  {user?.addresses && user.addresses.length > 0 && (
                    <div className="space-y-3 pb-4 border-b border-[#E7D1CC]">
                      <label className="text-xs font-bold text-[#2E211E] block">
                        Select Delivery Address
                      </label>
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        {user.addresses.map((addr) => {
                          const isSelected = selectedAddressId === addr.id;
                          return (
                            <div
                              key={addr.id}
                              onClick={() => {
                                setSelectedAddressId(addr.id);
                                setAddressLine1(addr.addressLine1 || "");
                                setAddressLine2(addr.addressLine2 || "");
                                setCity(addr.city || "");
                                setState(addr.state || "");
                                setPincode(addr.pincode || "");
                              }}
                              className={`p-3.5 rounded-2xl border cursor-pointer transition-all ${
                                isSelected
                                  ? "border-[#913638] bg-[#FCE9E5]/50 ring-1 ring-[#913638]"
                                  : "border-[#E7D1CC] hover:bg-[#FFF9F6]"
                              }`}
                            >
                              <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                  <input
                                    type="radio"
                                    name="selected_address"
                                    checked={isSelected}
                                    onChange={() => {}}
                                    className="text-[#913638] focus:ring-[#913638]"
                                  />
                                  <span className="text-xs font-bold text-[#2E211E]">
                                    {addr.fullName || user.name}
                                  </span>
                                </div>
                                {addr.isDefault && (
                                  <span className="text-[10px] font-bold bg-[#913638] text-white px-2 py-0.5 rounded-full">
                                    Default
                                  </span>
                                )}
                              </div>
                              <p className="text-[11px] text-[#786864] mt-1 pl-5 leading-relaxed">
                                {addr.addressLine1}
                                {addr.addressLine2 && `, ${addr.addressLine2}`}
                                <br />
                                {addr.city}, {addr.state} - {addr.pincode}
                              </p>
                            </div>
                          );
                        })}

                        {/* Option to Add New Address */}
                        <div
                          onClick={() => {
                            setSelectedAddressId("new");
                            setAddressLine1("");
                            setAddressLine2("");
                            setCity("");
                            setState("");
                            setPincode("");
                          }}
                          className={`p-3.5 rounded-2xl border border-dashed flex items-center justify-center gap-2 cursor-pointer transition-all ${
                            selectedAddressId === "new"
                              ? "border-[#913638] bg-[#FCE9E5]/40 text-[#913638] font-bold"
                              : "border-[#E7D1CC] text-[#786864] hover:bg-[#FFF9F6] hover:text-[#2E211E]"
                          }`}
                        >
                          <Plus className="w-4 h-4" />
                          <span className="text-xs font-semibold">+ Add New Delivery Address</span>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Form fields for Address */}
                  <div className="space-y-3">
                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        House / Flat / Building / Street *
                      </label>
                      <input
                        type="text"
                        value={addressLine1}
                        onChange={(e) => {
                          setAddressLine1(e.target.value);
                          if (selectedAddressId !== "new") setSelectedAddressId("new");
                        }}
                        placeholder="House No., Flat, Building, Street Name"
                        className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                      />
                    </div>

                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        Area / Landmark (Optional)
                      </label>
                      <input
                        type="text"
                        value={addressLine2}
                        onChange={(e) => {
                          setAddressLine2(e.target.value);
                          if (selectedAddressId !== "new") setSelectedAddressId("new");
                        }}
                        placeholder="Area, Sector, or Nearby Landmark"
                        className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                      />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div className="space-y-1">
                        <label className="text-xs font-semibold text-[#2E211E] block">
                          City *
                        </label>
                        <input
                          type="text"
                          value={city}
                          onChange={(e) => {
                            setCity(e.target.value);
                            if (selectedAddressId !== "new") setSelectedAddressId("new");
                          }}
                          placeholder="City"
                          className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-xs font-semibold text-[#2E211E] block">
                          State *
                        </label>
                        <input
                          type="text"
                          value={state}
                          onChange={(e) => {
                            setState(e.target.value);
                            if (selectedAddressId !== "new") setSelectedAddressId("new");
                          }}
                          placeholder="State"
                          className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-xs font-semibold text-[#2E211E] block">
                          Pincode *
                        </label>
                        <input
                          type="text"
                          required
                          inputMode="numeric"
                          pattern="[0-9]{6}"
                          maxLength={6}
                          value={pincode}
                          onChange={(e) => {
                            const val = e.target.value.replace(/\D/g, "").slice(0, 6);
                            setPincode(val);
                            if (selectedAddressId !== "new") setSelectedAddressId("new");
                          }}
                          placeholder="6-digit Pincode"
                          className="w-full px-3.5 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
                        />
                      </div>
                    </div>
                  </div>

                  <div className="pt-2 flex justify-between">
                    <button
                      type="button"
                      onClick={() => setActiveStep(1)}
                      className="px-5 py-2 rounded-full bg-white text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs font-semibold active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                    >
                      Back
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        const cleanPin = pincode.replace(/\D/g, "").slice(0, 6);
                        if (!addressLine1.trim() || !city.trim() || cleanPin.length !== 6 || !/^\d{6}$/.test(cleanPin)) {
                          showToast("Invalid Address or Pincode", "Please enter valid street address, city, and a 6-digit numeric pincode.", "error");
                          return;
                        }
                        // Save address to user's database if it's new
                        if (selectedAddressId === "new") {
                          addAddress({
                            fullName: fullName || user.name,
                            email: email || user.email,
                            phone: phone || user.phone,
                            addressLine1: addressLine1.trim(),
                            addressLine2: addressLine2.trim(),
                            city: city.trim(),
                            state: state.trim() || "Gujarat",
                            pincode: cleanPin,
                            country: "India",
                          });
                        }
                        setActiveStep(3);
                      }}
                      className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>Continue to Payment</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              ) : activeStep > 2 ? (
                <div className="pt-3 text-xs text-[#786864]">
                  <p>{addressLine1}{addressLine2 ? `, ${addressLine2}` : ""}</p>
                  <p>{city}{state ? `, ${state}` : ""} - {pincode}, India</p>
                </div>
              ) : null}
            </div>

            {/* STEP 03: Payment */}
            <div className={`bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique transition-all ${activeStep === 3 ? "ring-2 ring-[#913638]/20" : ""}`}>
              <div className="flex items-center justify-between pb-3 border-b border-[#E7D1CC]">
                <div className="flex items-center gap-2.5">
                  <span className={`w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center ${activeStep > 3 ? "bg-[#FCE9E5] text-[#913638]" : activeStep === 3 ? "bg-[#913638] text-white" : "bg-[#FFF9F6] text-[#786864] border border-[#E7D1CC]"}`}>
                    {activeStep > 3 ? "✓" : "03"}
                  </span>
                  <h3 className="font-serif-luxury text-base font-bold text-[#2E211E]">
                    Payment Method
                  </h3>
                </div>
                {activeStep > 3 && (
                  <button
                    onClick={() => setActiveStep(3)}
                    className="text-xs font-semibold text-[#913638] hover:underline cursor-pointer"
                  >
                    Edit
                  </button>
                )}
              </div>

              {activeStep === 3 ? (
                <div className="pt-4 space-y-4 animate-in fade-in">
                  
                  {/* Payment Options */}
                  <div className="space-y-3">
                    {/* Razorpay Online Payment */}
                    <label className={`flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition-all ${paymentMethod === "Razorpay" ? "border-[#913638] bg-[#FCE9E5]/50 ring-1 ring-[#913638]" : "border-[#E7D1CC] hover:bg-[#FFF9F6]"}`}>
                      <input
                        type="radio"
                        name="payment"
                        checked={paymentMethod === "Razorpay"}
                        onChange={() => setPaymentMethod("Razorpay")}
                        className="mt-1 text-[#913638] focus:ring-[#913638]"
                      />
                      <div className="flex-1">
                        <div className="flex items-center justify-between">
                          <span className="text-xs font-bold text-[#2E211E] flex items-center gap-2">
                            <QrCode className="w-4 h-4 text-[#913638]" />
                            <span>Razorpay Online Payment</span>
                          </span>
                          <span className="text-[10px] text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full font-bold">
                            Fast & Secure
                          </span>
                        </div>
                        <p className="text-[11px] font-semibold text-[#913638] mt-1">
                          UPI &bull; GPay &bull; PhonePe &bull; Paytm &bull; Cards &bull; NetBanking &bull; QR
                        </p>
                        <p className="text-[10px] text-[#786864] mt-0.5">
                          Pay safely using official 256-bit encrypted Razorpay Checkout.
                        </p>
                      </div>
                    </label>

                    {/* Cash on Delivery */}
                    <label className={`flex items-start gap-3 p-4 rounded-2xl border cursor-pointer transition-all ${paymentMethod === "Cash on Delivery" ? "border-[#913638] bg-[#FCE9E5]/50 ring-1 ring-[#913638]" : "border-[#E7D1CC] hover:bg-[#FFF9F6]"}`}>
                      <input
                        type="radio"
                        name="payment"
                        checked={paymentMethod === "Cash on Delivery"}
                        onChange={() => setPaymentMethod("Cash on Delivery")}
                        className="mt-1 text-[#913638] focus:ring-[#913638]"
                      />
                      <div className="flex-1">
                        <span className="text-xs font-bold text-[#2E211E] flex items-center gap-2">
                          <Truck className="w-4 h-4 text-[#913638]" />
                          <span>Cash on Delivery (COD)</span>
                        </span>
                        <p className="text-[11px] text-[#786864] mt-0.5">
                          Pay cash upon delivery to the courier agent.
                        </p>
                      </div>
                    </label>
                  </div>

                  <div className="pt-2 flex justify-between">
                    <button
                      type="button"
                      onClick={() => setActiveStep(2)}
                      className="px-5 py-2 rounded-full bg-white text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs font-semibold active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                    >
                      Back
                    </button>
                    <button
                      type="button"
                      onClick={() => setActiveStep(4)}
                      className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <span>Review Order</span>
                      <ArrowRight className="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>
              ) : activeStep > 3 ? (
                <div className="pt-3 text-xs text-[#786864]">
                  <p className="font-semibold text-[#2E211E]">{paymentMethod}</p>
                </div>
              ) : null}
            </div>

            {/* STEP 04: Review & Place Order */}
            {activeStep === 4 && (
              <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-6 animate-in fade-in">
                <div className="flex items-center gap-2.5 pb-3 border-b border-[#E7D1CC]">
                  <span className="w-7 h-7 rounded-full text-xs font-bold bg-[#913638] text-white flex items-center justify-center">
                    04
                  </span>
                  <h3 className="font-serif-luxury text-base font-bold text-[#2E211E]">
                    Final Order Review
                  </h3>
                </div>

                <div className="p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] space-y-3 text-xs">
                  <div className="flex justify-between">
                    <span className="text-[#786864]">Recipient:</span>
                    <strong className="text-[#2E211E]">{fullName} ({phone})</strong>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-[#786864]">Ship to:</span>
                    <span className="text-[#2E211E] text-right">{addressLine1}, {city}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-[#786864]">Payment Mode:</span>
                    <strong className="text-[#913638]">{paymentMethod}</strong>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={handlePlaceOrder}
                  disabled={isSubmitting}
                  className="w-full py-4 px-8 rounded-full bg-[#913638] text-white text-sm font-bold tracking-wide hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <Sparkles className="w-4 h-4 text-[#C69A5A]" />
                  <span>{isSubmitting ? "Placing Order..." : "Place Order & Start Crafting →"}</span>
                </button>
              </div>
            )}

          </div>

          {/* Right Order Summary Box (Spans 5 cols on lg) */}
          <div className="lg:col-span-5 hidden lg:block sticky top-28">
            <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6">
              <h3 className="font-serif-luxury text-lg font-bold text-[#2E211E] pb-3 border-b border-[#E7D1CC]">
                Order Items ({items.length})
              </h3>

              <div className="divide-y divide-[#E7D1CC]/60 max-h-72 overflow-y-auto pr-1">
                {items.map((item) => {
                  const productId = item.product?.id || item.productId || item.id;
                  const isSaved = productId ? isInWishlist(productId) : false;
                  const productName = item.product?.name || (item as any)?.name || "Handmade Product";
                  const productImage = normalizeImageUrl(
                    item.product?.main_image || item.product?.images?.[0] || (item as any)?.image,
                    "/images/products/bunny-keychain.jpg"
                  );
                  return (
                    <div key={item.id} className="py-3 first:pt-0 last:pb-0 flex items-center gap-3">
                      <div className="relative w-14 h-14 rounded-xl overflow-hidden bg-[#FFF9F6] shrink-0 border border-[#E7D1CC]">
                        <Image
                          src={productImage}
                          alt={productName}
                          fill
                          sizes="56px"
                          className="object-cover"
                        />
                        <span className="absolute top-0 right-0 bg-[#913638] text-white text-[10px] font-bold w-4 h-4 rounded-bl flex items-center justify-center">
                          {item.quantity}
                        </span>
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-xs font-bold text-[#2E211E] truncate">
                          {productName}
                        </p>
                        {item.customization?.color && (
                          <p className="text-[10px] text-[#786864]">
                            {item.customization.color.name}
                          </p>
                        )}
                        {item.product && (
                          <button
                            type="button"
                            onClick={() => toggleWishlist(item.product)}
                            className="mt-0.5 inline-flex items-center gap-1 text-[10px] font-semibold text-[#913638] hover:underline cursor-pointer"
                          >
                            <Heart className={`w-3 h-3 ${isSaved ? "fill-[#913638] text-[#913638]" : ""}`} />
                            <span>{isSaved ? "Remove Wishlist" : "Add Wishlist"}</span>
                          </button>
                        )}
                      </div>
                      <span className="text-xs font-bold text-[#913638]">
                        ₹{((item.price || 0) * (item.quantity || 1)).toLocaleString("en-IN")}
                      </span>
                    </div>
                  );
                })}
              </div>

              {/* Offer / Promo Code Apply Section (Desktop) */}
              <div className="pt-4 border-t border-[#E7D1CC] space-y-3">
                <label className="text-xs font-bold text-[#2E211E] flex items-center gap-1.5">
                  <Tag className="w-4 h-4 text-[#913638]" />
                  <span>Promo or Gift Voucher:</span>
                </label>
                {promoCode ? (
                  <div className="flex items-center justify-between p-3 rounded-2xl bg-[#FCE9E5] border border-[#E7D1CC] text-xs">
                    <span className="font-bold text-[#913638] flex items-center gap-1.5">
                      <Sparkles className="w-4 h-4 text-[#913638]" />
                      {promoCode} Applied!
                    </span>
                    <button
                      type="button"
                      onClick={removePromoCode}
                      className="text-xs font-semibold text-[#786864] hover:text-[#913638] cursor-pointer hover:underline"
                    >
                      Remove
                    </button>
                  </div>
                ) : (
                  <div className="space-y-2.5">
                    <form onSubmit={handleApplyCouponSubmit} className="flex gap-2">
                      <input
                        type="text"
                        value={couponInput}
                        onChange={(e) => setCouponInput(e.target.value)}
                        placeholder="Try KNOTELLE10"
                        className="flex-1 px-3.5 py-2.5 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-xs uppercase font-semibold text-[#2E211E] placeholder-[#786864]/50 focus:outline-none focus:border-[#913638] transition-all"
                      />
                      <button
                        type="submit"
                        disabled={isApplyingCoupon || !couponInput.trim()}
                        className="px-5 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {isApplyingCoupon ? "Applying..." : "Apply"}
                      </button>
                    </form>

                    {/* Dynamic Active Offers from Database */}
                    {validOffers.length > 0 && (
                      <div className="space-y-1.5 pt-1">
                        <span className="text-[11px] font-semibold text-[#786864] block">Available Active Offers:</span>
                        <div className="flex flex-wrap gap-1.5">
                          {validOffers.map((off) => (
                            <button
                              key={off.id || off.code}
                              type="button"
                              disabled={isApplyingCoupon}
                              onClick={async () => {
                                setCouponInput(off.code);
                                await applyPromoCode(off.code);
                              }}
                              className="px-2.5 py-1 rounded-xl bg-[#FCE9E5]/70 hover:bg-[#FCE9E5] text-[#913638] text-[11px] font-bold border border-[#E7D1CC] hover:border-[#913638] transition-all cursor-pointer flex items-center gap-1 shadow-2xs active:scale-[0.98] disabled:opacity-50"
                            >
                              <span>🏷️ {off.code}</span>
                              {off.discount_value && (
                                <span className="text-[10px] text-[#786864] font-semibold">({off.discount_value}% OFF)</span>
                              )}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>

              {/* Price Details */}
              <div className="space-y-2.5 pt-4 border-t border-[#E7D1CC] text-xs text-[#786864]">
                <div className="flex justify-between">
                  <span>Subtotal</span>
                  <span className="font-semibold text-[#2E211E]">
                    ₹{subtotal.toLocaleString("en-IN")}
                  </span>
                </div>
                {discount > 0 && (
                  <div className="flex justify-between text-[#913638]">
                    <span>Discount {promoCode ? `(${promoCode})` : ""}</span>
                    <span className="font-bold">-₹{discount.toLocaleString("en-IN")}</span>
                  </div>
                )}
                <div className="flex justify-between">
                  <span>Shipping</span>
                  <span>{shipping === 0 ? <strong className="text-emerald-800">FREE</strong> : `₹${shipping}`}</span>
                </div>
                <div className="flex justify-between text-base font-bold text-[#2E211E] pt-3 border-t border-[#E7D1CC]">
                  <span>Total Due</span>
                  <span className="text-[#913638] text-lg">
                    ₹{total.toLocaleString("en-IN")}
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>
  );
}
