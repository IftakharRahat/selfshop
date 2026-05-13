import { useCallback, useMemo, useState } from "react";
import {
  Image,
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { useQuery } from "@tanstack/react-query";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Text } from "tamagui";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string | null {
  if (!path || path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

function formatDate(value?: string | null): string {
  if (!value) return "Latest update";
  const date = new Date(value);
  if (isNaN(date.getTime())) return "Latest update";
  return date.toLocaleDateString("en-GB", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

function getAnnouncementKey(announcement: any): string {
  const rawKey =
    announcement?.id ??
    announcement?.uuid ??
    announcement?.slug ??
    `${announcement?.title ?? "announcement"}-${announcement?.published_at ?? announcement?.created_at ?? ""}`;
  return String(rawKey);
}

export function LaunchAnnouncementPopup() {
  const insets = useSafeAreaInsets();

  // Session-only dismissal state — resets every time the app opens
  const [dismissedKeys, setDismissedKeys] = useState<string[]>([]);

  const announcementsQuery = useQuery({
    queryKey: ["announcements"],
    queryFn: async () => {
      try {
        const { data } = await apiClient.get("/announcements");
        return data?.data ?? data ?? { announcements: [] };
      } catch {
        return { announcements: [] };
      }
    },
    staleTime: 2 * 60 * 1000,
  });

  const activeAnnouncements = useMemo(() => {
    const announcements = announcementsQuery.data?.announcements;
    if (!Array.isArray(announcements) || announcements.length === 0) {
      return [];
    }
    // The API already filters by status='Active', so all returned
    // announcements are valid. No further date filtering needed.
    return announcements;
  }, [announcementsQuery.data]);

  const pendingAnnouncements = useMemo(
    () =>
      activeAnnouncements.filter(
        (announcement: any) => !dismissedKeys.includes(getAnnouncementKey(announcement)),
      ),
    [activeAnnouncements, dismissedKeys],
  );

  const currentAnnouncement = pendingAnnouncements[0] ?? null;
  const currentAnnouncementKey = currentAnnouncement ? getAnnouncementKey(currentAnnouncement) : "";
  const remainingCount = pendingAnnouncements.length;
  const visible = Boolean(currentAnnouncement);
  const imageUrl = resolveImageUrl(currentAnnouncement?.image ?? currentAnnouncement?.banner);
  const publishedDate = formatDate(
    currentAnnouncement?.published_at ?? currentAnnouncement?.created_at,
  );

  // Dismiss only for this session — next app open will show it again
  const dismissCurrentAnnouncement = useCallback(() => {
    if (!currentAnnouncementKey) return;
    setDismissedKeys((prev) => {
      if (prev.includes(currentAnnouncementKey)) return prev;
      return [...prev, currentAnnouncementKey];
    });
  }, [currentAnnouncementKey]);

  if (!currentAnnouncement) return null;

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      statusBarTranslucent
      onRequestClose={dismissCurrentAnnouncement}
    >
      <View style={[styles.overlay, { paddingTop: insets.top + 24 }]}>
        <Pressable style={styles.backdrop} onPress={dismissCurrentAnnouncement} />
        <View style={styles.card}>
          <Pressable
            style={styles.closeButton}
            onPress={dismissCurrentAnnouncement}
            hitSlop={10}
          >
            <Ionicons name="close" size={22} color="#1A1A2E" />
          </Pressable>

          {imageUrl ? (
            <Image source={{ uri: imageUrl }} style={styles.heroImage} resizeMode="cover" />
          ) : (
            <LinearGradient
              colors={["#FFF1F6", "#FFFFFF"]}
              style={styles.textHero}
            >
              <View style={styles.heroIcon}>
                <Ionicons name="megaphone" size={30} color={ACCENT} />
              </View>
            </LinearGradient>
          )}

          <View style={styles.content}>
            <View style={styles.badge}>
              <Ionicons name="sparkles-outline" size={13} color={ACCENT} />
              <Text style={styles.badgeText}>Announcement</Text>
            </View>

            <Text style={styles.title} numberOfLines={2}>
              {currentAnnouncement.title || "New announcement"}
            </Text>

            <Text style={styles.dateText}>{publishedDate}</Text>

            {(currentAnnouncement.description ||
              currentAnnouncement.message ||
              currentAnnouncement.content) ? (
              <ScrollView
                style={styles.descriptionScroll}
                showsVerticalScrollIndicator={false}
              >
                <Text style={styles.description}>
                  {currentAnnouncement.description ||
                    currentAnnouncement.message ||
                    currentAnnouncement.content}
                </Text>
              </ScrollView>
            ) : null}

            {remainingCount > 1 ? (
              <Text style={styles.stackText}>
                {remainingCount - 1} more announcement{remainingCount - 1 !== 1 ? "s" : ""}
              </Text>
            ) : null}

            <Pressable
              style={({ pressed }) => [
                styles.doneButton,
                pressed && { opacity: 0.88, transform: [{ scale: 0.98 }] },
              ]}
              onPress={dismissCurrentAnnouncement}
            >
              <Text style={styles.doneButtonText}>
                {remainingCount > 1 ? "Next" : "Got it"}
              </Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    justifyContent: "center",
    paddingHorizontal: 22,
    paddingBottom: 24,
  },
  backdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(17,24,39,0.58)",
  },
  card: {
    width: "100%",
    maxHeight: "86%",
    borderRadius: 24,
    backgroundColor: "#fff",
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "rgba(255,255,255,0.65)",
  },
  closeButton: {
    position: "absolute",
    top: 12,
    right: 12,
    zIndex: 2,
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: "rgba(255,255,255,0.94)",
    alignItems: "center",
    justifyContent: "center",
  },
  heroImage: {
    width: "100%",
    height: 190,
    backgroundColor: "#F3F4F6",
  },
  textHero: {
    height: 132,
    alignItems: "center",
    justifyContent: "center",
  },
  heroIcon: {
    width: 62,
    height: 62,
    borderRadius: 31,
    backgroundColor: "#fff",
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: "#FCE7F3",
  },
  content: {
    padding: 20,
  },
  badge: {
    alignSelf: "flex-start",
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#FDF2F8",
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginBottom: 12,
  },
  badgeText: {
    fontSize: 11,
    fontWeight: "800",
    color: ACCENT,
    textTransform: "uppercase",
  },
  title: {
    fontSize: 22,
    lineHeight: 28,
    fontWeight: "800",
    color: "#1A1A2E",
  },
  dateText: {
    fontSize: 12,
    color: "#9CA3AF",
    fontWeight: "600",
    marginTop: 6,
  },
  descriptionScroll: {
    maxHeight: 150,
    marginTop: 12,
  },
  description: {
    fontSize: 14,
    lineHeight: 21,
    color: "#4B5563",
  },
  stackText: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "700",
    textAlign: "center",
    marginTop: 14,
  },
  doneButton: {
    alignItems: "center",
    justifyContent: "center",
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: ACCENT,
    marginTop: 18,
  },
  doneButtonText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#fff",
  },
});
