/**
 * KNOTELLE API Client
 * Provides seamless connection between Next.js frontend and Laravel backend.
 */

import { Product } from "@/types/product";

export function normalizeInternalLink(url?: string | null): string {
  if (!url) return "/";
  let clean = url.trim();
  if (
    clean.startsWith("mailto:") ||
    clean.startsWith("tel:") ||
    clean.startsWith("#") ||
    clean.startsWith("javascript:")
  ) {
    return clean;
  }

  // Extract path if url is a full absolute URL (e.g. http://187.127.158.24/shop or https://knotelle.com/shop)
  try {
    if (clean.startsWith("http://") || clean.startsWith("https://")) {
      const parsed = new URL(clean);
      clean = parsed.pathname + parsed.search + parsed.hash;
    }
  } catch {}

  let path = clean.startsWith("/") ? clean : `/${clean}`;
  // Strip /knottele prefix so Next.js <Link> doesn't duplicate the basePath
  if (path.startsWith("/knottele")) {
    path = path.replace(/^\/knottele(?:\/|$)/, "/");
  }

  // Ensure trailing slash for paths without file extension, query, or hash
  // to align with Next.js trailingSlash: true export structure
  if (
    path &&
    !path.includes("?") &&
    !path.includes("#") &&
    !/\.[a-zA-Z0-9]+$/.test(path) &&
    !path.endsWith("/")
  ) {
    path = `${path}/`;
  }

  return path || "/";
}

export function isRouteActive(currentPath?: string | null, targetHref?: string | null): boolean {
  if (!currentPath || !targetHref) return false;
  const norm = (p: string) => {
    let clean = p.startsWith("/knottele") ? p.replace(/^\/knottele(?:\/|$)/, "/") : p;
    clean = clean.split("?")[0].split("#")[0].trim();
    const withoutSlash = clean.replace(/\/+$/, "");
    return withoutSlash === "" ? "/" : withoutSlash;
  };
  return norm(currentPath) === norm(targetHref);
}

export function getFullPath(path: string): string {
  let clean = path.startsWith("/") ? path : `/${path}`;
  if (clean.startsWith("/knottele")) {
    clean = clean.replace(/^\/knottele(?:\/|$)/, "/");
  }
  return clean || "/";
}

export function getBrowserApiBaseUrl(): string {
  if (typeof window !== "undefined") {
    const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";

    // Only connect to loopback port 8000 if developing locally on port 3000
    if (isLocalhost && window.location.port === "3000") {
      return "http://127.0.0.1:8000/api";
    }

    // On remote production server, always use standard /api
    return "/api";
  }

  // Fallback
  return "/api";
}

export function getServerApiBaseUrl(): string {
  if (process.env.INTERNAL_API_URL) {
    return process.env.INTERNAL_API_URL;
  }
  if (process.env.NODE_ENV === "production") {
    return "http://127.0.0.1/api";
  }
  return "http://127.0.0.1:8000/api";
}

// ============================================================================
// PERFORMANCE: In-Memory Client Cache & Promise Deduplicator
// Prevents duplicate concurrent fetches and eliminates server latency on page navigation
// ============================================================================
interface ApiCacheEntry<T> {
  data: T;
  timestamp: number;
}
const clientApiCache = new Map<string, ApiCacheEntry<any>>();
const inFlightRequests = new Map<string, Promise<any>>();

export function getCachedOrFetch<T>(
  key: string,
  fetcher: () => Promise<T>,
  ttlMs: number = 60000
): Promise<T> {
  if (typeof window === "undefined") {
    return fetcher();
  }

  // 1. Check valid in-memory cache
  const cached = clientApiCache.get(key);
  if (cached && Date.now() - cached.timestamp < ttlMs) {
    return Promise.resolve(cached.data);
  }

  // 2. Deduplicate in-flight requests (returns identical promise if already requested)
  if (inFlightRequests.has(key)) {
    return inFlightRequests.get(key)!;
  }

  const promise = fetcher()
    .then((data) => {
      if (data !== null && data !== undefined) {
        clientApiCache.set(key, { data, timestamp: Date.now() });
      }
      return data;
    })
    .finally(() => {
      inFlightRequests.delete(key);
    });

  inFlightRequests.set(key, promise);
  return promise;
}

export function clearClientApiCache(prefix?: string) {
  if (!prefix) {
    clientApiCache.clear();
  } else {
    for (const k of clientApiCache.keys()) {
      if (k.startsWith(prefix)) {
        clientApiCache.delete(k);
      }
    }
  }
}

export const getApiBaseUrl = (): string => {
  if (typeof window !== "undefined") {
    return getBrowserApiBaseUrl();
  }
  return getServerApiBaseUrl();
};

export interface ContactFormData {
  name: string;
  email: string;
  phone?: string;
  subject?: string;
  message: string;
}

export interface ContactSettingsData {
  studio_name?: string;
  studio_badge?: string;
  address_line_1: string;
  address_line_2?: string;
  phone: string;
  hours: string;
  email: string;
  response_time: string;
}

export interface ApiCategory {
  id: string;
  db_id?: number;
  name: string;
  slug: string;
  description: string;
  image: string;
  featured: boolean;
  priceFrom: string;
  itemCount: number;
}

