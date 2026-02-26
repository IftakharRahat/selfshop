"use client";

import { useEffect } from "react";

type NotificationPanel = "user" | "supplier";

type Props = {
	panel: NotificationPanel;
	userId?: number | string | null;
	enabled?: boolean;
};

const ONE_SIGNAL_APP_ID = process.env.NEXT_PUBLIC_ONESIGNAL_APP_ID ?? "";
const ONE_SIGNAL_USER_APP_ID =
	process.env.NEXT_PUBLIC_ONESIGNAL_USER_APP_ID ?? ONE_SIGNAL_APP_ID;
const ONE_SIGNAL_SUPPLIER_APP_ID =
	process.env.NEXT_PUBLIC_ONESIGNAL_SUPPLIER_APP_ID ?? "";
const ONE_SIGNAL_SAFARI_WEB_ID =
	process.env.NEXT_PUBLIC_ONESIGNAL_SAFARI_WEB_ID ?? "";

function resolveOneSignalAppId(panel: NotificationPanel): string {
	if (panel === "supplier") {
		return ONE_SIGNAL_SUPPLIER_APP_ID || ONE_SIGNAL_USER_APP_ID || ONE_SIGNAL_APP_ID;
	}

	return ONE_SIGNAL_USER_APP_ID || ONE_SIGNAL_APP_ID || ONE_SIGNAL_SUPPLIER_APP_ID;
}

function playForegroundNotificationSound() {
	try {
		const AudioContextClass =
			window.AudioContext ||
			(window as Window & typeof globalThis & { webkitAudioContext?: typeof AudioContext })
				.webkitAudioContext;

		if (!AudioContextClass) {
			return;
		}

		const audioContext = new AudioContextClass();
		const oscillator = audioContext.createOscillator();
		const gainNode = audioContext.createGain();

		oscillator.type = "sine";
		oscillator.frequency.setValueAtTime(880, audioContext.currentTime);
		gainNode.gain.setValueAtTime(0.0001, audioContext.currentTime);
		gainNode.gain.exponentialRampToValueAtTime(0.18, audioContext.currentTime + 0.01);
		gainNode.gain.exponentialRampToValueAtTime(0.0001, audioContext.currentTime + 0.22);

		oscillator.connect(gainNode);
		gainNode.connect(audioContext.destination);

		oscillator.start(audioContext.currentTime);
		oscillator.stop(audioContext.currentTime + 0.24);

		window.setTimeout(() => {
			void audioContext.close().catch(() => undefined);
		}, 350);
	} catch {
		// Best effort only.
	}
}

function pushToOneSignalQueue(
	callback: (oneSignal: OneSignalInstance) => void | Promise<void>,
) {
	window.OneSignalDeferred = window.OneSignalDeferred || [];
	window.OneSignalDeferred.push(callback);
}

async function ensureOneSignalInitialized(
	oneSignal: OneSignalInstance,
	appId: string,
) {
	if (
		window.__oneSignalInitializedAppId &&
		window.__oneSignalInitializedAppId !== appId
	) {
		console.warn(
			"OneSignal already initialized with another app id in this tab. Reload this page to switch app context.",
		);
		return;
	}

	if (!window.__oneSignalInitPromise) {
		const initOptions: OneSignalInitOptions = {
			appId,
			allowLocalhostAsSecureOrigin: true,
			notifyButton: { enable: false },
			serviceWorkerPath: "/OneSignalSDKWorker.js",
		};

		if (ONE_SIGNAL_SAFARI_WEB_ID) {
			initOptions.safari_web_id = ONE_SIGNAL_SAFARI_WEB_ID;
		}

		window.__oneSignalInitializedAppId = appId;
		window.__oneSignalInitPromise = oneSignal.init(initOptions);
	}

	await window.__oneSignalInitPromise;
}

function ensureForegroundSoundListener(oneSignal: OneSignalInstance) {
	if (window.__oneSignalForegroundListenerBound) {
		return;
	}

	oneSignal.Notifications.addEventListener(
		"foregroundWillDisplay",
		(event: OneSignalForegroundEvent) => {
			const additionalData = event?.notification?.additionalData ?? {};
			const shouldPlaySound = additionalData.play_sound !== false;

			if (shouldPlaySound) {
				playForegroundNotificationSound();
			}
		},
	);

	window.__oneSignalForegroundListenerBound = true;
}

function getPromptStorageKey(appId: string, panel: NotificationPanel, userId: string) {
	return `onesignal_prompted:${appId}:${panel}:${userId}`;
}

export default function OneSignalInitializer({
	panel,
	userId,
	enabled = true,
}: Props) {
	useEffect(() => {
		const oneSignalAppId = resolveOneSignalAppId(panel);

		if (typeof window === "undefined" || !oneSignalAppId) {
			return;
		}

		const normalizedUserId =
			userId === null || userId === undefined ? "" : String(userId).trim();

		if (!enabled || normalizedUserId === "") {
			return;
		}

		const externalUserId = `${panel}:${normalizedUserId}`;
		const promptStorageKey = getPromptStorageKey(
			oneSignalAppId,
			panel,
			normalizedUserId,
		);

		pushToOneSignalQueue(async (oneSignal) => {
			try {
				await ensureOneSignalInitialized(oneSignal, oneSignalAppId);
				ensureForegroundSoundListener(oneSignal);

				await oneSignal.login(externalUserId);
				await oneSignal.User.addTag("panel", panel);
				await oneSignal.User.addTag("user_id", normalizedUserId);
				await oneSignal.User.addTag("role", panel);

				const pushSupported = oneSignal.Notifications.isPushSupported();
				if (!pushSupported) {
					return;
				}

				const hasPermission = Boolean(oneSignal.Notifications.permission);
				const alreadyPrompted = window.localStorage.getItem(promptStorageKey) === "1";

				if (!hasPermission && !alreadyPrompted) {
					window.localStorage.setItem(promptStorageKey, "1");
					await oneSignal.Notifications.requestPermission();
				}
			} catch (error) {
				console.error("OneSignal init failed:", error);
			}
		});
	}, [enabled, panel, userId]);

	return null;
}
