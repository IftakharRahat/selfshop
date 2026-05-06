import { useRef, useState, useCallback, useEffect } from "react";
import { router } from "expo-router";
import {
  ScrollView,
  View,
  FlatList,
  Dimensions,
  StyleSheet,
  Pressable,
  Image,
  type ViewToken,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { Text } from "tamagui";
import { useQuery } from "@tanstack/react-query";

import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { SearchBar } from "@/components/search-bar";
import { HomeSkeleton } from "@/components/skeleton";
import { CategoryChip } from "@/components/category-chip";
import { ProductCard } from "@/components/product-card";
import apiClient from "@/lib/api-client";
import { useSession } from "@/lib/auth-client";

const { width } = Dimensions.get("window");

/* ── Image URL helper (mirrors web getImageUrl logic) ── */
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

function SectionHeader({ title, onSeeAll }: { title: string; onSeeAll: () => void }) {
  return (
    <View style={styles.sectionHeader}>
      <Text fontSize="$5" fontWeight="bold" color="#1A1A2E">
        {title}
      </Text>
      <Pressable onPress={onSeeAll} style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
        <Text fontSize="$3" color="#E5005F" fontWeight="600">
          See All
        </Text>
        <View style={{ width: 28, height: 28, borderRadius: 14, backgroundColor: "#E5005F", justifyContent: "center", alignItems: "center" }}>
          <Ionicons name="arrow-forward" size={14} color="#fff" />
        </View>
      </Pressable>
    </View>
  );
}

export default function HomeScreen() {
  const insets = useSafeAreaInsets();
  const [bannerIndex, setBannerIndex] = useState(0);
  const bannerRef = useRef<FlatList>(null);

  /* ── Queries ── */
  const sliders = useQuery({
    queryKey: ["sliders"],
    queryFn: async () => {
      const { data } = await apiClient.get("/sliders");
      return (data?.data ?? []).map((s: any) => ({
        id: String(s.id ?? s.slider_title ?? Math.random()),
        title: s.slider_title ?? "",
        image: resolveImageUrl(s.slider_image),
        link: s.slider_btn_link ?? "",
      }));
    },
  });

  const categories = useQuery({
    queryKey: ["categories", "active"],
    queryFn: async () => {
      const { data } = await apiClient.get("/categories");
      return data?.data ?? data ?? [];
    },
  });

  const newProducts = useQuery({
    queryKey: ["new-products"],
    queryFn: async () => {
      const { data } = await apiClient.get("/new-products");
      return data?.data?.data ?? data?.data ?? [];
    },
  });

  const featuredProducts = useQuery({
    queryKey: ["featured-products"],
    queryFn: async () => {
      const { data } = await apiClient.get("/featured-products");
      return data?.data?.data ?? data?.data ?? [];
    },
  });

  const flashSale = useQuery({
    queryKey: ["flash-sale"],
    queryFn: async () => {
      const { data } = await apiClient.get("/flash-sale");
      return data?.data ?? data;
    },
    staleTime: 60 * 1000,
  });

  const brands = useQuery({
    queryKey: ["brands"],
    queryFn: async () => {
      const { data } = await apiClient.get("/brands");
      return (data?.data ?? data ?? []).map((b: any) => ({
        id: b.id,
        name: b.brand_name ?? b.name ?? "",
        icon: resolveImageUrl(b.brand_icon ?? b.icon),
        slug: b.slug,
      }));
    },
  });

  const promotionalSections = useQuery({
    queryKey: ["promotional-sections"],
    queryFn: async () => {
      const { data } = await apiClient.get("/promotional-sections");
      return (data?.data ?? []).map((s: any) => ({
        ...s,
        banner_image: resolveImageUrl(s.banner_image),
        products: (s.products ?? []).map((p: any) => ({
          ...p,
          ViewProductImage: resolveImageUrl(p.ViewProductImage),
        })),
      }));
    },
  });

  const popularSuppliers = useQuery({
    queryKey: ["popular-vendors"],
    queryFn: async () => {
      const { data } = await apiClient.get("/popular-vendors");
      return data?.data ?? data ?? [];
    },
  });

  const bannerList = sliders.data ?? [];

  // Auto-scroll banners
  useEffect(() => {
    if (bannerList.length < 2) return;
    const timer = setInterval(() => {
      setBannerIndex((prev) => {
        const next = (prev + 1) % bannerList.length;
        bannerRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 4000);
    return () => clearInterval(timer);
  }, [bannerList.length]);

  const onBannerViewableChange = useCallback(
    ({ viewableItems }: { viewableItems: ViewToken[] }) => {
      if (viewableItems.length > 0 && viewableItems[0].index !== null) {
        setBannerIndex(viewableItems[0].index);
      }
    },
    [],
  );

  const bannerViewConfig = useRef({ viewAreaCoveragePercentThreshold: 50 }).current;

  const allFeatured = featuredProducts.data ?? [];
  const newArrivals = newProducts.data ?? [];
  const categoryList = categories.data ?? [];
  const brandList: any[] = brands.data ?? [];
  const promoSections: any[] = promotionalSections.data ?? [];
  const supplierList: any[] = popularSuppliers.data ?? [];

  /* ── Notification badge count ── */
  const { data: session } = useSession();
  const notificationsQuery = useQuery({
    queryKey: ["notifications-count"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-notification?unread_only=true&per_page=1");
      return data?.unread_count ?? 0;
    },
    enabled: !!session?.user,
    staleTime: 30 * 1000,
  });
  const unreadCount = notificationsQuery.data ?? 0;

  if (newProducts.isLoading && categories.isLoading) {
    return <HomeSkeleton />;
  }

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {/* Header */}
      <View style={[styles.header, { paddingTop: insets.top + 12 }]}>
        <Text fontSize="$7" fontWeight="bold" color="#1A1A2E">
          SelfShop
        </Text>
        <View style={styles.headerRight}>
          <Pressable
            style={styles.headerIconButton}
            onPress={() => router.push("/account/notifications" as any)}
          >
            <Ionicons name="notifications-outline" size={22} color="#1A1A2E" />
            {unreadCount > 0 && (
              <View style={styles.headerBadge}>
                <Text style={styles.headerBadgeText}>
                  {unreadCount > 9 ? "9+" : unreadCount}
                </Text>
              </View>
            )}
          </Pressable>
          <Pressable
            style={styles.headerIconButton}
            onPress={() => router.push("/account/my-shop" as any)}
          >
            <Ionicons name="storefront-outline" size={22} color="#1A1A2E" />
          </Pressable>
        </View>
      </View>

      {/* Search Bar */}
      <View style={styles.section}>
        <SearchBar placeholder="Search on SelfShop..." />
      </View>

      {/* Banner Carousel */}
      {bannerList.length > 0 && (
        <View style={styles.bannerSection}>
          <FlatList
            ref={bannerRef}
            data={bannerList}
            horizontal
            pagingEnabled
            showsHorizontalScrollIndicator={false}
            bounces={false}
            onViewableItemsChanged={onBannerViewableChange}
            viewabilityConfig={bannerViewConfig}
            keyExtractor={(item) => item.id}
            renderItem={({ item }) => (
              <View style={styles.bannerCard}>
                {item.image ? (
                  <Image
                    source={{ uri: item.image }}
                    style={styles.bannerImage}
                    resizeMode="cover"
                  />
                ) : (
                  <View style={styles.bannerPlaceholder}>
                    <Ionicons name="image-outline" size={36} color="#ccc" />
                  </View>
                )}
              </View>
            )}
          />
          {/* Banner Dots */}
          <View style={styles.bannerDots}>
            {bannerList.map((_: any, i: number) => (
              <View
                key={i}
                style={[
                  styles.dot,
                  bannerIndex === i ? styles.dotActive : styles.dotInactive,
                ]}
              />
            ))}
          </View>
        </View>
      )}

      {/* Categories – 2-row horizontal scroll */}
      {categoryList.length > 0 && (
        <View style={styles.sectionContainer}>
          <SectionHeader title="Categories" onSeeAll={() => router.push("/categories")} />
          <FlatList
            data={
              /* group into columns of 2 */
              categoryList.reduce((cols: any[][], cat: any, idx: number) => {
                if (idx % 2 === 0) cols.push([cat]);
                else cols[cols.length - 1].push(cat);
                return cols;
              }, [] as any[][])
            }
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.categoryGrid}
            keyExtractor={(_: any, idx: number) => `cat-col-${idx}`}
            renderItem={({ item: column }: any) => (
              <View style={styles.categoryColumn}>
                {column.map((cat: any) => (
                  <CategoryChip
                    key={cat.id}
                    name={cat.category_name}
                    image={cat.category_icon}
                    onPress={() => router.push({
                      pathname: "/category-products",
                      params: { type: "category", slug: cat.slug, title: cat.category_name },
                    } as any)}
                  />
                ))}
              </View>
            )}
          />
        </View>
      )}

      {/* Popular Suppliers */}
      {supplierList.length > 0 && (
        <View style={styles.sectionContainer}>
          <SectionHeader title="Popular Suppliers" onSeeAll={() => router.push("/all-suppliers" as any)} />
          <FlatList
            data={supplierList.slice(0, 10)}
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.supplierScrollList}
            keyExtractor={(item: any) => String(item.id)}
            renderItem={({ item }: any) => {
              const logo = resolveImageUrl(item.shop_logo ?? item.logo);
              const name = item.shop_name ?? item.company_name ?? item.name ?? "Supplier";
              const totalProducts = item.total_products ?? item.products_count ?? 0;
              const rating = item.rating ?? item.avg_rating ?? 0;
              return (
                <Pressable
                  style={({ pressed }) => [
                    styles.supplierCard,
                    pressed && { opacity: 0.88, transform: [{ scale: 0.97 }] },
                  ]}
                  onPress={() => router.push({ pathname: "/supplier/[slug]", params: { slug: item.slug ?? item.id } } as any)}
                >
                  {/* Logo */}
                  <View style={styles.supplierLogoWrap}>
                    {logo ? (
                      <Image source={{ uri: logo }} style={styles.supplierLogo} resizeMode="cover" />
                    ) : (
                      <View style={[styles.supplierLogo, { backgroundColor: "#F3F4F6", justifyContent: "center", alignItems: "center" }]}>
                        <Ionicons name="storefront" size={20} color="#9CA3AF" />
                      </View>
                    )}
                  </View>
                  {/* Name */}
                  <Text numberOfLines={1} style={styles.supplierName}>{name}</Text>
                  {/* Meta */}
                  <View style={styles.supplierMeta}>
                    <Ionicons name="cube-outline" size={11} color="#6B7280" />
                    <Text style={styles.supplierMetaText}>{totalProducts}</Text>
                    {rating > 0 && (
                      <>
                        <Ionicons name="star" size={11} color="#F59E0B" />
                        <Text style={styles.supplierMetaText}>{Number(rating).toFixed(1)}</Text>
                      </>
                    )}
                  </View>
                </Pressable>
              );
            }}
          />
        </View>
      )}

      {/* Flash Sale Banner */}
      {flashSale.data?.products?.length > 0 && (
        <Pressable
          style={{
            marginHorizontal: 20,
            marginBottom: 24,
            borderRadius: 16,
            overflow: "hidden",
            backgroundColor: "#3257D9",
            padding: 16,
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "space-between",
          }}
          onPress={() => router.push("/flash-sale" as any)}
        >
          <View style={{ flex: 1 }}>
            <View style={{ flexDirection: "row", alignItems: "center", gap: 6 }}>
              <Text style={{ fontSize: 18 }}>⚡</Text>
              <Text style={{ fontSize: 18, fontWeight: "800", color: "#fff" }}>
                Flash Sale
              </Text>
            </View>
            <Text style={{ fontSize: 12, color: "rgba(255,255,255,0.7)", marginTop: 4 }}>
              {flashSale.data.products.length} products on sale
            </Text>
          </View>
          <View style={{
            backgroundColor: "#E5005F",
            paddingHorizontal: 14,
            paddingVertical: 8,
            borderRadius: 10,
          }}>
            <Text style={{ fontSize: 12, fontWeight: "700", color: "#fff" }}>Shop Now</Text>
          </View>
        </Pressable>
      )}

      {/* ── Promotional Sections (Offer Banners) ── */}
      {promoSections.length > 0 && promoSections.map((section: any) => {
        const sectionProducts = section.products ?? [];
        if (sectionProducts.length === 0) return null;

        if (section.layout_type === "slider") {
          /* Slider layout: title + horizontal product carousel */
          return (
            <View key={section.id} style={[styles.sectionContainer, section.bg_color ? { backgroundColor: section.bg_color } : undefined]}>
              <SectionHeader
                title={section.title?.toUpperCase() ?? ""}
                onSeeAll={() => {}}
              />
              <FlatList
                data={sectionProducts}
                horizontal
                showsHorizontalScrollIndicator={false}
                contentContainerStyle={styles.productList}
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
          );
        }

        /* Card layout – premium redesign */
        return (
          <View key={section.id} style={styles.promoCard}>
            {/* Banner with gradient overlay */}
            <View style={styles.promoBannerWrap}>
              {section.banner_image ? (
                <Image
                  source={{ uri: section.banner_image }}
                  style={styles.promoBanner}
                  resizeMode="cover"
                />
              ) : (
                <LinearGradient
                  colors={["#FF4081", "#E5005F"]}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                  style={styles.promoBanner}
                >
                  <Text style={{ fontSize: 20, fontWeight: "800", color: "#fff", letterSpacing: 1 }}>
                    {section.title?.toUpperCase()}
                  </Text>
                </LinearGradient>
              )}
              <LinearGradient
                colors={["transparent", "rgba(0,0,0,0.55)"]}
                style={styles.promoBannerOverlay}
              >
                <Text style={styles.promoBannerTitle}>
                  {section.title?.toUpperCase()}
                </Text>
                <Pressable
                  style={styles.promoExploreBtn}
                  onPress={() => router.push({ pathname: "/section/[slug]", params: { slug: section.slug ?? "" } } as any)}
                >
                  <Text style={styles.promoExploreBtnText}>Explore</Text>
                  <Ionicons name="arrow-forward" size={12} color="#fff" />
                </Pressable>
              </LinearGradient>
            </View>

            {/* Product preview cards */}
            <View style={styles.promoProductRow}>
              {sectionProducts.slice(0, 3).map((product: any) => (
                <Pressable
                  key={product.id}
                  style={({ pressed }) => [
                    styles.promoProductItem,
                    pressed && { opacity: 0.85, transform: [{ scale: 0.97 }] },
                  ]}
                  onPress={() => router.push({ pathname: "/product-detail", params: { slug: product.ProductSlug } } as any)}
                >
                  <View style={styles.promoProductImageWrap}>
                    {product.ViewProductImage ? (
                      <Image
                        source={{ uri: product.ViewProductImage }}
                        style={styles.promoProductImage}
                        resizeMode="cover"
                      />
                    ) : (
                      <View style={[styles.promoProductImage, styles.promoProductImagePlaceholder]}>
                        <Ionicons name="image-outline" size={22} color="#D1D5DB" />
                      </View>
                    )}
                  </View>
                  <Text numberOfLines={2} style={styles.promoProductName}>
                    {product.ProductName}
                  </Text>
                  {(product.storefront_price || product.ProductSalePrice) && (
                    <Text style={styles.promoProductPrice}>
                      ৳{parseFloat(product.storefront_price ?? product.ProductSalePrice).toFixed(2)}
                    </Text>
                  )}
                </Pressable>
              ))}
            </View>
          </View>
        );
      })}

      {/* Featured Products */}
      {allFeatured.length > 0 && (
        <View style={styles.sectionContainer}>
          <SectionHeader title="Featured" onSeeAll={() => router.push("/collection/featured" as any)} />
          <FlatList
            data={allFeatured}
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.productList}
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

      {/* New Arrivals */}
      {newArrivals.length > 0 && (
        <View style={styles.sectionContainer}>
          <SectionHeader title="New Arrivals" onSeeAll={() => router.push("/collection/new_arrivel" as any)} />
          <FlatList
            data={newArrivals}
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.productList}
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

      {/* ── Most Popular Brands ── */}
      {brandList.length > 0 && (
        <View style={styles.sectionContainer}>
          <Text style={styles.brandsTitle}>Most Popular Brands</Text>
          <FlatList
            data={brandList}
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.brandList}
            keyExtractor={(item: any) => String(item.id)}
            renderItem={({ item }: any) => (
              <Pressable
                style={styles.brandCard}
                onPress={() => router.push({ pathname: "/brand/[slug]", params: { slug: item.slug } } as any)}
              >
                {item.icon ? (
                  <Image
                    source={{ uri: item.icon }}
                    style={styles.brandIcon}
                    resizeMode="contain"
                  />
                ) : (
                  <View style={[styles.brandIcon, { backgroundColor: "#F3F4F6", justifyContent: "center", alignItems: "center" }]}>
                    <Text style={{ fontSize: 10, fontWeight: "600", color: "#9CA3AF", textAlign: "center" }}>
                      {item.name}
                    </Text>
                  </View>
                )}
              </Pressable>
            )}
          />
        </View>
      )}

      {/* Bottom spacing for floating tab bar */}
      <View style={{ height: 100 }} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingBottom: 8,
  },
  headerRight: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  headerIconButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#F5F5F5",
    justifyContent: "center",
    alignItems: "center",
  },
  headerBadge: {
    position: "absolute",
    top: 2,
    right: 2,
    backgroundColor: "#E5005F",
    borderRadius: 8,
    minWidth: 16,
    height: 16,
    paddingHorizontal: 4,
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 1.5,
    borderColor: "#F5F5F5",
  },
  headerBadgeText: {
    fontSize: 9,
    fontWeight: "700",
    color: "#fff",
  },
  section: {
    paddingHorizontal: 20,
    marginBottom: 16,
  },
  bannerSection: {
    marginBottom: 20,
  },
  bannerCard: {
    width: width - 40,
    marginHorizontal: 20,
    height: 180,
    borderRadius: 16,
    overflow: "hidden",
    backgroundColor: "#F0F0F5",
  },
  bannerImage: {
    width: "100%",
    height: "100%",
  },
  bannerPlaceholder: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F0F0F5",
  },
  bannerDots: {
    flexDirection: "row",
    justifyContent: "center",
    gap: 6,
    marginTop: 10,
  },
  dot: {
    height: 6,
    borderRadius: 3,
  },
  dotActive: {
    width: 20,
    backgroundColor: "#E5005F",
  },
  dotInactive: {
    width: 6,
    backgroundColor: "#D8D8D8",
  },
  sectionContainer: {
    marginBottom: 24,
  },
  sectionHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    marginBottom: 12,
  },
  categoryGrid: {
    paddingHorizontal: 20,
    gap: 10,
  },
  categoryColumn: {
    gap: 10,
    alignItems: "center",
  },
  productList: {
    paddingHorizontal: 20,
    gap: 12,
  },
  productGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    paddingHorizontal: 20,
    gap: 12,
  },
  /* ── Brands ── */
  brandsTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#322F35",
    textAlign: "center",
    marginBottom: 14,
  },
  brandList: {
    paddingHorizontal: 20,
    gap: 10,
  },
  brandCard: {
    width: 72,
    height: 72,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
    padding: 6,
  },
  brandIcon: {
    width: 56,
    height: 56,
    borderRadius: 8,
  },
  /* ── Promotional Sections (Premium) ── */
  promoCard: {
    marginHorizontal: 16,
    marginBottom: 24,
    borderRadius: 20,
    overflow: "hidden",
    backgroundColor: "#fff",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 6,
  },
  promoBannerWrap: {
    position: "relative",
    width: "100%",
    height: 160,
  },
  promoBanner: {
    width: "100%",
    height: "100%",
    justifyContent: "center",
    alignItems: "center",
  },
  promoBannerOverlay: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    flexDirection: "row",
    alignItems: "flex-end",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingBottom: 14,
    paddingTop: 40,
  },
  promoBannerTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#fff",
    letterSpacing: 0.8,
    textShadowColor: "rgba(0,0,0,0.3)",
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
    flex: 1,
  },
  promoExploreBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    backgroundColor: "rgba(255,255,255,0.22)",
    borderWidth: 1,
    borderColor: "rgba(255,255,255,0.5)",
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
  },
  promoExploreBtnText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#fff",
  },
  promoProductRow: {
    flexDirection: "row",
    paddingHorizontal: 12,
    paddingVertical: 14,
    gap: 10,
  },
  promoProductItem: {
    flex: 1,
    alignItems: "center",
    backgroundColor: "#FAFAFA",
    borderRadius: 14,
    paddingBottom: 10,
    overflow: "hidden",
  },
  promoProductImageWrap: {
    width: "100%",
    aspectRatio: 1,
    borderRadius: 14,
    overflow: "hidden",
    backgroundColor: "#F5F5F7",
  },
  promoProductImage: {
    width: "100%",
    height: "100%",
  },
  promoProductImagePlaceholder: {
    backgroundColor: "#F0F0F5",
    justifyContent: "center",
    alignItems: "center",
  },
  promoProductName: {
    fontSize: 11,
    color: "#374151",
    fontWeight: "600",
    textAlign: "center",
    marginTop: 8,
    paddingHorizontal: 6,
    lineHeight: 15,
  },
  promoProductPrice: {
    fontSize: 13,
    color: "#1A1A2E",
    fontWeight: "800",
    marginTop: 2,
  },
  /* ── Suppliers ── */
  supplierScrollList: {
    paddingHorizontal: 20,
    gap: 12,
  },
  supplierCard: {
    width: 120,
    backgroundColor: "#fff",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    alignItems: "center",
    paddingVertical: 12,
    paddingHorizontal: 8,
  },
  supplierLogoWrap: {
    marginBottom: 8,
  },
  supplierLogo: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: "#F9FAFB",
  },
  supplierName: {
    fontSize: 12,
    fontWeight: "700",
    color: "#1A1A2E",
    textAlign: "center",
    marginBottom: 4,
  },
  supplierMeta: {
    flexDirection: "row",
    alignItems: "center",
    gap: 3,
  },
  supplierMetaText: {
    fontSize: 10,
    color: "#6B7280",
  },
});

