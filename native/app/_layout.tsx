import { QueryClientProvider } from "@tanstack/react-query";
import { useFonts } from "expo-font";
import { Stack, router } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import { StatusBar } from "expo-status-bar";
import React, { useEffect, useState } from "react";
import { StyleSheet } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { TamaguiProvider } from "tamagui";
import * as SecureStore from "expo-secure-store";
import { Toaster } from "sonner-native";

import { queryClient } from "@/lib/query-client";
import { tamaguiConfig } from "../tamagui.config";
import { useForceUpdate } from "@/hooks/useForceUpdate";
import { ForceUpdateModal } from "@/components/force-update-modal";
import NotificationProvider from "@/components/NotificationProvider";

SplashScreen.preventAutoHideAsync();

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
});

/**
 * Inner gate that checks for forced updates.
 * Must be rendered INSIDE QueryClientProvider so useQuery works.
 */
function ForceUpdateGate() {
  const { updateRequired, storeUrl } = useForceUpdate();
  return <ForceUpdateModal visible={updateRequired} storeUrl={storeUrl} />;
}

export default function RootLayout() {
  const [isReady, setIsReady] = useState(false);
  const [showOnboarding, setShowOnboarding] = useState(false);

  const [fontsLoaded] = useFonts({
    Inter: require("@tamagui/font-inter/otf/Inter-Medium.otf"),
    InterBold: require("@tamagui/font-inter/otf/Inter-Bold.otf"),
  });

  useEffect(() => {
    async function checkOnboarding() {
      const completed = await SecureStore.getItemAsync("onboarding_completed");
      setShowOnboarding(completed !== "true");
      setIsReady(true);
    }
    checkOnboarding();
  }, []);

  useEffect(() => {
    if (fontsLoaded && isReady) {
      SplashScreen.hideAsync();
      if (showOnboarding) {
        router.replace("/onboarding");
      }
    }
  }, [fontsLoaded, isReady, showOnboarding]);

  if (!fontsLoaded || !isReady) {
    return null;
  }

  return (
    <TamaguiProvider config={tamaguiConfig} defaultTheme="light">
      <QueryClientProvider client={queryClient}>
        <StatusBar style="dark" />
        <GestureHandlerRootView style={styles.container}>
          <NotificationProvider>
            <Stack
              screenOptions={{
                headerShown: false,
                contentStyle: { backgroundColor: "#fff" },
                animation: "ios_from_right",
                gestureEnabled: true,
                fullScreenGestureEnabled: true,
              }}
            >
              {/* Tab root — fade (instant feel) */}
              <Stack.Screen name="(tabs)" options={{ animation: "fade" }} />

              {/* Auth — slide up (modal feel) */}
              <Stack.Screen name="onboarding" options={{ animation: "fade" }} />
              <Stack.Screen name="login" options={{ animation: "slide_from_bottom", presentation: "modal", gestureDirection: "vertical" }} />
              <Stack.Screen name="register" options={{ animation: "slide_from_bottom", presentation: "modal", gestureDirection: "vertical" }} />
              <Stack.Screen name="forgot-password" options={{ animation: "slide_from_bottom", presentation: "modal", gestureDirection: "vertical" }} />

              {/* Checkout & modals — slide up */}
              <Stack.Screen name="pricing" options={{ animation: "slide_from_bottom", gestureDirection: "vertical" }} />
              <Stack.Screen name="invoice" options={{ animation: "slide_from_bottom", gestureDirection: "vertical" }} />
              <Stack.Screen name="order-confirmation" options={{ animation: "slide_from_bottom", gestureDirection: "vertical" }} />

              {/* Content screens — platform default (iOS parallax card / Android material) */}
              <Stack.Screen name="product-detail" />
              <Stack.Screen name="category-products" />
              <Stack.Screen name="collection/[slug]" />
              <Stack.Screen name="section/[slug]" />
              <Stack.Screen name="search" options={{ animation: "fade_from_bottom" }} />

              {/* Account sub-screens — platform default drill-in */}
              <Stack.Screen name="account/orders" />
              <Stack.Screen name="account/order-detail" />
              <Stack.Screen name="account/addresses" />
              <Stack.Screen name="account/address-form" />
              <Stack.Screen name="account/edit-profile" />
              <Stack.Screen name="account/tickets" />
              <Stack.Screen name="account/ticket-detail" />
              <Stack.Screen name="account/create-ticket" />
              <Stack.Screen name="account/faq" />
              <Stack.Screen name="account/change-password" />
              <Stack.Screen name="account/my-shop" />
              <Stack.Screen name="account/reseller-shop" />
              <Stack.Screen name="account/notifications" />
              <Stack.Screen name="account/balance-transfer" />
              <Stack.Screen name="account/income-history" />
              <Stack.Screen name="account/team-members" />
              <Stack.Screen name="account/referral" />
              <Stack.Screen name="account/withdraw" />
              <Stack.Screen name="account/track-order" />
            </Stack>
            <Toaster
              position="top-center"
              offset={60}
              swipeToDismissDirection="up"
              richColors
              closeButton
            />
            <ForceUpdateGate />
          </NotificationProvider>
        </GestureHandlerRootView>
      </QueryClientProvider>
    </TamaguiProvider>
  );
}
