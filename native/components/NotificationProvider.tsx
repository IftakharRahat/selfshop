import { useEffect, useRef, useCallback } from "react";
import { Text, Platform, Vibration } from "react-native";
import * as Haptics from "expo-haptics";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner-native";
import PusherRN from "pusher-js/react-native";
import EchoModule from "laravel-echo";

// Handle ESM/CJS interop
// pusher-js/react-native exports { Pusher, logToConsole } — need the .Pusher property
const PusherModule: any = (PusherRN as any).default ?? PusherRN;
const Pusher: any = PusherModule.Pusher ?? PusherModule;
const Echo: any = (EchoModule as any).default ?? EchoModule;

import { useSession } from "@/lib/auth-client";

const PUSHER_KEY = process.env.EXPO_PUBLIC_PUSHER_APP_KEY || "";
const PUSHER_CLUSTER = process.env.EXPO_PUBLIC_PUSHER_APP_CLUSTER || "ap1";
const BASE_URL = (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "");

// Enable Pusher logging in dev
if (__DEV__) {
  Pusher.logToConsole = true;
}

export default function NotificationProvider({
  children,
}: {
  children: React.ReactNode;
}) {
  const { data: session, isLoading } = useSession();
  const queryClient = useQueryClient();
  const echoRef = useRef<Echo<"pusher"> | null>(null);

  const showNotification = useCallback(
    (data: { title: string; message: string; type?: string }) => {
      // Refresh notification list & badge count
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
      queryClient.invalidateQueries({ queryKey: ["notifications-count"] });

      const iconMap: Record<string, string> = {
        success: "✅",
        warning: "⚠️",
        error: "❌",
        info: "🔔",
      };

      const emoji = iconMap[data.type || "info"] || "🔔";

      // Haptic feedback + vibration for notification
      try {
        if (Platform.OS === "ios") {
          Haptics.notificationAsync(Haptics.NotificationFeedbackType.Success);
        } else {
          Vibration.vibrate([0, 250, 100, 250]); // pattern: pause, vibrate, pause, vibrate
        }
      } catch (e) {
        // Ignore haptics errors on simulators
      }

      toast(data.title, {
        description: data.message,
        duration: 6000,
        icon: <Text>{emoji}</Text>,
      });
    },
    [queryClient],
  );

  useEffect(() => {
    if (isLoading) return;

    const token = session?.token;
    const userId = session?.user?.id;

    console.log("[Pusher] Effect running. token:", !!token, "userId:", userId);

    if (!token || !userId) {
      // Cleanup on logout
      if (echoRef.current) {
        console.log("[Pusher] Disconnecting (no token/user)");
        echoRef.current.disconnect();
        echoRef.current = null;
      }
      return;
    }

    if (!PUSHER_KEY) {
      console.warn("[Pusher] EXPO_PUBLIC_PUSHER_APP_KEY not set");
      return;
    }

    // Avoid duplicate connections
    if (echoRef.current) {
      console.log("[Pusher] Already connected, skipping");
      return;
    }

    console.log("[Pusher] Config:", { key: PUSHER_KEY, cluster: PUSHER_CLUSTER, baseUrl: BASE_URL });
    console.log("[Pusher] Pusher type:", typeof Pusher, "Echo type:", typeof Echo);
    console.log("[Pusher] Pusher keys:", Object.keys(Pusher || {}));
    console.log("[Pusher] Initializing Echo...");

    // Create Pusher client with auth
    const pusherClient = new Pusher(PUSHER_KEY, {
      cluster: PUSHER_CLUSTER,
      forceTLS: true,
      authEndpoint: `${BASE_URL}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      },
    });

    // Monitor connection state
    pusherClient.connection.bind("connected", () => {
      console.log("[Pusher] ✅ Connected! Socket ID:", pusherClient.connection.socket_id);
    });
    pusherClient.connection.bind("error", (err: any) => {
      console.error("[Pusher] ❌ Connection error:", JSON.stringify(err));
    });
    pusherClient.connection.bind("disconnected", () => {
      console.log("[Pusher] Disconnected");
    });
    pusherClient.connection.bind("state_change", (states: any) => {
      console.log("[Pusher] State:", states.previous, "→", states.current);
    });

    // Initialize Echo with pre-configured Pusher client
    const echo = new Echo({
      broadcaster: "pusher",
      client: pusherClient,
    });

    echoRef.current = echo;

    console.log(`[Pusher] Subscribing to private-user.${userId}`);

    // Listen on the private channel for this user
    const channel = echo.private(`user.${userId}`);

    channel.listen(
      ".order.notification",
      (data: { title: string; message: string; type?: string }) => {
        console.log(`[Pusher] 🔔 Notification received`, data);
        showNotification(data);
      },
    );

    // Also log subscription success/failure
    channel.error((err: any) => {
      console.error("[Pusher] Channel subscription error:", JSON.stringify(err));
    });

    return () => {
      console.log(`[Pusher] Cleanup: leaving user.${userId}`);
      echo.leave(`user.${userId}`);
      echo.disconnect();
      echoRef.current = null;
    };
  }, [session?.token, session?.user?.id, isLoading, showNotification]);

  return <>{children}</>;
}
