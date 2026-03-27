import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  FlatList,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import { OrderListSkeleton } from "@/components/skeleton";

interface OrderItem {
  id: number;
  invoiceID: string;
  orderDate: string | null;
  status: string;
  display_status?: string;
  customer_name: string | null;
  customer_phone: string | null;
  vendor_item_count: number;
  vendor_subtotal: number;
}

const STATUS_TABS = [
  { key: "all", label: "All" },
  { key: "Pending", label: "Pending" },
  { key: "Confirmed", label: "Confirmed" },
  { key: "Ontheway", label: "On Delivery" },
  { key: "Delivered", label: "Delivered" },
  { key: "Canceled", label: "Cancelled" },
];

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Confirmed: { bg: "#D1FAE5", text: "#065F46" },
  Processing: { bg: "#DBEAFE", text: "#1E40AF" },
  Ontheway: { bg: "#E0E7FF", text: "#3730A3" },
  OnDelivery: { bg: "#E0E7FF", text: "#3730A3" },
  Delivered: { bg: "#D1FAE5", text: "#065F46" },
  Complete: { bg: "#D1FAE5", text: "#065F46" },
  Canceled: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

export default function OrdersScreen() {
  const insets = useSafeAreaInsets();
  const [activeStatus, setActiveStatus] = useState("all");

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-orders", activeStatus],
    queryFn: async () => {
      const params: Record<string, string | number> = { per_page: 25 };
      if (activeStatus !== "all") params.status = activeStatus;
      const { data } = await apiClient.get("/vendor/orders", { params });
      return data?.data as { orders: OrderItem[]; pagination: { total: number } };
    },
  });

  const orders = data?.orders ?? [];
  const statusColor = (status: string) =>
    STATUS_COLORS[status] ?? { bg: "#F3F4F6", text: "#374151" };

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>Orders</Text>
        </View>
        <OrderListSkeleton />
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Orders</Text>
        <Text style={styles.totalBadge}>{data?.pagination?.total ?? 0}</Text>
      </View>

      {/* Status Tabs */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.tabsContainer}
      >
        {STATUS_TABS.map((tab) => (
          <TouchableOpacity
            key={tab.key}
            onPress={() => setActiveStatus(tab.key)}
            style={[styles.tab, activeStatus === tab.key && styles.tabActive]}
          >
            <Text style={[styles.tabText, activeStatus === tab.key && styles.tabTextActive]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        ))}
      </ScrollView>

      {isError ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="alert-circle-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>Failed to load orders</Text>
        </View>
      ) : orders.length === 0 ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="clipboard-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>No orders found</Text>
        </View>
      ) : (
        <FlatList
          data={orders}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />
          }
          renderItem={({ item }) => {
            const sc = statusColor(item.status);
            return (
              <TouchableOpacity style={styles.orderCard} onPress={() => router.push(`/order/${item.id}`)} activeOpacity={0.7}>
                <View style={styles.orderHeader}>
                  <Text style={styles.invoiceId}>#{item.invoiceID}</Text>
                  <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
                    <Text style={[styles.statusText, { color: sc.text }]}>
                      {item.display_status ?? item.status}
                    </Text>
                  </View>
                </View>
                <View style={styles.orderBody}>
                  <View style={styles.orderInfo}>
                    <Ionicons name="person-outline" size={14} color="#9ca3af" />
                    <Text style={styles.orderInfoText}>{item.customer_name ?? "—"}</Text>
                  </View>
                  <View style={styles.orderInfo}>
                    <Ionicons name="cube-outline" size={14} color="#9ca3af" />
                    <Text style={styles.orderInfoText}>{item.vendor_item_count} items</Text>
                  </View>
                </View>
                <View style={styles.orderFooter}>
                  <Text style={styles.orderDate}>
                    {item.orderDate
                      ? new Date(item.orderDate).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })
                      : "—"}
                  </Text>
                  <Text style={styles.orderTotal}>৳{item.vendor_subtotal?.toLocaleString()}</Text>
                </View>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={<View style={{ height: 100 }} />}
        />
      )}
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
  totalBadge: {
    fontSize: 12,
    fontWeight: "600",
    color: BRAND.primary,
    backgroundColor: BRAND.primaryLight,
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 10,
    overflow: "hidden",
  },
  tabsContainer: { paddingHorizontal: 16, paddingVertical: 10, gap: 6 },
  tab: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#e5e7eb",
  },
  tabActive: {
    backgroundColor: BRAND.primary,
    borderColor: BRAND.primary,
  },
  tabText: { fontSize: 13, fontWeight: "500", color: "#6b7280" },
  tabTextActive: { color: "#fff" },
  listContent: { padding: 16, gap: 10 },
  orderCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  orderHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  invoiceId: { fontSize: 14, fontWeight: "600", color: "#1a1a2e" },
  statusBadge: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  statusText: { fontSize: 11, fontWeight: "600" },
  orderBody: { flexDirection: "row", gap: 16, marginBottom: 8 },
  orderInfo: { flexDirection: "row", alignItems: "center", gap: 4 },
  orderInfoText: { fontSize: 12, color: "#6b7280" },
  orderFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: "#f9fafb",
  },
  orderDate: { fontSize: 12, color: "#9ca3af" },
  orderTotal: { fontSize: 15, fontWeight: "700", color: BRAND.primary },
  emptyContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
});
