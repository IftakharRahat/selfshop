import React from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { DashboardSkeleton } from "@/components/skeleton";

interface DashboardData {
  product_count: number;
  total_orders: number;
  total_sales: number;
  this_month_sales: number;
  last_month_sales: number;
  pending_amount: number;
  avg_rating: number;
  total_followers: number;
  orders_this_month_by_status: Record<string, number>;
}

export default function DashboardScreen() {
  const insets = useSafeAreaInsets();

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-dashboard"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/dashboard");
      return data?.data as DashboardData;
    },
  });

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Dashboard</Text>
        </View>
        <DashboardSkeleton />
      </View>
    );
  }

  if (isError) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Dashboard</Text>
        </View>
        <View style={styles.errorContainer}>
          <Ionicons name="alert-circle-outline" size={48} color="#d1d5db" />
          <Text style={styles.errorText}>Failed to load dashboard</Text>
          <Text style={styles.errorSubtext}>Please log in to access your supplier dashboard</Text>
        </View>
      </View>
    );
  }

  const statCards = [
    { label: "Products", value: data?.product_count ?? 0, icon: "cube" as const, color: "#4f46e5" },
    { label: "Total Orders", value: data?.total_orders ?? 0, icon: "clipboard" as const, color: "#059669" },
    { label: "Total Sales", value: `৳${(data?.total_sales ?? 0).toLocaleString()}`, icon: "trending-up" as const, color: "#d97706" },
    { label: "This Month", value: `৳${(data?.this_month_sales ?? 0).toLocaleString()}`, icon: "calendar" as const, color: "#0891b2" },
  ];

  const orderStatuses = [
    { label: "Pending", keys: ["Pending", "New"], icon: "time-outline" as const, color: "#f59e0b" },
    { label: "Confirmed", keys: ["Confirmed"], icon: "checkmark-circle-outline" as const, color: "#10b981" },
    { label: "On Delivery", keys: ["Ontheway", "OnDelivery"], icon: "bicycle-outline" as const, color: "#3b82f6" },
    { label: "Delivered", keys: ["Delivered", "Complete"], icon: "checkmark-done-outline" as const, color: "#059669" },
    { label: "Cancelled", keys: ["Canceled", "Cancelled"], icon: "close-circle-outline" as const, color: "#ef4444" },
  ];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Dashboard</Text>
        <View style={styles.ratingBadge}>
          <Ionicons name="star" size={14} color="#f59e0b" />
          <Text style={styles.ratingText}>{data?.avg_rating ?? "—"}</Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />
        }
        showsVerticalScrollIndicator={false}
      >
        {/* ── Stat Cards ── */}
        <View style={styles.statsGrid}>
          {statCards.map((card) => (
            <View key={card.label} style={styles.statCard}>
              <View style={[styles.statIconWrap, { backgroundColor: card.color + "15" }]}>
                <Ionicons name={card.icon} size={20} color={card.color} />
              </View>
              <Text style={styles.statValue}>{card.value}</Text>
              <Text style={styles.statLabel}>{card.label}</Text>
            </View>
          ))}
        </View>

        {/* ── Followers ── */}
        <View style={styles.followersCard}>
          <Ionicons name="people-outline" size={20} color={BRAND.primary} />
          <Text style={styles.followersText}>
            <Text style={styles.followersCount}>{data?.total_followers ?? 0}</Text> Followers
          </Text>
        </View>

        {/* ── Orders This Month ── */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Orders This Month</Text>
          {orderStatuses.map((status) => {
            const count = status.keys.reduce(
              (sum, key) => sum + (data?.orders_this_month_by_status?.[key] ?? 0),
              0,
            );
            return (
              <View key={status.label} style={styles.orderRow}>
                <View style={styles.orderRowLeft}>
                  <Ionicons name={status.icon} size={18} color={status.color} />
                  <Text style={styles.orderLabel}>{status.label}</Text>
                </View>
                <Text style={styles.orderCount}>{count}</Text>
              </View>
            );
          })}
        </View>

        {/* ── Pending Amount ── */}
        <View style={[styles.sectionCard, { backgroundColor: "#FFF7ED", borderColor: "#FED7AA" }]}>
          <Text style={[styles.sectionTitle, { color: "#92400e" }]}>Pending Amount</Text>
          <Text style={styles.pendingAmount}>৳{(data?.pending_amount ?? 0).toLocaleString()}</Text>
          <Text style={{ fontSize: 12, color: "#b45309", marginTop: 2 }}>Awaiting delivery confirmation</Text>
        </View>

        {/* Bottom spacer for tab bar */}
        <View style={{ height: 100 }} />
      </ScrollView>
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
  ratingBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#FFFBEB",
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  ratingText: { fontSize: 13, fontWeight: "600", color: "#92400e" },
  scrollContent: { padding: 16 },
  statsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
  },
  statCard: {
    width: "48%",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  statIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 10,
  },
  statValue: { fontSize: 20, fontWeight: "700", color: "#1a1a2e" },
  statLabel: { fontSize: 12, color: "#6b7280", marginTop: 2 },
  followersCard: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    backgroundColor: BRAND.primaryBg,
    borderRadius: 12,
    padding: 14,
    marginTop: 12,
  },
  followersText: { fontSize: 14, color: "#6b7280" },
  followersCount: { fontSize: 16, fontWeight: "700", color: BRAND.primary },
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginTop: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 12 },
  orderRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#f9fafb",
  },
  orderRowLeft: { flexDirection: "row", alignItems: "center", gap: 10 },
  orderLabel: { fontSize: 14, color: "#374151" },
  orderCount: { fontSize: 15, fontWeight: "600", color: "#1a1a2e" },
  pendingAmount: { fontSize: 28, fontWeight: "700", color: "#ea580c" },
  errorContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  errorText: { fontSize: 16, fontWeight: "600", color: "#6b7280" },
  errorSubtext: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingHorizontal: 40 },
});
