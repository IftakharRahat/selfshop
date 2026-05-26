import * as SecureStore from "expo-secure-store";
import { useCallback } from "react";
import { useQuery } from "@tanstack/react-query";
import apiClient, { TOKEN_KEY } from "./api-client";
import { queryClient } from "./query-client";
import { unregisterDevicePushTokenAsync } from "./push-notifications";
import type { SubscriptionState } from "./subscription-routing";

interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
}

interface Session {
  user: User;
  token: string;
  profilePayload?: any;
  subscription?: SubscriptionState | null;
}

function parseAuthResponse(data: any) {
  const payload = data?.data ?? data;

  return {
    token:
      payload?.token ??
      payload?.access_token ??
      data?.token ??
      data?.access_token,
    user: payload?.user ?? data?.user,
    payload,
  };
}

async function fetchCurrentUser(): Promise<User> {
  const { data } = await apiClient.get("/user");
  const user = data?.data?.user ?? data?.data ?? data?.user ?? data;

  if (!user?.id) {
    throw new Error("No user received");
  }

  return user;
}

function cacheSession(session: Session | null) {
  queryClient.setQueryData(["session"], session);
  queryClient.setQueryData(["auth-token"], session?.token ?? null);
}

async function createSessionFromAuthResponse(data: any): Promise<Session> {
  if (data?.status === false) {
    throw new Error(data?.message ?? "Authentication failed");
  }

  const { token, user: responseUser, payload } = parseAuthResponse(data);

  if (!token) throw new Error(data?.message ?? "No token received");

  await SecureStore.setItemAsync(TOKEN_KEY, token);

  try {
    const user = responseUser?.id ? responseUser : await fetchCurrentUser();
    const session = {
      user,
      token,
      profilePayload: payload,
      subscription: payload?.subscription ?? null,
    };
    cacheSession(session);
    if (payload?.subscription || payload?.profile) {
      queryClient.setQueryData(["user-profile"], payload);
    }
    return session;
  } catch (error) {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    cacheSession(null);
    throw error;
  }
}

// ── Auth functions ──

export async function login(email: string, password: string): Promise<Session> {
  const { data } = await apiClient.post("/login", { email, password });
  return createSessionFromAuthResponse(data);
}

export async function register(
  name: string,
  email: string,
  password: string,
  password_confirmation: string,
  refer_by?: string,
): Promise<Session> {
  const { data } = await apiClient.post("/register", {
    name,
    email,
    password,
    password_confirmation,
    refer_by: refer_by?.trim() || undefined,
  });
  return createSessionFromAuthResponse(data);
}

export async function logout(): Promise<void> {
  try {
    await unregisterDevicePushTokenAsync();
    await apiClient.post("/logout");
  } catch {
    // ignore — token may already be invalid
  }
  await SecureStore.deleteItemAsync(TOKEN_KEY);
  cacheSession(null);
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

    const user = await fetchCurrentUser();
    return { user, token };
  } catch {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    cacheSession(null);
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
