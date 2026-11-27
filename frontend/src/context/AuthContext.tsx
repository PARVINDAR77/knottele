"use client";

import React, { createContext, useContext, useState, useEffect, useCallback } from "react";
import { Order, ShippingAddress } from "@/types/order";
import { useToast } from "./ToastContext";
import {
  fetchCustomerOrders,
  fetchCustomerAddresses,
  fetchCustomerProfile,
  saveCustomerAddress,
  logoutCustomer,
  deleteCustomerAddress as apiDeleteAddress,
  setDefaultCustomerAddress as apiSetDefaultAddress,
  getFullPath,
} from "@/lib/api";

export interface UserProfile {
  name: string;
  email: string;
  phone: string;
  avatar: string;
  addresses: (ShippingAddress & { id: string; isDefault?: boolean })[];
}

interface AuthContextType {
  user: UserProfile | null;
  isLoggedIn: boolean;
  isAuthLoading: boolean;
  orders: Order[];
  login: (email: string, userData?: Partial<UserProfile>, token?: string) => void;
  logout: () => void;
  updateProfile: (data: Partial<UserProfile>) => void;
  addAddress: (address: Omit<ShippingAddress, "id">) => Promise<void>;
  deleteAddress: (id: string) => Promise<void>;
  setDefaultAddress: (id: string) => Promise<void>;
  addOrder: (order: Order) => void;
  refetchOrders: () => Promise<void>;
  refetchAddresses: () => Promise<void>;
  refetchProfile: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [isAuthLoading, setIsAuthLoading] = useState<boolean>(true);
  const [orders, setOrders] = useState<Order[]>([]);
  const { showToast } = useToast();

  const refetchAddresses = useCallback(async () => {
    if (typeof window === "undefined") return;
    const token = localStorage.getItem("knotelle_customer_token");
    if (!token) return;

    const liveAddresses = await fetchCustomerAddresses();
    if (Array.isArray(liveAddresses)) {
      setUser((prev) => {
        if (!prev) return null;
        const updated = { ...prev, addresses: liveAddresses };
        try {
          localStorage.setItem("knotelle_customer_user", JSON.stringify(updated));
        } catch {}
        return updated;
      });
    }
  }, []);

  const refetchProfile = useCallback(async () => {
    if (typeof window === "undefined") {
      setIsAuthLoading(false);
      return;
    }

    const token = localStorage.getItem("knotelle_customer_token");
    if (!token) {
      setUser(null);
      setIsAuthLoading(false);
      return;
    }

    try {
      const res = await fetchCustomerProfile();
      if (res.success && res.user) {
        const u = res.user;
        const liveAddrs = (await fetchCustomerAddresses()) || [];
        const liveUser: UserProfile = {
          name: u.name || "",
          email: u.email || "",
          phone: u.mobile || u.phone || "",
          avatar: u.avatar || "",
          addresses: Array.isArray(liveAddrs) ? liveAddrs : [],
        };
        setUser(liveUser);
        localStorage.setItem("knotelle_customer_user", JSON.stringify(liveUser));
      } else {
        // Token invalid or expired - clear auth state
        localStorage.removeItem("knotelle_customer_token");
        localStorage.removeItem("knotelle_customer_user");
        setUser(null);
      }
    } catch (err) {
      console.warn("Profile fetch fallback on refresh:", err);
      // Fallback to local storage if offline
      const stored = localStorage.getItem("knotelle_customer_user");
      if (stored) {
        try {
          setUser(JSON.parse(stored));
        } catch {}
      }
    } finally {
      setIsAuthLoading(false);
    }
  }, []);

  // Synchronous hydration on mount + API token validation
  useEffect(() => {
    if (typeof window !== "undefined") {
      try {
        const stored = localStorage.getItem("knotelle_customer_user");
        const token = localStorage.getItem("knotelle_customer_token");
        if (stored && token) {
          const parsed = JSON.parse(stored);
          if (parsed.avatar && parsed.avatar.includes("unsplash.com")) {
            parsed.avatar = "";
          }
          if (!Array.isArray(parsed.addresses)) {
            parsed.addresses = [];
          }
          setUser(parsed);
        }
      } catch {}
    }

    refetchProfile();
  }, [refetchProfile]);

  const refetchOrders = useCallback(async () => {
    let targetEmail = user?.email;
    if (!targetEmail && typeof window !== "undefined") {
      targetEmail = localStorage.getItem("knotelle_guest_email") || undefined;
    }
    const liveOrders = await fetchCustomerOrders(targetEmail);
    if (Array.isArray(liveOrders)) {
      setOrders(liveOrders);
    }
  }, [user?.email]);

  useEffect(() => {
    if (!user) return;
    refetchOrders();
    const interval = setInterval(refetchOrders, 5000);
    const handleFocus = () => {
      refetchOrders();
      refetchAddresses();
    };
    window.addEventListener("focus", handleFocus);
    return () => {
      clearInterval(interval);
      window.removeEventListener("focus", handleFocus);
    };
  }, [user, refetchOrders, refetchAddresses]);

