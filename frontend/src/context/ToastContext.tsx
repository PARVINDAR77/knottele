"use client";

import React, { createContext, useContext, useState, useCallback } from "react";
import { CheckCircle2, Heart, ShoppingBag, AlertCircle, Info, X } from "lucide-react";

export type ToastType = "success" | "cart" | "wishlist" | "info" | "error" | "warning";

interface Toast {
  id: string;
  title: string;
  message?: string;
  type: ToastType;
}

interface ToastContextType {
  showToast: (title: string, message?: string, type?: ToastType) => void;
}

const ToastContext = createContext<ToastContextType | undefined>(undefined);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const showToast = useCallback((title: string, message?: string, type: ToastType = "success") => {
    const id = Math.random().toString(36).substring(2, 9);
    setToasts((prev) => [...prev, { id, title, message, type }]);

    setTimeout(() => {
      setToasts((prev) => prev.filter((t) => t.id !== id));
    }, 4000);
  }, []);

  const removeToast = (id: string) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  return (
    <ToastContext.Provider value={{ showToast }}>
      {children}
      {/* Toast Notification Container - Positioned at upper side (top-center on mobile, top-right on desktop) with high z-index */}
      <div className="fixed top-4 sm:top-6 left-1/2 -translate-x-1/2 sm:left-auto sm:translate-x-0 sm:right-6 z-[120] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-4 sm:px-0">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className="pointer-events-auto flex items-start gap-3 p-3.5 sm:p-4 bg-white/98 backdrop-blur-md rounded-2xl border border-[#E7D1CC] shadow-xl transition-all animate-in slide-in-from-top-4 fade-in duration-300"
          >
            <div className="shrink-0 mt-0.5">
              {toast.type === "cart" && (
                <div className="w-8 h-8 rounded-full bg-[#FCE9E5] text-[#913638] flex items-center justify-center">
                  <ShoppingBag className="w-4 h-4" />
                </div>
              )}
              {toast.type === "wishlist" && (
                <div className="w-8 h-8 rounded-full bg-[#FCE9E5] text-[#913638] flex items-center justify-center">
                  <Heart className="w-4 h-4 fill-[#913638]" />
                </div>
              )}
              {toast.type === "success" && (
                <div className="w-8 h-8 rounded-full bg-[#FCE9E5] text-[#913638] flex items-center justify-center">
                  <CheckCircle2 className="w-4 h-4" />
                </div>
              )}
              {toast.type === "info" && (
                <div className="w-8 h-8 rounded-full bg-[#EBF2F7] text-[#2B6CB0] flex items-center justify-center">
                  <Info className="w-4 h-4" />
                </div>
              )}
              {toast.type === "error" && (
                <div className="w-8 h-8 rounded-full bg-[#FCE9E5] text-[#913638] flex items-center justify-center">
                  <AlertCircle className="w-4 h-4" />
                </div>
              )}
              {toast.type === "warning" && (
                <div className="w-8 h-8 rounded-full bg-[#FFFBEB] text-[#B45309] flex items-center justify-center">
                  <AlertCircle className="w-4 h-4" />
                </div>
              )}
            </div>

            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-[#2E211E]">{toast.title}</p>
              {toast.message && (
                <p className="text-xs text-[#786864] mt-0.5 leading-relaxed">{toast.message}</p>
              )}
            </div>

            <button
              onClick={() => removeToast(toast.id)}
              className="text-[#786864] hover:text-[#2E211E] transition-colors p-1 cursor-pointer"
              aria-label="Close notification"
            >
              <X className="w-4 h-4" />
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast() {
  const context = useContext(ToastContext);
  if (!context) {
    throw new Error("useToast must be used within a ToastProvider");
  }
  return context;
}
