"use client";

import React, { useEffect, useState } from "react";
import { ShieldAlert, RotateCw } from "lucide-react";

export function PanelConflictGuard() {
  const [isAdminOpen, setIsAdminOpen] = useState(false);

  useEffect(() => {
    if (typeof window === "undefined") return;

    let channel: BroadcastChannel | null = null;
    try {
      if ("BroadcastChannel" in window) {
        channel = new BroadcastChannel("knotelle_panel_guard");
      }
    } catch {}

    const updateStorefrontHeartbeat = () => {
      try {
        localStorage.setItem("knotelle_storefront_tab_heartbeat", Date.now().toString());
      } catch {}
    };

    const isCurrentAdminActive = () => {
      try {
        const raw = localStorage.getItem("knotelle_admin_tab_heartbeat");
        if (raw) {
          const ts = parseInt(raw, 10);
          if (Date.now() - ts < 4500) {
            return true;
          }
        }
      } catch {}
      return false;
    };

    const checkConflict = () => {
      setIsAdminOpen(isCurrentAdminActive());
    };

    // Initial heartbeat and immediate conflict check
    updateStorefrontHeartbeat();
    checkConflict();

    // Heartbeat every 2 seconds
    const interval = setInterval(() => {
      updateStorefrontHeartbeat();
      checkConflict();
      if (channel) {
        try {
          channel.postMessage({ type: "STOREFRONT_ACTIVE", ts: Date.now() });
        } catch {}
      }
    }, 2000);

    // BroadcastChannel real-time listener
    if (channel) {
      channel.onmessage = (e) => {
        if (e.data?.type === "ADMIN_ACTIVE") {
          setIsAdminOpen(true);
        } else if (e.data?.type === "ADMIN_CLOSED") {
          checkConflict();
        }
      };
    }

    // Storage listener across tabs in the same browser
    const handleStorage = (e: StorageEvent) => {
      if (e.key === "knotelle_admin_tab_heartbeat") {
        checkConflict();
      }
    };
    window.addEventListener("storage", handleStorage);

    const handleBeforeUnload = () => {
      try {
        localStorage.setItem("knotelle_storefront_tab_heartbeat", "0");
        if (channel) {
          channel.postMessage({ type: "STOREFRONT_CLOSED", ts: Date.now() });
        }
      } catch {}
    };
    window.addEventListener("beforeunload", handleBeforeUnload);

    return () => {
      clearInterval(interval);
      window.removeEventListener("storage", handleStorage);
      window.removeEventListener("beforeunload", handleBeforeUnload);
      if (channel) {
        try {
          channel.close();
        } catch {}
      }
    };
  }, []);

  if (!isAdminOpen) return null;

  return (
    <div className="fixed inset-0 z-[99999999] bg-[#1C1917]/95 backdrop-blur-md flex items-center justify-center p-4 sm:p-6 transition-all duration-300">
      <div className="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 text-center shadow-2xl border border-rose-100 animate-in fade-in zoom-in-95 duration-200">
        <div className="w-16 h-16 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-200">
          <ShieldAlert className="w-8 h-8" />
        </div>

        <h2 className="font-serif-luxury text-2xl font-bold text-[#2E211E] mb-2 tracking-tight">
          Browser Isolation Active
        </h2>

        <p className="text-sm text-[#786864] leading-relaxed mb-5">
          The <strong>KNOTELLE Admin Panel</strong> is currently active in another tab of this browser.
          To eliminate browser cache collisions and protect data integrity, <strong>the Storefront and Admin Panel cannot be accessed in the same browser simultaneously</strong>.
        </p>

        <div className="bg-[#FFF8F5] border border-[#E7D1CC] rounded-2xl p-4 mb-6 text-left text-xs sm:text-sm text-[#913638] leading-relaxed space-y-1.5">
          <p className="font-bold text-[#2E211E]">To continue, choose one:</p>
          <p>• <strong>Close all Admin Panel tabs</strong> in this browser, then click below.</p>
          <p>• <strong>OR</strong> open the Storefront in an <strong>Incognito Window</strong> or another browser (like Edge or Safari).</p>
        </div>

        <button
          onClick={() => {
            if (typeof window !== "undefined") {
              window.location.reload();
            }
          }}
          className="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-full bg-[#913638] hover:bg-[#782c2e] text-white font-medium text-sm transition-all shadow-md active:scale-95 cursor-pointer"
        >
          <RotateCw className="w-4 h-4" />
          <span>Continue to Storefront</span>
        </button>
      </div>
    </div>
  );
}
