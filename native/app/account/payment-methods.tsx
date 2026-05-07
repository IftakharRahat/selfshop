import { useCallback, useEffect, useMemo, useState } from "react";
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
  Image,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
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

/* ── Known method icon fallbacks (Ionicons names) ── */
const METHOD_ICONS: Record<string, string> = {
  nagad: "phone-portrait-outline",
  bkash: "phone-portrait-outline",
  rocket: "phone-portrait-outline",
  bank: "business-outline",
};

function getMethodIcon(name: string): string {
  const key = name.toLowerCase().trim();
  for (const [k, icon] of Object.entries(METHOD_ICONS)) {
    if (key.includes(k)) return icon;
  }
  return "card-outline";
}

/* ── Known method brand colors ── */
const METHOD_COLORS: Record<string, string> = {
  nagad: "#E2136E",
  bkash: "#E2136E",
  rocket: "#8B2F87",
  bank: "#1A6DB0",
};

function getMethodColor(name: string): string {
  const key = name.toLowerCase().trim();
  for (const [k, color] of Object.entries(METHOD_COLORS)) {
    if (key.includes(k)) return color;
  }
  return ACCENT;
}

function isWalletMethod(method: any): boolean {
  return String(method?.paymentTypeName ?? method?.name ?? "")
    .trim()
    .toLowerCase()
    .includes("wallet");
}

interface SavedAccount {
  id: string;
  methodId: number;
  methodName: string;
  accountNumber: string;
  isDefault: boolean;
}

