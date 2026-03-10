"use client";

import { useEffect, useRef, useCallback, useState } from "react";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import type { RootState } from "@/redux/store";
import { useAppDispatch } from "@/redux/hooks";
import { baseApi } from "@/redux/api/baseApi";

// Extend window type for Pusher
declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<"pusher">;
    }
}

export default function NotificationProvider({
    children,
}: {
    children: React.ReactNode;
}) {
    const accessToken = useSelector(
        (state: RootState) => state.auth.access_token,
    );
    const user = useSelector((state: RootState) => state.auth.user);
    const echoRef = useRef<Echo<"pusher"> | null>(null);
    const audioRef = useRef<HTMLAudioElement | null>(null);
    const dispatch = useAppDispatch();
    const [userId, setUserId] = useState<number | null>(null);

    // Fetch user ID when token is available but user object has no ID
    useEffect(() => {
        if (user?.id) {
            setUserId(user.id);
            return;
        }
        if (!accessToken) {
            setUserId(null);
            return;
        }

        // Fetch user ID from backend using Sanctum token
        const fetchUserId = async () => {
            try {
                const res = await fetch(`${process.env.NEXT_PUBLIC_BASE_URL}/user`, {
                    headers: {
                        Authorization: `Bearer ${accessToken}`,
                        Accept: "application/json",
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    console.log("[Pusher] Fetched user data:", data);
                    setUserId(data.id || null);
                } else {
                    console.warn("[Pusher] Failed to fetch user:", res.status);
                }
            } catch (err) {
                console.warn("[Pusher] Error fetching user:", err);
            }
        };

        fetchUserId();
    }, [accessToken, user]);

    const audioCtxRef = useRef<AudioContext | null>(null);

    // Initialize AudioContext on first user interaction (click/keypress)
    useEffect(() => {
        const initAudio = () => {
            if (!audioCtxRef.current) {
                const AudioCtx =
                    window.AudioContext ||
                    (window as unknown as { webkitAudioContext: typeof AudioContext })
                        .webkitAudioContext;
                if (AudioCtx) {
                    audioCtxRef.current = new AudioCtx();
                    console.log("[Pusher] AudioContext initialized on user gesture");
                }
            } else if (audioCtxRef.current.state === "suspended") {
                audioCtxRef.current.resume();
            }
        };

        document.addEventListener("click", initAudio, { once: false });
        document.addEventListener("keydown", initAudio, { once: false });

        return () => {
            document.removeEventListener("click", initAudio);
            document.removeEventListener("keydown", initAudio);
        };
    }, []);

    const playNotificationSound = useCallback(() => {
        try {
            const ctx = audioCtxRef.current;
            if (!ctx) return;

            // Resume if suspended
            if (ctx.state === "suspended") {
                ctx.resume();
            }

            // Create a pleasant two-tone chime
            const playTone = (freq: number, startTime: number, duration: number) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.type = "sine";
                osc.frequency.setValueAtTime(freq, startTime);
                gain.gain.setValueAtTime(0.3, startTime);
                gain.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
                osc.start(startTime);
                osc.stop(startTime + duration);
            };

            const now = ctx.currentTime;
            playTone(830, now, 0.15);
            playTone(1050, now + 0.12, 0.2);
        } catch {
            // Silent fail for notification sound
        }
    }, []);

    const showNotification = useCallback(
        (data: {
            title: string;
            message: string;
            type: string;
            meta?: Record<string, unknown>;
        }) => {
            // Refetch notification list from database in real-time
            dispatch(baseApi.util.invalidateTags(["userNotifications", "vendorNotifications"]));

            playNotificationSound();

            const iconMap: Record<string, string> = {
                success: "✅",
                warning: "⚠️",
                error: "❌",
                info: "🔔",
            };

            const icon = iconMap[data.type] || "🔔";

            toast(data.title, {
                description: data.message,
                duration: 6000,
                icon,
            });
        },
        [playNotificationSound, dispatch],
    );

    useEffect(() => {
        console.log("[Pusher] Effect running. accessToken:", !!accessToken, "userId:", userId);

        if (!accessToken || !userId) {
            // Cleanup if user logs out
            if (echoRef.current) {
                echoRef.current.disconnect();
                echoRef.current = null;
            }
            return;
        }

        const pusherKey = process.env.NEXT_PUBLIC_PUSHER_APP_KEY;
        const pusherCluster = process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER;
        const baseUrl = process.env.NEXT_PUBLIC_BASE_URL;

        console.log("[Pusher] Config:", { pusherKey, pusherCluster, baseUrl });

        if (!pusherKey || !pusherCluster) {
            console.warn(
                "[Pusher] Config missing: NEXT_PUBLIC_PUSHER_APP_KEY / NEXT_PUBLIC_PUSHER_APP_CLUSTER",
            );
            return;
        }

        // Make Pusher available globally for Laravel Echo
        window.Pusher = Pusher;
        // Enable Pusher logging for debug
        Pusher.logToConsole = true;

        // Base URL includes /api but broadcasting/auth is at root
        const serverUrl = (baseUrl || "").replace(/\/api\/?$/, "");

        console.log("[Pusher] Initializing Echo...");

        const echo = new Echo({
            broadcaster: "pusher",
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
            authEndpoint: `${serverUrl}/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                    Accept: "application/json",
                },
            },
        });

        echoRef.current = echo;

        console.log("[Pusher] Subscribing to private-user." + userId);

        // Listen on private channel for this user
        echo.private(`user.${userId}`).listen(
            ".order.notification",
            (data: {
                title: string;
                message: string;
                type: string;
                meta?: Record<string, unknown>;
            }) => {
                console.log("[Pusher] 🔔 Notification received on user." + userId, data);
                showNotification(data);
            },
        );

        return () => {
            echo.leave(`user.${userId}`);
            echo.disconnect();
            echoRef.current = null;
        };
    }, [accessToken, userId, showNotification]);

    return <>{children}</>;
}
