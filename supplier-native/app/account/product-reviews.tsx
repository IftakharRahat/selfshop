import React from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  Image,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

export default function ProductReviewsScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id: string }>();
  const productId = Number(params.id);

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-product-reviews", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/reviews/${productId}`);
      return data?.data as {
        product: { ProductName: string; ViewProductImage: string | null };
        avg_rating: number;
        review_count: number;
        reviews: { id: number; messages: string | null; rating: number; status: string; created_at: string; user?: { name: string } | null }[];
      };
    },
  });

  const reviews = data?.reviews ?? [];
  const product = data?.product;

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>Product Reviews</Text>
        <View style={{ width: 32 }} />
      </View>

      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
        >
          {/* Product Summary */}
          {product && (
            <View style={styles.summaryCard}>
              <View style={styles.summaryRow}>
                {product.ViewProductImage && (
                  <Image source={{ uri: getImageUrl(product.ViewProductImage)! }} style={styles.summaryImage} />
                )}
                <View style={{ flex: 1 }}>
                  <Text style={styles.summaryName} numberOfLines={2}>{product.ProductName}</Text>
                  <View style={styles.ratingRow}>
                    {[1, 2, 3, 4, 5].map((s) => (
                      <Ionicons key={s} name={s <= Math.round(data!.avg_rating) ? "star" : "star-outline"} size={16} color="#f59e0b" />
                    ))}
                    <Text style={styles.avgRating}>{data!.avg_rating.toFixed(1)}</Text>
                    <Text style={styles.reviewCount}>({data!.review_count} reviews)</Text>
                  </View>
                </View>
              </View>
            </View>
          )}

          {/* Reviews */}
          {reviews.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="chatbubble-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyText}>No reviews yet</Text>
            </View>
          ) : (
            reviews.map((review) => (
              <View key={review.id} style={styles.reviewCard}>
                <View style={styles.reviewHeader}>
                  <View style={styles.reviewUser}>
                    <View style={styles.avatar}>
                      <Text style={styles.avatarText}>{review.user?.name?.charAt(0)?.toUpperCase() ?? "U"}</Text>
                    </View>
                    <View>
                      <Text style={styles.userName}>{review.user?.name ?? "Customer"}</Text>
                      <Text style={styles.reviewDate}>
                        {new Date(review.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}
                      </Text>
                    </View>
                  </View>
                  <View style={styles.starsRow}>
                    {[1, 2, 3, 4, 5].map((s) => (
                      <Ionicons key={s} name={s <= review.rating ? "star" : "star-outline"} size={12} color="#f59e0b" />
                    ))}
                  </View>
                </View>
                {review.messages && <Text style={styles.reviewMessage}>{review.messages}</Text>}
              </View>
            ))
          )}
          <View style={{ height: 40 }} />
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e", flex: 1, textAlign: "center" },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  scrollContent: { padding: 16 },
  summaryCard: { backgroundColor: "#fff", borderRadius: 14, padding: 14, marginBottom: 12, borderWidth: 1, borderColor: "#f3f4f6" },
  summaryRow: { flexDirection: "row", gap: 12, alignItems: "center" },
  summaryImage: { width: 56, height: 56, borderRadius: 10, resizeMode: "cover" },
  summaryName: { fontSize: 14, fontWeight: "600", color: "#1a1a2e", marginBottom: 4 },
  ratingRow: { flexDirection: "row", alignItems: "center", gap: 4 },
  avgRating: { fontSize: 14, fontWeight: "700", color: "#92400E", marginLeft: 4 },
  reviewCount: { fontSize: 11, color: "#9ca3af" },
  emptyState: { alignItems: "center", paddingVertical: 60, gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  reviewCard: { backgroundColor: "#fff", borderRadius: 12, padding: 14, marginBottom: 8, borderWidth: 1, borderColor: "#f3f4f6" },
  reviewHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 8 },
  reviewUser: { flexDirection: "row", alignItems: "center", gap: 8 },
  avatar: { width: 32, height: 32, borderRadius: 16, backgroundColor: BRAND.primaryLight, alignItems: "center", justifyContent: "center" },
  avatarText: { fontSize: 13, fontWeight: "700", color: BRAND.primary },
  userName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  reviewDate: { fontSize: 10, color: "#9ca3af" },
  starsRow: { flexDirection: "row", gap: 1 },
  reviewMessage: { fontSize: 13, color: "#374151", lineHeight: 19 },
});
