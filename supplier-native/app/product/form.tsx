import React, { useMemo, useState, useRef } from "react";
import {
  View, Text, StyleSheet, ScrollView, TouchableOpacity,
  ActivityIndicator, Switch, Platform, Animated, KeyboardAvoidingView,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import * as Haptics from "expo-haptics";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";
import FormField from "@/components/product-form/FormField";
import StepProgressBar from "@/components/product-form/StepProgressBar";
import CategoryPicker, { type CategorySelection, type Category } from "@/components/product-form/CategoryPicker";
import ImageGalleryPicker from "@/components/product-form/ImageGalleryPicker";
import VariantBuilder, { type VariantRow } from "@/components/product-form/VariantBuilder";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

const STEPS = [
  { label: "Basic", icon: "information-circle-outline" as const },
  { label: "Media", icon: "images-outline" as const },
  { label: "Pricing", icon: "pricetag-outline" as const },
  { label: "Details", icon: "document-text-outline" as const },
  { label: "Variants", icon: "color-palette-outline" as const },
];

const SELLING_TYPES = [
  { value: "wholesale", label: "Wholesale", icon: "🏭", sub: "Bulk pricing tiers", accent: "#059669", bg: "#ECFDF5" },
  { value: "dropshipping", label: "Dropshipping", icon: "🚀", sub: "Single price & stock", accent: "#3b82f6", bg: "#EFF6FF" },
  { value: "both", label: "Both", icon: "🔄", sub: "Wholesale + Dropship", accent: "#d97706", bg: "#FFFBEB" },
] as const;

export default function ProductFormScreen() {
  const insets = useSafeAreaInsets();
  const params = useLocalSearchParams<{ id?: string }>();
  const productId = params.id ? Number(params.id) : null;
  const isEdit = !!productId;
  const queryClient = useQueryClient();
  const [step, setStep] = useState(0);
  const [loaded, setLoaded] = useState(false);
  const slideAnim = useRef(new Animated.Value(0)).current;

  // ── Form state ──
  const [name, setName] = useState("");
  const [sku, setSku] = useState("");
  const [sellingType, setSellingType] = useState<"wholesale"|"dropshipping"|"both">("wholesale");
  const [category, setCategory] = useState<CategorySelection>({
    categoryId: null, categoryName: "", subcategoryId: null, subcategoryName: "", minicategoryId: null, minicategoryName: "",
  });
  const [brandId, setBrandId] = useState<string>("");
  const [thumbnailUri, setThumbnailUri] = useState<string | null>(null);
  const [galleryUris, setGalleryUris] = useState<string[]>([]);
  const [resellerPrice, setResellerPrice] = useState("");
  const [regularPrice, setRegularPrice] = useState("");
  const [qty, setQty] = useState("");
  const [discount, setDiscount] = useState("");
  const [lowStock, setLowStock] = useState("");
  const [weight, setWeight] = useState("");
  const [minimumQty, setMinimumQty] = useState("");
  const [unit, setUnit] = useState("");
  const [brief, setBrief] = useState("");
  const [details, setDetails] = useState("");
  const [tags, setTags] = useState("");
  const [isFeatured, setIsFeatured] = useState(false);
  const [warrantyEnabled, setWarrantyEnabled] = useState(false);
  const [warrantyDays, setWarrantyDays] = useState("");
  const [variants, setVariants] = useState<VariantRow[]>([]);
  const [errors, setErrors] = useState<Record<string, string>>({});

  // ── Fetch categories & brands ──
  const { data: categoriesData } = useQuery({
    queryKey: ["all-categories"],
    queryFn: async () => {
      const { data } = await apiClient.get("/categories");
      return Array.isArray(data?.data) ? (data.data as Category[]) : [];
    },
  });
  const { data: brandsData } = useQuery({
    queryKey: ["all-brands"],
    queryFn: async () => {
      const { data } = await apiClient.get("/brands");
      return Array.isArray(data?.data) ? (data.data as { id: number; brand_name: string }[]) : [];
    },
  });
  const categories = categoriesData ?? [];
  const brands = brandsData ?? [];
  const selectedCategory = useMemo(
    () => categories.find((cat: any) => cat && Number(cat.id) === Number(category.categoryId)) ?? null,
    [categories, category.categoryId],
  );
  const selectedSubcategory = useMemo(() => {
    const subcategories = Array.isArray((selectedCategory as any)?.subcategories)
      ? (selectedCategory as any).subcategories
      : [];
    return subcategories.find((sub: any) => sub && Number(sub.id) === Number(category.subcategoryId)) ?? null;
  }, [selectedCategory, category.subcategoryId]);
  const selectedSubcategoryRequiresMini = useMemo(() => {
    const minicategories = Array.isArray((selectedSubcategory as any)?.minicategories)
      ? (selectedSubcategory as any).minicategories
      : [];
    return minicategories.some((mini: any) => mini && mini.mini_category_name);
  }, [selectedSubcategory]);

  // ── Edit: fetch product ──
  const { isLoading } = useQuery({
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
        setUnit(p.unit ?? "");
        setTags(p.MetaKey ?? "");
        setLowStock(String(p.low_stock ?? ""));
        if (p.category_id) {
          setCategory({
            categoryId: Number(p.category_id),
            categoryName: p.categories?.category_name ?? p.category?.category_name ?? "",
            subcategoryId: p.subcategory_id ? Number(p.subcategory_id) : null,
            subcategoryName: p.subcategories?.sub_category_name ?? p.subcategory?.sub_category_name ?? "",
            minicategoryId: p.minicategory_id ? Number(p.minicategory_id) : null,
            minicategoryName: p.minicategories?.mini_category_name ?? p.minicategory?.mini_category_name ?? "",
          });
        }
        if (p.brand_id) setBrandId(String(p.brand_id));
        setLoaded(true);
      }
      return p;
    },
    enabled: isEdit,
  });

  // ── Validation ──
  const validateStep = (s: number): boolean => {
    const e: Record<string, string> = {};
    if (s === 0) {
      if (!name.trim()) e.name = "Product name is required";
      if (!category.categoryId) e.category = "Category is required";
      else if (!category.subcategoryId) e.category = "Subcategory is required";
      else if (selectedSubcategoryRequiresMini && !category.minicategoryId) e.category = "Child category is required";
    }
    if (s === 2 && (sellingType === "dropshipping" || sellingType === "both")) {
      if (!resellerPrice.trim()) e.resellerPrice = "Reseller price is required";
    }
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  // ── Navigation ──
  const goNext = () => {
    if (!validateStep(step)) return;
    if (Platform.OS !== "web") Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    Animated.sequence([
      Animated.timing(slideAnim, { toValue: -20, duration: 100, useNativeDriver: true }),
      Animated.timing(slideAnim, { toValue: 0, duration: 150, useNativeDriver: true }),
    ]).start();
    setStep(step + 1);
  };
  const goBack = () => {
    if (step === 0) { router.back(); return; }
    setStep(step - 1);
  };

  // ── Save ──
  const saveMutation = useMutation({
    mutationFn: async () => {
      const fd = new FormData();
      fd.append("ProductName", name);
      if (sku) fd.append("ProductSku", sku);
      fd.append("selling_type", sellingType);
      fd.append("allow_dropship", sellingType === "dropshipping" || sellingType === "both" ? "1" : "0");
      if (category.categoryId) fd.append("category_id", String(category.categoryId));
      if (category.subcategoryId) fd.append("subcategory_id", String(category.subcategoryId));
      if (category.minicategoryId) fd.append("minicategory_id", String(category.minicategoryId));
      if (brandId) fd.append("brand_id", brandId);
      fd.append("ProductResellerPrice", resellerPrice || "0");
      fd.append("ProductRegularPrice", regularPrice || resellerPrice || "0");
      fd.append("qty", qty || "0");
      if (discount) fd.append("Discount", discount);
      if (lowStock) fd.append("low_stock", lowStock);
      if (weight) fd.append("product_weight", weight);
      if (minimumQty) fd.append("minimum_qty", minimumQty);
      if (unit) fd.append("unit", unit);
      if (brief) fd.append("ProductBreaf", brief);
      if (details) fd.append("ProductDetails", details);
      if (tags) fd.append("MetaKey", tags);
      fd.append("frature", isFeatured ? "1" : "0");
      if (warrantyEnabled && warrantyDays) fd.append("warranty_days", warrantyDays);
      if (thumbnailUri) {
        const fn = thumbnailUri.split("/").pop() ?? "thumb.jpg";
        fd.append("ProductImage", { uri: thumbnailUri, name: fn, type: "image/jpeg" } as any);
      }
      galleryUris.forEach((uri, i) => {
        const fn = uri.split("/").pop() ?? `gallery_${i}.jpg`;
        fd.append("PostImage[]", { uri, name: fn, type: "image/jpeg" } as any);
      });
      let result;
      if (isEdit) {
        fd.append("_method", "POST");
        result = (await apiClient.post(`/vendor/products/${productId}`, fd, { headers: { "Content-Type": "multipart/form-data" } })).data;
      } else {
        result = (await apiClient.post("/vendor/products", fd, { headers: { "Content-Type": "multipart/form-data" } })).data;
      }
      // Chain variant/size/bulk API calls
      const newId = isEdit ? productId : result?.data?.product?.id;
      if (newId && variants.length > 0) {
        for (const v of variants) {
          try {
            const varRes = await apiClient.post(`/vendor/products/${newId}/variants`, {
              title: v.title || v.color_name || "Variant", color_name: v.color_name || undefined,
              color_code: v.color_code || undefined, qty: 0, price: 0,
            });
            const variantId = varRes.data?.data?.variant?.id;
            if (!variantId) continue;
            for (const sz of v.sizes) {
              if (!sz.size_name) continue;
              const szRes = await apiClient.post(`/vendor/products/${newId}/variants/${variantId}/sizes`, {
                size_name: sz.size_name, qty: parseInt(sz.qty, 10) || 0,
                price: sz.price ? parseFloat(sz.price) : null, status: "Active",
              });
              const sizeId = szRes.data?.data?.size?.id;
              if (!sizeId) continue;
              for (const bt of sz.bulkTiers) {
                if (!bt.min_qty || !bt.bulk_price) continue;
                await apiClient.post(`/vendor/products/${newId}/variants/${variantId}/sizes/${sizeId}/bulk-prices`, {
                  min_qty: parseInt(bt.min_qty, 10), max_qty: bt.max_qty ? parseInt(bt.max_qty, 10) : null,
                  bulk_price: parseFloat(bt.bulk_price),
                });
              }
            }
          } catch (e) { console.error("Variant creation failed:", e); }
        }
      }
      return result;
    },
    onSuccess: () => {
      toast.success(isEdit ? "Product updated" : "Product created");
      queryClient.invalidateQueries({ queryKey: ["vendor-products"] });
      if (isEdit) queryClient.invalidateQueries({ queryKey: ["vendor-product", productId] });
      router.back();
    },
    onError: (err: any) => { toast.error(err?.response?.data?.message ?? "Failed to save product"); },
  });

  const handleSave = () => { if (!validateStep(step)) return; saveMutation.mutate(); };
  const showPriceFields = sellingType === "dropshipping" || sellingType === "both";

  if (isEdit && isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <Header title="Edit Product" />
        <View style={styles.center}><ActivityIndicator size="large" color={BRAND.primary} /></View>
      </View>
    );
  }

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <Header title={isEdit ? "Edit Product" : "New Product"} />
      <StepProgressBar steps={STEPS} currentStep={step} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === "ios" ? "padding" : undefined}>
        <Animated.View style={{ flex: 1, transform: [{ translateX: slideAnim }] }}>
          <ScrollView contentContainerStyle={styles.scroll} showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">

            {/* ═══ STEP 0: Basic Info ═══ */}
            {step === 0 && (
              <View>
                <Text style={styles.stepTitle}>Basic Information</Text>
                <Text style={styles.stepSub}>Enter the core product details</Text>
                <View style={styles.card}>
                  <FormField label="Product Name" required value={name} onChangeText={setName} placeholder="Enter product name" error={errors.name} />
                  <FormField label="SKU" value={sku} onChangeText={setSku} placeholder="Product SKU code" helperText="Optional unique identifier" />
                </View>
                <View style={styles.card}>
                  <Text style={styles.cardTitle}>Selling Type</Text>
                  <View style={styles.sellingRow}>
                    {SELLING_TYPES.map((st) => {
                      const active = sellingType === st.value;
                      return (
                        <TouchableOpacity key={st.value} style={[styles.sellingCard, { borderColor: active ? st.accent : "#e5e7eb" }, active && { backgroundColor: st.bg }]} onPress={() => setSellingType(st.value)} activeOpacity={0.7}>
                          <Text style={styles.sellingIcon}>{st.icon}</Text>
                          <Text style={[styles.sellingLabel, active && { color: st.accent }]}>{st.label}</Text>
                          <Text style={styles.sellingSub}>{st.sub}</Text>
                        </TouchableOpacity>
                      );
                    })}
                  </View>
                </View>
                <View style={styles.card}>
                  <CategoryPicker categories={categories} selection={category} onChange={setCategory} error={errors.category} />
                  <Text style={styles.fieldLabel}>Brand</Text>
                  <View style={styles.brandRow}>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                      <TouchableOpacity style={[styles.brandChip, !brandId && styles.brandChipActive]} onPress={() => setBrandId("")}>
                        <Text style={[styles.brandChipText, !brandId && styles.brandChipTextActive]}>None</Text>
                      </TouchableOpacity>
                      {brands.map((b) => (
                        <TouchableOpacity key={b.id} style={[styles.brandChip, brandId === String(b.id) && styles.brandChipActive]} onPress={() => setBrandId(String(b.id))}>
                          <Text style={[styles.brandChipText, brandId === String(b.id) && styles.brandChipTextActive]}>{b.brand_name}</Text>
                        </TouchableOpacity>
                      ))}
                    </ScrollView>
                  </View>
                </View>
              </View>
            )}

            {/* ═══ STEP 1: Media ═══ */}
            {step === 1 && (
              <View>
                <Text style={styles.stepTitle}>Product Images</Text>
                <Text style={styles.stepSub}>Add a thumbnail and gallery images</Text>
                <View style={styles.card}>
                  <ImageGalleryPicker thumbnail={thumbnailUri} gallery={galleryUris} onThumbnailChange={setThumbnailUri} onGalleryChange={setGalleryUris} />
                </View>
              </View>
            )}

            {/* ═══ STEP 2: Pricing ═══ */}
            {step === 2 && (
              <View>
                <Text style={styles.stepTitle}>Pricing & Stock</Text>
                <Text style={styles.stepSub}>{showPriceFields ? "Set your product pricing and inventory" : "Pricing is managed through variants"}</Text>
                {showPriceFields ? (
                  <View style={styles.card}>
                    <View style={styles.row}>
                      <View style={{ flex: 1 }}><FormField label="Reseller Price" required prefix="৳" value={resellerPrice} onChangeText={setResellerPrice} placeholder="0" keyboardType="numeric" error={errors.resellerPrice} /></View>
                      <View style={{ flex: 1 }}><FormField label="Regular Price" prefix="৳" value={regularPrice} onChangeText={setRegularPrice} placeholder="0" keyboardType="numeric" /></View>
                    </View>
                    <View style={styles.row}>
                      <View style={{ flex: 1 }}><FormField label="Quantity" value={qty} onChangeText={setQty} placeholder="0" keyboardType="numeric" /></View>
                      <View style={{ flex: 1 }}><FormField label="Discount (%)" value={discount} onChangeText={setDiscount} placeholder="0" keyboardType="numeric" /></View>
                    </View>
                    <View style={styles.row}>
                      <View style={{ flex: 1 }}><FormField label="Low Stock Alert" value={lowStock} onChangeText={setLowStock} placeholder="0" keyboardType="numeric" helperText="Warn when stock drops below" /></View>
                      <View style={{ flex: 1 }}><FormField label="Min Order Qty" value={minimumQty} onChangeText={setMinimumQty} placeholder="1" keyboardType="numeric" /></View>
                    </View>
                  </View>
                ) : (
                  <View style={styles.infoCard}>
                    <Ionicons name="information-circle" size={22} color="#4f46e5" />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.infoTitle}>Wholesale pricing via variants</Text>
                      <Text style={styles.infoText}>Price & stock are managed through variants (colors & sizes).{"\n"}Add them in the next step.</Text>
                    </View>
                  </View>
                )}
              </View>
            )}

            {/* ═══ STEP 3: Details ═══ */}
            {step === 3 && (
              <View>
                <Text style={styles.stepTitle}>Description & Extras</Text>
                <Text style={styles.stepSub}>Add details to help resellers sell your product</Text>
                <View style={styles.card}>
                  <FormField label="Brief Description" value={brief} onChangeText={setBrief} placeholder="Short product summary" multiline />
                  <FormField label="Full Details" value={details} onChangeText={setDetails} placeholder="Detailed product description" multiline />
                </View>
                <View style={styles.card}>
                  <Text style={styles.cardTitle}>Additional Info</Text>
                  <View style={styles.row}>
                    <View style={{ flex: 1 }}><FormField label="Unit" value={unit} onChangeText={setUnit} placeholder="e.g. Pc, Kg" /></View>
                    <View style={{ flex: 1 }}><FormField label="Weight" value={weight} onChangeText={setWeight} placeholder="e.g. 500g" /></View>
                  </View>
                  <FormField label="Tags" value={tags} onChangeText={setTags} placeholder="tag1, tag2, tag3" helperText="Comma separated keywords for search" />
                </View>
                <View style={styles.card}>
                  <View style={styles.switchRow}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.switchLabel}>Featured Product</Text>
                      <Text style={styles.switchSub}>Highlight this product in your store</Text>
                    </View>
                    <Switch value={isFeatured} onValueChange={setIsFeatured} trackColor={{ true: BRAND.primary, false: "#e5e7eb" }} thumbColor="#fff" />
                  </View>
                  <View style={[styles.switchRow, { marginTop: 16 }]}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.switchLabel}>Warranty / Exchange</Text>
                      <Text style={styles.switchSub}>Allow claims within warranty period</Text>
                    </View>
                    <Switch value={warrantyEnabled} onValueChange={setWarrantyEnabled} trackColor={{ true: "#059669", false: "#e5e7eb" }} thumbColor="#fff" />
                  </View>
                  {warrantyEnabled && (
                    <View style={{ marginTop: 12 }}>
                      <FormField label="Warranty Days" value={warrantyDays} onChangeText={setWarrantyDays} placeholder="e.g. 30" keyboardType="numeric" helperText="Number of days from delivery" />
                    </View>
                  )}
                </View>
              </View>
            )}

            {/* ═══ STEP 4: Variants ═══ */}
            {step === 4 && (
              <View>
                <Text style={styles.stepTitle}>Product Variants</Text>
                <Text style={styles.stepSub}>Add color variants with sizes and bulk pricing</Text>
                <VariantBuilder variants={variants} onChange={setVariants} />
                {isEdit && (
                  <TouchableOpacity style={styles.variantsBtn} onPress={() => router.push({ pathname: "/product/variants", params: { id: String(productId) } })} activeOpacity={0.7}>
                    <Ionicons name="color-palette-outline" size={20} color={BRAND.primary} />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.variantsBtnTitle}>Manage Existing Variants</Text>
                      <Text style={styles.variantsBtnSub}>Edit or delete saved variants on server</Text>
                    </View>
                    <Ionicons name="chevron-forward" size={18} color="#9ca3af" />
                  </TouchableOpacity>
                )}
              </View>
            )}

            <View style={{ height: 100 }} />
          </ScrollView>
        </Animated.View>
      </KeyboardAvoidingView>

      {/* ── Bottom Action Bar ── */}
      <View style={[styles.bottomBar, { paddingBottom: Math.max(insets.bottom, 12) }]}>
        <TouchableOpacity style={styles.backBtn} onPress={goBack} activeOpacity={0.7}>
          <Ionicons name={step === 0 ? "close" : "arrow-back"} size={20} color="#374151" />
          <Text style={styles.backBtnText}>{step === 0 ? "Cancel" : "Back"}</Text>
        </TouchableOpacity>
        {step < 4 ? (
          <TouchableOpacity style={styles.nextBtn} onPress={goNext} activeOpacity={0.8}>
            <Text style={styles.nextBtnText}>Next</Text>
            <Ionicons name="arrow-forward" size={18} color="#fff" />
          </TouchableOpacity>
        ) : (
          <TouchableOpacity style={[styles.saveBtn, saveMutation.isPending && { opacity: 0.6 }]} onPress={handleSave} disabled={saveMutation.isPending} activeOpacity={0.8}>
            {saveMutation.isPending ? <ActivityIndicator color="#fff" size="small" /> : <Text style={styles.saveBtnText}>{isEdit ? "Update Product" : "Create Product"}</Text>}
          </TouchableOpacity>
        )}
      </View>
    </View>
  );
}
function Header({ title }: { title: string }) {
  return (
    <View style={styles.header}>
      <TouchableOpacity onPress={() => router.back()} style={styles.headerBack}>
        <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
      </TouchableOpacity>
      <Text style={styles.headerTitle}>{title}</Text>
      <View style={{ width: 32 }} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  center: { flex: 1, alignItems: "center", justifyContent: "center" },
  header: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  headerBack: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scroll: { padding: 16 },
  stepTitle: { fontSize: 20, fontWeight: "700", color: "#1a1a2e", marginBottom: 4 },
  stepSub: { fontSize: 13, color: "#6b7280", marginBottom: 16 },
  card: { backgroundColor: "#fff", borderRadius: 14, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: "#f3f4f6" },
  cardTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 14 },
  row: { flexDirection: "row", gap: 10 },
  fieldLabel: { fontSize: 13, fontWeight: "600", color: "#374151", marginBottom: 8, marginLeft: 2 },

  /* Selling type */
  sellingRow: { flexDirection: "row", gap: 8, marginBottom: 4 },
  sellingCard: { flex: 1, borderWidth: 2, borderRadius: 12, padding: 10, alignItems: "center", gap: 2, backgroundColor: "#fff" },
  sellingIcon: { fontSize: 20, marginBottom: 2 },
  sellingLabel: { fontSize: 11, fontWeight: "700", color: "#374151" },
  sellingSub: { fontSize: 9, color: "#9ca3af", textAlign: "center" },

  /* Brand chips */
  brandRow: { marginBottom: 8 },
  brandChip: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: "#f3f4f6", borderWidth: 1, borderColor: "#e5e7eb" },
  brandChipActive: { backgroundColor: "#4f46e5", borderColor: "#4f46e5" },
  brandChipText: { fontSize: 12, fontWeight: "500", color: "#6b7280" },
  brandChipTextActive: { color: "#fff" },

  /* Info card */
  infoCard: { flexDirection: "row", backgroundColor: "#EEF2FF", borderRadius: 12, padding: 14, gap: 10, marginBottom: 12, alignItems: "flex-start" },
  infoTitle: { fontSize: 13, fontWeight: "600", color: "#312E81", marginBottom: 2 },
  infoText: { fontSize: 11, color: "#4338CA", lineHeight: 16 },

  /* Switches */
  switchRow: { flexDirection: "row", alignItems: "center", justifyContent: "space-between" },
  switchLabel: { fontSize: 14, fontWeight: "600", color: "#1a1a2e" },
  switchSub: { fontSize: 11, color: "#9ca3af", marginTop: 1 },

  /* Variants */
  variantsBtn: { flexDirection: "row", alignItems: "center", gap: 12, backgroundColor: "#fff", borderRadius: 14, padding: 14, marginBottom: 12, borderWidth: 1, borderColor: "#E0E7FF", borderStyle: "dashed" },
  variantsBtnTitle: { fontSize: 14, fontWeight: "600", color: "#4f46e5" },
  variantsBtnSub: { fontSize: 11, color: "#6b7280", marginTop: 1 },

  /* Bottom bar */
  bottomBar: { flexDirection: "row", gap: 10, paddingHorizontal: 16, paddingTop: 12, backgroundColor: "#fff", borderTopWidth: 1, borderTopColor: "#f3f4f6", ...Platform.select({ ios: { shadowColor: "#000", shadowOffset: { width: 0, height: -3 }, shadowOpacity: 0.06, shadowRadius: 8 }, android: { elevation: 8 } }) },
  backBtn: { flexDirection: "row", alignItems: "center", gap: 6, paddingHorizontal: 16, paddingVertical: 12, borderRadius: 12, backgroundColor: "#f3f4f6" },
  backBtnText: { fontSize: 14, fontWeight: "500", color: "#374151" },
  nextBtn: { flex: 1, flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6, paddingVertical: 14, borderRadius: 12, backgroundColor: "#4f46e5" },
  nextBtnText: { fontSize: 15, fontWeight: "600", color: "#fff" },
  saveBtn: { flex: 1, alignItems: "center", justifyContent: "center", paddingVertical: 14, borderRadius: 12, backgroundColor: BRAND.primary },
  saveBtnText: { fontSize: 15, fontWeight: "600", color: "#fff" },
});
