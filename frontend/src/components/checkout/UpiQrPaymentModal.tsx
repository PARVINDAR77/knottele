"use client";

import React, { useState, useEffect, useId } from "react";
import Image from "next/image";
import {
  X,
  Copy,
  Check,
  Clock,
  ShieldCheck,
  AlertCircle,
  QrCode,
  Smartphone,
  ExternalLink,
  HelpCircle,
  Loader2,
  Sparkles,
} from "lucide-react";

interface UpiQrPaymentModalProps {
  isOpen: boolean;
  onClose: () => void;
  amount: number;
  upiId?: string;
  accountName?: string;
  qrImageUrl?: string;
  timerMinutes?: number;
  instructions?: string;
  onSubmitUtr: (utrNumber: string) => Promise<void>;
  isSubmitting?: boolean;
}

export default function UpiQrPaymentModal({
  isOpen,
  onClose,
  amount,
  upiId = "knotelle@upi",
  accountName = "KNOTELLE BOUTIQUE",
  qrImageUrl = "/images/payments/upi-qr.png",
  timerMinutes = 5,
  instructions = "Scan the QR code using any UPI app (Google Pay, PhonePe, Paytm, BHIM). After completing the payment, enter the 12-digit UTR / Reference number below to verify your order.",
  onSubmitUtr,
  isSubmitting = false,
}: UpiQrPaymentModalProps) {
  const [timeLeft, setTimeLeft] = useState<number>(timerMinutes * 60);
  const [utrNumber, setUtrNumber] = useState<string>("");
  const [copiedField, setCopiedField] = useState<string | null>(null);
  const [showHelper, setShowHelper] = useState<boolean>(false);
  const [imgLoadError, setImgLoadError] = useState<boolean>(false);
  const [validationError, setValidationError] = useState<string | null>(null);

  // Reset timer whenever modal opens or timerMinutes changes
  useEffect(() => {
    if (isOpen) {
      setTimeLeft(Math.max(60, (timerMinutes || 5) * 60));
      setUtrNumber("");
      setValidationError(null);
      setImgLoadError(false);
    }
  }, [isOpen, timerMinutes]);

  // Countdown timer interval
  useEffect(() => {
    if (!isOpen || timeLeft <= 0) return;

    const timer = setInterval(() => {
      setTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [isOpen, timeLeft]);

  if (!isOpen) return null;

  const safeAmount = typeof amount === "number" && !isNaN(amount) ? Math.max(0, amount) : 0;
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  const formattedTime = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
  const totalSeconds = Math.max(60, (timerMinutes || 5) * 60);
  const progressPercent = Math.max(0, Math.min(100, (timeLeft / totalSeconds) * 100));

  // Copy to clipboard helper
  const handleCopy = (text: string, field: string) => {
    if (typeof navigator !== "undefined" && navigator.clipboard) {
      navigator.clipboard.writeText(text);
      setCopiedField(field);
      setTimeout(() => setCopiedField(null), 2000);
    }
  };

  // UPI deep link for mobile apps
  const upiIntentUrl = `upi://pay?pa=${encodeURIComponent(upiId)}&pn=${encodeURIComponent(accountName)}&am=${safeAmount.toFixed(2)}&cu=INR&tn=Knotelle%20Order`;

  // Fallback dynamic QR URL if custom uploaded QR doesn't load
  const dynamicQrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=${encodeURIComponent(upiIntentUrl)}`;

  const resolvedQrImage = !imgLoadError && qrImageUrl && qrImageUrl.trim() !== "" ? qrImageUrl : dynamicQrUrl;

  const handleUtrSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const cleanUtr = utrNumber.trim();

    if (!cleanUtr) {
      setValidationError("Please enter your 12-digit UPI UTR / Reference number.");
      return;
    }

    if (cleanUtr.length < 8 || cleanUtr.length > 25) {
      setValidationError("Please enter a valid 12-digit UTR / Reference number.");
      return;
    }

    setValidationError(null);
    await onSubmitUtr(cleanUtr);
  };

  const handleRestartTimer = () => {
    setTimeLeft(Math.max(60, (timerMinutes || 5) * 60));
  };

  return (
    <div
      role="dialog"
      aria-modal="true"
      aria-labelledby="upi-qr-modal-title"
      className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-stone-900/70 backdrop-blur-sm overflow-y-auto"
    >
      <div className="relative w-full max-w-lg my-auto bg-white rounded-3xl shadow-2xl border border-[#E7D1CC] overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        {/* Header Bar */}
        <div className="relative bg-gradient-to-r from-[#913638] via-[#A03E40] to-[#7A2B2D] px-6 py-4 text-white">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 rounded-xl bg-white/15 backdrop-blur-xs flex items-center justify-center border border-white/20">
                <QrCode className="w-5 h-5 text-[#FCE9E5]" />
              </div>
              <div>
                <h3 id="upi-qr-modal-title" className="font-serif text-lg font-bold tracking-tight text-white">
                  Instant UPI QR Payment
                </h3>
                <p className="text-xs text-[#FCE9E5]/80">Zero transaction fees • Instant verification</p>
              </div>
            </div>

            <button
              onClick={onClose}
              disabled={isSubmitting}
              className="p-1.5 rounded-full text-white/80 hover:text-white hover:bg-white/10 transition-colors disabled:opacity-50"
              title="Close"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Countdown Timer Strip */}
          <div className="mt-3 pt-3 border-t border-white/15 flex items-center justify-between text-xs">
            <div className="flex items-center gap-1.5 font-medium">
              <Clock className={`w-4 h-4 ${timeLeft < 60 ? "animate-pulse text-amber-300" : "text-[#FCE9E5]"}`} />
              <span className="text-[#FCE9E5]">Session Expires In:</span>
              <span className={`font-mono font-bold text-sm tracking-wider ${timeLeft < 60 ? "text-amber-300" : "text-white"}`}>
                {formattedTime}
              </span>
            </div>

            {timeLeft === 0 && (
              <button
                type="button"
                onClick={handleRestartTimer}
                className="text-[11px] underline text-[#FCE9E5] hover:text-white font-semibold"
              >
                Reset Timer
              </button>
            )}
          </div>

          {/* Timer Progress Bar */}
          <div className="absolute bottom-0 left-0 right-0 h-1 bg-black/20">
            <div
              className={`h-full transition-all duration-1000 ease-linear ${
                timeLeft < 60 ? "bg-amber-400" : "bg-[#FCE9E5]"
              }`}
              style={{ width: `${progressPercent}%` }}
            />
          </div>
        </div>

        {/* Modal Body */}
        <div className="p-5 sm:p-6 space-y-5 max-h-[80vh] overflow-y-auto">
          {timeLeft === 0 ? (
            /* Expired Session View */
            <div className="text-center py-8 space-y-4">
              <div className="w-16 h-16 mx-auto rounded-full bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200">
                <Clock className="w-8 h-8" />
              </div>
              <div>
                <h4 className="text-lg font-bold text-gray-900">Payment Session Timed Out</h4>
                <p className="text-sm text-gray-600 mt-1 max-w-sm mx-auto">
                  For your transaction security, payment sessions expire after {timerMinutes} minutes. If you have already paid, enter your UTR below, or refresh the session.
                </p>
              </div>
              <div className="flex justify-center gap-3">
                <button
                  type="button"
                  onClick={handleRestartTimer}
                  className="px-5 py-2.5 bg-[#913638] text-white text-sm font-semibold rounded-xl hover:bg-[#7A2B2D] transition-colors shadow-sm"
                >
                  Refresh Payment Session
                </button>
              </div>
            </div>
          ) : (
            <>
              {/* QR Code Presentation Box */}
              <div className="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC]/80">
                {/* QR Code Canvas */}
                <div className="relative shrink-0 p-3 bg-white rounded-2xl border border-[#E7D1CC] shadow-sm flex flex-col items-center">
                  <div className="relative w-44 h-44 flex items-center justify-center bg-white overflow-hidden rounded-xl">
                    <img
                      src={resolvedQrImage}
                      alt="Scan UPI QR Code"
                      className="w-full h-full object-contain"
                      onError={() => setImgLoadError(true)}
                    />
                  </div>
                  <div className="mt-2 flex items-center gap-1.5 text-[11px] font-semibold text-gray-600">
                    <Sparkles className="w-3.5 h-3.5 text-[#913638]" />
                    <span>Scan with Any UPI App</span>
                  </div>
                </div>

                {/* Amount & UPI Details */}
                <div className="flex-1 w-full space-y-3 text-left">
                  {/* Payable Amount Highlight */}
                  <div className="p-3 bg-white rounded-xl border border-[#E7D1CC]/70 shadow-xs">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-gray-500">Amount to Pay</span>
                    <div className="flex items-center justify-between mt-0.5">
                      <span className="text-2xl font-serif font-bold text-[#913638]">
                        ₹{safeAmount.toFixed(2)}
                      </span>
                      <button
                        type="button"
                        onClick={() => handleCopy(safeAmount.toFixed(2), "amount")}
                        className="px-2.5 py-1 text-xs font-semibold text-[#913638] bg-[#FCE9E5] hover:bg-[#F8D5CE] rounded-lg transition-colors flex items-center gap-1"
                        title="Copy Amount"
                      >
                        {copiedField === "amount" ? (
                          <>
                            <Check className="w-3.5 h-3.5 text-green-600" />
                            <span className="text-green-700">Copied</span>
                          </>
                        ) : (
                          <>
                            <Copy className="w-3.5 h-3.5" />
                            <span>Copy</span>
                          </>
                        )}
                      </button>
                    </div>
                  </div>

                  {/* UPI ID / VPA */}
                  <div className="p-3 bg-white rounded-xl border border-[#E7D1CC]/70 shadow-xs">
                    <span className="text-[11px] font-bold uppercase tracking-wider text-gray-500">UPI ID / VPA</span>
                    <div className="flex items-center justify-between mt-0.5 gap-2">
                      <span className="font-mono text-xs sm:text-sm font-semibold text-gray-800 truncate">
                        {upiId}
                      </span>
                      <button
                        type="button"
                        onClick={() => handleCopy(upiId, "upiId")}
                        className="px-2.5 py-1 text-xs font-semibold text-[#913638] bg-[#FCE9E5] hover:bg-[#F8D5CE] rounded-lg transition-colors flex items-center gap-1 shrink-0"
                        title="Copy UPI ID"
                      >
                        {copiedField === "upiId" ? (
                          <>
                            <Check className="w-3.5 h-3.5 text-green-600" />
                            <span className="text-green-700">Copied</span>
                          </>
                        ) : (
                          <>
                            <Copy className="w-3.5 h-3.5" />
                            <span>Copy</span>
                          </>
                        )}
                      </button>
                    </div>
                  </div>

                  {/* Payee Name */}
                  <div className="text-xs text-gray-600 flex items-center gap-1.5 px-1">
                    <ShieldCheck className="w-4 h-4 text-green-600 shrink-0" />
                    <span>
                      Payee: <strong className="text-gray-900">{accountName}</strong>
                    </span>
                  </div>
                </div>
              </div>

              {/* Supported UPI Apps & Mobile Intent */}
              <div className="space-y-2">
                <div className="flex items-center justify-between text-xs text-gray-500">
                  <span className="font-semibold uppercase tracking-wider">Fast Pay on Mobile</span>
                  <span className="text-[11px]">Tap to launch app</span>
                </div>

                <div className="grid grid-cols-4 gap-2">
                  <a
                    href={upiIntentUrl}
                    className="flex flex-col items-center justify-center p-2 rounded-xl bg-gray-50 hover:bg-[#FCE9E5]/60 border border-gray-200 hover:border-[#913638] transition-all group"
                  >
                    <span className="text-xs font-bold text-gray-700 group-hover:text-[#913638]">GPay</span>
                    <span className="text-[9px] text-gray-400">Google Pay</span>
                  </a>
                  <a
                    href={upiIntentUrl}
                    className="flex flex-col items-center justify-center p-2 rounded-xl bg-gray-50 hover:bg-[#FCE9E5]/60 border border-gray-200 hover:border-[#913638] transition-all group"
                  >
                    <span className="text-xs font-bold text-gray-700 group-hover:text-[#913638]">PhonePe</span>
                    <span className="text-[9px] text-gray-400">UPI App</span>
                  </a>
                  <a
                    href={upiIntentUrl}
                    className="flex flex-col items-center justify-center p-2 rounded-xl bg-gray-50 hover:bg-[#FCE9E5]/60 border border-gray-200 hover:border-[#913638] transition-all group"
                  >
                    <span className="text-xs font-bold text-gray-700 group-hover:text-[#913638]">Paytm</span>
                    <span className="text-[9px] text-gray-400">UPI Wallet</span>
                  </a>
                  <a
                    href={upiIntentUrl}
                    className="flex flex-col items-center justify-center p-2 rounded-xl bg-gray-50 hover:bg-[#FCE9E5]/60 border border-gray-200 hover:border-[#913638] transition-all group"
                  >
                    <span className="text-xs font-bold text-gray-700 group-hover:text-[#913638]">BHIM</span>
                    <span className="text-[9px] text-gray-400">Any UPI</span>
                  </a>
                </div>
              </div>

              {/* Instructions Callout */}
              <div className="p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl text-xs text-amber-900 flex items-start gap-2.5">
                <AlertCircle className="w-4 h-4 text-amber-700 shrink-0 mt-0.5" />
                <div className="space-y-1">
                  <p className="font-semibold text-amber-950">Important Verification Step:</p>
                  <p className="text-[11px] leading-relaxed text-amber-900/90">{instructions}</p>
                </div>
              </div>

              {/* UTR Input Form */}
              <form onSubmit={handleUtrSubmit} className="space-y-3 pt-1">
                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <label htmlFor="utr-input" className="text-xs font-bold uppercase tracking-wider text-gray-700 flex items-center gap-1">
                      <span>UPI UTR / Reference ID</span>
                      <span className="text-red-500">*</span>
                    </label>
                    <button
                      type="button"
                      onClick={() => setShowHelper(!showHelper)}
                      className="text-[11px] text-[#913638] hover:underline flex items-center gap-1 font-medium"
                    >
                      <HelpCircle className="w-3.5 h-3.5" />
                      <span>Where to find UTR?</span>
                    </button>
                  </div>

                  {showHelper && (
                    <div className="mb-2 p-3 bg-stone-50 border border-stone-200 rounded-xl text-xs text-stone-700 space-y-1.5 animate-in fade-in duration-150">
                      <p className="font-semibold text-stone-900">Locating your 12-digit UPI Reference:</p>
                      <ul className="list-disc pl-4 space-y-1 text-[11px] text-stone-600">
                        <li><strong>Google Pay:</strong> Open transaction &rarr; look for &ldquo;UPI transaction ID&rdquo;</li>
                        <li><strong>PhonePe:</strong> Open transaction receipt &rarr; &ldquo;UTR&rdquo; (12 digits)</li>
                        <li><strong>Paytm:</strong> View payment details &rarr; &ldquo;UPI Ref No.&rdquo;</li>
                        <li><strong>Bank SMS:</strong> The SMS confirmation specifies Ref No. or UPI Ref.</li>
                      </ul>
                    </div>
                  )}

                  <input
                    id="utr-input"
                    type="text"
                    value={utrNumber}
                    onChange={(e) => {
                      setUtrNumber(e.target.value.replace(/[^a-zA-Z0-9]/g, ""));
                      if (validationError) setValidationError(null);
                    }}
                    placeholder="Enter 12-digit UTR (e.g. 423456789012)"
                    maxLength={25}
                    disabled={isSubmitting}
                    className="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-300 rounded-xl text-sm font-mono tracking-wider text-gray-900 focus:ring-2 focus:ring-[#913638]/20 focus:border-[#913638] outline-none transition-all placeholder:text-gray-400 placeholder:tracking-normal placeholder:font-sans"
                  />
                  {validationError && (
                    <p className="mt-1 text-xs text-rose-600 flex items-center gap-1">
                      <AlertCircle className="w-3.5 h-3.5" />
                      <span>{validationError}</span>
                    </p>
                  )}
                </div>

                {/* Submit Order Verification Button */}
                <button
                  type="submit"
                  disabled={isSubmitting || !utrNumber.trim()}
                  className="w-full py-3.5 px-6 bg-[#913638] hover:bg-[#7A2B2D] disabled:bg-gray-300 text-white font-semibold rounded-2xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 text-sm disabled:cursor-not-allowed group"
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      <span>Submitting Order Verification...</span>
                    </>
                  ) : (
                    <>
                      <ShieldCheck className="w-4 h-4 text-[#FCE9E5]" />
                      <span>I Have Paid & Verify Order</span>
                      <Sparkles className="w-4 h-4 text-amber-200 opacity-80 group-hover:scale-110 transition-transform" />
                    </>
                  )}
                </button>
              </form>

              {/* Security & Support Guarantee */}
              <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500">
                <span className="flex items-center gap-1">
                  <ShieldCheck className="w-3.5 h-3.5 text-green-600" />
                  Direct Artisan Boutique Payment
                </span>
                <span>Crafting begins upon verification</span>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}

