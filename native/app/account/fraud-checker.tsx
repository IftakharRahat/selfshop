import { useCallback, useState } from "react";
import {
  View,
  StyleSheet,
  Pressable,
  TextInput,
  ActivityIndicator,
  Platform,
  Modal,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { KeyboardAwareScrollView, KeyboardStickyView } from "react-native-keyboard-controller";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

export default function FraudCheckerScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const insets = useSafeAreaInsets();

  /* ── State ── */
  const [inputValue, setInputValue] = useState("");
  const [phoneNumber, setPhoneNumber] = useState("");
  const [hasSearched, setHasSearched] = useState(false);
  const [showReportModal, setShowReportModal] = useState(false);
  const [reportPhone, setReportPhone] = useState("");
  const [reportMessage, setReportMessage] = useState("");

  /* ── Query: check fraud ── */
  const fraudQuery = useQuery({
    queryKey: ["check-fraud", phoneNumber],
    queryFn: async () => {
      const { data } = await apiClient.get(`/check-fraud?number=${phoneNumber}`);
      return data?.data ?? data ?? [];
    },
    enabled: !!phoneNumber,
  });

  /* ── Mutation: report fraud ── */
  const reportMutation = useMutation({
    mutationFn: async (body: { phone: string; message: string }) => {
      const { data } = await apiClient.post("/store-fraud-number", body);
      return data;
    },
    onSuccess: () => {
      toast.success("Fraud number reported successfully!");
      setShowReportModal(false);
      setReportPhone("");
      setReportMessage("");
      // Re-fetch if we were searching the same number
      if (phoneNumber) {
        queryClient.invalidateQueries({ queryKey: ["check-fraud", phoneNumber] });
      }
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? "Failed to report. Please try again.");
    },
  });

  /* ── Handlers ── */
  const handleSearch = () => {
    const trimmed = inputValue.trim();
    if (!trimmed) {
      toast.error("Please enter a phone number.");
      return;
    }
    setPhoneNumber(trimmed);
    setHasSearched(true);
  };

  const handleReport = () => {
    if (!reportPhone.trim() || reportPhone.trim().length < 6) {
      showDialog({ tone: "warning", title: "Check phone number", message: "Please enter a valid phone number with at least 6 digits." });
      return;
    }
    if (!reportMessage.trim()) {
      showDialog({ tone: "warning", title: "Details needed", message: "Please describe the fraud before submitting." });
      return;
    }
    reportMutation.mutate({ phone: reportPhone.trim(), message: reportMessage.trim() });
  };

  /* ── Derived ── */
  const fraudRecords: any[] = Array.isArray(fraudQuery.data) ? fraudQuery.data : [];
  const hasFraudRecords = hasSearched && !fraudQuery.isFetching && !fraudQuery.isError && fraudRecords.length > 0;
  const isClean = hasSearched && !fraudQuery.isFetching && !fraudQuery.isError && !!phoneNumber && fraudRecords.length === 0;
  const bottomInset = Math.max(insets.bottom, 16);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Fraud Checker",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <KeyboardAwareScrollView
          style={styles.container}
          contentContainerStyle={{ paddingBottom: bottomInset + 48 }}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
          keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
          bottomOffset={bottomInset + 24}
        >
          {/* ── Header Info ── */}
          <View style={styles.headerCard}>
            <View style={styles.headerIcon}>
              <Ionicons name="shield-checkmark-outline" size={28} color={ACCENT} />
            </View>
            <Text fontSize="$2" color="#6B7280" style={{ textAlign: "center" }} mt="$2">
              Check if a phone number has been reported as fraudulent before shipping your order.
            </Text>
          </View>

          {/* ── Search Section ── */}
          <View style={styles.section}>
            <Text style={styles.inputLabel}>Phone Number</Text>
            <View style={styles.searchRow}>
              <TextInput
                style={styles.searchInput}
                placeholder="Enter phone number"
                placeholderTextColor="#9CA3AF"
                keyboardType="phone-pad"
                value={inputValue}
                onChangeText={setInputValue}
                onSubmitEditing={handleSearch}
                returnKeyType="search"
              />
            </View>

            <Pressable
              style={({ pressed }) => [
                styles.searchButton,
                pressed && { opacity: 0.85 },
                fraudQuery.isFetching && { opacity: 0.6 },
              ]}
              onPress={handleSearch}
              disabled={fraudQuery.isFetching}
            >
              {fraudQuery.isFetching ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <Ionicons name="search" size={18} color="#fff" />
                  <Text fontSize="$4" fontWeight="bold" color="#fff">
                    Check
                  </Text>
                </>
              )}
            </Pressable>
          </View>

          {/* ── Results ── */}

          {/* Fraud records found */}
          {hasFraudRecords && (
            <View style={styles.section}>
              <View style={styles.dangerHeader}>
                <Ionicons name="warning" size={18} color="#DC2626" />
                <Text style={styles.dangerHeaderText}>
                  ⚠ {fraudRecords.length} Fraud Record{fraudRecords.length > 1 ? "s" : ""} Found
                </Text>
              </View>

              {fraudRecords.map((item: any, index: number) => (
                <View key={item.id ?? index} style={styles.fraudCard}>
                  <View style={styles.fraudCardTop}>
                    <Text style={styles.fraudPhone}>📱 {item.phone}</Text>
                    <View style={styles.fraudBadge}>
                      <Text style={styles.fraudBadgeText}>
                        {item.status || "Reported"}
                      </Text>
                    </View>
                  </View>
                  <Text style={styles.fraudMessage} numberOfLines={3}>
                    {item.message || "No details provided"}
                  </Text>
                  <Text style={styles.fraudDate}>
                    Reported on: {item.created_at ? new Date(item.created_at).toLocaleDateString("en-US", {
                      year: "numeric",
                      month: "short",
                      day: "numeric",
                    }) : "Unknown"}
                  </Text>
                </View>
              ))}
            </View>
          )}

          {/* Clean / no fraud */}
          {isClean && (
            <View style={styles.section}>
              <View style={styles.safeCard}>
                <Ionicons name="shield-checkmark" size={40} color="#059669" />
                <Text fontSize="$4" fontWeight="700" color="#059669" mt="$2">
                  No Fraud Record Found
                </Text>
                <Text fontSize="$2" color="#059669" mt="$1" style={{ textAlign: "center" }}>
                  The phone number{" "}
                  <Text fontWeight="700">{phoneNumber}</Text>
                  {" "}has not been reported as fraudulent.
                </Text>
              </View>
            </View>
          )}

          {/* Error */}
          {fraudQuery.isError && hasSearched && (
            <View style={styles.section}>
              <View style={styles.errorCard}>
                <Ionicons name="alert-circle" size={20} color="#DC2626" />
                <Text fontSize="$3" color="#DC2626" ml="$2">
                  Failed to check this number. Please try again.
                </Text>
              </View>
            </View>
          )}

          {/* ── Report Button ── */}
          <View style={styles.section}>
            <Pressable
              style={({ pressed }) => [
                styles.reportButton,
                pressed && { opacity: 0.85 },
              ]}
              onPress={() => setShowReportModal(true)}
            >
              <Ionicons name="flag-outline" size={18} color={ACCENT} />
              <Text fontSize="$3" fontWeight="700" color={ACCENT}>
                Report Fraud Number
              </Text>
            </Pressable>
          </View>

          <View style={{ height: 40 }} />
      </KeyboardAwareScrollView>

      {/* ── Report Modal ── */}
      <Modal
        visible={showReportModal}
        animationType="slide"
        transparent
        onRequestClose={() => setShowReportModal(false)}
      >
        <Pressable
          style={styles.modalOverlay}
          onPress={() => setShowReportModal(false)}
        >
          <KeyboardStickyView offset={{ closed: 0, opened: -8 }}>
          <Pressable style={styles.modalContent} onPress={() => {}}>
            <View style={styles.modalHandle} />
            <Text fontSize="$5" fontWeight="bold" color="#1A1A2E" mb="$4">
              Report Fraud Number
            </Text>

            <Text style={styles.inputLabel}>Phone Number</Text>
            <TextInput
              style={styles.modalInput}
              placeholder="e.g. 01XXXXXXXXX"
              placeholderTextColor="#9CA3AF"
              keyboardType="phone-pad"
              value={reportPhone}
              onChangeText={setReportPhone}
            />

            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Reason / Details</Text>
            <TextInput
              style={styles.modalTextArea}
              placeholder="Describe what happened (e.g. scam order, fake payment, etc.)"
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={3}
              textAlignVertical="top"
              value={reportMessage}
              onChangeText={setReportMessage}
            />

            <Pressable
              style={({ pressed }) => [
                styles.submitButton,
                pressed && { opacity: 0.85 },
                reportMutation.isPending && { opacity: 0.6 },
              ]}
              onPress={handleReport}
              disabled={reportMutation.isPending}
            >
              {reportMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Text fontSize="$4" fontWeight="bold" color="#fff">
                  Submit Report
                </Text>
              )}
            </Pressable>

            <Pressable
              style={styles.cancelButton}
              onPress={() => setShowReportModal(false)}
            >
              <Text fontSize="$3" fontWeight="600" color="#6B7280">
                Cancel
              </Text>
            </Pressable>
          </Pressable>
          </KeyboardStickyView>
        </Pressable>
      </Modal>
      <AppDialog state={dialog} onClose={closeDialog} />
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  /* ── Header ── */
  headerCard: {
    alignItems: "center",
    padding: 24,
    paddingBottom: 16,
  },
  headerIcon: {
    width: 56,
    height: 56,
    borderRadius: 20,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },

  /* ── Section ── */
  section: {
    paddingHorizontal: 16,
    marginTop: 8,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 6,
  },

  /* ── Search ── */
  searchRow: {
    flexDirection: "row",
    gap: 8,
  },
  searchInput: {
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
  searchButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: ACCENT,
    borderRadius: 14,
    height: 50,
    marginTop: 12,
  },

  /* ── Danger / Fraud Results ── */
  dangerHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 12,
  },
  dangerHeaderText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#DC2626",
    textTransform: "uppercase",
    letterSpacing: 0.5,
  },
  fraudCard: {
    backgroundColor: "#FEF2F2",
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  fraudCardTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  fraudPhone: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  fraudBadge: {
    backgroundColor: "#FEE2E2",
    paddingHorizontal: 10,
    paddingVertical: 3,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  fraudBadgeText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#991B1B",
  },
  fraudMessage: {
    fontSize: 13,
    color: "#374151",
    lineHeight: 18,
    marginBottom: 6,
  },
  fraudDate: {
    fontSize: 11,
    color: "#9CA3AF",
  },

  /* ── Safe Card ── */
  safeCard: {
    backgroundColor: "#ECFDF5",
    borderRadius: 16,
    padding: 24,
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#A7F3D0",
  },

  /* ── Error Card ── */
  errorCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FEF2F2",
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: "#FECACA",
  },

  /* ── Report Button ── */
  reportButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: ACCENT,
    backgroundColor: "#FDF2F8",
    marginTop: 16,
  },

  /* ── Modal ── */
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.4)",
    justifyContent: "flex-end",
  },
  modalContent: {
    backgroundColor: "#fff",
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    paddingBottom: 40,
  },
  modalHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: "#E5E7EB",
    alignSelf: "center",
    marginBottom: 20,
  },
  modalInput: {
    height: 48,
    backgroundColor: "#F9FAFB",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 14,
    fontSize: 15,
    color: "#1A1A2E",
  },
  modalTextArea: {
    backgroundColor: "#F9FAFB",
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
  cancelButton: {
    alignItems: "center",
    paddingVertical: 12,
    marginTop: 4,
  },
});
