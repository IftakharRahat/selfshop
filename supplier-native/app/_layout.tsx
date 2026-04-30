import { QueryClientProvider } from "@tanstack/react-query";
import { useFonts } from "expo-font";
import { Stack, router, useSegments } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import * as SecureStore from "expo-secure-store";
import { StatusBar } from "expo-status-bar";
import React, { useEffect, useState } from "react";
import {
  StyleSheet,
  View,
  Text,
  TouchableOpacity,
} from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { TamaguiProvider } from "tamagui";
import { Toaster } from "sonner-native";
import { Ionicons } from "@expo/vector-icons";

import { queryClient } from "@/lib/query-client";
import { useSession, AuthProvider } from "@/lib/auth-client";
import { BRAND } from "@/lib/constants";
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
  const { data: session, isLoading, isVendor, isVendorChecking } = useSession();
  const segments = useSegments();
  const [hasNavigated, setHasNavigated] = useState(false);
  const [showOnboarding, setShowOnboarding] = useState(false);
  const [isOnboardingChecked, setIsOnboardingChecked] = useState(false);

  useEffect(() => {
    async function checkOnboarding() {
      const completed = await SecureStore.getItemAsync("supplier_onboarding_completed");
      setShowOnboarding(completed !== "true");
      setIsOnboardingChecked(true);
    }
    checkOnboarding();
  }, []);

  useEffect(() => {
    if (isLoading || !isOnboardingChecked) return; // wait for initial auth check and onboarding check
    if (isVendorChecking) return; // wait for vendor profile check to finish

    // Hide splash once auth + vendor state + onboarding state is fully known
    SplashScreen.hideAsync();

    const inAuthGroup = segments[0] === "login" || segments[0] === "register";

    if (showOnboarding && segments[0] !== "onboarding") {
      router.replace("/onboarding");
      setHasNavigated(true);
    } else if (!session && !inAuthGroup && !showOnboarding) {
      // Not logged in → go to login
      router.replace("/login");
      setHasNavigated(true);
    } else if (session && inAuthGroup && isVendor) {
      // Logged in vendor on auth screen → go to tabs
      router.replace("/(tabs)");
      setHasNavigated(true);
    } else if (session && inAuthGroup && !isVendor) {
      // Logged in but NOT a vendor, still on auth screen → go to tabs (will hit guard)
      router.replace("/(tabs)");
      setHasNavigated(true);
    }
  }, [session, isLoading, isVendorChecking, isVendor, segments, showOnboarding, isOnboardingChecked]);

  // While vendor check or onboarding check is in progress, don't render anything blocking
  if (isLoading || isVendorChecking || !isOnboardingChecked) {
    return (
      <Stack
        screenOptions={{
          headerShown: false,
          contentStyle: { backgroundColor: "#fff" },
        }}
      />
    );
  }

  // If logged in but NOT a vendor → show access denied screen
  const inAuthGroup = segments[0] === "login" || segments[0] === "register";
  if (session && !isVendor && !inAuthGroup && segments[0] !== "onboarding") {
    return <AccessDeniedScreen />;
  }

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

      {/* Auth / Onboarding — slide up (modal feel) */}
      <Stack.Screen name="onboarding" options={{ animation: "fade" }} />
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

/**
 * Full-screen blocking overlay shown when a non-vendor user logs in.
 * They must log out and use a supplier account instead.
 */
function AccessDeniedScreen() {
  const { signOut } = useSession();

  const handleLogout = async () => {
    await signOut();
    queryClient.clear();
    router.replace("/login");
  };

  return (
    <View style={adStyles.container}>
      <View style={adStyles.iconWrap}>
        <Ionicons name="shield-outline" size={56} color={BRAND.primary} />
      </View>
      <Text style={adStyles.title}>Supplier Access Only</Text>
      <Text style={adStyles.subtitle}>
        This app is exclusively for registered suppliers and vendors.
        Your account does not have supplier privileges.
      </Text>

      <View style={adStyles.infoCard}>
        <Ionicons name="information-circle" size={20} color="#3b82f6" />
        <Text style={adStyles.infoText}>
          If you believe this is an error, please contact support or register as a supplier from the "Register" screen.
        </Text>
      </View>

      <TouchableOpacity style={adStyles.logoutBtn} onPress={handleLogout} activeOpacity={0.8}>
        <Ionicons name="log-out-outline" size={20} color="#fff" />
        <Text style={adStyles.logoutText}>Sign Out & Try Again</Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={adStyles.registerBtn}
        onPress={async () => {
          await signOut();
          queryClient.clear();
          router.replace("/register");
        }}
        activeOpacity={0.8}
      >
        <Ionicons name="storefront-outline" size={18} color={BRAND.primary} />
        <Text style={adStyles.registerText}>Register as Supplier</Text>
      </TouchableOpacity>
    </View>
  );
}

const adStyles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 32,
  },
  iconWrap: {
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: BRAND.primaryLight,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 24,
  },
  title: {
    fontSize: 22,
    fontWeight: "700",
    color: "#1a1a2e",
    textAlign: "center",
    marginBottom: 10,
  },
  subtitle: {
    fontSize: 14,
    color: "#6b7280",
    textAlign: "center",
    lineHeight: 21,
    marginBottom: 24,
  },
  infoCard: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 10,
    backgroundColor: "#EFF6FF",
    borderRadius: 12,
    padding: 14,
    marginBottom: 32,
    width: "100%",
  },
  infoText: {
    flex: 1,
    fontSize: 12,
    color: "#1E40AF",
    lineHeight: 18,
  },
  logoutBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: BRAND.primary,
    borderRadius: 12,
    paddingVertical: 15,
    width: "100%",
    marginBottom: 12,
  },
  logoutText: {
    fontSize: 15,
    fontWeight: "600",
    color: "#fff",
  },
  registerBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: "#fff",
    borderRadius: 12,
    paddingVertical: 14,
    width: "100%",
    borderWidth: 1.5,
    borderColor: BRAND.primary,
  },
  registerText: {
    fontSize: 14,
    fontWeight: "600",
    color: BRAND.primary,
  },
});
