import { useCallback, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Image,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { Stack } from "expo-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Sheet, Text } from "tamagui";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";
import { formatPaymentMethodName, rawPaymentMethodName } from "@/lib/payment-method-name";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const ACCENT = "#E5005F";

const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

type PaymentMethod = {
  id: number;
  paymentTypeName?: string;
  name?: string;
  icon?: string | null;
  saved_account?: PayoutAccount | null;
};

type PayoutAccount = {
  id: number;
  paymenttype_id: number;
  channel_type?: string | null;
  provider_name?: string | null;
  account_name?: string | null;
  account_number?: string | null;
  bank_name?: string | null;
  branch_name?: string | null;
  routing_number?: string | null;
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

function methodName(method?: PaymentMethod | null): string {
  return formatPaymentMethodName(method);
}

function isWalletMethod(method: PaymentMethod): boolean {
  return rawPaymentMethodName(method).trim().toLowerCase().includes("wallet");
}

function isBankMethod(method?: PaymentMethod | null): boolean {
  return rawPaymentMethodName(method).trim().toLowerCase().includes("bank");
}

function getMethodIcon(name: string): string {
  const key = name.toLowerCase().trim();
  if (key.includes("nagad") || key.includes("bkash") || key.includes("rocket")) {
    return "phone-portrait-outline";
  }
  if (key.includes("bank")) return "business-outline";
  return "card-outline";
}

function getMethodColor(name: string): string {
  const key = name.toLowerCase().trim();
  if (key.includes("nagad") || key.includes("bkash")) return "#E2136E";
  if (key.includes("rocket")) return "#8B2F87";
  if (key.includes("bank")) return "#1A6DB0";
  return ACCENT;
}

function savedAccountTitle(account?: PayoutAccount | null): string {
  if (!account?.account_number) return "No account added";
  if (account.channel_type === "bank") {
    return `${account.bank_name || "Bank"}: ${account.account_number}`;
  }
  return account.account_number;
}

export default function PaymentMethodsScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();

  const [sheetOpen, setSheetOpen] = useState(false);
  const [activeMethod, setActiveMethod] = useState<PaymentMethod | null>(null);
  const [accountName, setAccountName] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [bankName, setBankName] = useState("");
  const [branchName, setBranchName] = useState("");
  const [routingNumber, setRoutingNumber] = useState("");

  const methodsQuery = useQuery({
    queryKey: ["withdraw-methods"],
    queryFn: async () => {
      const { data } = await apiClient.get("/get-payment-types");
      return data?.data ?? data ?? [];
    },
    enabled: isResellerActive,
  });

  const accountsQuery = useQuery({
    queryKey: ["user-payout-accounts"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-payout-accounts");
      return data?.data?.payout_accounts ?? data?.data ?? [];
    },
    enabled: isResellerActive,
  });

  const rawMethods: PaymentMethod[] = methodsQuery.data ?? [];
  const accounts: PayoutAccount[] = Array.isArray(accountsQuery.data) ? accountsQuery.data : [];
  const accountByMethod = useMemo(() => {
    const map = new Map<number, PayoutAccount>();
    accounts.forEach((account) => {
      if (account.paymenttype_id) map.set(Number(account.paymenttype_id), account);
    });
    rawMethods.forEach((method) => {
      if (method.saved_account) map.set(Number(method.id), method.saved_account);
    });
    return map;
  }, [accounts, rawMethods]);

  const methods = useMemo(() => rawMethods.filter((method) => !isWalletMethod(method)), [rawMethods]);
  const activeAccount = activeMethod ? accountByMethod.get(Number(activeMethod.id)) : null;
  const activeIsBank = isBankMethod(activeMethod);

  const saveMutation = useMutation({
    mutationFn: async () => {
      if (!activeMethod) return null;

      const payload = {
        paymenttype_id: activeMethod.id,
        account_name: accountName.trim() || null,
        account_number: accountNumber.trim(),
        bank_name: activeIsBank ? bankName.trim() : null,
        branch_name: activeIsBank ? branchName.trim() : null,
        routing_number: activeIsBank ? routingNumber.trim() : null,
      };

      const { data } = activeAccount?.id
        ? await apiClient.put(`/user-payout-accounts/${activeAccount.id}`, payload)
        : await apiClient.post("/user-payout-accounts", payload);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["withdraw-methods"] });
      queryClient.invalidateQueries({ queryKey: ["user-payout-accounts"] });
      queryClient.invalidateQueries({ queryKey: ["bank-info"] });
      setSheetOpen(false);
      showDialog({
        tone: "success",
        title: "Payment method saved",
        message: "Your withdrawal account has been updated.",
      });
    },
    onError: (err: any) => {
      const errors = err?.response?.data?.errors as Record<string, string[]> | undefined;
      const firstValidationError = errors ? (Object.values(errors).flat().find(Boolean) as string | undefined) : undefined;
      showDialog({
        tone: "error",
        title: "Could not save account",
        message: firstValidationError || err?.response?.data?.message || "Failed to save payment method.",
      });
    },
  });

  const isRefreshing = methodsQuery.isRefetching || accountsQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["withdraw-methods"] });
    queryClient.invalidateQueries({ queryKey: ["user-payout-accounts"] });
  }, [queryClient]);

  function openMethodSheet(method: PaymentMethod) {
    const account = accountByMethod.get(Number(method.id));
    setActiveMethod(method);
    setAccountName(account?.account_name ?? "");
    setAccountNumber(account?.account_number ?? "");
    setBankName(account?.bank_name ?? "");
    setBranchName(account?.branch_name ?? "");
    setRoutingNumber(account?.routing_number ?? "");
    setSheetOpen(true);
  }

  function handleSave() {
    if (!activeMethod) return;
    if (!accountNumber.trim()) {
      showDialog({ tone: "warning", title: "Account number required", message: "Please enter the account number." });
      return;
    }
    if (activeIsBank) {
      if (!bankName.trim() || !branchName.trim() || !accountName.trim() || !routingNumber.trim()) {
        showDialog({
          tone: "warning",
          title: "Bank details required",
          message: "Bank name, branch name, account holder name, account number, and routing number are required.",
        });
        return;
      }
    }
    saveMutation.mutate();
  }

  const isLoading = methodsQuery.isLoading || accountsQuery.isLoading;

  if (isSubscriptionLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Payment Methods", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <View style={styles.emptyState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Payment Methods", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <SubscriptionRequired
          title="Activate to Manage Payments"
          message="Activate your subscription to manage payout accounts and withdrawal methods."
        />
      </>
    );
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Payment Methods",
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
          refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />}
        >
          <View style={styles.headerSection}>
            <View style={styles.headerIcon}>
              <Ionicons name="card" size={28} color={ACCENT} />
            </View>
            <Text fontSize="$3" color="#6B7280" mt="$2" style={styles.headerSubtitle}>
              Save the accounts you use for withdrawals
            </Text>
          </View>

          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Available Methods</Text>

            {isLoading ? (
              <ActivityIndicator size="large" color={ACCENT} style={{ marginVertical: 40 }} />
            ) : methods.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="card-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$3" color="#9CA3AF" mt="$2">
                  No payment methods available
                </Text>
              </View>
            ) : (
              methods.map((method) => {
                const name = methodName(method);
                const account = accountByMethod.get(Number(method.id));
                const iconUri = resolveImageUrl(method.icon);
                const fallbackIcon = getMethodIcon(name);
                const brandColor = getMethodColor(name);
                const isSaved = !!account?.account_number;

                return (
                  <Pressable
                    key={method.id}
                    style={({ pressed }) => [styles.methodCard, pressed && { backgroundColor: "#FCFCFD" }]}
                    onPress={() => openMethodSheet(method)}
                  >
                    <View style={styles.methodHeader}>
                      <View style={[styles.methodIconContainer, { backgroundColor: `${brandColor}15` }]}>
                        {iconUri ? (
                          <Image source={{ uri: iconUri }} style={styles.methodIconImage} resizeMode="contain" />
                        ) : (
                          <Ionicons name={fallbackIcon as any} size={24} color={brandColor} />
                        )}
                      </View>

                      <View style={styles.methodInfo}>
                        <Text fontSize="$4" fontWeight="700" color="#1A1A2E">
                          {name}
                        </Text>
                        <Text fontSize="$2" color={isSaved ? "#374151" : "#9CA3AF"} numberOfLines={1}>
                          {savedAccountTitle(account)}
                        </Text>
                      </View>

                      <View style={[styles.statusPill, isSaved ? styles.statusPillSaved : styles.statusPillEmpty]}>
                        <Text style={[styles.statusPillText, isSaved ? styles.statusPillTextSaved : styles.statusPillTextEmpty]}>
                          {isSaved ? "Edit" : "Add"}
                        </Text>
                      </View>
                    </View>
                  </Pressable>
                );
              })
            )}
          </View>

          <View style={styles.infoSection}>
            <View style={styles.infoCard}>
              <Ionicons name="information-circle" size={20} color="#3B82F6" />
              <Text fontSize="$2" color="#6B7280" ml="$2" flex={1}>
                Withdraw requests use these saved account details. To change a number, update it here first.
              </Text>
            </View>
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>

      <Sheet
        modal
        open={sheetOpen}
        onOpenChange={setSheetOpen}
        snapPoints={[86]}
        dismissOnSnapToBottom
      >
        <Sheet.Overlay style={styles.sheetOverlay} />
        <Sheet.Handle />
        <Sheet.Frame
          style={[
            styles.sheetFrame,
            { paddingBottom: Math.max(insets.bottom, 16) },
          ]}
        >
          <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
            <Text fontSize="$6" fontWeight="800" color="#1A1A2E">
              {activeAccount ? "Edit" : "Add"} {methodName(activeMethod)} Account
            </Text>

            {activeIsBank ? (
              <>
                <FormField label="Bank Name" value={bankName} onChangeText={setBankName} placeholder="Example: BRAC Bank" />
                <FormField label="Branch Name" value={branchName} onChangeText={setBranchName} placeholder="Example: Banani" />
                <FormField label="Account Holder Name" value={accountName} onChangeText={setAccountName} placeholder="Name on account" />
                <FormField label="Account Number" value={accountNumber} onChangeText={setAccountNumber} placeholder="Bank account number" keyboardType="number-pad" />
                <FormField label="Routing Number" value={routingNumber} onChangeText={setRoutingNumber} placeholder="9 digit routing number" keyboardType="number-pad" />
              </>
            ) : (
              <>
                <FormField label="Account Number" value={accountNumber} onChangeText={setAccountNumber} placeholder="01XXXXXXXXX" keyboardType="phone-pad" />
                <FormField label="Account Name (optional)" value={accountName} onChangeText={setAccountName} placeholder="Account holder name" />
              </>
            )}

            <Pressable
              style={({ pressed }) => [
                styles.saveButton,
                pressed && { opacity: 0.85 },
                saveMutation.isPending && { opacity: 0.6 },
              ]}
              onPress={handleSave}
              disabled={saveMutation.isPending}
            >
              {saveMutation.isPending ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text fontSize="$4" fontWeight="bold" color="#fff">
                  Save Account
                </Text>
              )}
            </Pressable>
          </ScrollView>
        </Sheet.Frame>
      </Sheet>

      <AppDialog state={dialog} onClose={closeDialog} />
    </>
  );
}

