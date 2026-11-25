import React from "react";
import { fetchCategories } from "@/lib/api";
import { CATEGORIES } from "@/data/categories";
import CategoryPageView from "./CategoryPageView";

export const dynamicParams = false;

interface CategoryPageProps {
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
    if (Array.isArray(dbSlugs?.categories)) {
      dbSlugs.categories.forEach((s: string) => {
        if (s) allSlugs.add(s);
      });
    }
  } catch {}

  // 2. Slugs from live API if reachable
  try {
    const cats = await fetchCategories();
    if (cats && cats.length > 0) {
      cats.forEach((c) => {
        if (c.slug) allSlugs.add(c.slug);
      });
    }
  } catch {}

  // 3. Slugs from static categories
  CATEGORIES.forEach((category) => {
    if (category.slug) allSlugs.add(category.slug);
  });

  // 4. Critical guaranteed active database categories
  allSlugs.add("combo-packs");
  allSlugs.add("keychain");
  allSlugs.add("flower");
  allSlugs.add("cup-tea-coffee");
  allSlugs.add("hair-accessories");
  allSlugs.add("clothing");

  return Array.from(allSlugs)
    .filter(Boolean)
    .map((slug) => ({ slug }));
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = await params;
  return <CategoryPageView slug={slug} />;
}
