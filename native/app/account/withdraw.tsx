import { useCallback, useEffect, useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Dimensions,
  Image,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";

import apiClient from "@/lib/api-client";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";

/* ── Image URL helper (same as home screen) ── */
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

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
  Completed: { bg: "#D1FAE5", text: "#065F46" },
  Approved: { bg: "#D1FAE5", text: "#065F46" },
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

export default function WithdrawScreen() {
  const queryClient = useQueryClient();

  /* ── State ── */
  const [amount, setAmount] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [additionalInfo, setAdditionalInfo] = useState("");
  const [selectedMethodId, setSelectedMethodId] = useState<number | null>(null);

  /* ── Queries ── */
  const dashboardQuery = useQuery({
    queryKey: ["dashboard-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/dashboard-data");
      return data?.data ?? data;
    },
    staleTime: 2 * 60 * 1000,
  });

  const methodsQuery = useQuery({
    queryKey: ["withdraw-methods"],
    queryFn: async () => {
      const { data } = await apiClient.get("/get-payment-types");
      return data?.data ?? data ?? [];
    },
  });

  const historyQuery = useQuery({
    queryKey: ["withdraw-list"],
    queryFn: async () => {
      const { data } = await apiClient.get("/withdraw-list");
      return data?.data ?? data ?? [];
    },
  });

  /* ── Mutation ── */
  const withdrawMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await apiClient.post("/give-withdraw-request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      Alert.alert("Success", "Withdrawal request submitted successfully.");
      setAmount("");
      setAccountNumber("");
      setAdditionalInfo("");
      queryClient.invalidateQueries({ queryKey: ["withdraw-list"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      Alert.alert("Error", err?.response?.data?.message ?? "Failed to submit withdrawal request.");
    },
  });

  /* ── Derived ── */
  const methods: any[] = methodsQuery.data ?? [];
  const history: any[] = historyQuery.data ?? [];
  const walletBalance = Number(dashboardQuery.data?.balance ?? dashboardQuery.data?.blance ?? 0);
  const lastWithdraw = history.length > 0
    ? Number(history[0]?.withdrew_amount ?? 0)
    : Number(dashboardQuery.data?.withdraw ?? 0);

  // Auto-select first method
  useEffect(() => {
    if (selectedMethodId === null && methods.length > 0) {
      setSelectedMethodId(methods[0].id);
    }
  }, [methods, selectedMethodId]);

  const selectedMethodName = methods.find((m: any) => m.id === selectedMethodId)?.paymentTypeName ?? "";

  /* ── Submit ── */
  const handleSubmit = () => {
    if (!amount.trim() || isNaN(Number(amount)) || Number(amount) <= 0) {
      Alert.alert("Invalid", "Please enter a valid amount.");
      return;
    }
    if (!selectedMethodId) {
      Alert.alert("Invalid", "Please select a payment method.");
      return;
    }
    if (!accountNumber.trim() || accountNumber.trim().length < 6) {
      Alert.alert("Invalid", "Account number must be at least 6 characters.");
      return;
    }

    const formData = new FormData();
    formData.append("withdrew_amount", amount.trim());
    formData.append("paymenttype_id", String(selectedMethodId));
    formData.append("to_account_number", accountNumber.trim());
    if (additionalInfo.trim()) {
      formData.append("to_additional_info", additionalInfo.trim());
    }
    withdrawMutation.mutate(formData);
  };

  /* ── Refresh ── */
  const isRefreshing = dashboardQuery.isRefetching || historyQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    queryClient.invalidateQueries({ queryKey: ["withdraw-list"] });
    queryClient.invalidateQueries({ queryKey: ["withdraw-methods"] });
  }, [queryClient]);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Withdraw",
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
          refreshControl={
            <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />
          }
          keyboardShouldPersistTaps="handled"
        >
          {/* ── Balance Card ── */}
          <LinearGradient
            colors={["#E5005F", "#B8004C"]}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
            style={styles.balanceCard}
          >
            <View>
              <Text fontSize="$2" color="rgba(255,255,255,0.7)">
                Your Total Balance
              </Text>
              <Text fontSize="$8" fontWeight="bold" color="#fff" mt="$1">
                {dashboardQuery.isLoading ? "..." : formatCurrency(walletBalance)}
              </Text>
              <Text fontSize="$2" color="rgba(255,255,255,0.6)" mt="$1">
                Last Withdraw: {dashboardQuery.isLoading ? "..." : formatCurrency(lastWithdraw)}
              </Text>
            </View>
            <Ionicons name="wallet" size={40} color="rgba(255,255,255,0.2)" />
          </LinearGradient>

          {/* ── Withdraw Form ── */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Request Withdrawal</Text>

            {/* Amount */}
            <Text style={styles.inputLabel}>Amount</Text>
            <View style={styles.amountRow}>
              <View style={styles.currencyTag}>
                <Text style={styles.currencyText}>৳</Text>
              </View>
              <TextInput
                style={styles.amountInput}
                placeholder="Enter amount"
                placeholderTextColor="#9CA3AF"
                keyboardType="numeric"
                value={amount}
                onChangeText={setAmount}
              />
            </View>

            {/* Payment Methods */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Payment Method</Text>
            {methodsQuery.isLoading ? (
              <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 12 }} />
            ) : (
              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.methodsRow}
              >
                {methods.map((method: any) => {
                  const isSelected = method.id === selectedMethodId;
                  return (
                    <Pressable
                      key={method.id}
                      style={[
                        styles.methodChip,
                        isSelected && styles.methodChipActive,
                      ]}
                      onPress={() => setSelectedMethodId(method.id)}
                    >
                    {(() => {
                        const iconUri = resolveImageUrl(method.icon);
                        return iconUri ? (
                          <Image
                            source={{ uri: iconUri }}
                            style={styles.methodIcon}
                            resizeMode="contain"
                          />
                        ) : null;
                      })()}
                      <Text
                        style={[
                          styles.methodName,
                          isSelected && { color: ACCENT },
                        ]}
                        numberOfLines={1}
                      >
                        {method.paymentTypeName}
                      </Text>
                    </Pressable>
                  );
                })}
              </ScrollView>
            )}

            {/* Account Number */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Account Number</Text>
            <View style={styles.amountRow}>
              <View style={styles.currencyTag}>
                <Text style={[styles.currencyText, { fontSize: 11 }]}>{selectedMethodName || "—"}</Text>
              </View>
              <TextInput
                style={styles.amountInput}
                placeholder="Enter account number"
                placeholderTextColor="#9CA3AF"
                value={accountNumber}
                onChangeText={setAccountNumber}
              />
            </View>

            {/* Additional Info */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Additional Info (optional)</Text>
            <TextInput
              style={styles.textArea}
              placeholder="Any additional details..."
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={3}
              textAlignVertical="top"
              value={additionalInfo}
              onChangeText={setAdditionalInfo}
            />

            {/* Submit */}
            <Pressable
              style={({ pressed }) => [
                styles.submitButton,
                pressed && { opacity: 0.85 },
                withdrawMutation.isPending && { opacity: 0.6 },
              ]}
              onPress={handleSubmit}
              disabled={withdrawMutation.isPending}
            >
              {withdrawMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Text fontSize="$4" fontWeight="bold" color="#fff">
                  Request Withdrawal
                </Text>
              )}
            </Pressable>
          </View>

          {/* ── History ── */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Withdrawal History</Text>

            {historyQuery.isLoading ? (
              <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 20 }} />
            ) : history.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="receipt-outline" size={40} color="#D1D5DB" />
                <Text fontSize="$3" color="#9CA3AF" mt="$2">
                  No withdrawal history yet
                </Text>
              </View>
            ) : (
              history.map((item: any) => {
                const statusStyle = STATUS_STYLES[item.status] ?? STATUS_STYLES.Pending;
                return (
                  <View key={item.id} style={styles.historyCard}>
                    <View style={styles.historyTop}>
                      <Text style={styles.historyId}>#{item.id}</Text>
                      <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
                        <Text style={[styles.statusText, { color: statusStyle.text }]}>
                          {item.status}
                        </Text>
                      </View>
                    </View>
                    <Text style={styles.historyAmount}>{formatCurrency(item.withdrew_amount)}</Text>
                    <View style={styles.historyBottom}>
                      <Text style={styles.historyMeta}>{item.paymenttype_name}</Text>
                      <Text style={styles.historyMeta}>
                        {item.created_at ? new Date(item.created_at).toLocaleDateString() : ""}
                      </Text>
                    </View>
                  </View>
                );
              })
            )}
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  balanceCard: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    margin: 16,
    padding: 20,
    borderRadius: 20,
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 14,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 6,
  },
  amountRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  currencyTag: {
    height: 48,
    minWidth: 48,
    paddingHorizontal: 12,
    backgroundColor: "#F0F0F5",
    borderRadius: 12,
    justifyContent: "center",
    alignItems: "center",
  },
  currencyText: {
    fontSize: 16,
    fontWeight: "700",
    color: "#374151",
  },
  amountInput: {
    flex: 1,
    height: 48,
    backgroundColor: "#fff",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 14,
    fontSize: 15,
    color: "#1A1A2E",
  },
  methodsRow: {
    gap: 10,
    paddingVertical: 4,
  },
  methodChip: {
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    backgroundColor: "#fff",
    minWidth: 80,
  },
  methodChipActive: {
    borderColor: ACCENT,
    backgroundColor: "#FDF2F8",
  },
  methodIcon: {
    width: 28,
    height: 28,
  },
  methodName: {
    fontSize: 12,
    fontWeight: "600",
    color: "#374151",
    textAlign: "center",
  },
  textArea: {
    backgroundColor: "#fff",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 15,
    color: "#1A1A2E",
    minHeight: 80,
  },
  submitButton: {
    backgroundColor: ACCENT,
    borderRadius: 14,
    height: 50,
    justifyContent: "center",
    alignItems: "center",
    marginTop: 20,
  },
  emptyState: {
    alignItems: "center",
    paddingVertical: 32,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  historyCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  historyTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  historyId: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "500",
  },
  statusPill: {
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 11,
    fontWeight: "600",
  },
  historyAmount: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 6,
  },
  historyBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  historyMeta: {
    fontSize: 12,
    color: "#9CA3AF",
  },
});
