import { useState, useCallback, useRef, useEffect } from "react";
import {
  View, ScrollView, Image, Pressable, StyleSheet, Dimensions,
  ActivityIndicator, FlatList, type ViewToken, Linking, Modal, Animated,
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
  const [qty, setQty] = useState(1);
  const [imgIdx, setImgIdx] = useState(0);
  const [wishlisted, setWishlisted] = useState(false);
  const [activeTab, setActiveTab] = useState<"desc" | "specs">("desc");
  const [descExpanded, setDescExpanded] = useState(false);
  const [showActivation, setShowActivation] = useState(false);
  const { isActive: isResellerActive, isLoggedIn } = useIsActiveReseller();
  const imgRef = useRef<FlatList>(null);

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

  // ── Shop hooks (MUST be before early returns to respect Rules of Hooks) ──
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

  // Pricing
  const salePrice = Number(product.storefront_price ?? product.ProductSalePrice ?? 0);
  const regularPrice = Number(product.ProductRegularPrice ?? 0);
  const hasDiscount = regularPrice > salePrice && regularPrice > 0;
  const discountPercent = hasDiscount ? Math.round(((regularPrice - salePrice) / regularPrice) * 100) : 0;

  // Variants
  const variants: any[] = product.varients ?? [];

  // Meta
  const categoryName = product.categories?.category_name ?? "";
  const vendorName = product.vendor?.company_name ?? product.vendor?.CompanyName ?? "";
  const minQty = Number(product.minimum_qty ?? 1);
  const stockQty = Number(product.qty ?? 0);
  const sku = product.ProductSku ?? "";
  const youtubeLink = product.youtube_link ?? "";

  // Description
  const rawDesc = product.ProductDetails ?? product.ProductBreaf ?? "";
  const cleanDesc = rawDesc.replace(/<[^>]*>/g, "").replace(/&nbsp;/g, " ").trim();

  const scrollToImage = (idx: number) => {
    imgRef.current?.scrollToIndex({ index: idx, animated: true });
    setImgIdx(idx);
  };

  return (
    <View style={s.container}>
      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{ paddingBottom: 110 }}>

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
          {/* Image counter badge */}
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
                <Image
                  source={{ uri: img }}
                  style={[s.thumbImg, imgIdx === i && s.thumbActive]}
                  resizeMode="cover"
                />
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

          {/* Price */}
          <View style={s.priceSection}>
            {isResellerActive ? (
              <>
                <Text fontSize={32} fontWeight="800" color={ACCENT} style={{ letterSpacing: -1 }}>
                  ৳{salePrice.toLocaleString()}
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

          {/* Product Name */}
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
            {sku ? (
              <View style={s.metaChip}>
                <Text fontSize={11} color="#666">SKU: {sku}</Text>
              </View>
            ) : null}
            {categoryName ? (
              <View style={s.metaChip}>
                <Text fontSize={11} color="#666">{categoryName}</Text>
              </View>
            ) : null}
          </View>
        </View>

        {/* ═══ QUANTITY ═══ */}
        {isResellerActive && (
          <View style={s.card}>
            <View style={s.qtyRow}>
              <View>
                <Text fontSize="$4" fontWeight="700" color={DARK}>Quantity</Text>
                <Text fontSize={11} color={GREY}>Min order: {minQty}</Text>
              </View>
              <View style={s.stepper}>
                <Pressable
                  style={[s.stepBtn, qty <= minQty && s.stepBtnDisabled]}
                  onPress={() => setQty(p => Math.max(minQty, p - 1))}
                  disabled={qty <= minQty}
                >
                  <Ionicons name="remove" size={18} color={qty <= minQty ? "#ccc" : ACCENT} />
                </Pressable>
                <View style={s.stepVal}>
                  <Text fontSize="$5" fontWeight="800" color={DARK}>{qty}</Text>
                </View>
                <Pressable style={s.stepBtn} onPress={() => setQty(p => p + 1)}>
                  <Ionicons name="add" size={18} color={ACCENT} />
                </Pressable>
              </View>
            </View>
            {/* Subtotal */}
            <View style={s.subtotalRow}>
              <Text fontSize="$3" color={GREY}>Subtotal</Text>
              <Text fontSize="$5" fontWeight="800" color={DARK}>৳{(salePrice * qty).toLocaleString()}</Text>
            </View>
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

        {/* ═══ VARIANTS ═══ */}
        {variants.length > 0 && (
          <View style={s.card}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Colors & Variants</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 12 }}>
              {variants.map((v: any, i: number) => (
                <View key={v.id} style={s.variantItem}>
                  {v.image ? (
                    <Image source={{ uri: v.image }} style={s.variantImg} resizeMode="cover" />
                  ) : v.color_code ? (
                    <View style={[s.variantSwatch, { backgroundColor: v.color_code }]} />
                  ) : (
                    <View style={s.variantFallback}>
                      <Text fontSize={12} fontWeight="600" color={GREY}>{(v.color_name || v.title || "?").slice(0, 2)}</Text>
                    </View>
                  )}
                  <Text fontSize={10} color="#555" numberOfLines={1} textAlign="center">
                    {v.color_name || v.title || `V${i + 1}`}
                  </Text>
                </View>
              ))}
            </ScrollView>
          </View>
        )}

        {/* ═══ YOUTUBE ═══ */}
        {youtubeLink ? (
          <Pressable style={s.youtubeCard} onPress={() => Linking.openURL(youtubeLink)}>
            <View style={s.ytIcon}>
              <Ionicons name="play" size={18} color="#fff" />
            </View>
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
              onPress={() => toast.success("Added to cart")}
            >
              <Ionicons name="cart-outline" size={18} color="#fff" />
              <Text fontSize="$3" fontWeight="700" color="#fff">Add to Cart</Text>
            </Pressable>
            <Pressable
              style={({ pressed }) => [s.buyBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={() => toast.success("Proceeding to checkout")}
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
            {/* Handle bar */}
            <View style={s.sheetHandle} />

            {/* Icon */}
            <View style={s.sheetIconWrap}>
              <Ionicons name="shield-checkmark" size={36} color={ACCENT} />
            </View>

            {/* Title */}
            <Text fontSize="$6" fontWeight="bold" color={DARK} text="center" mt="$3">
              Activate Your Profile
            </Text>

            {/* Description */}
            <Text fontSize="$3" color={GREY} text="center" mt="$2" lineHeight={20} mx="$2">
              You need an active reseller profile to view prices and place orders.{"\n"}
              Upgrade your membership to unlock wholesale pricing, place bulk orders, and access exclusive deals.
            </Text>

            {/* Benefits */}
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

            {/* CTA */}
            <Pressable
              style={({ pressed }) => [s.sheetCta, pressed && { opacity: 0.85 }]}
              onPress={() => {
                setShowActivation(false);
                router.push("/pricing");
              }}
            >
              <Ionicons name="rocket" size={18} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff" ml="$2">
                Activate Now
              </Text>
            </Pressable>

            {/* Dismiss */}
            <Pressable onPress={() => setShowActivation(false)} style={{ marginTop: 12 }}>
              <Text fontSize="$3" color={GREY} text="center">
                Maybe later
              </Text>
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

  // Flash Sale
  flashBanner: {
    backgroundColor: ACCENT, borderRadius: 12, padding: 12, marginBottom: 14,
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
  },
  flashLeft: { flexDirection: "row", alignItems: "center", gap: 10 },

  // Price
  priceSection: { },
  discountBadge: {
    backgroundColor: "#FF3D71", borderRadius: 6, paddingHorizontal: 8, paddingVertical: 2,
  },
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

  // Variants
  variantItem: { alignItems: "center", gap: 4, width: 56 },
  variantImg: {
    width: 44, height: 44, borderRadius: 10, backgroundColor: BG, borderWidth: 1, borderColor: "#E5E5E5",
  },
  variantSwatch: {
    width: 44, height: 44, borderRadius: 22, borderWidth: 2, borderColor: "#E0E0E5",
  },
  variantFallback: {
    width: 44, height: 44, borderRadius: 22, backgroundColor: BG,
    justifyContent: "center", alignItems: "center", borderWidth: 1, borderColor: "#E0E0E5",
  },

  // Quantity
  qtyRow: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  stepper: { flexDirection: "row", alignItems: "center", gap: 4 },
  stepBtn: {
    width: 38, height: 38, borderRadius: 12,
    borderWidth: 1.5, borderColor: "#E5E5E5", justifyContent: "center", alignItems: "center",
    backgroundColor: "#fff",
  },
  stepBtnDisabled: { borderColor: "#F0F0F5", backgroundColor: "#FAFAFA" },
  stepVal: { minWidth: 40, alignItems: "center", justifyContent: "center", paddingHorizontal: 4 },
  subtotalRow: {
    flexDirection: "row", justifyContent: "space-between", alignItems: "center",
    marginTop: 12, paddingTop: 12, borderTopWidth: 1, borderTopColor: "#F5F5F5",
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
  specLabel: { width: "35%", fontSize: 13, color: GREY, fontWeight: "500" },
  specValue: { flex: 1, fontSize: 13, color: DARK, fontWeight: "600" },

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
  sheetOverlay: {
    flex: 1, backgroundColor: "rgba(0,0,0,0.4)", justifyContent: "flex-end",
  },
  sheetContainer: {
    backgroundColor: "#fff", borderTopLeftRadius: 24, borderTopRightRadius: 24,
    padding: 24, paddingBottom: 36, alignItems: "center",
  },
  sheetHandle: {
    width: 40, height: 4, borderRadius: 2, backgroundColor: "#E0E0E0", marginBottom: 16,
  },
  sheetIconWrap: {
    width: 72, height: 72, borderRadius: 36, backgroundColor: "#FFF0F5",
    justifyContent: "center", alignItems: "center",
  },
  sheetBenefits: {
    width: "100%", marginTop: 20, gap: 12,
    backgroundColor: "#FAFAFA", borderRadius: 14, padding: 16,
  },
  sheetBenefitRow: {
    flexDirection: "row", alignItems: "center",
  },
  sheetCta: {
    flexDirection: "row", alignItems: "center", justifyContent: "center",
    backgroundColor: ACCENT, borderRadius: 30, paddingVertical: 16,
    width: "100%", marginTop: 20, gap: 8,
  },
});
