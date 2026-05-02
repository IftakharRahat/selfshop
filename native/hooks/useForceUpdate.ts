import { useQuery } from "@tanstack/react-query";
import Constants from "expo-constants";
import { Platform } from "react-native";
import apiClient from "@/lib/api-client";

interface ForceUpdateResult {
  updateRequired: boolean;
  storeUrl: string;
  isLoading: boolean;
}

/**
 * Checks the backend for the minimum required app version code.
 * If the running build is below that threshold, `updateRequired` is true
 * and the UI should show a blocking modal redirecting to the Play Store.
 *
 * Works without authentication so even logged-out users are gated.
 */
export function useForceUpdate(): ForceUpdateResult {
  const versionCode =
    Platform.OS === "android"
      ? Constants.expoConfig?.android?.versionCode ?? 1
      : 0; // iOS not yet tracked

  const { data, isLoading } = useQuery({
    queryKey: ["app-version-check", versionCode],
    queryFn: async () => {
      const { data: res } = await apiClient.get("/app-version-check", {
        params: { platform: Platform.OS, version_code: versionCode },
      });
      return res;
    },
    staleTime: 5 * 60 * 1000, // re-check every 5 min
    refetchOnWindowFocus: true,
    retry: 2,
  });

  return {
    updateRequired: data?.update_required === true,
    storeUrl:
      data?.store_url ??
      "https://play.google.com/store/apps/details?id=com.selfshop.app",
    isLoading,
  };
}
