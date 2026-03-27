import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
  Switch,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

export default function ProductFormScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id?: string }>();
  const productId = params.id ? Number(params.id) : null;
  const isEdit = !!productId;
  const queryClient = useQueryClient();

  // Form state
  const [name, setName] = useState("");
  const [sku, setSku] = useState("");
  const [resellerPrice, setResellerPrice] = useState("");
  const [regularPrice, setRegularPrice] = useState("");
  const [qty, setQty] = useState("");
  const [brief, setBrief] = useState("");
  const [details, setDetails] = useState("");
  const [weight, setWeight] = useState("");
  const [minimumQty, setMinimumQty] = useState("");
  const [discount, setDiscount] = useState("");
  const [sellingType, setSellingType] = useState<"wholesale" | "dropshipping" | "both">("both");
  const [isFeatured, setIsFeatured] = useState(false);
  const [imageUri, setImageUri] = useState<string | null>(null);
  const [loaded, setLoaded] = useState(false);

  // Fetch existing product for edit
  const { isLoading } = useQuery({
    queryKey: ["vendor-product", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/products/${productId}`);
      return data?.data?.product;
    },
    enabled: isEdit,
    staleTime: 0,
    gcTime: 0,
  });

  // Populate form on load
  const { data: productData } = useQuery({
    queryKey: ["vendor-product", productId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/vendor/products/${productId}`);
      const p = data?.data?.product;
      if (p && !loaded) {
        setName(p.ProductName ?? "");
        setSku(p.ProductSku ?? "");
        setResellerPrice(String(p.ProductResellerPrice ?? ""));
        setRegularPrice(String(p.ProductRegularPrice ?? ""));
        setQty(String(p.qty ?? ""));
        setBrief(p.ProductBreaf ?? "");
        setDetails(p.ProductDetails ?? "");
        setWeight(p.weight ?? "");
        setMinimumQty(String(p.minimum_qty ?? ""));
        setDiscount(String(p.Discount ?? ""));
        setSellingType(p.selling_type ?? "both");
        setIsFeatured(!!p.frature);
        setLoaded(true);
      }
      return p;
    },
    enabled: isEdit,
  });

  const saveMutation = useMutation({
    mutationFn: async () => {
      const formData = new FormData();
      formData.append("ProductName", name);
      if (sku) formData.append("ProductSku", sku);
      formData.append("ProductResellerPrice", resellerPrice);
      formData.append("ProductRegularPrice", regularPrice || resellerPrice);
      formData.append("qty", qty || "0");
      if (brief) formData.append("ProductBreaf", brief);
      if (details) formData.append("ProductDetails", details);
      if (weight) formData.append("weight", weight);
      if (minimumQty) formData.append("minimum_qty", minimumQty);
      if (discount) formData.append("Discount", discount);
      formData.append("selling_type", sellingType);
      formData.append("frature", isFeatured ? "1" : "0");

      if (imageUri) {
        const filename = imageUri.split("/").pop() ?? "product.jpg";
        formData.append("ProductImage", { uri: imageUri, name: filename, type: "image/jpeg" } as any);
      }

      if (isEdit) {
        formData.append("_method", "POST");
        const { data } = await apiClient.post(`/vendor/products/${productId}`, formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        return data;
      } else {
        const { data } = await apiClient.post("/vendor/products", formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });
        return data;
      }
    },
    onSuccess: (result) => {
      toast.success(isEdit ? "Product updated" : "Product created");
      queryClient.invalidateQueries({ queryKey: ["vendor-products"] });
      if (isEdit) {
        queryClient.invalidateQueries({ queryKey: ["vendor-product", productId] });
      }
      router.back();
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message ?? "Failed to save product";
      toast.error(msg);
    },
  });

  const pickImage = async () => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.8,
      allowsEditing: true,
      aspect: [1, 1],
    });
    if (!result.canceled && result.assets[0]) {
      setImageUri(result.assets[0].uri);
    }
  };

  const existingImage = getImageUrl(productData?.ViewProductImage ?? productData?.ProductImage);

  const SELLING_TYPES = [
    { value: "wholesale", label: "Wholesale" },
    { value: "dropshipping", label: "Dropshipping" },
    { value: "both", label: "Both" },
  ] as const;

  if (isEdit && isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Edit Product</Text>
          <View style={{ width: 32 }} />
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>{isEdit ? "Edit Product" : "New Product"}</Text>
        <View style={{ width: 32 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Product Image */}
        <TouchableOpacity style={styles.imageWrap} onPress={pickImage} activeOpacity={0.7}>
          {imageUri ? (
            <Image source={{ uri: imageUri }} style={styles.productImage} />
          ) : existingImage ? (
            <Image source={{ uri: existingImage }} style={styles.productImage} />
          ) : (
            <View style={styles.imagePlaceholder}>
              <Ionicons name="camera-outline" size={32} color="#9ca3af" />
              <Text style={styles.imagePlaceholderText}>Add product image</Text>
            </View>
          )}
          <View style={styles.imageEditBadge}>
            <Ionicons name="camera" size={14} color="#fff" />
          </View>
        </TouchableOpacity>

        {/* Basic Info */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Basic Information</Text>

          <Field label="Product Name *" value={name} onChange={setName} placeholder="Enter product name" />
          <Field label="SKU" value={sku} onChange={setSku} placeholder="Product SKU code" />

          <View style={styles.row}>
            <View style={{ flex: 1 }}>
              <Field label="Reseller Price *" value={resellerPrice} onChange={setResellerPrice} placeholder="0" keyboard="numeric" />
            </View>
            <View style={{ flex: 1 }}>
              <Field label="Regular Price" value={regularPrice} onChange={setRegularPrice} placeholder="0" keyboard="numeric" />
            </View>
          </View>

          <View style={styles.row}>
            <View style={{ flex: 1 }}>
              <Field label="Quantity" value={qty} onChange={setQty} placeholder="0" keyboard="numeric" />
            </View>
            <View style={{ flex: 1 }}>
              <Field label="Discount (%)" value={discount} onChange={setDiscount} placeholder="0" keyboard="numeric" />
            </View>
          </View>

          <View style={styles.row}>
            <View style={{ flex: 1 }}>
              <Field label="Weight" value={weight} onChange={setWeight} placeholder="e.g. 500g" />
            </View>
            <View style={{ flex: 1 }}>
              <Field label="Min Order Qty" value={minimumQty} onChange={setMinimumQty} placeholder="1" keyboard="numeric" />
            </View>
          </View>
        </View>

        {/* Selling Type */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Selling Type</Text>
          <View style={styles.chipRow}>
            {SELLING_TYPES.map((st) => (
              <TouchableOpacity
                key={st.value}
                style={[styles.chip, sellingType === st.value && styles.chipActive]}
                onPress={() => setSellingType(st.value)}
              >
                <Text style={[styles.chipText, sellingType === st.value && styles.chipTextActive]}>
                  {st.label}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          <View style={styles.switchRow}>
            <Text style={styles.switchLabel}>Featured Product</Text>
            <Switch
              value={isFeatured}
              onValueChange={setIsFeatured}
              trackColor={{ true: BRAND.primary, false: "#e5e7eb" }}
              thumbColor="#fff"
            />
          </View>
        </View>

        {/* Description */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Description</Text>
          <Field label="Brief Description" value={brief} onChange={setBrief} placeholder="Short product summary" multiline />
          <Field label="Full Details" value={details} onChange={setDetails} placeholder="Detailed product description" multiline />
        </View>

        {/* Save */}
        <TouchableOpacity
          style={[styles.saveBtn, saveMutation.isPending && { opacity: 0.6 }]}
          onPress={() => saveMutation.mutate()}
          disabled={saveMutation.isPending || !name.trim() || !resellerPrice.trim()}
          activeOpacity={0.8}
        >
          {saveMutation.isPending ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <Text style={styles.saveBtnText}>{isEdit ? "Update Product" : "Create Product"}</Text>
          )}
        </TouchableOpacity>

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

function Field({ label, value, onChange, placeholder, keyboard, multiline }: {
  label: string;
  value: string;
  onChange: (t: string) => void;
  placeholder: string;
  keyboard?: "default" | "numeric" | "email-address";
  multiline?: boolean;
}) {
  return (
    <View style={styles.inputGroup}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={[styles.input, multiline && { height: 80, textAlignVertical: "top" }]}
        value={value}
        onChangeText={onChange}
        placeholder={placeholder}
        placeholderTextColor="#9ca3af"
        keyboardType={keyboard ?? "default"}
        multiline={multiline}
      />
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
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  imageWrap: {
    width: "100%",
    height: 200,
    borderRadius: 14,
    overflow: "hidden",
    backgroundColor: "#f3f4f6",
    marginBottom: 16,
    position: "relative",
  },
  productImage: { width: "100%", height: "100%", resizeMode: "cover" },
  imagePlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center" },
  imagePlaceholderText: { fontSize: 13, color: "#9ca3af", marginTop: 6 },
  imageEditBadge: {
    position: "absolute",
    bottom: 10,
    right: 10,
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
  },
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 14 },
  row: { flexDirection: "row", gap: 10 },
  inputGroup: { marginBottom: 14 },
  fieldLabel: { fontSize: 12, fontWeight: "600", color: "#374151", marginBottom: 6, marginLeft: 2 },
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
  chipRow: { flexDirection: "row", gap: 8, marginBottom: 16 },
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
  switchRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingVertical: 4,
  },
  switchLabel: { fontSize: 14, color: "#374151", fontWeight: "500" },
  saveBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 12,
    paddingVertical: 15,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 4,
  },
  saveBtnText: { fontSize: 15, fontWeight: "600", color: "#fff" },
});
