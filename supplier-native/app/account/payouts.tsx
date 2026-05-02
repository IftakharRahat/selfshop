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
  Modal,
  Switch,
  KeyboardAvoidingView,
  Platform,
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
  routing_number?: string | null;
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

const CHANNEL_OPTIONS = [
  { value: "bank", label: "Bank", icon: "business-outline" as const, color: "#2d2a5d", bg: "#2d2a5d12", border: "#2d2a5d40" },
  { value: "bkash", label: "bKash", icon: "phone-portrait-outline" as const, color: "#E2136E", bg: "#E2136E15", border: "#E2136E40" },
  { value: "nagad", label: "Nagad", icon: "phone-portrait-outline" as const, color: "#F6921E", bg: "#F6921E15", border: "#F6921E40" },
  { value: "rocket", label: "Rocket", icon: "phone-portrait-outline" as const, color: "#8C3494", bg: "#8C349415", border: "#8C349440" },
] as const;

const channelLabel = (t: string) => CHANNEL_OPTIONS.find((c) => c.value === t)?.label ?? t;
const channelStyle = (t: string) => CHANNEL_OPTIONS.find((c) => c.value === t) ?? CHANNEL_OPTIONS[0];
const isMobileWallet = (t: string) => t === "bkash" || t === "nagad" || t === "rocket";

/** Map UI type to backend-accepted channel_type */
function toApiPayload(form: FormState) {
  const isWallet = isMobileWallet(form.channel_type);
  return {
    channel_type: isWallet ? "mobile_wallet" : form.channel_type,
    provider_name: isWallet ? channelLabel(form.channel_type) : (form.provider_name || undefined),
    account_name: form.account_name,
    account_number: form.account_number,
    routing_number: form.routing_number || undefined,
    is_default: form.is_default,
  };
}

/** Detect UI channel type from backend data */
function detectChannelType(acc: PayoutAccount): string {
  if (acc.channel_type === "mobile_wallet") {
    const p = (acc.provider_name ?? "").toLowerCase();
    if (p.includes("nagad")) return "nagad";
    if (p.includes("rocket")) return "rocket";
    return "bkash";
  }
  return acc.channel_type;
}

const PAYOUT_STATUS: Record<string, { bg: string; text: string }> = {
  pending: { bg: "#FEF3C7", text: "#92400E" },
  approved: { bg: "#D1FAE5", text: "#065F46" },
  processed: { bg: "#D1FAE5", text: "#065F46" },
  rejected: { bg: "#FEE2E2", text: "#991B1B" },
};

interface FormState {
  channel_type: string;
  provider_name: string;
  account_name: string;
  account_number: string;
  routing_number: string;
  is_default: boolean;
}

const EMPTY_FORM: FormState = {
  channel_type: "bkash",
  provider_name: "",
  account_name: "",
  account_number: "",
  routing_number: "",
  is_default: false,
};

