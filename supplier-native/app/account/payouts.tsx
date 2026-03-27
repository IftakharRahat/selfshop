import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
  RefreshControl,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

interface PayoutAccount {
  id: number;
  channel_type: string;
  provider_name?: string | null;
  account_name?: string | null;
  account_number?: string | null;
  is_default: boolean;
  is_active: boolean;
}

interface PayoutRequest {
  id: number;
  amount: number;
  status: string;
  admin_notes?: string | null;
  processed_at?: string | null;
  created_at: string;
  payout_account?: { channel_type: string; account_name: string; account_number: string } | null;
}

const CHANNEL_TYPES = [
  { value: "bank", label: "Bank Transfer", icon: "business-outline" },
  { value: "bkash", label: "bKash", icon: "phone-portrait-outline" },
  { value: "nagad", label: "Nagad", icon: "phone-portrait-outline" },
  { value: "rocket", label: "Rocket", icon: "phone-portrait-outline" },
] as const;

const PAYOUT_STATUS: Record<string, { bg: string; text: string }> = {
  pending: { bg: "#FEF3C7", text: "#92400E" },
  approved: { bg: "#D1FAE5", text: "#065F46" },
  processed: { bg: "#D1FAE5", text: "#065F46" },
  rejected: { bg: "#FEE2E2", text: "#991B1B" },
};

