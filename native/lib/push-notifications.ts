import Constants from "expo-constants";
import * as Notifications from "expo-notifications";
import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

import apiClient from "@/lib/api-client";

const REGISTERED_PUSH_TOKEN_KEY = "selfshop_registered_push_token";
const REGISTERED_PUSH_TOKEN_USER_KEY = "selfshop_registered_push_token_user";

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

async function ensureAndroidNotificationChannelAsync() {
  if (Platform.OS !== "android") return;

  await Notifications.setNotificationChannelAsync("default", {
    name: "Default",
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 100, 250],
    lightColor: "#E5005F",
  });
}

function getDeviceInfo() {
  return [
    Platform.OS,
    `version:${Platform.Version}`,
    `ownership:${Constants.appOwnership ?? "standalone"}`,
    `runtime:${Constants.executionEnvironment ?? "unknown"}`,
  ].join("; ");
}

function getTokenData(token: Notifications.DevicePushToken): string | null {
  return typeof token.data === "string" && token.data.length > 0 ? token.data : null;
}

export function canUseNativePushNotifications() {
  return Platform.OS === "android" && Constants.appOwnership !== "expo";
}

export async function registerPushTokenWithBackendAsync(
  token: Notifications.DevicePushToken,
  userId: number,
) {
  if (token.type !== "android") return null;

  const tokenData = getTokenData(token);
  if (!tokenData) return null;

  const storedToken = await SecureStore.getItemAsync(REGISTERED_PUSH_TOKEN_KEY);
  const storedUserId = await SecureStore.getItemAsync(REGISTERED_PUSH_TOKEN_USER_KEY);
  const currentUserId = String(userId);

  if (storedToken === tokenData && storedUserId === currentUserId) {
    return tokenData;
  }

  await apiClient.post("/fcm-token", {
    token: tokenData,
    device_info: getDeviceInfo(),
  });

  await SecureStore.setItemAsync(REGISTERED_PUSH_TOKEN_KEY, tokenData);
  await SecureStore.setItemAsync(REGISTERED_PUSH_TOKEN_USER_KEY, currentUserId);

  return tokenData;
}

export async function registerDeviceForPushNotificationsAsync(userId: number) {
  if (!canUseNativePushNotifications()) {
    if (__DEV__) {
      console.info("[Push] Native Android push requires a development build or APK.");
    }
    return null;
  }

  await ensureAndroidNotificationChannelAsync();

  const currentPermissions = await Notifications.getPermissionsAsync();
  let finalStatus = currentPermissions.status;

  if (finalStatus !== "granted") {
    const requestedPermissions = await Notifications.requestPermissionsAsync();
    finalStatus = requestedPermissions.status;
  }

  if (finalStatus !== "granted") {
    if (__DEV__) {
      console.info("[Push] Notification permission was not granted.");
    }
    return null;
  }

  const token = await Notifications.getDevicePushTokenAsync();
  return registerPushTokenWithBackendAsync(token, userId);
}

export async function unregisterDevicePushTokenAsync() {
  const token = await SecureStore.getItemAsync(REGISTERED_PUSH_TOKEN_KEY);

  try {
    if (token) {
      await apiClient.delete("/fcm-token", {
        data: { token },
      });
    }
  } catch (error) {
    if (__DEV__) {
      console.warn("[Push] Failed to unregister FCM token.", error);
    }
  } finally {
    await SecureStore.deleteItemAsync(REGISTERED_PUSH_TOKEN_KEY);
    await SecureStore.deleteItemAsync(REGISTERED_PUSH_TOKEN_USER_KEY);
  }
}
