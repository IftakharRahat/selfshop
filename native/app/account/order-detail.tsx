import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Alert,
  Linking,
  Image,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

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

const STATUS_COLORS: Record<string, { color: string; bg: string }> = {
  Pending: { color: "#92400E", bg: "#FEF3C7" },
  Accepted: { color: "#065F46", bg: "#D1FAE5" },
  Confirmed: { color: "#1E40AF", bg: "#DBEAFE" },
  Processing: { color: "#3730A3", bg: "#E0E7FF" },
  Ontheway: { color: "#155E75", bg: "#CFFAFE" },
  "On the way": { color: "#155E75", bg: "#CFFAFE" },
  "Shipped to warehouse": { color: "#1E40AF", bg: "#DBEAFE" },
  Shipped: { color: "#1E40AF", bg: "#DBEAFE" },
  Delivered: { color: "#065F46", bg: "#D1FAE5" },
  Canceled: { color: "#991B1B", bg: "#FEE2E2" },
  Cancelled: { color: "#991B1B", bg: "#FEE2E2" },
  Rejected: { color: "#991B1B", bg: "#FEE2E2" },
};

function formatBDT(v: number | string | undefined | null): string {
  const n = parseFloat(String(v ?? 0)) || 0;
  return `৳${n.toLocaleString("en-BD")}`;
}

