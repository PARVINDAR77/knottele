"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import { notFound, useParams } from "next/navigation";
import {
  Clock,
  ArrowLeft,
  Sparkles,
  Loader2,
  Download,
} from "lucide-react";
import { useAuth } from "@/context/AuthContext";
import { normalizeImageUrl, fetchOrderById, getFullPath } from "@/lib/api";
import { Order } from "@/types/order";

interface OrderDetailViewProps {
  id: string;
}

export default function OrderDetailView({ id: propId }: OrderDetailViewProps) {
  const { orders } = useAuth();
  const [apiOrder, setApiOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(false);

  const params = useParams();
  const rawParamId = typeof params?.id === "string" ? params.id : Array.isArray(params?.id) ? params.id[0] : "";
  const pathnameId = typeof window !== "undefined"
    ? window.location.pathname.replace(/^.*\/account\/orders\//, "").replace(/\/.*$/, "")
    : "";

  const effectiveId = (propId && propId !== "placeholder")
    ? propId
    : (rawParamId && rawParamId !== "placeholder")
      ? rawParamId
      : (pathnameId && pathnameId !== "placeholder" ? pathnameId : "");

  const cleanId = decodeURIComponent(effectiveId || "");
  const contextOrder = orders.find(
    (o) =>
      o.id === cleanId ||
      o.orderNumber === cleanId ||
      o.id === `ord-${cleanId}` ||
      o.id === cleanId.replace("ord-", "") ||
      o.orderNumber.toLowerCase() === cleanId.toLowerCase()
  );

  const order = contextOrder || apiOrder;

  useEffect(() => {
    if (!contextOrder && cleanId && cleanId !== "placeholder") {
      setLoading(true);
      fetchOrderById(cleanId)
        .then((fetched) => {
          if (fetched) {
            setApiOrder(fetched);
          }
        })
        .finally(() => setLoading(false));
    }
  }, [cleanId, contextOrder]);

  if (loading && !order) {
    return (
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-12 text-center space-y-4">
        <Loader2 className="w-8 h-8 text-[#913638] animate-spin mx-auto" />
        <p className="text-xs text-[#786864]">Fetching tracking & order details...</p>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-8 text-center space-y-4">
        <h2 className="font-serif-luxury text-xl font-bold text-[#2E211E]">Order Not Found</h2>
        <p className="text-xs text-[#786864]">We couldn't locate an order with ID: <strong className="font-mono">{cleanId}</strong></p>
        <Link
          href="/account/orders"
          className="inline-block px-6 py-2 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] transition-colors"
        >
          ← Back to All Orders
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-8 animate-in fade-in">
      
      {/* Back Link & Header */}
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-4">
        <Link
          href="/account/orders"
          className="inline-flex items-center gap-1.5 text-xs font-semibold text-[#913638] hover:underline"
        >
          <ArrowLeft className="w-3.5 h-3.5" />
          <span>Back to All Orders</span>
        </Link>

        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2 border-t border-[#E7D1CC]">
          <div>
            <span className="text-[10px] uppercase font-bold text-[#786864] tracking-wider">
              Handmade Boutique Order
            </span>
            <h1 className="font-mono text-xl sm:text-2xl font-bold text-[#913638]">
              {order.orderNumber}
            </h1>
            <p className="text-xs text-[#786864] mt-0.5">
              Placed on {order.orderDate} &bull; Est. Delivery: {order.estimatedDelivery}
            </p>
          </div>

          <div className="flex flex-col sm:items-end gap-2">
            <div className="flex items-center gap-2 flex-wrap">
              <a
                href={getFullPath(`/orders/${order.orderNumber || order.id}/invoice?download=1`)}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#913638] text-white hover:bg-[#74292B] active:scale-[0.98] transition-all shadow-xs group cursor-pointer"
                title="Download or Print Official Invoice"
              >
                <Download className="w-3.5 h-3.5 group-hover:translate-y-0.5 transition-transform" />
                <span>Download Invoice</span>
              </a>

              <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC]">
                <Sparkles className="w-3.5 h-3.5 text-[#C69A5A]" />
                <span>{order.status}</span>
              </span>
            </div>

            {order.trackingNumber && (
              <p className="text-[11px] text-[#786864] mt-0.5">
                Tracking: <strong className="font-mono text-[#2E211E]">{order.trackingNumber}</strong> ({order.courierName})
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Handmade Progress Timeline */}
      <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6">
        <div className="flex items-center justify-between border-b border-[#E7D1CC] pb-3">
          <div className="flex items-center gap-2">
            <Clock className="w-4 h-4 text-[#913638]" />
            <h2 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
              Crafting & Delivery Journey
            </h2>
          </div>
          <span className="text-xs text-[#786864]">Live Status</span>
        </div>

        {/* Numbered & Icon Timeline */}
        <div className="relative pl-6 sm:pl-8 space-y-8 before:absolute before:left-2.5 sm:before:left-3.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#E7D1CC]">
          {order.timeline.map((step, index) => {
            const isDone = step.completed;
            const isCurrent = step.current;
            return (
              <div key={index} className="relative group">
                {/* Step Circle Indicator */}
                <div
                  className={`absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all ${
                    isCurrent
                      ? "bg-[#913638] text-white ring-4 ring-[#913638]/20"
                      : isDone
                      ? "bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC]"
                      : "bg-[#FFF9F6] text-[#786864] border border-[#E7D1CC]"
                  }`}
                >
                  {isDone ? "✓" : index + 1}
                </div>

                <div className="space-y-0.5">
                  <div className="flex items-center gap-2">
                    <h3 className={`text-xs sm:text-sm font-bold ${isCurrent ? "text-[#913638]" : isDone ? "text-[#2E211E]" : "text-[#786864]"}`}>
                      {step.status}
                    </h3>
                    {isCurrent && (
                      <span className="text-[10px] font-semibold bg-[#913638] text-white px-2 py-0.5 rounded-full">
                        In Progress
                      </span>
                    )}
                  </div>
                  <p className="text-[11px] text-[#786864]">{step.description}</p>
                  <span className="text-[10px] text-[#786864]/80 font-mono block pt-0.5">
                    {step.date}
                  </span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Order Items & Breakdown */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        {/* Items List (Spans 7 cols) */}
        <div className="md:col-span-7 bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-4">
          <h3 className="font-serif-luxury text-base font-bold text-[#2E211E] pb-3 border-b border-[#E7D1CC]">
            Items in this Package ({order.items.length})
          </h3>

          <div className="divide-y divide-[#E7D1CC]/60 space-y-3">
            {order.items.map((item) => (
              <div key={item.id} className="pt-3 first:pt-0 flex gap-4 items-center">
                <div className="relative w-16 h-16 rounded-xl overflow-hidden bg-[#FFF9F6] shrink-0 border border-[#E7D1CC]">
                  <Image
                    src={normalizeImageUrl(item.product.main_image || item.product.images?.[0], "/images/products/bunny-keychain.jpg")}
                    alt={item.product.name}
                    fill
                    sizes="64px"
                    className="object-cover"
                  />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-bold text-[#2E211E] truncate">
                    {item.product.name}
                  </p>
                  {item.customization?.color && (
                    <p className="text-[10px] text-[#786864]">
                      Color: {item.customization.color.name}
                    </p>
                  )}
                  {item.customization?.customText && (
                    <p className="text-[10px] text-[#913638] font-semibold">
                      Tag: &ldquo;{item.customization.customText}&rdquo;
                    </p>
                  )}
                  <p className="text-[11px] text-[#786864] mt-0.5">
                    Qty: {item.quantity} &bull; ₹{item.price.toLocaleString("en-IN")} each
                  </p>
                </div>
                <span className="text-xs font-bold text-[#913638]">
                  ₹{(item.price * item.quantity).toLocaleString("en-IN")}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Shipping & Payment (Spans 5 cols) */}
        <div className="md:col-span-5 space-y-6">
          
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-3 text-xs">
            <h3 className="font-serif-luxury text-base font-bold text-[#2E211E] pb-2 border-b border-[#E7D1CC]">
              Delivery Address
            </h3>
            <p className="font-bold text-[#2E211E]">{order.shippingAddress.fullName}</p>
            <p className="text-[#786864] leading-relaxed">
              {order.shippingAddress.addressLine1}, {order.shippingAddress.addressLine2}
              <br />
              {order.shippingAddress.city}, {order.shippingAddress.state} - {order.shippingAddress.pincode}
            </p>
            <p className="text-[#786864] pt-1">
              Phone: {order.shippingAddress.phone}
            </p>
          </div>

          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-3 text-xs">
            <h3 className="font-serif-luxury text-base font-bold text-[#2E211E] pb-2 border-b border-[#E7D1CC]">
              Payment Summary
            </h3>
            <div className="space-y-1.5 text-[#786864]">
              <div className="flex justify-between">
                <span>Subtotal:</span>
                <span className="font-semibold text-[#2E211E]">₹{order.subtotal.toLocaleString("en-IN")}</span>
              </div>
              {order.discount > 0 && (
                <div className="flex justify-between text-[#913638]">
                  <span>Discount:</span>
                  <span className="font-bold">-₹{order.discount.toLocaleString("en-IN")}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span>Shipping:</span>
                <span>{order.shipping === 0 ? "FREE" : `₹${order.shipping}`}</span>
              </div>
              <div className="flex justify-between text-sm font-bold text-[#2E211E] pt-2 border-t border-[#E7D1CC]">
                <span>Total Paid:</span>
                <span className="text-[#913638]">₹{Math.max(0, (order.subtotal || 0) - (order.discount || 0) + (order.shipping || 0)).toLocaleString("en-IN")}</span>
              </div>
              <p className="text-[10px] text-[#786864] pt-1">
                Mode: {order.paymentMethod} ({order.paymentStatus})
              </p>
              {order.paymentStatus?.toLowerCase().includes("pending") && (
                <div className="mt-2 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-amber-950">
                    <Clock className="w-3.5 h-3.5 text-amber-700 animate-spin" />
                    <span>Payment Verification Pending</span>
                  </div>
                  <p className="text-[11px] text-amber-800 leading-relaxed">
                    Our team is verifying your payment receipt. Once approved, order crafting will commence.
                  </p>
                  {order.utrNumber && (
                    <p className="text-[11px] font-mono text-amber-950 font-semibold pt-0.5">
                      UTR: <span className="bg-white/80 px-1.5 py-0.5 rounded border border-amber-300">{order.utrNumber}</span>
                    </p>
                  )}
                </div>
              )}
            </div>

            <div className="pt-3 border-t border-[#E7D1CC]">
              <a
                href={getFullPath(`/orders/${order.orderNumber || order.id}/invoice?download=1`)}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full py-2 px-3 rounded-full bg-[#FFF9F6] text-[#913638] border border-[#EFB8B0] hover:bg-[#913638] hover:text-white text-xs font-semibold active:scale-[0.98] transition-all inline-flex items-center justify-center gap-1.5 shadow-2xs group cursor-pointer"
              >
                <Download className="w-3.5 h-3.5 group-hover:translate-y-0.5 transition-transform" />
                <span>Download Tax Invoice</span>
              </a>
            </div>
          </div>

        </div>

      </div>

    </div>
  );
}
