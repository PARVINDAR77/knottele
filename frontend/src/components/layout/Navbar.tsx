"use client";

import React, { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Search, Heart, User, ShoppingBag, Sparkles, ArrowRight, Menu } from "lucide-react";
import { KnotelleCrownLogo } from "@/components/ui/BotanicalDecorations";
import { useCart } from "@/context/CartContext";
import { useWishlist } from "@/context/WishlistContext";
import { useWebsiteMedia } from "@/context/MediaContext";
import { normalizeInternalLink, isRouteActive } from "@/lib/api";
import { SearchModal } from "./SearchModal";
import { MobileDrawer } from "./MobileDrawer";

export function Navbar() {
  const pathname = usePathname();
  const { media } = useWebsiteMedia();
  const { totalItemsCount, setIsCartOpen } = useCart();
  const { wishlistCount } = useWishlist();
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const navbar = media?.navbar;
  const announcement = navbar?.announcement;
  const actions = navbar?.actions;

  const defaultNavLinks = [
    { name: "Home", href: "/", is_highlighted: false, is_active: true },
    { name: "Shop", href: "/shop/", is_highlighted: false, is_active: true },
    { name: "Custom Order", href: "/custom-order/", is_highlighted: true, is_active: true },
    { name: "About", href: "/about/", is_highlighted: false, is_active: true },
    { name: "Contact", href: "/contact/", is_highlighted: false, is_active: true },
  ];

  const rawNavLinks = (navbar?.nav_links && navbar.nav_links.length > 0)
    ? navbar.nav_links
    : defaultNavLinks;

  const navLinks = rawNavLinks.filter((link) => link.is_active !== false);

  const showSearch = actions?.show_search !== false;
  const showWishlist = actions?.show_wishlist !== false;
  const showAccount = actions?.show_account !== false;
  const showCart = actions?.show_cart !== false;

  return (
    <>
      {/* Dynamic Announcement Banner */}
      {announcement?.is_active && announcement?.text && (
        <div className="bg-[#913638] text-white text-xs font-medium py-2 px-4 text-center tracking-wide transition-all z-50 relative">
          {announcement.link ? (
            <Link
              href={normalizeInternalLink(announcement.link)}
              prefetch={true}
              className="inline-flex items-center justify-center gap-1.5 hover:underline decoration-white/60 underline-offset-4"
            >
              <span>{announcement.text}</span>
              <ArrowRight className="w-3.5 h-3.5" />
            </Link>
          ) : (
            <span>{announcement.text}</span>
          )}
        </div>
      )}

      <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-[#E7D1CC] transition-all">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-20">
            
            {/* Left: Mobile Menu Button + KNOTELLE Logo */}
            <div className="flex items-center gap-1.5 sm:gap-2">
              <button
                type="button"
                onClick={() => setIsMobileMenuOpen(true)}
                className="lg:hidden p-2.5 -ml-1 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] active:scale-95 transition-all cursor-pointer"
                aria-label="Open mobile menu"
              >
                <Menu className="w-6 h-6 text-[#2E211E]" />
              </button>

              <Link href={normalizeInternalLink("/")} prefetch={true} className="flex items-center group py-1" aria-label="KNOTELLE Home">
                <KnotelleCrownLogo className="h-12 sm:h-16 w-auto" />
              </Link>
            </div>

            {/* Center: Dynamic Navigation Links */}
            <nav className="hidden lg:flex items-center gap-7 xl:gap-9">
              {navLinks.map((link, idx) => {
                const href = normalizeInternalLink(link.href);
                const isActive = isRouteActive(pathname, href);
                const isHighlighted = Boolean(link.is_highlighted);

                if (isHighlighted) {
                  return (
                    <Link
                      key={link.href + idx}
                      href={href}
                      prefetch={true}
                      className={`inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold tracking-wide transition-all shadow-xs active:scale-95 ${
                        isActive
                          ? "bg-[#913638] text-white ring-2 ring-[#913638]/25 shadow-sm"
                          : "bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC] hover:bg-[#913638] hover:text-white hover:border-[#913638] hover:shadow-xs"
                      }`}
                    >
                      <Sparkles className="w-3.5 h-3.5" />
                      <span>{link.name}</span>
                    </Link>
                  );
                }

                return (
                  <Link
                    key={link.href + idx}
                    href={href}
                    prefetch={true}
                    className={`relative text-sm font-medium tracking-wide py-2 transition-colors flex flex-col items-center ${
                      isActive
                        ? "text-[#913638] font-bold"
                        : "text-[#2E211E] hover:text-[#913638]"
                    }`}
                  >
                    <span>{link.name}</span>
                    {isActive && (
                      <span className="absolute bottom-0 w-6 h-0.5 bg-[#913638] rounded-full" />
                    )}
                  </Link>
                );
              })}
            </nav>

            {/* Right: Actions */}
            <div className="flex items-center gap-1 sm:gap-2">
              {showSearch && (
                <button
                  onClick={() => setIsSearchOpen(true)}
                  className="p-2.5 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] transition-colors cursor-pointer"
                  aria-label="Search store"
                >
                  <Search className="w-5 h-5" />
                </button>
              )}

              {showWishlist && (
                <Link
                  href={normalizeInternalLink("/account/wishlist")}
                  prefetch={true}
                  className="relative p-2.5 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] transition-colors flex items-center justify-center"
                  aria-label="View wishlist"
                >
                  <Heart className="w-5 h-5" />
                  {wishlistCount > 0 && (
                    <span className="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#913638] text-white text-[9px] font-bold flex items-center justify-center">
                      {wishlistCount}
                    </span>
                  )}
                </Link>
              )}

              {showAccount && (
                <Link
                  href={normalizeInternalLink("/account")}
                  prefetch={true}
                  className="p-2.5 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] transition-colors flex items-center justify-center"
                  aria-label="My Account"
                >
                  <User className="w-5 h-5" />
                </Link>
              )}

              {showCart && (
                <button
                  onClick={() => setIsCartOpen(true)}
                  className="relative p-2.5 rounded-full text-[#2E211E] hover:text-[#913638] hover:bg-[#FCE9E5] transition-colors flex items-center justify-center cursor-pointer"
                  aria-label="Shopping Cart"
                >
                  <ShoppingBag className="w-5 h-5" />
                  {totalItemsCount > 0 && (
                    <span className="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#913638] text-white text-[9px] font-bold flex items-center justify-center">
                      {totalItemsCount}
                    </span>
                  )}
                </button>
              )}
            </div>

          </div>
        </div>
      </header>

      {/* Search Modal */}
      <SearchModal
        isOpen={isSearchOpen}
        onClose={() => setIsSearchOpen(false)}
      />

      {/* Mobile Menu Drawer (Slides from Left) */}
      <MobileDrawer
        isOpen={isMobileMenuOpen}
        onClose={() => setIsMobileMenuOpen(false)}
        onOpenSearch={() => {
          setIsMobileMenuOpen(false);
          setIsSearchOpen(true);
        }}
        navLinks={navLinks}
      />
    </>
  );
}

