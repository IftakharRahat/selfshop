import { QueryClientProvider } from "@tanstack/react-query";
import { useFonts } from "expo-font";
import { Stack, router, useSegments } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import { StatusBar } from "expo-status-bar";
import React, { useEffect, useState } from "react";
import { StyleSheet } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { TamaguiProvider } from "tamagui";
import { Toaster } from "sonner-native";

import { queryClient } from "@/lib/query-client";
import { useSession, AuthProvider } from "@/lib/auth-client";
import { tamaguiConfig } from "../tamagui.config";

SplashScreen.preventAutoHideAsync();

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
});

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    Inter: require("@tamagui/font-inter/otf/Inter-Medium.otf"),
    InterBold: require("@tamagui/font-inter/otf/Inter-Bold.otf"),
  });

  if (!fontsLoaded) {
    return null;
  }

  return (
    <TamaguiProvider config={tamaguiConfig} defaultTheme="light">
      <QueryClientProvider client={queryClient}>
        <StatusBar style="dark" translucent backgroundColor="transparent" />
        <GestureHandlerRootView style={styles.container}>
          <AuthProvider>
            <AuthGate />
            <Toaster
              position="top-center"
              offset={60}
              swipeToDismissDirection="up"
              richColors
              closeButton
            />
          </AuthProvider>
        </GestureHandlerRootView>
      </QueryClientProvider>
    </TamaguiProvider>
  );
}

function AuthGate() {
  const { data: session, isLoading } = useSession();
  const segments = useSegments();
  const [hasNavigated, setHasNavigated] = useState(false);

  useEffect(() => {
    if (isLoading) return; // wait for auth check

    // Hide splash once auth state is known
    SplashScreen.hideAsync();

    const inAuthGroup = segments[0] === "login" || segments[0] === "register";

    if (!session && !inAuthGroup) {
      // Not logged in → go to login
      router.replace("/login");
      setHasNavigated(true);
    } else if (session && inAuthGroup) {
      // Logged in but on auth screen → go to tabs
      router.replace("/(tabs)");
      setHasNavigated(true);
    }
  }, [session, isLoading, segments]);

  return (
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
      <Stack.Screen name="login" options={{ animation: "slide_from_bottom", presentation: "modal", gestureDirection: "vertical" }} />
      <Stack.Screen name="register" options={{ animation: "slide_from_bottom", presentation: "modal", gestureDirection: "vertical" }} />

      {/* Account sub-screens — platform default drill-in */}
      <Stack.Screen name="account/profile" />
      <Stack.Screen name="account/kyc" />
      <Stack.Screen name="account/notifications" />

      {/* Product sub-screens */}
      <Stack.Screen name="product/form" />
      <Stack.Screen name="product/[id]" />
      <Stack.Screen name="product/variants" />

      {/* Order sub-screens */}
      <Stack.Screen name="order/[id]" />

      {/* Account extended screens */}
      <Stack.Screen name="account/earnings" />
      <Stack.Screen name="account/payouts" />
      <Stack.Screen name="account/inventory" />
      <Stack.Screen name="account/shipping" />
      <Stack.Screen name="account/reviews" />
      <Stack.Screen name="account/product-reviews" />
      <Stack.Screen name="account/discounts" />
      <Stack.Screen name="account/reports" />
    </Stack>
  );
}
