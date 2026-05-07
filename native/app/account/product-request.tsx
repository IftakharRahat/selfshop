import { useCallback, useState } from "react";
import {
  View,
  ScrollView,
  FlatList,
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
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

const QUANTITY_OPTIONS = ["1", "2", "3", "4", "5", "10", "20", "50", "100+"];

const STATUS_STYLES: Record<string, { bg: string; text: string }> = {
  Paid: { bg: "#D1FAE5", text: "#065F46" },
  Approved: { bg: "#D1FAE5", text: "#065F46" },
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
};

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

export default function ProductRequestScreen() {
  const queryClient = useQueryClient();

  /* ── Form State ── */
  const [productName, setProductName] = useState("");
  const [description, setDescription] = useState("");
  const [quantity, setQuantity] = useState("1");
  const [selectedImage, setSelectedImage] = useState<ImagePicker.ImagePickerAsset | null>(null);

  /* ── Queries ── */
  const listQuery = useQuery({
    queryKey: ["product-request-list"],
    queryFn: async () => {
      const { data } = await apiClient.get("/request-product-list");
      return data?.data ?? data ?? [];
    },
  });

  /* ── Mutation ── */
  const createMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data } = await apiClient.post("/give-product-request", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      toast.success("Product request submitted!");
      setProductName("");
      setDescription("");
      setQuantity("1");
      setSelectedImage(null);
      queryClient.invalidateQueries({ queryKey: ["product-request-list"] });
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? "Failed to submit request.");
    },
  });

  const requestList: any[] = Array.isArray(listQuery.data) ? listQuery.data : [];

  /* ── Image Picker ── */
  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.8,
    });

    if (!result.canceled && result.assets[0]) {
      setSelectedImage(result.assets[0]);
    }
  };

  /* ── Submit ── */
  const handleSubmit = () => {
    if (!productName.trim()) {
      Alert.alert("Required", "Please enter a product name.");
      return;
    }
    if (!description.trim()) {
      Alert.alert("Required", "Please enter a description.");
      return;
    }

    const formData = new FormData();
    formData.append("p_name", productName.trim());
    formData.append("p_quantity", quantity);
    formData.append("p_description", description.trim());

    if (selectedImage) {
      const uri = selectedImage.uri;
      const ext = uri.split(".").pop() ?? "jpg";
      formData.append("attachment", {
        uri,
        type: `image/${ext}`,
        name: `product_request.${ext}`,
      } as any);
    }

    createMutation.mutate(formData);
  };

  /* ── Refresh ── */
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["product-request-list"] });
  }, [queryClient]);

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Product Request",
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
            <RefreshControl
              refreshing={listQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          keyboardShouldPersistTaps="handled"
        >
          {/* ── Request Form ── */}
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>Request a Product</Text>
            <Text fontSize="$2" color="#6B7280" mb="$3">
              Can't find a product? Submit a request and we'll try to add it.
            </Text>

            {/* Product Name */}
            <Text style={styles.inputLabel}>Product Name *</Text>
            <TextInput
              style={styles.input}
              placeholder="Enter the product name"
              placeholderTextColor="#9CA3AF"
              value={productName}
              onChangeText={setProductName}
            />

            {/* Description */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Description *</Text>
            <TextInput
              style={styles.textArea}
              placeholder="Describe the product you need..."
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={3}
              textAlignVertical="top"
              value={description}
              onChangeText={setDescription}
            />

            {/* Quantity */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Quantity</Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.qtyRow}
            >
              {QUANTITY_OPTIONS.map((opt) => {
                const isActive = opt === quantity;
                return (
                  <Pressable
                    key={opt}
                    style={[styles.qtyChip, isActive && styles.qtyChipActive]}
                    onPress={() => setQuantity(opt)}
                  >
                    <Text
                      style={[styles.qtyChipText, isActive && { color: "#fff" }]}
                    >
                      {opt}
                    </Text>
                  </Pressable>
                );
              })}
            </ScrollView>

            {/* Image Picker */}
            <Text style={[styles.inputLabel, { marginTop: 16 }]}>Product Image (optional)</Text>
            <Pressable
              style={({ pressed }) => [
                styles.imagePicker,
                pressed && { opacity: 0.8 },
              ]}
              onPress={pickImage}
            >
              {selectedImage ? (
                <Image
                  source={{ uri: selectedImage.uri }}
                  style={styles.previewImage}
                  resizeMode="contain"
                />
              ) : (
                <View style={styles.imagePickerContent}>
                  <Ionicons name="camera-outline" size={28} color="#9CA3AF" />
                  <Text fontSize="$2" color="#9CA3AF" mt="$1">
                    Tap to upload image
                  </Text>
                </View>
              )}
            </Pressable>

            {/* Submit */}
            <Pressable
              style={({ pressed }) => [
                styles.submitButton,
                pressed && { opacity: 0.85 },
                createMutation.isPending && { opacity: 0.6 },
              ]}
              onPress={handleSubmit}
              disabled={createMutation.isPending}
            >
              {createMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <Ionicons name="cloud-upload-outline" size={18} color="#fff" />
                  <Text fontSize="$4" fontWeight="bold" color="#fff">
                    Submit Request
                  </Text>
                </>
              )}
            </Pressable>
          </View>

          {/* ── Previous Requests ── */}
          <View style={styles.section}>
            <Text style={styles.sectionTitle}>Your Requests</Text>

            {listQuery.isLoading ? (
              <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 20 }} />
            ) : requestList.length === 0 ? (
              <View style={styles.emptyState}>
                <Ionicons name="document-text-outline" size={40} color="#D1D5DB" />
                <Text fontSize="$3" color="#9CA3AF" mt="$2">
                  No requests yet
                </Text>
              </View>
            ) : (
              requestList.map((item: any) => {
                const statusStyle = STATUS_STYLES[item.status] ?? STATUS_STYLES.Pending;
                const imageUri = resolveImageUrl(item.attachment);

                return (
                  <View key={item.id} style={styles.requestCard}>
                    <View style={styles.requestRow}>
                      {imageUri ? (
                        <Image
                          source={{ uri: imageUri }}
                          style={styles.requestImage}
                          resizeMode="cover"
                        />
                      ) : (
                        <View style={[styles.requestImage, styles.requestImagePlaceholder]}>
                          <Ionicons name="image-outline" size={20} color="#D1D5DB" />
                        </View>
                      )}
                      <View style={{ flex: 1 }}>
                        <View style={styles.requestTopRow}>
                          <Text style={styles.requestName} numberOfLines={1}>
                            {item.p_name}
                          </Text>
                          <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
                            <Text style={[styles.statusText, { color: statusStyle.text }]}>
                              {item.status}
                            </Text>
                          </View>
                        </View>
                        {item.p_description && (
                          <Text style={styles.requestDesc} numberOfLines={2}>
                            {item.p_description}
                          </Text>
                        )}
                        <View style={styles.requestBottomRow}>
                          <Text style={styles.requestMeta}>Qty: {item.p_quantity || "—"}</Text>
                          <Text style={styles.requestMeta}>
                            {item.created_at ? new Date(item.created_at).toLocaleDateString() : ""}
                          </Text>
                        </View>
                      </View>
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

  /* ── Form ── */
  formCard: {
    margin: 16,
    padding: 20,
    backgroundColor: "#fff",
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  formTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 4,
  },
  inputLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 6,
  },
  input: {
    height: 48,
    backgroundColor: "#F9FAFB",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 14,
    fontSize: 15,
    color: "#1A1A2E",
  },
  textArea: {
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

  /* ── Quantity ── */
  qtyRow: {
    gap: 8,
    paddingVertical: 4,
  },
  qtyChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    backgroundColor: "#fff",
  },
  qtyChipActive: {
    borderColor: ACCENT,
    backgroundColor: ACCENT,
  },
  qtyChipText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
  },

  /* ── Image Picker ── */
  imagePicker: {
    width: "100%",
    height: 120,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderStyle: "dashed",
    backgroundColor: "#F9FAFB",
    overflow: "hidden",
  },
  imagePickerContent: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  previewImage: {
    width: "100%",
    height: "100%",
  },

  /* ── Submit ── */
  submitButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: ACCENT,
    borderRadius: 14,
    height: 50,
    marginTop: 20,
  },

  /* ── Requests List ── */
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
  emptyState: {
    alignItems: "center",
    paddingVertical: 32,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },

  /* ── Request Card ── */
  requestCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  requestRow: {
    flexDirection: "row",
    gap: 12,
  },
  requestImage: {
    width: 48,
    height: 48,
    borderRadius: 10,
    backgroundColor: "#F5F5FA",
  },
  requestImagePlaceholder: {
    justifyContent: "center",
    alignItems: "center",
  },
  requestTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  requestName: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1A1A2E",
    flex: 1,
    marginRight: 8,
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
  requestDesc: {
    fontSize: 12,
    color: "#6B7280",
    lineHeight: 17,
    marginBottom: 4,
  },
  requestBottomRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  requestMeta: {
    fontSize: 11,
    color: "#9CA3AF",
  },
});
