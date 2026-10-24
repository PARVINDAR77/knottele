"use client";

import React, { useEffect, useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  X,
  Heart,
  ShoppingBag,
  User,
  Sparkles,
  LogOut,
  ChevronRight,
  Phone,
  Mail,
  Search,
  Tag,
  Grid,
} from "lucide-react";
import { KnotelleCrownLogo } from "@/components/ui/BotanicalDecorations";
import { useWishlist } from "@/context/WishlistContext";
import { useCart } from "@/context/CartContext";
import { useWebsiteMedia } from "@/context/MediaContext";
import { useAuth } from "@/context/AuthContext";
import { normalizeInternalLink, isRouteActive } from "@/lib/api";

export interface MobileNavItem {
  name: string;
  href: string;
  is_highlighted?: boolean;
  is_active?: boolean;
}

interface MobileDrawerProps {
  isOpen: boolean;
  onClose: () => void;
  onOpenSearch: () => void;
  navLinks?: MobileNavItem[];
}

export function MobileDrawer({
  isOpen,
  onClose,
  onOpenSearch,
  navLinks,
}: MobileDrawerProps) {
  const pathname = usePathname();
  const router = useRouter();
  const { media } = useWebsiteMedia();
  const { wishlistCount } = useWishlist();
  const { totalItemsCount } = useCart();
  const { isLoggedIn, user, logout } = useAuth();
  const [searchQuery, setSearchQuery] = useState("");

  // Close on ESC key and lock body scroll while open
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        onClose();
      }
    };

    if (isOpen) {
      document.body.style.overflow = "hidden";
      window.addEventListener("keydown", handleKeyDown);
    } else {
      document.body.style.overflow = "unset";
    }

    return () => {
      document.body.style.overflow = "unset";
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const footer = media?.footer;
  const categories = media?.categories || [];

  // Contact support info from footer/settings
  const contactPhone = footer?.column_3?.phone || "+91 97730 39243";
  const contactPhoneLink =
    footer?.column_3?.phone_link || `tel:${contactPhone.replace(/\s+/g, "")}`;
  const contactEmail = footer?.column_3?.email || "support@knotelle.in";
  const contactEmailLink =
    footer?.column_3?.email_link || `mailto:${contactEmail}`;

  const defaultItems: MobileNavItem[] = [
    { name: "Home", href: "/", is_highlighted: false },
    { name: "Shop", href: "/shop/", is_highlighted: false },
    { name: "Custom Order", href: "/custom-order/", is_highlighted: true },
    { name: "About", href: "/about/", is_highlighted: false },
    { name: "Contact", href: "/contact/", is_highlighted: false },
  ];

  const items = navLinks && navLinks.length > 0 ? navLinks : defaultItems;

  const handleDrawerNavClick = (targetHref: string) => (e: React.MouseEvent<HTMLAnchorElement>) => {
    onClose();
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
      return;
    }
    const cleanTarget = targetHref.split("?")[0].split("#")[0].replace(/\/$/, "");
    setTimeout(() => {
      if (typeof window !== "undefined") {
        const current = window.location.pathname.split("?")[0].split("#")[0].replace(/\/$/, "");
        if (current !== cleanTarget) {
          window.location.href = targetHref;
        }
      }
    }, 120);
  };

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      onClose();
      router.push(normalizeInternalLink(`/search?q=${encodeURIComponent(searchQuery.trim())}`));
    } else {
      onOpenSearch();
    }
  };

  return (
    <div
      className="fixed inset-0 z-[100] overflow-hidden"
      role="dialog"
      aria-modal="true"
      aria-label="Mobile Navigation"
    >
      {/* 1. Backdrop Overlay */}
      <div
        className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity duration-300 ease-out"
        onClick={onClose}
        aria-hidden="true"
      />

      {/* 2. Slide Drawer from LEFT Side */}
      <div className="fixed inset-y-0 left-0 max-w-full flex pr-10 sm:pr-14 z-[101]">
        <div className="w-screen max-w-[330px] sm:max-w-sm bg-white border-r border-[#E7D1CC] shadow-2xl flex flex-col h-full overflow-hidden animate-in slide-in-from-left duration-300">
          
          {/* Top Brand Header */}
          <div className="p-4 sm:p-5 border-b border-[#E7D1CC]/80 flex items-center justify-between bg-[#FFF9F6]">
            <Link
              href={normalizeInternalLink("/")}
              prefetch={true}
              onClick={onClose}
              className="flex items-center gap-2 group cursor-pointer"
            >
              <KnotelleCrownLogo className="h-10 sm:h-12 w-auto" />
            </Link>

            <button
              type="button"
              onClick={onClose}
              className="p-2 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] transition-colors cursor-pointer"
              aria-label="Close navigation menu"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Quick Search Input */}
          <div className="px-5 pt-4 pb-2 bg-white">
            <form onSubmit={handleSearchSubmit} className="relative">
              <input
                type="text"
                placeholder="Search handmade crochet..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full bg-[#FFF9F6] border border-[#E7D1CC] rounded-full pl-9 pr-4 py-2 text-xs text-[#2E211E] placeholder:text-[#786864]/70 focus:outline-none focus:border-[#913638] focus:ring-1 focus:ring-[#913638]"
              />
              <Search className="w-4 h-4 text-[#786864] absolute left-3 top-2.5 pointer-events-none" />
            </form>
          </div>

          {/* Quick Shortcuts (Wishlist & Basket) */}
          <div className="grid grid-cols-2 gap-2.5 px-5 py-3 bg-[#FFF9F6] border-y border-[#E7D1CC]/70">
            <Link
              href={normalizeInternalLink("/account/wishlist")}
              prefetch={true}
              onClick={onClose}
              className="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] transition-colors shadow-2xs relative"
            >
              <Heart className="w-3.5 h-3.5 text-[#913638]" />
              <span>Wishlist</span>
              {wishlistCount > 0 && (
                <span className="w-4 h-4 rounded-full bg-[#913638] text-white text-[9px] font-bold flex items-center justify-center ml-1">
                  {wishlistCount}
                </span>
              )}
            </Link>

            <Link
              href={normalizeInternalLink("/cart")}
              prefetch={true}
              onClick={onClose}
              className="flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-white border border-[#E7D1CC] text-xs font-semibold text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] transition-colors shadow-2xs relative"
            >
              <ShoppingBag className="w-3.5 h-3.5 text-[#913638]" />
              <span>Basket</span>
              {totalItemsCount > 0 && (
                <span className="w-4 h-4 rounded-full bg-[#913638] text-white text-[9px] font-bold flex items-center justify-center ml-1">
                  {totalItemsCount}
                </span>
              )}
            </Link>
          </div>

          {/* Scrollable Main Navigation Content */}
          <div className="flex-1 overflow-y-auto px-5 py-4 space-y-5">
            
            {/* 1. Main Navigation Links */}
            <div>
              <p className="text-[10px] font-bold uppercase tracking-wider text-[#786864]/80 mb-2">
                Navigation
              </p>
              <nav className="flex flex-col space-y-1">
                {items.map((item, idx) => {
                  const href = normalizeInternalLink(item.href);
                  const isActive = isRouteActive(pathname, href);
                  const isHighlighted = Boolean(item.is_highlighted);

                  return (
                    <Link
                      key={item.href + idx}
                      href={href}
                      prefetch={true}
                      onClick={handleDrawerNavClick(href)}
                      className={`group flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all ${
                        isActive
                          ? "bg-[#913638] text-white font-bold shadow-xs"
                          : isHighlighted
                          ? "bg-[#FCE9E5] text-[#913638] font-semibold hover:bg-[#913638] hover:text-white"
                          : "text-[#2E211E] hover:bg-[#FCE9E5]/60 hover:text-[#913638]"
                      }`}
                    >
                      <div className="flex items-center gap-2">
                        {isHighlighted && (
                          <Sparkles
                            className={`w-3.5 h-3.5 ${
                              isActive ? "text-white" : "text-[#913638] group-hover:text-white"
                            }`}
                          />
                        )}
                        <span>{item.name}</span>
                      </div>
                      <ChevronRight
                        className={`w-4 h-4 opacity-50 group-hover:opacity-100 transition-opacity ${
                          isActive ? "text-white" : "text-[#786864]"
                        }`}
                      />
                    </Link>
                  );
                })}
              </nav>
            </div>

            {/* 2. Popular Categories Quick Jump */}
            {categories.length > 0 && (
              <div className="pt-2 border-t border-[#E7D1CC]/70">
                <div className="flex items-center justify-between mb-2">
                  <p className="text-[10px] font-bold uppercase tracking-wider text-[#786864]/80">
                    Featured Categories
                  </p>
                  <Link
                    href={normalizeInternalLink("/shop")}
                    prefetch={true}
                    onClick={handleDrawerNavClick(normalizeInternalLink("/shop"))}
                    className="text-[10px] font-semibold text-[#913638] hover:underline"
                  >
                    View All
                  </Link>
                </div>
                <div className="grid grid-cols-2 gap-1.5">
                  {categories.slice(0, 6).map((cat) => {
                    const catHref = normalizeInternalLink(`/category/${cat.slug}`);
                    return (
                      <Link
                        key={cat.id || cat.slug}
                        href={catHref}
                        prefetch={true}
                        onClick={handleDrawerNavClick(catHref)}
                        className="flex items-center gap-1.5 px-2.5 py-2 rounded-lg bg-[#FFF9F6] border border-[#E7D1CC]/60 text-xs text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638] transition-colors truncate"
                      >
                        <Tag className="w-3 h-3 text-[#913638] shrink-0" />
                        <span className="truncate">{cat.name}</span>
                      </Link>
                    );
                  })}
                </div>
              </div>
            )}

            {/* 3. Account & Authentication Section */}
            <div className="pt-2 border-t border-[#E7D1CC]/70">
              <p className="text-[10px] font-bold uppercase tracking-wider text-[#786864]/80 mb-2">
                Account
              </p>
              {isLoggedIn ? (
                <div className="space-y-1.5">
                  <div className="p-3 rounded-xl bg-[#FCE9E5]/60 border border-[#E7D1CC]">
                    <p className="text-[10px] uppercase tracking-wider font-bold text-[#913638]">
                      Signed in as
                    </p>
                    <p className="text-xs font-bold text-[#2E211E] truncate">
                      {user?.name || user?.email}
                    </p>
                  </div>

                  <Link
                    href={normalizeInternalLink("/account")}
                    prefetch={true}
                    onClick={handleDrawerNavClick(normalizeInternalLink("/account"))}
                    className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#2E211E] hover:bg-[#FCE9E5]/60 hover:text-[#913638] transition-colors"
                  >
                    <User className="w-4 h-4 text-[#913638]" />
                    <span>My Account</span>
                  </Link>

                  <Link
                    href={normalizeInternalLink("/account/orders")}
                    prefetch={true}
                    onClick={handleDrawerNavClick(normalizeInternalLink("/account/orders"))}
                    className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#2E211E] hover:bg-[#FCE9E5]/60 hover:text-[#913638] transition-colors"
                  >
                    <ShoppingBag className="w-4 h-4 text-[#913638]" />
                    <span>My Orders</span>
                  </Link>

                  <button
                    type="button"
                    onClick={() => {
                      logout();
                      onClose();
                    }}
                    className="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#913638] hover:bg-red-50 transition-colors text-left cursor-pointer"
                  >
                    <LogOut className="w-4 h-4" />
                    <span>Sign Out</span>
                  </button>
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-2">
                  <Link
                    href={normalizeInternalLink("/login")}
                    prefetch={true}
                    onClick={handleDrawerNavClick(normalizeInternalLink("/login"))}
                    className="flex items-center justify-center py-2 px-3 rounded-xl bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] transition-colors text-center shadow-xs"
                  >
                    Login
                  </Link>
                  <Link
                    href={normalizeInternalLink("/signup")}
                    prefetch={true}
                    onClick={handleDrawerNavClick(normalizeInternalLink("/signup"))}
                    className="flex items-center justify-center py-2 px-3 rounded-xl bg-white border border-[#E7D1CC] text-[#2E211E] text-xs font-semibold hover:bg-[#FCE9E5] hover:text-[#913638] transition-colors text-center shadow-2xs"
                  >
                    Register
                  </Link>
                </div>
              )}
            </div>

            {/* 4. Custom Order Promotion Box */}
            <div className="p-3.5 rounded-2xl bg-gradient-to-br from-[#FCE9E5] to-[#EFB8B0]/30 border border-[#E7D1CC] text-center space-y-1">
              <Sparkles className="w-4 h-4 text-[#913638] mx-auto" />
              <h4 className="font-serif-luxury text-xs font-bold text-[#2E211E]">
                Need Custom Crochet?
              </h4>
              <p className="text-[10px] text-[#786864] leading-tight">
                Send us your design ideas and our artisans will craft it for you.
              </p>
              <div className="pt-1.5">
                <Link
                  href={normalizeInternalLink("/custom-order")}
                  prefetch={true}
                  onClick={handleDrawerNavClick(normalizeInternalLink("/custom-order"))}
                  className="inline-block w-full py-1.5 px-3 rounded-full bg-[#913638] text-white text-[11px] font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all text-center cursor-pointer shadow-2xs"
                >
                  Start Custom Order →
                </Link>
              </div>
            </div>

          </div>

          {/* Bottom Footer Contact Information */}
          <div className="p-4 border-t border-[#E7D1CC] bg-[#FFF9F6] space-y-1.5 text-[11px] text-[#786864]">
            <a
              href={contactPhoneLink}
              className="flex items-center gap-2 hover:text-[#913638] transition-colors"
            >
              <Phone className="w-3.5 h-3.5 text-[#913638] shrink-0" />
              <span className="truncate">{contactPhone}</span>
            </a>
            <a
              href={contactEmailLink}
              className="flex items-center gap-2 hover:text-[#913638] transition-colors"
            >
              <Mail className="w-3.5 h-3.5 text-[#913638] shrink-0" />
              <span className="truncate">{contactEmail}</span>
            </a>
          </div>

        </div>
      </div>
    </div>
  );
}
