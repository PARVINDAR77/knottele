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

  // 3. Slugs from local PRODUCTS static array
  PRODUCTS.forEach((product) => {
    if (product.slug) allSlugs.add(product.slug);
  });

  // 4. Critical guaranteed aliases
  allSlugs.add("cute-bunny-amigurumi-keychain");
  allSlugs.add("cute-bunny-keychain");

  return Array.from(allSlugs)
    .filter(Boolean)
    .map((slug) => ({ slug }));
}

export default async function ProductDetailPage({ params }: ProductPageProps) {
  const { slug } = await params;
  return <ProductDetailView slug={slug} />;
}