export default function PayoutsScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const [showAccountForm, setShowAccountForm] = useState(false);
  const [channelType, setChannelType] = useState("bkash");
  const [accountName, setAccountName] = useState("");
  const [accountNumber, setAccountNumber] = useState("");
  const [providerName, setProviderName] = useState("");

  const [showRequestForm, setShowRequestForm] = useState(false);
  const [requestAmount, setRequestAmount] = useState("");
  const [selectedAccountId, setSelectedAccountId] = useState<number | null>(null);

  const { data: accounts, isLoading: accountsLoading, refetch: refetchAccounts } = useQuery({
    queryKey: ["vendor-payout-accounts"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/payout-accounts");
      return data?.data?.payout_accounts as PayoutAccount[];
    },
  });

  const { data: requests, isLoading: requestsLoading, refetch: refetchRequests, isRefetching } = useQuery({
    queryKey: ["vendor-payout-requests"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/payout-requests");
      return data?.data?.payout_requests as PayoutRequest[];
    },
  });

  const createAccountMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post("/vendor/payout-accounts", {
        channel_type: channelType,
        account_name: accountName,
        account_number: accountNumber,
        provider_name: providerName || undefined,
      });
    },
    onSuccess: () => {
      toast.success("Account added");
      setShowAccountForm(false);
      setAccountName(""); setAccountNumber(""); setProviderName("");
      queryClient.invalidateQueries({ queryKey: ["vendor-payout-accounts"] });
    },
    onError: () => toast.error("Failed to add account"),
  });

  const deleteAccountMutation = useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/vendor/payout-accounts/${id}`);
    },
    onSuccess: () => {
      toast.success("Account removed");
      queryClient.invalidateQueries({ queryKey: ["vendor-payout-accounts"] });
    },
  });

  const createRequestMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post("/vendor/payout-requests", {
        amount: Number(requestAmount),
        payout_account_id: selectedAccountId ?? undefined,
      });
    },
    onSuccess: () => {
      toast.success("Payout request submitted");
      setShowRequestForm(false);
      setRequestAmount("");
      queryClient.invalidateQueries({ queryKey: ["vendor-payout-requests"] });
      queryClient.invalidateQueries({ queryKey: ["vendor-earnings-summary"] });
    },
    onError: () => toast.error("Failed to submit request"),
  });

  const payoutAccounts = accounts ?? [];
  const payoutRequests = requests ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Payouts</Text>
        <View style={{ width: 32 }} />
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={() => { refetchAccounts(); refetchRequests(); }} tintColor={BRAND.primary} />}
      >
        {/* Payout Accounts */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Payout Accounts</Text>
            <TouchableOpacity onPress={() => setShowAccountForm(!showAccountForm)}>
              <Ionicons name={showAccountForm ? "close-circle" : "add-circle"} size={22} color={BRAND.primary} />
            </TouchableOpacity>
          </View>

          {showAccountForm && (
            <View style={styles.formWrap}>
              <View style={styles.chipRow}>
                {CHANNEL_TYPES.map((ct) => (
                  <TouchableOpacity
                    key={ct.value}
                    style={[styles.chip, channelType === ct.value && styles.chipActive]}
                    onPress={() => setChannelType(ct.value)}
                  >
                    <Text style={[styles.chipText, channelType === ct.value && styles.chipTextActive]}>{ct.label}</Text>
                  </TouchableOpacity>
                ))}
              </View>
              {channelType === "bank" && (
                <TextInput style={styles.input} value={providerName} onChangeText={setProviderName} placeholder="Bank name" placeholderTextColor="#9ca3af" />
              )}
              <TextInput style={styles.input} value={accountName} onChangeText={setAccountName} placeholder="Account holder name" placeholderTextColor="#9ca3af" />
              <TextInput style={styles.input} value={accountNumber} onChangeText={setAccountNumber} placeholder="Account/phone number" keyboardType="phone-pad" placeholderTextColor="#9ca3af" />
              <TouchableOpacity
                style={[styles.submitBtn, createAccountMutation.isPending && { opacity: 0.6 }]}
                onPress={() => createAccountMutation.mutate()}
                disabled={createAccountMutation.isPending || !accountName || !accountNumber}
              >
                <Text style={styles.submitBtnText}>Add Account</Text>
              </TouchableOpacity>
            </View>
          )}

          {accountsLoading ? (
            <ActivityIndicator color={BRAND.primary} />
          ) : payoutAccounts.length === 0 ? (
            <Text style={styles.emptyText}>No payout accounts</Text>
          ) : (
            payoutAccounts.map((acc) => (
              <View key={acc.id} style={styles.accountRow}>
                <Ionicons name={acc.channel_type === "bank" ? "business" : "phone-portrait"} size={18} color={BRAND.primary} />
                <View style={styles.accountInfo}>
                  <Text style={styles.accountName}>{acc.account_name}</Text>
                  <Text style={styles.accountNumber}>{acc.channel_type.toUpperCase()} · {acc.account_number}</Text>
                </View>
                {acc.is_default && <View style={styles.defaultBadge}><Text style={styles.defaultText}>Default</Text></View>}
                <TouchableOpacity onPress={() => Alert.alert("Delete Account", "Remove this account?", [
                  { text: "Cancel", style: "cancel" },
                  { text: "Delete", style: "destructive", onPress: () => deleteAccountMutation.mutate(acc.id) },
                ])}>
                  <Ionicons name="trash-outline" size={16} color="#ef4444" />
                </TouchableOpacity>
              </View>
            ))
          )}
        </View>

        {/* Request Payout */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Payout Requests</Text>
            <TouchableOpacity onPress={() => setShowRequestForm(!showRequestForm)}>
              <Ionicons name={showRequestForm ? "close-circle" : "add-circle"} size={22} color={BRAND.primary} />
            </TouchableOpacity>
          </View>

          {showRequestForm && (
            <View style={styles.formWrap}>
              <TextInput
                style={styles.input}
                value={requestAmount}
                onChangeText={setRequestAmount}
                placeholder="Amount (৳)"
                keyboardType="numeric"
                placeholderTextColor="#9ca3af"
              />
              {payoutAccounts.length > 0 && (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 8 }}>
                  <View style={styles.chipRow}>
                    {payoutAccounts.map((acc) => (
                      <TouchableOpacity
                        key={acc.id}
                        style={[styles.chip, selectedAccountId === acc.id && styles.chipActive]}
                        onPress={() => setSelectedAccountId(selectedAccountId === acc.id ? null : acc.id)}
                      >
                        <Text style={[styles.chipText, selectedAccountId === acc.id && styles.chipTextActive]}>
                          {acc.account_name}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                </ScrollView>
              )}
              <TouchableOpacity
                style={[styles.submitBtn, createRequestMutation.isPending && { opacity: 0.6 }]}
                onPress={() => createRequestMutation.mutate()}
                disabled={createRequestMutation.isPending || !requestAmount}
              >
                <Text style={styles.submitBtnText}>Request Payout</Text>
              </TouchableOpacity>
            </View>
          )}

          {requestsLoading ? (
            <ActivityIndicator color={BRAND.primary} />
          ) : payoutRequests.length === 0 ? (
            <Text style={styles.emptyText}>No payout requests</Text>
          ) : (
            payoutRequests.map((req) => {
              const sc = PAYOUT_STATUS[req.status] ?? PAYOUT_STATUS.pending;
              return (
                <View key={req.id} style={styles.requestRow}>
                  <View style={styles.requestInfo}>
                    <Text style={styles.requestAmount}>৳{req.amount.toLocaleString()}</Text>
                    <Text style={styles.requestDate}>
                      {new Date(req.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}
                      {req.payout_account ? ` · ${req.payout_account.channel_type}` : ""}
                    </Text>
                  </View>
                  <View style={[styles.requestStatus, { backgroundColor: sc.bg }]}>
                    <Text style={[styles.requestStatusText, { color: sc.text }]}>{req.status}</Text>
                  </View>
                </View>
              );
            })
          )}
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff",
    borderBottomWidth: 1, borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  sectionCard: {
    backgroundColor: "#fff", borderRadius: 14, padding: 16, marginBottom: 12,
    borderWidth: 1, borderColor: "#f3f4f6",
  },
  sectionHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 12 },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e" },
  formWrap: { backgroundColor: "#f9fafb", borderRadius: 10, padding: 12, marginBottom: 12, gap: 8 },
  chipRow: { flexDirection: "row", gap: 6, flexWrap: "wrap", marginBottom: 8 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, backgroundColor: "#f3f4f6", borderWidth: 1, borderColor: "#e5e7eb" },
  chipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  chipText: { fontSize: 11, fontWeight: "500", color: "#6b7280" },
  chipTextActive: { color: "#fff" },
  input: {
    backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8,
    paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: "#1a1a2e",
  },
  submitBtn: { backgroundColor: BRAND.primary, borderRadius: 8, paddingVertical: 11, alignItems: "center" },
  submitBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  emptyText: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingVertical: 12 },
  accountRow: { flexDirection: "row", alignItems: "center", gap: 10, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb" },
  accountInfo: { flex: 1 },
  accountName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  accountNumber: { fontSize: 11, color: "#9ca3af", marginTop: 1 },
  defaultBadge: { backgroundColor: "#D1FAE5", paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  defaultText: { fontSize: 9, fontWeight: "600", color: "#065F46" },
  requestRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb" },
  requestInfo: { flex: 1 },
  requestAmount: { fontSize: 14, fontWeight: "700", color: "#1a1a2e" },
  requestDate: { fontSize: 11, color: "#9ca3af", marginTop: 1 },
  requestStatus: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  requestStatusText: { fontSize: 10, fontWeight: "600", textTransform: "capitalize" },
});
