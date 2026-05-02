import { useState, useCallback, useMemo, useRef } from "react";
import {
  View,
  FlatList,
  ActivityIndicator,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  TextInput,
  Keyboard,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useInfiniteQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";
import { ProductGridSkeleton } from "@/components/skeleton";

const { width: SCREEN_WIDTH } = Dimensions.get("window");
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";

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
  const insets = useSafeAreaInsets();

  const [sectionTitle, setSectionTitle] = useState("");
  const [bannerImage, setBannerImage] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState("");
  const inputRef = useRef<TextInput>(null);

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

  const allProducts = data?.pages.flatMap((p) => p.products) ?? [];

  /* ── Client-side search filter ── */
  const filteredProducts = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    if (!q) return allProducts;
    return allProducts.filter((item: any) =>
      (item.ProductName ?? "").toLowerCase().includes(q),
    );
  }, [allProducts, searchQuery]);

  const displayTitle = sectionTitle || slug?.replace(/_/g, " ") || "Products";

  const onEndReached = useCallback(() => {
    if (hasNextPage && !isFetchingNextPage) {
      fetchNextPage();
    }
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  const clearSearch = useCallback(() => {
    setSearchQuery("");
    inputRef.current?.focus();
  }, []);

  const renderFooter = () => {
    if (isFetchingNextPage) {
      return (
        <View style={styles.footerLoader}>
          <ActivityIndicator size="small" color={ACCENT} />
          <Text style={styles.footerText}>Loading more...</Text>
        </View>
      );
    }
    if (!hasNextPage && allProducts.length > 0 && !searchQuery) {
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
      {/* ═══ HEADER ═══ */}
      <View style={[styles.header, { paddingTop: insets.top + 12 }]}>
        <Pressable onPress={() => router.back()} style={styles.backButton}>
          <Ionicons name="arrow-back" size={22} color={DARK} />
        </Pressable>
        <Text
          fontSize="$5"
          fontWeight="bold"
          color={DARK}
          numberOfLines={1}
          style={{ flex: 1, textAlign: "center", textTransform: "capitalize" }}
        >
          {displayTitle}
        </Text>
        <View style={{ width: 40 }} />
      </View>

      {/* ═══ SEARCH BAR ═══ */}
      <View style={styles.searchBar}>
        <View style={styles.searchInputContainer}>
          <Ionicons name="search" size={18} color={GREY} />
          <TextInput
            ref={inputRef}
            style={styles.searchInput}
            placeholder="Search in this section..."
            placeholderTextColor="#B0B0B8"
            value={searchQuery}
            onChangeText={setSearchQuery}
            returnKeyType="search"
            onSubmitEditing={() => Keyboard.dismiss()}
            autoCapitalize="none"
            autoCorrect={false}
          />
          {searchQuery.length > 0 && (
            <Pressable onPress={clearSearch} hitSlop={8}>
              <View style={styles.clearBtn}>
                <Ionicons name="close" size={14} color="#fff" />
              </View>
            </Pressable>
          )}
        </View>
      </View>

      {isLoading ? (
        <ProductGridSkeleton />
      ) : isError ? (
        <View style={styles.center}>
          <Ionicons name="alert-circle-outline" size={48} color={ACCENT} />
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
          data={filteredProducts}
          keyExtractor={(item: any) => String(item.id)}
          numColumns={2}
          contentContainerStyle={styles.listContent}
          columnWrapperStyle={styles.columnWrapper}
          showsVerticalScrollIndicator={false}
          keyboardDismissMode="on-drag"
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
              <View style={styles.countBar}>
                <Ionicons name="grid-outline" size={14} color="#9CA3AF" />
                <Text style={styles.countText}>
                  {searchQuery
                    ? `${filteredProducts.length} of ${allProducts.length} product${allProducts.length !== 1 ? "s" : ""}`
                    : `${allProducts.length} product${allProducts.length !== 1 ? "s" : ""}`}
                </Text>
              </View>
            </>
          }
          ListEmptyComponent={
            !isLoading ? (
              <View style={styles.center}>
                {searchQuery ? (
                  <>
                    <View style={styles.emptySearchIcon}>
                      <Ionicons name="search-outline" size={40} color={ACCENT} />
                    </View>
                    <Text color={DARK} fontWeight="700" fontSize={16} mt="$3">
                      No matches found
                    </Text>
                    <Text color="#666" fontSize={13} mt="$1" style={{ textAlign: "center", maxWidth: 260 }}>
                      No products match "{searchQuery}" in this section
                    </Text>
                    <Pressable
                      style={styles.clearSearchBtn}
                      onPress={() => setSearchQuery("")}
                    >
                      <Ionicons name="close-circle-outline" size={16} color="#fff" />
                      <Text style={{ color: "#fff", fontWeight: "600", fontSize: 13 }}>
                        Clear Search
                      </Text>
                    </Pressable>
                  </>
                ) : (
                  <>
                    <Ionicons name="cube-outline" size={48} color="#ccc" />
                    <Text color="#666" mt="$2">
                      No products found in this section
                    </Text>
                  </>
                )}
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

  /* Search bar */
  searchBar: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
    backgroundColor: "#fff",
  },
  searchInputContainer: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    backgroundColor: "#F5F5FA",
    borderRadius: 14,
    paddingHorizontal: 14,
    height: 44,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    color: "#1A1A2E",
    padding: 0,
  },
  clearBtn: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: "#C7C7CC",
    justifyContent: "center",
    alignItems: "center",
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
  emptySearchIcon: {
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "#FFF0F5",
    justifyContent: "center",
    alignItems: "center",
  },
  clearSearchBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginTop: 16,
    backgroundColor: "#E5005F",
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 10,
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