export interface HomepageMedia {
  hero: {
    desktop: string;
    mobile: string;
    slides: Array<{
      id: number;
      title: string;
      tagline?: string;
      subtitle?: string;
      description?: string;
      primary_button_text?: string;
      primary_button_link?: string;
      secondary_button_text?: string;
      secondary_button_link?: string;
      cta_text?: string;
      cta_link?: string;
      secondary_cta_text?: string;
      secondary_cta_link?: string;
      tag_text?: string;
      alt: string;
      desktop: string;
      desktop_image?: string;
      mobile: string;
      mobile_image?: string;
      status?: boolean;
      is_active?: boolean;
      sort_order: number;
    }>;
  };
  categories: ApiCategory[];
  categorySection?: {
    tag_text?: string;
    title?: string;
    subtitle?: string;
    description?: string;
    is_active?: boolean;
  };
  customCrochet: {
    desktop: string;
    mobile: string;
    title?: string;
    subtitle?: string;
    description?: string;
    cta_text?: string;
    cta_link?: string;
    tag_text?: string;
    tag_active?: boolean;
    alt_text?: string;
    is_active?: boolean;
  };
  brandStory: {
    desktop: string;
    mobile: string;
    badge?: string;
    title?: string;
    subtitle?: string;
    description?: string;
    cta_text?: string;
    cta_link?: string;
    features?: Array<{ title: string; icon?: string }>;
    is_active?: boolean;
  };
  customOrder: {
    desktop: string | null;
    mobile: string | null;
    badge?: string;
    title_line1?: string;
    title_line2?: string;
    description?: string;
    cta_text?: string;
    cta_link?: string;
    is_active?: boolean;
  };
  newsletter: {
    bg: string | null;
    left: string | null;
    right: string | null;
    title?: string;
    subtitle?: string;
  };
  footer: {
    bg: string;
    image?: string;
    left?: string | null;
    right?: string | null;
    title?: string;
    subtitle?: string;
    tagline?: string;
    copyright_text?: string;
    heart_tagline?: string;
    social?: {
      instagram?: { url: string; is_active: boolean };
      facebook?: { url: string; is_active: boolean };
      pinterest?: { url: string; is_active: boolean };
      youtube?: { url: string; is_active: boolean };
      twitter?: { url: string; is_active: boolean };
      linkedin?: { url: string; is_active: boolean };
    };
    column_1?: {
      title: string;
      links: Array<{ label?: string; name?: string; url?: string; href?: string; is_active?: boolean }>;
    };
    column_2?: {
      title: string;
      links: Array<{ label?: string; name?: string; url?: string; href?: string; is_active?: boolean }>;
    };
    column_3?: {
      title: string;
      phone?: string;
      phone_link?: string;
      email?: string;
      email_link?: string;
      address?: string;
      address_link?: string;
    };
    is_active?: boolean;
  };
  navbar?: {
    logo?: string;
    announcement?: {
      text: string;
      link?: string;
      is_active: boolean;
    };
    nav_links?: Array<{
      name: string;
      href: string;
      is_highlighted?: boolean;
      is_active?: boolean;
      sort_order?: number;
    }>;
    actions?: {
      show_search?: boolean;
      show_wishlist?: boolean;
      show_account?: boolean;
      show_cart?: boolean;
    };
    is_active?: boolean;
  };
  global: {
    logo: string;
    mobileLogo: string;
    favicon: string;
    defaultProduct: string;
    defaultCategory: string;
    store_name?: string;
    store_email?: string;
    store_phone?: string;
    store_phone_alt?: string;
    store_address?: string;
    currency?: string;
    theme_color?: string;
    default_shipping_rate?: number;
    free_shipping_min?: number;
    tax_rate?: number;
    cod_enabled?: boolean;
    upi_qr_enabled?: boolean;
    upi_id?: string;
    upi_account_name?: string;
    upi_qr_image?: string;
    upi_timer_minutes?: number;
    upi_instructions?: string;
  };
  settings?: Record<string, any>;
  shop?: {
    banner?: {
      desktop?: string | null;
      mobile?: string | null;
      title?: string;
      subtitle?: string;
      tag_text?: string;
      cta_text?: string | null;
      cta_link?: string | null;
      is_active?: boolean;
    };
    promo?: {
      banner?: string | null;
      title?: string;
      subtitle?: string;
      cta_text?: string;
      cta_link?: string;
      is_active?: boolean;
    };
  };
  about?: {
    hero?: {
      image?: string;
      title?: string;
      subtitle?: string;
      description?: string;
      badge?: string;
      cta_text?: string;
      cta_link?: string;
      is_active?: boolean;
    };
    studio?: {
      image?: string | null;
      title?: string;
      subtitle?: string;
    };
  };
  contact?: {
    banner?: {
      image?: string | null;
      title?: string;
      subtitle?: string;
      tag_text?: string;
    };
    studio?: {
      image?: string | null;
      title?: string;
      subtitle?: string;
    };
  };
  blogReels?: {
    title?: string;
    subtitle?: string;
    tag_text?: string;
    cta_text?: string;
    cta_link?: string;
    is_active?: boolean;
    items: Array<{
      id: string;
      db_id?: number;
      title: string;
      subtitle: string;
      description?: string;
      caption?: string;
      content_type: "reel" | "video" | "blog_video" | string;
      category: string;
      thumbnail: string;
      mobile_thumbnail?: string;
      video_url: string;
      videoUrl?: string;
      duration: string;
      audio_name?: string;
      audio_track?: string;
      audioTrack?: string;
      likes: string | number;
      likes_count?: number;
      comments: string | number;
      comments_count?: number;
      views: string | number;
      views_count?: number;
      is_featured?: boolean;
      sort_order?: number;
      author?: {
        name: string;
        role: string;
        avatar: string;
      };
      tags?: string[];
    }>;
  };
  testimonials?: Array<{
    id: number | string;
    name: string;
    designation?: string;
    message: string;
    rating: number;
    image?: string;
    is_active?: boolean;
  }>;
  customOrderItems?: Array<{
    id: number;
    name: string;
    title?: string;
    subtitle?: string;
    icon?: string;
    tag_text?: string;
    sort_order?: number;
    is_active?: boolean;
  }>;
}

const STATIC_PRODUCT_IMAGES = new Set([
  "bunny-keychain.jpg",
  "sunflower-stem.jpg",
  "rose-bouquet.jpg",
  "teddy-bear.jpg",
  "daisy-phone-cover.jpg",
  "granny-square-bag.jpg",
  "strawberry-coin-purse.jpg",
  "tulip-mug-cozy.jpg",
  "sprout-bookmark.jpg",
  "floral-scrunchies.jpg",
  "crochet-vest.jpg",
  "potted-tulips.jpg",
]);

export function normalizeImageUrl(url?: string | null, fallback = "/images/products/bunny-keychain.jpg"): string {
  if (!url) return fallback;

  let cleanUrl = url.trim();

  // If a .json file was mistakenly passed as an image URL (e.g. footer_settings.json), immediately use fallback
  const pathWithoutQuery = cleanUrl.split("?")[0].toLowerCase();
  if (pathWithoutQuery.endsWith(".json")) {
    return fallback;
  }

  // Strip localhost:8000 and 127.0.0.1:8000 so assets load from the host server
  cleanUrl = cleanUrl.replace(/^https?:\/\/(127\.0\.0\.1|localhost):8000/, "");

  const filename = cleanUrl.split("/").pop()?.split("?")[0] || "";

  // If it's one of the 12 bundled static images, serve relative from Next.js public/images/products
  if (STATIC_PRODUCT_IMAGES.has(filename)) {
    return `/images/products/${filename}`;
  }

  // If it's an HTTP/HTTPS URL from current host or live VPS, convert to same-origin path to avoid mixed content / SSL cert errors
  try {
    if (cleanUrl.startsWith("http://") || cleanUrl.startsWith("https://")) {
      const parsed = new URL(cleanUrl);
      const isSameHost = typeof window !== "undefined"
        ? (parsed.hostname === window.location.hostname || parsed.hostname === "187.127.158.24" || parsed.hostname === "localhost" || parsed.hostname === "127.0.0.1")
        : (parsed.hostname === "187.127.158.24" || parsed.hostname === "localhost" || parsed.hostname === "127.0.0.1");

      if (isSameHost) {
        cleanUrl = parsed.pathname + parsed.search;
      } else {
        return cleanUrl;
      }
    }
  } catch {}

  // If it's another external HTTP/HTTPS URL (CDN, Unsplash, Cloudinary, etc.)
  if (cleanUrl.startsWith("http://") || cleanUrl.startsWith("https://") || cleanUrl.startsWith("data:")) {
    return cleanUrl;
  }

  return cleanUrl.startsWith("/") ? cleanUrl : `/${cleanUrl}`;
}

