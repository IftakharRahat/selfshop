import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
  ScrollView,
} from "react-native";
import { Text } from "tamagui";
import { router, Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { OrdersSkeleton } from "@/components/skeleton";

const ACCENT = "#E5005F";

/* ── Status Tabs ── */
const STATUS_TABS = [
  { key: "Pending", label: "Pending", color: "#D97706", bg: "#FEF3C7" },
  { key: "Accepted", label: "Accepted", color: "#065F46", bg: "#D1FAE5" },
  { key: "Confirmed", label: "Confirmed", color: "#1E40AF", bg: "#DBEAFE" },
  { key: "Processing", label: "Processing", color: "#3730A3", bg: "#E0E7FF" },
  { key: "Ontheway", label: "On the Way", color: "#155E75", bg: "#CFFAFE" },
  { key: "Delivered", label: "Delivered", color: "#065F46", bg: "#D1FAE5" },
  { key: "Canceled", label: "Cancelled", color: "#991B1B", bg: "#FEE2E2" },
];

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Accepted: { bg: "#D1FAE5", text: "#065F46" },
  Confirmed: { bg: "#DBEAFE", text: "#1E40AF" },
  Processing: { bg: "#E0E7FF", text: "#3730A3" },
  Ontheway: { bg: "#CFFAFE", text: "#155E75" },
  "On the way": { bg: "#CFFAFE", text: "#155E75" },
  Delivered: { bg: "#D1FAE5", text: "#065F46" },
  Canceled: { bg: "#FEE2E2", text: "#991B1B" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
};

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

export default function OrdersScreen() {
  const queryClient = useQueryClient();
  const [activeStatus, setActiveStatus] = useState("Pending");
  const [page, setPage] = useState(1);

  /* ── Order Counts ── */
  const countQuery = useQuery({
    queryKey: ["order-count"],
    queryFn: async () => {
      const { data } = await apiClient.get("/order-count");
      return data?.data ?? data ?? {};
    },
    staleTime: 60 * 1000,
  });

  /* ── Orders by Status ── */
  const ordersQuery = useQuery({
    queryKey: ["orders", activeStatus, page],
    queryFn: async () => {
      const { data } = await apiClient.get(`/order-data/${activeStatus}?page=${page}`);
      return data;
    },
    staleTime: 30 * 1000,
  });

  const ordersRaw = ordersQuery.data;
  // Handle: data.data.data (triple-nested), data.data (double), or data (flat array)
  const ordersList =
    ordersRaw?.data?.data ?? ordersRaw?.data ?? (Array.isArray(ordersRaw) ? ordersRaw : []);
  const orders: any[] = Array.isArray(ordersList) ? ordersList : [];
  const lastPage = ordersRaw?.data?.last_page ?? ordersRaw?.last_page ?? 1;
  const counts = countQuery.data ?? {};

  /* ── Handlers ── */
  const handleTabChange = (status: string) => {
    setActiveStatus(status);
    setPage(1);
  };

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["order-count"] });
    queryClient.invalidateQueries({ queryKey: ["orders", activeStatus] });
  }, [queryClient, activeStatus]);

  const loadMore = () => {
    if (page < lastPage && !ordersQuery.isFetching) {
      setPage((p) => p + 1);
    }
  };

  /* ── Get count for a status tab ── */
  function getCount(key: string): number {
    const k = key.toLowerCase();
    return Number(counts[key] ?? counts[k] ?? counts[`${k}_count`] ?? 0);
  }

  /* ── Render ── */
  const renderOrder = ({ item: order }: { item: any }) => {
    const displayStatus =
      order.customer_status ?? order.display_status ?? order.status ?? activeStatus;
    const statusStyle = STATUS_COLORS[displayStatus] ?? STATUS_COLORS.Pending;

    return (
      <Pressable
        style={({ pressed }) => [
          styles.orderCard,
          pressed && { backgroundColor: "#FAFAFA" },
        ]}
        onPress={() =>
          router.push({
            pathname: "/account/order-detail",
            params: { invoiceID: order.invoiceID, id: order.id },
          } as any)
        }
      >
        <View style={styles.orderTop}>
          <View style={styles.orderIconWrapper}>
            <Ionicons name="cube-outline" size={20} color={ACCENT} />
          </View>
          <View style={styles.orderInfo}>
            <Text style={styles.orderInvoice} numberOfLines={1}>
              {order.invoiceID}
            </Text>
            <Text style={styles.orderCustomer} numberOfLines={1}>
              {order.customers?.customerName ?? "Customer"}
            </Text>
            <Text style={styles.orderDate}>{order.orderDate}</Text>
          </View>
          <View style={styles.orderRight}>
            <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
              <Text style={[styles.statusText, { color: statusStyle.text }]}>
                {displayStatus}
              </Text>
            </View>
            <Text style={styles.orderAmount}>{formatCurrency(order.total)}</Text>
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
          title: "My Orders",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        {/* ── Status Tabs ── */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          style={{ flexGrow: 0 }}
          contentContainerStyle={styles.tabsContainer}
        >
          {STATUS_TABS.map((tab) => {
            const isActive = tab.key === activeStatus;
            const count = getCount(tab.key);
            return (
              <Pressable
                key={tab.key}
                style={[styles.tab, isActive && styles.tabActive]}
                onPress={() => handleTabChange(tab.key)}
              >
                <Text style={[styles.tabLabel, isActive && styles.tabLabelActive]}>
                  {tab.label}
                </Text>
                {count > 0 && (
                  <View style={[styles.tabBadge, isActive && styles.tabBadgeActive]}>
                    <Text style={[styles.tabBadgeText, isActive && { color: "#fff" }]}>
                      {count > 99 ? "99+" : count}
                    </Text>
                  </View>
                )}
              </Pressable>
            );
          })}
        </ScrollView>

        {/* ── Orders List ── */}
        <FlatList
          data={orders}
          renderItem={renderOrder}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={[
            styles.listContent,
            orders.length === 0 && { flex: 1 },
          ]}
          ItemSeparatorComponent={() => <View style={{ height: 10 }} />}
          refreshControl={
            <RefreshControl
              refreshing={ordersQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          onEndReached={loadMore}
          onEndReachedThreshold={0.3}
          ListEmptyComponent={
            ordersQuery.isLoading ? (
              <OrdersSkeleton />
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="receipt-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                  No {activeStatus.toLowerCase()} orders
                </Text>
              </View>
            )
          }
          ListFooterComponent={
            ordersQuery.isFetching && page > 1 ? (
              <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 16 }} />
            ) : null
          }
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  /* ── Tabs ── */
  tabsContainer: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    gap: 8,
  },
  tab: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 24,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  tabActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  tabLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
  },
  tabLabelActive: {
    color: "#fff",
  },
  tabBadge: {
    minWidth: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: "#F0F0F5",
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 5,
  },
  tabBadgeActive: {
    backgroundColor: "rgba(255,255,255,0.25)",
  },
  tabBadgeText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#374151",
  },

  /* ── List ── */
  listContent: {
    padding: 16,
    paddingBottom: 40,
  },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
  },

  /* ── Order Card ── */
  orderCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  orderTop: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 12,
  },
  orderIconWrapper: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginTop: 2,
  },
  orderInfo: { flex: 1 },
  orderInvoice: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  orderCustomer: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 2,
  },
  orderDate: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 2,
  },
  orderRight: {
    alignItems: "flex-end",
    gap: 6,
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
  orderAmount: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
  },
});
