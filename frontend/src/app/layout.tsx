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
import { PanelConflictGuard } from "@/components/layout/PanelConflictGuard";

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
        {/* Meta Pixel Code */}
        <script
          dangerouslySetInnerHTML={{
            __html: `!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1005369632636183');
fbq('track', 'PageView');`,
          }}
        />
        <noscript>
          <img
            height="1"
            width="1"
            style={{ display: "none" }}
            src="https://www.facebook.com/tr?id=1005369632636183&ev=PageView&noscript=1"
            alt=""
          />
        </noscript>
        {/* End Meta Pixel Code */}
      </head>
      <body suppressHydrationWarning className="min-h-full flex flex-col bg-[#FFF8F5] text-[#2C201D] font-sans">
        <PanelConflictGuard />
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