export interface ApiProduct {
  id: number | string;
  slug: string;
  name: string;
  short_description?: string;
  description?: string;
  main_image?: string;
  images?: Array<string | { id?: number; url: string; is_primary?: boolean }>;
  price: number;
  compare_price?: number | null;
  tax_rate?: number;
  discount_percent?: number;
  is_in_stock?: boolean;
  stock_quantity?: number;
  sku?: string;
  brand?: string | { id?: number; name: string } | null;
  category?: string | { id?: number; name: string; slug?: string } | null;
  category_id?: number;
  category_slug?: string | null;
  is_featured?: boolean;
  is_new?: boolean;
  is_bestseller?: boolean;
  rating?: number;
  review_count?: number;
  variants_count?: number;
  variants?: Array<{
    id: number;
    sku: string;
    price: number;
    compare_price?: number | null;
    stock_quantity?: number;
    size?: string;
    images?: Array<{ id?: number; url: string }>;
  }>;
  reviews_data?: Array<{
    id: number | string;
    user_name?: string;
    rating?: number;
    review?: string;
    created_at?: string;
  }>;
}

/**
 * Transforms an ApiProduct from Laravel into the standard frontend Product interface.
 */
export function transformApiProductToProduct(apiProduct: ApiProduct): Product {
  const rawMain =
    apiProduct.main_image ||
    (apiProduct.images && apiProduct.images.length > 0
      ? typeof apiProduct.images[0] === "string"
        ? apiProduct.images[0]
        : apiProduct.images[0].url
      : "/images/logo/Logo_1.png");

  const mainImage = normalizeImageUrl(rawMain);

  const rawImages: string[] =
    apiProduct.images && apiProduct.images.length > 0
      ? apiProduct.images.map((img) => (typeof img === "string" ? img : img.url))
      : [rawMain];

  const allImages: string[] = rawImages.map((img) => normalizeImageUrl(img));

  // Extract category name & slug
  const categoryName =
    typeof apiProduct.category === "string"
      ? apiProduct.category
      : apiProduct.category?.name || "Handmade";

  const categorySlug =
    apiProduct.category_slug ||
    (typeof apiProduct.category === "object" ? apiProduct.category?.slug : null) ||
    "all";

  // Build sizes from variants if available
  const sizes =
    apiProduct.variants && apiProduct.variants.length > 0
      ? Array.from(
          new Set(
            apiProduct.variants
              .map((v) => v.size || (v.price ? `Size (₹${v.price})` : "Standard"))
              .filter(Boolean)
          )
        )
      : ["Standard Size"];

  return {
    id: String(apiProduct.id),
    slug: apiProduct.slug,
    name: apiProduct.name,
    category: categoryName,
    categorySlug: categorySlug,
    price: Number(apiProduct.price) || 0,
    comparePrice: apiProduct.compare_price ? Number(apiProduct.compare_price) : undefined,
    images: allImages.length > 0 ? allImages : [mainImage],
    description: apiProduct.description || apiProduct.short_description || "",
    shortDescription: apiProduct.short_description || "",
    materials: [
      "100% Pure Natural Cotton",
      "Hypoallergenic Fiberfill",
      "Artisan Hand-stitched",
    ],
    dimensions: "Standard Artisan Craft Size",
    craftTime: "2-4 hours of meticulous handcrafting",
    colors: [
      { name: "Artisan Natural", hex: "#FFFDF9" },
      { name: "Blush Rose", hex: "#F8C8C8" },
      { name: "Sage Garden", hex: "#D8E2DC" },
      { name: "Dusty Terracotta", hex: "#E8B4B8" },
    ],
    sizes: sizes.length > 0 ? sizes : ["Standard Size"],
    isCustomizable: true,
    isFeatured: Boolean(apiProduct.is_featured),
    isBestSeller: Boolean(apiProduct.is_bestseller),
    isNew: Boolean(apiProduct.is_new),
    rating: apiProduct.rating ? Number(apiProduct.rating) : 4.9,
    reviewCount: apiProduct.review_count ? Number(apiProduct.review_count) : 16,
    stock: apiProduct.stock_quantity ?? 25,
    tags: [categorySlug, "handmade", "crochet", "boutique", "gift"],
    reviews:
      apiProduct.reviews_data && apiProduct.reviews_data.length > 0
        ? apiProduct.reviews_data.map((r) => ({
            id: String(r.id),
            userName: r.user_name || "Boutique Customer",
            rating: Number(r.rating || 5),
            date: r.created_at || "Recently",
            comment: r.review || "Exquisite craftsmanship, wonderful quality!",
            verified: true,
          }))
        : [
            {
              id: "rev-default",
              userName: "Verified Patron",
              rating: 5,
              date: "Recent",
              comment:
                "Exquisite craftsmanship, the stitches are neat and the yarn is delightfully soft!",
              verified: true,
            },
          ],
  };
}

/**
 * Fetch Categories from Laravel Backend API
 */
export async function fetchCategories(): Promise<ApiCategory[]> {
  return getCachedOrFetch("categories_list", async () => {
    try {
      const url = `${getApiBaseUrl()}/customer/categories`;
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
      });
      const result = await response.json();
      if (result.status === "success" && Array.isArray(result.data)) {
        return result.data;
      }
      return [];
    } catch (error) {
      console.warn("Fetch categories network issue:", error);
      return [];
    }
  }, 300000); // 5 minutes in-memory cache with request deduplication
}

/**
 * Fetch Products from Laravel Backend API with dynamic filters
 */
