import * as SecureStore from "expo-secure-store";
import { useCallback } from "react";
import { useQuery } from "@tanstack/react-query";
import apiClient, { TOKEN_KEY } from "./api-client";
import { queryClient } from "./query-client";

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
  const session = { user, token };
  queryClient.setQueryData(["session"], session);
  queryClient.setQueryData(["auth-token"], token);
  return session;
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
  const session = { user, token };
  queryClient.setQueryData(["session"], session);
  queryClient.setQueryData(["auth-token"], token);
  return session;
}

export async function logout(): Promise<void> {
  try {
    await apiClient.post("/logout");
  } catch {
    // ignore — token may already be invalid
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  queryClient.setQueryData(["session"], null);
  queryClient.setQueryData(["auth-token"], null);
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

async function readSession(): Promise<Session | null> {
  try {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    if (!token) return null;

    const { data } = await apiClient.get("/user");
    const user = data?.data ?? data;
    return { user, token };
  } catch {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    queryClient.setQueryData(["auth-token"], null);
    return null;
  }
}

export function useSession() {
  const sessionQuery = useQuery({
    queryKey: ["session"],
    queryFn: readSession,
    staleTime: 5 * 60 * 1000,
  });

  const signOut = useCallback(async () => {
    await logout();
  }, []);

  return {
    data: sessionQuery.data ?? null,
    isLoading: sessionQuery.isLoading,
    isFetching: sessionQuery.isFetching,
    refetch: sessionQuery.refetch,
    signOut,
  };
}
