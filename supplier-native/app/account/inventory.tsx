import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  TextInput,
  RefreshControl,
  Image,
  Alert,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND, CARD_SHADOW } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { SubScreenSkeleton } from "@/components/skeleton";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

interface InventorySummary {
  total: number;
  in_stock: number;
  low_stock: number;
  out_of_stock: number;
}

interface InventoryProduct {
  id: number;
  ProductName: string;
  ViewProductImage?: string | null;
  qty: number;
  low_stock?: number;
  status: string;
}

const FILTERS = [
  { key: "all", label: "All" },
  { key: "in_stock", label: "In Stock" },
  { key: "low_stock", label: "Low Stock" },
  { key: "out_of_stock", label: "Out of Stock" },
];

export default function InventoryScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const [filter, setFilter] = useState("all");
  const [search, setSearch] = useState("");

  // Adjust stock form
  const [adjustFor, setAdjustFor] = useState<number | null>(null);
  const [adjustQty, setAdjustQty] = useState("");
  const [adjustType, setAdjustType] = useState<"purchase" | "adjustment">("adjustment");
  const [adjustNote, setAdjustNote] = useState("");

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-inventory", filter, search],
    queryFn: async () => {
      const params: Record<string, any> = { per_page: 50 };
      if (filter !== "all") params.stock_status = filter;
      if (search) params.search = search;
      const { data } = await apiClient.get("/vendor/inventory", { params });
      return data?.data as { summary: InventorySummary; products: InventoryProduct[] };
    },
  });

  const adjustMutation = useMutation({
    mutationFn: async (productId: number) => {
      await apiClient.post(`/vendor/inventory/${productId}/adjust`, {
        quantity: Number(adjustQty),
        type: adjustType,
        note: adjustNote || undefined,
      });
    },
    onSuccess: () => {
      toast.success("Stock adjusted");
      setAdjustFor(null);
      setAdjustQty(""); setAdjustNote("");
      queryClient.invalidateQueries({ queryKey: ["vendor-inventory"] });
    },
    onError: () => toast.error("Failed to adjust stock"),
  });

  const summary = data?.summary;
  const products = data?.products ?? [];

  const SUMMARY_CARDS = [
    { label: "Total", value: summary?.total, color: "#6366f1", icon: "cube" },
    { label: "In Stock", value: summary?.in_stock, color: "#10b981", icon: "checkmark-circle" },
    { label: "Low Stock", value: summary?.low_stock, color: "#f59e0b", icon: "warning" },
    { label: "Out", value: summary?.out_of_stock, color: "#ef4444", icon: "close-circle" },
  ];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Inventory</Text>
        <View style={{ width: 32 }} />
      </View>

      {isLoading ? (
        <SubScreenSkeleton />
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item) => item.id.toString()}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
          ListHeaderComponent={
            <View>
              {/* Summary */}
              <View style={styles.summaryGrid}>
                {SUMMARY_CARDS.map((c) => (
                  <View key={c.label} style={styles.summaryCard}>
                    <Ionicons name={c.icon as any} size={16} color={c.color} />
                    <Text style={styles.summaryValue}>{c.value ?? 0}</Text>
                    <Text style={styles.summaryLabel}>{c.label}</Text>
                  </View>
                ))}
              </View>

              {/* Search */}
              <View style={styles.searchRow}>
                <Ionicons name="search-outline" size={16} color="#9ca3af" />
                <TextInput
                  style={styles.searchInput}
                  value={search}
                  onChangeText={setSearch}
                  placeholder="Search products..."
                  placeholderTextColor="#9ca3af"
                />
                {search.length > 0 && (
                  <TouchableOpacity onPress={() => setSearch("")}>
                    <Ionicons name="close-circle" size={16} color="#9ca3af" />
                  </TouchableOpacity>
                )}
              </View>

              {/* Filter tabs */}
              <View style={styles.filterRow}>
                {FILTERS.map((f) => (
                  <TouchableOpacity
                    key={f.key}
                    style={[styles.filterChip, filter === f.key && styles.filterChipActive]}
                    onPress={() => setFilter(f.key)}
                  >
                    <Text style={[styles.filterText, filter === f.key && styles.filterTextActive]}>{f.label}</Text>
                  </TouchableOpacity>
                ))}
              </View>
            </View>
          }
          contentContainerStyle={styles.listContent}
          renderItem={({ item }) => {
            const imgUrl = getImageUrl(item.ViewProductImage);
            const isLow = item.low_stock && item.qty > 0 && item.qty <= item.low_stock;
            const isOut = item.qty === 0;

            return (
              <View style={styles.productCard}>
                <View style={styles.productRow}>
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
                    <Text style={styles.productName} numberOfLines={1}>{item.ProductName}</Text>
                    <View style={styles.stockRow}>
                      <View style={[styles.stockBadge, { backgroundColor: isOut ? "#FEE2E2" : isLow ? "#FEF3C7" : "#D1FAE5" }]}>
                        <Text style={[styles.stockText, { color: isOut ? "#DC2626" : isLow ? "#92400e" : "#059669" }]}>
                          {isOut ? "Out of Stock" : isLow ? "Low Stock" : "In Stock"}: {item.qty}
                        </Text>
                      </View>
                      {item.low_stock !== undefined && (
                        <Text style={styles.thresholdText}>Threshold: {item.low_stock}</Text>
                      )}
                    </View>
                  </View>
                  <TouchableOpacity onPress={() => setAdjustFor(adjustFor === item.id ? null : item.id)} style={styles.adjustToggle}>
                    <Ionicons name={adjustFor === item.id ? "close" : "add"} size={18} color={BRAND.primary} />
                  </TouchableOpacity>
                </View>

                {adjustFor === item.id && (
                  <View style={styles.adjustForm}>
                    <View style={styles.row}>
                      <TouchableOpacity
                        style={[styles.typeChip, adjustType === "purchase" && styles.typeChipActive]}
                        onPress={() => setAdjustType("purchase")}
                      >
                        <Text style={[styles.typeText, adjustType === "purchase" && styles.typeTextActive]}>+ Purchase</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.typeChip, adjustType === "adjustment" && styles.typeChipActive]}
                        onPress={() => setAdjustType("adjustment")}
                      >
                        <Text style={[styles.typeText, adjustType === "adjustment" && styles.typeTextActive]}>± Adjust</Text>
                      </TouchableOpacity>
                    </View>
                    <View style={styles.row}>
                      <TextInput style={[styles.input, { flex: 1 }]} value={adjustQty} onChangeText={setAdjustQty} placeholder="Qty" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                      <TextInput style={[styles.input, { flex: 2 }]} value={adjustNote} onChangeText={setAdjustNote} placeholder="Note (optional)" placeholderTextColor="#9ca3af" />
                    </View>
                    <TouchableOpacity
                      style={[styles.adjustBtn, adjustMutation.isPending && { opacity: 0.6 }]}
                      onPress={() => adjustMutation.mutate(item.id)}
                      disabled={adjustMutation.isPending || !adjustQty}
                    >
                      <Text style={styles.adjustBtnText}>Adjust Stock</Text>
                    </TouchableOpacity>
                  </View>
                )}
              </View>
            );
          }}
          ListEmptyComponent={
            <View style={styles.emptyState}>
              <Ionicons name="layers-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyText}>No products found</Text>
            </View>
          }
          ListFooterComponent={<View style={{ height: 40 }} />}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff",
    borderBottomWidth: 1, borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  listContent: { padding: 16 },
  summaryGrid: { flexDirection: "row", gap: 8, marginBottom: 12 },
  summaryCard: { flex: 1, backgroundColor: "#fff", borderRadius: 10, padding: 10, alignItems: "center", borderWidth: 1, borderColor: "#f3f4f6", gap: 2 },
  summaryValue: { fontSize: 16, fontWeight: "700", color: "#1a1a2e" },
  summaryLabel: { fontSize: 9, color: "#9ca3af", fontWeight: "500" },
  searchRow: {
    flexDirection: "row", alignItems: "center", backgroundColor: "#fff", borderRadius: 10,
    paddingHorizontal: 10, paddingVertical: 8, borderWidth: 1, borderColor: "#e5e7eb", gap: 6, marginBottom: 10,
  },
  searchInput: { flex: 1, fontSize: 13, color: "#1a1a2e" },
  filterRow: { flexDirection: "row", gap: 6, marginBottom: 12 },
  filterChip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb" },
  filterChipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  filterText: { fontSize: 11, fontWeight: "500", color: "#6b7280" },
  filterTextActive: { color: "#fff" },
  productCard: { backgroundColor: "#fff", borderRadius: 12, padding: 12, marginBottom: 8, ...CARD_SHADOW },
  productRow: { flexDirection: "row", alignItems: "center", gap: 10 },
  productImageWrap: { width: 44, height: 44, borderRadius: 8, overflow: "hidden", backgroundColor: "#f9fafb" },
  productImage: { width: "100%", height: "100%", resizeMode: "cover" },
  productImagePlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6" },
  productInfo: { flex: 1 },
  productName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  stockRow: { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 4 },
  stockBadge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  stockText: { fontSize: 10, fontWeight: "600" },
  thresholdText: { fontSize: 9, color: "#9ca3af" },
  adjustToggle: { width: 30, height: 30, borderRadius: 8, backgroundColor: BRAND.primaryLight, alignItems: "center", justifyContent: "center" },
  adjustForm: { marginTop: 10, backgroundColor: "#f9fafb", borderRadius: 8, padding: 10, gap: 8 },
  row: { flexDirection: "row", gap: 8 },
  typeChip: { flex: 1, paddingVertical: 8, borderRadius: 8, backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", alignItems: "center" },
  typeChipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  typeText: { fontSize: 12, fontWeight: "500", color: "#6b7280" },
  typeTextActive: { color: "#fff" },
  input: {
    backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8,
    paddingHorizontal: 10, paddingVertical: 8, fontSize: 13, color: "#1a1a2e",
  },
  adjustBtn: { backgroundColor: BRAND.primary, borderRadius: 8, paddingVertical: 10, alignItems: "center" },
  adjustBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  emptyState: { alignItems: "center", paddingVertical: 40, gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
});
