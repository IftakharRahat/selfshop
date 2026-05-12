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

const ACCENT = "#E5005F";

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

export default function BalanceTransferScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();

  /* ── State ── */
  const [recipientId, setRecipientId] = useState("");
  const [amount, setAmount] = useState("");

  /* ── Queries ── */
  const dashboardQuery = useQuery({
    queryKey: ["dashboard-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/dashboard-data");
      return data?.data ?? data;
    },
    staleTime: 2 * 60 * 1000,
  });

  const historyQuery = useQuery({
    queryKey: ["balance-transfers"],
    queryFn: async () => {
      const { data } = await apiClient.get("/balance-transferlists");
      return data?.data ?? data ?? [];
    },
  });

  /* ── Mutation ── */
  const transferMutation = useMutation({
    mutationFn: async (body: { to_user_id: string; amount: string }) => {
      const { data } = await apiClient.post("/give-transfer-request", body);
      return data;
    },
    onSuccess: () => {
      showDialog({ tone: "success", title: "Transfer submitted", message: "Your balance transfer request has been submitted successfully." });
      setRecipientId("");
      setAmount("");
      queryClient.invalidateQueries({ queryKey: ["balance-transfers"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      showDialog({ tone: "error", title: "Transfer failed", message: err?.response?.data?.message ?? "Transfer failed." });
    },
  });

  /* ── Derived ── */
  const walletBalance = Number(dashboardQuery.data?.balance ?? dashboardQuery.data?.blance ?? 0);
  const history: any[] = historyQuery.data ?? [];

  /* ── Submit ── */
  const handleSubmit = () => {
    if (!recipientId.trim()) {
      showDialog({ tone: "warning", title: "Recipient needed", message: "Please enter recipient ID or phone." });
      return;
    }
    if (!amount.trim() || isNaN(Number(amount)) || Number(amount) <= 0) {
      showDialog({ tone: "warning", title: "Check amount", message: "Please enter a valid amount." });
      return;
    }
    if (Number(amount) > walletBalance) {
      showDialog({ tone: "warning", title: "Insufficient balance", message: "You don't have enough balance for this transfer." });
      return;
    }
    transferMutation.mutate({ to_user_id: recipientId.trim(), amount: amount.trim() });
  };

  /* ── Refresh ── */
  const isRefreshing = historyQuery.isRefetching;
  const bottomInset = Math.max(insets.bottom, 16);
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["balance-transfers"] });
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
  }, [queryClient]);

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
          {/* ── Balance Info ── */}
          <View style={styles.balanceCard}>
            <View style={styles.balanceIcon}>
              <Ionicons name="swap-horizontal" size={24} color={ACCENT} />
            </View>
            <View>
              <Text fontSize="$2" color="#6B7280">Available Balance</Text>
              <Text fontSize="$7" fontWeight="bold" color="#1A1A2E" mt="$0.5">
                {dashboardQuery.isLoading ? "..." : formatCurrency(walletBalance)}
              </Text>
            </View>
          </View>

          {/* ── Transfer Form ── */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>New Transfer</Text>

            <Text style={styles.inputLabel}>Recipient ID / Phone</Text>
            <TextInput
              style={styles.input}
              placeholder="Enter recipient ID or phone number"
              placeholderTextColor="#9CA3AF"
              value={recipientId}
              onChangeText={setRecipientId}
            />

            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Amount</Text>
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

          {/* ── History ── */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Transfer History</Text>

            {historyQuery.isLoading ? (
              <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 20 }} />
            ) : history.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="swap-horizontal-outline" size={40} color="#D1D5DB" />
                <Text fontSize="$3" color="#9CA3AF" mt="$2">
                  No transfers yet
                </Text>
              </View>
            ) : (
              history.map((item: any) => (
                <View key={item.id} style={styles.historyCard}>
                  <View style={styles.historyRow}>
                    <View style={styles.historyIconWrapper}>
                      <Ionicons name="arrow-forward-outline" size={18} color={ACCENT} />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.historyTitle}>
                        To: {item.to_user?.name ?? item.to_user_id ?? "User"}
                      </Text>
                      <Text style={styles.historyMeta}>
                        {item.created_at ? new Date(item.created_at).toLocaleDateString() : ""}
                      </Text>
                    </View>
                    <Text style={styles.historyAmount}>
                      {formatCurrency(item.amount)}
                    </Text>
                  </View>
                </View>
              ))
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
  historyRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  historyIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  historyTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1A1A2E",
  },
  historyMeta: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 2,
  },
  historyAmount: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1A1A2E",
  },
});
