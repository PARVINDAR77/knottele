"use client";

import React, { useEffect } from "react";
import Link from "next/link";
import { RefreshCw, Home, ShoppingBag, Sparkles } from "lucide-react";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";

export default function ErrorBoundary({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error("KNOTELLE App Error Boundary caught error:", error);
  }, [error]);

  return (
    <div className="bg-[#FFF9F6] min-h-[80vh] flex items-center justify-center py-16 px-4">
      <div className="max-w-md w-full bg-white rounded-3xl border border-[#E7D1CC] p-8 sm:p-12 text-center shadow-boutique space-y-6 relative overflow-hidden">
        
        {/* Boutique Icon */}
        <div className="relative w-20 h-20 sm:w-24 sm:h-24 mx-auto">
          <div className="w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-[#FCE9E5] border border-[#E7D1CC] flex items-center justify-center text-[#913638] shadow-sm">
            <FlowerIcon className="w-10 h-10 sm:w-12 sm:h-12 text-[#913638]" />
          </div>
          <div className="absolute -top-1 -right-1 w-8 h-8 rounded-full bg-white border border-[#E7D1CC] flex items-center justify-center text-[#C69A5A] shadow-xs">
            <Sparkles className="w-4 h-4" />
          </div>
        </div>

        <div className="space-y-2">
          <span className="font-serif-luxury text-xs font-bold uppercase tracking-wider text-[#913638] block">
            Artisan Notice
          </span>
          <h1 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
            A Stitch Gently Tangled
          </h1>
          <p className="text-xs sm:text-sm text-[#786864] leading-relaxed">
            We encountered a temporary hiccup while preparing your boutique experience. Let&apos;s untangle this and try again.
          </p>
        </div>

        <div className="flex justify-center">
          <BotanicalFlourish className="w-20 h-4 text-[#EFB8B0]" />
        </div>

        <div className="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
          <button
            type="button"
            onClick={() => reset()}
            className="w-full sm:w-auto px-6 py-3 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer"
          >
            <RefreshCw className="w-3.5 h-3.5" />
            <span>Try Again</span>
          </button>
          <Link
            href="/cart"
            className="w-full sm:w-auto px-5 py-3 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] hover:border-[#EFB8B0] text-xs font-semibold hover:bg-[#FCE9E5] hover:text-[#913638] active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs"
          >
            <ShoppingBag className="w-3.5 h-3.5" />
            <span>Return to Cart</span>
          </Link>
          <Link
            href="/"
            className="w-full sm:w-auto px-5 py-3 rounded-full bg-white text-[#786864] border border-[#E7D1CC] hover:text-[#913638] text-xs font-semibold active:scale-[0.98] transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs"
          >
            <Home className="w-3.5 h-3.5" />
            <span>Home</span>
          </Link>
        </div>

      </div>
    </div>
  );
}
