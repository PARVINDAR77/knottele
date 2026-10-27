"use client";

import React from "react";

export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>KNOTELLE | Boutique Error Recovery</title>
      </head>
      <body style={{ margin: 0, fontFamily: "serif, system-ui, sans-serif", backgroundColor: "#FFF8F5", color: "#2C201D", display: "flex", minHeight: "100vh", alignItems: "center", justifyContent: "center", padding: "16px" }}>
        <div style={{ maxWidth: "440px", width: "100%", backgroundColor: "#FFFFFF", border: "1px solid #E7D1CC", borderRadius: "24px", padding: "32px 24px", textAlign: "center", boxShadow: "0 10px 25px -5px rgba(145, 54, 56, 0.08)" }}>
          <div style={{ width: "64px", height: "64px", borderRadius: "50%", backgroundColor: "#FCE9E5", border: "1px solid #E7D1CC", display: "flex", alignItems: "center", justifyContent: "center", margin: "0 auto 16px auto", color: "#913638", fontSize: "28px" }}>
            🧶
          </div>
          <h1 style={{ fontSize: "22px", fontWeight: "bold", color: "#2E211E", margin: "0 0 8px 0" }}>
            A Stitch Gently Tangled
          </h1>
          <p style={{ fontSize: "13px", color: "#786864", lineHeight: "1.5", margin: "0 0 24px 0" }}>
            We encountered a momentary glitch. Please reload to resume your boutique experience.
          </p>
          <div style={{ display: "flex", gap: "10px", justifyContent: "center", flexWrap: "wrap" }}>
            <button
              onClick={() => reset()}
              style={{ padding: "10px 24px", backgroundColor: "#913638", color: "#FFFFFF", border: "none", borderRadius: "9999px", fontSize: "13px", fontWeight: "600", cursor: "pointer" }}
            >
              Try Again
            </button>
            <a
              href="/"
              style={{ padding: "10px 20px", backgroundColor: "#FFFFFF", color: "#913638", border: "1px solid #E7D1CC", borderRadius: "9999px", fontSize: "13px", fontWeight: "600", textDecoration: "none" }}
            >
              Back to Home
            </a>
          </div>
        </div>
      </body>
    </html>
  );
}
