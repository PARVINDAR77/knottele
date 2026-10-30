"use client";

import React, { Suspense } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { CheckCircle2, Heart, Sparkles, ArrowRight, Package, Printer, Download, Clock } from "lucide-react";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { getFullPath } from "@/lib/api";

function ConfirmationContent() {
  const searchParams = useSearchParams();
  const orderNumber = searchParams.get("orderNumber") || "KN-ORDER";
  const orderId = searchParams.get("orderId") || "";
  const method = searchParams.get("method") || "";

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-10 lg:py-16">
      <div className="max-w-2xl mx-auto px-4 sm:px-6 text-center">
        
        {/* Animated Check Icon Box */}
        <div className="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-6">
          <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-[#FCE9E5] border-2 border-[#E7D1CC] flex items-center justify-center text-[#913638] shadow-lg animate-in zoom-in-50 duration-500">
            <CheckCircle2 className="w-10 h-10 sm:w-12 sm:h-12 text-[#913638]" />
          </div>
          <div className="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-white border border-[#E7D1CC] flex items-center justify-center text-[#C69A5A] shadow-sm">
            <Sparkles className="w-4 h-4" />
          </div>
        </div>

        {/* Heading & Brand Message */}
        <div className="space-y-3 mb-8">
          <div className="flex justify-center mb-1">
            <BotanicalFlourish className="w-24 h-5 text-[#EFB8B0]" />
          </div>

          <h1 className="font-serif-luxury text-2xl sm:text-4xl lg:text-5xl font-bold text-[#2E211E] tracking-tight">
            Thank You for Your Order!
          </h1>

          <p className="text-xs sm:text-base text-[#786864] max-w-md mx-auto leading-relaxed">
            Your handmade creation is now on its way to becoming something special. Our artisan is preparing soft cotton yarns with love.
          </p>
        </div>

        {/* Order Details Receipt Box */}
        <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique text-left space-y-4 mb-8">
          <div className="flex items-center justify-between pb-3 border-b border-[#E7D1CC]">
            <div>
              <p className="text-[10px] uppercase font-bold text-[#786864] tracking-wider">
                Order Reference
              </p>
              <p className="font-mono text-sm sm:text-base font-bold text-[#913638]">
                {orderNumber}
              </p>
            </div>
            {method === "upi_qr" ? (
              <span className="px-3 py-1 rounded-full bg-amber-50 text-amber-900 border border-amber-300 text-xs font-bold flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-amber-700 animate-spin" />
                Payment Verification Pending
              </span>
            ) : (
              <span className="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold">
                Payment Confirmed
              </span>
            )}
          </div>

          <div className="grid grid-cols-2 gap-4 text-xs text-[#786864]">
            <div>
              <p className="text-[10px] uppercase font-semibold text-[#786864]/80">Order Date</p>
              <p className="font-medium text-[#2E211E] mt-0.5">
                {new Date().toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}
              </p>
            </div>
            <div>
              <p className="text-[10px] uppercase font-semibold text-[#786864]/80">Estimated Delivery</p>
              <p className="font-medium text-[#2E211E] mt-0.5">
                {new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}
              </p>
            </div>
          </div>

          {/* UPI Pending Verification Callout */}
          {method === "upi_qr" && (
            <div className="p-4 rounded-2xl bg-amber-50/90 border border-amber-200 text-left space-y-1.5">
              <div className="flex items-center gap-2 text-amber-950 font-bold text-xs sm:text-sm">
                <Clock className="w-4 h-4 text-amber-700 shrink-0" />
                <span>UPI Payment Verification Pending Admin Approval</span>
              </div>
              <p className="text-xs text-amber-900/90 leading-relaxed">
                Thank you! Your UPI reference number has been received and is waiting for admin verification. Once verified by our team, your order status will be updated to confirmed and crafting will begin.
              </p>
            </div>
          )}

          {/* Handmade Progress Callout */}
          <div className="p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] flex items-center gap-3 text-xs text-[#2E211E]">
            <FlowerIcon className="w-5 h-5 text-[#913638] shrink-0" />
            <span>
              <strong>Crafting Notification:</strong> We will send you progress photos as your flowers or plushies take shape on our wooden hooks.
            </span>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row items-center justify-center gap-3">
          <Link
            href={`/account/orders/${orderId}`}
            className="w-full sm:w-auto px-6 py-3 rounded-full bg-[#913638] text-white text-xs sm:text-sm font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-sm hover:shadow-boutique-hover transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <Package className="w-4 h-4" />
            <span>Track Order Timeline</span>
          </Link>
          <a
            href={getFullPath(`/orders/${orderNumber || orderId}/invoice?download=1`)}
            target="_blank"
            rel="noopener noreferrer"
            className="w-full sm:w-auto px-6 py-3 rounded-full bg-white text-[#913638] border border-[#913638]/40 hover:bg-[#FCE9E5] text-xs sm:text-sm font-semibold active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs group"
          >
            <Download className="w-4 h-4 group-hover:translate-y-0.5 transition-transform" />
            <span>Download Invoice</span>
          </a>
          <Link
            href="/shop"
            className="w-full sm:w-auto px-6 py-3 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs sm:text-sm font-semibold hover:bg-[#FCE9E5] hover:text-[#913638] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs"
          >
            <span>Continue Shopping</span>
            <ArrowRight className="w-4 h-4" />
          </Link>
        </div>

        <div className="flex items-center justify-center gap-1 text-[11px] text-[#786864] mt-8">
          <Heart className="w-3.5 h-3.5 fill-[#913638] text-[#913638]" />
          <span>Made with love by KNOTELLE Atelier</span>
        </div>

      </div>
    </div>
  );
}

export default function OrderConfirmationPage() {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-[#FFF8F5] flex items-center justify-center">
          <div className="w-8 h-8 border-2 border-[#8F3032] border-t-transparent rounded-full animate-spin" />
        </div>
      }
    >
      <ConfirmationContent />
    </Suspense>
  );
}