  const login = (email: string, userData?: Partial<UserProfile>, token?: string) => {
    const nameToUse = userData?.name || (email && email.includes("@") ? email.split("@")[0].replace(".", " ").replace(/(^\w|\s\w)/g, (m) => m.toUpperCase()) : "Customer");
    const newUser: UserProfile = {
      name: nameToUse,
      email: email || "",
      phone: userData?.phone || (userData as any)?.mobile || "",
      avatar: userData?.avatar || "",
      addresses: userData?.addresses || [],
    };

    setUser(newUser);
    setIsAuthLoading(false);

    if (typeof window !== "undefined") {
      localStorage.setItem("knotelle_customer_user", JSON.stringify(newUser));
      if (token) {
        localStorage.setItem("knotelle_customer_token", token);
      }
    }
    showToast("Welcome! ✨", "You are now logged in to KNOTELLE.", "success");
    
    // Fetch user's saved addresses from backend database
    setTimeout(() => {
      refetchProfile();
    }, 100);
  };

  const logout = async () => {
    try {
      await logoutCustomer();
    } catch {}

    setUser(null);
    setOrders([]);
    setIsAuthLoading(false);
    if (typeof window !== "undefined") {
      localStorage.removeItem("knotelle_customer_user");
      localStorage.removeItem("knotelle_customer_token");
      localStorage.removeItem("knotelle_wishlist");
      localStorage.removeItem("knotelle_cart");
      localStorage.removeItem("knotelle_guest_email");
      localStorage.removeItem("knotelle_pending_wishlist");

      showToast("Signed Out", "You have been signed out successfully.", "info");
      window.location.href = getFullPath("/login");
    }
  };

  const updateProfile = (data: Partial<UserProfile>) => {
    if (user) {
      const updated = { ...user, ...data };
      setUser(updated);
      if (typeof window !== "undefined") {
        localStorage.setItem("knotelle_customer_user", JSON.stringify(updated));
      }
      showToast("Profile Updated 🌸", "Your details have been saved.", "success");
    }
  };

  const addAddress = async (address: Omit<ShippingAddress, "id">) => {
    if (!user) return;
    const res = await saveCustomerAddress(address);
    if (res.success && res.address) {
      setUser((prev) => {
        if (!prev) return null;
        const updatedAddrs = [...prev.addresses, res.address];
        const updatedUser = { ...prev, addresses: updatedAddrs };
        if (typeof window !== "undefined") {
          localStorage.setItem("knotelle_customer_user", JSON.stringify(updatedUser));
        }
        return updatedUser;
      });
      showToast("Address Saved", "New shipping address added.", "success");
    } else {
      // Fallback local update if API is unreachable
      const newId = `addr-${Date.now()}`;
      const newAddr = { ...address, id: newId, isDefault: user.addresses.length === 0 };
      const updatedUser = { ...user, addresses: [...user.addresses, newAddr] };
      setUser(updatedUser);
      if (typeof window !== "undefined") {
        localStorage.setItem("knotelle_customer_user", JSON.stringify(updatedUser));
      }
      showToast("Address Saved", "New shipping address added.", "success");
    }
  };

  const deleteAddress = async (id: string) => {
    if (!user) return;
    await apiDeleteAddress(id);
    setUser((prev) => {
      if (!prev) return null;
      const updatedAddrs = prev.addresses.filter((a) => a.id !== id);
      const updatedUser = { ...prev, addresses: updatedAddrs };
      if (typeof window !== "undefined") {
        localStorage.setItem("knotelle_customer_user", JSON.stringify(updatedUser));
      }
      return updatedUser;
    });
    showToast("Address Removed", "Address deleted from your address book.", "info");
  };

  const setDefaultAddress = async (id: string) => {
    if (!user) return;
    await apiSetDefaultAddress(id);
    setUser((prev) => {
      if (!prev) return null;
      const updatedAddrs = prev.addresses.map((a) => ({
        ...a,
        isDefault: a.id === id,
      }));
      const updatedUser = { ...prev, addresses: updatedAddrs };
      if (typeof window !== "undefined") {
        localStorage.setItem("knotelle_customer_user", JSON.stringify(updatedUser));
      }
      return updatedUser;
    });
    showToast("Default Updated", "Default shipping address set.", "success");
  };

  const addOrder = (newOrder: Order) => {
    setOrders((prev) => [newOrder, ...prev]);
    setTimeout(() => {
      refetchOrders();
    }, 800);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoggedIn: !!user,
        isAuthLoading,
        orders,
        login,
        logout,
        updateProfile,
        addAddress,
        deleteAddress,
        setDefaultAddress,
        addOrder,
        refetchOrders,
        refetchAddresses,
        refetchProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}

