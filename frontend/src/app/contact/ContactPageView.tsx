"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import {
  Mail,
  Phone,
  MapPin,
  Send,
  MessageCircle,
  Clock,
  CheckCircle2,
  Globe,
  Sparkles,
  Heart,
  ChevronDown,
  Loader2,
  ArrowRight,
  Gift,
} from "lucide-react";
import { BotanicalFlourish, FlowerIcon } from "@/components/ui/BotanicalDecorations";
import { Breadcrumbs } from "@/components/ui/Breadcrumbs";
import { useToast } from "@/context/ToastContext";
import {
  submitContactForm,
  fetchContactPageData,
  ContactPageData,
  ContactDetailItem,
  ContactFaqItem,
} from "@/lib/api";
import { setupAdminSyncListener, useClientCache, setLocalCache } from "@/lib/cache";

const CONTACT_CACHE_KEY = "knotelle_cache_contact";

const defaultContactData: ContactPageData = {
  intro: {
    badge: "Let's Connect",
    title: "Let's Connect",
    subtitle: "Have a question about a product, order, or collaboration? We'd love to hear from you.",
    description: "We're here to help bring your handcrafted crochet dreams to life. Reach out for order questions, personalised gifts, workshop bookings, or wholesale inquiries.",
    cta_text: "Send Us a Message",
    cta_link: "#contact-form",
    is_active: true,
  },
  info: {
    badge: "Atelier Studio",
    title: "KNOTELLE Studio",
    subtitle: "Handmade with love in Bengaluru, India",
    custom_order_box: {
      title: "Explore Boutique Collection",
      text: "Browse our charming collection of handmade crochet flowers, plushies, bookmarks, and accessories.",
      link: "/shop",
      is_active: false,
    },
    items: [
      {
        id: 1,
        title: "Visit Our Studio",
        value: "12th Main Road, Indiranagar",
        address_line_2: "Bengaluru, Karnataka 560038, India",
        link: "https://maps.google.com/?q=Indiranagar+Bengaluru",
        icon: "MapPin",
        sort_order: 1,
        is_active: true,
      },
      {
        id: 2,
        title: "Direct Phone & WhatsApp",
        value: "+91 98765 43210",
        address_line_2: "Mon – Sat, 10:00 AM – 7:00 PM IST",
        link: "tel:+919876543210",
        icon: "Phone",
        sort_order: 2,
        is_active: true,
      },
      {
        id: 3,
        title: "Email Support",
        value: "hello@knotelle.com",
        address_line_2: "We usually reply within 24 hours",
        link: "mailto:hello@knotelle.com",
        icon: "Mail",
        sort_order: 3,
        is_active: true,
      },
      {
        id: 4,
        title: "Studio Working Hours",
        value: "Monday – Saturday: 10:00 AM – 7:00 PM",
        address_line_2: "Sunday: By Appointment Only",
        icon: "Clock",
        sort_order: 4,
        is_active: true,
      },
    ],
    is_active: true,
  },
  form: {
    badge: "Get In Touch",
    title: "Send Us a Message",
    subtitle: "Fill in your details and our team will get back to you promptly.",
    submit_btn_text: "Send Message",
    cta_text: "Send Message",
    success_title: "Message Sent!",
    success_message: "Thank you! Your message has been sent successfully. We will get back to you shortly.",
    error_message: "Something went wrong while sending your message. Please check the form and try again.",
    fields: [
      { key: "name", label: "Your Name", placeholder: "Enter your full name", required: true, is_active: true },
      { key: "email", label: "Email Address", placeholder: "Enter your email address", required: true, is_active: true },
      { key: "phone", label: "Phone / WhatsApp", placeholder: "Enter your 10-digit phone number (optional)", required: false, is_active: true },
      { key: "subject", label: "Subject", placeholder: "What is this regarding?", required: false, is_active: true },
      { key: "message", label: "How can we help you?", placeholder: "Tell us about your questions, orders, bulk inquiries...", required: true, is_active: true },
    ],
    is_active: true,
  },
  faqs: {
    badge: "Help & Support",
    title: "Frequently Asked Questions",
    subtitle: "Quick answers about our handmade creations, orders, and delivery.",
    items: [
      {
        id: 1,
        question: "How long does an order take to craft?",
        answer: "Standard handmade orders take between 2 to 4 business days to craft with love, after which they are express dispatched across India.",
        sort_order: 1,
        is_active: true,
      },
      {
        id: 2,
        question: "Do you ship internationally?",
        answer: "Currently we provide seamless Pan-India tracked delivery. For international inquiries, please get in touch with our studio team directly.",
        sort_order: 2,
        is_active: true,
      },
      {
        id: 3,
        question: "How do I care for my crochet flowers and plushies?",
        answer: "Gently dust with a soft makeup brush or blowdryer on cool air. Spot clean using cold water and mild baby detergent. Do not machine wash.",
        sort_order: 3,
        is_active: true,
      },
      {
        id: 4,
        question: "Can I choose my own flower bouquet combinations?",
        answer: "Yes! Explore our shop or contact us on WhatsApp with your dream flower list, colors, and wrap preference.",
        sort_order: 4,
        is_active: true,
      },
    ],
    is_active: true,
  },
};

