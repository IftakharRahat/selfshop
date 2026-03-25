import { useQuery } from "@tanstack/react-query";
import * as SecureStore from "expo-secure-store";
import apiClient, { TOKEN_KEY } from "@/lib/api-client";

/**
 * Determines if the current user is an active reseller with a paid membership.
 * Mirrors the web frontend's useIsActiveReseller hook logic.
 *
 * Uses React Query for global caching — all ProductCards share the same query
 * instead of each making its own /user call via useSession().
 */
export function useIsActiveReseller() {
  // Step 1: Check if token exists (shared across all components via React Query cache)
  const tokenQuery = useQuery({
    queryKey: ["auth-token"],
    queryFn: () => SecureStore.getItemAsync(TOKEN_KEY),
    staleTime: 30 * 1000, // re-check every 30s
  });

  const token = tokenQuery.data;
  const isLoggedIn = !!token;

  // Step 2: Fetch profile only when logged in (also shared via cache)
  const profileQuery = useQuery({
    queryKey: ["user-profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-profile");
      return data?.data ?? data;
    },
    enabled: isLoggedIn,
    staleTime: 5 * 60 * 1000,
  });

  if (tokenQuery.isLoading) {
    return { isActive: false, isLoading: true, isLoggedIn: false, isExpired: false };
  }

  if (!isLoggedIn) {
    return { isActive: false, isLoading: false, isLoggedIn: false, isExpired: false };
  }

  if (profileQuery.isLoading) {
    return { isActive: false, isLoading: true, isLoggedIn: true, isExpired: false };
  }

  const profile = profileQuery.data?.profile ?? profileQuery.data;
  const membershipStatus = String(profile?.membership_status ?? "").toLowerCase();
  const accountStatus = String(profile?.status ?? "").toLowerCase();
  const expireDate = profile?.expire_date;

  let isExpired = false;
  if (expireDate) {
    const parsed = new Date(expireDate);
    if (!isNaN(parsed.getTime())) {
      parsed.setHours(23, 59, 59, 999);
      isExpired = parsed.getTime() < Date.now();
    }
  }

  const isActive = !isExpired && (membershipStatus === "paid" || accountStatus === "active");

  return { isActive, isLoading: false, isLoggedIn: true, isExpired };
}
