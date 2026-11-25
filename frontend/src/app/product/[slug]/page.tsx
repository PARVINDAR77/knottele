import React from "react";
import { PRODUCTS } from "@/data/products";
import { fetchProducts } from "@/lib/api";
import ProductDetailView from "./ProductDetailView";

export const dynamicParams = false;

interface ProductPageProps {
  params: Promise<{
    slug: string;
  }>;
}

export async function generateStaticParams() {
  const allSlugs = new Set<string>();

  // 1. Slugs from local db-slugs.json (exported by deploy.sh / artisan)
  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    const dbSlugs = require("@/data/db-slugs.json");
    if (Array.isArray(dbSlugs?.products)) {
      dbSlugs.products.forEach((s: string) => {
        if (s) allSlugs.add(s);
      });
    }
  } catch { }

  // 2. Slugs from live API if reachable
  try {
    const res = await fetchProducts({ per_page: 500 });
    res?.products?.forEach((p) => {
      if (p.slug) allSlugs.add(p.slug);
    });
  } catch (error) {
    console.warn("generateStaticParams for products API warning:", error);
  }

  // 3. Critical guaranteed live database products
  allSlugs.add("rose-keychain");
  allSlugs.add("cute-bunny-keychain");
  allSlugs.add("cute-bunny-amigurumi-keychain");
  allSlugs.add("crochet-gift-box");
  allSlugs.add("red-love-crochet-gift-box");
  allSlugs.add("rose-pot");
  allSlugs.add("sun-flower-pot");
  allSlugs.add("tulip-pot");
  allSlugs.add("coaster");
  allSlugs.add("bandana");
  allSlugs.add("head-band");
  allSlugs.add("scrunchies");
  allSlugs.add("bow-hair-band");
  allSlugs.add("sun-flower-hair-band");
  allSlugs.add("rose-hair-band");
  allSlugs.add("marigold");
  allSlugs.add("clips");
  allSlugs.add("evil-eye-key-chain");
  allSlugs.add("white-daisy-key-chain");
  allSlugs.add("sun-flower-key-chain");
  allSlugs.add("heart-keychain");
  allSlugs.add("nistha-1");

  return Array.from(allSlugs)
    .filter(Boolean)
    .map((slug) => ({ slug }));
}

export default async function ProductDetailPage({ params }: ProductPageProps) {
  const { slug } = await params;
  return <ProductDetailView slug={slug} />;
}