export default function PayoutsScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  // Account modal state
  const [accountModal, setAccountModal] = useState<{ open: boolean; editId: number | null }>({ open: false, editId: null });
  const [form, setForm] = useState<FormState>(EMPTY_FORM);

  // Request payout state
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

  const saveAccountMutation = useMutation({
    mutationFn: async () => {
      const payload = toApiPayload(form);
      if (accountModal.editId) {
        await apiClient.put(`/vendor/payout-accounts/${accountModal.editId}`, payload);
      } else {
        await apiClient.post("/vendor/payout-accounts", payload);
      }
    },
    onSuccess: () => {
      toast.success(accountModal.editId ? "Account updated" : "Account added");
      closeAccountModal();
      queryClient.invalidateQueries({ queryKey: ["vendor-payout-accounts"] });
    },
    onError: () => toast.error("Failed to save account"),
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

  const openCreateAccount = () => {
    setForm(EMPTY_FORM);
    setAccountModal({ open: true, editId: null });
  };

  const openEditAccount = (acc: PayoutAccount) => {
    setForm({
      channel_type: detectChannelType(acc),
      provider_name: acc.provider_name ?? "",
      account_name: acc.account_name ?? "",
      account_number: acc.account_number ?? "",
      routing_number: acc.routing_number ?? "",
      is_default: acc.is_default,
    });
    setAccountModal({ open: true, editId: acc.id });
  };

  const closeAccountModal = () => {
    setAccountModal({ open: false, editId: null });
    setForm(EMPTY_FORM);
  };

  const handleSaveAccount = () => {
    if (!form.account_name.trim() || !form.account_number.trim()) {
      toast.error("Please fill in all required fields");
      return;
    }
    if (form.channel_type === "bank" && !form.provider_name.trim()) {
      toast.error("Please enter a bank name");
      return;
    }
    saveAccountMutation.mutate();
  };

  const payoutAccounts = accounts ?? [];
  const payoutRequests = requests ?? [];
  const activeStyle = channelStyle(form.channel_type);

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
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
            <TouchableOpacity onPress={openCreateAccount} style={styles.addBtn}>
              <Ionicons name="add" size={16} color="#fff" />
              <Text style={styles.addBtnText}>Add</Text>
            </TouchableOpacity>
          </View>

          {accountsLoading ? (
            <ActivityIndicator color={BRAND.primary} style={{ paddingVertical: 20 }} />
          ) : payoutAccounts.length === 0 ? (
            <View style={styles.emptyWrap}>
              <Ionicons name="wallet-outline" size={32} color="#d1d5db" />
              <Text style={styles.emptyText}>No payout accounts yet</Text>
              <TouchableOpacity onPress={openCreateAccount}>
                <Text style={styles.emptyLink}>Add your first account</Text>
              </TouchableOpacity>
            </View>
          ) : (
            payoutAccounts.map((acc) => {
              const cs = channelStyle(acc.channel_type);
              return (
                <TouchableOpacity key={acc.id} style={[styles.accountCard, { borderLeftColor: cs.color, backgroundColor: acc.is_default ? cs.bg : "#fff" }]} onPress={() => openEditAccount(acc)} activeOpacity={0.7}>
                  <View style={[styles.accountIcon, { backgroundColor: cs.bg }]}>
                    <Ionicons name={acc.channel_type === "bank" ? "business" : "phone-portrait"} size={18} color={cs.color} />
                  </View>
                  <View style={styles.accountInfo}>
                    <View style={styles.accountTitleRow}>
                      <Text style={[styles.accountType, { color: cs.color }]}>{channelLabel(acc.channel_type)}</Text>
                      {acc.is_default && (
                        <View style={[styles.defaultBadge, { backgroundColor: cs.bg }]}>
                          <Text style={[styles.defaultText, { color: cs.color }]}>DEFAULT</Text>
                        </View>
                      )}
                    </View>
                    {acc.provider_name ? <Text style={styles.accountProvider}>{acc.provider_name}</Text> : null}
                    <Text style={styles.accountName}>{acc.account_name}</Text>
                    <Text style={styles.accountNumber}>{acc.account_number}</Text>
                  </View>
                  <View style={styles.accountActions}>
                    <TouchableOpacity onPress={() => openEditAccount(acc)} hitSlop={8}>
                      <Ionicons name="create-outline" size={16} color="#6b7280" />
                    </TouchableOpacity>
                    <TouchableOpacity onPress={() => Alert.alert("Delete Account", "Remove this account?", [
                      { text: "Cancel", style: "cancel" },
                      { text: "Delete", style: "destructive", onPress: () => deleteAccountMutation.mutate(acc.id) },
                    ])} hitSlop={8}>
                      <Ionicons name="trash-outline" size={16} color="#ef4444" />
                    </TouchableOpacity>
                  </View>
                </TouchableOpacity>
              );
            })
          )}
        </View>

        {/* Payout Requests */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Payout Requests</Text>
            <TouchableOpacity onPress={() => setShowRequestForm(!showRequestForm)}>
              <Ionicons name={showRequestForm ? "close-circle" : "add-circle"} size={22} color={BRAND.primary} />
            </TouchableOpacity>
          </View>

          {showRequestForm && (
            <View style={styles.formWrap}>
              <TextInput style={styles.input} value={requestAmount} onChangeText={setRequestAmount} placeholder="Amount (৳)" keyboardType="numeric" placeholderTextColor="#9ca3af" />
              {payoutAccounts.length > 0 && (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 8 }}>
                  <View style={styles.chipRow}>
                    {payoutAccounts.map((acc) => (
                      <TouchableOpacity
                        key={acc.id}
                        style={[styles.chip, selectedAccountId === acc.id && { backgroundColor: channelStyle(acc.channel_type).color, borderColor: channelStyle(acc.channel_type).color }]}
                        onPress={() => setSelectedAccountId(selectedAccountId === acc.id ? null : acc.id)}
                      >
                        <Text style={[styles.chipText, selectedAccountId === acc.id && { color: "#fff" }]}>{acc.account_name}</Text>
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
            <Text style={styles.emptyTextSimple}>No payout requests</Text>
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

      {/* ── Bottom Sheet Modal for Add/Edit Account ── */}
      <Modal visible={accountModal.open} animationType="slide" transparent onRequestClose={closeAccountModal}>
        <View style={styles.modalOverlay}>
          <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.modalSheet}>
            <View style={styles.modalDragHandle} />
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>{accountModal.editId ? "Edit Account" : "Add Payout Account"}</Text>
              <TouchableOpacity onPress={closeAccountModal} hitSlop={12}>
                <Ionicons name="close" size={22} color="#6b7280" />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 20 }}>
              {/* Account Type Selector */}
              <Text style={styles.fieldLabel}>Account Type</Text>
              <View style={styles.typeGrid}>
                {CHANNEL_OPTIONS.map((opt) => {
                  const selected = form.channel_type === opt.value;
                  return (
                    <TouchableOpacity
                      key={opt.value}
                      style={[styles.typeCard, { borderColor: selected ? opt.color : "#e5e7eb", backgroundColor: selected ? opt.bg : "#f9fafb" }]}
                      onPress={() => setForm({ ...EMPTY_FORM, channel_type: opt.value, is_default: form.is_default })}
                      activeOpacity={0.7}
                    >
                      <Ionicons name={opt.icon} size={20} color={selected ? opt.color : "#9ca3af"} />
                      <Text style={[styles.typeLabel, { color: selected ? opt.color : "#9ca3af", fontWeight: selected ? "700" : "500" }]}>{opt.label}</Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              {/* Branded header for mobile wallets */}
              {isMobileWallet(form.channel_type) && (
                <View style={[styles.brandedCard, { backgroundColor: activeStyle.bg, borderColor: activeStyle.border }]}>
                  <Text style={[styles.brandedTitle, { color: activeStyle.color }]}>{channelLabel(form.channel_type)}</Text>
                  <Text style={styles.brandedSub}>Enter your {channelLabel(form.channel_type)} account details below</Text>
                </View>
              )}

              {/* Bank name — only for bank */}
              {form.channel_type === "bank" && (
                <>
                  <Text style={styles.fieldLabel}>Bank Name *</Text>
                  <TextInput style={styles.modalInput} value={form.provider_name} onChangeText={(v) => setForm({ ...form, provider_name: v })} placeholder="e.g. Dutch-Bangla Bank" placeholderTextColor="#9ca3af" />
                </>
              )}

              {/* Account name */}
              <Text style={styles.fieldLabel}>
                {isMobileWallet(form.channel_type) ? `${channelLabel(form.channel_type)} Account Name` : "Account Holder Name"} *
              </Text>
              <TextInput
                style={styles.modalInput}
                value={form.account_name}
                onChangeText={(v) => setForm({ ...form, account_name: v })}
                placeholder={isMobileWallet(form.channel_type) ? `Name registered on ${channelLabel(form.channel_type)}` : "Name on account"}
                placeholderTextColor="#9ca3af"
              />

              {/* Account number */}
              <Text style={styles.fieldLabel}>
                {isMobileWallet(form.channel_type) ? `${channelLabel(form.channel_type)} Number` : "Account Number"} *
              </Text>
              <TextInput
                style={styles.modalInput}
                value={form.account_number}
                onChangeText={(v) => setForm({ ...form, account_number: v })}
                placeholder={isMobileWallet(form.channel_type) ? "01XXXXXXXXX" : "Account number"}
                keyboardType={isMobileWallet(form.channel_type) ? "phone-pad" : "default"}
                placeholderTextColor="#9ca3af"
              />

              {/* Routing number — bank only */}
              {form.channel_type === "bank" && (
                <>
                  <Text style={styles.fieldLabel}>Routing Number (optional)</Text>
                  <TextInput style={styles.modalInput} value={form.routing_number} onChangeText={(v) => setForm({ ...form, routing_number: v })} placeholder="Bank routing number" placeholderTextColor="#9ca3af" />
                </>
              )}

              {/* Default toggle */}
              <View style={styles.toggleRow}>
                <Text style={styles.toggleLabel}>Set as default for payouts</Text>
                <Switch value={form.is_default} onValueChange={(v) => setForm({ ...form, is_default: v })} trackColor={{ false: "#e5e7eb", true: activeStyle.color + "60" }} thumbColor={form.is_default ? activeStyle.color : "#f4f3f4"} />
              </View>

              {/* Action buttons */}
              <View style={styles.modalActions}>
                <TouchableOpacity style={styles.cancelBtn} onPress={closeAccountModal}>
                  <Text style={styles.cancelBtnText}>Cancel</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.saveBtn, { backgroundColor: activeStyle.color }, saveAccountMutation.isPending && { opacity: 0.6 }]}
                  onPress={handleSaveAccount}
                  disabled={saveAccountMutation.isPending}
                >
                  <Text style={styles.saveBtnText}>{saveAccountMutation.isPending ? "Saving..." : accountModal.editId ? "Update" : "Add Account"}</Text>
                </TouchableOpacity>
              </View>
            </ScrollView>
          </KeyboardAvoidingView>
        </View>
      </Modal>
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
  addBtn: { flexDirection: "row", alignItems: "center", gap: 4, backgroundColor: BRAND.primary, paddingHorizontal: 12, paddingVertical: 6, borderRadius: 8 },
  addBtnText: { fontSize: 12, fontWeight: "600", color: "#fff" },
  // Empty state
  emptyWrap: { alignItems: "center", paddingVertical: 24, gap: 6 },
  emptyText: { fontSize: 13, color: "#9ca3af" },
  emptyTextSimple: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingVertical: 12 },
  emptyLink: { fontSize: 13, color: BRAND.primary, fontWeight: "600" },
  // Account cards
  accountCard: {
    flexDirection: "row", alignItems: "center", gap: 12, padding: 14,
    borderRadius: 12, borderWidth: 1, borderColor: "#f3f4f6", borderLeftWidth: 4,
    marginBottom: 8,
  },
  accountIcon: { width: 40, height: 40, borderRadius: 10, alignItems: "center", justifyContent: "center" },
  accountInfo: { flex: 1 },
  accountTitleRow: { flexDirection: "row", alignItems: "center", gap: 6 },
  accountType: { fontSize: 13, fontWeight: "700" },
  accountProvider: { fontSize: 11, color: "#9ca3af", marginTop: 1 },
  accountName: { fontSize: 13, fontWeight: "500", color: "#1a1a2e", marginTop: 2 },
  accountNumber: { fontSize: 11, color: "#9ca3af", fontFamily: Platform.OS === "ios" ? "Menlo" : "monospace", marginTop: 1 },
  defaultBadge: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
  defaultText: { fontSize: 9, fontWeight: "700" },
  accountActions: { gap: 12, alignItems: "center" },
  // Request form
  formWrap: { backgroundColor: "#f9fafb", borderRadius: 10, padding: 12, marginBottom: 12, gap: 8 },
  chipRow: { flexDirection: "row", gap: 6, flexWrap: "wrap", marginBottom: 8 },
  chip: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 16, backgroundColor: "#f3f4f6", borderWidth: 1, borderColor: "#e5e7eb" },
  chipText: { fontSize: 11, fontWeight: "500", color: "#6b7280" },
  input: {
    backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 8,
    paddingHorizontal: 12, paddingVertical: 10, fontSize: 13, color: "#1a1a2e",
  },
  submitBtn: { backgroundColor: BRAND.primary, borderRadius: 8, paddingVertical: 11, alignItems: "center" },
  submitBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  // Request rows
  requestRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#f9fafb" },
  requestInfo: { flex: 1 },
  requestAmount: { fontSize: 14, fontWeight: "700", color: "#1a1a2e" },
  requestDate: { fontSize: 11, color: "#9ca3af", marginTop: 1 },
  requestStatus: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  requestStatusText: { fontSize: 10, fontWeight: "600", textTransform: "capitalize" },
  // Modal
  modalOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.4)", justifyContent: "flex-end" },
  modalSheet: { backgroundColor: "#fff", borderTopLeftRadius: 20, borderTopRightRadius: 20, maxHeight: "90%", paddingHorizontal: 20 },
  modalDragHandle: { width: 36, height: 4, backgroundColor: "#d1d5db", borderRadius: 2, alignSelf: "center", marginTop: 10, marginBottom: 6 },
  modalHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: "#f3f4f6", marginBottom: 16 },
  modalTitle: { fontSize: 17, fontWeight: "700", color: "#1a1a2e" },
  fieldLabel: { fontSize: 13, fontWeight: "600", color: "#374151", marginBottom: 6, marginTop: 14 },
  modalInput: {
    backgroundColor: "#f9fafb", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 12, fontSize: 14, color: "#1a1a2e",
  },
  // Type selector
  typeGrid: { flexDirection: "row", gap: 8 },
  typeCard: {
    flex: 1, alignItems: "center", justifyContent: "center", gap: 4,
    paddingVertical: 14, borderRadius: 12, borderWidth: 2,
  },
  typeLabel: { fontSize: 11 },
  // Branded card
  brandedCard: { borderRadius: 10, padding: 12, marginTop: 12, borderWidth: 1 },
  brandedTitle: { fontSize: 14, fontWeight: "700" },
  brandedSub: { fontSize: 11, color: "#6b7280", marginTop: 2 },
  // Toggle
  toggleRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginTop: 18, paddingVertical: 4 },
  toggleLabel: { fontSize: 13, fontWeight: "500", color: "#374151" },
  // Modal actions
  modalActions: { flexDirection: "row", gap: 10, marginTop: 20 },
  cancelBtn: { flex: 1, paddingVertical: 13, borderRadius: 10, borderWidth: 1, borderColor: "#e5e7eb", alignItems: "center" },
  cancelBtnText: { fontSize: 14, fontWeight: "600", color: "#6b7280" },
  saveBtn: { flex: 1, paddingVertical: 13, borderRadius: 10, alignItems: "center" },
  saveBtnText: { fontSize: 14, fontWeight: "600", color: "#fff" },
});
