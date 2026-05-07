import { useCallback } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Image,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

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

export default function AnnouncementsScreen() {
  const queryClient = useQueryClient();

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

  const isRefreshing = announcementsQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["announcements"] });
  }, [queryClient]);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Announcements",
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
        {/* Header */}
        <View style={styles.headerSection}>
          <View style={styles.headerIcon}>
            <Ionicons name="megaphone" size={28} color={ACCENT} />
          </View>
          <Text fontSize="$5" fontWeight="700" color="#1A1A2E" mt="$3">
            Announcements
          </Text>
          <Text fontSize="$3" color="#6B7280" textAlign="center" mt="$1">
            Stay updated with the latest news
          </Text>
        </View>

        {/* List */}
        <View style={styles.section}>
          {announcementsQuery.isLoading ? (
            <ActivityIndicator size="large" color={ACCENT} style={{ marginVertical: 40 }} />
          ) : announcements.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="megaphone-outline" size={56} color="#D1D5DB" />
              <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                No Announcements
              </Text>
              <Text fontSize="$2" color="#C7C7CC" mt="$1" textAlign="center">
                Check back later for updates and news
              </Text>
            </View>
          ) : (
            announcements.map((item: any, index: number) => {
              const imageUrl = resolveImageUrl(item.image ?? item.banner);
              return (
                <View key={item.id ?? index} style={styles.card}>
                  {imageUrl && (
                    <Image
                      source={{ uri: imageUrl }}
                      style={styles.cardImage}
                      resizeMode="cover"
                    />
                  )}
                  <View style={styles.cardContent}>
                    <View style={styles.cardHeader}>
                      <View style={styles.dateBadge}>
                        <Ionicons name="time-outline" size={12} color="#6B7280" />
                        <Text fontSize={11} color="#6B7280" ml={4}>
                          {item.created_at
                            ? new Date(item.created_at).toLocaleDateString("en-GB", {
                                day: "numeric",
                                month: "short",
                                year: "numeric",
                              })
                            : "Recently"}
                        </Text>
                      </View>
                    </View>
                    {item.title ? (
                      <Text fontSize="$4" fontWeight="700" color="#1A1A2E" mt="$1">
                        {item.title}
                      </Text>
                    ) : null}
                    {(item.description || item.message || item.content) ? (
                      <Text fontSize="$3" color="#6B7280" mt="$1" lineHeight={20}>
                        {item.description || item.message || item.content}
                      </Text>
                    ) : null}
                  </View>
                </View>
              );
            })
          )}
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  headerSection: {
    alignItems: "center",
    paddingVertical: 24,
    paddingHorizontal: 20,
  },
  headerIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },

  section: {
    paddingHorizontal: 16,
    marginTop: 4,
  },

  emptyState: {
    alignItems: "center",
    paddingVertical: 50,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },

  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    overflow: "hidden",
  },
  cardImage: {
    width: "100%",
    height: 160,
  },
  cardContent: {
    padding: 16,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  dateBadge: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F3F4F6",
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
});
