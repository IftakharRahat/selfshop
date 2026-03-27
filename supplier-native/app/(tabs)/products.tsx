import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  Image,
  TextInput,
  TouchableOpacity,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { ProductListSkeleton } from "@/components/skeleton";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";

interface Product {
  id: number;
  ProductName: string;
  ProductSlug: string;
  qty: number;
  ProductResellerPrice: number;
  ProductRegularPrice: number;
  status: string;
  ViewProductImage?: string | null;
  vendor_approval_status?: string | null;
}

function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

const STATUS_BADGE: Record<string, { bg: string; text: string; label: string }> = {
  Active: { bg: "#D1FAE5", text: "#065F46", label: "Active" },
  Inactive: { bg: "#FEE2E2", text: "#991B1B", label: "Inactive" },
  pending: { bg: "#FEF3C7", text: "#92400E", label: "Pending" },
  approved: { bg: "#D1FAE5", text: "#065F46", label: "Approved" },
  rejected: { bg: "#FEE2E2", text: "#991B1B", label: "Rejected" },
};

export default function ProductsScreen() {
  const insets = useSafeAreaInsets();
  const [search, setSearch] = useState("");

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-products", search],
    queryFn: async () => {
      const params: Record<string, string> = {};
      if (search) params.search = search;
      const { data } = await apiClient.get("/vendor/products", { params });
      return data?.data?.products as Product[];
    },
  });

  const products = data ?? [];

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Products</Text>
        </View>
        <ProductListSkeleton />
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Products</Text>
        <Text style={styles.countBadge}>{products.length}</Text>
      </View>

      {/* Search */}
      <View style={styles.searchContainer}>
        <Ionicons name="search-outline" size={18} color="#9ca3af" />
        <TextInput
          style={styles.searchInput}
          placeholder="Search products..."
          placeholderTextColor="#9ca3af"
          value={search}
          onChangeText={setSearch}
          returnKeyType="search"
        />
        {search.length > 0 && (
          <TouchableOpacity onPress={() => setSearch("")}>
            <Ionicons name="close-circle" size={18} color="#9ca3af" />
          </TouchableOpacity>
        )}
      </View>

      {isError ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="alert-circle-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>Failed to load products</Text>
        </View>
      ) : products.length === 0 ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="cube-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>
            {search ? "No products match your search" : "No products yet"}
          </Text>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />
          }
          renderItem={({ item }) => {
            const imgUrl = getImageUrl(item.ViewProductImage);
            const approval = STATUS_BADGE[item.vendor_approval_status ?? ""] ?? null;
            const status = STATUS_BADGE[item.status] ?? { bg: "#F3F4F6", text: "#374151", label: item.status };

            return (
              <TouchableOpacity style={styles.productCard} onPress={() => router.push(`/product/${item.id}`)} activeOpacity={0.7}>
                <View style={styles.productImageWrap}>
                  {imgUrl ? (
                    <Image source={{ uri: imgUrl }} style={styles.productImage} />
                  ) : (
                    <View style={styles.productImagePlaceholder}>
                      <Ionicons name="cube-outline" size={24} color="#d1d5db" />
                    </View>
                  )}
                </View>
                <View style={styles.productInfo}>
                  <Text style={styles.productName} numberOfLines={2}>{item.ProductName}</Text>
                  <View style={styles.priceRow}>
                    <Text style={styles.price}>৳{item.ProductResellerPrice?.toLocaleString()}</Text>
                    {item.ProductRegularPrice > item.ProductResellerPrice && (
                      <Text style={styles.regularPrice}>৳{item.ProductRegularPrice?.toLocaleString()}</Text>
                    )}
                  </View>
                  <View style={styles.badgeRow}>
                    <View style={[styles.badge, { backgroundColor: status.bg }]}>
                      <Text style={[styles.badgeText, { color: status.text }]}>{status.label}</Text>
                    </View>
                    {approval && (
                      <View style={[styles.badge, { backgroundColor: approval.bg }]}>
                        <Text style={[styles.badgeText, { color: approval.text }]}>{approval.label}</Text>
                      </View>
                    )}
                    <Text style={styles.qtyText}>Qty: {item.qty}</Text>
                  </View>
                </View>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={<View style={{ height: 100 }} />}
        />
      )}

      {/* FAB — Create Product */}
      <TouchableOpacity
        style={styles.fab}
        onPress={() => router.push("/product/form")}
        activeOpacity={0.8}
      >
        <Ionicons name="add" size={26} color="#fff" />
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 20,
    paddingVertical: 14,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  headerTitle: { fontSize: 22, fontWeight: "700", color: "#1a1a2e" },
  countBadge: {
    fontSize: 12,
    fontWeight: "600",
    color: BRAND.primary,
    backgroundColor: BRAND.primaryLight,
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 10,
    overflow: "hidden",
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    marginHorizontal: 16,
    marginTop: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#e5e7eb",
    gap: 8,
  },
  searchInput: { flex: 1, fontSize: 14, color: "#1a1a2e" },
  listContent: { padding: 16, gap: 10 },
  productCard: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
    gap: 12,
  },
  productImageWrap: {
    width: 72,
    height: 72,
    borderRadius: 10,
    overflow: "hidden",
    backgroundColor: "#f9fafb",
  },
  productImage: { width: "100%", height: "100%", resizeMode: "cover" },
  productImagePlaceholder: {
    width: "100%",
    height: "100%",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#f3f4f6",
  },
  productInfo: { flex: 1, justifyContent: "center" },
  productName: { fontSize: 14, fontWeight: "600", color: "#1a1a2e", marginBottom: 4 },
  priceRow: { flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 6 },
  price: { fontSize: 14, fontWeight: "700", color: BRAND.primary },
  regularPrice: { fontSize: 12, color: "#9ca3af", textDecorationLine: "line-through" },
  badgeRow: { flexDirection: "row", alignItems: "center", gap: 6 },
  badge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  badgeText: { fontSize: 10, fontWeight: "600" },
  qtyText: { fontSize: 11, color: "#6b7280" },
  emptyContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  fab: {
    position: "absolute",
    bottom: 100,
    right: 20,
    width: 52,
    height: 52,
    borderRadius: 26,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
    elevation: 6,
    shadowColor: BRAND.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
});
