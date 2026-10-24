import type { Metadata } from "next";
import "./globals.css";
import { ToastProvider } from "@/context/ToastContext";
import { CartProvider } from "@/context/CartContext";
import { WishlistProvider } from "@/context/WishlistContext";
import { AuthProvider } from "@/context/AuthContext";
import { MediaProvider } from "@/context/MediaContext";
import { AnnouncementBar } from "@/components/layout/AnnouncementBar";
import { Navbar } from "@/components/layout/Navbar";
import { Footer } from "@/components/layout/Footer";
import { CartDrawer } from "@/components/layout/CartDrawer";

const serifFont = { variable: "font-serif-luxury" };
const sansFont = { variable: "font-sans" };
const scriptFont = { variable: "font-script-boutique" };

export const metadata: Metadata = {
  title: "KNOTELLE | Customized Crochet Products & Handmade Boutique",
  description: "Handmade crochet products that add warmth to your everyday life. Custom bouquets, bags, soft toys, keychains, phone covers, and personalized gifts made with love.",
  keywords: ["crochet", "handmade", "custom crochet", "crochet bouquet", "amigurumi", "granny square bag", "crochet keychain", "knotelle"],
  openGraph: {
    title: "KNOTELLE | Little Stitches, Big Happiness",
    description: "Handmade crochet boutique specializing in customized creations, forever floral bouquets, and bespoke accessories.",
    url: "https://knotelle.com",
    siteName: "KNOTELLE",
    locale: "en_IN",
    type: "website",
  },
  icons: {
    icon: [
      { url: "/images/logo/Logo_1.png?v=2", type: "image/png" },
      { url: "/icon.png?v=2", type: "image/png" },
    ],
    shortcut: "/images/logo/Logo_1.png?v=2",
    apple: "/images/logo/Logo_1.png?v=2",
  },
};

import { fetchHomepageMedia } from "@/lib/api";

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const initialMedia = await fetchHomepageMedia();

  return (
    <html
      lang="en"
      suppressHydrationWarning
      className={`${serifFont.variable} ${sansFont.variable} ${scriptFont.variable} h-full antialiased`}
    >
      <head>
        <link rel="icon" href="/images/logo/Logo_1.png?v=2" type="image/png" />
        <link rel="icon" href="/icon.png?v=2" type="image/png" />
        <link rel="apple-touch-icon" href="/images/logo/Logo_1.png?v=2" />
      </head>
      <body suppressHydrationWarning className="min-h-full flex flex-col bg-[#FFF8F5] text-[#2C201D] font-sans">
        <MediaProvider initialMedia={initialMedia}>
          <ToastProvider>
            <AuthProvider>
              <CartProvider>
                <WishlistProvider>
                  <AnnouncementBar />
                  <Navbar />
                  <main className="flex-1">{children}</main>
                  <Footer />
                  <CartDrawer />
                </WishlistProvider>
              </CartProvider>
            </AuthProvider>
          </ToastProvider>
        </MediaProvider>
      </body>
    </html>
  );
}