export async function fetchProducts(params?: {
  category_id?: number | string;
  category_slug?: string;
  search?: string;
  min_price?: number;
  max_price?: number;
  is_featured?: boolean;
  is_bestseller?: boolean;
  is_new?: boolean;
  sort_by?: string;
  per_page?: number;
  in_stock?: boolean;
}): Promise<{ products: Product[]; rawApiProducts?: ApiProduct[]; meta?: any; filters?: any }> {
  const query = new URLSearchParams();
  if (params?.category_id) query.set("category_id", String(params.category_id));
  if (params?.search) query.set("search", params.search);
  if (params?.min_price !== undefined) query.set("min_price", String(params.min_price));
  if (params?.max_price !== undefined) query.set("max_price", String(params.max_price));
  if (params?.is_featured !== undefined) query.set("is_featured", String(params.is_featured));
  if (params?.is_bestseller !== undefined) query.set("is_bestseller", String(params.is_bestseller));
  if (params?.is_new !== undefined) query.set("is_new", String(params.is_new));
  if (params?.sort_by) query.set("sort_by", params.sort_by);
  if (params?.per_page) query.set("per_page", String(params.per_page));
  if (params?.in_stock) query.set("in_stock", "true");

  const qs = query.toString();
  const cacheKey = `products_${qs || "default"}`;

  return getCachedOrFetch(cacheKey, async () => {
    try {
      const url = `${getApiBaseUrl()}/customer/products${qs ? `?${qs}` : ""}`;
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
      });

      const result = await response.json();
      if (result.success && result.data && Array.isArray(result.data.products)) {
        const transformed = result.data.products.map(transformApiProductToProduct);
        return {
          products: transformed,
          rawApiProducts: result.data.products,
          meta: result.data.meta,
          filters: result.data.filters,
        };
      }
      return { products: [] };
    } catch (error) {
      console.warn("Fetch products error:", error);
      return { products: [] };
    }
  }, 60000); // 1 minute in-memory cache with request deduplication
}

/**
 * Fetch Single Product by Slug from Laravel Backend API
 */
export async function fetchProductBySlug(slug: string): Promise<Product | null> {
  return getCachedOrFetch(`product_slug_${slug}`, async () => {
    try {
      const url = `${getApiBaseUrl()}/customer/products/${encodeURIComponent(slug)}`;
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
      });
      const result = await response.json();
      if (result.success && result.data) {
        return transformApiProductToProduct(result.data);
      }
      return null;
    } catch (error) {
      console.warn(`Fetch product by slug (${slug}) error:`, error);
      return null;
    }
  }, 300000); // 5 minutes in-memory cache
}

/**
 * Fetch Related Products by Product ID
 */
export async function fetchRelatedProducts(productId: number | string): Promise<Product[]> {
  return getCachedOrFetch(`product_related_${productId}`, async () => {
    try {
      const url = `${getApiBaseUrl()}/customer/products/${productId}/related`;
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
      });
      const result = await response.json();
      if (result.success && Array.isArray(result.data)) {
        return result.data.map(transformApiProductToProduct);
      }
      return [];
    } catch (error) {
      console.warn(`Fetch related products error:`, error);
      return [];
    }
  }, 300000); // 5 minutes in-memory cache
}

/**
 * Fetch Home Page Data (Banners, Categories, Sections, Testimonials)
 */
export async function fetchHomeData(): Promise<any> {
  try {
    const isBrowser = typeof window !== "undefined";
    const isDev = process.env.NODE_ENV === "development";
    const bypassCache = isBrowser || isDev;
    const url = bypassCache
      ? `${getApiBaseUrl()}/customer/home?_t=${Date.now()}`
      : `${getApiBaseUrl()}/customer/home`;

    const response = await fetch(url, {
      headers: { Accept: "application/json" },
      ...(bypassCache ? { cache: "no-store" as const } : { next: { revalidate: 60 } }),
    });
    const result = await response.json();
    if (result.success && result.data) {
      return result.data;
    }
    return null;
  } catch (error) {
    console.warn("Fetch home data error:", error);
    return null;
  }
}

/**
 * Fetch Contact Page Settings from Laravel Backend API
 */
export async function fetchContactSettings(): Promise<ContactSettingsData | null> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/contact/settings?t=${Date.now()}`, {
      headers: {
        Accept: "application/json",
      },
      cache: "no-store",
    });

    if (!response.ok) return null;
    const result = await response.json();
    if (result.status === "success" && result.data) {
      return result.data as ContactSettingsData;
    }
    return null;
  } catch (error) {
    console.warn("Error fetching contact settings:", error);
    return null;
  }
}

/**
 * Submit Contact Form to Laravel Backend API
 */
export async function submitContactForm(
  data: ContactFormData
): Promise<{ success: boolean; message: string }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/contact`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        name: data.name,
        email: data.email,
        phone: data.phone ? data.phone.replace(/\D/g, "").slice(-10) : undefined,
        subject: data.subject || undefined,
        message: data.message,
      }),
    });

    const result = await response.json();
    if (response.ok && (result.status === "success" || result.success === true)) {
      return { success: true, message: result.message || "Message sent successfully." };
    }
    return { success: false, message: result.message || "Failed to submit message." };
  } catch (error) {
    console.warn("Contact API network issue:", error);
    return { success: false, message: "Network error. Please try again." };
  }
}