export default function PaymentMethodsScreen() {
  const queryClient = useQueryClient();

  /* ── State ── */
  const [editingMethodId, setEditingMethodId] = useState<number | null>(null);
  const [accountNumber, setAccountNumber] = useState("");

  /* ── Queries ── */
  const methodsQuery = useQuery({
    queryKey: ["withdraw-methods"],
    queryFn: async () => {
      const { data } = await apiClient.get("/get-payment-types");
      return data?.data ?? data ?? [];
    },
  });

  const bankQuery = useQuery({
    queryKey: ["bank-info"],
    queryFn: async () => {
      try {
        const { data } = await apiClient.get("/bank-info");
        return data?.data ?? data ?? null;
      } catch {
        return null;
      }
    },
  });

  /* ── Mutation for bank info ── */
  const bankMutation = useMutation({
    mutationFn: async (formData: any) => {
      const { data } = await apiClient.post("/bank-info", formData);
      return data;
    },
    onSuccess: () => {
      Alert.alert("Success", "Bank information updated successfully.");
      queryClient.invalidateQueries({ queryKey: ["bank-info"] });
      setEditingMethodId(null);
      setAccountNumber("");
    },
    onError: (err: any) => {
      Alert.alert("Error", err?.response?.data?.message ?? "Failed to update bank info.");
    },
  });

  /* ── Derived ── */
  const rawMethods: any[] = methodsQuery.data ?? [];
  const methods = useMemo(
    () => rawMethods.filter((method) => !isWalletMethod(method)),
    [rawMethods]
  );

  /* ── Refresh ── */
  const isRefreshing = methodsQuery.isRefetching || bankQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["withdraw-methods"] });
    queryClient.invalidateQueries({ queryKey: ["bank-info"] });
  }, [queryClient]);

  const isLoading = methodsQuery.isLoading;

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
          refreshControl={
            <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />
          }
          keyboardShouldPersistTaps="handled"
        >
          {/* Header */}
          <View style={styles.headerSection}>
            <View style={styles.headerIcon}>
              <Ionicons name="card" size={28} color={ACCENT} />
            </View>
            <Text fontSize="$3" color="#6B7280" textAlign="center" mt="$2">
              Manage your payment methods for withdrawals
            </Text>
          </View>

          {/* Payment Methods List */}
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
              methods.map((method: any) => {
                const methodName = method.paymentTypeName ?? "";
                const iconUri = resolveImageUrl(method.icon);
                const fallbackIcon = getMethodIcon(methodName);
                const brandColor = getMethodColor(methodName);
                const isBank = methodName.toLowerCase().includes("bank");
                const isEditing = editingMethodId === method.id;
                const bankData = bankQuery.data;

                return (
                  <View key={method.id} style={styles.methodCard}>
                    <View style={styles.methodHeader}>
                      <View style={[styles.methodIconContainer, { backgroundColor: `${brandColor}15` }]}>
                        {iconUri ? (
                          <Image
                            source={{ uri: iconUri }}
                            style={styles.methodIconImage}
                            resizeMode="contain"
                          />
                        ) : (
                          <Ionicons name={fallbackIcon as any} size={24} color={brandColor} />
                        )}
                      </View>

                      <View style={styles.methodInfo}>
                        <Text fontSize="$4" fontWeight="700" color="#1A1A2E">
                          {methodName}
                        </Text>
                        <Text fontSize="$2" color="#9CA3AF">
                          {isBank
                            ? bankData?.account_number
                              ? `A/C: ${bankData.account_number}`
                              : "No account linked"
                            : "Available for withdrawal"}
                        </Text>
                      </View>

                      <View style={[styles.statusDot, { backgroundColor: "#22C55E" }]} />
                    </View>

                    {/* Bank-specific: show saved info or edit form */}
                    {isBank && bankData && !isEditing && (
                      <View style={styles.bankDetails}>
                        {bankData.bank_name ? (
                          <View style={styles.bankDetailRow}>
                            <Text style={styles.bankDetailLabel}>Bank</Text>
                            <Text style={styles.bankDetailValue}>{bankData.bank_name}</Text>
                          </View>
                        ) : null}
                        {bankData.account_name ? (
                          <View style={styles.bankDetailRow}>
                            <Text style={styles.bankDetailLabel}>Name</Text>
                            <Text style={styles.bankDetailValue}>{bankData.account_name}</Text>
                          </View>
                        ) : null}
                        {bankData.account_number ? (
                          <View style={styles.bankDetailRow}>
                            <Text style={styles.bankDetailLabel}>Account</Text>
                            <Text style={styles.bankDetailValue}>{bankData.account_number}</Text>
                          </View>
                        ) : null}
                        {bankData.routing_number ? (
                          <View style={styles.bankDetailRow}>
                            <Text style={styles.bankDetailLabel}>Routing</Text>
                            <Text style={styles.bankDetailValue}>{bankData.routing_number}</Text>
                          </View>
                        ) : null}
                      </View>
                    )}

                    {/* Divider + action */}
                    <View style={styles.methodFooter}>
                      <View style={[styles.methodTag, { backgroundColor: `${brandColor}15` }]}>
                        <Text style={[styles.methodTagText, { color: brandColor }]}>
                          {isBank ? "Bank Transfer" : "Mobile Banking"}
                        </Text>
                      </View>

                      <Pressable
                        style={({ pressed }) => [
                          styles.methodAction,
                          pressed && { opacity: 0.7 },
                        ]}
                        onPress={() => {
                          if (isBank) {
                            // Navigate to withdraw with bank pre-selected
                            Alert.alert(
                              "Bank Withdrawal",
                              "Use the Withdraw screen to make a bank withdrawal. This method will appear in your withdrawal options."
                            );
                          }
                        }}
                      >
                        <Ionicons name="chevron-forward" size={16} color="#9CA3AF" />
                      </Pressable>
                    </View>
                  </View>
                );
              })
            )}
          </View>

          {/* Info Section */}
          <View style={styles.infoSection}>
            <View style={styles.infoCard}>
              <Ionicons name="information-circle" size={20} color="#3B82F6" />
              <Text fontSize="$2" color="#6B7280" ml="$2" flex={1}>
                All payment methods shown here are available for withdrawals. Select your preferred method on the withdrawal screen.
              </Text>
            </View>
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </>
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
  statusDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },

  bankDetails: {
    marginHorizontal: 16,
    marginBottom: 12,
    backgroundColor: "#F8F9FA",
    borderRadius: 12,
    padding: 12,
    gap: 8,
  },
  bankDetailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  bankDetailLabel: {
    fontSize: 12,
    color: "#9CA3AF",
    fontWeight: "500",
  },
  bankDetailValue: {
    fontSize: 13,
    color: "#374151",
    fontWeight: "600",
  },

  methodFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderTopWidth: 1,
    borderTopColor: "#F5F5F5",
  },
  methodTag: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  methodTagText: {
    fontSize: 11,
    fontWeight: "600",
  },
  methodAction: {
    padding: 4,
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
});
