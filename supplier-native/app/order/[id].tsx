import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
  RefreshControl,
  TextInput,
  Modal,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  New: { bg: "#FEF3C7", text: "#92400E" },
  Confirmed: { bg: "#D1FAE5", text: "#065F46" },
  Ontheway: { bg: "#DBEAFE", text: "#1E40AF" },
  OnDelivery: { bg: "#DBEAFE", text: "#1E40AF" },
  Delivered: { bg: "#D1FAE5", text: "#065F46" },
  Complete: { bg: "#D1FAE5", text: "#065F46" },
  Canceled: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

export default function OrderDetailScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id: string }>();
  const orderId = Number(params.id);
  const queryClient = useQueryClient();

  const [showTrackingModal, setShowTrackingModal] = useState(false);
  const [trackingNumber, setTrackingNumber] = useState("");
  const [cancelReason, setCancelReason] = useState("");
  const [showCancelModal, setShowCancelModal] = useState(false);

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-order", orderId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/orders/${orderId}`);
      return data?.data as {
        order: any;
        customer: { customerName: string; customerPhone: string; customerAddress: string } | null;
        line_items: any[];
        vendor_subtotal: number;
      };
    },
  });

  const acceptMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/orders/${orderId}/status`, { action: "accept" });
    },
    onSuccess: () => {
      toast.success("Order accepted");
      queryClient.invalidateQueries({ queryKey: ["vendor-order", orderId] });
      queryClient.invalidateQueries({ queryKey: ["vendor-orders"] });
    },
    onError: () => toast.error("Failed to accept order"),
  });

  const cancelMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/orders/${orderId}/status`, {
        action: "cancel",
        cancel_reason: cancelReason || "Cancelled by vendor",
      });
    },
    onSuccess: () => {
      toast.success("Order cancelled");
      setShowCancelModal(false);
      setCancelReason("");
      queryClient.invalidateQueries({ queryKey: ["vendor-order", orderId] });
      queryClient.invalidateQueries({ queryKey: ["vendor-orders"] });
    },
    onError: () => toast.error("Failed to cancel order"),
  });

  const warehouseMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/orders/${orderId}/send-to-warehouse`);
    },
    onSuccess: () => {
      toast.success("Sent to warehouse");
      queryClient.invalidateQueries({ queryKey: ["vendor-order", orderId] });
      queryClient.invalidateQueries({ queryKey: ["vendor-orders"] });
    },
    onError: () => toast.error("Failed to send to warehouse"),
  });

  const trackingMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/orders/${orderId}/tracking`, {
        tracking_number: trackingNumber,
      });
    },
    onSuccess: () => {
      toast.success("Tracking number added");
      setShowTrackingModal(false);
      setTrackingNumber("");
      queryClient.invalidateQueries({ queryKey: ["vendor-order", orderId] });
    },
    onError: () => toast.error("Failed to add tracking"),
  });

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <Header />
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      </View>
    );
  }

  if (isError || !data) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <Header />
        <View style={styles.loadingContainer}>
          <Ionicons name="alert-circle-outline" size={48} color="#d1d5db" />
          <Text style={{ color: "#9ca3af", marginTop: 8 }}>Failed to load order</Text>
        </View>
      </View>
    );
  }

  const order = data.order;
  const customer = data.customer;
  const lineItems = data.line_items ?? [];
  const vendorSubtotal = data.vendor_subtotal ?? 0;
  const sc = STATUS_COLORS[order.status] ?? { bg: "#F3F4F6", text: "#374151" };
  const isPending = order.status === "Pending" || order.status === "New";
  const isConfirmed = order.status === "Confirmed";
  const canSendWarehouse = isConfirmed && !order.warehouse_sent_at;

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <Header />

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
      >
        {/* Order Header */}
        <View style={styles.sectionCard}>
          <View style={styles.orderHeaderRow}>
            <View>
              <Text style={styles.invoiceId}>#{order.invoiceID}</Text>
              <Text style={styles.orderDate}>
                {order.orderDate ? new Date(order.orderDate).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }) : "—"}
              </Text>
            </View>
            <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
              <Text style={[styles.statusText, { color: sc.text }]}>{order.display_status ?? order.status}</Text>
            </View>
          </View>

          <View style={styles.amountRow}>
            <View style={styles.amountItem}>
              <Text style={styles.amountLabel}>Your Subtotal</Text>
              <Text style={styles.amountValue}>৳{vendorSubtotal.toLocaleString()}</Text>
            </View>
            <View style={styles.amountItem}>
              <Text style={styles.amountLabel}>Order Total</Text>
              <Text style={styles.amountValue}>৳{order.subTotal?.toLocaleString()}</Text>
            </View>
            <View style={styles.amountItem}>
              <Text style={styles.amountLabel}>Payment</Text>
              <Text style={styles.amountValue}>{order.Payment ?? "—"}</Text>
            </View>
          </View>

          {order.tracking_number && (
            <View style={styles.trackingRow}>
              <Ionicons name="locate-outline" size={14} color="#6b7280" />
              <Text style={styles.trackingText}>Tracking: {order.tracking_number}</Text>
            </View>
          )}
          {order.warehouse_sent_at && (
            <View style={styles.trackingRow}>
              <Ionicons name="business-outline" size={14} color="#6b7280" />
              <Text style={styles.trackingText}>
                Sent to warehouse: {new Date(order.warehouse_sent_at).toLocaleDateString()}
              </Text>
            </View>
          )}
        </View>

        {/* Actions */}
        {(isPending || canSendWarehouse || isConfirmed) && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Actions</Text>
            <View style={styles.actionGrid}>
              {isPending && (
                <>
                  <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: "#D1FAE5" }]}
                    onPress={() => Alert.alert("Accept Order", "Confirm this order?", [
                      { text: "Cancel", style: "cancel" },
                      { text: "Accept", onPress: () => acceptMutation.mutate() },
                    ])}
                  >
                    <Ionicons name="checkmark-circle" size={20} color="#059669" />
                    <Text style={[styles.actionBtnText, { color: "#059669" }]}>Accept</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                    style={[styles.actionBtn, { backgroundColor: "#FEE2E2" }]}
                    onPress={() => setShowCancelModal(true)}
                  >
                    <Ionicons name="close-circle" size={20} color="#DC2626" />
                    <Text style={[styles.actionBtnText, { color: "#DC2626" }]}>Cancel</Text>
                  </TouchableOpacity>
                </>
              )}
              {canSendWarehouse && (
                <TouchableOpacity
                  style={[styles.actionBtn, { backgroundColor: "#DBEAFE" }]}
                  onPress={() => Alert.alert("Send to Warehouse", "Send this order to warehouse?", [
                    { text: "Cancel", style: "cancel" },
                    { text: "Send", onPress: () => warehouseMutation.mutate() },
                  ])}
                >
                  <Ionicons name="business" size={20} color="#1E40AF" />
                  <Text style={[styles.actionBtnText, { color: "#1E40AF" }]}>Warehouse</Text>
                </TouchableOpacity>
              )}
              {isConfirmed && (
                <TouchableOpacity
                  style={[styles.actionBtn, { backgroundColor: BRAND.primaryLight }]}
                  onPress={() => setShowTrackingModal(true)}
                >
                  <Ionicons name="locate" size={20} color={BRAND.primary} />
                  <Text style={[styles.actionBtnText, { color: BRAND.primary }]}>Tracking</Text>
                </TouchableOpacity>
              )}
            </View>
          </View>
        )}

        {/* Customer */}
        {customer && (
          <View style={styles.sectionCard}>
            <Text style={styles.sectionTitle}>Customer</Text>
            <View style={styles.customerRow}>
              <Ionicons name="person-outline" size={16} color="#6b7280" />
              <Text style={styles.customerText}>{customer.customerName}</Text>
            </View>
            <View style={styles.customerRow}>
              <Ionicons name="call-outline" size={16} color="#6b7280" />
              <Text style={styles.customerText}>{customer.customerPhone}</Text>
            </View>
            <View style={styles.customerRow}>
              <Ionicons name="location-outline" size={16} color="#6b7280" />
              <Text style={styles.customerText}>{customer.customerAddress}</Text>
            </View>
          </View>
        )}

        {/* Line Items */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Products ({lineItems.length})</Text>
          {lineItems.map((item: any) => {
            const productImg = getImageUrl(item.product?.ViewProductImage);
            return (
              <View key={item.id} style={styles.lineItem}>
                <View style={styles.lineItemImageWrap}>
                  {productImg ? (
                    <Image source={{ uri: productImg }} style={styles.lineItemImage} />
                  ) : (
                    <View style={styles.lineItemImagePlaceholder}>
                      <Ionicons name="cube-outline" size={16} color="#d1d5db" />
                    </View>
                  )}
                </View>
                <View style={styles.lineItemInfo}>
                  <Text style={styles.lineItemName} numberOfLines={2}>
                    {item.productName ?? item.product?.ProductName ?? "Product"}
                  </Text>
                  {(item.color || item.size) && (
                    <Text style={styles.lineItemVariant}>
                      {[item.color, item.size].filter(Boolean).join(" · ")}
                    </Text>
                  )}
                  <View style={styles.lineItemPriceRow}>
                    <Text style={styles.lineItemPrice}>৳{item.productPrice}</Text>
                    <Text style={styles.lineItemQty}>× {item.quantity}</Text>
                    <Text style={styles.lineItemTotal}>৳{item.line_total?.toLocaleString()}</Text>
                  </View>
                  {item.tracking_number && (
                    <Text style={styles.lineItemTracking}>🔗 {item.tracking_number}</Text>
                  )}
                </View>
              </View>
            );
          })}
        </View>

        {/* Order Note */}
        {order.customerNote && (
          <View style={[styles.sectionCard, { backgroundColor: "#FFFBEB", borderColor: "#FEF3C7" }]}>
            <Text style={[styles.sectionTitle, { color: "#92400E" }]}>Customer Note</Text>
            <Text style={{ fontSize: 13, color: "#78350F", lineHeight: 19 }}>{order.customerNote}</Text>
          </View>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>

      {/* Cancel Modal */}
      <Modal visible={showCancelModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Cancel Order</Text>
            <Text style={styles.modalSubtitle}>Please provide a reason for cancellation</Text>
            <TextInput
              style={[styles.modalInput, { height: 80, textAlignVertical: "top" }]}
              value={cancelReason}
              onChangeText={setCancelReason}
              placeholder="Enter reason..."
              placeholderTextColor="#9ca3af"
              multiline
            />
            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setShowCancelModal(false)}>
                <Text style={styles.modalCancelText}>Back</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.modalConfirmBtn, { backgroundColor: "#DC2626" }]}
                onPress={() => cancelMutation.mutate()}
                disabled={cancelMutation.isPending}
              >
                {cancelMutation.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text style={styles.modalConfirmText}>Cancel Order</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* Tracking Modal */}
      <Modal visible={showTrackingModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Add Tracking Number</Text>
            <TextInput
              style={styles.modalInput}
              value={trackingNumber}
              onChangeText={setTrackingNumber}
              placeholder="Enter tracking number"
              placeholderTextColor="#9ca3af"
            />
            <View style={styles.modalActions}>
              <TouchableOpacity style={styles.modalCancelBtn} onPress={() => setShowTrackingModal(false)}>
                <Text style={styles.modalCancelText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={styles.modalConfirmBtn}
                onPress={() => trackingMutation.mutate()}
                disabled={trackingMutation.isPending || !trackingNumber.trim()}
              >
                {trackingMutation.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text style={styles.modalConfirmText}>Add Tracking</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

function Header() {
  return (
    <View style={styles.header}>
      <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
        <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
      </TouchableOpacity>
      <Text style={styles.headerTitle}>Order Detail</Text>
      <View style={{ width: 32 }} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 12 },
  orderHeaderRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "flex-start", marginBottom: 14 },
  invoiceId: { fontSize: 18, fontWeight: "700", color: "#1a1a2e" },
  orderDate: { fontSize: 12, color: "#9ca3af", marginTop: 2 },
  statusBadge: { paddingHorizontal: 12, paddingVertical: 5, borderRadius: 10 },
  statusText: { fontSize: 12, fontWeight: "600" },
  amountRow: { flexDirection: "row", gap: 12 },
  amountItem: { flex: 1, backgroundColor: "#f9fafb", borderRadius: 10, padding: 10, alignItems: "center" },
  amountLabel: { fontSize: 10, color: "#9ca3af", fontWeight: "500" },
  amountValue: { fontSize: 14, fontWeight: "700", color: "#1a1a2e", marginTop: 2 },
  trackingRow: { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 10 },
  trackingText: { fontSize: 12, color: "#6b7280" },
  actionGrid: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  actionBtn: {
    flex: 1,
    minWidth: "40%",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingVertical: 12,
    borderRadius: 10,
  },
  actionBtnText: { fontSize: 13, fontWeight: "600" },
  customerRow: { flexDirection: "row", alignItems: "flex-start", gap: 8, marginBottom: 8 },
  customerText: { fontSize: 13, color: "#374151", flex: 1 },
  lineItem: { flexDirection: "row", paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb", gap: 10 },
  lineItemImageWrap: { width: 52, height: 52, borderRadius: 8, overflow: "hidden", backgroundColor: "#f9fafb" },
  lineItemImage: { width: "100%", height: "100%", resizeMode: "cover" },
  lineItemImagePlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center", backgroundColor: "#f3f4f6" },
  lineItemInfo: { flex: 1 },
  lineItemName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  lineItemVariant: { fontSize: 11, color: "#9ca3af", marginTop: 2 },
  lineItemPriceRow: { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 4 },
  lineItemPrice: { fontSize: 12, color: "#6b7280" },
  lineItemQty: { fontSize: 12, color: "#9ca3af" },
  lineItemTotal: { fontSize: 13, fontWeight: "600", color: BRAND.primary },
  lineItemTracking: { fontSize: 10, color: "#6b7280", marginTop: 2 },
  // Modals
  modalOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.5)", justifyContent: "flex-end" },
  modalContent: { backgroundColor: "#fff", borderTopLeftRadius: 20, borderTopRightRadius: 20, padding: 20 },
  modalTitle: { fontSize: 17, fontWeight: "700", color: "#1a1a2e", marginBottom: 4 },
  modalSubtitle: { fontSize: 12, color: "#9ca3af", marginBottom: 16 },
  modalInput: {
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: "#1a1a2e",
    marginBottom: 16,
  },
  modalActions: { flexDirection: "row", gap: 10 },
  modalCancelBtn: { flex: 1, paddingVertical: 13, borderRadius: 10, backgroundColor: "#f3f4f6", alignItems: "center" },
  modalCancelText: { fontSize: 14, fontWeight: "600", color: "#6b7280" },
  modalConfirmBtn: { flex: 1, paddingVertical: 13, borderRadius: 10, backgroundColor: BRAND.primary, alignItems: "center" },
  modalConfirmText: { fontSize: 14, fontWeight: "600", color: "#fff" },
});