export default function OrderDetailScreen() {
  const params = useLocalSearchParams<{ invoiceID?: string; id?: string }>();
  const invoiceID = (params.invoiceID ?? "").trim().replace(/^[^A-Za-z0-9]+/, "");
  const orderId = params.id ?? "";
  const queryClient = useQueryClient();

  /* ── Fetch order using track-order (same as web) ── */
  const orderQuery = useQuery({
    queryKey: ["order-detail", invoiceID, orderId],
    queryFn: async () => {
      const searchParams = new URLSearchParams();
      if (invoiceID) searchParams.set("invoiceID", invoiceID);
      if (orderId) searchParams.set("id", orderId);
      const res = await apiClient.get(`/track-order?${searchParams.toString()}`);
      const apiResponse = res.data;
      return apiResponse?.data ?? apiResponse;
    },
    enabled: !!invoiceID || !!orderId,
  });

  const cancelMutation = useMutation({
    mutationFn: (id: number) => apiClient.post(`/orders/${id}/cancel`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      queryClient.invalidateQueries({ queryKey: ["order-detail"] });
      Alert.alert("Success", "Order cancelled successfully");
    },
    onError: (err: any) => {
      Alert.alert("Error", err?.response?.data?.message || "Failed to cancel order");
    },
  });

  const order = orderQuery.data;

  if (orderQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Order Detail", headerShadowVisible: false }} />
        <View style={styles.center}><ActivityIndicator size="large" color={ACCENT} /></View>
      </>
    );
  }

  if (!order) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Order Detail", headerShadowVisible: false }} />
        <View style={styles.center}>
          <Ionicons name="receipt-outline" size={48} color="#D1D5DB" />
          <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">Order not found</Text>
          <Pressable onPress={() => router.back()} style={{ marginTop: 16 }}>
            <Text fontSize="$3" fontWeight="600" color={ACCENT}>← Go Back</Text>
          </Pressable>
        </View>
      </>
    );
  }

  /* ── Read fields matching web API response ── */
  const displayStatus = order.customer_status ?? order.display_status ?? order.status ?? "Pending";
  const statusStyle = STATUS_COLORS[displayStatus] ?? STATUS_COLORS.Pending;
  const customer = order.customers ?? {};
  const courier = order.couriers ?? {};
  const orderProducts: any[] = order.orderproducts ?? order.order_products ?? order.products ?? order.items ?? order.order_items ?? [];
  const subTotal = parseFloat(order.subTotal) || 0;
  const profit = parseFloat(order.profit) || 0;
  const deliveryCharge = parseFloat(order.deliveryCharge) || 0;
  const total = subTotal + deliveryCharge;
  const canCancel = displayStatus.toLowerCase() === "pending";

  function handleCancel() {
    Alert.alert("Cancel Order", "Are you sure you want to cancel this order?", [
      { text: "No", style: "cancel" },
      { text: "Yes, Cancel", style: "destructive", onPress: () => cancelMutation.mutate(order.id) },
    ]);
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: order.invoiceID ?? "Order Detail",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Status & Invoice Header */}
        <View style={styles.headerCard}>
          <View style={styles.headerTop}>
            <View style={{ flex: 1 }}>
              <Text style={styles.invoiceText}>{order.invoiceID}</Text>
              <Text style={styles.dateText}>{order.orderDate}</Text>
            </View>
            <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
              <Text style={[styles.statusText, { color: statusStyle.color }]}>{displayStatus}</Text>
            </View>
          </View>
        </View>

        {/* Tracking Info (if available) */}
        {(order.tracking_number || order.carrybee_tracking_code) && (
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Tracking & Shipment</Text>
            {order.tracking_number && (
              <View style={styles.infoRow}>
                <Ionicons name="barcode-outline" size={16} color="#6B7280" />
                <View style={{ flex: 1 }}>
                  <Text style={styles.infoLabel}>Tracking Number</Text>
                  <Text style={styles.infoValue}>{order.tracking_number}</Text>
                </View>
              </View>
            )}
            {order.carrybee_tracking_code && (
              <Pressable
                style={styles.trackButton}
                onPress={() => Linking.openURL(
                  order.trackingLink || `https://merchant.carrybee.com/order-track/${order.carrybee_tracking_code}`
                )}
              >
                <Ionicons name="navigate-outline" size={14} color="#fff" />
                <Text style={{ fontSize: 12, fontWeight: "700", color: "#fff" }}>Track Delivery</Text>
              </Pressable>
            )}
          </View>
        )}

        {/* Customer Info */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Customer Information</Text>
          {customer.customerName && (
            <View style={styles.infoRow}>
              <Ionicons name="person-outline" size={16} color="#6B7280" />
              <Text style={styles.infoValue}>{customer.customerName}</Text>
            </View>
          )}
          {customer.customerPhone && (
            <Pressable style={styles.infoRow} onPress={() => Linking.openURL(`tel:${customer.customerPhone}`)}>
              <Ionicons name="call-outline" size={16} color="#6B7280" />
              <Text style={[styles.infoValue, { color: ACCENT }]}>{customer.customerPhone}</Text>
            </Pressable>
          )}
          {customer.customerAddress && (
            <View style={styles.infoRow}>
              <Ionicons name="location-outline" size={16} color="#6B7280" />
              <Text style={[styles.infoValue, { flex: 1 }]}>{customer.customerAddress}</Text>
            </View>
          )}
          {courier.courierName && (
            <View style={styles.infoRow}>
              <Ionicons name="bus-outline" size={16} color="#6B7280" />
              <Text style={styles.infoValue}>Courier: {courier.courierName}</Text>
            </View>
          )}
        </View>

        {/* Products */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Products ({orderProducts.length})</Text>
          {orderProducts.map((item: any, idx: number) => {
            const costPrice = parseFloat(item.productPrice) || 0;
            const qty = parseInt(item.quantity) || 1;
            const itemTotal = costPrice * qty;
            return (
              <View key={item.id ?? idx} style={styles.productRow}>
                {(() => {
                  const imgUri = resolveImageUrl(item.product?.ViewProductImage);
                  return imgUri ? (
                    <Image
                      source={{ uri: imgUri }}
                      style={styles.productImage}
                      resizeMode="cover"
                    />
                  ) : (
                    <View style={styles.productImagePlaceholder}>
                      <Ionicons name="cube-outline" size={16} color="#9CA3AF" />
                    </View>
                  );
                })()}
                <View style={{ flex: 1 }}>
                  <Text style={styles.productName} numberOfLines={2}>
                    {item.productName ?? item.ProductName ?? "Product"}
                  </Text>
                  <Text style={styles.productMeta}>
                    ৳{costPrice.toLocaleString("en-BD")} × {qty}
                  </Text>
                  {item.fulfillment_status && item.fulfillment_status !== "pending" && (
                    <View style={[styles.miniPill, {
                      backgroundColor: item.fulfillment_status === "delivered" ? "#D1FAE5" : "#DBEAFE"
                    }]}>
                      <Text style={{
                        fontSize: 10, fontWeight: "600",
                        color: item.fulfillment_status === "delivered" ? "#065F46" : "#1E40AF"
                      }}>
                        {item.fulfillment_status.charAt(0).toUpperCase() + item.fulfillment_status.slice(1)}
                      </Text>
                    </View>
                  )}
                </View>
                <Text style={styles.productTotal}>{formatBDT(itemTotal)}</Text>
              </View>
            );
          })}
          {orderProducts.length === 0 && (
            <Text style={{ fontSize: 13, color: "#9CA3AF", textAlign: "center", paddingVertical: 12 }}>
              No products found
            </Text>
          )}
        </View>

        {/* Pricing Summary */}
        <View style={styles.section}>
          <View style={styles.priceRow}>
            <Text style={styles.priceLabel}>Resell Price</Text>
            <Text style={styles.priceValue}>{formatBDT(subTotal)}</Text>
          </View>
          <View style={styles.priceRow}>
            <Text style={styles.priceLabel}>Seller Profit</Text>
            <Text style={[styles.priceValue, { color: "#059669" }]}>{formatBDT(profit)}</Text>
          </View>
          {deliveryCharge > 0 && (
            <View style={styles.priceRow}>
              <Text style={styles.priceLabel}>Delivery Charge</Text>
              <Text style={styles.priceValue}>{formatBDT(deliveryCharge)}</Text>
            </View>
          )}
          <View style={[styles.priceRow, styles.totalRow]}>
            <Text style={styles.totalLabel}>Total</Text>
            <Text style={styles.totalValue}>{formatBDT(total)}</Text>
          </View>
        </View>

        {/* Cancel Button */}
        {canCancel && (
          <View style={{ paddingHorizontal: 16, paddingBottom: 16 }}>
            <Pressable
              style={({ pressed }) => [styles.cancelButton, pressed && { opacity: 0.8 }]}
              onPress={handleCancel}
              disabled={cancelMutation.isPending}
            >
              {cancelMutation.isPending ? (
                <ActivityIndicator color="#DC2626" />
              ) : (
                <>
                  <Ionicons name="close-circle-outline" size={18} color="#DC2626" />
                  <Text style={{ fontSize: 14, fontWeight: "600", color: "#DC2626" }}>Cancel Order</Text>
                </>
              )}
            </Pressable>
          </View>
        )}

        <View style={{ height: 30 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  center: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F8F8FA", paddingBottom: 60 },

  headerCard: {
    margin: 16, marginBottom: 0, backgroundColor: "#fff", borderRadius: 16,
    padding: 16, borderWidth: 1, borderColor: "#F0F0F5",
  },
  headerTop: { flexDirection: "row", alignItems: "flex-start", justifyContent: "space-between" },
  invoiceText: { fontSize: 16, fontWeight: "800", color: "#1A1A2E" },
  dateText: { fontSize: 12, color: "#9CA3AF", marginTop: 4 },
  statusPill: { paddingHorizontal: 12, paddingVertical: 5, borderRadius: 14 },
  statusText: { fontSize: 12, fontWeight: "700" },

  section: {
    margin: 16, marginBottom: 0, backgroundColor: "#fff", borderRadius: 16,
    padding: 16, borderWidth: 1, borderColor: "#F0F0F5",
  },
  sectionTitle: { fontSize: 13, fontWeight: "700", color: "#6B7280", textTransform: "uppercase", letterSpacing: 0.5, marginBottom: 10 },

  infoRow: { flexDirection: "row", alignItems: "center", gap: 10, paddingVertical: 5 },
  infoLabel: { fontSize: 10, color: "#9CA3AF" },
  infoValue: { fontSize: 13, fontWeight: "500", color: "#1A1A2E" },

  trackButton: {
    flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6,
    backgroundColor: "#2D2A5D", borderRadius: 8, paddingVertical: 8, marginTop: 8,
  },

  productRow: {
    flexDirection: "row", alignItems: "flex-start", gap: 10,
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#F5F5FA",
  },
  productImage: {
    width: 36, height: 36, borderRadius: 8, marginTop: 2,
  },
  productImagePlaceholder: {
    width: 36, height: 36, borderRadius: 8, backgroundColor: "#F5F5FA",
    justifyContent: "center", alignItems: "center", marginTop: 2,
  },
  productName: { fontSize: 13, fontWeight: "600", color: "#1A1A2E" },
  productMeta: { fontSize: 11, color: "#9CA3AF", marginTop: 2 },
  productTotal: { fontSize: 14, fontWeight: "700", color: "#1A1A2E" },
  miniPill: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4, marginTop: 4, alignSelf: "flex-start" },

  priceRow: { flexDirection: "row", justifyContent: "space-between", paddingVertical: 5 },
  priceLabel: { fontSize: 13, color: "#6B7280" },
  priceValue: { fontSize: 13, fontWeight: "600", color: "#1A1A2E" },
  totalRow: { borderTopWidth: 1, borderTopColor: "#F0F0F5", paddingTop: 10, marginTop: 6 },
  totalLabel: { fontSize: 15, fontWeight: "800", color: "#1A1A2E" },
  totalValue: { fontSize: 15, fontWeight: "800", color: ACCENT },

  cancelButton: {
    flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6,
    paddingVertical: 14, borderRadius: 14, borderWidth: 1, borderColor: "#FECACA", backgroundColor: "#FEF2F2",
  },
});
