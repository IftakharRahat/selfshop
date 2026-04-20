import { useState, useCallback } from "react";
import {
  View,
  FlatList,
  ActivityIndicator,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";
import { ProductGridSkeleton } from "@/components/skeleton";

const { width: SCREEN_WIDTH } = Dimensions.get("window");
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string | null {
  if (!path || path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/"))
    return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/"))
    return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

const PAGE_LIMIT = 20;

export default function SectionScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();

  const [sectionTitle, setSectionTitle] = useState("");
  const [bannerImage, setBannerImage] = useState<string | null>(null);

  const {
    data,
    isLoading,
    isError,
    fetchNextPage,
    hasNextPage,
    isFetchingNextPage,
  } = useInfiniteQuery({
    queryKey: ["section", slug],
    queryFn: async ({ pageParam = 1 }) => {
      const { data: res } = await apiClient.get(
        `/promotional-sections/${slug}`,
        { params: { page: pageParam, limit: PAGE_LIMIT } },
      );

      if (res?.section) {
        setSectionTitle(res.section.title ?? "");
        setBannerImage(resolveImageUrl(res.section.banner_image));
      }

      const products = res?.data?.data ?? [];
      const currentPage = res?.data?.current_page ?? 1;
      const lastPage = res?.data?.last_page ?? 1;

      return { products, currentPage, lastPage };
    },
    getNextPageParam: (lastPage) =>
      lastPage.currentPage < lastPage.lastPage
        ? lastPage.currentPage + 1
        : undefined,
    initialPageParam: 1,
    enabled: !!slug,
  });

  const products = data?.pages.flatMap((p) => p.products) ?? [];

  const displayTitle = sectionTitle || slug?.replace(/_/g, " ") || "Products";

  const onEndReached = useCallback(() => {
    if (hasNextPage && !isFetchingNextPage) {
      fetchNextPage();
    }
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  const renderFooter = () => {
    if (isFetchingNextPage) {
      return (
        <View style={styles.footerLoader}>
          <ActivityIndicator size="small" color="#E5005F" />
          <Text style={styles.footerText}>Loading more...</Text>
        </View>
      );
    }
    if (!hasNextPage && products.length > 0) {
      return (
        <View style={styles.footerLoader}>
          <Text style={styles.footerTextDone}>All products loaded</Text>
        </View>
      );
    }
    return null;
  };

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backButton}>
          <Ionicons name="arrow-back" size={22} color="#1A1A2E" />
        </Pressable>
        <Text
          fontSize="$5"
          fontWeight="bold"
          color="#1A1A2E"
          numberOfLines={1}
          style={{ flex: 1, textAlign: "center", textTransform: "capitalize" }}
        >
          {displayTitle}
        </Text>
        <View style={{ width: 40 }} />
      </View>

      {isLoading ? (
        <ProductGridSkeleton />
      ) : isError ? (
        <View style={styles.center}>
          <Ionicons name="alert-circle-outline" size={48} color="#E5005F" />
          <Text color="#666" fontSize={15} mt="$3">
            Failed to load products
          </Text>
          <Pressable style={styles.retryBtn} onPress={() => fetchNextPage()}>
            <Text style={{ color: "#fff", fontWeight: "700", fontSize: 13 }}>
              Try Again
            </Text>
          </Pressable>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item: any) => String(item.id)}
          numColumns={2}
          contentContainerStyle={styles.listContent}
          columnWrapperStyle={styles.columnWrapper}
          showsVerticalScrollIndicator={false}
          onEndReached={onEndReached}
          onEndReachedThreshold={0.4}
          ListHeaderComponent={
            <>
              {bannerImage && (
                <View style={styles.bannerWrap}>
                  <Image
                    source={{ uri: bannerImage }}
                    style={styles.bannerImage}
                    resizeMode="cover"
                  />
                  <LinearGradient
                    colors={["transparent", "rgba(0,0,0,0.5)"]}
                    style={styles.bannerGradient}
                  >
                    <Text style={styles.bannerTitle}>{displayTitle}</Text>
                  </LinearGradient>
                </View>
              )}
              {products.length > 0 && (
                <View style={styles.countBar}>
                  <Ionicons name="grid-outline" size={14} color="#9CA3AF" />
                  <Text style={styles.countText}>
                    {products.length} product{products.length !== 1 ? "s" : ""}
                  </Text>
                </View>
              )}
            </>
          }
          ListEmptyComponent={
            !isLoading ? (
              <View style={styles.center}>
                <Ionicons name="cube-outline" size={48} color="#ccc" />
                <Text color="#666" mt="$2">
                  No products found in this section
                </Text>
              </View>
            ) : null
          }
          ListFooterComponent={renderFooter}
          renderItem={({ item }: any) => (
            <ProductCard
              name={item.ProductName}
              price={String(
                item.storefront_price ??
                  item.ProductSalePrice ??
                  item.ProductRegularPrice,
              )}
              image={
                resolveImageUrl(item.ViewProductImage) ?? item.ViewProductImage
              }
              slug={item.ProductSlug}
              variant="grid"
            />
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  header: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 8,
    paddingTop: 56,
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
    backgroundColor: "#fff",
  },
  backButton: {
    width: 40,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
  },
  bannerWrap: {
    width: SCREEN_WIDTH - 32,
    height: 160,
    borderRadius: 16,
    overflow: "hidden",
    marginBottom: 16,
    backgroundColor: "#F0F0F5",
  },
  bannerImage: {
    width: "100%",
    height: "100%",
  },
  bannerGradient: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 16,
    paddingBottom: 14,
    paddingTop: 40,
  },
  bannerTitle: {
    fontSize: 20,
    fontWeight: "800",
    color: "#fff",
    textTransform: "capitalize",
    textShadowColor: "rgba(0,0,0,0.3)",
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  countBar: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 16,
  },
  countText: {
    fontSize: 13,
    color: "#9CA3AF",
    fontWeight: "500",
  },
  center: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingTop: 80,
  },
  retryBtn: {
    marginTop: 16,
    backgroundColor: "#E5005F",
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 10,
  },
  listContent: {
    padding: 16,
    paddingBottom: 100,
  },
  columnWrapper: {
    justifyContent: "space-between",
    marginBottom: 12,
  },
  footerLoader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    paddingVertical: 20,
  },
  footerText: {
    fontSize: 13,
    color: "#6B7280",
  },
  footerTextDone: {
    fontSize: 13,
    color: "#9CA3AF",
  },
});
