import { useCallback, useEffect, useMemo, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  Image,
  TextInput,
  Text as RNText,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useFocusEffect } from "expo-router";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { useSession } from "@/lib/auth-client";
import apiClient from "@/lib/api-client";
import { OrdersSkeleton } from "@/components/skeleton";
import DateFilter, { DateFilterKey, isWithinDateRange } from "@/components/date-filter";
import { TAB_BAR_HEIGHT } from "@/components/floating-tab-bar";

const ACCENT = "#E5005F";

/* ── Image URL helper ── */
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string | null {
  if (!path || path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

/* ── Status Tabs ── */
const STATUS_TABS = [
  { key: "Pending", label: "Pending", color: "#D97706", bg: "#FEF3C7" },
  { key: "Accepted", label: "Accepted", color: "#065F46", bg: "#D1FAE5" },
  { key: "Confirmed", label: "Confirmed", color: "#1E40AF", bg: "#DBEAFE" },
  { key: "Processing", label: "Processing", color: "#3730A3", bg: "#E0E7FF" },
  { key: "Ontheway", label: "On the Way", color: "#155E75", bg: "#CFFAFE" },
  { key: "Delivered", label: "Delivered", color: "#065F46", bg: "#D1FAE5" },
  { key: "Rejected", label: "Rejected", color: "#991B1B", bg: "#FEE2E2" },
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

function parseMoney(value: unknown): number {
  const num = Number(value ?? 0);
  return Number.isFinite(num) ? num : 0;
}

function hasMoneyValue(value: unknown): boolean {
  return value !== undefined && value !== null && value !== "";
}

function getOrderTotal(order: any): number {
  if (hasMoneyValue(order?.subTotal) || hasMoneyValue(order?.deliveryCharge)) {
    return (
      parseMoney(order?.subTotal) +
      parseMoney(order?.deliveryCharge) -
      parseMoney(order?.discountCharge)
    );
  }
  return parseMoney(order?.total ?? order?.paymentAmount ?? order?.payable_amount);
}

export default function OrdersTabScreen() {
  const insets = useSafeAreaInsets();
  const { data: session } = useSession();
  const isLoggedIn = !!session?.user;
  const queryClient = useQueryClient();
  const [activeStatus, setActiveStatus] = useState("Pending");
  const [page, setPage] = useState(1);
  const [searchQuery, setSearchQuery] = useState("");
  const [debouncedSearchQuery, setDebouncedSearchQuery] = useState("");
  const [dateFilter, setDateFilter] = useState<DateFilterKey>("all");

  /* ── Refresh on focus ── */
  useFocusEffect(
    useCallback(() => {
      queryClient.invalidateQueries({ queryKey: ["order-count"] });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
    }, [queryClient])
  );

  /* ── Order Counts ── */
  const countQuery = useQuery({
    queryKey: ["order-count"],
    queryFn: async () => {
      const { data } = await apiClient.get("/order-count");
      return data?.data ?? data ?? {};
    },
    enabled: isLoggedIn,
    staleTime: 60 * 1000,
  });

  /* ── Orders by Status ── */
  const ordersQuery = useQuery({
    queryKey: ["orders", activeStatus, page, debouncedSearchQuery.trim()],
    queryFn: async () => {
      const params = new URLSearchParams({ page: String(page) });
      const search = debouncedSearchQuery.trim();
      if (search) params.set("search", search);
      const { data } = await apiClient.get(`/order-data/${activeStatus}?${params.toString()}`);
      return data;
    },
    enabled: isLoggedIn,
    staleTime: 30 * 1000,
  });

  const ordersRaw = ordersQuery.data;
  const ordersList =
    ordersRaw?.data?.data ?? ordersRaw?.data ?? (Array.isArray(ordersRaw) ? ordersRaw : []);
  const orders: any[] = Array.isArray(ordersList) ? ordersList : [];
  const lastPage = ordersRaw?.data?.last_page ?? ordersRaw?.last_page ?? 1;
  const counts = countQuery.data ?? {};
  const normalizedSearch = searchQuery.trim().toLowerCase();
  const isSearching = normalizedSearch.length > 0;

  const filteredOrders = useMemo(() => {
    return orders.filter((order) => {
      if (!isWithinDateRange(order.orderDate ?? order.created_at, dateFilter)) return false;
      if (!isSearching) return true;
      const searchableValues = [
        order.invoiceID, order.id, order.order_id, order.orderId,
        order.customers?.customerName, order.customers?.customerPhone,
        order.customer?.name, order.customer?.phone,
        order.status, order.customer_status, order.display_status,
      ];
      return searchableValues.some((value) =>
        String(value ?? "").toLowerCase().includes(normalizedSearch)
      );
    });
  }, [orders, isSearching, normalizedSearch, dateFilter]);

  useEffect(() => {
    const timeout = setTimeout(() => {
      setDebouncedSearchQuery(searchQuery.trim());
    }, 350);
    return () => clearTimeout(timeout);
  }, [searchQuery]);

  /* ── Handlers ── */
  const handleTabChange = (status: string) => {
    setActiveStatus(status);
    setPage(1);
  };

  const handleDateFilterChange = (key: DateFilterKey) => {
    setDateFilter(key);
    setPage(1);
  };

  const handleSearchChange = (value: string) => {
    setSearchQuery(value);
    setPage(1);
  };

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["order-count"] });
    queryClient.invalidateQueries({ queryKey: ["orders", activeStatus] });
  }, [queryClient, activeStatus]);

  const loadMore = () => {
    if (!isSearching && page < lastPage && !ordersQuery.isFetching) {
      setPage((p) => p + 1);
    }
  };

  function getCount(key: string): number {
    const k = key.toLowerCase();
    return Number(counts[key] ?? counts[k] ?? counts[`${k}_count`] ?? 0);
  }

  /* ── Guest View ── */
  if (!isLoggedIn) {
    return (
      <View style={styles.container}>
        <View style={styles.guestContent}>
          <Ionicons name="cube-outline" size={80} color="#D8D8D8" />
          <Text fontSize="$5" fontWeight="bold" color="#1A1A2E" mt="$3">
            My Orders
          </Text>
          <Text fontSize="$3" color="#8E8E93" mt="$1" style={{ textAlign: "center" }}>
            Sign in to view your orders
          </Text>
          <Pressable
            style={({ pressed }) => [styles.signInButton, pressed && { opacity: 0.85 }]}
            onPress={() => router.push("/login")}
          >
            <Text fontSize="$4" fontWeight="bold" color="#fff">
              Sign In
            </Text>
          </Pressable>
        </View>
      </View>
    );
  }

  /* ── Render Order Card ── */
  const renderOrder = ({ item: order }: { item: any }) => {
    const displayStatus =
      order.customer_status ?? order.display_status ?? order.status ?? activeStatus;
    const statusStyle = STATUS_COLORS[displayStatus] ?? STATUS_COLORS.Pending;
    const orderTotal = getOrderTotal(order);

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
          {(() => {
            const imgPath = order.orderproducts?.[0]?.product?.ViewProductImage;
            const imgUri = resolveImageUrl(imgPath);
            return imgUri ? (
              <Image source={{ uri: imgUri }} style={styles.orderImage} resizeMode="cover" />
            ) : (
              <View style={styles.orderIconWrapper}>
                <Ionicons name="cube-outline" size={20} color={ACCENT} />
              </View>
            );
          })()}
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
            <Text style={styles.orderAmount}>{formatCurrency(orderTotal)}</Text>
          </View>
        </View>
      </Pressable>
    );
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      {/* ── Header ── */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>My Orders</Text>
      </View>

      {/* ── Status Tabs ── */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.tabsScroll}
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
              <RNText style={[styles.tabLabel, isActive && styles.tabLabelActive]} numberOfLines={1}>
                {tab.label}
              </RNText>
              {count > 0 && (
                <View style={[styles.tabBadge, isActive && styles.tabBadgeActive]}>
                  <RNText style={[styles.tabBadgeText, isActive && { color: "#fff" }]} numberOfLines={1}>
                    {count > 99 ? "99+" : count}
                  </RNText>
                </View>
              )}
            </Pressable>
          );
        })}
      </ScrollView>

      {/* ── Date Filter ── */}
      <DateFilter value={dateFilter} onChange={handleDateFilterChange} />

      {/* ── Search ── */}
      <View style={styles.searchSection}>
        <View style={styles.searchInputWrap}>
          <Ionicons name="search-outline" size={18} color="#8E8E93" />
          <TextInput
            style={styles.searchInput}
            value={searchQuery}
            onChangeText={handleSearchChange}
            placeholder="Search by order ID or invoice"
            placeholderTextColor="#9CA3AF"
            returnKeyType="search"
            autoCapitalize="none"
            autoCorrect={false}
          />
          {isSearching && (
            <Pressable
              onPress={() => setSearchQuery("")}
              hitSlop={8}
              style={({ pressed }) => [styles.searchClearBtn, pressed && { opacity: 0.75 }]}
            >
              <Ionicons name="close" size={14} color="#fff" />
            </Pressable>
          )}
        </View>
      </View>

      {/* ── Orders List ── */}
      <FlatList
        data={filteredOrders}
        renderItem={renderOrder}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={[
          styles.listContent,
          { paddingBottom: TAB_BAR_HEIGHT + 20 },
          filteredOrders.length === 0 && { flex: 1 },
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
                {isSearching
                  ? `No orders match "${searchQuery.trim()}"`
                  : `No ${activeStatus.toLowerCase()} orders`}
              </Text>
              {isSearching && (
                <Pressable
                  style={({ pressed }) => [
                    styles.clearSearchButton,
                    pressed && { opacity: 0.85 },
                  ]}
                  onPress={() => setSearchQuery("")}
                >
                  <Text style={styles.clearSearchText}>Clear Search</Text>
                </Pressable>
              )}
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
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  /* ── Guest ── */
  guestContent: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 40,
    paddingBottom: 100,
  },
  signInButton: {
    backgroundColor: ACCENT,
    paddingVertical: 14,
    paddingHorizontal: 48,
    borderRadius: 30,
    marginTop: 24,
    width: "100%",
    alignItems: "center",
  },

  /* ── Header ── */
  header: {
    paddingHorizontal: 20,
    paddingTop: 8,
    paddingBottom: 4,
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: "700",
    color: "#1A1A2E",
  },

  /* ── Tabs ── */
  tabsScroll: {
    flexGrow: 0,
    flexShrink: 0,
    height: 58,
    backgroundColor: "#F8F8FA",
  },
  tabsContainer: {
    paddingHorizontal: 16,
    height: 58,
    alignItems: "center",
  },
  tab: {
    flexDirection: "row",
    alignItems: "center",
    flexShrink: 0,
    minWidth: 106,
    justifyContent: "center",
    paddingHorizontal: 16,
    height: 38,
    marginRight: 8,
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
    flexShrink: 0,
    lineHeight: 17,
    textAlignVertical: "center",
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
    marginLeft: 6,
  },
  tabBadgeActive: {
    backgroundColor: "rgba(255,255,255,0.25)",
  },
  tabBadgeText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#374151",
    lineHeight: 12,
    textAlignVertical: "center",
  },

  /* ── Search ── */
  searchSection: {
    paddingHorizontal: 16,
    paddingTop: 6,
    paddingBottom: 4,
    backgroundColor: "#F8F8FA",
  },
  searchInputWrap: {
    height: 46,
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 14,
    paddingHorizontal: 14,
  },
  searchInput: {
    flex: 1,
    color: "#1A1A2E",
    fontSize: 14,
    fontWeight: "500",
    paddingVertical: 0,
  },
  searchClearBtn: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: "#C7C7CC",
    justifyContent: "center",
    alignItems: "center",
  },
  clearSearchButton: {
    marginTop: 14,
    paddingHorizontal: 18,
    paddingVertical: 9,
    borderRadius: 999,
    backgroundColor: ACCENT,
  },
  clearSearchText: {
    color: "#fff",
    fontSize: 13,
    fontWeight: "700",
  },

  /* ── List ── */
  listContent: {
    padding: 16,
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
  orderImage: {
    width: 40,
    height: 40,
    borderRadius: 12,
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
