import { useQuery } from "@tanstack/react-query";
import * as SecureStore from "expo-secure-store";
import apiClient, { TOKEN_KEY } from "@/lib/api-client";
import { queryClient } from "@/lib/query-client";
import {
  getPendingInvoiceFromProfile,
  getProfileFromProfileResponse,
  getSubscriptionFromProfile,
  isSubscriptionActive,
  isSubscriptionExpired,
  subscriptionDestinationFromProfile,
} from "@/lib/subscription-routing";

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

  const refreshSubscription = async () => {
    queryClient.invalidateQueries({ queryKey: ["user-profile"] });
    return profileQuery.refetch();
  };

  if (tokenQuery.isLoading) {
    return {
      isActive: false,
      isLoading: true,
      isLoggedIn: false,
      isExpired: false,
      profile: null,
      subscription: null,
      pendingInvoice: null,
      subscriptionDestination: "/login",
      refreshSubscription,
    };
  }

  if (!isLoggedIn) {
    return {
      isActive: false,
      isLoading: false,
      isLoggedIn: false,
      isExpired: false,
      profile: null,
      subscription: null,
      pendingInvoice: null,
      subscriptionDestination: "/login",
      refreshSubscription,
    };
  }

  if (profileQuery.isLoading) {
    return {
      isActive: false,
      isLoading: true,
      isLoggedIn: true,
      isExpired: false,
      profile: null,
      subscription: null,
      pendingInvoice: null,
      subscriptionDestination: "/pricing",
      refreshSubscription,
    };
  }

  const profile = getProfileFromProfileResponse(profileQuery.data);
  const subscription = getSubscriptionFromProfile(profileQuery.data);
  const pendingInvoice = getPendingInvoiceFromProfile(profileQuery.data);
  const isExpired = isSubscriptionExpired(profileQuery.data);
  const isActive = isSubscriptionActive(profileQuery.data);
  const subscriptionDestination = subscriptionDestinationFromProfile(profileQuery.data);

  return {
    isActive,
    isLoading: false,
    isLoggedIn: true,
    isExpired,
    profile,
    subscription,
    pendingInvoice,
    subscriptionDestination,
    refreshSubscription,
  };
}
