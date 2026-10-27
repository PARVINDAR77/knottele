"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Package,
  Heart,
  User,
  MapPin,
  LogOut,
  Sparkles,
  Edit2,
  Phone,
  Lock,
} from "lucide-react";
import { useAuth } from "@/context/AuthContext";
import { useWishlist } from "@/context/WishlistContext";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { AuthCard } from "@/components/auth/AuthCard";
import { updateCustomerProfile } from "@/lib/api";

export default function AccountLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, isAuthLoading, logout, updateProfile } = useAuth();
  const { wishlistCount } = useWishlist();

  const isOrderTrackingPath = pathname.startsWith("/account/orders");

  // Redirect unauthenticated guests to login only AFTER auth initialization completes
  useEffect(() => {
    if (!isAuthLoading && !user && !isOrderTrackingPath) {
      router.replace(`/login?redirectTo=${encodeURIComponent(pathname)}`);
    }
  }, [isAuthLoading, user, pathname, router, isOrderTrackingPath]);

  // Edit Profile Modal state
  const [isEditOpen, setIsEditOpen] = useState(false);
  const [editName, setEditName] = useState("");
  const [editMobile, setEditMobile] = useState("");
  const [saving, setSaving] = useState(false);
  const [editError, setEditError] = useState<string | null>(null);

  if (isAuthLoading) {
    return (
      <div className="bg-[#FFF9F6] min-h-screen py-24 flex flex-col items-center justify-center text-center">
        <div className="w-10 h-10 border-3 border-[#913638] border-t-transparent rounded-full animate-spin mb-4" />
        <p className="font-serif-luxury text-base font-bold text-[#2E211E]">Loading KNOTELLE Account...</p>
        <p className="text-xs text-[#786864] mt-1">Restoring member session</p>
      </div>
    );
  }

  if (!user) {
    if (isOrderTrackingPath) {
      return (
        <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            {children}
          </div>
        </div>
      );
    }
    return (
      <div className="bg-[#FFF9F6] min-h-screen py-16 flex items-center justify-center text-center">
        <p className="text-xs text-[#786864]">Redirecting to Login...</p>
      </div>
    );
  }

  const openEditModal = () => {
    setEditName(user?.name || "");
    const rawMobile = user?.phone || (user as any)?.mobile || "";
    const digits = rawMobile.replace(/\D/g, "");
    setEditMobile(digits.length >= 10 ? digits.slice(-10) : digits);
    setEditError(null);
    setIsEditOpen(true);
  };

  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setEditError(null);

    if (!editName.trim()) {
      setEditError("Please enter your full name.");
      return;
    }
    const cleanDigits = editMobile.replace(/\D/g, "");
    if (!cleanDigits || cleanDigits.length !== 10) {
      setEditError("Contact number must be exactly 10 digits.");
      return;
    }

    setSaving(true);
    try {
      const formattedMobile = `+91 ${cleanDigits}`;
      const result = await updateCustomerProfile({
        email: user.email,
        name: editName.trim(),
        mobile: formattedMobile,
      });

      if (result.success) {
        updateProfile({
          name: editName.trim(),
          phone: formattedMobile,
        });
        setIsEditOpen(false);
      } else {
        setEditError(result.message || "Failed to update profile.");
      }
    } catch {
      setEditError("Connection error. Please try again.");
    } finally {
      setSaving(false);
    }
  };

  const navItems = [
    { name: "Overview", href: "/account", icon: LayoutDashboard },
    { name: "My Orders", href: "/account/orders", icon: Package },
    { name: "Custom Orders", href: "/account/custom-orders", icon: Sparkles },
    { name: "Wishlist", href: "/account/wishlist", icon: Heart, badge: wishlistCount },
    { name: "My Addresses", href: "/account/addresses", icon: MapPin },
  ];

  return (
    <div className="bg-[#FFF9F6] min-h-screen py-8 lg:py-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumbs */}
        <Breadcrumbs
          items={[
            { label: "My Account", href: "/account" },
            { label: navItems.find((n) => n.href === pathname)?.name || "Dashboard" },
          ]}
          className="mb-8"
        />

        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* Left Account Sidebar (Spans 4 cols on lg) */}
          <aside className="lg:col-span-4 bg-white rounded-3xl border border-[#E7D1CC] p-6 shadow-boutique space-y-6">
            
            {/* User Greeting Card */}
            <div className="flex items-center gap-4 pb-6 border-b border-[#E7D1CC]">
              {user?.avatar && !user.avatar.includes("unsplash.com") ? (
                <div className="relative w-14 h-14 rounded-full overflow-hidden border-2 border-[#913638] shrink-0 bg-[#FFF9F6]">
                  <Image
                    src={user.avatar}
                    alt={user?.name || "User"}
                    fill
                    sizes="56px"
                    className="object-cover"
                  />
                </div>
              ) : (
                <div className="w-14 h-14 rounded-full bg-gradient-to-br from-[#913638] to-[#74292B] border-2 border-[#E7D1CC] shrink-0 flex items-center justify-center text-white font-serif-luxury font-bold text-xl shadow-sm uppercase">
                  {(user?.name || "K").charAt(0)}
                </div>
              )}
              <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-1.5">
                  <span className="text-[10px] uppercase font-bold text-[#913638] tracking-wider bg-[#FCE9E5] px-2.5 py-0.5 rounded-full">
                    KNOTELLE Member
                  </span>
                  <button
                    onClick={openEditModal}
                    title="Edit Name & Contact Number"
                    className="p-1.5 rounded-full text-[#913638] bg-[#FCE9E5] hover:bg-[#913638] hover:text-white transition-all cursor-pointer shadow-2xs flex items-center justify-center shrink-0"
                  >
                    <Edit2 className="w-3.5 h-3.5" />
                  </button>
                </div>
                <h3 className="font-serif-luxury text-lg font-bold text-[#2E211E] truncate mt-1">
                  {user?.name || "Member"}
                </h3>
                <p className="text-xs text-[#786864] truncate">{user?.email || ""}</p>
                {(user?.phone || (user as any)?.mobile) && (
                  <p className="text-[11px] text-[#913638] font-medium truncate mt-0.5">
                    {user.phone || (user as any)?.mobile}
                  </p>
                )}
              </div>
            </div>

            {/* Nav Links */}
            <nav className="space-y-1.5">
              {navItems.map((item) => {
                const Icon = item.icon;
                const isActive = pathname === item.href;
                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    className={`flex items-center justify-between px-4 py-3 rounded-2xl text-xs sm:text-sm font-semibold transition-all ${
                      isActive
                        ? "bg-[#913638] text-white shadow-xs"
                        : "text-[#2E211E] hover:bg-[#FCE9E5] hover:text-[#913638]"
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <Icon className="w-4 h-4" />
                      <span>{item.name}</span>
                    </div>
                    {item.badge !== undefined && item.badge > 0 && (
                      <span
                        className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                          isActive
                            ? "bg-white text-[#913638]"
                            : "bg-[#FCE9E5] text-[#913638]"
                        }`}
                      >
                        {item.badge}
                      </span>
                    )}
                  </Link>
                );
              })}

              {/* Logout Button */}
              <button
                onClick={logout}
                className="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-xs sm:text-sm font-semibold text-[#786864] hover:bg-rose-50 hover:text-rose-700 transition-colors text-left pt-2 cursor-pointer"
              >
                <LogOut className="w-4 h-4" />
                <span>Sign Out</span>
              </button>
            </nav>

            {/* Custom Order Box */}
            <div className="p-4 rounded-2xl bg-gradient-to-br from-[#FCE9E5] to-[#FFF9F6] border border-[#E7D1CC] space-y-2">
              <div className="flex items-center gap-1.5 text-xs font-bold text-[#913638]">
                <Sparkles className="w-3.5 h-3.5 text-[#C69A5A]" />
                <span>Need a Custom Creation?</span>
              </div>
              <p className="text-[11px] text-[#786864] leading-relaxed">
                Have a unique design in mind? Work directly with our master artisans.
              </p>
              <Link
                href="/custom-order"
                onClick={(e) => {
                  if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                  e.preventDefault();
                  window.location.href = "/custom-order";
                }}
                className="inline-block text-xs font-semibold text-[#913638] hover:underline pt-1 cursor-pointer"
              >
                Request Custom Order →
              </Link>
            </div>
          </aside>

          {/* Right Main Content (Spans 8 cols on lg) */}
          <main className="lg:col-span-8">{children}</main>
        </div>
      </div>

      {/* EDIT PROFILE MODAL */}
      {isEditOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in">
          <div className="w-full max-w-md bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-2xl relative space-y-5 animate-in zoom-in-95">
            
            {/* Header */}
            <div className="flex items-center justify-between border-b border-[#E7D1CC] pb-4">
              <div>
                <h2 className="font-serif-luxury text-xl font-bold text-[#2E211E]">
                  Edit Account Profile
                </h2>
                <p className="text-xs text-[#786864] mt-0.5">
                  Update your full name and contact number
                </p>
              </div>
              <button
                onClick={() => setIsEditOpen(false)}
                className="w-8 h-8 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-[#786864] hover:text-[#913638] flex items-center justify-center font-bold text-sm cursor-pointer"
              >
                ✕
              </button>
            </div>

            {/* Error alert */}
            {editError && (
              <div className="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                {editError}
              </div>
            )}

            {/* Form */}
            <form onSubmit={handleSaveProfile} className="space-y-4">
              
              {/* FULL NAME */}
              <div className="space-y-1">
                <label className="text-[11px] font-bold tracking-wider text-[#2E211E] uppercase block">
                  FULL NAME
                </label>
                <div className="relative">
                  <input
                    type="text"
                    required
                    value={editName}
                    onChange={(e) => setEditName(e.target.value)}
                    placeholder="Enter your full name"
                    className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs font-medium text-[#2E211E] placeholder-[#786864]/50 focus:outline-none focus:border-[#913638] focus:ring-1 focus:ring-[#913638]"
                  />
                  <User className="w-4 h-4 text-[#786864] absolute left-3.5 top-1/2 -translate-y-1/2" />
                </div>
              </div>

              {/* CONTACT NUMBER */}
              <div className="space-y-1">
                <label className="text-[11px] font-bold tracking-wider text-[#2E211E] uppercase block">
                  CONTACT NUMBER (+91)
                </label>
                <div className="relative flex items-center">
                  <div className="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1 text-xs font-bold text-[#913638] pointer-events-none select-none">
                    <Phone className="w-3.5 h-3.5 text-[#786864]" />
                    <span>+91</span>
                    <span className="text-[#E7D1CC] ml-0.5">|</span>
                  </div>
                  <input
                    type="tel"
                    required
                    maxLength={10}
                    value={editMobile}
                    onChange={(e) => {
                      const val = e.target.value.replace(/\D/g, "");
                      if (val.length <= 10) setEditMobile(val);
                    }}
                    placeholder="Enter 10-digit contact number"
                    className="w-full pl-20 pr-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs font-medium text-[#2E211E] placeholder-[#786864]/50 focus:outline-none focus:border-[#913638] focus:ring-1 focus:ring-[#913638]"
                  />
                </div>
              </div>

              {/* EMAIL ID (READ-ONLY) */}
              <div className="space-y-1">
                <div className="flex items-center justify-between">
                  <label className="text-[11px] font-bold tracking-wider text-[#786864] uppercase block">
                    EMAIL ADDRESS (READ-ONLY)
                  </label>
                  <span className="text-[10px] text-[#786864] font-medium italic">Cannot be edited</span>
                </div>
                <div className="relative">
                  <input
                    type="email"
                    disabled
                    value={user.email}
                    className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-gray-100 border border-gray-200 text-xs font-medium text-gray-500 cursor-not-allowed select-none"
                  />
                  <Lock className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex items-center justify-end gap-3 pt-3 border-t border-[#E7D1CC]">
                <button
                  type="button"
                  onClick={() => setIsEditOpen(false)}
                  className="px-5 py-2.5 rounded-full border border-[#E7D1CC] text-xs font-semibold text-[#786864] hover:bg-[#FFF9F6] transition-colors cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] transition-colors cursor-pointer flex items-center gap-2 disabled:opacity-60"
                >
                  {saving ? (
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  ) : (
                    <span>Save Changes</span>
                  )}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