export async function fetchCustomerOrders(email?: string): Promise<any[]> {
  try {
    let emailToUse = email;
    if (!emailToUse && typeof window !== "undefined") {
      const storedUser = localStorage.getItem("knotelle_customer_user");
      if (storedUser) {
        try {
          const parsed = JSON.parse(storedUser);
          if (parsed.email) emailToUse = parsed.email;
        } catch {}
      }
      if (!emailToUse) {
        emailToUse = localStorage.getItem("knotelle_guest_email") || undefined;
      }
    }
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const url = emailToUse
      ? `${getApiBaseUrl()}/customer/orders?email=${encodeURIComponent(emailToUse)}`
      : `${getApiBaseUrl()}/customer/orders`;
    const headers: Record<string, string> = {
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const response = await fetch(url, {
      headers,
      cache: "no-store",
    });
    const result = await response.json();
    if (result.success && Array.isArray(result.data)) {
      return result.data;
    }
    return [];
  } catch (error) {
    console.warn("Failed to fetch customer orders:", error);
    return [];
  }
}

/**
 * Fetch Single Order by ID or Order Number
 */
export async function fetchOrderById(id: string, email?: string): Promise<any | null> {
  try {
    let emailToUse = email;
    if (!emailToUse && typeof window !== "undefined") {
      const storedUser = localStorage.getItem("knotelle_customer_user");
      if (storedUser) {
        try {
          const parsed = JSON.parse(storedUser);
          if (parsed.email) emailToUse = parsed.email;
        } catch {}
      }
      if (!emailToUse) {
        emailToUse = localStorage.getItem("knotelle_guest_email") || undefined;
      }
    }
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const query = emailToUse ? `?email=${encodeURIComponent(emailToUse)}` : "";
    const url = `${getApiBaseUrl()}/customer/orders/${encodeURIComponent(id)}${query}`;
    const headers: Record<string, string> = {
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const response = await fetch(url, { headers, cache: "no-store" });
    const result = await response.json();
    if (result.success && result.data) {
      return result.data;
    }
    return null;
  } catch (error) {
    console.warn("Failed to fetch order detail by id:", error);
    return null;
  }
}

/**
 * Submit a Custom Crochet Commission Request
 */
export async function submitCustomOrder(formData: FormData): Promise<{ success: boolean; message: string; data?: any; reference_id?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const headers: Record<string, string> = {
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }

    const response = await fetch(`${getApiBaseUrl()}/customer/custom-orders`, {
      method: "POST",
      headers,
      body: formData,
    });

    const result = await response.json();
    if (response.ok && result.success) {
      return {
        success: true,
        message: result.message || "Custom order submitted successfully!",
        data: result.data,
        reference_id: result.reference_id || result.data?.reference_id,
      };
    }
    return {
      success: false,
      message: result.message || "Failed to submit custom order.",
    };
  } catch (error) {
    console.warn("Submit custom order error:", error);
    return {
      success: false,
      message: "Network error. Please try submitting again.",
    };
  }
}

/**
 * Fetch Custom Orders for Customer / Guest Email
 */
export async function fetchCustomerCustomOrders(email?: string): Promise<any[]> {
  try {
    let emailToUse = email;
    if (!emailToUse && typeof window !== "undefined") {
      const storedUser = localStorage.getItem("knotelle_customer_user");
      if (storedUser) {
        try {
          const parsed = JSON.parse(storedUser);
          if (parsed.email) emailToUse = parsed.email;
        } catch {}
      }
      if (!emailToUse) {
        emailToUse = localStorage.getItem("knotelle_guest_email") || undefined;
      }
    }
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const url = emailToUse
      ? `${getApiBaseUrl()}/customer/custom-orders?email=${encodeURIComponent(emailToUse)}`
      : `${getApiBaseUrl()}/customer/custom-orders`;
    const headers: Record<string, string> = {
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const response = await fetch(url, {
      headers,
      cache: "no-store",
    });
    const result = await response.json();
    if (result.success && Array.isArray(result.data)) {
      return result.data;
    }
    return [];
  } catch (error) {
    console.warn("Failed to fetch customer custom orders:", error);
    return [];
  }
}

/**
 * Fetch Single Custom Order by Reference ID or ID
 */
export async function fetchCustomOrderById(referenceOrId: string, email?: string): Promise<any | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const query = email ? `?email=${encodeURIComponent(email)}` : "";
    const url = `${getApiBaseUrl()}/customer/custom-orders/${encodeURIComponent(referenceOrId)}${query}`;
    const headers: Record<string, string> = {
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const response = await fetch(url, {
      headers,
      cache: "no-store",
    });
    const result = await response.json();
    if (result.success && result.data) {
      return result.data;
    }
    return null;
  } catch (error) {
    console.warn("Failed to fetch custom order details:", error);
    return null;
  }
}

/**
 * Fetch Active Offers/Coupons from Laravel Backend API
 */
export async function fetchActiveOffers(subtotal?: number): Promise<Array<{
  id: number;
  name: string;
  code: string;
  offer_type: string;
  discount_value: string | number;
  min_cart_amount?: string | number | null;
  max_cart_amount?: string | number | null;
  starts_at?: string | null;
  ends_at?: string | null;
}>> {
  try {
    const subtotalParam = subtotal != null && !isNaN(subtotal) ? `&subtotal=${encodeURIComponent(subtotal)}` : "";
    const url = `${getApiBaseUrl()}/customer/offers/active?_t=${Date.now()}${subtotalParam}`;
    const response = await fetch(url, {
      headers: { Accept: "application/json" },
      cache: "no-store",
    });
    const result = await response.json();
    if (result.success && Array.isArray(result.data)) {
      return result.data;
    }
    return [];
  } catch (error) {
    console.warn("Failed to fetch active offers:", error);
    return [];
  }
}

/**
 * Validate an Offer/Coupon Code against Subtotal with Backend API
 */
export async function validateOfferCode(code: string, subtotal: number): Promise<{
  success: boolean;
  message?: string;
  discount_amount?: number;
  offer?: any;
}> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/offers/validate`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ code: code.trim().toUpperCase(), subtotal }),
    });
    const result = await response.json();
    if (response.ok && result.success && result.data) {
      return {
        success: true,
        discount_amount: result.data.discount_amount,
        offer: result.data.offer,
        message: result.message,
      };
    }
    return {
      success: false,
      message: result.message || "Invalid or expired coupon code.",
    };
  } catch (error) {
    console.warn("Validate offer error:", error);
    return {
      success: false,
      message: "Connection error validating offer code.",
    };
  }
}

/**
 * Submit Order to Laravel Backend API
 */
export async function submitOrder(
  orderPayload: Record<string, unknown>
): Promise<{ success: boolean; orderId?: string; orderNumber?: string; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const headers: Record<string, string> = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };
    if (token) {
      headers["Authorization"] = `Bearer ${token}`;
    }
    const response = await fetch(`${getApiBaseUrl()}/customer/orders`, {
      method: "POST",
      headers,
      body: JSON.stringify(orderPayload),
    });
    const result = await response.json();
    if (response.ok && result.success) {
      return {
        success: true,
        orderId: result.data?.order_id,
        orderNumber: result.data?.order_number,
        message: result.message,
      };
    }
    return { success: false, message: result.message || "Order placement failed." };
  } catch (error) {
    console.warn("Order submit error:", error);
    return { success: false, message: "Connection error" };
  }
}

/**
 * Register Customer to Laravel Backend API
 */

/**
 * Create Razorpay Order from Backend API
 * @param amountInPaise Amount in paise (minimum 100 paise = ₹1.00)
 * @param currency Currency code (default: INR)
 * @param receipt Optional receipt identifier
 */
export async function createRazorpayOrder(amountInPaise: number, currency: string = "INR", receipt?: string): Promise<{
  success: boolean;
  order_id?: string;
  id?: string;
  key_id?: string;
  amount?: number;
  currency?: string;
  message?: string;
}> {
  try {
    const validPaise = Math.max(100, Math.round(amountInPaise));

    let res = await fetch(`${getApiBaseUrl()}/customer/payment/razorpay/order`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ amount: validPaise, currency, receipt }),
    });

    if (res.status === 404) {
      res = await fetch(`${getApiBaseUrl()}/create-order`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ amount: validPaise, currency, receipt }),
      });
    }

    const text = await res.text();
    let data: any = {};
    try {
      data = JSON.parse(text);
    } catch {
      return { success: false, message: `Server error (${res.status})` };
    }
    return data;
  } catch (err) {
    console.error("Create Razorpay Order error:", err);
    return { success: false, message: "Network error creating Razorpay order." };
  }
}

/**
 * Verify Razorpay Payment Signature with Backend API
 */
export async function verifyRazorpayPayment(payload: {
  razorpay_payment_id: string;
  razorpay_order_id: string;
  razorpay_signature: string;
}): Promise<{ success: boolean; message?: string }> {
  try {
    let res = await fetch(`${getApiBaseUrl()}/customer/payment/razorpay/verify`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });

    if (res.status === 404) {
      res = await fetch(`${getApiBaseUrl()}/verify-payment`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify(payload),
      });
    }

    const text = await res.text();
    let data: any = {};
    try {
      data = JSON.parse(text);
    } catch {
      return { success: false, message: `Server error (${res.status})` };
    }
    return data;
  } catch (err) {
    console.error("Verify Razorpay Payment error:", err);
    return { success: false, message: "Network error verifying Razorpay payment." };
  }
}

export async function registerCustomer(payload: {
  name: string;
  email: string;
  password: string;
  password_confirmation?: string;
  mobile?: string;
}): Promise<{ success: boolean; token?: string; user?: any; message?: string; email?: string; requires_otp?: boolean }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/register`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        ...payload,
        password_confirmation: payload.password_confirmation || payload.password,
      }),
    });
    const text = await response.text();
    let result: any = {};
    try {
      result = JSON.parse(text);
    } catch {
      return {
        success: false,
        message: response.status === 504 ? "Server timeout. Please try again." : `Server error (${response.status}). Please try again.`,
      };
    }
    if (response.ok && result.success) {
      return {
        success: true,
        token: result.token,
        user: result.user,
        email: result.email,
        requires_otp: Boolean(result.requires_otp),
        message: result.message || "Registration successful!",
      };
    }
    return {
      success: false,
      message: result.message || (result.errors ? Object.values(result.errors).flat().join(", ") : "Registration failed."),
    };
  } catch (error) {
    console.warn("Register customer error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Verify Customer Email OTP
 */
export async function verifyCustomerOtp(payload: {
  email: string;
  otp: string;
}): Promise<{ success: boolean; token?: string; user?: any; message?: string }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/verify-otp`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const text = await response.text();
    let result: any = {};
    try {
      result = JSON.parse(text);
    } catch {
      return {
        success: false,
        message: response.status === 504 ? "Server timeout. Please try again." : `Server error (${response.status}). Please try again.`,
      };
    }
    if (response.ok && result.success) {
      return {
        success: true,
        token: result.token,
        user: result.user,
        message: result.message || "Email verified successfully!",
      };
    }
    return {
      success: false,
      message: result.message || (result.errors ? Object.values(result.errors).flat().join(", ") : "Verification failed."),
    };
  } catch (error) {
    console.warn("Verify OTP error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Resend Customer Email OTP
 */
export async function resendCustomerOtp(payload: {
  email: string;
}): Promise<{ success: boolean; message?: string; cooldown_remaining?: number }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/resend-otp`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (response.ok && result.success) {
      return {
        success: true,
        message: result.message || "Verification code sent!",
      };
    }
    return {
      success: false,
      message: result.message || "Failed to resend verification code.",
      cooldown_remaining: result.cooldown_remaining,
    };
  } catch (error) {
    console.warn("Resend OTP error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Safely sanitize internal redirect URL to prevent open redirect vulnerabilities.
 */
export function sanitizeRedirectUrl(url: string | null | undefined): string {
  if (!url) return "/account/";
  const trimmed = url.trim();
  if (
    trimmed.startsWith("/") &&
    !trimmed.startsWith("//") &&
    !trimmed.startsWith("/\\") &&
    !trimmed.includes(":\\") &&
    !trimmed.includes("://") &&
    !trimmed.toLowerCase().includes("javascript:")
  ) {
    return trimmed;
  }
  return "/account/";
}


/**
 * Login Customer to Laravel Backend API
 */
export async function loginCustomer(payload: {
  email: string;
  password: string;
}): Promise<{ success: boolean; token?: string; user?: any; message?: string }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const text = await response.text();
    let result: any = {};
    try {
      result = JSON.parse(text);
    } catch {
      return {
        success: false,
        message: response.status === 504 ? "Server timeout. Please try again." : `Server error (${response.status}). Please try again.`,
      };
    }
    if (response.ok && result.success) {
      return {
        success: true,
        token: result.token,
        user: result.user,
        message: result.message || "Login successful!",
      };
    }
    return {
      success: false,
      message: result.message || "Invalid email or password.",
    };
  } catch (error) {
    console.warn("Login customer error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Logout Customer from Laravel Backend API
 */
export async function logoutCustomer(): Promise<{ success: boolean }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (token) {
      await fetch(`${getApiBaseUrl()}/customer/logout`, {
        method: "POST",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
      });
    }
    return { success: true };
  } catch (err) {
    console.warn("Logout API error:", err);
    return { success: true };
  }
}

/**
 * Fetch Current Customer Profile from Laravel Backend API
 */
export async function fetchCustomerProfile(): Promise<{ success: boolean; user?: any; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return { success: false, message: "No token" };

    const response = await fetch(`${getApiBaseUrl()}/customer/profile?_t=${Date.now()}`, {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });
    const result = await response.json();
    if (response.ok && result.success && result.user) {
      return {
        success: true,
        user: result.user,
      };
    }
    return {
      success: false,
      message: result.message || "Unauthenticated.",
    };
  } catch (error) {
    console.warn("Fetch customer profile error:", error);
    return { success: false, message: "Connection error." };
  }
}

/**
 * Request Password Reset Email/Code
 */
export async function forgotCustomerPassword(email: string): Promise<{ success: boolean; message?: string }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/forgot-password`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ email }),
    });
    const result = await response.json();
    return {
      success: response.ok && result.success,
      message: result.message || "If that email is registered, instructions have been sent.",
    };
  } catch (error) {
    console.warn("Forgot password error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Reset Customer Password
 */
export async function resetCustomerPassword(payload: { email: string; password: string }): Promise<{ success: boolean; message?: string }> {
  try {
    const response = await fetch(`${getApiBaseUrl()}/customer/reset-password`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    return {
      success: response.ok && result.success,
      message: result.message || "Password update failed.",
    };
  } catch (error) {
    console.warn("Reset password error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Update Customer Profile (Name & Mobile Number only) in Laravel Backend API
 */
export async function updateCustomerProfile(payload: {
  email: string;
  name: string;
  mobile: string;
}): Promise<{ success: boolean; user?: any; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    const response = await fetch(`${getApiBaseUrl()}/customer/update-profile`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: JSON.stringify(payload),
    });
    const result = await response.json();
    if (response.ok && result.success) {
      return {
        success: true,
        user: result.user,
        message: result.message || "Profile updated successfully!",
      };
    }
    return {
      success: false,
      message: result.message || (result.errors ? Object.values(result.errors).flat().join(", ") : "Failed to update profile."),
    };
  } catch (error) {
    console.warn("Update customer profile error:", error);
    return { success: false, message: "Connection error. Please try again." };
  }
}

/**
 * Fetch Structured Homepage & Global Media (Always Live in Browser, Static-friendly on Server)
 */
export async function fetchHomepageMedia(): Promise<HomepageMedia | null> {
  return getCachedOrFetch("homepage_media", async () => {
    try {
      const url = `${getApiBaseUrl()}/customer/media`;
      const response = await fetch(url, {
        headers: { Accept: "application/json" },
      });

      if (!response.ok) {
        console.warn(`Fetch homepage media returned HTTP ${response.status}`);
        return null;
      }

      const contentType = response.headers.get("content-type") || "";
      if (!contentType.includes("application/json")) {
        console.warn("Fetch homepage media received non-JSON content type:", contentType);
        return null;
      }

      const result = await response.json();
      if (result.success && result.data) {
        return result.data as HomepageMedia;
      }
      return null;
    } catch (error) {
      console.warn("Fetch homepage media error:", error);
      return null;
    }
  }, 180000); // 3 minutes in-memory cache with request deduplication
}

export interface AboutStoryData {
  id?: number | null;
  badge?: string;
  tag_text?: string;
  title?: string;
  tagline?: string;
  subtitle?: string;
  description?: string;
  paragraph_1?: string;
  paragraph_2?: string;
  secondary_description?: string;
  desktop_image?: string;
  image?: string;
  mobile_image?: string | null;
  alt_text?: string;
  floating_badge?: {
    title: string;
    subtitle: string;
    icon: string;
    is_active: boolean;
  };
  cta?: {
    text: string;
    url: string;
    link?: string;
    is_active: boolean;
  };
  cta_text?: string;
  cta_link?: string;
  is_active?: boolean;
}

export interface CraftPillarItem {
  id: number | string;
  title: string;
  description: string;
  icon?: string;
  icon_name?: string;
  icon_type?: "preset" | "svg" | "image" | "custom" | string;
  icon_url?: string | null;
  sort_order?: number;
  is_active?: boolean;
}

export interface CraftPillarsData {
  title?: string;
  subtitle?: string;
  badge?: string;
  is_active?: boolean;
  items: CraftPillarItem[];
}

export interface AboutPageData {
  story: AboutStoryData;
  hero?: AboutStoryData;
  craftPillars: CraftPillarsData;
  pillars?: CraftPillarsData;
}

/**
 * Fetch Structured Dedicated About Page Data (Always Live in Browser & Dev Mode, Static-friendly on Build)
 */
export async function fetchAboutPageData(): Promise<AboutPageData | null> {
  try {
    const isBrowser = typeof window !== "undefined";
    const isDev = process.env.NODE_ENV === "development";
    const bypassCache = isBrowser || isDev;
    const url = bypassCache
      ? `${getApiBaseUrl()}/customer/about?_t=${Date.now()}`
      : `${getApiBaseUrl()}/customer/about`;

    const response = await fetch(url, {
      headers: {
        Accept: "application/json",
      },
      ...(bypassCache ? { cache: "no-store" as const } : {}),
    });
    const result = await response.json();
    if (result.success && result.data) {
      return result.data as AboutPageData;
    }
    return null;
  } catch (error) {
    console.warn("Fetch about page data error:", error);
    return null;
  }
}

/**
 * Record a View on Video / Reel Playback
 */
export async function recordVideoView(id: number | string): Promise<void> {
  try {
    await fetch(`${getApiBaseUrl()}/customer/media/video/${id}/view`, {
      method: "POST",
      headers: {
        Accept: "application/json",
      },
    });
  } catch (err) {
    // Non-critical metric
  }
}

export interface ContactIntroData {
  id?: number | null;
  badge?: string;
  tag_text?: string;
  title: string;
  tagline?: string;
  subtitle?: string;
  description?: string;
  image?: string;
  desktop_image?: string;
  mobile_image?: string;
  alt_text?: string;
  cta?: {
    text: string;
    link?: string;
    url?: string;
  };
  cta_text?: string;
  cta_link?: string;
  is_active?: boolean;
}

export interface ContactDetailItem {
  id: number | string;
  title: string;
  value: string;
  address_line_2?: string;
  link?: string;
  cta_link?: string;
  icon?: string;
  icon_name?: string;
  sort_order?: number;
  is_active?: boolean;
}

export interface ContactInfoSectionData {
  badge?: string;
  tag_text?: string;
  title: string;
  subtitle?: string;
  custom_order_box?: {
    title: string;
    text: string;
    link: string;
    is_active: boolean;
  };
  custom_order_box_title?: string;
  custom_order_box_text?: string;
  custom_order_box_link?: string;
  custom_order_box_active?: boolean;
  is_active?: boolean;
  items: ContactDetailItem[];
}

export interface ContactFormFieldConfig {
  key: "name" | "email" | "phone" | "subject" | "message" | string;
  label: string;
  placeholder?: string;
  required?: boolean;
  is_active?: boolean;
}

export interface ContactFormSettingsData {
  badge?: string;
  tag_text?: string;
  title: string;
  subtitle?: string;
  submit_btn_text?: string;
  cta_text?: string;
  success_title?: string;
  success_message?: string;
  error_message?: string;
  fields?: ContactFormFieldConfig[];
  is_active?: boolean;
}

export interface ContactFaqItem {
  id: number | string;
  question: string;
  answer: string;
  title?: string;
  description?: string;
  sort_order?: number;
  is_active?: boolean;
}

export interface ContactFaqsSectionData {
  badge?: string;
  tag_text?: string;
  title: string;
  subtitle?: string;
  is_active?: boolean;
  items: ContactFaqItem[];
}

export interface ContactPageData {
  intro: ContactIntroData;
  info: ContactInfoSectionData;
  form: ContactFormSettingsData;
  faqs: ContactFaqsSectionData;
}

/**
 * Fetch Structured Dedicated Contact Page Data (Always Live in Browser & Dev Mode, Static-friendly on Build)
 */
export async function fetchContactPageData(): Promise<ContactPageData | null> {
  try {
    const isBrowser = typeof window !== "undefined";
    const isDev = process.env.NODE_ENV === "development";
    const bypassCache = isBrowser || isDev;
    const url = bypassCache
      ? `${getApiBaseUrl()}/customer/contact?_t=${Date.now()}`
      : `${getApiBaseUrl()}/customer/contact`;

    const response = await fetch(url, {
      headers: {
        Accept: "application/json",
      },
      ...(bypassCache ? { cache: "no-store" as const } : {}),
    });
    const result = await response.json();
    if (result.success && result.data) {
      const d = result.data;
      return {
        intro: d.intro,
        info: d.info || d.contactDetails || d.contactInfo || d.details,
        form: d.form || d.formSettings,
        faqs: d.faqs,
      } as ContactPageData;
    }
    return null;
  } catch (error) {
    console.warn("Fetch contact page data error:", error);
    return null;
  }
}

/**
 * Customer Wishlist APIs
 */
export async function fetchCustomerWishlist(): Promise<{ products: Product[]; product_ids: string[] } | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return null;

    const res = await fetch(`${getApiBaseUrl()}/customer/wishlist`, {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });
    const data = await res.json();
    if (data.success) {
      return {
        products: data.data || [],
        product_ids: data.product_ids || [],
      };
    }
    return null;
  } catch (err) {
    console.warn("Fetch wishlist error:", err);
    return null;
  }
}

export async function toggleCustomerWishlist(productId: string): Promise<{ in_wishlist: boolean; count: number } | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return null;

    const res = await fetch(`${getApiBaseUrl()}/customer/wishlist/${productId}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json();
    if (data.success) {
      return {
        in_wishlist: data.in_wishlist,
        count: data.count,
      };
    }
    return null;
  } catch (err) {
    console.warn("Toggle wishlist error:", err);
    return null;
  }
}

