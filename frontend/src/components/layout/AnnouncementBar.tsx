"use client";

import React from "react";
import Link from "next/link";
import { Sparkles, Phone, Mail } from "lucide-react";
import { WhatsAppIcon } from "@/components/ui/BotanicalDecorations";

export function AnnouncementBar() {
  return (
    <div className="bg-[#FCE9E5] text-[#786864] text-[11px] sm:text-xs py-1.5 px-4 border-b border-[#E7D1CC] relative z-40 transition-colors">
      <div className="max-w-7xl mx-auto flex items-center justify-between">
        
        {/* Left / Center Message */}
        <div className="flex items-center gap-2 sm:gap-4 mx-auto sm:mx-0">
          <span className="flex items-center gap-1.5 font-medium text-[#2E211E]">
            <span className="text-[#C89B61]">✨</span>
            <span>Handmade with Love</span>
          </span>
          <span className="text-[#E7D1CC]">|</span>
          <span className="font-medium text-[#786864]">Pan India Express Shipping</span>
        </div>

        {/* Right Contact Quick Links */}
        <div className="hidden sm:flex items-center gap-4 text-xs font-medium text-[#2E211E]">
          <a
            href="tel:+919773039243"
            className="flex items-center gap-1.5 hover:text-[#913638] transition-colors"
          >
            <Phone className="w-3.5 h-3.5 text-[#913638]" />
            <span>+91 97730 39243</span>
          </a>
          <a
            href="mailto:support@knotelle.in"
            aria-label="Email support"
            className="p-1 hover:text-[#913638] transition-colors"
          >
            <Mail className="w-3.5 h-3.5" />
          </a>
          <a
            href="https://wa.me/919773039243"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="WhatsApp chat"
            className="p-1 hover:text-[#913638] transition-colors"
          >
            <WhatsAppIcon className="w-3.5 h-3.5 text-[#913638]" />
          </a>
        </div>

      </div>
    </div>
  );
}
