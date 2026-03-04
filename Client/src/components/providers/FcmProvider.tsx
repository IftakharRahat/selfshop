"use client";

import { useEffect, useRef } from "react";
import { useCurrentToken } from "@/redux/features/auth/authSlice";
import { useAppDispatch, useAppSelector } from "@/redux/hooks";
import { baseApi } from "@/redux/api/baseApi";
import { getToken, onMessage } from "firebase/messaging";
import { getFirebaseMessaging } from "@/lib/firebase";
import { toast } from "sonner";

const VAPID_KEY = process.env.NEXT_PUBLIC_FIREBASE_VAPID_KEY;
const BASE_URL = process.env.NEXT_PUBLIC_BASE_URL;

export default function FcmProvider({ children }: { children: React.ReactNode }) {
    const dispatch = useAppDispatch();
    const accessToken = useAppSelector(useCurrentToken);
    const tokenRegistered = useRef(false);
    const setupRunning = useRef(false);

    useEffect(() => {
        if (!accessToken || tokenRegistered.current || setupRunning.current) return;
        if (typeof window === "undefined") return;
        if (!("Notification" in window) || !("serviceWorker" in navigator)) {
            console.log("[FCM] Browser does not support notifications or service workers");
            return;
        }

        setupRunning.current = true;

        const setupFcm = async () => {
            try {
                // Step 1: Check/request permission
                let permission = Notification.permission;
                console.log("[FCM] Current permission:", permission);

                if (permission === "default") {
                    console.log("[FCM] Requesting permission...");
                    permission = await Notification.requestPermission();
                    console.log("[FCM] Permission result:", permission);
                }

                if (permission !== "granted") {
                    console.warn("[FCM] Permission not granted:", permission);
                    setupRunning.current = false;
                    return;
                }

                // Step 2: Get messaging
                console.log("[FCM] Getting messaging instance...");
                const messaging = await getFirebaseMessaging();
                if (!messaging) {
                    console.error("[FCM] Messaging not supported");
                    setupRunning.current = false;
                    return;
                }
                console.log("[FCM] ✅ Messaging ready");

                // Step 3: Register service worker
                console.log("[FCM] Registering service worker...");
                const registration = await navigator.serviceWorker.register("/firebase-messaging-sw.js");
                await navigator.serviceWorker.ready;
                console.log("[FCM] ✅ Service worker ready:", registration.scope);

                // Step 4: Get FCM token
                console.log("[FCM] Getting FCM token with VAPID key...");
                const fcmToken = await getToken(messaging, {
                    vapidKey: VAPID_KEY,
                    serviceWorkerRegistration: registration,
                });

                if (!fcmToken) {
                    console.error("[FCM] ❌ No token returned");
                    setupRunning.current = false;
                    return;
                }
                console.log("[FCM] ✅ FCM token:", fcmToken.substring(0, 40) + "...");

                // Step 5: Send to backend
                console.log("[FCM] Sending token to backend...");
                const res = await fetch(`${BASE_URL}/fcm-token`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${accessToken}`,
                    },
                    body: JSON.stringify({
                        token: fcmToken,
                        device_info: navigator.userAgent,
                    }),
                });

                const resData = await res.json().catch(() => null);
                console.log("[FCM] Backend response:", res.status, resData);

                if (res.ok) {
                    tokenRegistered.current = true;
                    console.log("[FCM] ✅ ALL DONE — Token registered!");
                } else {
                    console.error("[FCM] ❌ Backend error:", res.status, resData);
                }

                // Step 6: Foreground listener
                onMessage(messaging, (payload) => {
                    console.log("[FCM] 🔔 Foreground message:", payload);

                    dispatch(baseApi.util.invalidateTags(["userNotifications", "vendorNotifications"]));

                    const title = payload.notification?.title || payload.data?.title || "Notification";
                    const body = payload.notification?.body || payload.data?.body || "";

                    try {
                        const audio = new Audio("/notification-sound.wav");
                        audio.volume = 0.5;
                        audio.play().catch(() => { });
                    } catch { }

                    toast(title, {
                        description: body,
                        duration: 8000,
                        action: payload.data?.click_action
                            ? {
                                label: "View",
                                onClick: () => window.open(payload.data!.click_action, "_self"),
                            }
                            : undefined,
                    });
                });
            } catch (error) {
                console.error("[FCM] ❌ Setup error:", error);
                setupRunning.current = false;
            }
        };

        setupFcm();
    }, [accessToken]);

    return <>{children}</>;
}
