import { useEffect, useRef, useCallback } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { toast } from "sonner-native";
import Pusher from "pusher-js";
import Echo from "laravel-echo";

import { useSession } from "@/lib/auth-client";

const PUSHER_KEY = process.env.EXPO_PUBLIC_PUSHER_APP_KEY || "";
const PUSHER_CLUSTER = process.env.EXPO_PUBLIC_PUSHER_APP_CLUSTER || "ap1";
const BASE_URL = (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "");

export default function NotificationProvider({
  children,
}: {
  children: React.ReactNode;
}) {
  const { data: session } = useSession();
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

      toast(data.title, {
        description: data.message,
        duration: 6000,
        icon: iconMap[data.type || "info"] || "🔔",
      });
    },
    [queryClient],
  );

  useEffect(() => {
    const token = session?.token;
    const userId = session?.user?.id;

    if (!token || !userId) {
      // Cleanup on logout
      if (echoRef.current) {
        echoRef.current.disconnect();
        echoRef.current = null;
      }
      return;
    }

    if (!PUSHER_KEY) {
      console.warn("[Pusher] EXPO_PUBLIC_PUSHER_APP_KEY not set");
      return;
    }

    // Initialize Echo with Pusher broadcaster
    const echo = new Echo({
      broadcaster: "pusher",
      client: new Pusher(PUSHER_KEY, {
        cluster: PUSHER_CLUSTER,
        forceTLS: true,
        authEndpoint: `${BASE_URL}/broadcasting/auth`,
        auth: {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
          },
        },
      }),
    });

    echoRef.current = echo;

    console.log(`[Pusher] Subscribing to private-user.${userId}`);

    // Listen on the private channel for this user
    echo
      .private(`user.${userId}`)
      .listen(
        ".order.notification",
        (data: { title: string; message: string; type?: string }) => {
          console.log(`[Pusher] 🔔 Notification received`, data);
          showNotification(data);
        },
      );

    return () => {
      echo.leave(`user.${userId}`);
      echo.disconnect();
      echoRef.current = null;
    };
  }, [session?.token, session?.user?.id, showNotification]);

  return <>{children}</>;
}
