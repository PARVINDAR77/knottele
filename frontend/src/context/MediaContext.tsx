"use client";

import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from "react";
import { fetchHomepageMedia, HomepageMedia } from "@/lib/api";
import { setupAdminSyncListener } from "@/lib/cache";

const MEDIA_CACHE_KEY = "knotelle_cache_media";

interface MediaContextType {
  media: HomepageMedia | null;
  loading: boolean;
  lastUpdated: number;
  refetchMedia: () => Promise<void>;
}

const MediaContext = createContext<MediaContextType | undefined>(undefined);

export function MediaProvider({
  children,
  initialMedia = null,
}: {
  children: React.ReactNode;
  initialMedia?: HomepageMedia | null;
}) {
  const [media, setMedia] = useState<HomepageMedia | null>(initialMedia);
  const [loading, setLoading] = useState<boolean>(!initialMedia);
  const [lastUpdated, setLastUpdated] = useState<number>(Date.now());
  const isFetchingRef = useRef(false);

  // Check localStorage once on client mount
  useEffect(() => {
    try {
      const cached = localStorage.getItem(MEDIA_CACHE_KEY);
      if (cached) {
        const parsed = JSON.parse(cached);
        if (parsed) setMedia(parsed);
      }
    } catch {}
  }, []);

  const refetchMedia = useCallback(async () => {
    if (isFetchingRef.current) return;
    isFetchingRef.current = true;
    try {
      const data = await fetchHomepageMedia();
      if (data) {
        setMedia(data);
        setLastUpdated(Date.now());
        try {
          localStorage.setItem(MEDIA_CACHE_KEY, JSON.stringify(data));
        } catch {}
      }
    } catch (err) {
      console.warn("Live media sync notice:", err);
    } finally {
      isFetchingRef.current = false;
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    // 1. Initial live fetch to ensure cache freshness
    refetchMedia();

    // 2. Setup unified real-time admin sync listeners (BroadcastChannel, storage event, visibility, focus)
    const cleanupListeners = setupAdminSyncListener(() => {
      refetchMedia();
    });

    // 3. Fallback background polling (every 60 seconds when visible)
    const pollTimer = setInterval(() => {
      if (document.visibilityState === "visible") {
        refetchMedia();
      }
    }, 60000);

    return () => {
      cleanupListeners();
      clearInterval(pollTimer);
    };
  }, [refetchMedia]);

  return (
    <MediaContext.Provider value={{ media, loading, lastUpdated, refetchMedia }}>
      {children}
    </MediaContext.Provider>
  );
}

export function useWebsiteMedia() {
  const context = useContext(MediaContext);
  if (!context) {
    throw new Error("useWebsiteMedia must be used within a MediaProvider");
  }
  return context;
}
