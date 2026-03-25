import { useState, useCallback, useRef, useEffect } from "react";
import {
  View, ScrollView, Image, Pressable, StyleSheet, Dimensions,
  ActivityIndicator, FlatList, type ViewToken, Linking, Modal, Animated,
  TextInput, Alert,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const { width: SW } = Dimensions.get("window");
const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";
const THUMB_SIZE = 52;

export default function ProductDetailScreen() {
  const params = useLocalSearchParams<{ slug: string }>();
  const insets = useSafeAreaInsets();
  const slug = Array.isArray(params.slug) ? params.slug[0] : params.slug;
  const queryClient = useQueryClient();
  const [imgIdx, setImgIdx] = useState(0);
  const [wishlisted, setWishlisted] = useState(false);
  const [activeTab, setActiveTab] = useState<"desc" | "specs">("desc");
  const [descExpanded, setDescExpanded] = useState(false);
  const [showActivation, setShowActivation] = useState(false);
  const { isActive: isResellerActive, isLoggedIn } = useIsActiveReseller();
  const imgRef = useRef<FlatList>(null);

  // ── Variant / Size ordering state ──
  const [activeVariantIdx, setActiveVariantIdx] = useState(0);
  const [variantQuantities, setVariantQuantities] = useState<Record<number, Record<string, number>>>({});
  const [variantSellingPrices, setVariantSellingPrices] = useState<Record<number, Record<string, string>>>({});

  // Bottom sheet spring animation
  const sheetAnim = useRef(new Animated.Value(400)).current;
  useEffect(() => {
    if (showActivation) {
      sheetAnim.setValue(400);
      Animated.spring(sheetAnim, {
        toValue: 0,
        tension: 65,
        friction: 11,
        useNativeDriver: true,
      }).start();
    }
  }, [showActivation]);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["product-detail", slug],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/product-details/${slug}`);
      return d?.data ?? d;
    },
    enabled: !!slug && slug.length > 0,
  });

  // ── Shop hooks ──
  const productId = (data?.product_details ?? data?.product ?? data)?.id;

  const shopCheckQuery = useQuery({
    queryKey: ["check-in-shop", productId],
    queryFn: async () => {
      const { data: d } = await apiClient.get(`/check-in-shop/${productId}`);
      return d;
    },
    enabled: !!productId && isLoggedIn,
    staleTime: 60 * 1000,
  });
  const isInShop = shopCheckQuery.data?.in_shop ?? false;

  const shopToggleMutation = useMutation({
    mutationFn: async () => {
      const endpoint = isInShop ? `/remove-from-shop/${productId}` : `/add-to-shop/${productId}`;
      const { data: d } = await apiClient.get(endpoint);
      return d;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["check-in-shop", productId] });
      queryClient.invalidateQueries({ queryKey: ["shop-products"] });
      toast.success(isInShop ? "Removed from your shop" : "Added to your shop!");
    },
    onError: () => {
      toast.error("Something went wrong. Please try again.");
    },
  });

  // ── Add to Cart mutation ──
  const addToCartMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const { data: d } = await apiClient.post("/user-add-to-cart", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return d;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
    },
    onError: () => {
      toast.error("Failed to add to cart");
    },
  });

  const onImgChange = useCallback(({ viewableItems }: { viewableItems: ViewToken[] }) => {
    if (viewableItems.length > 0 && viewableItems[0].index !== null) setImgIdx(viewableItems[0].index);
  }, []);
  const imgViewCfg = useRef({ viewAreaCoveragePercentThreshold: 50 }).current;

  if (isLoading) return (
    <View style={s.loadC}>
      <ActivityIndicator size="large" color={ACCENT} />
      <Text fontSize="$3" color={GREY} mt="$3">Loading product...</Text>
    </View>
  );
  if (isError || !data) return (
    <View style={s.loadC}>
      <View style={s.errorIcon}>
        <Ionicons name="bag-remove-outline" size={48} color={ACCENT} />
      </View>
      <Text fontSize="$5" fontWeight="bold" color={DARK} mt="$3">Product Not Found</Text>
      <Text fontSize="$3" color={GREY} mt="$1" textAlign="center" style={{ maxWidth: 260 }}>
        This product may have been removed or is no longer available.
      </Text>
      <Pressable style={s.goBackBtn} onPress={() => router.back()}>
        <Ionicons name="arrow-back" size={16} color="#fff" />
        <Text fontSize="$3" fontWeight="600" color="#fff">Go Back</Text>
      </Pressable>
    </View>
  );

  const product = data.product_details ?? data.product ?? data;
  const relatedProducts: any[] = data.relatedproducts?.data ?? [];
  const flashSale = data.flash_sale;

  // Images
  const mainImg = product.ViewProductImage ?? product.ProductImage;
  let postImages: string[] = [];
  try { postImages = product.PostImage ? JSON.parse(product.PostImage) : []; } catch { postImages = []; }
  const allImgs: string[] = [mainImg, ...postImages].filter(Boolean);

  // Commission
  const commissionPercent = parseFloat(data.commission_percent || product.commission_percent || "0");
  const commissionFactor = 1 + (commissionPercent / 100);

  // Pricing
  const basePrice = Number(product.ProductResellerPrice || product.ProductRegularPrice || 0);
  const regularPrice = Number(product.ProductRegularPrice ?? 0);
  const priceTiers: any[] = product.price_tiers || [];
  const hasTiers = priceTiers.length > 0;

  // Variants
  const variants: any[] = product.varients ?? [];

  // Selling type
  const sellingType: "wholesale" | "dropshipping" | "both" = product.selling_type || "both";
  const showWholesale = (sellingType === "wholesale" || sellingType === "both") && hasTiers;
  const showDropshipping = sellingType === "dropshipping" || sellingType === "both";

  // Meta
  const categoryName = product.categories?.category_name ?? "";
  const vendorName = product.vendor?.company_name || product.vendor?.CompanyName || "";
  const minQty = Number(product.minimum_qty ?? 1);
  const stockQty = Number(product.qty ?? 0);
  const sku = product.ProductSku ?? "";
  const youtubeLink = product.youtube_link ?? "";

  // Description
  const rawDesc = product.ProductDetails ?? product.ProductBreaf ?? "";
  const cleanDesc = rawDesc.replace(/<[^>]*>/g, "").replace(/&nbsp;/g, " ").trim();

  // ── Computed sizes for current variant ──
  const currentVariant = variants[activeVariantIdx];
  const currentVarId = currentVariant?.id ?? 0;

  let sizesForTable: Array<{ size_name: string; price: any; qty: number; bulk_prices: any[] }> = [];
  if (currentVariant?.sizes && currentVariant.sizes.length > 0) {
    sizesForTable = currentVariant.sizes.map((sz: any) => ({
      size_name: sz.size_name,
      price: sz.price,
      qty: sz.qty ?? 0,
      bulk_prices: sz.bulkPrices || sz.bulk_prices || [],
    }));
  } else {
    const overrideStock = currentVariant ? currentVariant.qty ?? stockQty : stockQty;
    const productSizes = Array.isArray(product.size)
      ? product.size
      : typeof product.size === "string"
        ? (() => { try { return JSON.parse(product.size); } catch { return []; } })()
        : [];
    const legacySizes = productSizes.length > 0 ? productSizes : ["Default"];
    sizesForTable = legacySizes.map((sName: string) => ({
      size_name: sName,
      price: null,
      qty: overrideStock,
      bulk_prices: [],
    }));
  }

  // Total quantity across all variants and sizes
  const totalQuantity = Object.values(variantQuantities)
    .flatMap((sizes) => Object.values(sizes))
    .reduce((a, b) => a + b, 0);

  // Active tier based on total qty
  const activeTier = hasTiers
    ? priceTiers
        .slice()
        .sort((a: any, b: any) => b.min_qty - a.min_qty)
        .find((t: any) => totalQuantity >= t.min_qty) ?? priceTiers[0]
    : null;

  // Get price for a size item
  const getSizePrice = (sizeItem: any, qty: number): number => {
    if (flashSale && flashSale.flash_price > 0) {
      return parseFloat(flashSale.flash_price);
    }
    // Size-level bulk tiers
    const tiers = sizeItem.bulk_prices || [];
    if (tiers.length > 0) {
      const tier = tiers.slice().sort((a: any, b: any) => b.min_qty - a.min_qty).find((t: any) => qty >= t.min_qty);
      if (tier) return Math.round(parseFloat(tier.bulk_price || tier.unit_price) * commissionFactor * 100) / 100;
    }
    // Size-level base price
    if (sizeItem.price !== null && sizeItem.price !== undefined) {
      const sp = parseFloat(sizeItem.price);
      if (sp > 0) return Math.round(sp * commissionFactor * 100) / 100;
    }
    // Product-level tier
    if (hasTiers && activeTier) {
      return Math.round(parseFloat(activeTier.unit_price) * commissionFactor * 100) / 100;
    }
    // Fallback
    return Math.round(basePrice * commissionFactor * 100) / 100;
  };

  // Display price (for hero section)
  const salePrice = flashSale && flashSale.flash_price > 0
    ? parseFloat(flashSale.flash_price)
    : activeTier
      ? Math.round(parseFloat(activeTier.unit_price) * commissionFactor * 100) / 100
      : Math.round(
          Number(product.storefront_price ?? product.ProductSalePrice ?? basePrice) *
            (product.storefront_price ? 1 : commissionFactor) *
            100
        ) / 100;

  const hasDiscount = regularPrice > salePrice && regularPrice > 0;
  const discountPercent = hasDiscount ? Math.round(((regularPrice - salePrice) / regularPrice) * 100) : 0;

  // ── Qty handlers ──
  const handleQtyChange = (variantId: number, size: string, type: "increase" | "decrease", stock?: number) => {
    setVariantQuantities((prev) => {
      const varSizes = { ...(prev[variantId] || {}) };
      const cur = varSizes[size] || 0;
      let next = type === "increase" ? cur + 1 : Math.max(0, cur - 1);
      if (type === "increase" && stock !== undefined && next > stock) {
        next = stock;
        toast.error(`Only ${stock} items in stock for size ${size}`);
      }
      varSizes[size] = next;
      return { ...prev, [variantId]: varSizes };
    });
  };

  const handleSellingPriceChange = (variantId: number, size: string, value: string) => {
    setVariantSellingPrices((prev) => {
      const varSizes = { ...(prev[variantId] || {}) };
      varSizes[size] = value;
      return { ...prev, [variantId]: varSizes };
    });
  };

  // ── Get selected items for cart submission ──
  const getSelectedItems = () => {
    const items: { variantId: number; variantTitle: string; size: string; qty: number; price: number; sellingPrice: number | null }[] = [];
    for (const [vid, sizes] of Object.entries(variantQuantities)) {
      const v = variants.find((vr: any) => vr.id === Number(vid));
      for (const [sizeName, qty] of Object.entries(sizes)) {
        if (qty > 0) {
          const variantLabel = v?.color_name || v?.title || "";
          const sizeItem = v?.sizes?.find((sz: any) => sz.size_name === sizeName) || sizesForTable.find(sz => sz.size_name === sizeName);
          const itemPrice = sizeItem ? getSizePrice(sizeItem, qty) : salePrice;
          const spStr = variantSellingPrices[Number(vid)]?.[sizeName] || "";
          const sp = spStr ? parseFloat(spStr) : null;
          items.push({
            variantId: Number(vid),
            variantTitle: variantLabel,
            size: sizeName,
            qty,
            price: itemPrice,
            sellingPrice: sp && !isNaN(sp) ? sp : null,
          });
        }
      }
    }
    return items;
  };

  // ── Add to Cart ──
  const handleAddToCart = async () => {
    if (!isLoggedIn) {
      router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
      return;
    }

    const items = getSelectedItems();
    if (items.length === 0) {
      toast.error("Please select at least one item");
      return;
    }

    if (showDropshipping) {
      const invalid = items.find(i => !i.sellingPrice || i.sellingPrice < i.price);
      if (invalid) {
        toast.error("Please enter a valid selling price (≥ cost price) for all items");
        return;
      }
    }

    let successCount = 0;
    for (const item of items) {
      const formData = new FormData();
      formData.append("product_id", String(product.id));
      formData.append("price", item.price.toString());
      if (showDropshipping && item.sellingPrice) {
        formData.append("selling_price", item.sellingPrice.toString());
      }
      formData.append("qty", item.qty.toString());
      formData.append("size", item.size);
      if (item.variantId) formData.append("varient_id", item.variantId.toString());
      if (item.variantTitle) formData.append("color", item.variantTitle);

      try {
        await addToCartMutation.mutateAsync(formData);
        successCount++;
      } catch { /* error handled in mutation */ }
    }
    if (successCount > 0) {
      toast.success(`${successCount} item${successCount > 1 ? "s" : ""} added to cart`);
      // Reset quantities after adding
      setVariantQuantities({});
      setVariantSellingPrices({});
    }
  };

  // ── Buy Now ──
  const handleBuyNow = async () => {
    if (!isLoggedIn) {
      router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
      return;
    }

    const items = getSelectedItems();
    if (items.length === 0) {
      toast.error("Please select at least one item");
      return;
    }

    if (showDropshipping) {
      const invalid = items.find(i => !i.sellingPrice || i.sellingPrice < i.price);
      if (invalid) {
        toast.error("Please enter a valid selling price (≥ cost price) for all items");
        return;
      }
    }

    let lastSuccess = false;
    for (const item of items) {
      const formData = new FormData();
      formData.append("product_id", String(product.id));
      formData.append("price", item.price.toString());
      if (showDropshipping && item.sellingPrice) {
        formData.append("selling_price", item.sellingPrice.toString());
      }
      formData.append("qty", item.qty.toString());
      formData.append("size", item.size);
      if (item.variantId) formData.append("varient_id", item.variantId.toString());
      if (item.variantTitle) formData.append("color", item.variantTitle);

      try {
        await addToCartMutation.mutateAsync(formData);
        lastSuccess = true;
      } catch { /* error handled */ }
    }

    if (lastSuccess) {
      router.push("/order-confirmation" as any);
    }
  };

  const scrollToImage = (idx: number) => {
    imgRef.current?.scrollToIndex({ index: idx, animated: true });
    setImgIdx(idx);
  };

  const formatBDT = (num: number, dec = 2) => num.toLocaleString("en-BD", { minimumFractionDigits: dec, maximumFractionDigits: dec });

  return (
    <View style={s.container}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 130 }}>

        {/* ═══ IMAGE GALLERY ═══ */}
        <View style={s.hero}>
          <FlatList
            ref={imgRef} data={allImgs} horizontal pagingEnabled
            showsHorizontalScrollIndicator={false} bounces={false}
            onViewableItemsChanged={onImgChange} viewabilityConfig={imgViewCfg}
            keyExtractor={(_, i) => String(i)}
            renderItem={({ item }) => (
              <Image source={{ uri: item }} style={s.heroImg} resizeMode="contain" />
            )}
          />
          <Pressable style={[s.overlayBtn, { top: insets.top + 8, left: 16 }]} onPress={() => router.back()}>
            <Ionicons name="arrow-back" size={22} color={DARK} />
          </Pressable>
          <Pressable
            style={[s.overlayBtn, { top: insets.top + 8, right: 108 }]}
            onPress={() => setWishlisted(p => { toast.success(!p ? "Added to wishlist" : "Removed from wishlist"); return !p; })}
          >
            <Ionicons name={wishlisted ? "heart" : "heart-outline"} size={20} color={wishlisted ? "#EF4444" : DARK} />
          </Pressable>
          {isLoggedIn && (
            <Pressable
              style={[s.overlayBtn, { top: insets.top + 8, right: 60, backgroundColor: isInShop ? "#D1FAE5" : "rgba(255,255,255,0.95)" }]}
              onPress={() => shopToggleMutation.mutate()}
              disabled={shopToggleMutation.isPending}
            >
              <Ionicons name={isInShop ? "storefront" : "storefront-outline"} size={18} color={isInShop ? "#059669" : DARK} />
            </Pressable>
          )}
          <Pressable style={[s.overlayBtn, { top: insets.top + 8, right: 16 }]}>
            <Ionicons name="share-social-outline" size={20} color={DARK} />
          </Pressable>
          {allImgs.length > 1 && (
            <View style={s.imgCounter}>
              <Text fontSize={11} fontWeight="600" color="#fff">{imgIdx + 1}/{allImgs.length}</Text>
            </View>
          )}
        </View>

        {/* Thumbnails */}
        {allImgs.length > 1 && (
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={s.thumbRow}>
            {allImgs.map((img, i) => (
              <Pressable key={i} onPress={() => scrollToImage(i)}>
                <Image source={{ uri: img }} style={[s.thumbImg, imgIdx === i && s.thumbActive]} resizeMode="cover" />
              </Pressable>
            ))}
          </ScrollView>
        )}

        {/* ═══ PRICE + TITLE CARD ═══ */}
        <View style={s.mainCard}>
          {/* Flash Sale Banner */}
          {flashSale && (
            <View style={s.flashBanner}>
              <View style={s.flashLeft}>
                <Text fontSize={16}>⚡</Text>
                <View>
                  <Text fontSize={10} fontWeight="800" color="rgba(255,255,255,0.8)" style={{ textTransform: "uppercase", letterSpacing: 1 }}>Flash Sale</Text>
                  <Text fontSize="$3" fontWeight="bold" color="#fff">{flashSale.flash_sale_title ?? "Limited Time Offer"}</Text>
                </View>
              </View>
            </View>
          )}

          {/* Selling Type Badge */}
          <View style={{ flexDirection: "row", gap: 6, marginBottom: 8 }}>
            {sellingType === "wholesale" && (
              <View style={[s.typeBadge, { backgroundColor: "#ECFDF5" }]}>
                <Text fontSize={10} fontWeight="700" color="#059669">🏭 Wholesale</Text>
              </View>
            )}
            {sellingType === "dropshipping" && (
              <View style={[s.typeBadge, { backgroundColor: "#EFF6FF" }]}>
                <Text fontSize={10} fontWeight="700" color="#2563EB">🚀 Dropshipping</Text>
              </View>
            )}
            {sellingType === "both" && (
              <View style={[s.typeBadge, { backgroundColor: "#FFFBEB" }]}>
                <Text fontSize={10} fontWeight="700" color="#D97706">🔄 Wholesale + Dropshipping</Text>
              </View>
            )}
          </View>

          {/* Price */}
          <View style={s.priceSection}>
            {isResellerActive ? (
              <>
                <Text fontSize={32} fontWeight="800" color={ACCENT} style={{ letterSpacing: -1 }}>
                  ৳{formatBDT(salePrice, 0)}
                </Text>
                {hasDiscount && (
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 8, marginTop: 2 }}>
                    <Text fontSize="$3" color={GREY} textDecorationLine="line-through">
                      ৳{regularPrice.toLocaleString()}
                    </Text>
                    <View style={s.discountBadge}>
                      <Text fontSize={11} fontWeight="800" color="#fff">-{discountPercent}%</Text>
                    </View>
                  </View>
                )}
              </>
            ) : (
              <View style={{ flexDirection: "row", alignItems: "center", gap: 10 }}>
                <Text fontSize={28} fontWeight="800" color="#999">***</Text>
                <View style={s.lockBadge}>
                  <Ionicons name="lock-closed" size={12} color={ACCENT} />
                  <Text fontSize={11} fontWeight="700" color={ACCENT}>
                    {isLoggedIn ? "Active profile required" : "Login to see price"}
                  </Text>
                </View>
              </View>
            )}
          </View>

          {/* Name */}
          <Text fontSize={18} fontWeight="600" color={DARK} mt="$2" lineHeight={24}>
            {product.ProductName}
          </Text>

          {/* Stock + SKU row */}
          <View style={s.metaRow}>
            {stockQty > 0 ? (
              <View style={[s.metaChip, { backgroundColor: "#E8F5E9" }]}>
                <View style={[s.metaDot, { backgroundColor: "#4CAF50" }]} />
                <Text fontSize={11} fontWeight="600" color="#2E7D32">In Stock</Text>
              </View>
            ) : (
              <View style={[s.metaChip, { backgroundColor: "#FFEBEE" }]}>
                <View style={[s.metaDot, { backgroundColor: "#E53935" }]} />
                <Text fontSize={11} fontWeight="600" color="#C62828">Out of Stock</Text>
              </View>
            )}
            {sku ? <View style={s.metaChip}><Text fontSize={11} color="#666">SKU: {sku}</Text></View> : null}
            {categoryName ? <View style={s.metaChip}><Text fontSize={11} color="#666">{categoryName}</Text></View> : null}
          </View>
        </View>

        {/* ═══ WHOLESALE BULK TIER BADGES ═══ */}
        {showWholesale && isResellerActive && (
          <View style={s.card}>
            <Text fontSize="$3" fontWeight="700" color={DARK} mb="$2">Wholesale Pricing</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
              {priceTiers.map((tier: any) => {
                const isActive = activeTier?.id === tier.id;
                const qtyLabel = tier.max_qty ? `${tier.min_qty}-${tier.max_qty} Pcs` : `${tier.min_qty}+ Pcs`;
                return (
                  <View
                    key={tier.id}
                    style={[
                      s.tierBadge,
                      isActive && { borderColor: ACCENT, backgroundColor: "#FFF0F5" },
                    ]}
                  >
                    <Text fontSize={14} fontWeight="800" color={isActive ? ACCENT : DARK}>
                      ৳{formatBDT(parseFloat(tier.unit_price) * commissionFactor, 0)}
                    </Text>
                    <Text fontSize={10} fontWeight="600" color={isActive ? ACCENT : GREY}>{qtyLabel}</Text>
                  </View>
                );
              })}
            </ScrollView>
            {totalQuantity > 0 && (
              <Text fontSize={11} color={GREY} mt="$1">Total selected: {totalQuantity} pcs</Text>
            )}
          </View>
        )}

        {/* ═══ VENDOR CARD ═══ */}
        {vendorName ? (
          <View style={s.card}>
            <View style={s.vendorRow}>
              <View style={s.vendorLogo}>
                <Text fontSize={13} fontWeight="800" color="#fff">
                  {vendorName.split(" ").map((w: string) => w[0]).join("").slice(0, 2).toUpperCase()}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text fontSize={10} color={GREY} style={{ textTransform: "uppercase", letterSpacing: 0.5 }}>Sold by</Text>
                <Text fontSize="$3" fontWeight="700" color={DARK}>{vendorName}</Text>
              </View>
              <View style={s.vendorArrow}>
                <Ionicons name="chevron-forward" size={14} color={GREY} />
              </View>
            </View>
          </View>
        ) : null}

        {/* ═══ VARIANT + SIZE ORDERING TABLE ═══ */}
        {isResellerActive && (
          <View style={s.card}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Select Items</Text>

            {/* Variant (Color) Tabs */}
            {variants.length > 0 && (
              <View style={{ marginBottom: 12 }}>
                <Text fontSize={12} fontWeight="600" color={GREY} mb="$1">
                  Color: {currentVariant?.title || currentVariant?.color_name || "Default"}
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                  {variants.map((v: any, idx: number) => {
                    const isAct = idx === activeVariantIdx;
                    const selectedVarQty = Object.values(variantQuantities[Number(v.id)] ?? {}).reduce((sum, qty) => sum + Number(qty || 0), 0);
                    const colorCode = typeof v.color_code === "string" ? v.color_code : "";
                    return (
                      <Pressable
                        key={v.id}
                        onPress={() => setActiveVariantIdx(idx)}
                        style={[s.variantTab, isAct && { borderColor: ACCENT, backgroundColor: "#FFF0F5" }]}
                      >
                        {/* Qty badge */}
                        {selectedVarQty > 0 && (
                          <View style={s.variantQtyBadge}>
                            <Text fontSize={9} fontWeight="800" color="#fff">{selectedVarQty}</Text>
                          </View>
                        )}
                        {v.image ? (
                          <Image source={{ uri: v.image }} style={s.variantTabImg} resizeMode="cover" />
                        ) : colorCode ? (
                          <View style={[s.variantSwatch, { backgroundColor: colorCode }]} />
                        ) : (
                          <View style={s.variantFallback}>
                            <Text fontSize={12} fontWeight="600" color={GREY}>{(v.color_name || v.title || "?").slice(0, 2)}</Text>
                          </View>
                        )}
                        <Text fontSize={10} fontWeight="600" color={isAct ? ACCENT : "#555"} numberOfLines={1}>
                          {v.color_name || v.title || `V${idx + 1}`}
                        </Text>
                      </Pressable>
                    );
                  })}
                </ScrollView>
              </View>
            )}

            {/* Size Rows */}
            <View style={s.sizeTable}>
              {/* Header */}
              <View style={s.sizeTableHeader}>
                <Text style={[s.sizeCol, { flex: 1.2 }]} fontSize={10} fontWeight="700" color={GREY}>SIZE</Text>
                <Text style={[s.sizeCol, { flex: 1.2 }]} fontSize={10} fontWeight="700" color={GREY}>PRICE</Text>
                <Text style={[s.sizeCol, { flex: 0.8, textAlign: "center" }]} fontSize={10} fontWeight="700" color={GREY}>STOCK</Text>
                <Text style={[s.sizeCol, { flex: 1.5, textAlign: "right" }]} fontSize={10} fontWeight="700" color={GREY}>QTY</Text>
              </View>

              {sizesForTable.map((sz) => {
                const size = sz.size_name;
                const qty = variantQuantities[currentVarId]?.[size] || 0;
                const displayPrice = getSizePrice(sz, qty);
                const rowSPStr = variantSellingPrices[currentVarId]?.[size] || "";
                const rowSP = rowSPStr ? parseFloat(rowSPStr) : 0;
                const rowEarnings = qty > 0 && rowSP >= displayPrice ? (rowSP - displayPrice) * qty : 0;
                const rowPriceInvalid = rowSPStr !== "" && rowSP < displayPrice;

                return (
                  <View key={size}>
                    <View style={[s.sizeTableRow, qty > 0 && { backgroundColor: "#FFF0F5" }]}>
                      <Text style={[s.sizeCol, { flex: 1.2 }]} fontSize={13} fontWeight="600" color={DARK}>{size}</Text>
                      <View style={[s.sizeCol, { flex: 1.2, flexDirection: "row", alignItems: "center", gap: 3 }]}>
                        <Text fontSize={13} fontWeight="600" color={DARK}>৳{formatBDT(displayPrice, 0)}</Text>
                        {sz.bulk_prices?.length > 0 && (
                          <View style={{ backgroundColor: "#ECFDF5", borderRadius: 3, paddingHorizontal: 3, paddingVertical: 1 }}>
                            <Text fontSize={8} fontWeight="800" color="#059669">BULK</Text>
                          </View>
                        )}
                      </View>
                      <Text style={[s.sizeCol, { flex: 0.8, textAlign: "center" }]} fontSize={12} fontWeight="500" color={sz.qty <= 0 ? "#EF4444" : "#666"}>
                        {sz.qty <= 0 ? "Out" : sz.qty}
                      </Text>
                      <View style={[s.sizeCol, { flex: 1.5, flexDirection: "row", alignItems: "center", justifyContent: "flex-end", gap: 2 }]}>
                        <Pressable
                          style={[s.miniStepBtn, qty <= 0 && s.miniStepBtnDisabled]}
                          onPress={() => handleQtyChange(currentVarId, size, "decrease", sz.qty)}
                          disabled={qty <= 0}
                        >
                          <Ionicons name="remove" size={14} color={qty <= 0 ? "#ccc" : DARK} />
                        </Pressable>
                        <View style={s.miniStepVal}>
                          <Text fontSize={13} fontWeight="800" color={qty > 0 ? ACCENT : DARK}>{qty}</Text>
                        </View>
                        <Pressable
                          style={[s.miniStepBtn, (qty >= sz.qty || sz.qty <= 0) && s.miniStepBtnDisabled]}
                          onPress={() => handleQtyChange(currentVarId, size, "increase", sz.qty)}
                          disabled={qty >= sz.qty || sz.qty <= 0}
                        >
                          <Ionicons name="add" size={14} color={(qty >= sz.qty || sz.qty <= 0) ? "#ccc" : ACCENT} />
                        </Pressable>
                      </View>
                    </View>

                    {/* Dropshipping selling price row */}
                    {showDropshipping && qty > 0 && (
                      <View style={s.sellingPriceRow}>
                        <Text fontSize={10} fontWeight="600" color={GREY}>My Price:</Text>
                        <TextInput
                          style={[
                            s.sellingPriceInput,
                            rowPriceInvalid && { borderColor: "#EF4444", backgroundColor: "#FEF2F2" },
                            rowSP >= displayPrice && rowSPStr ? { borderColor: "#059669", backgroundColor: "#ECFDF5" } : {},
                          ]}
                          keyboardType="numeric"
                          value={rowSPStr}
                          onChangeText={(v) => handleSellingPriceChange(currentVarId, size, v)}
                          placeholder={`≥${Math.ceil(displayPrice)}`}
                          placeholderTextColor="#aaa"
                        />
                        {rowEarnings > 0 ? (
                          <Text fontSize={11} fontWeight="700" color="#059669">+৳{formatBDT(rowEarnings, 0)}</Text>
                        ) : rowPriceInvalid ? (
                          <Text fontSize={10} fontWeight="600" color="#EF4444">Too low</Text>
                        ) : null}
                      </View>
                    )}
                  </View>
                );
              })}

              {/* Total Row */}
              {totalQuantity > 0 && (
                <View style={s.sizeTableTotal}>
                  <Text style={{ flex: 1 }} fontSize={13} fontWeight="800" color={DARK}>Total</Text>
                  <Text fontSize={13} fontWeight="800" color={ACCENT}>
                    ৳{formatBDT(getSelectedItems().reduce((sum, i) => sum + i.price * i.qty, 0), 0)}
                  </Text>
                  <Text fontSize={12} fontWeight="700" color={GREY} ml="$3">{totalQuantity} pcs</Text>
                </View>
              )}
            </View>
          </View>
        )}

        {/* ═══ YOUTUBE ═══ */}
        {youtubeLink ? (
          <Pressable style={s.youtubeCard} onPress={() => Linking.openURL(youtubeLink)}>
            <View style={s.ytIcon}><Ionicons name="play" size={18} color="#fff" /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Watch Product Video</Text>
              <Text fontSize={11} color={GREY}>See it in action on YouTube</Text>
            </View>
            <Ionicons name="open-outline" size={16} color={ACCENT} />
          </Pressable>
        ) : null}

        {/* ═══ DESCRIPTION / SPECS TABS ═══ */}
        <View style={s.card}>
          <View style={s.tabBar}>
            <Pressable style={[s.tab, activeTab === "desc" && s.tabActive]} onPress={() => setActiveTab("desc")}>
              <Text fontSize="$3" fontWeight="600" color={activeTab === "desc" ? ACCENT : GREY}>Description</Text>
            </Pressable>
            <Pressable style={[s.tab, activeTab === "specs" && s.tabActive]} onPress={() => setActiveTab("specs")}>
              <Text fontSize="$3" fontWeight="600" color={activeTab === "specs" ? ACCENT : GREY}>Specifications</Text>
            </Pressable>
          </View>

          {activeTab === "desc" && cleanDesc ? (
            <View style={{ marginTop: 12 }}>
              <Text fontSize="$3" color="#444" lineHeight={22} numberOfLines={descExpanded ? undefined : 5}>
                {cleanDesc}
              </Text>
              {cleanDesc.length > 200 && (
                <Pressable onPress={() => setDescExpanded(p => !p)} style={s.readMoreBtn}>
                  <Text fontSize="$2" fontWeight="600" color={ACCENT}>
                    {descExpanded ? "Show less" : "Read more"}
                  </Text>
                  <Ionicons name={descExpanded ? "chevron-up" : "chevron-down"} size={14} color={ACCENT} />
                </Pressable>
              )}
            </View>
          ) : null}

          {activeTab === "specs" && (
            <View style={{ marginTop: 12 }}>
              {[
                product.color && ["Color", product.color],
                product.size && ["Size", typeof product.size === "string" ? product.size : JSON.stringify(product.size)],
                (product.weight || product.product_weight) && ["Weight", product.weight ?? product.product_weight],
                sku && ["SKU", sku],
                categoryName && ["Category", categoryName],
                product.minimum_qty && ["Min. Order", String(product.minimum_qty)],
                product.shipping_days && ["Delivery", `${product.shipping_days} days`],
              ].filter(Boolean).map(([label, value], i) => (
                <View key={i} style={s.specRow}>
                  <Text style={s.specLabel}>{label as string}</Text>
                  <Text style={s.specValue}>{value as string}</Text>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* ═══ DELIVERY INFO ═══ */}
        <View style={s.card}>
          <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Delivery & Returns</Text>
          <View style={s.deliveryRow}>
            <View style={s.deliveryIcon}><Ionicons name="cube-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Standard Delivery</Text>
              <Text fontSize={11} color={GREY}>{product.shipping_days ? `Estimated ${product.shipping_days} days` : "Delivery time varies"}</Text>
            </View>
          </View>
          <View style={s.deliveryRow}>
            <View style={s.deliveryIcon}><Ionicons name="refresh-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Easy Returns</Text>
              <Text fontSize={11} color={GREY}>Hassle-free return policy</Text>
            </View>
          </View>
          <View style={[s.deliveryRow, { borderBottomWidth: 0 }]}>
            <View style={s.deliveryIcon}><Ionicons name="shield-checkmark-outline" size={18} color={ACCENT} /></View>
            <View style={{ flex: 1 }}>
              <Text fontSize="$3" fontWeight="600" color={DARK}>Secure Payment</Text>
              <Text fontSize={11} color={GREY}>Your payment is safe with us</Text>
            </View>
          </View>
        </View>

        {/* ═══ RELATED PRODUCTS ═══ */}
        {relatedProducts.length > 0 && (
          <View style={{ paddingTop: 16 }}>
            <Text fontSize="$5" fontWeight="700" color={DARK} ml={20} mb="$3">You May Also Like</Text>
            <FlatList
              data={relatedProducts.slice(0, 10)} horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={{ paddingHorizontal: 20, gap: 12 }}
              keyExtractor={(item: any) => String(item.id)}
              renderItem={({ item }: any) => (
                <ProductCard
                  name={item.ProductName}
                  price={String(item.storefront_price ?? item.ProductSalePrice ?? item.ProductRegularPrice)}
                  image={item.ViewProductImage}
                  slug={item.ProductSlug}
                  variant="horizontal"
                />
              )}
            />
          </View>
        )}
      </ScrollView>

      {/* ═══ STICKY BOTTOM BAR ═══ */}
      <View style={[s.bottomBar, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        {isResellerActive ? (
          <>
            <Pressable
              style={({ pressed }) => [s.cartBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={handleAddToCart}
              disabled={addToCartMutation.isPending || totalQuantity === 0}
            >
              {addToCartMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <Ionicons name="cart-outline" size={18} color="#fff" />
                  <Text fontSize="$3" fontWeight="700" color="#fff">
                    {totalQuantity > 0 ? `Add (${totalQuantity})` : "Add to Cart"}
                  </Text>
                </>
              )}
            </Pressable>
            <Pressable
              style={({ pressed }) => [s.buyBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={handleBuyNow}
              disabled={addToCartMutation.isPending || totalQuantity === 0}
            >
              <Text fontSize="$3" fontWeight="700" color="#fff">Buy Now</Text>
            </Pressable>
          </>
        ) : (
          <Pressable
            style={({ pressed }) => [s.lockedBtn, pressed && { opacity: 0.85 }]}
            onPress={() => {
              if (!isLoggedIn) {
                router.push({ pathname: "/login", params: { returnTo: `/product-detail?slug=${slug}` } });
              } else {
                setShowActivation(true);
              }
            }}
          >
            <Ionicons name="lock-closed" size={16} color="#fff" />
            <Text fontSize="$3" fontWeight="700" color="#fff">
              {isLoggedIn ? "Activate Profile to Order" : "Login to Order"}
            </Text>
          </Pressable>
        )}
      </View>

      {/* Activation Bottom Sheet Modal */}
      <Modal
        visible={showActivation}
        transparent
        statusBarTranslucent
        animationType="fade"
        onRequestClose={() => setShowActivation(false)}
      >
        <Pressable style={s.sheetOverlay} onPress={() => setShowActivation(false)}>
          <Animated.View style={[s.sheetContainer, { transform: [{ translateY: sheetAnim }] }]}>
            <View style={s.sheetHandle} />
            <View style={s.sheetIconWrap}>
              <Ionicons name="shield-checkmark" size={36} color={ACCENT} />
            </View>
            <Text fontSize="$6" fontWeight="bold" color={DARK} textAlign="center" mt="$3">
              Activate Your Profile
            </Text>
            <Text fontSize="$3" color={GREY} textAlign="center" mt="$2" lineHeight={20} mx="$2">
              You need an active reseller profile to view prices and place orders.{"\n"}
              Upgrade your membership to unlock wholesale pricing, place bulk orders, and access exclusive deals.
            </Text>
            <View style={s.sheetBenefits}>
              {[
                { icon: "pricetag", text: "View wholesale prices" },
                { icon: "cart", text: "Place orders & buy products" },
                { icon: "flash", text: "Access flash deals & discounts" },
              ].map((b, i) => (
                <View key={i} style={s.sheetBenefitRow}>
                  <Ionicons name={b.icon as any} size={18} color={ACCENT} />
                  <Text fontSize="$3" color={DARK} ml="$2">{b.text}</Text>
                </View>
              ))}
            </View>
            <Pressable
              style={({ pressed }) => [s.sheetCta, pressed && { opacity: 0.85 }]}
              onPress={() => {
                setShowActivation(false);
                router.push("/pricing");
              }}
            >
              <Ionicons name="rocket" size={18} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff" ml="$2">Activate Now</Text>
            </Pressable>
            <Pressable onPress={() => setShowActivation(false)} style={{ marginTop: 12 }}>
              <Text fontSize="$3" color={GREY} textAlign="center">Maybe later</Text>
            </Pressable>
          </Animated.View>
        </Pressable>
      </Modal>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: BG },
  loadC: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#fff" },
  errorIcon: {
    width: 80, height: 80, borderRadius: 40, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },
  goBackBtn: {
    flexDirection: "row", alignItems: "center", gap: 6, marginTop: 20,
    backgroundColor: ACCENT, borderRadius: 12, paddingHorizontal: 20, paddingVertical: 12,
  },

  // Hero
  hero: { position: "relative", backgroundColor: "#fff" },
  heroImg: { width: SW, height: SW, backgroundColor: "#fff" },
  overlayBtn: {
    position: "absolute", width: 38, height: 38, borderRadius: 19,
    backgroundColor: "rgba(255,255,255,0.95)", justifyContent: "center", alignItems: "center",
    elevation: 4, shadowColor: "#000", shadowOpacity: 0.12, shadowRadius: 6, shadowOffset: { width: 0, height: 2 },
  },
  imgCounter: {
    position: "absolute", bottom: 12, right: 16,
    backgroundColor: "rgba(0,0,0,0.5)", borderRadius: 12, paddingHorizontal: 10, paddingVertical: 4,
  },

  // Thumbnails
  thumbRow: { paddingHorizontal: 16, paddingVertical: 8, gap: 6, backgroundColor: "#fff" },
  thumbImg: {
    width: THUMB_SIZE, height: THUMB_SIZE, borderRadius: 8,
    borderWidth: 2, borderColor: "transparent", backgroundColor: "#F8F8F8",
  },
  thumbActive: { borderColor: ACCENT },

  // Cards
  card: {
    backgroundColor: "#fff", marginHorizontal: 12, marginTop: 10, borderRadius: 16,
    padding: 16, shadowColor: "#000", shadowOpacity: 0.04, shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 }, elevation: 1,
  },
  mainCard: {
    backgroundColor: "#fff", marginHorizontal: 0, borderRadius: 0,
    paddingHorizontal: 20, paddingTop: 20, paddingBottom: 16,
  },

  // Type badge
  typeBadge: { borderRadius: 8, paddingHorizontal: 10, paddingVertical: 4 },

  // Flash Sale
  flashBanner: {
    backgroundColor: ACCENT, borderRadius: 12, padding: 12, marginBottom: 14,
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
  },
  flashLeft: { flexDirection: "row", alignItems: "center", gap: 10 },

  // Price
  priceSection: {},
  discountBadge: { backgroundColor: "#FF3D71", borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2 },
  lockBadge: {
    flexDirection: "row", alignItems: "center", gap: 5,
    backgroundColor: "#FFF0F5", borderRadius: 8, paddingHorizontal: 12, paddingVertical: 6,
  },

  // Meta
  metaRow: { flexDirection: "row", flexWrap: "wrap", gap: 6, marginTop: 12 },
  metaChip: {
    flexDirection: "row", alignItems: "center", gap: 5,
    backgroundColor: BG, borderRadius: 8, paddingHorizontal: 10, paddingVertical: 5,
  },
  metaDot: { width: 6, height: 6, borderRadius: 3 },

  // Tier badges
  tierBadge: {
    alignItems: "center", paddingHorizontal: 16, paddingVertical: 10,
    borderRadius: 12, borderWidth: 1.5, borderColor: "#E5E5EA", backgroundColor: "#fff",
    minWidth: 100,
  },

  // Vendor
  vendorRow: { flexDirection: "row", alignItems: "center", gap: 10 },
  vendorLogo: {
    width: 38, height: 38, borderRadius: 19,
    backgroundColor: ACCENT, justifyContent: "center", alignItems: "center",
  },
  vendorArrow: {
    width: 28, height: 28, borderRadius: 14, backgroundColor: BG,
    justifyContent: "center", alignItems: "center",
  },

  // Variant tabs
  variantTab: {
    alignItems: "center", gap: 4, padding: 6, borderRadius: 10,
    borderWidth: 1.5, borderColor: "#E5E5EA", minWidth: 60, position: "relative",
  },
  variantTabImg: { width: 40, height: 40, borderRadius: 8, backgroundColor: BG },
  variantSwatch: { width: 40, height: 40, borderRadius: 20, borderWidth: 2, borderColor: "#E0E0E5" },
  variantFallback: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: BG,
    justifyContent: "center", alignItems: "center", borderWidth: 1, borderColor: "#E0E0E5",
  },
  variantQtyBadge: {
    position: "absolute", top: -6, right: -6, zIndex: 1,
    minWidth: 18, height: 18, borderRadius: 9, backgroundColor: ACCENT,
    justifyContent: "center", alignItems: "center", paddingHorizontal: 3,
  },

  // Size table
  sizeTable: { borderWidth: 1, borderColor: "#E5E5EA", borderRadius: 12, overflow: "hidden" },
  sizeTableHeader: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 8,
    backgroundColor: "#F9F9FB",
  },
  sizeTableRow: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 10,
    borderTopWidth: 1, borderTopColor: "#F0F0F5",
  },
  sizeTableTotal: {
    flexDirection: "row", alignItems: "center", paddingHorizontal: 10, paddingVertical: 10,
    borderTopWidth: 2, borderTopColor: "#E5E5EA", backgroundColor: "#F9F9FB",
  },
  sizeCol: {},
  miniStepBtn: {
    width: 28, height: 28, borderRadius: 8, borderWidth: 1.5, borderColor: "#E5E5EA",
    justifyContent: "center", alignItems: "center", backgroundColor: "#fff",
  },
  miniStepBtnDisabled: { borderColor: "#F0F0F5", backgroundColor: "#FAFAFA" },
  miniStepVal: { minWidth: 28, alignItems: "center", justifyContent: "center" },

  // Selling price
  sellingPriceRow: {
    flexDirection: "row", alignItems: "center", gap: 8,
    paddingHorizontal: 10, paddingBottom: 8, paddingTop: 2,
    borderTopWidth: 0,
  },
  sellingPriceInput: {
    width: 80, height: 30, borderRadius: 8, borderWidth: 1, borderColor: "#E5E5EA",
    textAlign: "center", fontSize: 13, fontWeight: "600", backgroundColor: "#fff",
    paddingHorizontal: 4,
  },

  // YouTube
  youtubeCard: {
    flexDirection: "row", alignItems: "center", gap: 12,
    backgroundColor: "#fff", marginHorizontal: 12, marginTop: 10,
    borderRadius: 16, padding: 14,
    elevation: 1, shadowColor: "#000", shadowOpacity: 0.04, shadowRadius: 8, shadowOffset: { width: 0, height: 2 },
  },
  ytIcon: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: "#FF0000",
    justifyContent: "center", alignItems: "center",
  },

  // Tabs
  tabBar: { flexDirection: "row", gap: 4 },
  tab: { paddingVertical: 8, paddingHorizontal: 14, borderRadius: 8 },
  tabActive: { backgroundColor: "#FFF0F5" },

  // Specs
  specRow: {
    flexDirection: "row", paddingVertical: 10,
    borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
  },
  specLabel: { width: "35%", fontSize: 13, color: GREY, fontWeight: "500" as any },
  specValue: { flex: 1, fontSize: 13, color: DARK, fontWeight: "600" as any },

  // Read more
  readMoreBtn: {
    flexDirection: "row", alignItems: "center", gap: 4, marginTop: 8, alignSelf: "flex-start",
  },

  // Delivery
  deliveryRow: {
    flexDirection: "row", alignItems: "center", gap: 12,
    paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
  },
  deliveryIcon: {
    width: 36, height: 36, borderRadius: 10, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },

  // Bottom bar
  bottomBar: {
    position: "absolute", bottom: 0, left: 0, right: 0,
    flexDirection: "row", alignItems: "center",
    paddingTop: 12, paddingHorizontal: 16, gap: 10,
    backgroundColor: "#fff", borderTopWidth: 1, borderTopColor: "#F0F0F0",
    elevation: 10, shadowColor: "#000", shadowOpacity: 0.1, shadowRadius: 10, shadowOffset: { width: 0, height: -4 },
  },
  cartBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: DARK,
    flexDirection: "row", justifyContent: "center", alignItems: "center", gap: 6,
  },
  buyBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: ACCENT,
    justifyContent: "center", alignItems: "center",
  },
  lockedBtn: {
    flex: 1, height: 50, borderRadius: 14, backgroundColor: ACCENT,
    flexDirection: "row", justifyContent: "center", alignItems: "center", gap: 8,
  },

  // Activation Bottom Sheet
  sheetOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.4)", justifyContent: "flex-end" },
  sheetContainer: {
    backgroundColor: "#fff", borderTopLeftRadius: 24, borderTopRightRadius: 24,
    padding: 24, paddingBottom: 36, alignItems: "center",
  },
  sheetHandle: { width: 40, height: 4, borderRadius: 2, backgroundColor: "#E0E0E0", marginBottom: 16 },
  sheetIconWrap: {
    width: 72, height: 72, borderRadius: 36, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },
  sheetBenefits: {
    width: "100%", marginTop: 20, gap: 12,
    backgroundColor: "#FAFAFA", borderRadius: 14, padding: 16,
  },
  sheetBenefitRow: { flexDirection: "row", alignItems: "center" },
  sheetCta: {
    flexDirection: "row", alignItems: "center", justifyContent: "center",
    backgroundColor: ACCENT, borderRadius: 30, paddingVertical: 16,
    width: "100%", marginTop: 20, gap: 8,
  },
});
