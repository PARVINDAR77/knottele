"use client";

import React, { useState, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import { Phone, ArrowRight, ShieldCheck, RefreshCw, User as UserIcon, Edit2 } from "lucide-react";
import { KnotelleCrownLogo, BotanicalFlourish } from "@/components/ui/BotanicalDecorations";
import { useAuth } from "@/context/AuthContext";
import { useToast } from "@/context/ToastContext";
import { sendMobileOtp, verifyMobileOtp, sanitizeRedirectUrl } from "@/lib/api";

interface AuthCardProps {
  defaultMode?: "signup" | "login";
  onSuccessRedirect?: string;
}

export function AuthCard({ defaultMode = "login", onSuccessRedirect = "/account" }: AuthCardProps) {
  const router = useRouter();
  const { login } = useAuth();
  const { showToast } = useToast();
  const safeRedirect = sanitizeRedirectUrl(onSuccessRedirect);

  const [step, setStep] = useState<"phone" | "otp">("phone");
  const [mobile, setMobile] = useState("");
  const [fullName, setFullName] = useState("");
  const [otp, setOtp] = useState(["", "", "", "", "", ""]);
  const [loading, setLoading] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [countdown, setCountdown] = useState(0);

  const otpInputsRef = useRef<(HTMLInputElement | null)[]>([]);

  // Timer countdown effect for Resend OTP
  useEffect(() => {
    if (countdown <= 0) return;
    const timer = setInterval(() => {
      setCountdown((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [countdown]);

  // Focus first OTP field when step transitions to "otp"
  useEffect(() => {
    if (step === "otp") {
      setTimeout(() => {
        otpInputsRef.current[0]?.focus();
      }, 100);
    }
  }, [step]);

  // Step 1: Send OTP
  const handleSendOtp = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    setErrorMsg(null);

    const cleanMobile = mobile.replace(/\D/g, "");
    if (!cleanMobile) {
      setErrorMsg("Please enter your 10-digit mobile number.");
      return;
    }
    if (cleanMobile.length !== 10) {
      setErrorMsg("Mobile number must be exactly 10 digits.");
      return;
    }
    if (!/^[6-9]/.test(cleanMobile)) {
      setErrorMsg("Please enter a valid Indian mobile number starting with 6, 7, 8, or 9.");
      return;
    }

    setLoading(true);
    try {
      const res = await sendMobileOtp({
        mobile: cleanMobile,
        name: fullName.trim() || undefined,
      });

      if (res.success) {
        if (res.sms_gateway === 'timeout' || res.sms_gateway === 'failed') {
          showToast("OTP Generated 🌸", res.message || "OTP generated. (If SMS is delayed, check Admin Panel > OTP Logs)", "info");
        } else {
          showToast("OTP Sent! 🌸", `We've sent a 6-digit verification code to +91 ${cleanMobile}`, "success");
        }
        setStep("otp");
        setCountdown(res.cooldown || 60);
      } else {
        setErrorMsg(res.message || "Failed to send OTP. Please check the number and try again.");
        showToast("SMS Error", res.message || "Failed to send OTP", "error");
      }
    } catch (err: any) {
      setErrorMsg("Connection error. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  // Step 2: Handle OTP input typing and pasting
  const handleOtpChange = (index: number, value: string) => {
    // Handle paste of full 6-digit code
    if (value.length > 1) {
      const pastedDigits = value.replace(/\D/g, "").slice(0, 6).split("");
      if (pastedDigits.length > 0) {
        const nextOtp = [...otp];
        pastedDigits.forEach((digit, i) => {
          if (i < 6) nextOtp[i] = digit;
        });
        setOtp(nextOtp);
        const nextFocusIndex = Math.min(pastedDigits.length, 5);
        otpInputsRef.current[nextFocusIndex]?.focus();
        return;
      }
    }

    const singleDigit = value.replace(/\D/g, "").slice(-1);
    const nextOtp = [...otp];
    nextOtp[index] = singleDigit;
    setOtp(nextOtp);

    // Auto-advance focus to next digit box
    if (singleDigit && index < 5) {
      otpInputsRef.current[index + 1]?.focus();
    }
  };

  const handleOtpKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Backspace" && !otp[index] && index > 0) {
      otpInputsRef.current[index - 1]?.focus();
    }
  };

  // Step 2: Verify OTP & Log In
  const handleVerifyOtp = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);

    const fullOtp = otp.join("");
    if (fullOtp.length !== 6) {
      setErrorMsg("Please enter the complete 6-digit OTP code.");
      return;
    }

    const cleanMobile = mobile.replace(/\D/g, "");
    setLoading(true);

    try {
      const res = await verifyMobileOtp({
        mobile: cleanMobile,
        otp: fullOtp,
        name: fullName.trim() || undefined,
      });

      if (res.success && res.token && res.user) {
        showToast(
          res.is_new_user ? "Welcome to KNOTELLE! 🌸" : "Welcome Back! ✨",
          res.message || "Logged in successfully.",
          "success"
        );
        login(res.user.email || "", res.user, res.token);
        router.push(safeRedirect);
      } else {
        setErrorMsg(res.message || "Invalid or expired OTP. Please try again.");
        showToast("Verification Failed", res.message || "Invalid OTP code", "error");
      }
    } catch (err: any) {
      setErrorMsg("Connection error while verifying OTP. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  const cleanMobile = mobile.replace(/\D/g, "");

  return (
    <div className="w-full max-w-md bg-[#FFFDFC] rounded-3xl p-6 sm:p-10 shadow-boutique border border-[#E7D1CC]/80 relative overflow-hidden backdrop-blur-sm">
      {/* Decorative Floral Background Elements */}
      <BotanicalFlourish className="absolute -top-12 -right-12 w-44 h-44 text-[#913638]/5 pointer-events-none rotate-12" />
      <BotanicalFlourish className="absolute -bottom-12 -left-12 w-44 h-44 text-[#913638]/5 pointer-events-none -rotate-45" />

      {/* Brand Header */}
      <div className="text-center mb-6 relative">
        <div className="inline-flex justify-center mb-2">
          <KnotelleCrownLogo className="w-9 h-9 text-[#913638]" />
        </div>
        <h1 className="font-serif text-2xl sm:text-3xl text-[#2E211E] tracking-tight font-medium">
          {step === "phone" ? "Welcome to KNOTELLE" : "Enter Verification Code"}
        </h1>
        <p className="text-xs sm:text-sm text-[#786864] mt-1 font-sans">
          {step === "phone"
            ? "Log in or create your account with mobile OTP"
            : `We sent a 6-digit OTP via SMS to +91 ${cleanMobile}`}
        </p>

        {step === "otp" && (
          <button
            type="button"
            onClick={() => {
              setStep("phone");
              setOtp(["", "", "", "", "", ""]);
              setErrorMsg(null);
            }}
            className="inline-flex items-center gap-1.5 text-xs text-[#913638] font-semibold hover:underline mt-2 cursor-pointer"
          >
            <Edit2 className="w-3.5 h-3.5" />
            <span>Change mobile number</span>
          </button>
        )}
      </div>

      {/* Error Message Box */}
      {errorMsg && (
        <div className="mb-5 p-3.5 rounded-2xl bg-red-50/90 border border-red-200/80 text-red-800 text-xs text-center font-medium leading-relaxed">
          {errorMsg}
        </div>
      )}

      {/* STEP 1: MOBILE NUMBER FORM */}
      {step === "phone" && (
        <form onSubmit={handleSendOtp} className="space-y-4 relative">
          {/* Mobile Number Input */}
          <div className="space-y-1.5">
            <label className="text-[11px] font-bold tracking-wider text-[#2E211E] uppercase block">
              MOBILE NUMBER
            </label>
            <div className="relative flex items-center">
              <div className="absolute left-3.5 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-xs font-bold text-[#913638] pointer-events-none select-none">
                <Phone className="w-4 h-4 text-[#786864]" />
                <span>+91</span>
                <span className="text-[#E7D1CC] ml-0.5">|</span>
              </div>
              <input
                type="tel"
                required
                autoFocus
                maxLength={10}
                value={mobile}
                onChange={(e) => {
                  const val = e.target.value.replace(/\D/g, "");
                  if (val.length <= 10) setMobile(val);
                }}
                placeholder="Enter 10-digit mobile number"
                className="w-full pl-20 pr-4 py-3 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] text-sm font-semibold tracking-wider text-[#2E211E] placeholder-[#786864]/40 focus:outline-none focus:border-[#913638] focus:ring-1 focus:ring-[#913638] transition-all"
              />
            </div>
            <p className="text-[11px] text-[#786864]/70 pl-1">
              We&apos;ll send a 6-digit one-time password via SMS to verify your phone.
            </p>
          </div>

          {/* Optional Name (Useful for new customers) */}
          <div className="space-y-1.5 pt-1">
            <label className="text-[11px] font-bold tracking-wider text-[#2E211E] uppercase block">
              YOUR NAME <span className="text-[#786864] font-normal normal-case">(Optional)</span>
            </label>
            <div className="relative">
              <input
                type="text"
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                placeholder="Enter your name"
                className="w-full pl-10 pr-4 py-2.5 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] placeholder-[#786864]/40 focus:outline-none focus:border-[#913638] focus:ring-1 focus:ring-[#913638] transition-colors"
              />
              <UserIcon className="w-4 h-4 text-[#786864] absolute left-3.5 top-1/2 -translate-y-1/2" />
            </div>
          </div>

          {/* Submit Button */}
          <div className="pt-3">
            <button
              type="submit"
              disabled={loading || cleanMobile.length !== 10}
              className="w-full py-3.5 rounded-full bg-[#913638] text-white text-sm font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-sm hover:shadow-boutique-hover transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? (
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
              ) : (
                <>
                  <span>Generate OTP</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </div>

          <div className="pt-2 text-center">
            <p className="text-[11px] text-[#786864] flex items-center justify-center gap-1.5">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
              <span>Safe & secure login • No password required</span>
            </p>
          </div>
        </form>
      )}

      {/* STEP 2: 6-DIGIT OTP VERIFICATION FORM */}
      {step === "otp" && (
        <form onSubmit={handleVerifyOtp} className="space-y-5 relative">
          <div className="space-y-2">
            <label className="text-[11px] font-bold tracking-wider text-[#2E211E] uppercase block text-center">
              ENTER 6-DIGIT OTP
            </label>

            {/* 6 Digit Input Boxes */}
            <div className="flex justify-center items-center gap-2 sm:gap-3">
              {otp.map((digit, index) => (
                <input
                  key={index}
                  ref={(el) => {
                    otpInputsRef.current[index] = el;
                  }}
                  type="text"
                  inputMode="numeric"
                  maxLength={6}
                  value={digit}
                  onChange={(e) => handleOtpChange(index, e.target.value)}
                  onKeyDown={(e) => handleOtpKeyDown(index, e)}
                  className="w-11 h-13 sm:w-12 sm:h-14 text-center text-lg sm:text-xl font-bold font-mono text-[#2E211E] rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] focus:outline-none focus:border-[#913638] focus:ring-2 focus:ring-[#913638]/20 transition-all shadow-inner"
                />
              ))}
            </div>
          </div>

          {/* Resend Countdown / Trigger */}
          <div className="text-center text-xs text-[#786864] pt-1">
            {countdown > 0 ? (
              <p className="font-medium">
                Resend OTP in <span className="font-bold text-[#913638] font-mono">{countdown}s</span>
              </p>
            ) : (
              <button
                type="button"
                onClick={() => handleSendOtp()}
                disabled={loading}
                className="inline-flex items-center gap-1.5 font-bold text-[#913638] hover:underline cursor-pointer disabled:opacity-50"
              >
                <RefreshCw className="w-3.5 h-3.5" />
                <span>Resend OTP Code</span>
              </button>
            )}
          </div>

          {/* Verify & Login Button */}
          <div className="pt-2">
            <button
              type="submit"
              disabled={loading || otp.join("").length !== 6}
              className="w-full py-3.5 rounded-full bg-[#913638] text-white text-sm font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-sm hover:shadow-boutique-hover transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {loading ? (
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
              ) : (
                <>
                  <span>Verify & Log In</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </div>
        </form>
      )}
    </div>
  );
}
