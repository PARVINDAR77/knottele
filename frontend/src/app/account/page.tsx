"use client";

import React from "react";
import Link from "next/link";
import Image from "next/image";
import {
  Package,
  Heart,
  MapPin,
  Clock,
  ArrowRight,
  Sparkles,
  ShoppingBag,
  CheckCircle2,
  ChevronRight,
  Download,
} from "lucide-react";
import { useAuth } from "@/context/AuthContext";
import { useWishlist } from "@/context/WishlistContext";
import { fetchCustomerCustomOrders, getFullPath } from "@/lib/api";

export default function AccountDashboardPage() {
  const { user, orders } = useAuth();
  const { wishlistCount } = useWishlist();
  const [customOrdersCount, setCustomOrdersCount] = React.useState(0);

  React.useEffect(() => {
    fetchCustomerCustomOrders()
      .then((res) => {
        if (Array.isArray(res)) setCustomOrdersCount(res.length);
      })
      .catch(() => {});
  }, [user]);

  const getStatusBadgeStyle = (status: string) => {
    const s = (status || "").toLowerCase();
    if (s.includes("delivered")) return "bg-emerald-50 text-emerald-800 border-emerald-200";
    if (s.includes("refunded")) return "bg-purple-50 text-purple-800 border-purple-200";
    if (s.includes("cancelled")) return "bg-rose-50 text-rose-800 border-rose-200";
    if (s.includes("shipped")) return "bg-blue-50 text-blue-800 border-blue-200";
    return "bg-[#FCE9E5] text-[#913638] border-[#E7D1CC]";
  };

  const activeOrders = orders.filter((o) => {
    const s = (o.status || "").toLowerCase();
    return !s.includes("delivered") && !s.includes("cancelled") && !s.includes("refunded");
  });

  const recentOrders = orders.slice(0, 3);
  const firstName = user?.name ? user.name.split(" ")[0] : "Member";

  return (
    <div className="space-y-6 animate-in fade-in">
      {/* Welcome Banner */}
      <div className="bg-gradient-to-br from-white via-[#FFF9F6] to-[#FCE9E5]/40 rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#FCE9E5] border border-[#E7D1CC] text-[11px] font-bold text-[#913638] uppercase tracking-wider mb-2">
              <Sparkles className="w-3.5 h-3.5" />
              <span>Welcome Back</span>
            </div>
            <h1 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
              Hello, {firstName}! 🌸
            </h1>
            <p className="text-xs sm:text-sm text-[#786864] mt-1 max-w-xl leading-relaxed">
              Track your handmade crochet creations, manage your saved favorites, and customize your delivery addresses all in one place.
            </p>
          </div>
          <div className="shrink-0 flex sm:flex-col items-start sm:items-end gap-2">
            <Link
              href="/shop"
              className="px-5 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-flex items-center gap-2 shadow-xs"
            >
              <ShoppingBag className="w-3.5 h-3.5" />
              <span>Explore Shop</span>
            </Link>
          </div>
        </div>
      </div>

      {/* 5 Stat Metric Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        {/* Total Orders */}
        <Link
          href="/account/orders"
          className="p-5 rounded-2xl bg-white border border-[#E7D1CC] hover:border-[#EFB8B0] shadow-xs hover:shadow-boutique transition-all group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-xl bg-[#FCE9E5] text-[#913638] flex items-center justify-center group-hover:scale-105 transition-transform">
              <Package className="w-5 h-5" />
            </div>
            <ChevronRight className="w-4 h-4 text-[#786864]/40 group-hover:text-[#913638] group-hover:translate-x-0.5 transition-all" />
          </div>
          <p className="text-2xl font-bold font-serif-luxury text-[#2E211E]">
            {orders.length}
          </p>
          <p className="text-xs text-[#786864] mt-0.5 font-medium">Total Orders</p>
        </Link>

        {/* Custom Orders */}
        <Link
          href="/account/custom-orders"
          className="p-5 rounded-2xl bg-white border border-[#E7D1CC] hover:border-[#EFB8B0] shadow-xs hover:shadow-boutique transition-all group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center group-hover:scale-105 transition-transform">
              <Sparkles className="w-5 h-5" />
            </div>
            <ChevronRight className="w-4 h-4 text-[#786864]/40 group-hover:text-[#913638] group-hover:translate-x-0.5 transition-all" />
          </div>
          <p className="text-2xl font-bold font-serif-luxury text-[#2E211E]">
            {customOrdersCount}
          </p>
          <p className="text-xs text-[#786864] mt-0.5 font-medium">Custom Orders</p>
        </Link>

        {/* Active In-Progress Orders */}
        <Link
          href="/account/orders"
          className="p-5 rounded-2xl bg-white border border-[#E7D1CC] hover:border-[#EFB8B0] shadow-xs hover:shadow-boutique transition-all group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center group-hover:scale-105 transition-transform">
              <Clock className="w-5 h-5" />
            </div>
            <ChevronRight className="w-4 h-4 text-[#786864]/40 group-hover:text-[#913638] group-hover:translate-x-0.5 transition-all" />
          </div>
          <p className="text-2xl font-bold font-serif-luxury text-[#2E211E]">
            {activeOrders.length}
          </p>
          <p className="text-xs text-[#786864] mt-0.5 font-medium">Active In-Flight</p>
        </Link>

        {/* Wishlist Items */}
        <Link
          href="/account/wishlist"
          className="p-5 rounded-2xl bg-white border border-[#E7D1CC] hover:border-[#EFB8B0] shadow-xs hover:shadow-boutique transition-all group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-xl bg-rose-50 text-[#913638] flex items-center justify-center group-hover:scale-105 transition-transform">
              <Heart className="w-5 h-5 fill-[#913638]" />
            </div>
            <ChevronRight className="w-4 h-4 text-[#786864]/40 group-hover:text-[#913638] group-hover:translate-x-0.5 transition-all" />
          </div>
          <p className="text-2xl font-bold font-serif-luxury text-[#2E211E]">
            {wishlistCount}
          </p>
          <p className="text-xs text-[#786864] mt-0.5 font-medium">Saved in Wishlist</p>
        </Link>

        {/* Saved Addresses */}
        <Link
          href="/account/addresses"
          className="p-5 rounded-2xl bg-white border border-[#E7D1CC] hover:border-[#EFB8B0] shadow-xs hover:shadow-boutique transition-all group"
        >
          <div className="flex items-center justify-between mb-3">
            <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center group-hover:scale-105 transition-transform">
              <MapPin className="w-5 h-5" />
            </div>
            <ChevronRight className="w-4 h-4 text-[#786864]/40 group-hover:text-[#913638] group-hover:translate-x-0.5 transition-all" />
          </div>
          <p className="text-2xl font-bold font-serif-luxury text-[#2E211E]">
            {user?.addresses?.length || 0}
          </p>
          <p className="text-xs text-[#786864] mt-0.5 font-medium">Saved Addresses</p>
        </Link>
      </div>

      {/* Recent Orders Section */}
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6">
        <div className="flex items-center justify-between pb-4 border-b border-[#E7D1CC]">
          <div>
            <h2 className="font-serif-luxury text-xl font-bold text-[#2E211E]">
              Recent Orders
            </h2>
            <p className="text-xs text-[#786864] mt-0.5">
              Review your recent artisanal purchases and tracking statuses.
            </p>
          </div>
          {orders.length > 0 && (
            <Link
              href="/account/orders"
              className="text-xs font-semibold text-[#913638] hover:text-[#74292B] hover:underline flex items-center gap-1"
            >
              <span>View All ({orders.length})</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          )}
        </div>

        {recentOrders.length === 0 ? (
          <div className="py-10 text-center space-y-3">
            <div className="w-12 h-12 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-[#913638] flex items-center justify-center mx-auto">
              <Package className="w-6 h-6" />
            </div>
            <div>
              <p className="text-sm font-bold text-[#2E211E]">No orders placed yet</p>
              <p className="text-xs text-[#786864] max-w-sm mx-auto mt-1">
                Your wardrobe is waiting for something handmade! Browse our bestselling crochet bags, bouquets, and accessories.
              </p>
            </div>
            <Link
              href="/shop"
              className="inline-block px-5 py-2 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] transition-all shadow-xs"
            >
              Browse Shop Collection →
            </Link>
          </div>
        ) : (
          <div className="space-y-3">
            {recentOrders.map((order) => (
              <div
                key={order.id}
                className="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC]/70 hover:border-[#EFB8B0] transition-all gap-4"
              >
                <div className="flex items-center gap-4 min-w-0">
                  <div className="w-10 h-10 rounded-xl bg-white border border-[#E7D1CC] text-[#913638] flex items-center justify-center shrink-0">
                    <Package className="w-5 h-5" />
                  </div>
                  <div className="min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-mono font-bold text-xs text-[#913638]">
                        {order.orderNumber}
                      </span>
                      <span className="text-[10px] text-[#786864]">• {order.orderDate}</span>
                      <span
                        className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border ${getStatusBadgeStyle(
                          order.status
                        )}`}
                      >
                        <span className="w-1 h-1 rounded-full bg-current" />
                        <span>{order.status}</span>
                      </span>
                    </div>
                    <p className="text-xs font-medium text-[#2E211E] truncate mt-1">
                      {order.items[0]?.product.name || "Handcrafted Crochet Item"}
                      {order.items.length > 1 && (
                        <span className="text-[#786864] ml-1">
                          (+{order.items.length - 1} more)
                        </span>
                      )}
                    </p>
                  </div>
                </div>

                <div className="flex items-center justify-between sm:justify-end gap-2 sm:gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-[#E7D1CC]/50 flex-wrap">
                  <span className="text-sm font-bold text-[#2E211E]">
                    ₹{order.total.toLocaleString("en-IN")}
                  </span>
                  <a
                    href={getFullPath(`/orders/${order.orderNumber || order.id}/invoice?download=1`)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-2.5 py-1.5 rounded-full bg-white text-[#913638] border border-[#913638]/40 hover:bg-[#FCE9E5] text-xs font-semibold transition-all inline-flex items-center gap-1 shadow-2xs group cursor-pointer"
                    title="Download Invoice"
                  >
                    <Download className="w-3 h-3 group-hover:translate-y-0.5 transition-transform" />
                    <span className="hidden sm:inline">Invoice</span>
                  </a>
                  <Link
                    href={`/account/orders/${order.id}`}
                    className="px-3.5 py-1.5 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#913638] text-xs font-semibold hover:bg-[#FCE9E5] hover:text-[#913638] transition-all inline-flex items-center gap-1 shadow-2xs"
                  >
                    <span>View</span>
                    <ArrowRight className="w-3 h-3" />
                  </Link>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Quick Action Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {/* Custom Order Box */}
        <div className="p-5 rounded-3xl bg-gradient-to-br from-[#FFF9F6] to-[#FCE9E5]/60 border border-[#E7D1CC] flex items-start gap-4">
          <div className="w-10 h-10 rounded-2xl bg-[#913638] text-white flex items-center justify-center shrink-0 shadow-xs">
            <Sparkles className="w-5 h-5" />
          </div>
          <div className="space-y-1">
            <h3 className="font-serif-luxury text-sm font-bold text-[#2E211E]">
              Custom Made Just for You
            </h3>
            <p className="text-xs text-[#786864] leading-relaxed">
              Have a bespoke design in mind? Share your inspiration and our artisans will knit your vision into reality.
            </p>
            <Link
              href="/custom-order"
              onClick={(e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                e.preventDefault();
                window.location.href = "/custom-order";
              }}
              className="inline-block text-xs font-semibold text-[#913638] hover:underline pt-1 cursor-pointer"
            >
              Request Custom Piece →
            </Link>
          </div>
        </div>

        {/* Addresses Box */}
        <div className="p-5 rounded-3xl bg-gradient-to-br from-[#FFF9F6] to-[#FCE9E5]/60 border border-[#E7D1CC] flex items-start gap-4">
          <div className="w-10 h-10 rounded-2xl bg-[#913638] text-white flex items-center justify-center shrink-0 shadow-xs">
            <MapPin className="w-5 h-5" />
          </div>
          <div className="space-y-1">
            <h3 className="font-serif-luxury text-sm font-bold text-[#2E211E]">
              Shipping Addresses
            </h3>
            <p className="text-xs text-[#786864] leading-relaxed">
              Ensure swift delivery for every purchase. Add or modify your home and gift delivery destinations.
            </p>
            <Link
              href="/account/addresses"
              className="inline-block text-xs font-semibold text-[#913638] hover:underline pt-1"
            >
              Manage Saved Addresses →
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
