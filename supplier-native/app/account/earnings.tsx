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

interface EarningsSummary {
  total_sales: number;
  total_commission: number;
  net_earnings: number;
  pending_balance: number;
  available_balance: number;
  paid_total: number;
  pending_payout_request_amount: number;
}

interface EarningRow {
  id: number;
  order_id: number;
  order: { invoiceID: string; orderDate: string; status: string } | null;
  product_name: string;
  quantity: number;
  line_total: number;
  commission_percent: number;
  commission_amount: number;
  net_amount: number;
  status: string;
  created_at: string;
}

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  confirmed: { bg: "#D1FAE5", text: "#065F46" },
  pending: { bg: "#FEF3C7", text: "#92400E" },
  cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

export default function EarningsScreen() {
  const insets = useSafeAreaInsets();

  const { data: summary, isLoading: summaryLoading } = useQuery({
    queryKey: ["vendor-earnings-summary"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/earnings/summary");
      return data?.data as EarningsSummary;
    },
  });

  const { data: earningsData, isLoading: earningsLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-earnings"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/earnings", { params: { per_page: 30 } });
      return data?.data?.earnings as EarningRow[];
    },
  });

  const isLoading = summaryLoading || earningsLoading;
  const earnings = earningsData ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Earnings</Text>
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
          {/* Summary Cards */}
          <View style={styles.summaryCard}>
            <Text style={styles.summaryLabel}>Available Balance</Text>
            <Text style={styles.summaryAmount}>৳{(summary?.available_balance ?? 0).toLocaleString()}</Text>
          </View>

          <View style={styles.statsGrid}>
            {[
              { label: "Total Sales", value: summary?.total_sales, icon: "trending-up", color: "#059669" },
              { label: "Net Earnings", value: summary?.net_earnings, icon: "wallet", color: BRAND.primary },
              { label: "Commission", value: summary?.total_commission, icon: "git-branch", color: "#d97706" },
              { label: "Paid Out", value: summary?.paid_total, icon: "checkmark-done", color: "#10b981" },
              { label: "Pending", value: summary?.pending_balance, icon: "time", color: "#f59e0b" },
              { label: "Payout Requests", value: summary?.pending_payout_request_amount, icon: "send", color: "#6366f1" },
            ].map((item) => (
              <View key={item.label} style={styles.statCard}>
                <Ionicons name={item.icon as any} size={16} color={item.color} />
                <Text style={styles.statCardValue}>৳{(item.value ?? 0).toLocaleString()}</Text>
                <Text style={styles.statCardLabel}>{item.label}</Text>
              </View>
            ))}
          </View>

          {/* Earnings History */}
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Recent Earnings</Text>
            {earnings.length === 0 ? (
              <Text style={styles.emptyText}>No earnings yet</Text>
            ) : (
              earnings.map((e) => {
                const sc = STATUS_COLORS[e.status] ?? STATUS_COLORS.pending;
                return (
                  <View key={e.id} style={styles.earningRow}>
                    <View style={styles.earningInfo}>
                      <Text style={styles.earningProduct} numberOfLines={1}>{e.product_name}</Text>
                      <Text style={styles.earningOrder}>
                        #{e.order?.invoiceID ?? "—"} · {e.quantity} pcs
                      </Text>
                    </View>
                    <View style={styles.earningRight}>
                      <Text style={styles.earningAmount}>৳{e.net_amount.toLocaleString()}</Text>
                      <View style={[styles.earningStatus, { backgroundColor: sc.bg }]}>
                        <Text style={[styles.earningStatusText, { color: sc.text }]}>{e.status}</Text>
                      </View>
                    </View>
                  </View>
                );
              })
            )}
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
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
  scrollContent: { padding: 16 },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  summaryCard: {
    backgroundColor: BRAND.primary, borderRadius: 16, padding: 20, alignItems: "center", marginBottom: 12,
  },
  summaryLabel: { fontSize: 13, color: "rgba(255,255,255,0.7)", fontWeight: "500" },
  summaryAmount: { fontSize: 32, fontWeight: "800", color: "#fff", marginTop: 4 },
  statsGrid: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginBottom: 12 },
  statCard: {
    width: "31%", backgroundColor: "#fff", borderRadius: 12, padding: 12,
    alignItems: "center", gap: 4, ...CARD_SHADOW,
  },
  statCardValue: { fontSize: 13, fontWeight: "700", color: "#1a1a2e" },
  statCardLabel: { fontSize: 9, color: "#9ca3af", fontWeight: "500", textAlign: "center" },
  sectionCard: {
    backgroundColor: "#fff", borderRadius: 14, padding: 16, ...CARD_SHADOW,
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 12 },
  emptyText: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingVertical: 16 },
  earningRow: {
    flexDirection: "row", justifyContent: "space-between", alignItems: "center",
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb",
  },
  earningInfo: { flex: 1 },
  earningProduct: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  earningOrder: { fontSize: 11, color: "#9ca3af", marginTop: 2 },
  earningRight: { alignItems: "flex-end", gap: 4 },
  earningAmount: { fontSize: 14, fontWeight: "700", color: BRAND.primary },
  earningStatus: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  earningStatusText: { fontSize: 9, fontWeight: "600", textTransform: "capitalize" },
});
