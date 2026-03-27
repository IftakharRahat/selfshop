import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
  RefreshControl,
  TextInput,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

interface Product {
  id: number;
  ProductName: string;
  ProductSlug: string;
  ProductSku: string;
  qty: number;
  ProductResellerPrice: number;
  ProductRegularPrice: number;
  status: string;
  ViewProductImage?: string | null;
  ProductBreaf?: string | null;
  Discount?: number | string;
  vendor_approval_status?: string | null;
  selling_type?: string | null;
  minimum_qty?: number;
  created_at?: string;
}

interface PriceTier {
  id: number;
  product_id: number;
  min_qty: number;
  unit_price: number;
  tier_label: string;
}

export default function ProductDetailScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id: string }>();
  const productId = Number(params.id);
  const queryClient = useQueryClient();

  // Price tier form
  const [showTierForm, setShowTierForm] = useState(false);
  const [tierMinQty, setTierMinQty] = useState("");
  const [tierPrice, setTierPrice] = useState("");
  const [tierLabel, setTierLabel] = useState("");

  const { data: product, isLoading, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-product", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/products/${productId}`);
      return data?.data?.product as Product;
    },
  });

  const { data: priceTiers } = useQuery({
    queryKey: ["vendor-price-tiers", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/products/${productId}/price-tiers`);
      return data?.data?.price_tiers as PriceTier[];
    },
  });

  const statusMutation = useMutation({
    mutationFn: async (status: "Active" | "Inactive") => {
      await apiClient.put(`/vendor/products/${productId}/status`, { status });
    },
    onSuccess: () => {
      toast.success("Status updated");
      queryClient.invalidateQueries({ queryKey: ["vendor-product", productId] });
      queryClient.invalidateQueries({ queryKey: ["vendor-products"] });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async () => {
      await apiClient.delete(`/vendor/products/${productId}`);
    },
    onSuccess: () => {
      toast.success("Product deleted");
      queryClient.invalidateQueries({ queryKey: ["vendor-products"] });
      router.back();
    },
    onError: () => toast.error("Failed to delete product"),
  });

  const createTierMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post(`/vendor/products/${productId}/price-tiers`, {
        min_qty: Number(tierMinQty),
        unit_price: Number(tierPrice),
        tier_label: tierLabel || "Tier",
      });
    },
    onSuccess: () => {
      toast.success("Price tier added");
      setShowTierForm(false);
      setTierMinQty("");
      setTierPrice("");
      setTierLabel("");
      queryClient.invalidateQueries({ queryKey: ["vendor-price-tiers", productId] });
    },
    onError: () => toast.error("Failed to add price tier"),
  });

  const deleteTierMutation = useMutation({
    mutationFn: async (tierId: number) => {
      await apiClient.delete(`/vendor/products/${productId}/price-tiers/${tierId}`);
    },
    onSuccess: () => {
      toast.success("Price tier deleted");
      queryClient.invalidateQueries({ queryKey: ["vendor-price-tiers", productId] });
    },
  });

  const handleDelete = () => {
    Alert.alert("Delete Product", "Are you sure? This cannot be undone.", [
      { text: "Cancel", style: "cancel" },
      { text: "Delete", style: "destructive", onPress: () => deleteMutation.mutate() },
    ]);
  };

  if (isLoading || !product) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Product</Text>
          <View style={{ width: 32 }} />
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      </View>
    );
  }

  const imgUrl = getImageUrl(product.ViewProductImage);
  const isActive = product.status === "Active";

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>Product Detail</Text>
        <TouchableOpacity onPress={() => router.push(`/product/form?id=${productId}`)}>
          <Ionicons name="create-outline" size={22} color={BRAND.primary} />
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />}
      >
        {/* Product Image */}
        {imgUrl && <Image source={{ uri: imgUrl }} style={styles.heroImage} />}

        {/* Product Info */}
        <View style={styles.sectionCard}>
          <Text style={styles.productName}>{product.ProductName}</Text>
          <Text style={styles.productSku}>SKU: {product.ProductSku}</Text>

          <View style={styles.priceRow}>
            <Text style={styles.price}>৳{product.ProductResellerPrice?.toLocaleString()}</Text>
            {product.ProductRegularPrice > product.ProductResellerPrice && (
              <Text style={styles.regularPrice}>৳{product.ProductRegularPrice?.toLocaleString()}</Text>
            )}
            {product.Discount && Number(product.Discount) > 0 && (
              <View style={styles.discountBadge}>
                <Text style={styles.discountText}>{product.Discount}% off</Text>
              </View>
            )}
          </View>

          <View style={styles.metaRow}>
            <View style={styles.metaItem}>
              <Ionicons name="cube-outline" size={14} color="#6b7280" />
              <Text style={styles.metaText}>Stock: {product.qty}</Text>
            </View>
            {product.minimum_qty && (
              <View style={styles.metaItem}>
                <Ionicons name="grid-outline" size={14} color="#6b7280" />
                <Text style={styles.metaText}>Min: {product.minimum_qty}</Text>
              </View>
            )}
            {product.selling_type && (
              <View style={styles.metaItem}>
                <Ionicons name="swap-horizontal-outline" size={14} color="#6b7280" />
                <Text style={styles.metaText}>{product.selling_type}</Text>
              </View>
            )}
          </View>

          {product.ProductBreaf && (
            <Text style={styles.description}>{product.ProductBreaf}</Text>
          )}
        </View>

        {/* Actions */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Actions</Text>
          <View style={styles.actionRow}>
            <TouchableOpacity
              style={[styles.actionBtn, { backgroundColor: isActive ? "#FEF3C7" : "#D1FAE5" }]}
              onPress={() => statusMutation.mutate(isActive ? "Inactive" : "Active")}
            >
              <Ionicons name={isActive ? "pause-circle" : "play-circle"} size={18} color={isActive ? "#92400E" : "#059669"} />
              <Text style={{ color: isActive ? "#92400E" : "#059669", fontWeight: "600", fontSize: 13 }}>
                {isActive ? "Deactivate" : "Activate"}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.actionBtn}
              onPress={() => router.push(`/product/variants?id=${productId}`)}
            >
              <Ionicons name="color-palette-outline" size={18} color={BRAND.primary} />
              <Text style={{ color: BRAND.primary, fontWeight: "600", fontSize: 13 }}>Variants</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.actionBtn, { backgroundColor: "#FEF2F2" }]}
              onPress={handleDelete}
            >
              <Ionicons name="trash-outline" size={18} color="#DC2626" />
              <Text style={{ color: "#DC2626", fontWeight: "600", fontSize: 13 }}>Delete</Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Price Tiers */}
        <View style={styles.sectionCard}>
          <View style={styles.tierHeader}>
            <Text style={styles.sectionTitle}>Price Tiers</Text>
            <TouchableOpacity onPress={() => setShowTierForm(!showTierForm)}>
              <Ionicons name={showTierForm ? "close-circle" : "add-circle"} size={22} color={BRAND.primary} />
            </TouchableOpacity>
          </View>

          {showTierForm && (
            <View style={styles.tierForm}>
              <View style={styles.row}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.fieldLabel}>Min Qty</Text>
                  <TextInput style={styles.input} value={tierMinQty} onChangeText={setTierMinQty} keyboardType="numeric" placeholder="e.g. 10" placeholderTextColor="#9ca3af" />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.fieldLabel}>Unit Price</Text>
                  <TextInput style={styles.input} value={tierPrice} onChangeText={setTierPrice} keyboardType="numeric" placeholder="৳" placeholderTextColor="#9ca3af" />
                </View>
              </View>
              <Text style={styles.fieldLabel}>Label</Text>
              <TextInput style={styles.input} value={tierLabel} onChangeText={setTierLabel} placeholder="e.g. Wholesale" placeholderTextColor="#9ca3af" />
              <TouchableOpacity
                style={[styles.tierAddBtn, createTierMutation.isPending && { opacity: 0.6 }]}
                onPress={() => createTierMutation.mutate()}
                disabled={createTierMutation.isPending || !tierMinQty || !tierPrice}
              >
                <Text style={styles.tierAddBtnText}>Add Tier</Text>
              </TouchableOpacity>
            </View>
          )}

          {(priceTiers ?? []).length === 0 ? (
            <Text style={styles.emptyText}>No price tiers configured</Text>
          ) : (
            (priceTiers ?? []).map((tier) => (
              <View key={tier.id} style={styles.tierRow}>
                <View style={styles.tierInfo}>
                  <Text style={styles.tierLabel}>{tier.tier_label}</Text>
                  <Text style={styles.tierDetail}>Min {tier.min_qty} pcs → ৳{tier.unit_price}/pc</Text>
                </View>
                <TouchableOpacity onPress={() => deleteTierMutation.mutate(tier.id)}>
                  <Ionicons name="trash-outline" size={16} color="#ef4444" />
                </TouchableOpacity>
              </View>
            ))
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
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e", flex: 1, textAlign: "center" },
  scrollContent: { padding: 16 },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  heroImage: { width: "100%", height: 220, borderRadius: 14, marginBottom: 12, resizeMode: "cover" },
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  productName: { fontSize: 18, fontWeight: "700", color: "#1a1a2e", marginBottom: 4 },
  productSku: { fontSize: 12, color: "#9ca3af", marginBottom: 10 },
  priceRow: { flexDirection: "row", alignItems: "center", gap: 8, marginBottom: 10 },
  price: { fontSize: 22, fontWeight: "700", color: BRAND.primary },
  regularPrice: { fontSize: 14, color: "#9ca3af", textDecorationLine: "line-through" },
  discountBadge: { backgroundColor: "#FEF3C7", paddingHorizontal: 8, paddingVertical: 2, borderRadius: 6 },
  discountText: { fontSize: 11, fontWeight: "600", color: "#92400E" },
  metaRow: { flexDirection: "row", gap: 16, marginBottom: 10 },
  metaItem: { flexDirection: "row", alignItems: "center", gap: 4 },
  metaText: { fontSize: 12, color: "#6b7280" },
  description: { fontSize: 13, color: "#6b7280", lineHeight: 19, marginTop: 8 },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 12 },
  actionRow: { flexDirection: "row", gap: 8 },
  actionBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    paddingVertical: 10,
    borderRadius: 10,
    backgroundColor: BRAND.primaryLight,
  },
  tierHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  tierForm: { backgroundColor: "#f9fafb", borderRadius: 10, padding: 12, marginBottom: 12, gap: 8 },
  row: { flexDirection: "row", gap: 10 },
  fieldLabel: { fontSize: 11, fontWeight: "600", color: "#374151", marginBottom: 4, marginLeft: 2 },
  input: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 9,
    fontSize: 13,
    color: "#1a1a2e",
    marginBottom: 4,
  },
  tierAddBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 8,
    paddingVertical: 10,
    alignItems: "center",
    marginTop: 4,
  },
  tierAddBtnText: { fontSize: 13, fontWeight: "600", color: "#fff" },
  tierRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#f9fafb",
  },
  tierInfo: { flex: 1 },
  tierLabel: { fontSize: 13, fontWeight: "600", color: "#1a1a2e" },
  tierDetail: { fontSize: 11, color: "#6b7280", marginTop: 2 },
  emptyText: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingVertical: 12 },
});
