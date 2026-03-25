import * as SecureStore from "expo-secure-store";
import { useState, useEffect, useCallback } from "react";
import apiClient, { TOKEN_KEY } from "./api-client";

interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
}

interface Session {
  user: User;
  token: string;
}

// ── Auth functions ──

export async function login(email: string, password: string): Promise<Session> {
  const { data } = await apiClient.post("/login", { email, password });
  const token = data?.data?.token ?? data?.token;
  const user = data?.data?.user ?? data?.user;

  if (!token) throw new Error("No token received");
  await SecureStore.setItemAsync(TOKEN_KEY, token);
  return { user, token };
}

export async function register(
  name: string,
  email: string,
  password: string,
  password_confirmation: string,
): Promise<Session> {
  const { data } = await apiClient.post("/register", {
    name,
    email,
    password,
    password_confirmation,
  });
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

export async function forgotPassword(phone: string) {
  const { data } = await apiClient.post("/forgot-password", { phone });
  return data;
}

export async function verifyOtp(phone: string, otp: string) {
  const { data } = await apiClient.post("/verify-otp", { phone, otp });
  return data;
}

export async function resetPassword(
  phone: string,
  otp: string,
  password: string,
  password_confirmation: string,
) {
  const { data } = await apiClient.post("/reset-password", {
    phone,
    otp,
    password,
    password_confirmation,
  });
  return data;
}

export async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(TOKEN_KEY);
}

// ── React hook ──

export function useSession() {
  const [session, setSession] = useState<Session | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const checkSession = useCallback(async () => {
    try {
      const token = await SecureStore.getItemAsync(TOKEN_KEY);
      if (!token) {
        setSession(null);
        return;
      }

      const { data } = await apiClient.get("/user");
      const user = data?.data ?? data;
      setSession({ user, token });
    } catch {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
      setSession(null);
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    checkSession();
  }, [checkSession]);

  const signOut = useCallback(async () => {
    await logout();
    setSession(null);
  }, []);

  return { data: session, isLoading, refetch: checkSession, signOut };
}
