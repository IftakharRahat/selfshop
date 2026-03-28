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
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

interface VariantSize {
  id: number;
  varient_id: number;
  size_name: string;
  qty: number;
  price: number | null;
  status: string;
  bulk_prices?: { id: number; min_qty: number; max_qty: number | null; bulk_price: number }[];
}

interface Variant {
  id: number;
  product_id: number;
  title: string;
  color_name?: string | null;
  color_code?: string | null;
  qty: number;
  price: number;
  status: string;
  sizes: VariantSize[];
}

export default function VariantsScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id: string }>();
  const productId = Number(params.id);
  const queryClient = useQueryClient();

  // New variant form
  const [showForm, setShowForm] = useState(false);
  const [title, setTitle] = useState("");
  const [colorName, setColorName] = useState("");
  const [colorCode, setColorCode] = useState("");
  const [price, setPrice] = useState("");
  const [qty, setQty] = useState("");

  // New size form (per variant)
  const [sizeFormFor, setSizeFormFor] = useState<number | null>(null);
  const [sizeName, setSizeName] = useState("");
  const [sizeQty, setSizeQty] = useState("");
  const [sizePrice, setSizePrice] = useState("");

  const { data, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-variants", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/products/${productId}/variants`);
      return data?.data?.variants as Variant[];
    },
  });

  const createVariantMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/products/${productId}/variants`, {
        title: title || "Default",
        color_name: colorName || undefined,
        color_code: colorCode || undefined,
        price: Number(price) || 0,
        qty: Number(qty) || 0,
      });
    },
    onSuccess: () => {
      toast.success("Variant created");
      setShowForm(false);
      setTitle(""); setColorName(""); setColorCode(""); setPrice(""); setQty("");
      queryClient.invalidateQueries({ queryKey: ["vendor-variants", productId] });
    },
    onError: () => toast.error("Failed to create variant"),
  });

  const deleteVariantMutation = useMutation({
    mutationFn: async (variantId: number) => {
      await apiClient.delete(`/vendor/products/${productId}/variants/${variantId}`);
    },
    onSuccess: () => {
      toast.success("Variant deleted");
      queryClient.invalidateQueries({ queryKey: ["vendor-variants", productId] });
    },
  });

  const createSizeMutation = useMutation({
    mutationFn: async (variantId: number) => {
      await apiClient.post(`/vendor/products/${productId}/variants/${variantId}/sizes`, {
        size_name: sizeName,
        qty: Number(sizeQty) || 0,
        price: sizePrice ? Number(sizePrice) : null,
      });
    },
    onSuccess: () => {
      toast.success("Size added");
      setSizeFormFor(null);
      setSizeName(""); setSizeQty(""); setSizePrice("");
      queryClient.invalidateQueries({ queryKey: ["vendor-variants", productId] });
    },
    onError: () => toast.error("Failed to add size"),
  });

  const deleteSizeMutation = useMutation({
    mutationFn: async ({ variantId, sizeId }: { variantId: number; sizeId: number }) => {
      await apiClient.delete(`/vendor/products/${productId}/variants/${variantId}/sizes/${sizeId}`);
    },
    onSuccess: () => {
      toast.success("Size deleted");
      queryClient.invalidateQueries({ queryKey: ["vendor-variants", productId] });
    },
  });

  const variants = data ?? [];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Variants</Text>
        <TouchableOpacity onPress={() => setShowForm(!showForm)}>
          <Ionicons name={showForm ? "close-circle" : "add-circle"} size={24} color={BRAND.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
      >
        {/* New Variant Form */}
        {showForm && (
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>New Variant</Text>
            <TextInput style={styles.input} value={title} onChangeText={setTitle} placeholder="Variant title (e.g. Color)" placeholderTextColor="#9ca3af" />
            <View style={styles.row}>
              <TextInput style={[styles.input, { flex: 1 }]} value={colorName} onChangeText={setColorName} placeholder="Color name" placeholderTextColor="#9ca3af" />
              <TextInput style={[styles.input, { width: 80 }]} value={colorCode} onChangeText={setColorCode} placeholder="#hex" placeholderTextColor="#9ca3af" />
            </View>
            <View style={styles.row}>
              <TextInput style={[styles.input, { flex: 1 }]} value={price} onChangeText={setPrice} placeholder="Price" keyboardType="numeric" placeholderTextColor="#9ca3af" />
              <TextInput style={[styles.input, { flex: 1 }]} value={qty} onChangeText={setQty} placeholder="Qty" keyboardType="numeric" placeholderTextColor="#9ca3af" />
            </View>
            <TouchableOpacity
              style={[styles.submitBtn, createVariantMutation.isPending && { opacity: 0.6 }]}
              onPress={() => createVariantMutation.mutate()}
              disabled={createVariantMutation.isPending}
            >
              {createVariantMutation.isPending ? <ActivityIndicator color="#fff" size="small" /> : <Text style={styles.submitBtnText}>Add Variant</Text>}
            </TouchableOpacity>
          </View>
        )}

        {isLoading ? (
          <ActivityIndicator size="large" color={BRAND.primary} style={{ marginTop: 40 }} />
        ) : variants.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="color-palette-outline" size={48} color="#d1d5db" />
            <Text style={styles.emptyText}>No variants yet</Text>
            <Text style={styles.emptySubtext}>Add variants like colors, materials, etc.</Text>
          </View>
        ) : (
          variants.map((variant) => (
            <View key={variant.id} style={styles.variantCard}>
              {/* Variant Header */}
              <View style={styles.variantHeader}>
                <View style={styles.variantTitleRow}>
                  {variant.color_code && (
                    <View style={[styles.colorDot, { backgroundColor: variant.color_code }]} />
                  )}
                  <View>
                    <Text style={styles.variantTitle}>{variant.title}</Text>
                    {variant.color_name && <Text style={styles.variantColor}>{variant.color_name}</Text>}
                  </View>
                </View>
                <TouchableOpacity
                  onPress={() => Alert.alert("Delete Variant", "Are you sure?", [
                    { text: "Cancel", style: "cancel" },
                    { text: "Delete", style: "destructive", onPress: () => deleteVariantMutation.mutate(variant.id) },
                  ])}
                >
                  <Ionicons name="trash-outline" size={18} color="#ef4444" />
                </TouchableOpacity>
              </View>

              <View style={styles.variantMeta}>
                <Text style={styles.variantMetaText}>৳{variant.price}</Text>
                <Text style={styles.variantMetaText}>Qty: {variant.qty}</Text>
                <View style={[styles.statusPill, { backgroundColor: variant.status === "Active" ? "#D1FAE5" : "#FEE2E2" }]}>
                  <Text style={[styles.statusPillText, { color: variant.status === "Active" ? "#059669" : "#DC2626" }]}>{variant.status}</Text>
                </View>
              </View>

              {/* Sizes */}
              {variant.sizes.length > 0 && (
                <View style={styles.sizesSection}>
                  <Text style={styles.sizesTitle}>Sizes</Text>
                  {variant.sizes.map((size) => (
                    <View key={size.id} style={styles.sizeRow}>
                      <View style={styles.sizeInfo}>
                        <Text style={styles.sizeName}>{size.size_name}</Text>
                        <Text style={styles.sizeDetail}>
                          {size.price ? `৳${size.price}` : "—"} · Qty: {size.qty}
                        </Text>
                        {size.bulk_prices && size.bulk_prices.length > 0 && (
                          <Text style={styles.sizeBulk}>
                            {size.bulk_prices.length} bulk price{size.bulk_prices.length > 1 ? "s" : ""}
                          </Text>
                        )}
                      </View>
                      <TouchableOpacity onPress={() => deleteSizeMutation.mutate({ variantId: variant.id, sizeId: size.id })}>
                        <Ionicons name="close-circle-outline" size={18} color="#9ca3af" />
                      </TouchableOpacity>
                    </View>
                  ))}
                </View>
              )}

              {/* Add Size */}
              {sizeFormFor === variant.id ? (
                <View style={styles.sizeForm}>
                  <View style={styles.row}>
                    <TextInput style={[styles.input, { flex: 1 }]} value={sizeName} onChangeText={setSizeName} placeholder="Size name" placeholderTextColor="#9ca3af" />
                    <TextInput style={[styles.input, { width: 60 }]} value={sizeQty} onChangeText={setSizeQty} placeholder="Qty" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                    <TextInput style={[styles.input, { width: 70 }]} value={sizePrice} onChangeText={setSizePrice} placeholder="Price" keyboardType="numeric" placeholderTextColor="#9ca3af" />
                  </View>
                  <View style={[styles.row, { gap: 8 }]}>
                    <TouchableOpacity style={[styles.submitBtn, { flex: 1 }]} onPress={() => createSizeMutation.mutate(variant.id)}>
                      <Text style={styles.submitBtnText}>Add</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={[styles.cancelBtn, { flex: 1 }]} onPress={() => setSizeFormFor(null)}>
                      <Text style={styles.cancelBtnText}>Cancel</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ) : (
                <TouchableOpacity style={styles.addSizeBtn} onPress={() => { setSizeFormFor(variant.id); setSizeName(""); setSizeQty(""); setSizePrice(""); }}>
                  <Ionicons name="add-circle-outline" size={16} color={BRAND.primary} />
                  <Text style={styles.addSizeBtnText}>Add Size</Text>
                </TouchableOpacity>
              )}
            </View>
          ))
        )}

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
  formCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: BRAND.primaryLight,
    gap: 10,
  },
  formTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 4 },
  row: { flexDirection: "row", gap: 8 },
  input: {
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: "#1a1a2e",
  },
  submitBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 8,
    paddingVertical: 11,
    alignItems: "center",
  },
  submitBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  cancelBtn: {
    backgroundColor: "#f3f4f6",
    borderRadius: 8,
    paddingVertical: 11,
    alignItems: "center",
  },
  cancelBtnText: { fontSize: 13, fontWeight: "500", color: "#6b7280" },
  emptyState: { alignItems: "center", paddingVertical: 60, gap: 8 },
  emptyText: { fontSize: 15, fontWeight: "600", color: "#9ca3af" },
  emptySubtext: { fontSize: 12, color: "#d1d5db" },
  variantCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  variantHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 8 },
  variantTitleRow: { flexDirection: "row", alignItems: "center", gap: 10 },
  colorDot: { width: 20, height: 20, borderRadius: 10, borderWidth: 1, borderColor: "#e5e7eb" },
  variantTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e" },
  variantColor: { fontSize: 11, color: "#6b7280", marginTop: 1 },
  variantMeta: { flexDirection: "row", alignItems: "center", gap: 12, marginBottom: 8 },
  variantMetaText: { fontSize: 12, color: "#6b7280", fontWeight: "500" },
  statusPill: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
  statusPillText: { fontSize: 10, fontWeight: "600" },
  sizesSection: { marginTop: 8, paddingTop: 8, borderTopWidth: 1, borderTopColor: "#f3f4f6" },
  sizesTitle: { fontSize: 12, fontWeight: "600", color: "#9ca3af", marginBottom: 6, textTransform: "uppercase", letterSpacing: 0.5 },
  sizeRow: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingVertical: 6 },
  sizeInfo: { flex: 1 },
  sizeName: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  sizeDetail: { fontSize: 11, color: "#6b7280", marginTop: 1 },
  sizeBulk: { fontSize: 10, color: BRAND.primary, fontWeight: "500", marginTop: 1 },
  sizeForm: { marginTop: 8, backgroundColor: "#f9fafb", borderRadius: 8, padding: 10, gap: 8 },
  addSizeBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
    paddingVertical: 8,
    marginTop: 8,
    borderWidth: 1,
    borderColor: BRAND.primaryLight,
    borderStyle: "dashed",
    borderRadius: 8,
  },
  addSizeBtnText: { fontSize: 12, fontWeight: "600", color: BRAND.primary },
});