function renderContactIcon(iconName?: string) {
  const name = (iconName || "MapPin").toLowerCase();
  switch (name) {
    case "phone":
      return <Phone className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "mail":
    case "email":
      return <Mail className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "clock":
    case "hours":
      return <Clock className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "messagecircle":
    case "whatsapp":
      return <MessageCircle className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "instagram":
      return (
        <svg className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
          <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
          <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
        </svg>
      );
    case "facebook":
      return (
        <svg className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
        </svg>
      );
    case "globe":
      return <Globe className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "sparkles":
      return <Sparkles className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "heart":
      return <Heart className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
    case "mappin":
    case "location":
    default:
      return <MapPin className="w-5 h-5 text-[#913638] shrink-0 mt-0.5" />;
  }
}

export function ContactPageView({ initialData }: { initialData?: ContactPageData | null }) {
  const { showToast } = useToast();
  const [cachedData, setCachedData] = useClientCache<ContactPageData | null>(
    CONTACT_CACHE_KEY,
    initialData || null
  );

  const contactData = cachedData || initialData || defaultContactData;
  const [openFaqId, setOpenFaqId] = useState<number | string | null>(null);

  // Form input states
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    subject: "",
    message: "",
  });
  const [isSent, setIsSent] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  useEffect(() => {
    let isMounted = true;
    const loadData = async () => {
      try {
        const data = await fetchContactPageData();
        if (data && isMounted) {
          setCachedData(data);
          setLocalCache(CONTACT_CACHE_KEY, data);
        }
      } catch (err) {
        console.warn("Live contact data fetch notice:", err);
      }
    };

    // 1. Fetch live contact data on mount
    loadData();

    // 2. Listen for real-time broadcast sync updates from Admin
    const cleanupListeners = setupAdminSyncListener(() => {
      loadData();
    });

    return () => {
      isMounted = false;
      cleanupListeners();
    };
  }, [setCachedData]);

  useEffect(() => {
    if (initialData) {
      setCachedData(initialData);
      setLocalCache(CONTACT_CACHE_KEY, initialData);
    }
  }, [initialData, setCachedData]);

  const intro = contactData?.intro || defaultContactData.intro;
  const info = contactData?.info || (contactData as any)?.contactDetails || (contactData as any)?.contactInfo || (contactData as any)?.details || defaultContactData.info;
  const formSettings = contactData?.form || (contactData as any)?.formSettings || defaultContactData.form;
  const faqs = contactData?.faqs || defaultContactData.faqs;

  // Active items
  const activeContactItems = (info?.items || defaultContactData.info.items || []).filter((item: any) => item.is_active !== false);
  const activeFaqs = (faqs?.items || defaultContactData.faqs.items || []).filter((faq: any) => faq.is_active !== false);

  // Field helpers
  const fields = formSettings?.fields || defaultContactData.form.fields || [];
  const nameField = fields.find((f: any) => f.key === "name") || { label: "Your Name", placeholder: "Enter your full name", required: true };
  const emailField = fields.find((f: any) => f.key === "email") || { label: "Email Address", placeholder: "Enter your email address", required: true };
  const phoneField = fields.find((f: any) => f.key === "phone") || { label: "Phone / WhatsApp", placeholder: "Enter your 10-digit phone number (optional)", required: false };
  const subjectField = fields.find((f: any) => f.key === "subject") || { label: "Subject", placeholder: "What is this regarding?", required: false };
  const messageField = fields.find((f: any) => f.key === "message") || { label: "How can we help you?", placeholder: "Tell us about your questions...", required: true };

  const customOrderBox = info?.custom_order_box || {
    title: (info as any)?.custom_order_box_title || "Looking for Custom Orders?",
    text: (info as any)?.custom_order_box_text || "Have a specific design, color palette, or bouquet arrangement in mind? Request a bespoke piece directly.",
    link: (info as any)?.custom_order_box_link || "/custom-order",
    is_active: (info as any)?.custom_order_box_active !== false,
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      const res = await submitContactForm({
        name: formData.name,
        email: formData.email,
        phone: formData.phone,
        subject: formData.subject,
        message: formData.message,
      });

      if (res.success) {
        setIsSent(true);
        showToast(
          formSettings.success_title || "Message Sent! 💌",
          formSettings.success_message || "Thank you for reaching out. We will get back to you shortly.",
          "success"
        );
      } else {
        showToast(
          "Submission Error",
          res.message || formSettings.error_message || "Something went wrong while sending your message.",
          "error"
        );
      }
    } catch {
      showToast(
        "Submission Error",
        formSettings.error_message || "Something went wrong while sending your message.",
        "error"
      );
    } finally {
      setIsSubmitting(false);
    }
  };

  const toggleFaq = (id: number | string) => {
    setOpenFaqId((prev) => (prev === id ? null : id));
  };

  return (
    <div suppressHydrationWarning className="bg-[#FFF9F6] min-h-screen py-8 lg:py-14">
      <div suppressHydrationWarning className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Breadcrumb Navigation */}
        <Breadcrumbs items={[{ label: intro.badge || intro.title || "Contact Studio" }]} className="mb-8" />

        {/* 1. Hero / Introduction Section */}
        {intro.is_active !== false && (
          <div className="text-center max-w-3xl mx-auto mb-14 animate-fadeIn">
            <div className="flex justify-center mb-2.5">
              <BotanicalFlourish className="w-24 h-5 text-[#EFB8B0]" />
            </div>
            
            {intro.badge && (
              <span className="inline-block px-3.5 py-1 rounded-full text-xs font-bold bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC] uppercase tracking-wider mb-3">
                {intro.badge}
              </span>
            )}

            <h1 className="font-serif-luxury text-3xl sm:text-5xl font-bold text-[#2E211E] tracking-tight mb-3">
              {intro.title || ""}
            </h1>

            {intro.subtitle && (
              <p className="text-sm sm:text-base text-[#786864] font-medium max-w-2xl mx-auto mb-3">
                {intro.subtitle}
              </p>
            )}

            {intro.description && (
              <p className="text-xs sm:text-sm text-[#786864]/90 max-w-xl mx-auto leading-relaxed">
                {intro.description}
              </p>
            )}

            {intro.cta_text && intro.cta_link && (
              <div className="mt-5">
                <a
                  href={intro.cta_link}
                  className="inline-flex items-center gap-2 px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-bold hover:bg-[#74292B] shadow-xs hover:shadow-md transition-all cursor-pointer"
                >
                  <span>{intro.cta_text}</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </a>
              </div>
            )}
          </div>
        )}

        {/* 2-Column: Studio Details + Send Us a Message Form */}
        <div id="contact-form" className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-start mb-20">
          
          {/* Left: Atelier Studio Information (5 Columns) */}
          {info.is_active !== false && (
            <div className="lg:col-span-5 bg-white rounded-3xl border border-[#E7D1CC] p-7 sm:p-8 shadow-boutique space-y-6">
              <div className="flex items-center gap-2.5 pb-4 border-b border-[#E7D1CC]">
                <FlowerIcon className="w-6 h-6 text-[#913638]" />
                <div>
                  {info.badge && (
                    <span className="text-[10px] font-bold text-[#913638] uppercase tracking-wider block">
                      {info.badge}
                    </span>
                  )}
                  <h2 className="font-serif-luxury text-xl font-bold text-[#2E211E]">
                    {info.title || "KNOTELLE Studio"}
                  </h2>
                </div>
              </div>

              {info.subtitle && (
                <p className="text-xs text-[#786864] italic">
                  {info.subtitle}
                </p>
              )}

              {/* Dynamic Contact Details Items */}
              <div className="space-y-4 text-xs sm:text-sm text-[#786864]">
                {activeContactItems.length > 0 ? (
                  activeContactItems.map((item: ContactDetailItem) => (
                    <div key={item.id} className="flex items-start gap-3.5 p-3 rounded-2xl bg-[#FFF9F6]/60 border border-[#E7D1CC]/50 hover:border-[#E7D1CC] transition-colors">
                      {renderContactIcon(item.icon || item.icon_name)}
                      <div className="flex-1 min-w-0">
                        <strong className="text-[#2E211E] block font-semibold text-xs sm:text-sm mb-0.5">
                          {item.title}
                        </strong>
                        {item.link || item.cta_link ? (
                          <a
                            href={item.link || item.cta_link}
                            target={item.link?.startsWith("http") ? "_blank" : undefined}
                            rel={item.link?.startsWith("http") ? "noopener noreferrer" : undefined}
                            className="text-[#913638] hover:text-[#74292B] font-medium transition-colors break-words block hover:underline"
                          >
                            {item.value}
                          </a>
                        ) : (
                          <p className="text-[#2E211E] font-medium break-words">
                            {item.value}
                          </p>
                        )}
                        {item.address_line_2 && (
                          <p className="text-[11px] text-[#786864] mt-0.5 leading-relaxed">
                            {item.address_line_2}
                          </p>
                        )}
                      </div>
                    </div>
                  ))
                ) : (
                  <p className="text-xs text-[#786864]">Contact details will appear here.</p>
                )}
              </div>
            </div>
          )}

          {/* Right: Send Us a Message Form (7 Columns) */}
          {formSettings.is_active !== false && (
            <div className="lg:col-span-7 bg-white rounded-3xl border border-[#E7D1CC] p-7 sm:p-8 shadow-boutique">
              {formSettings.badge && (
                <span className="inline-block px-3 py-0.5 rounded-full text-[11px] font-bold bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC] uppercase tracking-wider mb-2">
                  {formSettings.badge}
                </span>
              )}
              <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E] mb-1.5">
                {formSettings.title || "Send Us a Message"}
              </h2>
              {formSettings.subtitle && (
                <p className="text-xs sm:text-sm text-[#786864] mb-6">
                  {formSettings.subtitle}
                </p>
              )}

              {isSent ? (
                <div className="p-8 sm:p-10 rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] text-center space-y-4 animate-fadeIn">
                  <CheckCircle2 className="w-12 h-12 text-[#913638] mx-auto" />
                  <h3 className="font-serif-luxury text-2xl font-bold text-[#2E211E]">
                    {formSettings.success_title || "Message Sent!"}
                  </h3>
                  <p className="text-xs sm:text-sm text-[#786864] max-w-md mx-auto leading-relaxed">
                    {formSettings.success_message || `Thank you, ${formData.name}. We have received your message and will respond shortly.`}
                  </p>
                  <button
                    type="button"
                    onClick={() => {
                      setIsSent(false);
                      setFormData({ name: "", email: "", phone: "", subject: "", message: "" });
                    }}
                    className="mt-4 px-6 py-2.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs transition-all cursor-pointer"
                  >
                    Send Another Message
                  </button>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                  {/* Name & Email */}
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        {nameField.label} {nameField.required && <span className="text-[#913638]">*</span>}
                      </label>
                      <input
                        type="text"
                        required={nameField.required}
                        value={formData.name}
                        onChange={(e) => handleInputChange("name", e.target.value)}
                        placeholder={nameField.placeholder || "Your full name"}
                        className="w-full px-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638] focus:bg-white transition-colors"
                      />
                    </div>
                    <div className="space-y-1">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        {emailField.label} {emailField.required && <span className="text-[#913638]">*</span>}
                      </label>
                      <input
                        type="email"
                        required={emailField.required}
                        value={formData.email}
                        onChange={(e) => handleInputChange("email", e.target.value)}
                        placeholder={emailField.placeholder || "you@example.com"}
                        className="w-full px-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638] focus:bg-white transition-colors"
                      />
                    </div>
                  </div>

                  {/* Phone / WhatsApp */}
                  <div className="space-y-1">
                    <div className="flex items-center justify-between">
                      <label className="text-xs font-semibold text-[#2E211E] block">
                        {phoneField.label} {phoneField.required && <span className="text-[#913638]">*</span>}
                      </label>
                      {formData.phone.length > 0 && (
                        <span className="text-[11px] text-[#786864] font-mono">
                          {formData.phone.length}/10 digits
                        </span>
                      )}
                    </div>
                    <div className="relative flex items-center rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] focus-within:border-[#913638] focus-within:ring-1 focus-within:ring-[#913638] focus-within:bg-white transition-all overflow-hidden">
                      <div className="flex items-center gap-1.5 px-3.5 py-2.5 bg-[#FCE9E5] border-r border-[#E7D1CC] text-xs font-bold text-[#913638] shrink-0 select-none">
                        <span className="text-sm leading-none">🇮🇳</span>
                        <span>+91</span>
                      </div>
                      <input
                        type="tel"
                        inputMode="numeric"
                        pattern="[0-9]{10}"
                        maxLength={10}
                        required={phoneField.required}
                        value={formData.phone}
                        onChange={(e) => {
                          const digitsOnly = e.target.value.replace(/\D/g, "").slice(0, 10);
                          handleInputChange("phone", digitsOnly);
                        }}
                        placeholder={phoneField.placeholder || "98765 43210"}
                        className="w-full px-3.5 py-2.5 bg-transparent text-xs text-[#2E211E] placeholder-[#786864]/50 focus:outline-none"
                      />
                    </div>
                    {formData.phone.length > 0 && formData.phone.length < 10 && (
                      <p className="text-[11px] text-[#913638] pt-0.5">
                        Please enter a complete 10-digit phone number ({10 - formData.phone.length} more digits needed)
                      </p>
                    )}
                  </div>

                  {/* Subject */}
                  <div className="space-y-1">
                    <label className="text-xs font-semibold text-[#2E211E] block">
                      {subjectField.label} {subjectField.required && <span className="text-[#913638]">*</span>}
                    </label>
                    <input
                      type="text"
                      required={subjectField.required}
                      value={formData.subject}
                      onChange={(e) => handleInputChange("subject", e.target.value)}
                      placeholder={subjectField.placeholder || "What is this regarding?"}
                      className="w-full px-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638] focus:bg-white transition-colors"
                    />
                  </div>

                  {/* Message */}
                  <div className="space-y-1">
                    <label className="text-xs font-semibold text-[#2E211E] block">
                      {messageField.label} {messageField.required && <span className="text-[#913638]">*</span>}
                    </label>
                    <textarea
                      required={messageField.required}
                      rows={4}
                      value={formData.message}
                      onChange={(e) => handleInputChange("message", e.target.value)}
                      placeholder={messageField.placeholder || "Tell us how we can help you..."}
                      className="w-full px-4 py-2.5 rounded-xl bg-[#FFF9F6] border border-[#E7D1CC] text-xs text-[#2E211E] focus:outline-none focus:border-[#913638] focus:bg-white transition-colors leading-relaxed"
                    />
                  </div>

                  {/* Submit Button */}
                  <button
                    type="submit"
                    disabled={isSubmitting}
                    className="px-8 py-3.5 rounded-full bg-[#913638] text-white text-xs font-semibold hover:bg-[#74292B] active:scale-[0.98] shadow-xs hover:shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
                  >
                    {isSubmitting ? (
                      <>
                        <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        <span>Sending Message...</span>
                      </>
                    ) : (
                      <>
                        <Send className="w-3.5 h-3.5" />
                        <span>{formSettings.cta_text || formSettings.submit_btn_text || "Send Message"}</span>
                      </>
                    )}
                  </button>
                </form>
              )}
            </div>
          )}

        </div>

        {/* 3. Frequently Asked Questions (FAQs) Accordion Section */}
        {faqs.is_active !== false && activeFaqs.length > 0 && (
          <div className="bg-white rounded-3xl border border-[#E7D1CC] p-6 sm:p-12 shadow-boutique max-w-4xl mx-auto">
            <div className="text-center max-w-xl mx-auto mb-8">
              {faqs.badge && (
                <span className="inline-block px-3 py-0.5 rounded-full text-[11px] font-bold bg-[#FCE9E5] text-[#913638] border border-[#E7D1CC] uppercase tracking-wider mb-2">
                  {faqs.badge}
                </span>
              )}
              <h2 className="font-serif-luxury text-2xl sm:text-3xl font-bold text-[#2E211E]">
                {faqs.title || "Frequently Asked Questions"}
              </h2>
              {faqs.subtitle && (
                <p className="text-xs sm:text-sm text-[#786864] mt-1">
                  {faqs.subtitle}
                </p>
              )}
            </div>

            <div className="space-y-3.5">
              {activeFaqs.map((faq: ContactFaqItem, idx: number) => {
                const isOpen = openFaqId === faq.id || (openFaqId === null && idx === 0);
                return (
                  <div
                    key={faq.id}
                    className="rounded-2xl bg-[#FFF9F6] border border-[#E7D1CC] overflow-hidden transition-all duration-200"
                  >
                    <button
                      type="button"
                      onClick={() => toggleFaq(faq.id)}
                      className="w-full p-4 sm:p-5 flex items-center justify-between text-left gap-4 hover:bg-[#FCE9E5]/40 transition-colors cursor-pointer"
                    >
                      <span className="font-serif-luxury text-sm sm:text-base font-bold text-[#2E211E]">
                        {faq.question || faq.title}
                      </span>
                      <ChevronDown
                        className={`w-4 h-4 text-[#913638] shrink-0 transition-transform duration-200 ${
                          isOpen ? "rotate-180" : ""
                        }`}
                      />
                    </button>
                    {isOpen && (
                      <div className="px-4 sm:px-5 pb-4 sm:pb-5 pt-0 text-xs sm:text-sm text-[#786864] leading-relaxed border-t border-[#E7D1CC]/40 animate-fadeIn">
                        <p className="pt-3">{faq.answer || faq.description}</p>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
