import { useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  TextInput,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

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

export default function TrackOrderScreen() {
  const [invoiceId, setInvoiceId] = useState("");
  const [searchId, setSearchId] = useState("");

  const trackQuery = useQuery({
    queryKey: ["track-order", searchId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/track-order?invoiceID=${searchId}`);
      return data?.data ?? data;
    },
    enabled: !!searchId,
    retry: false,
  });

  const handleSearch = () => {
    const trimmed = invoiceId.trim();
    if (!trimmed) return;
    setSearchId(trimmed);
  };

  const order = trackQuery.data;
  const displayStatus = order?.customer_status ?? order?.display_status ?? order?.status ?? "";
  const statusStyle = STATUS_COLORS[displayStatus] ?? STATUS_COLORS.Pending;

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Track Order",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === "ios" ? "padding" : undefined}
      >
        <ScrollView
          style={styles.container}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >
          {/* ── Search ── */}
          <View style={styles.searchSection}>
            <View style={styles.searchIcon}>
              <Ionicons name="search-outline" size={28} color={ACCENT} />
            </View>
            <Text style={styles.searchTitle}>Track Your Order</Text>
            <Text style={styles.searchSubtitle}>
              Enter your invoice ID to get real-time order status
            </Text>

            <TextInput
              style={styles.searchInput}
              placeholder="Enter Invoice ID (e.g. INV-001)"
              placeholderTextColor="#9CA3AF"
              value={invoiceId}
              onChangeText={setInvoiceId}
              onSubmitEditing={handleSearch}
              returnKeyType="search"
              autoCapitalize="characters"
            />

            <Pressable
              style={({ pressed }) => [
                styles.searchButton,
                pressed && { opacity: 0.85 },
              ]}
              onPress={handleSearch}
            >
              <Ionicons name="search" size={18} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff" ml="$1.5">
                Search Now
              </Text>
            </Pressable>
          </View>

          {/* ── Loading ── */}
          {trackQuery.isFetching && (
            <View style={styles.loadingBox}>
              <ActivityIndicator size="large" color={ACCENT} />
              <Text fontSize="$3" color="#6B7280" mt="$2">Searching...</Text>
            </View>
          )}

          {/* ── Error ── */}
          {trackQuery.isError && !trackQuery.isFetching && (
            <View style={styles.errorBox}>
              <Ionicons name="alert-circle-outline" size={28} color="#DC2626" />
              <Text fontSize="$3" color="#DC2626" fontWeight="600" mt="$1">
                No Records Found
              </Text>
              <Text fontSize="$2" color="#6B7280" mt="$0.5" style={{ textAlign: "center" }}>
                Please check the invoice ID and try again
              </Text>
            </View>
          )}

          {/* ── Result ── */}
          {order && !trackQuery.isFetching && !trackQuery.isError && (
            <View style={styles.resultSection}>
              <View style={styles.resultCard}>
                {/* Header */}
                <View style={styles.resultHeader}>
                  <View>
                    <Text style={styles.resultInvoice}>{order.invoiceID}</Text>
                    <Text style={styles.resultDate}>{order.orderDate}</Text>
                  </View>
                  <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
                    <Text style={[styles.statusText, { color: statusStyle.text }]}>
                      {displayStatus}
                    </Text>
                  </View>
                </View>

                {/* Divider */}
                <View style={styles.divider} />

                {/* Details */}
                <DetailRow icon="person-outline" label="Customer" value={order.customers?.customerName ?? "—"} />
                <DetailRow icon="call-outline" label="Phone" value={order.customers?.customerPhone ?? "—"} />
                <DetailRow icon="location-outline" label="Address" value={order.customers?.customerAddress ?? "—"} />
                <DetailRow icon="cash-outline" label="Total" value={formatCurrency(order.total)} />
                <DetailRow icon="card-outline" label="Payment" value={order.payment_method ?? "—"} />

                {/* Products */}
                {order.products && order.products.length > 0 && (
                  <>
                    <View style={styles.divider} />
                    <Text style={styles.productsTitle}>Products</Text>
                    {order.products.map((p: any, i: number) => (
                      <View key={i} style={styles.productRow}>
                        <Text style={styles.productName} numberOfLines={1}>
                          {p.productName ?? p.name ?? "Product"}
                        </Text>
                        <Text style={styles.productQty}>×{p.quantity ?? 1}</Text>
                        <Text style={styles.productPrice}>{formatCurrency(p.sellingPrice ?? p.price)}</Text>
                      </View>
                    ))}
                  </>
                )}
              </View>
            </View>
          )}

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </>
  );
}

function DetailRow({ icon, label, value }: { icon: string; label: string; value: string }) {
  return (
    <View style={styles.detailRow}>
      <Ionicons name={icon as any} size={16} color="#9CA3AF" />
      <Text style={styles.detailLabel}>{label}</Text>
      <Text style={styles.detailValue} numberOfLines={2}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  searchSection: {
    margin: 16,
    padding: 24,
    backgroundColor: "#fff",
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    alignItems: "center",
  },
  searchIcon: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 14,
  },
  searchTitle: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 4,
  },
  searchSubtitle: {
    fontSize: 13,
    color: "#6B7280",
    textAlign: "center",
    marginBottom: 20,
  },
  searchInput: {
    width: "100%",
    height: 50,
    backgroundColor: "#F8F8FA",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 16,
    fontSize: 15,
    color: "#1A1A2E",
  },
  searchButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    width: "100%",
    height: 50,
    backgroundColor: ACCENT,
    borderRadius: 14,
    marginTop: 12,
  },
  loadingBox: {
    alignItems: "center",
    paddingVertical: 40,
  },
  errorBox: {
    margin: 16,
    padding: 24,
    backgroundColor: "#FEF2F2",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#FECACA",
    alignItems: "center",
  },
  resultSection: {
    paddingHorizontal: 16,
  },
  resultCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  resultHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
  },
  resultInvoice: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  resultDate: {
    fontSize: 12,
    color: "#9CA3AF",
    marginTop: 2,
  },
  statusPill: {
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 12,
    fontWeight: "600",
  },
  divider: {
    height: 1,
    backgroundColor: "#F0F0F5",
    marginVertical: 14,
  },
  detailRow: {
    flexDirection: "row",
    alignItems: "flex-start",
    marginBottom: 10,
    gap: 8,
  },
  detailLabel: {
    fontSize: 13,
    color: "#6B7280",
    width: 70,
  },
  detailValue: {
    fontSize: 13,
    fontWeight: "600",
    color: "#1A1A2E",
    flex: 1,
  },
  productsTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 10,
  },
  productRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
  },
  productName: {
    flex: 1,
    fontSize: 13,
    color: "#374151",
  },
  productQty: {
    fontSize: 13,
    color: "#6B7280",
    marginHorizontal: 8,
  },
  productPrice: {
    fontSize: 13,
    fontWeight: "600",
    color: "#1A1A2E",
  },
});
