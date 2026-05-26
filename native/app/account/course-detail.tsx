import { useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  Linking,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

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

export default function CourseDetailScreen() {
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();
  const { slug } = useLocalSearchParams<{ slug: string }>();

  const courseQuery = useQuery({
    queryKey: ["course-detail", slug],
    queryFn: async () => {
      const { data } = await apiClient.get(`/course-details/${slug}`);
      return data?.data ?? data;
    },
    enabled: !!slug && isResellerActive,
  });

  const course = courseQuery.data;
  const lessons: any[] = course?.courses ?? course?.lessons ?? [];

  if (isSubscriptionLoading || courseQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Course", headerShadowVisible: false }} />
        <View style={styles.loadingState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Course", headerShadowVisible: false }} />
        <SubscriptionRequired
          title="Activate to View Course"
          message="Activate your subscription to access reseller learning content."
        />
      </>
    );
  }

  if (!course) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Course", headerShadowVisible: false }} />
        <View style={styles.emptyState}>
          <Ionicons name="book-outline" size={48} color="#D1D5DB" />
          <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">Course not found</Text>
          <Pressable onPress={() => router.back()} style={{ marginTop: 16 }}>
            <Text fontSize="$3" fontWeight="600" color={ACCENT}>← Go Back</Text>
          </Pressable>
        </View>
      </>
    );
  }

  const imageUri = resolveImageUrl(course.coursecategory_image ?? course.image);
  const title = course.coursecategory_name ?? course.title ?? "Course";

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: title,
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Hero Image */}
        {imageUri && (
          <Image source={{ uri: imageUri }} style={styles.heroImage} resizeMode="cover" />
        )}

        {/* Course Info */}
        <View style={styles.infoCard}>
          <Text style={styles.courseTitle}>{title}</Text>
          <Text style={styles.lessonCount}>
            {lessons.length} {lessons.length === 1 ? "lesson" : "lessons"}
          </Text>
        </View>

        {/* Lessons List */}
        {lessons.length > 0 ? (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Lessons</Text>
            {lessons.map((lesson: any, index: number) => {
              const hasVideo = !!lesson.youtube_embade;
              return (
                <Pressable
                  key={lesson.id ?? index}
                  style={({ pressed }) => [
                    styles.lessonCard,
                    pressed && { opacity: 0.85 },
                  ]}
                  onPress={() => {
                    if (hasVideo) {
                      Linking.openURL(`https://www.youtube.com/watch?v=${lesson.youtube_embade}`);
                    }
                  }}
                >
                  <View style={styles.lessonNumber}>
                    <Text style={styles.lessonNumberText}>{index + 1}</Text>
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.lessonTitle} numberOfLines={2}>
                      {lesson.course_name ?? lesson.title ?? `Lesson ${index + 1}`}
                    </Text>
                    {lesson.course_description && (
                      <Text style={styles.lessonDesc} numberOfLines={2}>
                        {lesson.course_description}
                      </Text>
                    )}
                  </View>
                  {hasVideo && (
                    <View style={styles.videoIcon}>
                      <Ionicons name="play-circle" size={24} color="#DC2626" />
                    </View>
                  )}
                </Pressable>
              );
            })}
          </View>
        ) : (
          <View style={styles.emptyLessons}>
            <Ionicons name="document-text-outline" size={36} color="#D1D5DB" />
            <Text fontSize="$3" color="#9CA3AF" mt="$2">No lessons available yet</Text>
          </View>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  loadingState: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F8F8FA" },
  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F8F8FA", paddingBottom: 60 },

  heroImage: { width: "100%", height: 200, backgroundColor: "#1F2937" },

  infoCard: { padding: 16 },
  courseTitle: { fontSize: 20, fontWeight: "800", color: "#1A1A2E", lineHeight: 28 },
  lessonCount: { fontSize: 13, color: "#6B7280", marginTop: 4 },

  section: { paddingHorizontal: 16 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E", marginBottom: 12 },

  lessonCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    gap: 12,
  },
  lessonNumber: {
    width: 32,
    height: 32,
    borderRadius: 10,
    backgroundColor: "#F3F4F6",
    justifyContent: "center",
    alignItems: "center",
  },
  lessonNumberText: { fontSize: 13, fontWeight: "700", color: "#6B7280" },
  lessonTitle: { fontSize: 14, fontWeight: "600", color: "#1A1A2E", lineHeight: 20 },
  lessonDesc: { fontSize: 12, color: "#6B7280", marginTop: 2, lineHeight: 16 },
  videoIcon: { marginLeft: 4 },

  emptyLessons: {
    alignItems: "center",
    paddingVertical: 40,
    marginHorizontal: 16,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
});
