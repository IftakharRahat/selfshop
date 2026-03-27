import React from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  Image,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { DashboardSkeleton } from "@/components/skeleton";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

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
  sales_chart: { month: string; total: number }[];
  top_products: {
    id: number;
    name: string;
    image: string | null;
    price: number;
    total_sales: number;
    total_quantity: number;
    avg_rating: number;
  }[];
  category_wise_product_count: { category_name: string; product_count: number }[];
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

  // Sales chart data
  const chartData = data?.sales_chart ?? [];
  const maxSale = Math.max(...chartData.map((d) => d.total), 1);

  // Top products
  const topProducts = data?.top_products ?? [];

  // Last month comparison
  const thisMonth = data?.this_month_sales ?? 0;
  const lastMonth = data?.last_month_sales ?? 0;
  const growthPct = lastMonth > 0 ? Math.round(((thisMonth - lastMonth) / lastMonth) * 100) : thisMonth > 0 ? 100 : 0;

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

        {/* ── Sales Chart ── */}
        {chartData.length > 0 && (
          <View style={styles.sectionCard}>
            <View style={styles.chartHeader}>
              <Text style={styles.sectionTitle}>Monthly Sales</Text>
              <View style={[styles.growthBadge, { backgroundColor: growthPct >= 0 ? "#D1FAE5" : "#FEE2E2" }]}>
                <Ionicons
                  name={growthPct >= 0 ? "trending-up" : "trending-down"}
                  size={12}
                  color={growthPct >= 0 ? "#059669" : "#DC2626"}
                />
                <Text style={[styles.growthText, { color: growthPct >= 0 ? "#059669" : "#DC2626" }]}>
                  {growthPct > 0 ? "+" : ""}{growthPct}%
                </Text>
              </View>
            </View>
            <View style={styles.chartContainer}>
              {chartData.slice(-6).map((item, idx) => {
                const height = Math.max((item.total / maxSale) * 100, 4);
                return (
                  <View key={idx} style={styles.barCol}>
                    <Text style={styles.barValue}>
                      {item.total >= 1000 ? `${(item.total / 1000).toFixed(0)}k` : item.total}
                    </Text>
                    <View style={[styles.bar, { height, backgroundColor: idx === chartData.slice(-6).length - 1 ? BRAND.primary : BRAND.primaryLight }]} />
                    <Text style={styles.barLabel}>{item.month.slice(0, 3)}</Text>
                  </View>
                );
              })}
            </View>
          </View>
        )}

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

        {/* ── Top Products ── */}
        {topProducts.length > 0 && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Top Products</Text>
            {topProducts.slice(0, 5).map((product, idx) => {
              const imgUrl = getImageUrl(product.image);
              return (
                <View key={product.id} style={styles.topProductRow}>
                  <Text style={styles.topProductRank}>{idx + 1}</Text>
                  <View style={styles.topProductImageWrap}>
                    {imgUrl ? (
                      <Image source={{ uri: imgUrl }} style={styles.topProductImage} />
                    ) : (
                      <View style={styles.topProductImagePlaceholder}>
                        <Ionicons name="cube-outline" size={16} color="#d1d5db" />
                      </View>
                    )}
                  </View>
                  <View style={styles.topProductInfo}>
                    <Text style={styles.topProductName} numberOfLines={1}>{product.name}</Text>
                    <View style={styles.topProductStats}>
                      <Text style={styles.topProductSales}>৳{product.total_sales.toLocaleString()}</Text>
                      <Text style={styles.topProductQty}>{product.total_quantity} sold</Text>
                    </View>
                  </View>
                  {product.avg_rating > 0 && (
                    <View style={styles.topProductRating}>
                      <Ionicons name="star" size={11} color="#f59e0b" />
                      <Text style={styles.topProductRatingText}>{product.avg_rating.toFixed(1)}</Text>
                    </View>
                  )}
                </View>
              );
            })}
          </View>
        )}

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
  chartHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 4,
  },
  growthBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 3,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  growthText: { fontSize: 11, fontWeight: "600" },
  chartContainer: {
    flexDirection: "row",
    alignItems: "flex-end",
    justifyContent: "space-between",
    height: 140,
    paddingTop: 16,
  },
  barCol: {
    flex: 1,
    alignItems: "center",
    justifyContent: "flex-end",
  },
  barValue: {
    fontSize: 9,
    color: "#9ca3af",
    marginBottom: 4,
    fontWeight: "500",
  },
  bar: {
    width: 28,
    borderRadius: 6,
    minHeight: 4,
  },
  barLabel: {
    fontSize: 10,
    color: "#6b7280",
    marginTop: 6,
    fontWeight: "500",
  },
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
  topProductRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#f9fafb",
    gap: 10,
  },
  topProductRank: { fontSize: 13, fontWeight: "700", color: "#9ca3af", width: 20, textAlign: "center" },
  topProductImageWrap: {
    width: 44,
    height: 44,
    borderRadius: 8,
    overflow: "hidden",
    backgroundColor: "#f9fafb",
  },
  topProductImage: { width: "100%", height: "100%", resizeMode: "cover" },
  topProductImagePlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6" },
  topProductInfo: { flex: 1 },
  topProductName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  topProductStats: { flexDirection: "row", alignItems: "center", gap: 8, marginTop: 3 },
  topProductSales: { fontSize: 12, fontWeight: "600", color: BRAND.primary },
  topProductQty: { fontSize: 11, color: "#9ca3af" },
  topProductRating: {
    flexDirection: "row",
    alignItems: "center",
    gap: 2,
    backgroundColor: "#FFFBEB",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  topProductRatingText: { fontSize: 10, fontWeight: "600", color: "#92400e" },
  pendingAmount: { fontSize: 28, fontWeight: "700", color: "#ea580c" },
  errorContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  errorText: { fontSize: 16, fontWeight: "600", color: "#6b7280" },
  errorSubtext: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingHorizontal: 40 },
});
