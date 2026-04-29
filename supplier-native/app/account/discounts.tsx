import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  RefreshControl,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

interface CategoryDiscount {
  id: number;
  category_name: string;
  category_icon: string | null;
  slug: string;
  discount_percent: number;
  start_date: string | null;
  end_date: string | null;
}

interface CategoryCommission {
  category_id: number;
  category_name: string;
  category_slug: string;
  commission_percent: number;
}

export default function DiscountsScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const [tab, setTab] = useState<"discounts" | "commissions">("discounts");
  const [editingId, setEditingId] = useState<number | null>(null);
  const [discountValue, setDiscountValue] = useState("");

  const { data: discounts, isLoading: discountsLoading, refetch: refetchDiscounts, isRefetching } = useQuery({
    queryKey: ["vendor-category-discounts"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/category-discounts");
      return data?.data?.categories as CategoryDiscount[];
    },
  });

  const { data: commissions, isLoading: commissionsLoading } = useQuery({
    queryKey: ["vendor-category-commissions"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/category-commissions");
      return data?.data as { global_default_commission_percent: number; categories: CategoryCommission[] };
    },
  });

  const setDiscountMutation = useMutation({
    mutationFn: async (categoryId: number) => {
      await apiClient.post(`/vendor/category-discounts/${categoryId}`, {
        discount_percent: Number(discountValue),
      });
    },
    onSuccess: () => {
      toast.success("Discount updated");
      setEditingId(null);
      setDiscountValue("");
      queryClient.invalidateQueries({ queryKey: ["vendor-category-discounts"] });
    },
    onError: () => toast.error("Failed to update discount"),
  });

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Discounts & Commissions</Text>
        <View style={{ width: 32 }} />
      </View>

      {/* Tab Switch */}
      <View style={styles.tabRow}>
        <TouchableOpacity style={[styles.tabBtn, tab === "discounts" && styles.tabBtnActive]} onPress={() => setTab("discounts")}>
          <Text style={[styles.tabText, tab === "discounts" && styles.tabTextActive]}>Discounts</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.tabBtn, tab === "commissions" && styles.tabBtnActive]} onPress={() => setTab("commissions")}>
          <Text style={[styles.tabText, tab === "commissions" && styles.tabTextActive]}>Commissions</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetchDiscounts} tintColor={BRAND.primary} />}
      >
        {tab === "discounts" ? (
          discountsLoading ? (
            <ActivityIndicator size="large" color={BRAND.primary} style={{ marginTop: 40 }} />
          ) : (discounts ?? []).length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="pricetag-outline" size={48} color="#d1d5db" />
              <Text style={styles.emptyText}>No category discounts</Text>
            </View>
          ) : (
            (discounts ?? []).map((cat) => (
              <View key={cat.id} style={styles.categoryCard}>
                <View style={styles.categoryHeader}>
                  <Ionicons name="folder-outline" size={16} color={BRAND.primary} />
                  <Text style={styles.categoryName}>{cat.category_name}</Text>
                  <View style={[styles.discountBadge, { backgroundColor: cat.discount_percent > 0 ? "#D1FAE5" : "#f3f4f6" }]}>
                    <Text style={[styles.discountText, { color: cat.discount_percent > 0 ? "#059669" : "#9ca3af" }]}>
                      {cat.discount_percent}%
                    </Text>
                  </View>
                  <TouchableOpacity onPress={() => {
                    if (editingId === cat.id) { setEditingId(null); } else {
                      setEditingId(cat.id);
                      setDiscountValue(String(cat.discount_percent));
                    }
                  }}>
                    <Ionicons name="create-outline" size={16} color={BRAND.primary} />
                  </TouchableOpacity>
                </View>
                {editingId === cat.id && (
                  <View style={styles.editRow}>
                    <TextInput
                      style={[styles.input, { flex: 1 }]}
                      value={discountValue}
                      onChangeText={setDiscountValue}
                      placeholder="0"
                      keyboardType="numeric"
                      placeholderTextColor="#9ca3af"
                    />
                    <Text style={styles.percentSign}>%</Text>
                    <TouchableOpacity style={styles.saveChip} onPress={() => setDiscountMutation.mutate(cat.id)}>
                      <Text style={styles.saveChipText}>Save</Text>
                    </TouchableOpacity>
                  </View>
                )}
              </View>
            ))
          )
        ) : (
          commissionsLoading ? (
            <ActivityIndicator size="large" color={BRAND.primary} style={{ marginTop: 40 }} />
          ) : (
            <>
              <View style={styles.globalCard}>
                <Ionicons name="information-circle" size={16} color="#3b82f6" />
                <Text style={styles.globalText}>
                  Default commission: <Text style={{ fontWeight: "700" }}>{commissions?.global_default_commission_percent ?? 0}%</Text>
                </Text>
              </View>
              {(commissions?.categories ?? []).length === 0 ? (
                <View style={styles.emptyState}>
                  <Ionicons name="git-branch-outline" size={48} color="#d1d5db" />
                  <Text style={styles.emptyText}>No category-specific commissions</Text>
                  <Text style={{ fontSize: 11, color: "#d1d5db" }}>The global default rate applies</Text>
                </View>
              ) : (
                (commissions?.categories ?? []).map((cat) => (
                  <View key={cat.category_id} style={styles.categoryCard}>
                    <View style={styles.categoryHeader}>
                      <Ionicons name="folder-outline" size={16} color="#6b7280" />
                      <Text style={styles.categoryName}>{cat.category_name}</Text>
                      <View style={styles.discountBadge}>
                        <Text style={[styles.discountText, { color: "#6b7280" }]}>{cat.commission_percent}%</Text>
                      </View>
                    </View>
                  </View>
                ))
              )}
            </>
          )
        )}
        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 16, fontWeight: "600", color: "#1a1a2e" },
  tabRow: { flexDirection: "row", gap: 6, paddingHorizontal: 16, paddingVertical: 10, backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  tabBtn: { flex: 1, paddingVertical: 8, borderRadius: 10, backgroundColor: "#f3f4f6", alignItems: "center" },
  tabBtnActive: { backgroundColor: BRAND.primary },
  tabText: { fontSize: 13, fontWeight: "500", color: "#6b7280" },
  tabTextActive: { color: "#fff" },
  scrollContent: { padding: 16 },
  emptyState: { alignItems: "center", paddingVertical: 60, gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  globalCard: { flexDirection: "row", alignItems: "center", gap: 8, backgroundColor: "#EFF6FF", borderRadius: 10, padding: 12, marginBottom: 12 },
  globalText: { fontSize: 13, color: "#1E40AF" },
  categoryCard: { backgroundColor: "#fff", borderRadius: 10, padding: 12, marginBottom: 6, borderWidth: 1, borderColor: "#f3f4f6" },
  categoryHeader: { flexDirection: "row", alignItems: "center", gap: 8 },
  categoryName: { flex: 1, fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  discountBadge: { backgroundColor: "#f3f4f6", paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
  discountText: { fontSize: 11, fontWeight: "600" },
  editRow: { flexDirection: "row", alignItems: "center", gap: 8, marginTop: 8 },
  input: { backgroundColor: "#f9fafb", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8, paddingHorizontal: 10, paddingVertical: 8, fontSize: 13, color: "#1a1a2e" },
  percentSign: { fontSize: 14, fontWeight: "600", color: "#6b7280" },
  saveChip: { backgroundColor: BRAND.primary, paddingHorizontal: 14, paddingVertical: 8, borderRadius: 8 },
  saveChipText: { fontSize: 12, fontWeight: "600", color: "#fff" },
});
