import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  RefreshControl,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useInfiniteQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";
import { ProductGridSkeleton } from "@/components/skeleton";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const CARD_WIDTH = (width - 48) / 2;
const PAGE_LIMIT = 20;

export default function BrandProductsScreen() {
  const { slug } = useLocalSearchParams<{ slug: string }>();
  const queryClient = useQueryClient();
  const brandName = (slug ?? "").replace(/-/g, " ");

  const productsQuery = useInfiniteQuery({
    queryKey: ["brand-products", slug],
    queryFn: async ({ pageParam = 1 }) => {
      const { data } = await apiClient.get(`/brand-products/${slug}`, {
        params: { page: pageParam, limit: PAGE_LIMIT },
      });
      const paginated = data?.data;

      if (paginated && Array.isArray(paginated?.data)) {
        return {
          products: paginated.data,
          currentPage: paginated.current_page ?? pageParam,
          lastPage: paginated.last_page ?? 1,
          total: paginated.total ?? 0,
        };
      }

      const items = Array.isArray(paginated) ? paginated : [];
      return {
        products: items,
        currentPage: 1,
        lastPage: 1,
        total: items.length,
      };
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) =>
      lastPage.currentPage < lastPage.lastPage ? lastPage.currentPage + 1 : undefined,
    enabled: !!slug,
  });

  const products: any[] = productsQuery.data?.pages.flatMap((page) => page.products) ?? [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["brand-products", slug] });
  }, [queryClient, slug]);

  const handleEndReached = useCallback(() => {
    if (productsQuery.hasNextPage && !productsQuery.isFetchingNextPage) {
      productsQuery.fetchNextPage();
    }
  }, [productsQuery]);

  const renderFooter = () => {
    if (productsQuery.isFetchingNextPage) {
      return (
        <View style={styles.loadingFooter}>
          <ActivityIndicator size="small" color={ACCENT} />
          <Text style={styles.loadingFooterText}>Loading more...</Text>
        </View>
      );
    }

    if (!productsQuery.hasNextPage && products.length > 0) {
      return (
        <View style={styles.loadingFooter}>
          <Text style={styles.doneFooterText}>All products loaded</Text>
        </View>
      );
    }

    return null;
  };

  const renderProduct = ({ item }: { item: any }) => (
    <View style={{ width: CARD_WIDTH }}>
      <ProductCard
        name={item.ProductName}
        price={String(item.storefront_price ?? item.ProductSalePrice ?? item.ProductRegularPrice)}
        image={item.ViewProductImage}
        slug={item.ProductSlug}
        variant="grid"
      />
    </View>
  );

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: brandName || "Brand Products",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        <FlatList
          data={products}
          renderItem={renderProduct}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.gridRow}
          contentContainerStyle={[
            styles.gridContent,
            products.length === 0 && { flex: 1 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={productsQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          onEndReached={handleEndReached}
          onEndReachedThreshold={0.35}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={
            productsQuery.isLoading ? (
              <ProductGridSkeleton />
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="cube-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">
                  No products found for this brand
                </Text>
                <Pressable onPress={() => router.back()} style={{ marginTop: 16 }}>
                  <Text fontSize="$3" fontWeight="600" color={ACCENT}>← Go Back</Text>
                </Pressable>
              </View>
            )
          }
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  gridContent: { padding: 16, paddingBottom: 40 },
  gridRow: { justifyContent: "space-between", marginBottom: 12 },
  loadingFooter: { paddingVertical: 18, alignItems: "center" },
  loadingFooterText: { marginTop: 6, fontSize: 12, color: "#8E8E93", fontWeight: "600" },
  doneFooterText: { fontSize: 12, color: "#9CA3AF", fontWeight: "600" },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
  },
});
