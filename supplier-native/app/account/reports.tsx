import React from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND, CARD_SHADOW } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { SubScreenSkeleton } from "@/components/skeleton";

interface SalesSeriesItem {
  period: string;
  total_sales: number;
  total_commission: number;
  net_earnings: number;
  order_count: number;
}

interface TopProduct {
  product_id: number;
  product_name: string;
  total_sales: number;
  total_quantity: number;
  order_count: number;
}

export default function ReportsScreen() {
  const insets = useSafeAreaInsets();

  const { data: salesData, isLoading: salesLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-reports-sales"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/reports/sales");
      return data?.data as { period: string; from: string; to: string; series: SalesSeriesItem[] };
    },
  });

  const { data: topProducts, isLoading: topLoading } = useQuery({
    queryKey: ["vendor-reports-top-products"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/reports/top-products", { params: { limit: 10 } });
      return data?.data?.top_products as TopProduct[];
    },
  });

  const { data: breakdown } = useQuery({
    queryKey: ["vendor-reports-breakdown"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/reports/sales-breakdown");
      return data?.data as { by_fulfillment_type: Record<string, { total_sales: number; net_earnings: number; order_count: number }> };
    },
  });

  const series = salesData?.series ?? [];
  const maxSales = Math.max(...series.map((s) => s.total_sales), 1);
  const isLoading = salesLoading || topLoading;

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Reports</Text>
        <View style={{ width: 32 }} />
      </View>

      {isLoading ? (
        <SubScreenSkeleton />
      ) : (
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
        >
          {/* Sales Chart */}
          {series.length > 0 && (
            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>Sales Trend</Text>
              <Text style={styles.periodText}>{salesData?.period} · {salesData?.from} → {salesData?.to}</Text>
              <View style={styles.chartContainer}>
                {series.slice(-8).map((item, idx) => {
                  const height = Math.max((item.total_sales / maxSales) * 100, 4);
                  return (
                    <View key={idx} style={styles.barCol}>
                      <Text style={styles.barValue}>
                        {item.total_sales >= 1000 ? `${(item.total_sales / 1000).toFixed(0)}k` : item.total_sales}
                      </Text>
                      <View style={[styles.bar, { height, backgroundColor: idx === series.slice(-8).length - 1 ? BRAND.primary : BRAND.primaryLight }]} />
                      <Text style={styles.barLabel}>{item.period.slice(-5)}</Text>
                    </View>
                  );
                })}
              </View>

              {/* Totals */}
              <View style={styles.totalsRow}>
                {[
                  { label: "Total Sales", value: series.reduce((s, i) => s + i.total_sales, 0) },
                  { label: "Commission", value: series.reduce((s, i) => s + i.total_commission, 0) },
                  { label: "Net Earnings", value: series.reduce((s, i) => s + i.net_earnings, 0) },
                  { label: "Orders", value: series.reduce((s, i) => s + i.order_count, 0), isCurrency: false },
                ].map((t) => (
                  <View key={t.label} style={styles.totalItem}>
                    <Text style={styles.totalValue}>
                      {(t as any).isCurrency === false ? t.value.toLocaleString() : `৳${t.value.toLocaleString()}`}
                    </Text>
                    <Text style={styles.totalLabel}>{t.label}</Text>
                  </View>
                ))}
              </View>
            </View>
          )}

          {/* Fulfillment Breakdown */}
          {breakdown?.by_fulfillment_type && Object.keys(breakdown.by_fulfillment_type).length > 0 && (
            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>By Fulfillment Type</Text>
              {Object.entries(breakdown.by_fulfillment_type).map(([type, data]) => (
                <View key={type} style={styles.breakdownRow}>
                  <View style={styles.breakdownLeft}>
                    <Ionicons name={type === "dropship" ? "rocket-outline" : "cube-outline"} size={16} color={BRAND.primary} />
                    <Text style={styles.breakdownType}>{type || "Standard"}</Text>
                  </View>
                  <View style={styles.breakdownRight}>
                    <Text style={styles.breakdownSales}>৳{data.total_sales.toLocaleString()}</Text>
                    <Text style={styles.breakdownOrders}>{data.order_count} orders</Text>
                  </View>
                </View>
              ))}
            </View>
          )}

          {/* Top Products */}
          {(topProducts ?? []).length > 0 && (
            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>Top Products</Text>
              {(topProducts ?? []).map((p, idx) => (
                <View key={p.product_id} style={styles.topRow}>
                  <Text style={styles.topRank}>{idx + 1}</Text>
                  <View style={styles.topInfo}>
                    <Text style={styles.topName} numberOfLines={1}>{p.product_name}</Text>
                    <Text style={styles.topMeta}>{p.total_quantity} sold · {p.order_count} orders</Text>
                  </View>
                  <Text style={styles.topSales}>৳{p.total_sales.toLocaleString()}</Text>
                </View>
              ))}
            </View>
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
  sectionCard: { backgroundColor: "#fff", borderRadius: 14, padding: 16, marginBottom: 12, ...CARD_SHADOW },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 8 },
  periodText: { fontSize: 11, color: "#9ca3af", marginBottom: 12 },
  chartContainer: { flexDirection: "row", alignItems: "flex-end", justifyContent: "space-between", height: 120, paddingTop: 12 },
  barCol: { flex: 1, alignItems: "center", justifyContent: "flex-end" },
  barValue: { fontSize: 8, color: "#9ca3af", marginBottom: 3, fontWeight: "500" },
  bar: { width: 22, borderRadius: 4, minHeight: 4 },
  barLabel: { fontSize: 8, color: "#6b7280", marginTop: 4, fontWeight: "500" },
  totalsRow: { flexDirection: "row", marginTop: 14, paddingTop: 14, borderTopWidth: 1, borderTopColor: "#f3f4f6", gap: 4 },
  totalItem: { flex: 1, alignItems: "center" },
  totalValue: { fontSize: 12, fontWeight: "700", color: "#1a1a2e" },
  totalLabel: { fontSize: 8, color: "#9ca3af", fontWeight: "500", marginTop: 2 },
  breakdownRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb" },
  breakdownLeft: { flexDirection: "row", alignItems: "center", gap: 8 },
  breakdownType: { fontSize: 13, fontWeight: "600", color: "#1a1a2e", textTransform: "capitalize" },
  breakdownRight: { alignItems: "flex-end" },
  breakdownSales: { fontSize: 14, fontWeight: "700", color: BRAND.primary },
  breakdownOrders: { fontSize: 10, color: "#9ca3af" },
  topRow: { flexDirection: "row", alignItems: "center", paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: "#f9fafb", gap: 10 },
  topRank: { fontSize: 13, fontWeight: "700", color: "#9ca3af", width: 20, textAlign: "center" },
  topInfo: { flex: 1 },
  topName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  topMeta: { fontSize: 10, color: "#9ca3af", marginTop: 1 },
  topSales: { fontSize: 13, fontWeight: "700", color: BRAND.primary },
});
