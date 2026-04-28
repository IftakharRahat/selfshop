import * as SecureStore from "expo-secure-store";
import React, { createContext, useContext, useState, useEffect, useCallback } from "react";
import apiClient, { TOKEN_KEY } from "./api-client";

interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
}

interface VendorInfo {
  status: string;          // "approved" | "pending" | "rejected" etc.
  is_verified_badge?: boolean;
  company_name?: string;
}

interface Session {
  user: User;
  token: string;
}

interface AuthContextValue {
  session: Session | null;
  /** True while the initial auth + vendor check is running (splash stays up) */
  isLoading: boolean;
  /** True once the vendor profile API call completes and confirms vendor access */
  isVendor: boolean;
  /** True while vendor profile is being checked (after login, before result) */
  isVendorChecking: boolean;
  vendorInfo: VendorInfo | null;
  signIn: (session: Session) => void;
  signOut: () => Promise<void>;
  refetch: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue>({
  session: null,
  isLoading: true,
  isVendor: false,
  isVendorChecking: false,
  vendorInfo: null,
  signIn: () => {},
  signOut: async () => {},
  refetch: async () => {},
});

// ── Auth functions (plain, no state) ──

export async function login(phone: string, password: string): Promise<Session> {
  const { data } = await apiClient.post("/login", { email: phone, password });
  const token = data?.data?.token ?? data?.token;
  const user = data?.data?.user ?? data?.user;

  if (!token) throw new Error("No token received");
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  return { user, token };
}

export async function register(payload: {
  name: string;
  phone: string;
  password: string;
  company_name: string;
  business_type?: string;
  pickup_city_id?: number;
  pickup_zone_id?: number;
  pickup_area_id?: number;
  pickup_address?: string;
}): Promise<Session> {
  const { data } = await apiClient.post("/vendor/register", payload);
  const token = data?.data?.token ?? data?.token;
  const user = data?.data?.user ?? data?.user;

  if (!token) throw new Error("No token received");
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  return { user, token };
}

export async function logout(): Promise<void> {
  try {
    await apiClient.post("/logout");
  } catch {
    // ignore — token may already be invalid
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
}

/**
 * Check if the logged-in user has a vendor profile.
 * Returns the vendor info if they do, null if they don't.
 */
async function fetchVendorProfile(): Promise<VendorInfo | null> {
  try {
    const { data } = await apiClient.get("/vendor/profile");
    const vendor = data?.data?.vendor;
    if (vendor) {
      return {
        status: vendor.status ?? "pending",
        is_verified_badge: vendor.is_verified_badge ?? false,
        company_name: vendor.company_name ?? "",
      };
    }
    return null;
  } catch {
    return null;
  }
}

// ── Provider ──

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<Session | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isVendorChecking, setIsVendorChecking] = useState(false);
  const [vendorInfo, setVendorInfo] = useState<VendorInfo | null>(null);
  const [isVendor, setIsVendor] = useState(false);

  const checkSession = useCallback(async () => {
    try {
      const token = await SecureStore.getItemAsync(TOKEN_KEY);
      if (!token) {
        setSession(null);
        setIsVendor(false);
        setVendorInfo(null);
        return;
      }
      // Fetch user info
      const { data } = await apiClient.get("/user");
      const user = data?.data ?? data;
      setSession({ user, token });

      // Verify vendor status — do this BEFORE setting isLoading to false
      const vendor = await fetchVendorProfile();
      setVendorInfo(vendor);
      setIsVendor(vendor !== null);
    } catch {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      setSession(null);
      setIsVendor(false);
      setVendorInfo(null);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    checkSession();
  }, [checkSession]);

  const signIn = useCallback((s: Session) => {
    setSession(s);
    setIsVendorChecking(true);
    // After login, check vendor profile
    fetchVendorProfile().then((vendor) => {
      setVendorInfo(vendor);
      setIsVendor(vendor !== null);
      setIsVendorChecking(false);
    });
  }, []);

  const signOut = useCallback(async () => {
    await logout();
    setSession(null);
    setIsVendor(false);
    setVendorInfo(null);
    setIsVendorChecking(false);
  }, []);

  return (
    <AuthContext.Provider value={{
      session,
      isLoading,
      isVendor,
      isVendorChecking,
      vendorInfo,
      signIn,
      signOut,
      refetch: checkSession,
    }}>
      {children}
    </AuthContext.Provider>
  );
}

// ── Hook ──

export function useSession() {
  const ctx = useContext(AuthContext);
  return {
    data: ctx.session,
    isLoading: ctx.isLoading,
    isVendor: ctx.isVendor,
    isVendorChecking: ctx.isVendorChecking,
    vendorInfo: ctx.vendorInfo,
    signIn: ctx.signIn,
    signOut: ctx.signOut,
    refetch: ctx.refetch,
  };
}