export async function removeFromCustomerWishlist(productId: string): Promise<{ count: number } | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return null;

    const res = await fetch(`${getApiBaseUrl()}/customer/wishlist/${productId}`, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json();
    if (data.success) {
      return { count: data.count };
    }
    return null;
  } catch (err) {
    console.warn("Remove wishlist error:", err);
    return null;
  }
}

export async function syncCustomerWishlist(productIds: string[]): Promise<Product[] | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return null;

    const res = await fetch(`${getApiBaseUrl()}/customer/wishlist/sync`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ product_ids: productIds }),
    });
    const data = await res.json();
    if (data.success) {
      return data.data || [];
    }
    return null;
  } catch (err) {
    console.warn("Sync wishlist error:", err);
    return null;
  }
}

/**
 * Customer Addresses APIs
 */
export async function fetchCustomerAddresses(): Promise<any[] | null> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return null;

    const res = await fetch(`${getApiBaseUrl()}/customer/addresses?_t=${Date.now()}`, {
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      cache: "no-store",
    });
    const data = await res.json();
    if (data.success && Array.isArray(data.addresses)) {
      return data.addresses;
    }
    return [];
  } catch (err) {
    console.warn("Fetch addresses error:", err);
    return null;
  }
}

export async function saveCustomerAddress(addressData: Record<string, any>): Promise<{ success: boolean; address?: any; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return { success: false, message: "Unauthenticated" };

    const res = await fetch(`${getApiBaseUrl()}/customer/addresses`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(addressData),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      return { success: true, address: data.address, message: data.message };
    }
    return { success: false, message: data.message || "Failed to save address." };
  } catch (err) {
    console.warn("Save address error:", err);
    return { success: false, message: "Connection error. Please try again." };
  }
}

