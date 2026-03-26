import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  RefreshControl,
  ActivityIndicator,
  ScrollView,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const CARD_WIDTH = (width - 48) / 2;

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

export default function SupplierDetailScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const queryClient = useQueryClient();
  const [selectedCategory, setSelectedCategory] = useState<number | undefined>(undefined);

  const supplierQuery = useQuery({
    queryKey: ["supplier-detail", slug, selectedCategory],
    queryFn: async () => {
      const params: any = {};
      if (selectedCategory) params.category = selectedCategory;
      const { data } = await apiClient.get(`/supplier/${slug}`, { params });
      return data?.data ?? data;
    },
    enabled: !!slug,
  });

  const followStatusQuery = useQuery({
    queryKey: ["vendor-follow-status", supplierQuery.data?.vendor?.id],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor-follow/${supplierQuery.data.vendor.id}/status`);
      return data?.data ?? data;
    },
    enabled: !!supplierQuery.data?.vendor?.id,
  });

  const followMut = useMutation({
    mutationFn: async () => {
      const vendorId = supplierQuery.data?.vendor?.id;
      const isFollowed = followStatusQuery.data?.is_following;
      const url = `/vendor-follow/${vendorId}/${isFollowed ? "unfollow" : "follow"}`;
      const { data } = await apiClient.post(url);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["vendor-follow-status"] });
      toast.success(followStatusQuery.data?.is_following ? "Unfollowed" : "Following!");
    },
    onError: () => toast.error("Could not update follow status"),
  });

  const vendor = supplierQuery.data?.vendor;
  const categories: any[] = supplierQuery.data?.categories ?? [];
  const products: any[] = supplierQuery.data?.products?.data ?? [];
  const totalProducts = supplierQuery.data?.products?.total ?? 0;

  const isFollowed = followStatusQuery.data?.is_following ?? false;
  const followersCount = followStatusQuery.data?.followers_count ?? vendor?.followers_count ?? 0;

  const logoUri = resolveImageUrl(vendor?.logo_path);
  const bannerUri = resolveImageUrl(vendor?.banner_path);
  const companyName = vendor?.company_name ?? "Supplier";
  const initials = companyName.split(" ").map((w: string) => w[0]).join("").slice(0, 2).toUpperCase();

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["supplier-detail", slug] });
  }, [queryClient, slug]);

  const renderProduct = ({ item }: { item: any }) => (
    <View style={{ width: CARD_WIDTH }}>
      <ProductCard
        name={item.ProductName}
        price={String(item.storefront_price ?? item.ProductSalePrice ?? item.ProductRegularPrice)}
        image={item.ViewProductImage}
        slug={item.ProductSlug}
        variant="grid"
      />
    </View>
  );

  if (supplierQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Supplier", headerShadowVisible: false }} />
        <View style={styles.loadingState}><ActivityIndicator size="large" color={ACCENT} /></View>
      </>
    );
  }

  if (!vendor) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Supplier", headerShadowVisible: false }} />
        <View style={styles.emptyState}>
          <Ionicons name="storefront-outline" size={48} color="#D1D5DB" />
          <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">Supplier not found</Text>
          <Pressable onPress={() => router.back()} style={{ marginTop: 16 }}>
            <Text fontSize="$3" fontWeight="600" color={ACCENT}>← Go Back</Text>
          </Pressable>
        </View>
      </>
    );
  }

  return (
    <>
      <Stack.Screen options={{ headerShown: true, title: companyName, headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
      <View style={styles.container}>
        <FlatList
          data={products}
          renderItem={renderProduct}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.gridRow}
          contentContainerStyle={styles.gridContent}
          refreshControl={
            <RefreshControl refreshing={supplierQuery.isRefetching} onRefresh={onRefresh} tintColor={ACCENT} />
          }
          ListHeaderComponent={
            <>
              {/* Banner */}
              <View style={styles.banner}>
                {bannerUri ? (
                  <Image source={{ uri: bannerUri }} style={styles.bannerImage} resizeMode="cover" />
                ) : (
                  <View style={[styles.bannerImage, { backgroundColor: "#059669" }]} />
                )}
              </View>

              {/* Vendor Info */}
              <View style={styles.vendorInfo}>
                <View style={styles.logoRow}>
                  {logoUri ? (
                    <Image source={{ uri: logoUri }} style={styles.logo} resizeMode="cover" />
                  ) : (
                    <View style={[styles.logo, styles.logoPlaceholder]}>
                      <Text style={{ fontSize: 18, fontWeight: "800", color: "#fff" }}>{initials}</Text>
                    </View>
                  )}
                  <View style={{ flex: 1 }}>
                    <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
                      <Text style={styles.vendorName} numberOfLines={1}>{companyName}</Text>
                      {vendor.is_verified_badge && <Ionicons name="checkmark-circle" size={16} color="#2563EB" />}
                    </View>
                    <View style={styles.metaRow}>
                      {vendor.city && (
                        <>
                          <View style={styles.metaItem}>
                            <Ionicons name="location-outline" size={12} color="#6B7280" />
                            <Text style={styles.metaText}>{vendor.city}</Text>
                          </View>
                          <Text style={{ color: "#D1D5DB" }}>|</Text>
                        </>
                      )}
                      <View style={styles.metaItem}>
                        <Ionicons name="cube-outline" size={12} color="#6B7280" />
                        <Text style={styles.metaText}>{vendor.products_count ?? 0} Products</Text>
                      </View>
                    </View>
                    {vendor.avg_product_rating > 0 && (
                      <View style={[styles.metaRow, { marginTop: 4 }]}>
                        <Ionicons name="star" size={12} color="#F59E0B" />
                        <Text style={{ fontSize: 12, fontWeight: "600", color: "#374151" }}>{vendor.avg_product_rating}</Text>
                        <Text style={{ fontSize: 11, color: "#9CA3AF" }}>({vendor.review_count ?? 0} reviews)</Text>
                      </View>
                    )}
                  </View>
                </View>

                {/* Follow Row */}
                <View style={styles.followRow}>
                  <View style={{ alignItems: "center" }}>
                    <Text style={{ fontSize: 16, fontWeight: "800", color: "#1A1A2E" }}>{followersCount}</Text>
                    <Text style={{ fontSize: 10, color: "#9CA3AF" }}>Followers</Text>
                  </View>
                  <Pressable
                    style={[styles.followButton, isFollowed && styles.followButtonActive]}
                    onPress={() => followMut.mutate()}
                    disabled={followMut.isPending}
                  >
                    {followMut.isPending ? (
                      <ActivityIndicator size="small" color={isFollowed ? ACCENT : "#fff"} />
                    ) : (
                      <>
                        <Ionicons name={isFollowed ? "checkmark-outline" : "person-add-outline"} size={14} color={isFollowed ? ACCENT : "#fff"} />
                        <Text style={{ fontSize: 12, fontWeight: "700", color: isFollowed ? ACCENT : "#fff" }}>
                          {isFollowed ? "Following" : "Follow"}
                        </Text>
                      </>
                    )}
                  </Pressable>
                </View>
              </View>

              {/* Category Filter */}
              {categories.length > 0 && (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.categoryRow}>
                  <Pressable
                    style={[styles.categoryChip, !selectedCategory && styles.categoryChipActive]}
                    onPress={() => setSelectedCategory(undefined)}
                  >
                    <Text style={[styles.categoryChipText, !selectedCategory && { color: "#fff" }]}>All</Text>
                  </Pressable>
                  {categories.map((cat: any) => {
                    const isActive = selectedCategory === cat.id;
                    return (
                      <Pressable
                        key={cat.id}
                        style={[styles.categoryChip, isActive && styles.categoryChipActive]}
                        onPress={() => setSelectedCategory(cat.id)}
                      >
                        <Text style={[styles.categoryChipText, isActive && { color: "#fff" }]}>{cat.category_name}</Text>
                      </Pressable>
                    );
                  })}
                </ScrollView>
              )}

              {/* Products Header */}
              <View style={styles.productsHeader}>
                <Text style={styles.productsTitle}>
                  {selectedCategory ? categories.find((c: any) => c.id === selectedCategory)?.category_name ?? "Products" : "All Products"}
                </Text>
                <Text style={styles.productsCount}>({totalProducts})</Text>
              </View>
            </>
          }
          ListEmptyComponent={
            <View style={styles.emptyProducts}>
              <Ionicons name="cube-outline" size={40} color="#D1D5DB" />
              <Text fontSize="$3" color="#9CA3AF" mt="$2">No products available</Text>
            </View>
          }
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  loadingState: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F8F8FA" },
  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F8F8FA", paddingBottom: 60 },

  banner: { height: 140, backgroundColor: "#1F2937" },
  bannerImage: { width: "100%", height: "100%" },

  vendorInfo: { backgroundColor: "#fff", marginHorizontal: 16, marginTop: -20, borderRadius: 16, padding: 14, borderWidth: 1, borderColor: "#F0F0F5" },
  logoRow: { flexDirection: "row", alignItems: "flex-start", gap: 12 },
  logo: { width: 56, height: 56, borderRadius: 16, borderWidth: 2, borderColor: "#fff", backgroundColor: "#F9FAFB" },
  logoPlaceholder: { backgroundColor: "#059669", justifyContent: "center", alignItems: "center" },
  vendorName: { fontSize: 16, fontWeight: "800", color: "#1A1A2E", flex: 1 },
  metaRow: { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 2 },
  metaItem: { flexDirection: "row", alignItems: "center", gap: 3 },
  metaText: { fontSize: 11, color: "#6B7280" },
  followRow: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: "#F0F0F5" },
  followButton: { flexDirection: "row", alignItems: "center", gap: 6, backgroundColor: ACCENT, paddingHorizontal: 20, paddingVertical: 10, borderRadius: 24 },
  followButtonActive: { backgroundColor: "#FDF2F8", borderWidth: 1.5, borderColor: ACCENT },

  categoryRow: { paddingHorizontal: 16, paddingVertical: 12, gap: 8 },
  categoryChip: { paddingHorizontal: 14, paddingVertical: 7, borderRadius: 10, borderWidth: 1.5, borderColor: "#E5E7EB", backgroundColor: "#fff" },
  categoryChipActive: { borderColor: ACCENT, backgroundColor: ACCENT },
  categoryChipText: { fontSize: 12, fontWeight: "600", color: "#374151" },

  productsHeader: { flexDirection: "row", alignItems: "center", gap: 6, paddingHorizontal: 16, paddingBottom: 8 },
  productsTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E" },
  productsCount: { fontSize: 13, color: "#9CA3AF" },

  gridContent: { paddingBottom: 40 },
  gridRow: { paddingHorizontal: 16, justifyContent: "space-between", marginBottom: 12 },
  emptyProducts: { alignItems: "center", paddingVertical: 40 },
});
