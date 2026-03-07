"use client";

import { useEffect, useRef, useCallback } from "react";
import { useSelector } from "react-redux";
import { toast } from "sonner";
import Pusher from "pusher-js";
import Echo from "laravel-echo";
import type { RootState } from "@/redux/store";

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

    const playNotificationSound = useCallback(() => {
        try {
            const AudioCtx =
                window.AudioContext ||
                (window as unknown as { webkitAudioContext: typeof AudioContext })
                    .webkitAudioContext;
            if (!AudioCtx) return;

            const ctx = new AudioCtx();

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
            playNotificationSound();

            const toastType =
                data.type === "success"
                    ? "success"
                    : data.type === "warning"
                        ? "warning"
                        : data.type === "error"
                            ? "error"
                            : "info";

            if (toastType === "success") {
                toast.success(data.title, {
                    description: data.message,
                    duration: 6000,
                });
            } else if (toastType === "warning") {
                toast.warning(data.title, {
                    description: data.message,
                    duration: 6000,
                });
            } else if (toastType === "error") {
                toast.error(data.title, {
                    description: data.message,
                    duration: 6000,
                });
            } else {
                toast.info(data.title, {
                    description: data.message,
                    duration: 6000,
                });
            }
        },
        [playNotificationSound],
    );

    useEffect(() => {
        if (!accessToken || !user?.id) {
            // Cleanup if user logs out
            if (echoRef.current) {
                echoRef.current.disconnect();
                echoRef.current = null;
            }
            return;
        }

        const pusherKey = process.env.NEXT_PUBLIC_PUSHER_APP_KEY;
        const pusherCluster = process.env.NEXT_PUBLIC_PUSHER_APP_CLUSTER;

        if (!pusherKey || !pusherCluster) {
            console.warn(
                "Pusher config missing: NEXT_PUBLIC_PUSHER_APP_KEY / NEXT_PUBLIC_PUSHER_APP_CLUSTER",
            );
            return;
        }

        // Make Pusher available globally for Laravel Echo
        window.Pusher = Pusher;

        const echo = new Echo({
            broadcaster: "pusher",
            key: pusherKey,
            cluster: pusherCluster,
            forceTLS: true,
            authEndpoint: `${process.env.NEXT_PUBLIC_BASE_URL}/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${accessToken}`,
                    Accept: "application/json",
                },
            },
        });

        echoRef.current = echo;

        // Listen on private channel for this user
        echo.private(`user.${user.id}`).listen(
            ".order.notification",
            (data: {
                title: string;
                message: string;
                type: string;
                meta?: Record<string, unknown>;
            }) => {
                showNotification(data);
            },
        );

        // Also listen on admin broadcast channel (user.0) for admin notifications
        echo.private("user.0").listen(
            ".order.notification",
            (data: {
                title: string;
                message: string;
                type: string;
                meta?: Record<string, unknown>;
            }) => {
                showNotification(data);
            },
        );

        return () => {
            echo.leave(`user.${user.id}`);
            echo.leave("user.0");
            echo.disconnect();
            echoRef.current = null;
        };
    }, [accessToken, user?.id, showNotification]);

    return <>{children}</>;
}
