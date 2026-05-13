import { useCallback } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Image,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const { width } = Dimensions.get("window");

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
  if (!value) return "Recently";
  const date = new Date(value);
  if (isNaN(date.getTime())) return value;
  return date.toLocaleDateString("en-GB", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

export default function AnnouncementDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const queryClient = useQueryClient();

  // Reuse the same query key so we benefit from existing cache
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
  });

  const announcements: any[] = announcementsQuery.data?.announcements ?? [];
  const announcement = announcements.find(
    (item: any) => String(item.id) === String(id),
  );

  const isRefreshing = announcementsQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["announcements"] });
  }, [queryClient]);

  const imageUrl = resolveImageUrl(announcement?.image ?? announcement?.banner);
  const title = announcement?.title ?? "Announcement";
  const description =
    announcement?.description ?? announcement?.message ?? announcement?.content ?? "";
  const dateStr = formatDate(announcement?.published_at ?? announcement?.created_at);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Announcement",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />
        }
      >
        {announcementsQuery.isLoading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color={ACCENT} />
          </View>
        ) : !announcement ? (
          <View style={styles.emptyState}>
            <Ionicons name="megaphone-outline" size={56} color="#D1D5DB" />
            <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
              Announcement Not Found
            </Text>
            <Text fontSize="$2" color="#C7C7CC" mt="$1" style={{ textAlign: "center" }}>
              This announcement may have been removed
            </Text>
          </View>
        ) : (
          <View style={styles.contentWrapper}>
            {/* Hero Image */}
            {imageUrl ? (
              <Image
                source={{ uri: imageUrl }}
                style={styles.heroImage}
                resizeMode="cover"
              />
            ) : (
              <LinearGradient
                colors={["#FFF1F6", "#FDEEF5", "#F8F8FA"]}
                style={styles.heroPlaceholder}
              >
                <View style={styles.heroIcon}>
                  <Ionicons name="megaphone" size={40} color={ACCENT} />
                </View>
              </LinearGradient>
            )}

            {/* Content Card */}
            <View style={styles.card}>
              {/* Badge */}
              <View style={styles.badge}>
                <Ionicons name="sparkles-outline" size={13} color={ACCENT} />
                <Text style={styles.badgeText}>Announcement</Text>
              </View>

              {/* Title */}
              <Text style={styles.title}>{title}</Text>

              {/* Date */}
              <View style={styles.dateRow}>
                <Ionicons name="calendar-outline" size={14} color="#9CA3AF" />
                <Text style={styles.dateText}>{dateStr}</Text>
              </View>

              {/* Divider */}
              <View style={styles.divider} />

              {/* Description */}
              {description ? (
                <Text style={styles.description}>{description}</Text>
              ) : (
                <Text style={styles.noContent}>No additional details available.</Text>
              )}
            </View>
          </View>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F8F8FA",
  },
  loadingContainer: {
    paddingVertical: 80,
    alignItems: "center",
  },
  emptyState: {
    alignItems: "center",
    paddingVertical: 80,
    paddingHorizontal: 40,
  },
  contentWrapper: {
    flex: 1,
  },
  heroImage: {
    width: "100%",
    height: 220,
    backgroundColor: "#F3F4F6",
  },
  heroPlaceholder: {
    width: "100%",
    height: 180,
    alignItems: "center",
    justifyContent: "center",
  },
  heroIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "#fff",
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1,
    borderColor: "#FCE7F3",
    shadowColor: "#E5005F",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.1,
    shadowRadius: 12,
    elevation: 4,
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 20,
    marginHorizontal: 16,
    marginTop: -24,
    padding: 20,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 3,
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
    lineHeight: 30,
    fontWeight: "800",
    color: "#1A1A2E",
  },
  dateRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginTop: 10,
  },
  dateText: {
    fontSize: 13,
    color: "#9CA3AF",
    fontWeight: "600",
  },
  divider: {
    height: 1,
    backgroundColor: "#F0F0F5",
    marginVertical: 18,
  },
  description: {
    fontSize: 15,
    lineHeight: 24,
    color: "#4B5563",
  },
  noContent: {
    fontSize: 14,
    color: "#9CA3AF",
    fontStyle: "italic",
  },
});
