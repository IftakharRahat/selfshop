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
import { Stack, router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

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

const FILTER_OPTIONS = [
  { value: "best_rated", label: "Best Rated" },
  { value: "top_supplier", label: "Top Supplier" },
  { value: "recent", label: "Recent" },
] as const;

export default function AllSuppliersScreen() {
  const queryClient = useQueryClient();
  const [activeFilter, setActiveFilter] = useState("best_rated");

  const suppliersQuery = useQuery({
    queryKey: ["popular-vendors", activeFilter],
    queryFn: async () => {
      const { data } = await apiClient.get(`/popular-vendors`, {
        params: { sort: activeFilter },
      });
      return data?.data ?? data ?? [];
    },
  });

  const suppliers: any[] = Array.isArray(suppliersQuery.data) ? suppliersQuery.data : [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["popular-vendors", activeFilter] });
  }, [queryClient, activeFilter]);

  const renderSupplier = ({ item }: { item: any }) => {
    const logo = resolveImageUrl(item.shop_logo ?? item.logo);
    const banner = resolveImageUrl(item.shop_banner ?? item.banner);
    const name = item.shop_name ?? item.company_name ?? item.name ?? "Supplier";
    const totalProducts = item.total_products ?? item.products_count ?? 0;
    const rating = item.rating ?? item.avg_rating ?? 0;

    return (
      <Pressable
        style={({ pressed }) => [
          styles.supplierCard,
          pressed && { opacity: 0.88, transform: [{ scale: 0.98 }] },
        ]}
        onPress={() =>
          router.push({
            pathname: "/webview-page",
            params: {
              url: `https://selfshop.com.bd/supplier/${item.slug ?? item.id}`,
              title: name,
            },
          } as any)
        }
      >
        {/* Banner */}
        <View style={styles.supplierBanner}>
          {banner ? (
            <Image source={{ uri: banner }} style={styles.bannerImage} resizeMode="cover" />
          ) : (
            <View style={[styles.bannerImage, { backgroundColor: "#E5E7EB" }]} />
          )}
        </View>

        {/* Logo */}
        <View style={styles.logoWrapper}>
          {logo ? (
            <Image source={{ uri: logo }} style={styles.logo} resizeMode="cover" />
          ) : (
            <View style={[styles.logo, styles.logoPlaceholder]}>
              <Ionicons name="storefront" size={18} color="#9CA3AF" />
            </View>
          )}
        </View>

        {/* Info */}
        <View style={styles.supplierInfo}>
          <Text style={styles.supplierName} numberOfLines={1}>{name}</Text>
          <View style={styles.supplierMeta}>
            <View style={styles.metaItem}>
              <Ionicons name="cube-outline" size={12} color="#6B7280" />
              <Text style={styles.metaText}>{totalProducts} products</Text>
            </View>
            {rating > 0 && (
              <View style={styles.metaItem}>
                <Ionicons name="star" size={12} color="#F59E0B" />
                <Text style={styles.metaText}>{Number(rating).toFixed(1)}</Text>
              </View>
            )}
          </View>
        </View>
      </Pressable>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "All Suppliers",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        {/* Filter Tabs */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.filterRow}
        >
          {FILTER_OPTIONS.map((opt) => {
            const isActive = opt.value === activeFilter;
            return (
              <Pressable
                key={opt.value}
                style={[styles.filterChip, isActive && styles.filterChipActive]}
                onPress={() => setActiveFilter(opt.value)}
              >
                <Text style={[styles.filterChipText, isActive && { color: "#fff" }]}>
                  {opt.label}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>

        <FlatList
          data={suppliers}
          renderItem={renderSupplier}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.gridRow}
          contentContainerStyle={[
            styles.gridContent,
            suppliers.length === 0 && { flex: 1 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={suppliersQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          ListEmptyComponent={
            suppliersQuery.isLoading ? (
              <View style={styles.emptyState}>
                <ActivityIndicator size="large" color={ACCENT} />
              </View>
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="storefront-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">
                  No suppliers found
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

  filterRow: { padding: 16, paddingBottom: 8, gap: 8 },
  filterChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    backgroundColor: "#fff",
  },
  filterChipActive: {
    borderColor: ACCENT,
    backgroundColor: ACCENT,
  },
  filterChipText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
  },

  gridContent: { padding: 16, paddingBottom: 40 },
  gridRow: { justifyContent: "space-between", marginBottom: 12 },
  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", paddingBottom: 60 },

  supplierCard: {
    width: CARD_WIDTH,
    backgroundColor: "#fff",
    borderRadius: 16,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  supplierBanner: { height: 64, backgroundColor: "#F3F4F6" },
  bannerImage: { width: "100%", height: "100%" },
  logoWrapper: {
    marginTop: -20,
    marginLeft: 12,
  },
  logo: {
    width: 40,
    height: 40,
    borderRadius: 12,
    borderWidth: 2,
    borderColor: "#fff",
    backgroundColor: "#F9FAFB",
  },
  logoPlaceholder: {
    justifyContent: "center",
    alignItems: "center",
  },
  supplierInfo: { padding: 10, paddingTop: 6 },
  supplierName: { fontSize: 13, fontWeight: "700", color: "#1A1A2E" },
  supplierMeta: { flexDirection: "row", gap: 10, marginTop: 4 },
  metaItem: { flexDirection: "row", alignItems: "center", gap: 3 },
  metaText: { fontSize: 11, color: "#6B7280" },
});
