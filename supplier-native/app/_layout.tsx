import { QueryClientProvider } from "@tanstack/react-query";
import { useFonts } from "expo-font";
import { Stack } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import { StatusBar } from "expo-status-bar";
import React, { useEffect, useState } from "react";
import { StyleSheet } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { TamaguiProvider } from "tamagui";
import { Toaster } from "sonner-native";

import { queryClient } from "@/lib/query-client";
import { tamaguiConfig } from "../tamagui.config";

SplashScreen.preventAutoHideAsync();

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
});

export default function RootLayout() {
  const [isReady, setIsReady] = useState(false);

  const [fontsLoaded] = useFonts({
    Inter: require("@tamagui/font-inter/otf/Inter-Medium.otf"),
    InterBold: require("@tamagui/font-inter/otf/Inter-Bold.otf"),
  });

  useEffect(() => {
    if (fontsLoaded) {
      setIsReady(true);
      SplashScreen.hideAsync();
    }
  }, [fontsLoaded]);

  if (!fontsLoaded || !isReady) {
    return null;
  }

  return (
    <TamaguiProvider config={tamaguiConfig} defaultTheme="light">
      <QueryClientProvider client={queryClient}>
        <StatusBar style="dark" />
        <GestureHandlerRootView style={styles.container}>
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
          </Stack>
          <Toaster
            position="top-center"
            offset={60}
            swipeToDismissDirection="up"
            richColors
            closeButton
          />
        </GestureHandlerRootView>
      </QueryClientProvider>
    </TamaguiProvider>
  );
}
