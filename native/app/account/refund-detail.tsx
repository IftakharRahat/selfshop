import { useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  TextInput,
  Image,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams } from "expo-router";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

type RefundClaimMessage = {
  id: number;
  sender_type: "user" | "admin";
  message: string;
  attachment_path?: string | null;
  created_at?: string | null;
};

type RefundClaim = {
  id: number;
  claim_number: string;
  status: string;
  message: string;
  image_path?: string | null;
  created_at?: string | null;
  delivery_date?: string | null;
  expires_at?: string | null;
  warranty_days: number;
  order?: { invoiceID?: string | null };
  orderproduct?: { productName?: string | null; quantity?: number | null; color?: string | null; size?: string | null };
  product?: { ProductName?: string | null; ViewProductImage?: string | null };
  messages: RefundClaimMessage[];
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
  const name = asset.fileName?.replace(/[^\w.-]/g, "_") || `refund_reply_${Date.now()}.${ext}`;
  return { uri: asset.uri, type: mimeType, name } as any;
}

export default function RefundDetailScreen() {
  const params = useLocalSearchParams<{ id?: string }>();
  const claimId = Number(params.id);
  const queryClient = useQueryClient();
  const [message, setMessage] = useState("");
  const [imageAsset, setImageAsset] = useState<ImagePicker.ImagePickerAsset | null>(null);

  const claimQuery = useQuery({
    queryKey: ["refund-claim", claimId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/refund/claims/${claimId}`);
      return data?.data?.claim as RefundClaim;
    },
    enabled: Number.isFinite(claimId) && claimId > 0,
  });

  const replyMutation = useMutation({
    mutationFn: async () => {
      const formData = new FormData();
      formData.append("message", message.trim());
      if (imageAsset) formData.append("image", makeUploadFile(imageAsset));
      const { data } = await apiClient.post(`/refund/claims/${claimId}/messages`, formData);
      if (data?.status === false) throw new Error(data?.message ?? "Failed to send reply");
      return data;
    },
    onSuccess: () => {
      toast.success("Reply sent");
      setMessage("");
      setImageAsset(null);
      queryClient.invalidateQueries({ queryKey: ["refund-claim", claimId] });
      queryClient.invalidateQueries({ queryKey: ["refund-claims"] });
    },
    onError: (error: any) => {
      toast.error(error?.response?.data?.message ?? error?.message ?? "Failed to send reply");
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

  const claim = claimQuery.data;
  const productImage = resolveImageUrl(claim?.product?.ViewProductImage);
  const initialImage = resolveImageUrl(claim?.image_path);
  const status = statusStyle(claim?.status ?? "pending");

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: claim?.claim_number ?? "Refund Detail",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8F8" },
        }}
      />
      {claimQuery.isLoading ? (
        <View style={styles.loading}>
          <ActivityIndicator color={ACCENT} size="large" />
        </View>
      ) : !claim ? (
        <View style={styles.loading}>
          <Text color="#8E8E93">Refund claim not found.</Text>
        </View>
      ) : (
        <ScrollView style={styles.container} contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
          <View style={styles.summaryCard}>
            <View style={styles.summaryTop}>
              {productImage ? <Image source={{ uri: productImage }} style={styles.productImage} /> : <View style={styles.imagePlaceholder} />}
              <View style={{ flex: 1 }}>
                <Text fontSize="$5" fontWeight="700" color="#1A1A2E" numberOfLines={2}>
                  {claim.orderproduct?.productName ?? claim.product?.ProductName ?? "Product"}
                </Text>
                <Text fontSize="$2" color="#8E8E93" mt="$1">
                  Invoice {claim.order?.invoiceID ?? "N/A"}
                </Text>
                <Text fontSize="$2" color="#8E8E93">
                  Delivered {formatDate(claim.delivery_date)} · {claim.warranty_days} days
                </Text>
              </View>
            </View>
            <View style={styles.summaryFooter}>
              <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
                <Text fontSize={11} fontWeight="700" style={{ color: status.color }}>
                  {statusLabel(claim.status)}
                </Text>
              </View>
              <Text fontSize="$2" color="#8E8E93">
                Expires {formatDate(claim.expires_at)}
              </Text>
            </View>
            <View style={styles.originalBox}>
              <Text fontSize="$2" fontWeight="700" color="#6B7280" mb="$1">
                Original message
              </Text>
              <Text fontSize="$3" color="#1F2937">
                {claim.message}
              </Text>
              {initialImage ? <Image source={{ uri: initialImage }} style={styles.attachmentImage} /> : null}
            </View>
          </View>

          <Text fontSize="$5" fontWeight="700" color="#1A1A2E">
            Conversation
          </Text>
          <View style={styles.messages}>
            {claim.messages.map((item) => {
              const image = resolveImageUrl(item.attachment_path);
              const isAdmin = item.sender_type === "admin";
              return (
                <View key={item.id} style={[styles.messageCard, isAdmin ? styles.adminMessage : styles.userMessage]}>
                  <View style={styles.messageMeta}>
                    <Text fontSize="$2" fontWeight="700" color={isAdmin ? "#075985" : "#6B7280"}>
                      {isAdmin ? "Admin" : "You"}
                    </Text>
                    <Text fontSize={11} color="#9CA3AF">
                      {formatDate(item.created_at)}
                    </Text>
                  </View>
                  <Text fontSize="$3" color="#1F2937">
                    {item.message}
                  </Text>
                  {image ? <Image source={{ uri: image }} style={styles.attachmentImage} /> : null}
                </View>
              );
            })}
          </View>

          {claim.status !== "closed" && (
            <View style={styles.replyBox}>
              <TextInput
                value={message}
                onChangeText={setMessage}
                placeholder="Write a reply"
                placeholderTextColor="#A1A1AA"
                style={styles.messageInput}
                multiline
                numberOfLines={4}
              />
              <Pressable style={styles.imageButton} onPress={pickImage}>
                <Ionicons name="image-outline" size={18} color={ACCENT} />
                <Text fontSize="$3" color={ACCENT} fontWeight="600">
                  {imageAsset ? imageAsset.fileName ?? "Image selected" : "Attach image (optional)"}
                </Text>
              </Pressable>
              <Pressable
                style={[styles.primaryButton, (!message.trim() || replyMutation.isPending) && styles.disabledButton]}
                disabled={!message.trim() || replyMutation.isPending}
                onPress={() => replyMutation.mutate()}
              >
                {replyMutation.isPending ? <ActivityIndicator color="#fff" /> : <Text fontSize="$4" fontWeight="700" color="#fff">Send Reply</Text>}
              </Pressable>
            </View>
          )}
          <View style={{ height: 36 }} />
        </ScrollView>
      )}
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8F8" },
  loading: { flex: 1, alignItems: "center", justifyContent: "center", backgroundColor: "#F8F8F8" },
  content: { padding: 16, gap: 14 },
  summaryCard: {
    backgroundColor: "#fff",
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    gap: 12,
  },
  summaryTop: { flexDirection: "row", gap: 12 },
  productImage: { width: 74, height: 74, borderRadius: 14, backgroundColor: "#F3F4F6" },
  imagePlaceholder: { width: 74, height: 74, borderRadius: 14, backgroundColor: "#F3F4F6" },
  summaryFooter: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", gap: 8 },
  statusBadge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 12 },
  originalBox: { backgroundColor: "#FAFAFA", borderRadius: 14, padding: 12, borderWidth: 1, borderColor: "#F3F4F6" },
  messages: { gap: 10 },
  messageCard: { borderRadius: 16, padding: 14, borderWidth: 1 },
  adminMessage: { backgroundColor: "#F0F9FF", borderColor: "#BAE6FD" },
  userMessage: { backgroundColor: "#fff", borderColor: "#F0F0F0" },
  messageMeta: { flexDirection: "row", justifyContent: "space-between", marginBottom: 6 },
  attachmentImage: { marginTop: 10, width: 112, height: 112, borderRadius: 12, backgroundColor: "#F3F4F6" },
  replyBox: {
    backgroundColor: "#fff",
    borderRadius: 18,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F0",
    gap: 12,
  },
  messageInput: {
    minHeight: 104,
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
});
