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

const BANNERS = [
  {
    id: "1",
    title: "Big Sale!",
    subtitle: "Up to 50% off on selected items",
    bgColor: "#E5005F",
  },
  {
    id: "2",
    title: "Free Delivery",
    subtitle: "On orders above ৳500",
    bgColor: "#4CAF50",
  },
  {
    id: "3",
    title: "New Arrivals",
    subtitle: "Check out the latest products",
    bgColor: "#3257D9",
  },
];

export default function HomeScreen() {
  const [bannerIndex, setBannerIndex] = useState(0);
  const bannerRef = useRef<FlatList>(null);

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
      // paginated response: { status, data: { data: [...], ... } }
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

  // Auto-scroll banners
  useEffect(() => {
    const timer = setInterval(() => {
      setBannerIndex((prev) => {
        const next = (prev + 1) % BANNERS.length;
        bannerRef.current?.scrollToIndex({ index: next, animated: true });
        return next;
      });
    }, 4000);
    return () => clearInterval(timer);
  }, []);

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
      <View style={styles.bannerSection}>
        <FlatList
          ref={bannerRef}
          data={BANNERS}
          horizontal
          pagingEnabled
          showsHorizontalScrollIndicator={false}
          bounces={false}
          onViewableItemsChanged={onBannerViewableChange}
          viewabilityConfig={bannerViewConfig}
          keyExtractor={(item) => item.id}
          renderItem={({ item }) => (
            <View style={[styles.bannerCard, { backgroundColor: item.bgColor }]}>
              <Text fontSize="$7" fontWeight="bold" color="#fff">
                {item.title}
              </Text>
              <Text fontSize="$3" color="rgba(255,255,255,0.9)" mt="$1">
                {item.subtitle}
              </Text>
              <Pressable style={styles.bannerButton}>
                <Text fontSize="$3" fontWeight="bold" color="#1A1A2E">
                  Shop Now
                </Text>
              </Pressable>
            </View>
          )}
        />
        {/* Banner Dots */}
        <View style={styles.bannerDots}>
          {BANNERS.map((_, i) => (
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

      {/* Categories */}
      {categoryList.length > 0 && (
        <View style={styles.sectionContainer}>
          <View style={styles.sectionHeader}>
            <Text fontSize="$5" fontWeight="bold" color="#1A1A2E">
              Categories
            </Text>
            <Pressable onPress={() => router.push("/categories")} style={{ flexDirection: "row", alignItems: "center", gap: 8 }}>
              <Text fontSize="$3" color="#E5005F" fontWeight="600">
                See All
              </Text>
              <View style={{ width: 28, height: 28, borderRadius: 14, backgroundColor: "#E5005F", justifyContent: "center", alignItems: "center" }}>
                <Ionicons name="arrow-forward" size={14} color="#fff" />
              </View>
            </Pressable>
          </View>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.categoryList}
          >
            {categoryList.map((cat: any) => (
              <CategoryChip key={cat.id} name={cat.category_name} image={cat.category_icon} />
            ))}
          </ScrollView>
        </View>
      )}

      {/* Featured Products */}
      {allFeatured.length > 0 && (
        <View style={styles.sectionContainer}>
          <View style={styles.sectionHeader}>
            <Text fontSize="$5" fontWeight="bold" color="#1A1A2E">
              Featured
            </Text>
            <Pressable>
              <Text fontSize="$3" color="#E5005F" fontWeight="600">
                See all
              </Text>
            </Pressable>
          </View>
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
          <View style={styles.sectionHeader}>
            <Text fontSize="$5" fontWeight="bold" color="#1A1A2E">
              New Arrivals
            </Text>
            <Pressable>
              <Text fontSize="$3" color="#E5005F" fontWeight="600">
                See all
              </Text>
            </Pressable>
          </View>
          <View style={styles.productGrid}>
            {newArrivals.map((item: any) => (
              <ProductCard
                key={item.id}
                name={item.ProductName}
                price={String(item.storefront_price ?? item.ProductSalePrice ?? item.ProductRegularPrice)}
                image={item.ViewProductImage}
                slug={item.ProductSlug}
                variant="grid"
              />
            ))}
          </View>
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
    height: 150,
    borderRadius: 16,
    padding: 24,
    justifyContent: "center",
  },
  bannerButton: {
    backgroundColor: "#fff",
    paddingVertical: 8,
    paddingHorizontal: 20,
    borderRadius: 20,
    alignSelf: "flex-start",
    marginTop: 12,
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
