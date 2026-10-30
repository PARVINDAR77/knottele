"use client";

import React, { useState } from "react";
import { CreditCard, Loader2 } from "lucide-react";
import { createRazorpayOrder, verifyRazorpayPayment } from "@/lib/api";
import { KNOTELLE_LOGO_BASE64 } from "@/lib/brandLogo";

interface RazorpayButtonProps {
  amount: number; // in Rupees
  customerName?: string;
  customerEmail?: string;
  customerPhone?: string;
  onSuccess?: (paymentData: {
    razorpay_order_id: string;
    razorpay_payment_id: string;
    razorpay_signature: string;
  }) => void;
  onError?: (error: any) => void;
  className?: string;
  label?: string;
}

export function loadRazorpayScript(): Promise<boolean> {
  return new Promise((resolve) => {
    if (typeof window === "undefined") {
      resolve(false);
      return;
    }
    if ((window as any).Razorpay) {
      resolve(true);
      return;
    }
    const script = document.createElement("script");
    script.src = "https://checkout.razorpay.com/v1/checkout.js";
    script.async = true;
    script.onload = () => resolve(true);
    script.onerror = () => resolve(false);
    document.body.appendChild(script);
  });
}

export default function RazorpayButton({
  amount,
  customerName = "",
  customerEmail = "",
  customerPhone = "",
  onSuccess,
  onError,
  className = "",
  label = "Pay with Razorpay",
}: RazorpayButtonProps) {
  const [loading, setLoading] = useState(false);

  const handlePay = async () => {
    try {
      setLoading(true);

      const scriptLoaded = await loadRazorpayScript();
      if (!scriptLoaded) {
        throw new Error("Razorpay SDK failed to load. Please check your internet connection.");
      }

      // Minimum amount 100 paise (₹1)
      const amountInPaise = Math.max(100, Math.round(amount * 100));

      const orderData = await createRazorpayOrder(amountInPaise, "INR");
      if (!orderData || !orderData.order_id) {
        throw new Error(orderData?.message || "Failed to create Razorpay order.");
      }

      const keyId = orderData.key_id || process.env.NEXT_PUBLIC_RAZORPAY_KEY_ID || "rzp_test_Tu0iAbxyAp9LSq";

      const options = {
        key: keyId,
        amount: orderData.amount,
        currency: orderData.currency || "INR",
        name: "KNOTELLE",
        description: `Bespoke Crochet Order - ₹${amount.toLocaleString("en-IN")}`,
        image: KNOTELLE_LOGO_BASE64,
        order_id: orderData.order_id,
        prefill: {
          name: customerName,
          email: customerEmail,
          contact: customerPhone,
        },
        theme: {
          color: "#913638",
        },
        modal: {
          ondismiss: () => {
            setLoading(false);
          },
        },
        handler: async (response: any) => {
          try {
            const verifyRes = await verifyRazorpayPayment({
              razorpay_order_id: response.razorpay_order_id,
              razorpay_payment_id: response.razorpay_payment_id,
              razorpay_signature: response.razorpay_signature,
            });

            if (!verifyRes || !verifyRes.success) {
              throw new Error(verifyRes?.message || "Payment verification failed.");
            }

            if (onSuccess) {
              onSuccess({
                razorpay_order_id: response.razorpay_order_id,
                razorpay_payment_id: response.razorpay_payment_id,
                razorpay_signature: response.razorpay_signature,
              });
            }
          } catch (err: any) {
            if (onError) onError(err);
          } finally {
            setLoading(false);
          }
        },
      };

      const rzpInstance = new (window as any).Razorpay(options);
      rzpInstance.on("payment.failed", (failRes: any) => {
        setLoading(false);
        if (onError) onError(failRes);
      });
      rzpInstance.open();
    } catch (err: any) {
      setLoading(false);
      if (onError) onError(err);
    }
  };

  return (
    <button
      type="button"
      onClick={handlePay}
      disabled={loading}
      className={
        className ||
        "w-full py-4 px-6 rounded-2xl bg-[#913638] hover:bg-[#782c2e] text-white font-bold transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
      }
    >
      {loading ? (
        <>
          <Loader2 className="w-5 h-5 animate-spin" />
          <span>Processing Payment...</span>
        </>
      ) : (
        <>
          <CreditCard className="w-5 h-5" />
          <span>{label}</span>
        </>
      )}
    </button>
  );
}
