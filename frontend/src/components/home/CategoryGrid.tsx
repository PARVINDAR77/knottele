"use client";

import React from "react";
import Image from "next/image";
import Link from "next/link";
import { Gift } from "lucide-react";
import { useWebsiteMedia } from "@/context/MediaContext";

export function CategoryGrid() {
  const { media } = useWebsiteMedia();
  const categories = media?.categories && media.categories.length > 0 ? media.categories : [];
  const categorySection = media?.categorySection;

  const sectionTitle = categorySection?.title || "Shop by Category";
  const sectionSubtitle = categorySection?.subtitle || "Explore our wide range of handmade crochet products.";
  const tagText = categorySection?.tag_text || "🌸";
  const isActive = categorySection?.is_active !== false;

  if (!isActive || categories.length === 0) {
    return null;
  }

  return (
    <section className="py-14 sm:py-18 bg-[#FFF9F6]">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Dynamic Header */}
        <div className="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
          <div className="flex items-center justify-center gap-2 mb-1.5">
            <span className="h-px w-10 bg-[#E7D1CC]" />
            <span className="text-xs text-[#913638]">{tagText}</span>
            <h2 className="font-serif-luxury text-2xl sm:text-3xl lg:text-4xl font-bold text-[#2E211E] tracking-tight">
              {sectionTitle}
            </h2>
            <span className="text-xs text-[#913638]">{tagText}</span>
            <span className="h-px w-10 bg-[#E7D1CC]" />
          </div>
          {sectionSubtitle && (
            <p className="text-xs sm:text-sm text-[#786864]">
              {sectionSubtitle}
            </p>
          )}
        </div>

        {/* Dynamic Category Grid */}
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4 md:gap-6">
          {categories.map((category) => (
            <Link
              key={category.id || category.slug}
              href={`/category/${category.slug}`}
              prefetch={false}
              className="group flex flex-col items-center text-center p-2 sm:p-3 rounded-2xl transition-all duration-300 transform hover:-translate-y-1"
            >
              {/* Soft Pink Circular Image Container */}
              <div className="relative w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-full overflow-hidden bg-[#FCE9E5] border-2 border-[#E7D1CC]/80 group-hover:border-[#913638] shadow-xs group-hover:shadow-md transition-all p-1 mb-2">
                <div className="relative w-full h-full rounded-full overflow-hidden">
                  <Image
                    src={category.image || "/images/logo/Logo_1.png"}
                    alt={category.name}
                    fill
                    sizes="(max-width: 640px) 80px, (max-width: 1024px) 110px, 140px"
                    className="object-cover group-hover:scale-110 transition-transform duration-500"
                  />
                </div>
              </div>

              {/* Title & Item Count / Price */}
              <h3 className="font-serif-luxury text-xs sm:text-sm md:text-base font-bold text-[#2E211E] group-hover:text-[#913638] transition-colors line-clamp-1">
                {category.name}
              </h3>

              <span className="text-[10px] sm:text-[11px] text-[#786864] group-hover:text-[#913638] font-medium mt-0.5 transition-colors">
                {category.priceFrom || (category.itemCount > 0 ? `${category.itemCount} items` : "Explore →")}
              </span>
            </Link>
          ))}

          {/* Explore All Card */}
          <Link
            href="/shop"
            className="group flex flex-col items-center text-center p-2 sm:p-3 rounded-2xl transition-all duration-300 transform hover:-translate-y-1"
          >
            <div className="relative w-20 h-20 sm:w-24 sm:h-24 md:w-28 md:h-28 rounded-full bg-[#FCE9E5] border-2 border-[#E7D1CC]/80 group-hover:border-[#913638] shadow-xs group-hover:shadow-md flex items-center justify-center text-[#913638] transition-all mb-2">
              <Gift className="w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 stroke-[1.3] group-hover:scale-110 transition-transform" />
            </div>

            <h3 className="font-serif-luxury text-xs sm:text-sm md:text-base font-bold text-[#2E211E] group-hover:text-[#913638] transition-colors">
              More
            </h3>

            <span className="text-[10px] sm:text-[11px] text-[#786864] group-hover:text-[#913638] font-medium mt-0.5 transition-colors">
              Explore All →
            </span>
          </Link>
        </div>

      </div>
    </section>
  );
}
