import type { QueryClient } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

export async function fetchUserProfilePayload() {
  const { data } = await apiClient.get("/user-profile");
  return data?.data ?? data;
}

export function invalidateSubscriptionAccessQueries(queryClient: QueryClient) {
  [
    ["auth-token"],
    ["session"],
    ["user-profile"],
    ["pricing-packages"],
    ["dashboard-data"],
    ["cart-items"],
    ["order-count"],
    ["orders"],
    ["shop-products"],
    ["income-history"],
    ["order-income-history"],
    ["withdraw-list"],
    ["balance-transfers"],
    ["referral-data"],
  ].forEach((queryKey) => {
    queryClient.invalidateQueries({ queryKey });
  });
}
