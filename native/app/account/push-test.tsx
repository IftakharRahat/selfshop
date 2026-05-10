import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
} from "react-native";
import * as Clipboard from "expo-clipboard";
import Constants from "expo-constants";
import * as Notifications from "expo-notifications";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { Text } from "tamagui";
import { toast } from "sonner-native";

const ACCENT = "#E5005F";

function getRuntimeLabel() {
  if (Constants.appOwnership === "expo") return "Expo Go";
  if (Constants.appOwnership === "standalone") return "Standalone build";
  return Constants.appOwnership ?? "Development build";
}

async function ensureTestChannelAsync() {
  if (Platform.OS !== "android") return;

  await Notifications.setNotificationChannelAsync("default", {
    name: "Default",
    importance: Notifications.AndroidImportance.MAX,
    vibrationPattern: [0, 250, 100, 250],
    lightColor: ACCENT,
  });
}

async function ensureNotificationPermissionAsync() {
  const current = await Notifications.getPermissionsAsync();
  if (current.status === "granted") return true;

  const requested = await Notifications.requestPermissionsAsync();
  return requested.status === "granted";
}

function StatusRow({
  icon,
  label,
  value,
}: {
  icon: string;
  label: string;
  value: string;
}) {
  return (
    <View style={styles.statusRow}>
      <View style={styles.statusIcon}>
        <Ionicons name={icon as any} size={18} color={ACCENT} />
      </View>
      <View style={styles.statusText}>
        <Text fontSize="$2" color="#8E8E93">
          {label}
        </Text>
        <Text fontSize="$3" fontWeight="600" color="#1A1A2E">
          {value}
        </Text>
      </View>
    </View>
  );
}

