import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Image,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Stack, router } from "expo-router";
import { LinearGradient } from "expo-linear-gradient";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Text } from "tamagui";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";
import { formatPaymentMethodName, rawPaymentMethodName } from "@/lib/payment-method-name";

const ACCENT = "#E5005F";
const TAKA = "\u09F3";
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
  Completed: { bg: "#D1FAE5", text: "#065F46" },
  Approved: { bg: "#D1FAE5", text: "#065F46" },
  Paid: { bg: "#D1FAE5", text: "#065F46" },
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

function resolveImageUrl(path?: unknown): string | null {
  if (typeof path !== "string") return null;
  if (path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  const safeNum = Number.isFinite(num) ? num : 0;
  return `${TAKA}${safeNum.toLocaleString("en-BD")}`;
}

function isWalletMethod(method: any): boolean {
  return rawPaymentMethodName(method)
    .trim()
    .toLowerCase()
    .includes("wallet");
}

function savedAccountInfo(account: any): string {
  if (!account) return "";
  if (String(account.channel_type ?? "").toLowerCase() === "bank") {
    return [
      account.bank_name ? `Bank: ${account.bank_name}` : null,
      account.branch_name ? `Branch: ${account.branch_name}` : null,
      account.account_name ? `Account: ${account.account_name}` : null,
      account.routing_number ? `Routing: ${account.routing_number}` : null,
    ].filter(Boolean).join(" | ");
  }
  return account.account_name ? `Account: ${account.account_name}` : "";
}

export default function WithdrawScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();

  const [amount, setAmount] = useState("");
  const [selectedMethodId, setSelectedMethodId] = useState<number | null>(null);

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

  const accountsQuery = useQuery({
    queryKey: ["user-payout-accounts"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-payout-accounts");
      return data?.data?.payout_accounts ?? data?.data ?? [];
    },
  });

  const historyQuery = useQuery({
    queryKey: ["withdraw-list"],
    queryFn: async () => {
      const { data } = await apiClient.get("/withdraw-list");
      return data?.data ?? data ?? [];
    },
  });

  const rawMethods: any[] = Array.isArray(methodsQuery.data) ? methodsQuery.data : [];
  const methods = useMemo(() => rawMethods.filter((method) => !isWalletMethod(method)), [rawMethods]);
  const savedAccounts: any[] = Array.isArray(accountsQuery.data) ? accountsQuery.data : [];
  const accountByMethod = useMemo(() => {
    const map = new Map<number, any>();
    savedAccounts.forEach((account) => {
      if (account.paymenttype_id) map.set(Number(account.paymenttype_id), account);
    });
    methods.forEach((method) => {
      if (method.saved_account) map.set(Number(method.id), method.saved_account);
    });
    return map;
  }, [savedAccounts, methods]);

  const selectedMethod = methods.find((method: any) => method.id === selectedMethodId);
  const selectedMethodName = formatPaymentMethodName(selectedMethod);
  const selectedAccount = selectedMethodId ? accountByMethod.get(Number(selectedMethodId)) : null;
  const selectedAccountInfo = savedAccountInfo(selectedAccount);
  const history: any[] = Array.isArray(historyQuery.data) ? historyQuery.data : [];
  const walletBalance = Number(dashboardQuery.data?.balance ?? dashboardQuery.data?.blance ?? 0);
  const lastWithdraw = history.length > 0
    ? Number(history[0]?.withdrew_amount ?? 0)
    : Number(dashboardQuery.data?.withdraw ?? 0);

  useEffect(() => {
    const selectedMethodIsValid = methods.some((method: any) => method.id === selectedMethodId);
    if (!selectedMethodIsValid && methods.length > 0) {
      setSelectedMethodId(methods[0].id);
    }
  }, [methods, selectedMethodId]);

  const withdrawMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await apiClient.post("/give-withdraw-request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      showDialog({
        tone: "success",
        title: "Request submitted",
        message: "Your withdrawal request has been sent for review.",
      });
      setAmount("");
      queryClient.invalidateQueries({ queryKey: ["withdraw-list"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      showDialog({
        tone: "error",
        title: "Withdrawal failed",
        message: err?.response?.data?.message ?? "Failed to submit withdrawal request.",
      });
    },
  });

  const handleSubmit = () => {
    if (!amount.trim() || isNaN(Number(amount)) || Number(amount) <= 0) {
      showDialog({ tone: "warning", title: "Check amount", message: "Please enter a valid withdrawal amount." });
      return;
    }
    if (!selectedMethodId) {
      showDialog({ tone: "warning", title: "Select method", message: "Please choose where you want to receive the withdrawal." });
      return;
    }
    if (!selectedAccount?.id) {
      showDialog({
        tone: "warning",
        title: "Payment account needed",
        message: "Please add this payment method in Payment Methods before withdrawing.",
        actions: [
          { label: "Add Now", onPress: () => router.push("/account/payment-methods" as any) },
          { label: "Cancel", tone: "neutral" },
        ],
      });
      return;
    }

    const formData = new FormData();
    formData.append("withdrew_amount", amount.trim());
    formData.append("paymenttype_id", String(selectedMethodId));
    formData.append("user_payout_account_id", String(selectedAccount.id));
    withdrawMutation.mutate(formData);
  };

  const isRefreshing =
    dashboardQuery.isRefetching ||
    historyQuery.isRefetching ||
    methodsQuery.isRefetching ||
    accountsQuery.isRefetching;
  const bottomInset = Math.max(insets.bottom, 16);
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    queryClient.invalidateQueries({ queryKey: ["withdraw-list"] });
    queryClient.invalidateQueries({ queryKey: ["withdraw-methods"] });
    queryClient.invalidateQueries({ queryKey: ["user-payout-accounts"] });
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
      <KeyboardAwareScrollView
        style={styles.container}
        contentContainerStyle={{ paddingBottom: bottomInset + 48 }}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />}
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        bottomOffset={bottomInset + 24}
      >
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

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Request Withdrawal</Text>

          <Text style={styles.inputLabel}>Amount</Text>
          <View style={styles.amountRow}>
            <View style={styles.currencyTag}>
              <Text style={styles.currencyText}>{TAKA}</Text>
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

          <Text style={[styles.inputLabel, { marginTop: 16 }]}>Payment Method</Text>
          {methodsQuery.isLoading || accountsQuery.isLoading ? (
            <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 12 }} />
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.methodsRow}>
              {methods.map((method: any) => {
                const isSelected = method.id === selectedMethodId;
                const iconUri = resolveImageUrl(method.icon);
                const name = rawPaymentMethodName(method).toLowerCase();
                const fallbackIcon = name.includes("bank") ? "business-outline" : "card-outline";
                const hasSavedAccount = !!accountByMethod.get(Number(method.id))?.account_number;

                return (
                  <Pressable
                    key={method.id}
                    style={[styles.methodChip, isSelected && styles.methodChipActive]}
                    onPress={() => setSelectedMethodId(method.id)}
                  >
                    {iconUri ? (
                      <Image source={{ uri: iconUri }} style={styles.methodIcon} resizeMode="contain" />
                    ) : (
                      <Ionicons name={fallbackIcon as any} size={24} color={isSelected ? ACCENT : "#6B7280"} />
                    )}
                    <Text style={[styles.methodName, isSelected && { color: ACCENT }]} numberOfLines={1}>
                      {formatPaymentMethodName(method)}
                    </Text>
                    <View style={[styles.savedDot, hasSavedAccount && styles.savedDotActive]} />
                  </Pressable>
                );
              })}
            </ScrollView>
          )}

          <Text style={[styles.inputLabel, { marginTop: 16 }]}>Saved Account</Text>
          {selectedAccount ? (
            <View style={styles.savedAccountCard}>
              <View style={styles.savedAccountHeader}>
                <View style={styles.savedAccountIcon}>
                  <Ionicons
                    name={String(selectedAccount.channel_type ?? "").toLowerCase() === "bank" ? "business-outline" : "phone-portrait-outline"}
                    size={20}
                    color={ACCENT}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.savedAccountMethod}>{selectedMethodName || "Payment Method"}</Text>
                  <Text style={styles.savedAccountNumber}>{selectedAccount.account_number}</Text>
                </View>
                <Pressable
                  style={({ pressed }) => [styles.editSavedButton, pressed && { opacity: 0.75 }]}
                  onPress={() => router.push("/account/payment-methods" as any)}
                >
                  <Text style={styles.editSavedButtonText}>Edit</Text>
                </Pressable>
              </View>
              {selectedAccountInfo ? <Text style={styles.savedAccountMeta}>{selectedAccountInfo}</Text> : null}
            </View>
          ) : (
            <View style={styles.noSavedAccountCard}>
              <Ionicons name="alert-circle-outline" size={22} color="#D97706" />
              <View style={{ flex: 1 }}>
                <Text style={styles.noSavedTitle}>No saved account for {selectedMethodName || "this method"}</Text>
                <Text style={styles.noSavedText}>Add the account from Payment Methods before requesting a withdrawal.</Text>
              </View>
              <Pressable
                style={({ pressed }) => [styles.addSavedButton, pressed && { opacity: 0.85 }]}
                onPress={() => router.push("/account/payment-methods" as any)}
              >
                <Text style={styles.addSavedButtonText}>Add</Text>
              </Pressable>
            </View>
          )}

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
                      <Text style={[styles.statusText, { color: statusStyle.text }]}>{item.status}</Text>
                    </View>
                  </View>
                  <Text style={styles.historyAmount}>{formatCurrency(item.withdrew_amount)}</Text>
                  <View style={styles.historyBottom}>
                    <Text style={styles.historyMeta}>{formatPaymentMethodName(item.paymenttype_name)}</Text>
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
      </KeyboardAwareScrollView>
      <AppDialog state={dialog} onClose={closeDialog} />
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
    minWidth: 82,
    position: "relative",
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
  savedDot: {
    position: "absolute",
    top: 8,
    right: 8,
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: "#D1D5DB",
  },
  savedDotActive: {
    backgroundColor: "#22C55E",
  },
  submitButton: {
    backgroundColor: ACCENT,
    borderRadius: 14,
    height: 50,
    justifyContent: "center",
    alignItems: "center",
    marginTop: 20,
  },
  savedAccountCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    padding: 14,
  },
  savedAccountHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  savedAccountIcon: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  savedAccountMethod: {
    fontSize: 13,
    color: "#6B7280",
    fontWeight: "600",
  },
  savedAccountNumber: {
    fontSize: 16,
    color: "#1A1A2E",
    fontWeight: "800",
    marginTop: 2,
  },
  savedAccountMeta: {
    fontSize: 12,
    color: "#6B7280",
    lineHeight: 18,
    marginTop: 12,
  },
  editSavedButton: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
    backgroundColor: "#FDF2F8",
  },
  editSavedButtonText: {
    color: ACCENT,
    fontSize: 12,
    fontWeight: "800",
  },
  noSavedAccountCard: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    backgroundColor: "#FFFBEB",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#FDE68A",
    padding: 14,
  },
  noSavedTitle: {
    fontSize: 13,
    color: "#92400E",
    fontWeight: "800",
  },
  noSavedText: {
    fontSize: 12,
    color: "#A16207",
    lineHeight: 17,
    marginTop: 2,
  },
  addSavedButton: {
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
    backgroundColor: ACCENT,
  },
  addSavedButtonText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "800",
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
