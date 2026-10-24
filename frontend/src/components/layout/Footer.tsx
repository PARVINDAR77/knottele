"use client";

import React, { useState, useEffect } from "react";
import Image from "next/image";
import Link from "next/link";
import {
  KnotelleCrownLogo,
  InstagramIcon,
  FacebookIcon,
  PinterestIcon,
  YouTubeIcon,
} from "@/components/ui/BotanicalDecorations";
import { Phone, Mail, MapPin } from "lucide-react";
import { useWebsiteMedia } from "@/context/MediaContext";
import { normalizeInternalLink, normalizeImageUrl } from "@/lib/api";

const DEFAULT_FOOTER_BG: string = "/images/categories/footer.png";
const FALLBACK_FOOTER_BG: string = "/images/footer/footer.png";

export function Footer() {
  const { media } = useWebsiteMedia();
  const [footerBg, setFooterBg] = useState<string>(DEFAULT_FOOTER_BG);

  useEffect(() => {
    const bg = media?.footer?.bg || media?.footer?.image;
    if (bg && !bg.toLowerCase().includes(".json")) {
      setFooterBg(normalizeImageUrl(bg, DEFAULT_FOOTER_BG));
    }
  }, [media?.footer?.bg, media?.footer?.image]);

  const f = media?.footer;

  // Social Links
  const instagram = f?.social?.instagram || { url: "https://instagram.com/knotelleindia", is_active: true };
  const facebook = f?.social?.facebook || { url: "https://facebook.com/knotelleindia", is_active: true };
  const pinterest = f?.social?.pinterest || { url: "https://pinterest.com/knotelleindia", is_active: true };
  const youtube = f?.social?.youtube || { url: "https://youtube.com/@knotelleindia", is_active: true };
  const twitter = f?.social?.twitter || { url: "", is_active: false };
  const linkedin = f?.social?.linkedin || { url: "", is_active: false };

  // Columns & Links
  const col1Title = f?.column_1?.title || "Quick Links";
  const col1Links = (f?.column_1?.links && f.column_1.links.length > 0)
    ? f.column_1.links.filter((l) => l.is_active !== false)
    : [
        { label: "Home", url: "/" },
        { label: "Shop", url: "/shop" },
        { label: "Custom Order", url: "/custom-order" },
        { label: "About", url: "/about" },
        { label: "Contact", url: "/contact" },
      ];

  const col2Title = f?.column_2?.title || "Help";
  const policyRoutesMap: Record<string, string> = {
    "shipping policy": "/contact",
    "shipping": "/contact",
    "return & refund": "/contact/return-refund",
    "return and refund": "/contact/return-refund",
    "returns": "/contact/return-refund",
    "faq": "/contact/faq",
    "faqs": "/contact/faq",
    "frequently asked questions": "/contact/faq",
    "track order": "/account/orders",
    "track your order": "/account/orders",
    "orders": "/account/orders",
  };

  interface PolicyLinkItem {
    label: string;
    url: string;
  }

  const requiredPolicies: PolicyLinkItem[] = [
    { label: "Shipping Policy", url: "/contact" },
    { label: "Return & Refund", url: "/contact/return-refund" },
    { label: "FAQ", url: "/contact/faq" },
    { label: "Track Order", url: "/account/orders" },
  ];

  let col2Links: PolicyLinkItem[] = requiredPolicies;
  if (f?.column_2?.links && f.column_2.links.length > 0) {
    const activeCustom: PolicyLinkItem[] = f.column_2.links
      .filter((l) => l.is_active !== false)
      .map((l) => {
        const rawLabel = l.label || (l as any).name || "Help";
        const lowerLabel = rawLabel.trim().toLowerCase();
        const mappedRoute = policyRoutesMap[lowerLabel];
        return {
          label: rawLabel,
          url: mappedRoute || l.url || (l as any).href || "/contact",
        };
      });

    const presentKeys = new Set(
      activeCustom.map((l) => l.label.trim().toLowerCase())
    );

    const missingPolicies = requiredPolicies.filter(
      (rp) => !presentKeys.has(rp.label.toLowerCase())
    );

    col2Links = [...activeCustom, ...missingPolicies];
  }

  const col3Title = f?.column_3?.title || "Contact";
  const contactPhone = f?.column_3?.phone || "+91 97730 39243";
  const contactPhoneLink = f?.column_3?.phone_link || `tel:${contactPhone.replace(/\s+/g, "")}`;
  const contactEmail = f?.column_3?.email || "support@knotelle.in";
  const contactEmailLink = f?.column_3?.email_link || `mailto:${contactEmail}`;
  const contactAddress = f?.column_3?.address || "India";

  // Bottom Bar
  const currentYear = new Date().getFullYear();
  const rawCopyright = f?.copyright_text || "© {year} Knotelle. All rights reserved.";
  const copyrightText = rawCopyright.includes("{year}")
    ? rawCopyright.replace("{year}", currentYear.toString())
    : rawCopyright;

  const heartTagline = f?.heart_tagline || "Made with ♡ for a kinder, cozier world.";
  const isActive = f?.is_active !== false;

  if (!isActive) return null;

  return (
    <footer className="relative w-full overflow-hidden bg-[#FFF9F6] border-t border-[#E7D1CC]/70 pt-12 sm:pt-16 pb-8 text-[#2E211E]">
      
      {/* Full-Width Panoramic Footer Background Image */}
      <div className="absolute inset-0 z-0 w-full h-full">
        <Image
          src={footerBg}
          alt="KNOTELLE Boutique Footer"
          fill
          quality={100}
          sizes="100vw"
          unoptimized={true}
          className="object-cover object-center lg:object-bottom pointer-events-none"
          onError={() => {
            if (footerBg !== DEFAULT_FOOTER_BG) {
              setFooterBg(DEFAULT_FOOTER_BG);
            } else if (footerBg !== FALLBACK_FOOTER_BG) {
              setFooterBg(FALLBACK_FOOTER_BG);
            }
          }}
        />
        {/* Soft Contrast Gradient Overlays for Crystal-Clear Text Legibility */}
        <div className="absolute inset-0 bg-[#FFF9F6]/85 sm:bg-[#FFF9F6]/75 md:bg-[#FFF9F6]/60 lg:bg-[#FFF9F6]/40 pointer-events-none" />
        <div className="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#D6A79F]/30 to-transparent pointer-events-none" />
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-12 relative z-10">
        
        {/* Main Columns Container */}
        <div className="flex flex-col lg:flex-row items-start justify-between gap-8 lg:gap-12 mb-12">
          
          {/* Left Block: Brand Identity + Socials */}
          <div className="flex flex-col items-start space-y-4 lg:pr-10 lg:border-r lg:border-[#E7D1CC]/90 shrink-0 w-full lg:w-auto">
            <Link href={normalizeInternalLink("/")} className="inline-block group" aria-label="KNOTELLE Home">
              <KnotelleCrownLogo className="h-24 sm:h-28 w-auto drop-shadow-xs" />
            </Link>

            {/* Social Media Icons Row */}
            <div className="flex items-center gap-2.5 pt-1.5 text-[#2E211E]">
              {instagram.is_active !== false && instagram.url && (
                <a
                  href={instagram.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Instagram"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <InstagramIcon className="w-4.5 h-4.5" />
                </a>
              )}
              {facebook.is_active !== false && facebook.url && (
                <a
                  href={facebook.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Facebook"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <FacebookIcon className="w-4.5 h-4.5" />
                </a>
              )}
              {pinterest.is_active !== false && pinterest.url && (
                <a
                  href={pinterest.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Pinterest"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <PinterestIcon className="w-4.5 h-4.5" />
                </a>
              )}
              {youtube.is_active !== false && youtube.url && (
                <a
                  href={youtube.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="YouTube"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <YouTubeIcon className="w-4.5 h-4.5" />
                </a>
              )}
              {twitter.is_active !== false && twitter.url && (
                <a
                  href={twitter.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="Twitter / X"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                  </svg>
                </a>
              )}
              {linkedin.is_active !== false && linkedin.url && (
                <a
                  href={linkedin.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  aria-label="LinkedIn"
                  className="w-8.5 h-8.5 rounded-full bg-white/85 backdrop-blur-xs border border-[#E7D1CC] shadow-xs flex items-center justify-center text-[#2E211E] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:scale-108 transition-all active:scale-95"
                >
                  <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                    <path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z" />
                  </svg>
                </a>
              )}
            </div>
          </div>

          {/* Right Columns Grid: Quick Links, Help, Contact */}
          {/* Responsive mode: 2 columns for Quick Links & Help side-by-side, Contact below */}
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-x-6 sm:gap-x-10 gap-y-8 sm:gap-y-10 lg:gap-16 w-full flex-1">
            
            {/* Column 1: Quick Links */}
            <div className="space-y-3.5">
              <h4 className="font-bold text-sm text-[#2E211E]">
                <span className="border-b-2 border-[#913638] pb-1 inline-block">
                  {col1Title}
                </span>
              </h4>
              <ul className="space-y-2 text-xs sm:text-sm text-[#5C4D49] font-medium pt-1">
                {col1Links.map((link, idx) => {
                  const href = normalizeInternalLink(link.url || link.href || "/");
                  const label = link.label || link.name || "Link";
                  return (
                    <li key={idx}>
                      <Link href={href} prefetch={true} className="hover:text-[#913638] transition-colors">
                        {label}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>

            {/* Column 2: Help */}
            <div className="space-y-3.5">
              <h4 className="font-bold text-sm text-[#2E211E]">
                <span className="border-b-2 border-[#913638] pb-1 inline-block">
                  {col2Title}
                </span>
              </h4>
              <ul className="space-y-2 text-xs sm:text-sm text-[#5C4D49] font-medium pt-1">
                {col2Links.map((link, idx) => {
                  const href = normalizeInternalLink(link.url || "/contact");
                  const label = link.label || "Help";
                  return (
                    <li key={idx}>
                      <Link href={href} prefetch={true} className="hover:text-[#913638] transition-colors">
                        {label}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>

            {/* Column 3: Contact */}
            <div className="col-span-2 sm:col-span-1 space-y-3.5 pt-1 sm:pt-0">
              <h4 className="font-bold text-sm text-[#2E211E]">
                <span className="border-b-2 border-[#913638] pb-1 inline-block">
                  {col3Title}
                </span>
              </h4>
              <div className="space-y-3 text-xs sm:text-sm text-[#5C4D49] font-medium pt-1">
                <p className="flex items-center gap-2.5">
                  <Phone className="w-4 h-4 text-[#2E211E] shrink-0" />
                  <a href={contactPhoneLink} className="hover:text-[#913638] transition-colors font-semibold text-[#2E211E]">
                    {contactPhone}
                  </a>
                </p>
                <p className="flex items-center gap-2.5">
                  <Mail className="w-4 h-4 text-[#2E211E] shrink-0" />
                  <a href={contactEmailLink} className="hover:text-[#913638] transition-colors font-semibold text-[#2E211E]">
                    {contactEmail}
                  </a>
                </p>
                <p className="flex items-center gap-2.5">
                  <MapPin className="w-4 h-4 text-[#2E211E] shrink-0" />
                  <span className="font-semibold text-[#2E211E]">{contactAddress}</span>
                </p>
              </div>
            </div>

          </div>

        </div>

        {/* Bottom Bar */}
        <div className="pt-6 border-t border-[#E7D1CC]/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-[#5C4D49] font-medium">
          <p>{copyrightText}</p>

          <p className="flex items-center gap-1.5">
            <span>{heartTagline}</span>
          </p>
        </div>

      </div>
    </footer>
  );
}



