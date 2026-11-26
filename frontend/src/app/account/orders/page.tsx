"use client";

import React, { useState } from "react";
import Link from "next/link";
import Image from "next/image";
import {
  Package,
  ArrowRight,
  Clock,
  Sparkles,
  Check,
  Search,
  Loader2,
  AlertCircle,
  Truck,
  CheckCircle2,
  HelpCircle,
  ExternalLink,
  Download,
} from "lucide-react";
import { useAuth } from "@/context/AuthContext";
import { FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { normalizeImageUrl, fetchOrderById, getFullPath } from "@/lib/api";
import { Order } from "@/types/order";
import { useToast } from "@/context/ToastContext";

// The 6 official KNOTELLE order statuses
const KNOTELLE_STATUS_STEPS = [
  { step: 1, label: "Order Placed", desc: "Received" },
  { step: 2, label: "In Progress", desc: "Payment verified" },
  { step: 3, label: "Order Confirmed", desc: "Approved" },
  { step: 4, label: "Crafting Your Order", desc: "Hand-crocheted" },
  { step: 5, label: "Shipped", desc: "In transit" },
  { step: 6, label: "Delivered", desc: "Arrived" },
] as const;

/**
 * Map order status string to 0..5 step index
 */
function getStatusStepIndex(status: string): number {
  const s = (status || "").toLowerCase().trim();
  if (s.includes("delivered")) return 5;
  if (s.includes("shipped") || s.includes("out for delivery") || s.includes("transit") || s.includes("dispatched")) return 4;
  if (s.includes("crafting") || s.includes("making") || s.includes("packed") || s.includes("processing")) return 3;
  if (s.includes("confirmed") || s.includes("approved") || s.includes("accepted")) return 2;
  if (s.includes("progress") || s.includes("preparing")) return 1;
  return 0; // "Order Placed"
}

function getStatusBadgeStyle(status: string) {
  const s = (status || "").toLowerCase();
  if (s.includes("delivered")) return "bg-emerald-50 text-emerald-800 border-emerald-200";
  if (s.includes("cancelled")) return "bg-rose-50 text-rose-800 border-rose-200";
  if (s.includes("shipped")) return "bg-blue-50 text-blue-800 border-blue-200";
  if (s.includes("crafting")) return "bg-amber-50 text-amber-800 border-amber-200";
  return "bg-[#FCE9E5] text-[#913638] border-[#E7D1CC]";
}

interface TimelineProps {
  currentStatus: string;
}

/**
 * 6-Status Horizontal Timeline for Desktop
 */
function HorizontalTimeline({ currentStatus }: TimelineProps) {
  const currentIndex = getStatusStepIndex(currentStatus);
  const isCancelled = currentStatus.toLowerCase().includes("cancelled");

  if (isCancelled) {
    return (
      <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center gap-2">
        <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
        <span>This order has been cancelled. Please reach out to customer care for refund details.</span>
      </div>
    );
  }

  return (
    <div className="w-full py-4">
      <div className="relative flex items-center justify-between">
        {/* Connecting progress background line */}
        <div className="absolute left-6 right-6 top-4 h-0.5 bg-[#E7D1CC] -z-0" />
        
        {/* Connecting progress active line */}
        <div
          className="absolute left-6 top-4 h-0.5 bg-[#913638] -z-0 transition-all duration-500"
          style={{
            width: `${Math.min(100, Math.max(0, (currentIndex / (KNOTELLE_STATUS_STEPS.length - 1)) * 100))}%`,
            maxWidth: "calc(100% - 3rem)",
          }}
        />

        {KNOTELLE_STATUS_STEPS.map((step, index) => {
          const isDone = index < currentIndex;
          const isCurrent = index === currentIndex;
          const isFuture = index > currentIndex;

          return (
            <div
              key={step.step}
              className="relative z-10 flex flex-col items-center text-center flex-1"
            >
              {/* Node Circle */}
              <div
                className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
                  isCurrent
                    ? "bg-[#913638] text-white ring-4 ring-[#913638]/20 shadow-sm scale-110"
                    : isDone
                    ? "bg-[#913638] text-white"
                    : "bg-white text-[#786864] border-2 border-[#E7D1CC]"
                }`}
              >
                {isDone ? <Check className="w-4 h-4" /> : step.step}
              </div>

              {/* Status Label */}
              <div className="mt-2.5 px-1">
                <p
                  className={`text-[11px] leading-tight transition-colors ${
                    isCurrent
                      ? "font-bold text-[#913638]"
                      : isDone
                      ? "font-semibold text-[#2E211E]"
                      : "font-normal text-[#786864]/70"
                  }`}
                >
                  {step.label}
                </p>
                {isCurrent && (
                  <span className="inline-block mt-0.5 text-[9px] font-bold text-[#913638] uppercase tracking-wider bg-[#FCE9E5] px-2 py-0.2 rounded-full border border-[#EFB8B0]">
                    Current Status
                  </span>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

/**
 * 6-Status Vertical Timeline for Mobile
 */
function VerticalTimeline({ currentStatus }: TimelineProps) {
  const currentIndex = getStatusStepIndex(currentStatus);
  const isCancelled = currentStatus.toLowerCase().includes("cancelled");

  if (isCancelled) {
    return (
      <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center gap-2">
        <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
        <span>This order was cancelled.</span>
      </div>
    );
  }

  return (
    <div className="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#E7D1CC]">
      {KNOTELLE_STATUS_STEPS.map((step, index) => {
        const isDone = index < currentIndex;
        const isCurrent = index === currentIndex;

        return (
          <div key={step.step} className="relative group">
            {/* Step Node */}
            <div
              className={`absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold transition-all ${
                isCurrent
                  ? "bg-[#913638] text-white ring-4 ring-[#913638]/20"
                  : isDone
                  ? "bg-[#913638] text-white"
                  : "bg-white text-[#786864] border border-[#E7D1CC]"
              }`}
            >
              {isDone ? <Check className="w-3 h-3" /> : step.step}
            </div>

            <div className="flex items-center justify-between">
              <div>
                <p
                  className={`text-xs ${
                    isCurrent
                      ? "font-bold text-[#913638]"
                      : isDone
                      ? "font-semibold text-[#2E211E]"
                      : "text-[#786864]/70"
                  }`}
                >
                  {step.label}
                </p>
                <p className="text-[10px] text-[#786864]">{step.desc}</p>
              </div>

              {isCurrent && (
                <span className="text-[9px] font-bold text-[#913638] bg-[#FCE9E5] px-2 py-0.5 rounded-full border border-[#EFB8B0]">
                  Active Step
                </span>
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
}

export default function TrackOrderPage() {
  const { user, orders, isLoggedIn } = useAuth();
  const { showToast } = useToast();

  // Search & Guest Tracking State
  const [searchOrderNumber, setSearchOrderNumber] = useState("");
  const [searchEmail, setSearchEmail] = useState("");
  const [searching, setSearching] = useState(false);
  const [searchedOrder, setSearchedOrder] = useState<Order | null>(null);
  const [searchError, setSearchError] = useState<string | null>(null);

  // Handle direct lookup via API
  const handleSearchOrder = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchOrderNumber.trim()) {
      setSearchError("Please enter your KNOTELLE Order ID or Number.");
      return;
    }

    setSearching(true);
    setSearchError(null);
    setSearchedOrder(null);

    try {
      const cleanId = searchOrderNumber.trim().replace(/^#/, "");
      // First check local authenticated orders
      const matchedLocal = orders.find(
        (o) =>
          o.orderNumber.toLowerCase() === cleanId.toLowerCase() ||
          o.id === cleanId ||
          o.orderNumber.toLowerCase() === searchOrderNumber.trim().toLowerCase()
      );

      if (matchedLocal) {
        setSearchedOrder(matchedLocal);
        setSearching(false);
        return;
      }

      // Fetch from API
      const result = await fetchOrderById(cleanId, searchEmail.trim() || undefined);
      if (result) {
        setSearchedOrder(result);
      } else {
        setSearchError(
          `No order found with number "${searchOrderNumber}". Please double-check your order ID or email receipt.`
        );
      }
    } catch {
      setSearchError("Could not retrieve tracking details. Please try again.");
    } finally {
      setSearching(false);
    }
  };

  return (
    <div className="space-y-8 animate-in fade-in">
      
      {/* Page Header */}
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-[#E7D1CC]">
          <div>
            <div className="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-[#FCE9E5] text-[#913638] text-[11px] font-semibold uppercase tracking-wider mb-2">
              <Truck className="w-3.5 h-3.5" />
              <span>Live Order Tracking</span>
            </div>
            <h1 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
              Track Your Order
            </h1>
            <p className="text-xs sm:text-sm text-[#786864] mt-1">
              View real-time crafting status, courier transit, and delivery updates for your handmade pieces.
            </p>
          </div>

          {isLoggedIn && (
            <div className="shrink-0">
              <span className="text-xs font-semibold bg-[#FCE9E5] text-[#913638] px-3.5 py-1.5 rounded-full border border-[#E7D1CC]">
                {orders.length} Registered Order{orders.length === 1 ? "" : "s"}
              </span>
            </div>
          )}
        </div>

        {/* Quick Search / Guest Lookup Box */}
        <div className="pt-6">
          <form onSubmit={handleSearchOrder} className="space-y-3">
            <p className="text-xs font-semibold text-[#2E211E]">
              Track any order by entering your order number:
            </p>
            <div className="flex flex-col sm:flex-row gap-2.5">
              <div className="relative flex-1">
                <Search className="w-4 h-4 text-[#786864] absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input
                  type="text"
                  value={searchOrderNumber}
                  onChange={(e) => setSearchOrderNumber(e.target.value)}
                  placeholder="Order Number (e.g. KNT-1024 or 1024)"
                  className="w-full pl-11 pr-4 py-2.5 rounded-full bg-[#FFFDFB] border border-[#E7D1CC] text-xs text-[#2E211E] placeholder:text-[#786864]/60 focus:outline-none focus:ring-2 focus:ring-[#913638]/20 focus:border-[#913638] transition-all"
                />
              </div>

              {!isLoggedIn && (
                <input
                  type="email"
                  value={searchEmail}
                  onChange={(e) => setSearchEmail(e.target.value)}
                  placeholder="Billing Email (optional)"
                  className="sm:w-60 px-4 py-2.5 rounded-full bg-[#FFFDFB] border border-[#E7D1CC] text-xs text-[#2E211E] placeholder:text-[#786864]/60 focus:outline-none focus:ring-2 focus:ring-[#913638]/20 focus:border-[#913638] transition-all"
                />
              )}

              <button
                type="submit"
                disabled={searching}
                className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-flex items-center justify-center gap-1.5 shadow-xs disabled:opacity-60 cursor-pointer"
              >
                {searching ? (
                  <>
                    <Loader2 className="w-3.5 h-3.5 animate-spin" />
                    <span>Searching...</span>
                  </>
                ) : (
                  <>
                    <span>Track Order</span>
                    <ArrowRight className="w-3.5 h-3.5" />
                  </>
                )}
              </button>
            </div>

            {searchError && (
              <div className="p-3 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                <span>{searchError}</span>
              </div>
            )}
          </form>
        </div>
      </div>

      {/* Searched Single Order Result */}
      {searchedOrder && (
        <div className="bg-white rounded-3xl border-2 border-[#EFB8B0] p-6 sm:p-8 shadow-boutique space-y-6 animate-in fade-in">
          <div className="flex items-center justify-between pb-4 border-b border-[#E7D1CC]">
            <div className="flex items-center gap-2">
              <Sparkles className="w-4 h-4 text-[#913638]" />
              <h2 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
                Tracking Result: <span className="font-mono text-[#913638]">{searchedOrder.orderNumber}</span>
              </h2>
            </div>
            <button
              onClick={() => setSearchedOrder(null)}
              className="text-xs text-[#786864] hover:text-[#913638] underline"
            >
              Close Result
            </button>
          </div>

          <OrderCard order={searchedOrder} />
        </div>
      )}

      {/* Authenticated Customer Orders List */}
      {isLoggedIn ? (
        <div className="space-y-6">
          <div className="flex items-center justify-between">
            <h2 className="font-serif-luxury text-lg sm:text-xl font-bold text-[#2E211E]">
              Your Order History & Tracking
            </h2>
            <Link
              href="/shop"
              className="text-xs font-semibold text-[#913638] hover:underline inline-flex items-center gap-1"
            >
              <span>Explore Collection</span>
              <ArrowRight className="w-3 h-3" />
            </Link>
          </div>

          {orders.length === 0 ? (
            <div className="bg-white rounded-3xl border border-[#E7D1CC] p-10 text-center space-y-4 shadow-boutique">
              <Package className="w-12 h-12 text-[#E7D1CC] mx-auto mb-2" />
              <p className="text-base font-bold text-[#2E211E]">No orders placed yet</p>
              <p className="text-xs text-[#786864] max-w-sm mx-auto">
                Explore our curated crochet collection to bring home your first handcrafted piece of happiness.
              </p>
              <Link
                href="/shop"
                className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-block"
              >
                Start Shopping →
              </Link>
            </div>
          ) : (
            <div className="space-y-6">
              {orders.map((order) => (
                <OrderCard key={order.id} order={order} />
              ))}
            </div>
          )}
        </div>
      ) : (
        /* Logged Out Banner */
        !searchedOrder && (
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-8 text-center space-y-4 shadow-boutique">
            <div className="w-12 h-12 rounded-full bg-[#FCE9E5] text-[#913638] flex items-center justify-center mx-auto">
              <Package className="w-6 h-6" />
            </div>
            <div>
              <h2 className="font-serif-luxury text-xl font-bold text-[#2E211E]">
                Have a KNOTELLE Account?
              </h2>
              <p className="text-xs sm:text-sm text-[#786864] mt-1 max-w-md mx-auto">
                Sign in to view all your past orders, delivery receipts, and live crafting progress in one place.
              </p>
            </div>

            <div className="flex flex-wrap items-center justify-center gap-3 pt-2">
              <Link
                href="/login?redirectTo=/account/orders"
                className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-flex items-center gap-1.5 shadow-sm"
              >
                <span>Sign In to Your Account</span>
                <ArrowRight className="w-3.5 h-3.5" />
              </Link>

              <Link
                href="/contact"
                className="px-5 py-2.5 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] text-xs font-semibold transition-all inline-flex items-center gap-1.5 shadow-xs"
              >
                <HelpCircle className="w-4 h-4 text-[#913638]" />
                <span>Need Help Finding Order?</span>
              </Link>
            </div>
          </div>
        )
      )}

      {/* Support & Policies Cross-link Footer */}
      <section className="rounded-3xl border border-[#E7D1CC] bg-[#FFF9F6] p-6 sm:p-7 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div className="text-center sm:text-left space-y-1">
          <h3 className="font-serif-luxury text-sm sm:text-base font-bold text-[#2E211E]">
            Questions about our delivery process?
          </h3>
          <p className="text-xs text-[#786864]">
            Review our shipping timeline or return guidelines before your order arrives.
          </p>
        </div>

        <div className="flex items-center gap-2.5 shrink-0">
          <Link
            href="/contact"
            className="px-4 py-2 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] text-xs font-semibold transition-colors"
          >
            Shipping Policy
          </Link>
          <Link
            href="/contact/return-refund"
            className="px-4 py-2 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#FCE9E5] text-xs font-semibold transition-colors"
          >
            Return Policy
          </Link>
        </div>
      </section>

    </div>
  );
}

/**
 * Dedicated Order Card displaying:
 * - Order number
 * - Order date
 * - Product
 * - Total
 * - Current status
 * - View details button
 * - Clean horizontal timeline on desktop
 * - Clean vertical timeline on mobile
 */
function OrderCard({ order }: { order: Order }) {
  const firstItem = order.items?.[0];
  const otherItemsCount = (order.items?.length || 1) - 1;
  const currentStatus = order.status || "Order Placed";

  return (
    <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-7 shadow-boutique space-y-6 hover:border-[#EFB8B0] transition-colors">
      
      {/* Top Details Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#E7D1CC]">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-bold uppercase tracking-wider text-[#786864]">
              Order ID
            </span>
            <span className="font-mono text-sm sm:text-base font-bold text-[#913638]">
              {order.orderNumber}
            </span>
          </div>
          <p className="text-xs text-[#786864]">
            Ordered on <strong className="text-[#2E211E]">{order.orderDate}</strong>
            {order.estimatedDelivery && (
              <span> &bull; Est. Delivery: {order.estimatedDelivery}</span>
            )}
          </p>
        </div>

        <div className="flex items-center gap-2 sm:gap-2.5 flex-wrap">
          <span
            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${getStatusBadgeStyle(
              currentStatus
            )}`}
          >
            <span className="w-1.5 h-1.5 rounded-full bg-current animate-pulse" />
            <span>{currentStatus}</span>
          </span>

          <a
            href={getFullPath(`/orders/${order.orderNumber || order.id}/invoice?download=1`)}
            target="_blank"
            rel="noopener noreferrer"
            className="px-3 py-1.5 rounded-full bg-white text-[#913638] border border-[#913638]/40 hover:bg-[#FCE9E5] hover:border-[#913638] text-xs font-semibold active:scale-[0.98] transition-all inline-flex items-center gap-1.5 shadow-2xs group cursor-pointer"
            title="Download Invoice"
          >
            <Download className="w-3 h-3 group-hover:translate-y-0.5 transition-transform" />
            <span>Invoice</span>
          </a>

          <a
            href={getFullPath(`/account/orders/${order.id}/`)}
            className="px-4 py-1.5 rounded-full bg-[#FFF9F6] text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] hover:bg-[#913638] hover:text-white text-xs font-semibold active:scale-[0.98] transition-all inline-flex items-center gap-1 shadow-2xs"
          >
            <span>View Details</span>
            <ArrowRight className="w-3 h-3" />
          </a>
        </div>
      </div>

      {/* Product Summary Row */}
      <div className="flex items-center justify-between gap-4 bg-[#FFF9F6] rounded-2xl p-4 border border-[#E7D1CC]/60">
        <div className="flex items-center gap-3 min-w-0">
          <div className="relative w-14 h-14 rounded-xl overflow-hidden bg-white border border-[#E7D1CC] shrink-0">
            {firstItem?.product ? (
              <Image
                src={normalizeImageUrl(
                  firstItem.product.main_image || firstItem.product.images?.[0],
                  "/images/products/bunny-keychain.jpg"
                )}
                alt={firstItem.product.name}
                fill
                sizes="56px"
                className="object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-[#913638]">
                <Package className="w-6 h-6" />
              </div>
            )}
          </div>

          <div className="min-w-0">
            <p className="text-xs sm:text-sm font-bold text-[#2E211E] truncate">
              {firstItem?.product?.name || "Handcrafted Crochet Item"}
            </p>
            <p className="text-[11px] text-[#786864]">
              Quantity: {firstItem?.quantity || 1}
              {otherItemsCount > 0 && ` &bull; +${otherItemsCount} other item(s)`}
            </p>
          </div>
        </div>

        <div className="text-right shrink-0">
          <span className="text-[10px] uppercase font-bold text-[#786864] block">
            Total Amount
          </span>
          <span className="font-serif-luxury text-base sm:text-lg font-bold text-[#2E211E]">
            ₹{(order.total || 0).toLocaleString("en-IN")}
          </span>
        </div>
      </div>

      {/* Timeline Section: Horizontal on Desktop, Vertical on Mobile */}
      <div className="pt-2">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5 text-[#913638]" />
            <span className="text-xs font-bold text-[#2E211E] uppercase tracking-wider">
              Crafting & Delivery Progress
            </span>
          </div>
          {order.trackingNumber && (
            <span className="text-[11px] text-[#786864]">
              AWB: <strong className="font-mono text-[#2E211E]">{order.trackingNumber}</strong> ({order.courierName || "Courier"})
            </span>
          )}
        </div>

        {/* Desktop View: Horizontal Timeline */}
        <div className="hidden md:block">
          <HorizontalTimeline currentStatus={currentStatus} />
        </div>

        {/* Mobile View: Vertical Timeline */}
        <div className="block md:hidden pt-2">
          <VerticalTimeline currentStatus={currentStatus} />
        </div>
      </div>

    </div>
  );
}
