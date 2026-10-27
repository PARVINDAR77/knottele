"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import Image from "next/image";
import {
  Sparkles,
  Clock,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  ExternalLink,
  Search,
  MessageCircle,
  FileText,
  Palette,
  X,
  Plus,
} from "lucide-react";
import { useAuth } from "@/context/AuthContext";
import { fetchCustomerCustomOrders, fetchCustomOrderById } from "@/lib/api";

interface CustomOrder {
  id: number;
  reference_id: string;
  category: string;
  selected_palette?: string;
  custom_colors?: string[];
  custom_color_notes?: string;
  size_preference?: string;
  personalization?: string;
  design_notes?: string;
  urgency?: string;
  budget_range?: string;
  reference_image_url?: string;
  reference_image_name?: string;
  status: string;
  status_label?: string;
  status_badge_class?: string;
  quoted_price?: number;
  admin_notes?: string;
  created_at: string;
}

export default function CustomerCustomOrdersPage() {
  const { user } = useAuth();
  const [orders, setOrders] = useState<CustomOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedOrder, setSelectedOrder] = useState<CustomOrder | null>(null);

  const handleNavigateToCustomOrder = (e: React.MouseEvent<HTMLAnchorElement>) => {
    // If middle click or Cmd/Ctrl/Shift held, allow browser new tab
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
      return;
    }
    e.preventDefault();
    window.location.href = "/custom-order";
  };

  const loadOrders = async () => {
    setLoading(true);
    try {
      // 1. Fetch from server API
      const serverOrders = await fetchCustomerCustomOrders();

      // 2. Also check local storage for requests submitted in this browser
      let localOrders: CustomOrder[] = [];
      if (typeof window !== "undefined") {
        try {
          const cached = JSON.parse(localStorage.getItem("knotelle_custom_orders") || "[]");
          if (Array.isArray(cached)) {
            localOrders = cached;
          }
        } catch {}
      }

      // Merge by reference_id
      const map = new Map<string, CustomOrder>();
      serverOrders.forEach((o: any) => map.set(o.reference_id, o));
      localOrders.forEach((o: any) => {
        if (!map.has(o.reference_id)) {
          map.set(o.reference_id, o);
        }
      });

      setOrders(Array.from(map.values()));
    } catch (err) {
      console.warn("Failed to load custom orders", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadOrders();
  }, [user]);

  const getStatusBadge = (status: string) => {
    const s = (status || "").toLowerCase();
    if (s === "completed") {
      return {
        label: "Completed",
        className: "bg-emerald-50 text-emerald-800 border-emerald-200",
      };
    }
    if (s === "in_progress") {
      return {
        label: "In Crafting 🧶",
        className: "bg-rose-50 text-rose-800 border-rose-200",
      };
    }
    if (s === "approved") {
      return {
        label: "Approved",
        className: "bg-teal-50 text-teal-800 border-teal-200",
      };
    }
    if (s === "quoted") {
      return {
        label: "Quote Ready ✨",
        className: "bg-purple-50 text-purple-800 border-purple-200 font-bold",
      };
    }
    if (s === "in_review") {
      return {
        label: "Artisan Reviewing",
        className: "bg-blue-50 text-blue-800 border-blue-200",
      };
    }
    if (s === "rejected") {
      return {
        label: "Declined",
        className: "bg-stone-100 text-stone-600 border-stone-200",
      };
    }
    return {
      label: "Pending Review",
      className: "bg-amber-50 text-amber-800 border-amber-200",
    };
  };

  const filteredOrders = orders.filter((o) => {
    if (!searchQuery.trim()) return true;
    const q = searchQuery.toLowerCase();
    return (
      (o.reference_id && o.reference_id.toLowerCase().includes(q)) ||
      (o.category && o.category.toLowerCase().includes(q)) ||
      (o.selected_palette && o.selected_palette.toLowerCase().includes(q))
    );
  });

  return (
    <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-8 shadow-boutique space-y-6 animate-in fade-in">
      {/* Top Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#E7D1CC]">
        <div>
          <div className="flex items-center gap-2 mb-1">
            <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC]">
              <Sparkles className="w-3 h-3 inline mr-1" />
              Bespoke Studio
            </span>
            <span className="text-xs text-[#786864] font-medium">
              ● Handcrafted to Order
            </span>
          </div>
          <h1 className="font-serif-luxury text-2xl font-bold text-[#2E211E]">
            Custom Orders
          </h1>
          <p className="text-xs text-[#786864] mt-0.5">
            View your custom crochet inquiries, artisan quotes, yarn palettes, and crafting progress.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <Link
            href="/custom-order"
            onClick={handleNavigateToCustomOrder}
            className="px-5 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all flex items-center gap-1.5 shadow-xs cursor-pointer"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>New Custom Request</span>
          </Link>
        </div>
      </div>

      {/* Search & Filter Bar */}
      <div className="flex items-center gap-3">
        <div className="relative flex-1 max-w-sm">
          <Search className="w-3.5 h-3.5 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#786864]/50" />
          <input
            type="text"
            placeholder="Search by reference # or category..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl pl-9 pr-4 py-2 text-xs text-[#2E211E] focus:outline-none focus:border-[#913638]"
          />
        </div>
        <span className="text-xs text-[#786864] font-medium hidden sm:inline">
          {filteredOrders.length} {filteredOrders.length === 1 ? "request" : "requests"}
        </span>
      </div>

      {/* Content */}
      {loading ? (
        <div className="py-16 text-center">
          <div className="w-8 h-8 border-2 border-[#913638] border-t-transparent rounded-full animate-spin mx-auto mb-3" />
          <p className="text-xs text-[#786864]">Loading your custom commissions...</p>
        </div>
      ) : filteredOrders.length === 0 ? (
        <div className="py-14 text-center">
          <div className="w-16 h-16 rounded-full bg-[#FFF9F6] border border-[#E7D1CC] text-[#913638] flex items-center justify-center mx-auto mb-4">
            <Sparkles className="w-8 h-8" />
          </div>
          <h3 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
            No Custom Requests Found
          </h3>
          <p className="text-xs text-[#786864] mt-1 mb-6 max-w-md mx-auto leading-relaxed">
            Have a dream crochet bouquet, amigurumi keepsake, or personalized wearable in mind? Share your inspiration with our master artisans.
          </p>
          <Link
            href="/custom-order"
            onClick={handleNavigateToCustomOrder}
            className="px-7 py-3 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] transition-all inline-flex items-center gap-2 shadow-xs cursor-pointer"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Design Your Custom Piece →</span>
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredOrders.map((order) => {
            const badge = getStatusBadge(order.status);
            const dateStr = order.created_at
              ? new Date(order.created_at).toLocaleDateString("en-IN", {
                  day: "numeric",
                  month: "short",
                  year: "numeric",
                })
              : "Recent";

            return (
              <div
                key={order.reference_id || order.id}
                className="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl p-5 hover:border-[#EFB8B0] transition-all shadow-2xs hover:shadow-boutique group"
              >
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  {/* Left: Ref, Category, Date */}
                  <div className="space-y-2">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span className="font-mono font-bold text-xs bg-white text-[#913638] border border-[#E7D1CC] px-2.5 py-0.5 rounded-lg shadow-2xs">
                        #{order.reference_id}
                      </span>
                      <span
                        className={`text-[11px] font-bold px-2.5 py-0.5 rounded-full border ${badge.className}`}
                      >
                        {badge.label}
                      </span>
                      <span className="text-[11px] text-[#786864]">
                        Submitted on {dateStr}
                      </span>
                    </div>

                    <div className="flex items-center gap-3">
                      <h3 className="font-serif-luxury text-base font-bold text-[#2E211E]">
                        Custom {order.category}
                      </h3>
                      {order.size_preference && (
                        <span className="text-xs text-[#786864] bg-white px-2 py-0.5 rounded-md border border-[#E7D1CC]/70">
                          {order.size_preference}
                        </span>
                      )}
                    </div>

                    {/* Palette & Swatches */}
                    <div className="flex items-center gap-2 text-xs text-[#786864] flex-wrap">
                      <span className="font-medium text-[#2E211E]">
                        Palette:
                      </span>
                      <span>{order.selected_palette || "Custom"}</span>
                      {order.custom_colors && Array.isArray(order.custom_colors) && order.custom_colors.length > 0 && (
                        <div className="flex items-center gap-1 ml-1">
                          {order.custom_colors.map((c, i) => (
                            <span
                              key={i}
                              className="w-3.5 h-3.5 rounded-full border border-stone-300 shadow-2xs inline-block"
                              style={{ backgroundColor: c }}
                              title={c}
                            />
                          ))}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Right: Quote & Action Button */}
                  <div className="flex sm:flex-col items-end justify-between sm:justify-center gap-3 shrink-0 border-t sm:border-t-0 pt-3 sm:pt-0 border-[#E7D1CC]/60">
                    <div className="text-right">
                      {order.quoted_price ? (
                        <div>
                          <span className="text-[10px] uppercase font-bold text-[#786864] block">
                            Artisan Quote
                          </span>
                          <span className="font-serif-luxury text-base sm:text-lg font-bold text-[#913638]">
                            ₹{Number(order.quoted_price).toLocaleString("en-IN")}
                          </span>
                        </div>
                      ) : (
                        <span className="text-xs text-[#786864] italic bg-white px-2.5 py-1 rounded-lg border border-[#E7D1CC]">
                          Quote Under Review
                        </span>
                      )}
                    </div>

                    <button
                      onClick={() => setSelectedOrder(order)}
                      className="px-4 py-2 rounded-full bg-white text-[#913638] border border-[#913638] text-xs font-semibold hover:bg-[#913638] hover:text-white transition-all shadow-2xs flex items-center gap-1.5 cursor-pointer"
                    >
                      <FileText className="w-3.5 h-3.5" />
                      <span>View Specifications</span>
                    </button>
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* MODAL: CUSTOM ORDER SPECIFICATIONS DETAIL */}
      {selectedOrder && (
        <div
          className="fixed inset-0 bg-stone-900/60 backdrop-blur-xs z-[9999] flex items-center justify-center p-4 animate-in fade-in duration-200"
          onClick={() => setSelectedOrder(null)}
        >
          <div
            className="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden shadow-2xl border border-[#E7D1CC]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-5 px-6 border-b border-[#E7D1CC] flex items-center justify-between bg-[#FFF9F6] shrink-0">
              <div>
                <div className="flex items-center gap-2 mb-1">
                  <span className="font-mono font-bold text-xs bg-[#FCE9E5] text-[#913638] px-2.5 py-0.5 rounded-lg border border-[#E7D1CC]">
                    #{selectedOrder.reference_id}
                  </span>
                  <span
                    className={`text-[11px] font-bold px-2 py-0.5 rounded-full border ${getStatusBadge(selectedOrder.status).className}`}
                  >
                    {getStatusBadge(selectedOrder.status).label}
                  </span>
                </div>
                <h2 className="font-serif-luxury text-lg font-bold text-[#2E211E]">
                  Custom {selectedOrder.category} Specifications
                </h2>
              </div>
              <button
                onClick={() => setSelectedOrder(null)}
                className="w-8 h-8 rounded-full bg-stone-100 text-stone-400 hover:text-stone-700 flex items-center justify-center transition-colors cursor-pointer"
              >
                <X className="w-4 h-4" />
              </button>
            </div>

            {/* Body */}
            <div className="p-6 space-y-5 overflow-y-auto flex-1 text-xs text-[#2E211E]">
              {/* Artisan Quote Banner */}
              {selectedOrder.quoted_price ? (
                <div className="bg-[#FCE9E5]/60 border border-[#E7D1CC] rounded-2xl p-4 flex items-center justify-between">
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-[#913638] block">
                      Approved Artisan Quote
                    </span>
                    <p className="font-serif-luxury text-xl font-bold text-[#913638] mt-0.5">
                      ₹{Number(selectedOrder.quoted_price).toLocaleString("en-IN")}
                    </p>
                    <p className="text-[11px] text-[#786864] mt-0.5">
                      Includes all yarn, custom crafting, and finishing.
                    </p>
                  </div>
                  <a
                    href={`https://wa.me/919773039243?text=${encodeURIComponent(
                      `Hello Knotelle! I would like to proceed with my Custom Order #${selectedOrder.reference_id} quoted at ₹${selectedOrder.quoted_price}.`
                    )}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-2xs transition-all shrink-0"
                  >
                    <MessageCircle className="w-3.5 h-3.5" />
                    <span>Confirm on WhatsApp</span>
                  </a>
                </div>
              ) : (
                <div className="bg-[#FFF9F6] border border-[#E7D1CC] rounded-2xl p-4 flex items-center gap-3">
                  <Clock className="w-5 h-5 text-[#913638] shrink-0" />
                  <div>
                    <p className="font-bold text-[#2E211E]">
                      Artisan Reviewing Specifications
                    </p>
                    <p className="text-[#786864] text-[11px] mt-0.5">
                      Our lead artisan evaluates yarn textures, bloom shapes, and sizing before quoting. Expect a WhatsApp message or update within 24 hours.
                    </p>
                  </div>
                </div>
              )}

              {/* Specs Grid */}
              <div className="grid grid-cols-2 gap-3 bg-[#FFF9F6] p-4 rounded-2xl border border-[#E7D1CC]">
                <div>
                  <span className="text-[10px] uppercase font-bold text-[#786864] block">
                    Category & Size
                  </span>
                  <p className="font-bold mt-0.5">
                    {selectedOrder.category} • {selectedOrder.size_preference || "Standard"}
                  </p>
                </div>
                <div>
                  <span className="text-[10px] uppercase font-bold text-[#786864] block">
                    Estimated Timeline & Budget
                  </span>
                  <p className="font-bold mt-0.5">
                    {selectedOrder.urgency || "Standard"} • {selectedOrder.budget_range || "Flexible"}
                  </p>
                </div>
              </div>

              {/* Palette & Swatches */}
              <div className="bg-[#FFF9F6] p-4 rounded-2xl border border-[#E7D1CC] space-y-2">
                <span className="text-[10px] uppercase font-bold text-[#786864] block">
                  Chosen Palette & Yarn Shades
                </span>
                <p className="font-bold">{selectedOrder.selected_palette || "Custom Palette"}</p>
                {selectedOrder.custom_colors && Array.isArray(selectedOrder.custom_colors) && (
                  <div className="flex items-center gap-2 flex-wrap pt-1">
                    {selectedOrder.custom_colors.map((hex, idx) => (
                      <div
                        key={idx}
                        className="flex items-center gap-1.5 px-2 py-1 rounded-full bg-white border border-[#E7D1CC] text-[10px] font-mono font-semibold"
                      >
                        <span
                          className="w-3.5 h-3.5 rounded-full border border-stone-300"
                          style={{ backgroundColor: hex }}
                        />
                        <span>{hex}</span>
                      </div>
                    ))}
                  </div>
                )}
                {selectedOrder.custom_color_notes && (
                  <p className="text-[11px] text-[#786864] italic mt-1">
                    Note: &ldquo;{selectedOrder.custom_color_notes}&rdquo;
                  </p>
                )}
              </div>

              {/* Personalization */}
              {selectedOrder.personalization && (
                <div className="bg-[#FFF9F6] p-4 rounded-2xl border border-[#E7D1CC]">
                  <span className="text-[10px] uppercase font-bold text-[#786864] block mb-1">
                    Personalization & Monograms
                  </span>
                  <p className="whitespace-pre-wrap">{selectedOrder.personalization}</p>
                </div>
              )}

              {/* Design Notes */}
              {selectedOrder.design_notes && (
                <div className="bg-[#FFF9F6] p-4 rounded-2xl border border-[#E7D1CC]">
                  <span className="text-[10px] uppercase font-bold text-[#786864] block mb-1">
                    Design Notes & Instructions
                  </span>
                  <p className="whitespace-pre-wrap">{selectedOrder.design_notes}</p>
                </div>
              )}

              {/* Attached Reference Image */}
              {selectedOrder.reference_image_url && (
                <div className="bg-[#FFF9F6] p-4 rounded-2xl border border-[#E7D1CC] space-y-2">
                  <span className="text-[10px] uppercase font-bold text-[#786864] block">
                    Attached Reference Artwork
                  </span>
                  <div className="relative w-full h-48 rounded-xl overflow-hidden bg-white border border-[#E7D1CC]">
                    <img
                      src={selectedOrder.reference_image_url}
                      alt="Reference"
                      className="w-full h-full object-contain"
                    />
                  </div>
                </div>
              )}

              {/* Artisan Notes */}
              {selectedOrder.admin_notes && (
                <div className="bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                  <span className="text-[10px] uppercase font-bold text-amber-800 block mb-1">
                    Artisan Note
                  </span>
                  <p className="text-amber-900 whitespace-pre-wrap">
                    {selectedOrder.admin_notes}
                  </p>
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="p-4 px-6 bg-[#FFF9F6] border-t border-[#E7D1CC] flex items-center justify-between shrink-0">
              <span className="text-[11px] text-[#786864]">
                Questions? WhatsApp: +91 97730 39243
              </span>
              <button
                onClick={() => setSelectedOrder(null)}
                className="px-5 py-2 rounded-full bg-white text-[#2E211E] border border-[#E7D1CC] text-xs font-semibold hover:bg-[#FCE9E5] transition-all cursor-pointer"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