export default function PushTestScreen() {
  const [permissionStatus, setPermissionStatus] = useState<string>("Not checked");
  const [deviceToken, setDeviceToken] = useState<string>("");
  const [isLoadingToken, setIsLoadingToken] = useState(false);
  const [isSendingLocal, setIsSendingLocal] = useState(false);

  const sendLocalNotification = useCallback(async () => {
    setIsSendingLocal(true);
    try {
      await ensureTestChannelAsync();
      const granted = await ensureNotificationPermissionAsync();
      setPermissionStatus(granted ? "Granted" : "Denied");

      if (!granted) {
        toast.error("Notification permission was not granted");
        return;
      }

      await Notifications.scheduleNotificationAsync({
        content: {
          title: "SelfShop test notification",
          body: "Local notifications are working on this device.",
          sound: true,
          data: { route: "/account/notifications", source: "push-test" },
        },
        trigger: null,
      });

      toast.success("Local test notification sent");
    } catch (error) {
      if (__DEV__) console.warn("[PushTest] Local notification failed.", error);
      toast.error("Failed to send local notification");
    } finally {
      setIsSendingLocal(false);
    }
  }, []);

  const fetchDeviceToken = useCallback(async () => {
    setIsLoadingToken(true);
    try {
      await ensureTestChannelAsync();
      const granted = await ensureNotificationPermissionAsync();
      setPermissionStatus(granted ? "Granted" : "Denied");

      if (!granted) {
        toast.error("Notification permission was not granted");
        return;
      }

      if (Platform.OS !== "android") {
        toast.error("This test is configured for Android FCM tokens");
        return;
      }

      if (Constants.appOwnership === "expo") {
        toast.error("Use an APK or development build, not Expo Go");
        return;
      }

      const token = await Notifications.getDevicePushTokenAsync();
      const tokenData = typeof token.data === "string" ? token.data : "";

      if (!tokenData) {
        toast.error("No FCM token returned");
        return;
      }

      setDeviceToken(tokenData);
      toast.success("FCM token loaded");
    } catch (error) {
      if (__DEV__) console.warn("[PushTest] Failed to fetch FCM token.", error);
      toast.error("Failed to fetch FCM token");
    } finally {
      setIsLoadingToken(false);
    }
  }, []);

  const copyDeviceToken = useCallback(async () => {
    if (!deviceToken) return;
    await Clipboard.setStringAsync(deviceToken);
    toast.success("FCM token copied");
  }, [deviceToken]);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Push Test",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.hero}>
          <View style={styles.heroIcon}>
            <Ionicons name="notifications" size={30} color="#fff" />
          </View>
          <Text style={styles.heroTitle}>Push Notification Test</Text>
          <Text style={styles.heroCopy}>
            Check local notification display and copy the native Android FCM token for Firebase Console testing.
          </Text>
        </View>

        <View style={styles.statusCard}>
          <StatusRow icon="phone-portrait-outline" label="Platform" value={Platform.OS} />
          <StatusRow icon="construct-outline" label="Runtime" value={getRuntimeLabel()} />
          <StatusRow icon="shield-checkmark-outline" label="Permission" value={permissionStatus} />
        </View>

        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <Ionicons name="flash-outline" size={20} color={ACCENT} />
            <Text fontSize="$5" fontWeight="700" color="#1A1A2E">
              Local Notification
            </Text>
          </View>
          <Text style={styles.cardCopy}>
            Sends a notification from the app itself. This confirms permission, foreground display, sound, and Android channel behavior.
          </Text>
          <Pressable
            style={({ pressed }) => [
              styles.primaryButton,
              pressed && { opacity: 0.86 },
              isSendingLocal && { opacity: 0.7 },
            ]}
            onPress={sendLocalNotification}
            disabled={isSendingLocal}
          >
            {isSendingLocal ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <>
                <Ionicons name="send" size={18} color="#fff" />
                <Text fontSize="$3" fontWeight="700" color="#fff">
                  Send Local Test
                </Text>
              </>
            )}
          </Pressable>
        </View>

        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <Ionicons name="key-outline" size={20} color={ACCENT} />
            <Text fontSize="$5" fontWeight="700" color="#1A1A2E">
              Native FCM Token
            </Text>
          </View>
          <Text style={styles.cardCopy}>
            Fetches the Android FCM registration token without registering it to the backend. Use this token in Firebase Console test messaging.
          </Text>
          <Pressable
            style={({ pressed }) => [
              styles.secondaryButton,
              pressed && { opacity: 0.86 },
              isLoadingToken && { opacity: 0.7 },
            ]}
            onPress={fetchDeviceToken}
            disabled={isLoadingToken}
          >
            {isLoadingToken ? (
              <ActivityIndicator color={ACCENT} />
            ) : (
              <>
                <Ionicons name="refresh" size={18} color={ACCENT} />
                <Text fontSize="$3" fontWeight="700" color={ACCENT}>
                  Get FCM Token
                </Text>
              </>
            )}
          </Pressable>

          {deviceToken ? (
            <View style={styles.tokenBox}>
              <Text style={styles.tokenText} selectable>
                {deviceToken}
              </Text>
              <Pressable
                style={({ pressed }) => [
                  styles.copyButton,
                  pressed && { opacity: 0.82 },
                ]}
                onPress={copyDeviceToken}
              >
                <Ionicons name="copy-outline" size={17} color="#fff" />
                <Text fontSize="$2" fontWeight="700" color="#fff">
                  Copy
                </Text>
              </Pressable>
            </View>
          ) : null}
        </View>

        <View style={styles.note}>
          <Ionicons name="information-circle-outline" size={18} color="#6B7280" />
          <Text style={styles.noteText}>
            Real remote push still needs a real APK/development build and Firebase delivery. Expo Go cannot validate this native FCM path.
          </Text>
        </View>
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F8F8FA",
  },
  content: {
    padding: 20,
    paddingBottom: 44,
    gap: 14,
  },
  hero: {
    backgroundColor: "#1A1A2E",
    borderRadius: 18,
    padding: 20,
    overflow: "hidden",
  },
  heroIcon: {
    width: 54,
    height: 54,
    borderRadius: 18,
    backgroundColor: ACCENT,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 16,
  },
  heroTitle: {
    fontSize: 22,
    fontWeight: "800",
    color: "#fff",
  },
  heroCopy: {
    color: "rgba(255,255,255,0.72)",
    fontSize: 13,
    lineHeight: 19,
    marginTop: 6,
  },
  statusCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    overflow: "hidden",
  },
  statusRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 13,
    borderBottomWidth: 1,
    borderBottomColor: "#F5F5F5",
  },
  statusIcon: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  statusText: {
    flex: 1,
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    padding: 16,
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  cardCopy: {
    color: "#6B7280",
    fontSize: 13,
    lineHeight: 19,
    marginTop: 8,
    marginBottom: 14,
  },
  primaryButton: {
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: ACCENT,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
  },
  secondaryButton: {
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: "#FDF2F8",
    borderWidth: 1,
    borderColor: "#FCE7F3",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
  },
  tokenBox: {
    marginTop: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    backgroundColor: "#F9FAFB",
    padding: 12,
  },
  tokenText: {
    color: "#374151",
    fontSize: 12,
    lineHeight: 18,
  },
  copyButton: {
    alignSelf: "flex-start",
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#1A1A2E",
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginTop: 12,
  },
  note: {
    flexDirection: "row",
    gap: 8,
    backgroundColor: "#F3F4F6",
    borderRadius: 14,
    padding: 14,
  },
  noteText: {
    flex: 1,
    color: "#6B7280",
    fontSize: 12,
    lineHeight: 18,
  },
});