export async function updateCustomerAddress(id: string, addressData: Record<string, any>): Promise<{ success: boolean; address?: any; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return { success: false, message: "Unauthenticated" };

    const res = await fetch(`${getApiBaseUrl()}/customer/addresses/${encodeURIComponent(id)}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(addressData),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      return { success: true, address: data.address, message: data.message };
    }
    return { success: false, message: data.message || "Failed to update address." };
  } catch (err) {
    console.warn("Update address error:", err);
    return { success: false, message: "Connection error. Please try again." };
  }
}

export async function deleteCustomerAddress(id: string): Promise<{ success: boolean; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return { success: false, message: "Unauthenticated" };

    const res = await fetch(`${getApiBaseUrl()}/customer/addresses/${encodeURIComponent(id)}`, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json();
    return { success: res.ok && data.success, message: data.message };
  } catch (err) {
    console.warn("Delete address error:", err);
    return { success: false, message: "Connection error." };
  }
}

export async function setDefaultCustomerAddress(id: string): Promise<{ success: boolean; message?: string }> {
  try {
    const token = typeof window !== "undefined" ? localStorage.getItem("knotelle_customer_token") : null;
    if (!token) return { success: false, message: "Unauthenticated" };

    const res = await fetch(`${getApiBaseUrl()}/customer/addresses/${encodeURIComponent(id)}/default`, {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
    });
    const data = await res.json();
    return { success: res.ok && data.success, message: data.message };
  } catch (err) {
    console.warn("Set default address error:", err);
    return { success: false, message: "Connection error." };
  }
}




/**
 * Fetch public store settings (UPI QR configs, COD status, etc.)
 */
export async function fetchStoreSettings(): Promise<Record<string, any>> {
  return getCachedOrFetch("store_settings", async () => {
    try {
      const res = await fetch(`${getApiBaseUrl()}/customer/settings`, {
        headers: {
          Accept: "application/json",
        },
      });
      const json = await res.json();
      return json?.data || {};
    } catch (err) {
      console.warn("fetchStoreSettings error:", err);
      return {};
    }
  }, 600000); // 10 minutes cache with request deduplication
}
