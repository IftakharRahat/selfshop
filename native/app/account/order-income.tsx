import { useCallback } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  RefreshControl,
  ActivityIndicator,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
  Delivered: { bg: "#D1FAE5", text: "#065F46" },
  Completed: { bg: "#D1FAE5", text: "#065F46" },
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Processing: { bg: "#E0E7FF", text: "#3730A3" },
  Canceled: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

const INSIGHT_CONFIG = [
  { key: "shopproducts", title: "My Shop", icon: "storefront-outline" as const, color: "#7C3AED", bg: "#F5F3FF" },
  { key: "totalorders", title: "Total Orders", icon: "receipt-outline" as const, color: "#2563EB", bg: "#EFF6FF" },
  { key: "soldamount", title: "Sold Amount", icon: "cash-outline" as const, color: "#059669", bg: "#ECFDF5" },
];

export default function OrderIncomeScreen() {
  const queryClient = useQueryClient();

  /* ── Queries ── */
  const incomeQuery = useQuery({
    queryKey: ["order-income-history"],
    queryFn: async () => {
      const { data } = await apiClient.get("/income-history");
      return data?.data ?? data ?? [];
    },
  });

  const meQuery = useQuery({
    queryKey: ["user-profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-profile");
      return data?.data ?? data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const incomeList: any[] = Array.isArray(incomeQuery.data) ? incomeQuery.data : [];
  const profile = meQuery.data;

  const isRefreshing = incomeQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["order-income-history"] });
    queryClient.invalidateQueries({ queryKey: ["user-profile"] });
  }, [queryClient]);

  const renderIncomeItem = ({ item, index }: { item: any; index: number }) => {
    const statusKey = item.status ?? "Pending";
    const statusStyle = STATUS_STYLES[statusKey] ?? STATUS_STYLES.Pending;

    return (
      <View style={styles.incomeCard}>
        <View style={styles.incomeTop}>
          <View style={styles.incomeLeft}>
            <View style={styles.incomeIconWrapper}>
              <Ionicons name="receipt-outline" size={18} color={ACCENT} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.invoiceText} numberOfLines={1}>
                {item.order_invoice ?? item.invoice_code ?? item.invoice_id ?? `#${index + 1}`}
              </Text>
              <Text style={styles.dateText}>
                {item.created_at ? new Date(item.created_at).toLocaleDateString() : ""}
              </Text>
            </View>
          </View>
          <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
            <Text style={[styles.statusText, { color: statusStyle.text }]}>
              {statusKey}
            </Text>
          </View>
        </View>

        <View style={styles.incomeBottom}>
          <View style={styles.pricePair}>
            <Text style={styles.priceLabel}>Product Price</Text>
            <Text style={styles.priceValue}>
              {formatCurrency(item.product_price)}
            </Text>
          </View>
          <View style={styles.pricePair}>
            <Text style={styles.priceLabel}>Income</Text>
            <Text style={[styles.priceValue, { color: "#059669" }]}>
              {formatCurrency(item.amount)}
            </Text>
          </View>
        </View>
      </View>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Order Income",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        {/* ── Insight Cards ── */}
        <View style={styles.insightsRow}>
          {INSIGHT_CONFIG.map((insight) => (
            <View key={insight.key} style={[styles.insightCard, { backgroundColor: insight.bg }]}>
              <View style={[styles.insightIcon, { backgroundColor: `${insight.color}15` }]}>
                <Ionicons name={insight.icon} size={18} color={insight.color} />
              </View>
              <Text style={styles.insightValue}>
                {meQuery.isLoading ? "..." : String(profile?.[insight.key] ?? 0)}
              </Text>
              <Text style={styles.insightLabel}>{insight.title}</Text>
            </View>
          ))}
        </View>

        {/* ── Income List ── */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>All Incomes</Text>
          <Text style={styles.sectionCount}>
            {incomeList.length} record{incomeList.length !== 1 ? "s" : ""}
          </Text>
        </View>

        <FlatList
          data={incomeList}
          renderItem={renderIncomeItem}
          keyExtractor={(item, i) => String(item.id ?? i)}
          contentContainerStyle={[
            styles.listContent,
            incomeList.length === 0 && { flex: 1 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={isRefreshing}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          ListEmptyComponent={
            incomeQuery.isLoading ? (
              <View style={styles.emptyState}>
                <ActivityIndicator size="large" color={ACCENT} />
              </View>
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="cash-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                  No income records yet
                </Text>
                <Text fontSize="$2" color="#9CA3AF" mt="$1">
                  Your per-order income will appear here
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

  /* ── Insights ── */
  insightsRow: {
    flexDirection: "row",
    gap: 10,
    padding: 16,
    paddingBottom: 8,
  },
  insightCard: {
    flex: 1,
    borderRadius: 14,
    padding: 12,
    alignItems: "center",
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.04)",
  },
  insightIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 6,
  },
  insightValue: {
    fontSize: 18,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 2,
  },
  insightLabel: {
    fontSize: 10,
    color: "#6B7280",
    fontWeight: "500",
    textAlign: "center",
  },

  /* ── Section Header ── */
  sectionHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  sectionCount: {
    fontSize: 12,
    color: "#9CA3AF",
    fontWeight: "500",
  },

  /* ── List ── */
  listContent: {
    paddingHorizontal: 16,
    gap: 10,
    paddingBottom: 40,
  },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
  },

  /* ── Income Card ── */
  incomeCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  incomeTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    marginBottom: 12,
  },
  incomeLeft: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    flex: 1,
    marginRight: 8,
  },
  incomeIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  invoiceText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  dateText: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 2,
  },
  statusPill: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 11,
    fontWeight: "600",
  },
  incomeBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    backgroundColor: "#F9FAFB",
    borderRadius: 10,
    padding: 10,
  },
  pricePair: {
    alignItems: "center",
  },
  priceLabel: {
    fontSize: 10,
    color: "#6B7280",
    fontWeight: "500",
    marginBottom: 2,
  },
  priceValue: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1A1A2E",
  },
});
