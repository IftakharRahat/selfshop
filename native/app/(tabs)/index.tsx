import { useRef, useState, useCallback, useEffect } from "react";
import { router } from "expo-router";
import {
  ScrollView,
  View,
  FlatList,
  Dimensions,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Image,
  type ViewToken,
} from "react-native";
import { Text } from "tamagui";
import { useQuery } from "@tanstack/react-query";

import { Ionicons } from "@expo/vector-icons";
import { SearchBar } from "@/components/search-bar";
import { CategoryChip } from "@/components/category-chip";
import { ProductCard } from "@/components/product-card";
import apiClient from "@/lib/api-client";

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

  if (newProducts.isLoading && categories.isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#E5005F" />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
      {/* Header */}
      <View style={styles.header}>
        <Text fontSize="$7" fontWeight="bold" color="#1A1A2E">
          SelfShop
        </Text>
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

      {/* Categories */}
      {categoryList.length > 0 && (
        <View style={styles.sectionContainer}>
          <SectionHeader title="Categories" onSeeAll={() => router.push("/categories")} />
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.categoryList}
          >
            {categoryList.map((cat: any) => (
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
          </ScrollView>
        </View>
      )}

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
    paddingHorizontal: 20,
    paddingTop: 56,
    paddingBottom: 8,
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
  categoryList: {
    paddingHorizontal: 20,
    gap: 12,
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
});