function FormField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
}: {
  label: string;
  value: string;
  onChangeText: (value: string) => void;
  placeholder: string;
  keyboardType?: "default" | "phone-pad" | "number-pad";
}) {
  return (
    <View style={styles.formField}>
      <Text fontSize="$3" fontWeight="600" color="#1A1A2E" mb="$1">
        {label}
      </Text>
      <TextInput
        style={styles.input}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#C7C7CC"
        keyboardType={keyboardType}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  headerSection: {
    alignItems: "center",
    paddingVertical: 24,
    paddingHorizontal: 20,
  },
  headerIcon: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  headerSubtitle: {
    textAlign: "center",
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 4,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 14,
  },
  emptyState: {
    alignItems: "center",
    paddingVertical: 40,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  methodCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    overflow: "hidden",
  },
  methodHeader: {
    flexDirection: "row",
    alignItems: "center",
    padding: 16,
    gap: 12,
  },
  methodIconContainer: {
    width: 48,
    height: 48,
    borderRadius: 14,
    justifyContent: "center",
    alignItems: "center",
  },
  methodIconImage: {
    width: 28,
    height: 28,
  },
  methodInfo: {
    flex: 1,
    gap: 2,
  },
  statusPill: {
    minWidth: 48,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 5,
    alignItems: "center",
  },
  statusPillSaved: {
    backgroundColor: "#FDF2F8",
  },
  statusPillEmpty: {
    backgroundColor: "#F3F4F6",
  },
  statusPillText: {
    fontSize: 11,
    fontWeight: "700",
  },
  statusPillTextSaved: {
    color: ACCENT,
  },
  statusPillTextEmpty: {
    color: "#6B7280",
  },
  infoSection: {
    paddingHorizontal: 16,
    marginTop: 20,
  },
  infoCard: {
    flexDirection: "row",
    alignItems: "flex-start",
    backgroundColor: "#EFF6FF",
    borderRadius: 12,
    padding: 14,
  },
  formField: {
    marginTop: 16,
  },
  input: {
    borderWidth: 1,
    borderColor: "#E8E8E8",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: "#1A1A2E",
    fontFamily: "Inter",
    backgroundColor: "#FAFAFA",
  },
  saveButton: {
    backgroundColor: ACCENT,
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: "center",
    marginTop: 22,
    marginBottom: 24,
  },
  sheetOverlay: {
    backgroundColor: "rgba(0,0,0,0.35)",
  },
  sheetFrame: {
    backgroundColor: "#fff",
    padding: 16,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
  },
});
