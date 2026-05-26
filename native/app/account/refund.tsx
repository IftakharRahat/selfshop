import { useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
  Image,
  TextInput,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

type EligibleRefundItem = {
  order_id: number;
  invoiceID: string;
  orderproduct_id: number;
  product_name: string;
  product_image?: string | null;
  quantity: number;
  product_price: number | string;
  delivery_date: string;
  expires_at: string;
  days_remaining: number;
  color?: string | null;
  size?: string | null;
};

type RefundClaim = {
  id: number;
  claim_number: string;
  status: string;
  created_at?: string | null;
  orderproduct?: { productName?: string | null };
  product?: { ProductName?: string | null };
};

function resolveImageUrl(path?: string | null): string | null {
  if (!path || path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

function formatDate(value?: string | null) {
  if (!value) return "N/A";
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? "N/A"
    : date.toLocaleDateString("en-BD", { day: "numeric", month: "short", year: "numeric" });
}

function formatMoney(value: number | string) {
  const amount = Number(value ?? 0);
  return `৳${(Number.isFinite(amount) ? amount : 0).toLocaleString("en-BD")}`;
}

function statusStyle(status: string) {
  switch (status) {
    case "approved":
      return { bg: "#DCFCE7", color: "#166534" };
    case "rejected":
      return { bg: "#FEE2E2", color: "#991B1B" };
    case "in_progress":
      return { bg: "#E0F2FE", color: "#075985" };
    case "closed":
      return { bg: "#F3F4F6", color: "#4B5563" };
    default:
      return { bg: "#FEF3C7", color: "#92400E" };
  }
}

function statusLabel(status: string) {
  return status.replace(/_/g, " ").replace(/\b\w/g, (letter) => letter.toUpperCase());
}

function makeUploadFile(asset: ImagePicker.ImagePickerAsset) {
  const mimeType = asset.mimeType || "image/jpeg";
  const ext = mimeType.includes("png") ? "png" : mimeType.includes("webp") ? "webp" : "jpg";
  const name = asset.fileName?.replace(/[^\w.-]/g, "_") || `refund_${Date.now()}.${ext}`;
  return { uri: asset.uri, type: mimeType, name } as any;
}

export default function RefundScreen() {
  const queryClient = useQueryClient();
  const [selectedItem, setSelectedItem] = useState<EligibleRefundItem | null>(null);
  const [message, setMessage] = useState("");
  const [imageAsset, setImageAsset] = useState<ImagePicker.ImagePickerAsset | null>(null);

  const eligibleQuery = useQuery({
    queryKey: ["refund-eligible"],
    queryFn: async () => {
      const { data } = await apiClient.get("/refund/eligible-orders");
      return (data?.data?.eligible_orders ?? []) as EligibleRefundItem[];
    },
  });

  const claimsQuery = useQuery({
    queryKey: ["refund-claims"],
    queryFn: async () => {
      const { data } = await apiClient.get("/refund/claims");
      return (data?.data?.claims ?? []) as RefundClaim[];
    },
  });

  const submitMutation = useMutation({
    mutationFn: async () => {
      if (!selectedItem) return null;
      const formData = new FormData();
      formData.append("orderproduct_id", String(selectedItem.orderproduct_id));
      formData.append("message", message.trim());
      if (imageAsset) formData.append("image", makeUploadFile(imageAsset));
      const { data } = await apiClient.post("/refund/claims", formData);
      if (data?.status === false) throw new Error(data?.message ?? "Failed to submit claim");
      return data;
    },
    onSuccess: () => {
      toast.success("Refund claim submitted");
      setSelectedItem(null);
      setMessage("");
      setImageAsset(null);
      queryClient.invalidateQueries({ queryKey: ["refund-eligible"] });
      queryClient.invalidateQueries({ queryKey: ["refund-claims"] });
    },
    onError: (error: any) => {
      toast.error(error?.response?.data?.message ?? error?.message ?? "Failed to submit claim");
    },
  });

  const pickImage = async () => {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) {
      toast.error("Image permission is required");
      return;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      allowsEditing: false,
      quality: 0.8,
    });
    if (!result.canceled) setImageAsset(result.assets[0]);
  };

  const refresh = () => {
    eligibleQuery.refetch();
    claimsQuery.refetch();
  };

  const isRefreshing = eligibleQuery.isRefetching || claimsQuery.isRefetching;
  const eligible = eligibleQuery.data ?? [];
  const claims = claimsQuery.data ?? [];

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Refund",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8F8" },
        }}
      />
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={ACCENT} />}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.sectionHeader}>
          <Text fontSize="$5" fontWeight="700" color="#1A1A2E">
            Eligible Products
          </Text>
          <Ionicons name="refresh-circle-outline" size={24} color={ACCENT} />
        </View>

        {eligibleQuery.isLoading ? (
          <ActivityIndicator color={ACCENT} style={{ marginVertical: 28 }} />
        ) : eligible.length === 0 ? (
          <View style={styles.emptyBox}>
            <Ionicons name="checkmark-done-circle-outline" size={42} color="#D1D5DB" />
            <Text fontSize="$3" color="#6B7280" mt="$2" style={{ textAlign: "center" }}>
              No delivered products are currently eligible.
            </Text>
          </View>
        ) : (
          <View style={styles.list}>
            {eligible.map((item) => {
              const imageUrl = resolveImageUrl(item.product_image);
              return (
                <View key={item.orderproduct_id} style={styles.eligibleCard}>
                  <View style={styles.productRow}>
                    {imageUrl ? <Image source={{ uri: imageUrl }} style={styles.productImage} /> : <View style={styles.imagePlaceholder} />}
                    <View style={styles.productInfo}>
                      <Text fontSize="$4" fontWeight="700" color="#1A1A2E" numberOfLines={2}>
                        {item.product_name}
                      </Text>
                      <Text fontSize="$2" color="#8E8E93">
                        Invoice {item.invoiceID}
                      </Text>
                      <Text fontSize="$2" color="#8E8E93">
                        Qty {item.quantity} · {formatMoney(item.product_price)}
                      </Text>
                      <Text fontSize="$2" fontWeight="700" color="#047857" mt="$1">
                        {item.days_remaining} days remaining
                      </Text>
                    </View>
                  </View>
                  <View style={styles.claimFooter}>
                    <Text fontSize="$2" color="#8E8E93">
                      Expires {formatDate(item.expires_at)}
                    </Text>
                    <Pressable style={styles.primarySmall} onPress={() => setSelectedItem(item)}>
                      <Text fontSize="$3" fontWeight="700" color="#fff">
                        Claim
                      </Text>
                    </Pressable>
                  </View>
                </View>
              );
            })}
          </View>
        )}

        {selectedItem && (
          <View style={styles.claimBox}>
            <View style={styles.claimBoxHeader}>
              <Text fontSize="$4" fontWeight="700" color="#1A1A2E" flex={1}>
                Claim {selectedItem.product_name}
              </Text>
              <Pressable onPress={() => setSelectedItem(null)}>
                <Ionicons name="close" size={22} color="#8E8E93" />
              </Pressable>
            </View>
            <TextInput
              value={message}
              onChangeText={setMessage}
              placeholder="Describe the issue"
              placeholderTextColor="#A1A1AA"
              style={styles.messageInput}
              multiline
              numberOfLines={5}
            />
            <Pressable style={styles.imageButton} onPress={pickImage}>
              <Ionicons name="image-outline" size={18} color={ACCENT} />
              <Text fontSize="$3" color={ACCENT} fontWeight="600">
                {imageAsset ? imageAsset.fileName ?? "Image selected" : "Attach image (optional)"}
              </Text>
            </Pressable>
            <Pressable
              style={[styles.primaryButton, (!message.trim() || submitMutation.isPending) && styles.disabledButton]}
              disabled={!message.trim() || submitMutation.isPending}
              onPress={() => submitMutation.mutate()}
            >
              {submitMutation.isPending ? <ActivityIndicator color="#fff" /> : <Text fontSize="$4" fontWeight="700" color="#fff">Submit Claim</Text>}
            </Pressable>
          </View>
        )}

        <View style={styles.sectionHeader}>
          <Text fontSize="$5" fontWeight="700" color="#1A1A2E">
            Claim History
          </Text>
        </View>
        {claimsQuery.isLoading ? (
          <ActivityIndicator color={ACCENT} style={{ marginVertical: 28 }} />
        ) : claims.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text fontSize="$3" color="#6B7280">
              No refund claims yet.
            </Text>
          </View>
        ) : (
          <View style={styles.list}>
            {claims.map((claim) => {
              const status = statusStyle(claim.status);
              return (
                <Pressable
                  key={claim.id}
                  style={({ pressed }) => [styles.claimCard, pressed && { opacity: 0.82 }]}
                  onPress={() => router.push({ pathname: "/account/refund-detail" as any, params: { id: String(claim.id) } })}
                >
                  <View style={styles.claimCardTop}>
                    <Text fontSize="$3" fontWeight="700" color="#1A1A2E">
                      {claim.claim_number}
                    </Text>
                    <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
                      <Text fontSize={11} fontWeight="700" style={{ color: status.color }}>
                        {statusLabel(claim.status)}
                      </Text>
                    </View>
                  </View>
                  <Text fontSize="$3" color="#6B7280" numberOfLines={1} mt="$1">
                    {claim.orderproduct?.productName ?? claim.product?.ProductName ?? "Product"}
                  </Text>
                  <Text fontSize="$2" color="#8E8E93" mt="$1">
                    Submitted {formatDate(claim.created_at)}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        )}
        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8F8" },
  content: { padding: 16, gap: 14 },
  sectionHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 4,
  },
  list: { gap: 12 },
  eligibleCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F0",
  },
  productRow: { flexDirection: "row", gap: 12 },
  productImage: { width: 72, height: 72, borderRadius: 12, backgroundColor: "#F3F4F6" },
  imagePlaceholder: { width: 72, height: 72, borderRadius: 12, backgroundColor: "#F3F4F6" },
  productInfo: { flex: 1 },
  claimFooter: {
    borderTopWidth: 1,
    borderTopColor: "#F3F4F6",
    marginTop: 12,
    paddingTop: 12,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  primarySmall: {
    backgroundColor: ACCENT,
    paddingHorizontal: 18,
    paddingVertical: 8,
    borderRadius: 10,
  },
  emptyBox: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 28,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    alignItems: "center",
  },
  claimBox: {
    backgroundColor: "#fff",
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    gap: 12,
  },
  claimBoxHeader: { flexDirection: "row", alignItems: "center", gap: 8 },
  messageInput: {
    minHeight: 120,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 14,
    padding: 12,
    textAlignVertical: "top",
    color: "#1A1A2E",
    backgroundColor: "#FAFAFA",
  },
  imageButton: {
    minHeight: 44,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#FBCFE8",
    backgroundColor: "#FDF2F8",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    paddingHorizontal: 12,
  },
  primaryButton: {
    minHeight: 50,
    borderRadius: 14,
    backgroundColor: ACCENT,
    alignItems: "center",
    justifyContent: "center",
  },
  disabledButton: { opacity: 0.55 },
  claimCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F0",
  },
  claimCardTop: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 8,
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
});
