import { Hero } from "@/components/home/Hero";
import { FeatureBar } from "@/components/home/FeatureBar";
import { CategoryGrid } from "@/components/home/CategoryGrid";
import { CustomBanner } from "@/components/home/CustomBanner";
import { BestSellers } from "@/components/home/BestSellers";
import { BrandStory } from "@/components/home/BrandStory";
import { CustomOrderCTA } from "@/components/home/CustomOrderCTA";
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

      {/* SECTION 5 — CUSTOM CROCHET BANNER */}
      <CustomBanner />

      {/* SECTION 6 — BRAND STORY */}
      <BrandStory />

      {/* SECTION 7 — CUSTOM ORDER CTA */}
      <CustomOrderCTA />

      {/* SECTION 8 — TESTIMONIALS */}
      <Testimonials />

      {/* SECTION 9 — BLOG & REELS */}
      <BlogReels />

      {/* SECTION 10 — NEWSLETTER */}
      <Newsletter />
    </div>
  );
}
