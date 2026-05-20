import { useCallback, useState } from "react";
import {
  View,
  StyleSheet,
  Pressable,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  Platform,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const ACCENT = "#E5005F";
const TAKA = "\u09F3";

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
  Paid: { bg: "#D1FAE5", text: "#065F46" },
  Completed: { bg: "#D1FAE5", text: "#065F46" },
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
  Cancelled: { bg: "#FEE2E2", text: "#991B1B" },
};

const DIRECTION_STYLES: Record<string, { bg: string; text: string }> = {
  Received: { bg: "#DBEAFE", text: "#1D4ED8" },
  Sent: { bg: "#FCE7F3", text: "#BE185D" },
};

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  const safeNum = Number.isFinite(num) ? num : 0;
  return `${TAKA}${safeNum.toLocaleString("en-BD")}`;
}

function getDirection(transfer: any): "Received" | "Sent" {
  return transfer?.transfer_direction === "Received" ? "Received" : "Sent";
}

function getCounterpartyLabel(transfer: any): string {
  return transfer?.counterparty_label || (getDirection(transfer) === "Received" ? "From" : "To");
}

function getCounterparty(transfer: any): string {
  return transfer?.counterparty || transfer?.to_account_number || "-";
}

export default function BalanceTransferScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();

  const [accountNumber, setAccountNumber] = useState("");
  const [amount, setAmount] = useState("");
  const [additionalInfo, setAdditionalInfo] = useState("");

  const dashboardQuery = useQuery({
    queryKey: ["dashboard-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/dashboard-data");
      return data?.data ?? data;
    },
    enabled: isResellerActive,
    staleTime: 2 * 60 * 1000,
  });

  const historyQuery = useQuery({
    queryKey: ["balance-transfers"],
    queryFn: async () => {
      const { data } = await apiClient.get("/balance-transferlists");
      return data?.data ?? data ?? [];
    },
    enabled: isResellerActive,
  });

  const transferMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await apiClient.post("/give-transfer-request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      showDialog({
        tone: "success",
        title: "Transfer completed",
        message: "Your balance transfer has been completed successfully.",
      });
      setAccountNumber("");
      setAmount("");
      setAdditionalInfo("");
      queryClient.invalidateQueries({ queryKey: ["balance-transfers"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      showDialog({
        tone: "error",
        title: "Transfer failed",
        message: err?.response?.data?.message ?? "Transfer failed.",
      });
    },
  });

  const walletBalance = Number(dashboardQuery.data?.balance ?? dashboardQuery.data?.blance ?? 0);
  const history: any[] = historyQuery.data ?? [];
  const lastTransfer = [...history].sort(
    (a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime(),
  )[0];
  const lastTransferAmount = Number(lastTransfer?.withdrew_amount ?? 0);
  const isBalanceLoading = dashboardQuery.isLoading || historyQuery.isLoading;

  const handleSubmit = () => {
    if (!amount.trim() || isNaN(Number(amount)) || Number(amount) <= 0) {
      showDialog({ tone: "warning", title: "Check amount", message: "Please enter a valid amount." });
      return;
    }
    if (!accountNumber.trim() || accountNumber.trim().length < 6) {
      showDialog({
        tone: "warning",
        title: "Check account number",
        message: "Account number must be at least 6 characters.",
      });
      return;
    }
    if (Number(amount) > walletBalance) {
      showDialog({
        tone: "warning",
        title: "Insufficient balance",
        message: "You don't have enough balance for this transfer.",
      });
      return;
    }

    const formData = new FormData();
    formData.append("withdrew_amount", amount.trim());
    formData.append("to_account_number", accountNumber.trim());
    if (additionalInfo.trim()) {
      formData.append("to_additional_info", additionalInfo.trim());
    }
    transferMutation.mutate(formData);
  };

  const isRefreshing = dashboardQuery.isRefetching || historyQuery.isRefetching;
  const bottomInset = Math.max(insets.bottom, 16);
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["balance-transfers"] });
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
  }, [queryClient]);

  if (isSubscriptionLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Balance Transfer", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <View style={styles.emptyState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Balance Transfer", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <SubscriptionRequired
          title="Activate to Transfer Balance"
          message="Activate your subscription to access balance transfers."
        />
      </>
    );
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Balance Transfer",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <KeyboardAwareScrollView
        style={styles.container}
        contentContainerStyle={{ paddingBottom: bottomInset + 48 }}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />
        }
        keyboardShouldPersistTaps="handled"
        keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
        bottomOffset={bottomInset + 24}
      >
        <View style={styles.balanceCard}>
          <View style={styles.balanceIcon}>
            <Ionicons name="swap-horizontal" size={24} color={ACCENT} />
          </View>
          <View style={{ flex: 1 }}>
            <Text fontSize="$2" color="#6B7280">Your total balance</Text>
            <Text fontSize="$7" fontWeight="bold" color="#1A1A2E" mt="$0.5">
              {isBalanceLoading ? "..." : formatCurrency(walletBalance)}
            </Text>
            <Text fontSize="$2" color="#059669" mt="$1">
              Last transfer: {isBalanceLoading ? "..." : formatCurrency(lastTransferAmount)}
            </Text>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>New Transfer</Text>

          <Text style={styles.inputLabel}>How much money do you want to transfer?</Text>
          <View style={styles.amountRow}>
            <View style={styles.currencyTag}>
              <Text style={styles.currencyText}>{TAKA}</Text>
            </View>
            <TextInput
              style={styles.amountInput}
              placeholder="Enter Your Amount"
              placeholderTextColor="#9CA3AF"
              keyboardType="numeric"
              value={amount}
              onChangeText={setAmount}
            />
          </View>

          <Text style={[styles.inputLabel, { marginTop: 16 }]}>To Send Money to Someone</Text>
          <TextInput
            style={styles.input}
            placeholder="Enter the account number"
            placeholderTextColor="#9CA3AF"
            value={accountNumber}
            onChangeText={setAccountNumber}
          />

          <Text style={[styles.inputLabel, { marginTop: 16 }]}>Additional information</Text>
          <TextInput
            style={styles.textArea}
            placeholder="If needed enter additional support"
            placeholderTextColor="#9CA3AF"
            multiline
            numberOfLines={3}
            textAlignVertical="top"
            value={additionalInfo}
            onChangeText={setAdditionalInfo}
            maxLength={1000}
          />

          <Pressable
            style={({ pressed }) => [
              styles.submitButton,
              pressed && { opacity: 0.85 },
              transferMutation.isPending && { opacity: 0.6 },
            ]}
            onPress={handleSubmit}
            disabled={transferMutation.isPending}
          >
            {transferMutation.isPending ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                Transfer Now
              </Text>
            )}
          </Pressable>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Transfer History</Text>

          {historyQuery.isLoading ? (
            <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 20 }} />
          ) : history.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="swap-horizontal-outline" size={40} color="#D1D5DB" />
              <Text fontSize="$3" color="#9CA3AF" mt="$2">
                No transfer history found.
              </Text>
            </View>
          ) : (
            history.map((item: any) => {
              const direction = getDirection(item);
              const directionStyle = DIRECTION_STYLES[direction];
              const statusStyle = STATUS_STYLES[item.status] ?? STATUS_STYLES.Pending;

              return (
                <View key={item.id} style={styles.historyCard}>
                  <View style={styles.historyTop}>
                    <Text style={styles.historyId}>#{item.id}</Text>
                    <View style={styles.pillRow}>
                      <View style={[styles.statusPill, { backgroundColor: directionStyle.bg }]}>
                        <Text style={[styles.statusText, { color: directionStyle.text }]}>
                          {direction}
                        </Text>
                      </View>
                      <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
                        <Text style={[styles.statusText, { color: statusStyle.text }]}>
                          {item.status ?? "Pending"}
                        </Text>
                      </View>
                    </View>
                  </View>
                  <Text style={styles.historyAmount}>{formatCurrency(item.withdrew_amount)}</Text>
                  <View style={styles.historyBottom}>
                    <Text style={styles.historyMeta} numberOfLines={1}>
                      {getCounterpartyLabel(item)}: {getCounterparty(item)}
                    </Text>
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
    alignItems: "center",
    gap: 14,
    margin: 16,
    padding: 20,
    backgroundColor: "#fff",
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  balanceIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
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
  input: {
    height: 48,
    backgroundColor: "#fff",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 14,
    fontSize: 15,
    color: "#1A1A2E",
  },
  amountRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  currencyTag: {
    height: 48,
    width: 48,
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
  pillRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
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
    gap: 12,
  },
  historyMeta: {
    flexShrink: 1,
    fontSize: 12,
    color: "#9CA3AF",
  },
});
