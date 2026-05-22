import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  RefreshControl,
  Linking,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const { width } = Dimensions.get("window");
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

export default function FreeCoursesScreen() {
  const queryClient = useQueryClient();
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();

  const coursesQuery = useQuery({
    queryKey: ["free-courses"],
    queryFn: async () => {
      const { data } = await apiClient.get("/view-course");
      return data?.data ?? data ?? [];
    },
    enabled: isResellerActive,
  });

  const courses: any[] = Array.isArray(coursesQuery.data) ? coursesQuery.data : [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["free-courses"] });
  }, [queryClient]);

  if (isSubscriptionLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Free Courses", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <View style={styles.emptyState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Free Courses", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <SubscriptionRequired
          title="Activate to View Courses"
          message="Activate your subscription to access reseller courses and learning content."
        />
      </>
    );
  }

  const renderCourse = ({ item }: { item: any }) => {
    const imageUri = resolveImageUrl(item.coursecategory_image);
    const totalCourses = item.totalcourse ?? 0;
    const hasVideo = !!item.youtube_embade;

    return (
      <Pressable
        style={({ pressed }) => [
          styles.courseCard,
          pressed && { opacity: 0.88, transform: [{ scale: 0.98 }] },
        ]}
        onPress={() =>
          router.push({
            pathname: "/account/course-detail",
            params: { slug: item.slug },
          } as any)
        }
      >
        {/* Thumbnail */}
        <View style={styles.thumbnailWrapper}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={styles.thumbnail} resizeMode="cover" />
          ) : (
            <View style={[styles.thumbnail, styles.thumbnailPlaceholder]}>
              <Ionicons name="book-outline" size={32} color="rgba(255,255,255,0.7)" />
            </View>
          )}

          {/* Play Button Overlay */}
          {hasVideo && (
            <View style={styles.playOverlay}>
              <View style={styles.playButton}>
                <Ionicons name="play" size={20} color="#fff" />
              </View>
            </View>
          )}

          {/* Title Overlay */}
          <View style={styles.titleOverlay}>
            <Text style={styles.courseTitle} numberOfLines={2}>
              {item.coursecategory_name}
            </Text>
            <Text style={styles.courseCount}>
              {totalCourses} {totalCourses === 1 ? "course" : "courses"}
            </Text>
          </View>
        </View>

        {/* Info */}
        <View style={styles.courseInfo}>
          <Text style={styles.courseInfoText}>
            {hasVideo ? "📺 Video available — tap to watch" : "📖 Tap to view course content"}
          </Text>
        </View>
      </Pressable>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Free Courses",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        <FlatList
          data={courses}
          renderItem={renderCourse}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={[
            styles.listContent,
            courses.length === 0 && { flex: 1 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={coursesQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          ListHeaderComponent={
            courses.length > 0 ? (
              <Text style={styles.headerCount}>
                Free Courses ({courses.length})
              </Text>
            ) : null
          }
          ListEmptyComponent={
            coursesQuery.isLoading ? (
              <View style={styles.emptyState}>
                <ActivityIndicator size="large" color={ACCENT} />
              </View>
            ) : (
              <View style={styles.emptyState}>
                <View style={styles.emptyIcon}>
                  <Ionicons name="book-outline" size={36} color={ACCENT} />
                </View>
                <Text fontSize="$5" fontWeight="700" color="#1A1A2E" mt="$3">
                  No Courses Available
                </Text>
                <Text fontSize="$2" color="#9CA3AF" mt="$1" style={{ textAlign: "center" }}>
                  Free courses will appear here once published. Check back soon!
                </Text>
              </View>
            )
          }
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  listContent: { padding: 16, gap: 16, paddingBottom: 40 },
  headerCount: { fontSize: 16, fontWeight: "700", color: "#1A1A2E", marginBottom: 4 },

  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", paddingHorizontal: 40, paddingBottom: 60 },
  emptyIcon: {
    width: 64,
    height: 64,
    borderRadius: 22,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },

  courseCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  thumbnailWrapper: {
    width: "100%",
    height: 180,
    backgroundColor: "#1F2937",
    position: "relative",
  },
  thumbnail: {
    width: "100%",
    height: "100%",
  },
  thumbnailPlaceholder: {
    backgroundColor: "#E5005F",
    justifyContent: "center",
    alignItems: "center",
  },

  playOverlay: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center",
    alignItems: "center",
  },
  playButton: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: "#DC2626",
    justifyContent: "center",
    alignItems: "center",
    paddingLeft: 3,
  },

  titleOverlay: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: "rgba(0,0,0,0.55)",
  },
  courseTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#fff",
    lineHeight: 22,
  },
  courseCount: {
    fontSize: 11,
    color: "rgba(255,255,255,0.8)",
    marginTop: 2,
  },

  courseInfo: {
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  courseInfoText: {
    fontSize: 12,
    color: "#6B7280",
  },
});
