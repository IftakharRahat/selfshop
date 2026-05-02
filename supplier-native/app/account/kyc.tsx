import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  ActivityIndicator,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

interface KycDocument {
  id: number;
  vendor_id: number;
  document_type: string;
  document_number?: string | null;
  status: "pending" | "approved" | "rejected";
  created_at: string;
}

const DOC_TYPES = [
  { value: "nid", label: "NID" },
  { value: "trade_license", label: "Trade License" },
  { value: "passport", label: "Passport" },
  { value: "driving_license", label: "Driving License" },
  { value: "tin_certificate", label: "TIN Certificate" },
  { value: "other", label: "Other" },
];

const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  approved: { bg: "#D1FAE5", text: "#065F46" },
  pending: { bg: "#FEF3C7", text: "#92400E" },
  rejected: { bg: "#FEE2E2", text: "#991B1B" },
};

export default function KycScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const [selectedType, setSelectedType] = useState("");
  const [docNumber, setDocNumber] = useState("");
  const [fileUri, setFileUri] = useState<string | null>(null);
  const [fileName, setFileName] = useState("");

  const { data, isLoading, refetch } = useQuery({
    queryKey: ["vendor-kyc"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/kyc-documents");
      return data?.data?.documents as KycDocument[];
    },
  });

  const submitMutation = useMutation({
    mutationFn: async () => {
      if (!selectedType) throw new Error("Select a document type");
      const formData = new FormData();
      formData.append("document_type", selectedType);
      if (docNumber) formData.append("document_number", docNumber);
      if (fileUri) {
        const name = fileUri.split("/").pop() ?? "document.jpg";
        formData.append("file", { uri: fileUri, name, type: "image/jpeg" } as any);
      }
      const { data } = await apiClient.post("/vendor/kyc-documents", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      toast.success("KYC document submitted");
      setSelectedType("");
      setDocNumber("");
      setFileUri(null);
      setFileName("");
      queryClient.invalidateQueries({ queryKey: ["vendor-kyc"] });
    },
    onError: (err: any) => {
      toast.error(err?.message ?? "Failed to submit");
    },
  });

  const pickFile = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.8,
    });
    if (!result.canceled && result.assets[0]) {
      setFileUri(result.assets[0].uri);
      setFileName(result.assets[0].uri.split("/").pop() ?? "document");
    }
  };

  const documents = data ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>KYC Documents</Text>
        <View style={{ width: 32 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Submit Form */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Submit New Document</Text>
          <Text style={styles.sectionSubtitle}>Add NID, trade license or other documents for verification.</Text>

          {/* Document Type */}
          <Text style={styles.fieldLabel}>Document Type *</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 14 }}>
            <View style={styles.chipRow}>
              {DOC_TYPES.map((dt) => (
                <TouchableOpacity
                  key={dt.value}
                  style={[styles.chip, selectedType === dt.value && styles.chipActive]}
                  onPress={() => setSelectedType(dt.value)}
                >
                  <Text style={[styles.chipText, selectedType === dt.value && styles.chipTextActive]}>
                    {dt.label}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>
          </ScrollView>

          {/* Document Number */}
          <View style={styles.inputGroup}>
            <Text style={styles.fieldLabel}>Document Number (optional)</Text>
            <TextInput
              style={styles.input}
              value={docNumber}
              onChangeText={setDocNumber}
              placeholder="Enter document number"
              placeholderTextColor="#9ca3af"
            />
          </View>

          {/* File picker */}
          <Text style={styles.fieldLabel}>Document File</Text>
          <TouchableOpacity style={styles.filePicker} onPress={pickFile}>
            <Ionicons name="cloud-upload-outline" size={20} color="#6b7280" />
            <Text style={styles.filePickerText}>
              {fileName || "Tap to select image"}
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.submitBtn, submitMutation.isPending && { opacity: 0.6 }]}
            onPress={() => submitMutation.mutate()}
            disabled={submitMutation.isPending || !selectedType}
            activeOpacity={0.8}
          >
            {submitMutation.isPending ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <Text style={styles.submitBtnText}>Submit KYC</Text>
            )}
          </TouchableOpacity>
        </View>

        {/* Submitted Documents */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Submitted Documents</Text>

          {isLoading ? (
            <ActivityIndicator color={BRAND.primary} />
          ) : documents.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="document-text-outline" size={36} color="#d1d5db" />
              <Text style={styles.emptyText}>No documents submitted yet</Text>
            </View>
          ) : (
            documents.map((doc) => {
              const sc = STATUS_COLORS[doc.status] ?? STATUS_COLORS.pending;
              const typeLabel = DOC_TYPES.find((dt) => dt.value === doc.document_type)?.label ?? doc.document_type;
              return (
                <View key={doc.id} style={styles.docRow}>
                  <View style={styles.docIcon}>
                    <Ionicons name="document-text" size={18} color={BRAND.primary} />
                  </View>
                  <View style={styles.docInfo}>
                    <Text style={styles.docType}>{typeLabel}</Text>
                    {doc.document_number && (
                      <Text style={styles.docNumber}>{doc.document_number}</Text>
                    )}
                    <Text style={styles.docDate}>
                      {new Date(doc.created_at).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" })}
                    </Text>
                  </View>
                  <View style={[styles.docStatus, { backgroundColor: sc.bg }]}>
                    <Text style={[styles.docStatusText, { color: sc.text }]}>{doc.status}</Text>
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
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 4 },
  sectionSubtitle: { fontSize: 12, color: "#6b7280", marginBottom: 16 },
  fieldLabel: { fontSize: 12, fontWeight: "600", color: "#374151", marginBottom: 6, marginLeft: 2 },
  chipRow: { flexDirection: "row", gap: 8, paddingRight: 16 },
  chip: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    backgroundColor: "#f3f4f6",
    borderWidth: 1,
    borderColor: "#e5e7eb",
  },
  chipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  chipText: { fontSize: 12, fontWeight: "500", color: "#6b7280" },
  chipTextActive: { color: "#fff" },
  inputGroup: { marginBottom: 14 },
  input: {
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 11,
    fontSize: 14,
    color: "#1a1a2e",
  },
  filePicker: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderStyle: "dashed",
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 14,
    marginBottom: 16,
  },
  filePickerText: { fontSize: 13, color: "#6b7280" },
  submitBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 10,
    paddingVertical: 13,
    alignItems: "center",
  },
  submitBtnText: { fontSize: 14, fontWeight: "600", color: "#fff" },
  emptyState: { alignItems: "center", paddingVertical: 24, gap: 8 },
  emptyText: { fontSize: 13, color: "#9ca3af" },
  docRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#f9fafb",
    gap: 10,
  },
  docIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: BRAND.primaryLight,
    alignItems: "center",
    justifyContent: "center",
  },
  docInfo: { flex: 1 },
  docType: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  docNumber: { fontSize: 11, color: "#6b7280", marginTop: 1 },
  docDate: { fontSize: 10, color: "#9ca3af", marginTop: 1 },
  docStatus: { paddingHorizontal: 8, paddingVertical: 3, borderRadius: 8 },
  docStatusText: { fontSize: 10, fontWeight: "600", textTransform: "capitalize" },
});
