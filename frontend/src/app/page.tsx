import { Hero } from "@/components/home/Hero";
import { FeatureBar } from "@/components/home/FeatureBar";
import { CategoryGrid } from "@/components/home/CategoryGrid";
import { BestSellers } from "@/components/home/BestSellers";
import { BrandStory } from "@/components/home/BrandStory";
import { Testimonials } from "@/components/home/Testimonials";
import { BlogReels } from "@/components/home/BlogReels";
import { Newsletter } from "@/components/home/Newsletter";

export default function HomePage() {
  return (
    <div className="flex flex-col min-h-screen">
      {/* SECTION 1 — HERO */}
      <Hero />

      {/* SECTION 2 — FEATURE BAR */}
      <FeatureBar />

      {/* SECTION 3 — SHOP BY CATEGORY */}
      <CategoryGrid />

      {/* SECTION 4 — BEST SELLERS */}
      <BestSellers />

      {/* SECTION 5 — BRAND STORY */}
      <BrandStory />

      {/* SECTION 6 — TESTIMONIALS */}
      <Testimonials />

      {/* SECTION 7 — BLOG & REELS */}
      <BlogReels />

      {/* SECTION 8 — NEWSLETTER */}
      <Newsletter />
    </div>
  );
}
