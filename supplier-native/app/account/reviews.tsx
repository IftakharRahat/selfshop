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
import { router } from "expo-router";
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

interface ReviewProduct {
  id: number;
  ProductName: string;
  ViewProductImage: string | null;
  avg_rating: number;
  review_count: number;
  new_count: number;
}

interface Review {
  id: number;
  messages: string | null;
  rating: number;
  status: string;
  created_at: string;
  user?: { name: string; profile?: string | null } | null;
}

export default function ReviewsScreen() {
  const insets = useSafeAreaInsets();

  const { data: products, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-review-products"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/reviews");
      return data?.data?.products as ReviewProduct[];
    },
  });

  const reviewProducts = products ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Reviews</Text>
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
          {reviewProducts.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="star-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyText}>No reviews yet</Text>
            </View>
          ) : (
            reviewProducts.map((product) => {
              const imgUrl = getImageUrl(product.ViewProductImage);
              return (
                <TouchableOpacity
                  key={product.id}
                  style={styles.productCard}
                  onPress={() => router.push(`/account/product-reviews?id=${product.id}`)}
                  activeOpacity={0.7}
                >
                  <View style={styles.productImageWrap}>
                    {imgUrl ? (
                      <Image source={{ uri: imgUrl }} style={styles.productImage} />
                    ) : (
                      <View style={styles.productImagePlaceholder}>
                        <Ionicons name="cube-outline" size={16} color="#d1d5db" />
                      </View>
                    )}
                  </View>
                  <View style={styles.productInfo}>
                    <Text style={styles.productName} numberOfLines={2}>{product.ProductName}</Text>
                    <View style={styles.ratingRow}>
                      <View style={styles.starsRow}>
                        {[1, 2, 3, 4, 5].map((s) => (
                          <Ionicons
                            key={s}
                            name={s <= Math.round(product.avg_rating) ? "star" : "star-outline"}
                            size={12}
                            color="#f59e0b"
                          />
                        ))}
                      </View>
                      <Text style={styles.ratingText}>{product.avg_rating.toFixed(1)}</Text>
                      <Text style={styles.reviewCount}>({product.review_count})</Text>
                    </View>
                  </View>
                  {product.new_count > 0 && (
                    <View style={styles.newBadge}>
                      <Text style={styles.newBadgeText}>{product.new_count} new</Text>
                    </View>
                  )}
                  <Ionicons name="chevron-forward" size={16} color="#d1d5db" />
                </TouchableOpacity>
              );
            })
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
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  scrollContent: { padding: 16 },
  emptyState: { alignItems: "center", paddingVertical: 60, gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  productCard: { flexDirection: "row", alignItems: "center", backgroundColor: "#fff", borderRadius: 12, padding: 12, marginBottom: 8, borderWidth: 1, borderColor: "#f3f4f6", gap: 10 },
  productImageWrap: { width: 48, height: 48, borderRadius: 8, overflow: "hidden", backgroundColor: "#f9fafb" },
  productImage: { width: "100%", height: "100%", resizeMode: "cover" },
  productImagePlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6" },
  productInfo: { flex: 1 },
  productName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e", marginBottom: 4 },
  ratingRow: { flexDirection: "row", alignItems: "center", gap: 4 },
  starsRow: { flexDirection: "row", gap: 1 },
  ratingText: { fontSize: 12, fontWeight: "600", color: "#92400E" },
  reviewCount: { fontSize: 11, color: "#9ca3af" },
  newBadge: { backgroundColor: "#FEF3C7", paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  newBadgeText: { fontSize: 9, fontWeight: "600", color: "#92400E" },
});
