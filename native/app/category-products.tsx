import { useState, useRef, useCallback, useEffect } from "react";
import {
  View,
  FlatList,
  ActivityIndicator,
  StyleSheet,
  Pressable,
  ScrollView,
  Animated,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";

const SCREEN_HEIGHT = Dimensions.get("window").height;
const SHEET_HEIGHT = 340;

const ENDPOINTS: Record<string, string> = {
  category: "/products",
  subcategory: "/subcategory-products",
  minicategory: "/minicategory-products",
};

const SORT_OPTIONS = [
  { key: "rating", label: "Top Rated", icon: "star" as const },
  { key: "newest", label: "Newest First", icon: "time" as const },
  { key: "oldest", label: "Oldest First", icon: "hourglass" as const },
  { key: "price_asc", label: "Price: Low → High", icon: "trending-up" as const },
  { key: "price_desc", label: "Price: High → Low", icon: "trending-down" as const },
];

export default function CategoryProductsScreen() {
  const { type = "category", slug, title: paramTitle } = useLocalSearchParams<{
    type?: string;
    slug: string;
    title?: string;
  }>();

  const [sort, setSort] = useState("rating");
  const [showSort, setShowSort] = useState(false);

  const backdropOpacity = useRef(new Animated.Value(0)).current;
  const sheetTranslateY = useRef(new Animated.Value(SHEET_HEIGHT)).current;

  const openSheet = useCallback(() => {
    setShowSort(true);
    Animated.parallel([
      Animated.timing(backdropOpacity, {
        toValue: 1,
        duration: 250,
        useNativeDriver: true,
      }),
      Animated.spring(sheetTranslateY, {
        toValue: 0,
        damping: 20,
        stiffness: 200,
        useNativeDriver: true,
      }),
    ]).start();
  }, [backdropOpacity, sheetTranslateY]);

  const closeSheet = useCallback(
    (cb?: () => void) => {
      Animated.parallel([
        Animated.timing(backdropOpacity, {
          toValue: 0,
          duration: 200,
          useNativeDriver: true,
        }),
        Animated.timing(sheetTranslateY, {
          toValue: SHEET_HEIGHT,
          duration: 200,
          useNativeDriver: true,
        }),
      ]).start(() => {
        setShowSort(false);
        cb?.();
      });
    },
    [backdropOpacity, sheetTranslateY],
  );

  const displayTitle = paramTitle || "Products";
  const endpoint = `${ENDPOINTS[type] ?? "/products"}/${slug}`;
  const activeSortLabel =
    SORT_OPTIONS.find((o) => o.key === sort)?.label ?? "Top Rated";

  const { data, isLoading, isError } = useQuery({
    queryKey: ["category-products", type, slug, sort],
    queryFn: async () => {
      const response = await apiClient.get(endpoint, {
        params: { sort, limit: 40 },
      });
      const resData = response.data;
      return resData?.data?.data ?? resData?.data ?? [];
    },
    enabled: !!slug,
  });

  const products = Array.isArray(data) ? data : [];

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <Pressable onPress={() => router.back()} style={styles.backButton}>
          <Ionicons name="arrow-back" size={24} color="#1A1A2E" />
        </Pressable>
        <Text
          fontSize="$5"
          fontWeight="bold"
          color="#1A1A2E"
          numberOfLines={1}
          style={{ flex: 1, textAlign: "center" }}
        >
          {displayTitle}
        </Text>
        <View style={{ width: 40 }} />
      </View>

      {/* Sort Bar */}
      <View style={styles.toolbar}>
        <Pressable style={styles.sortButton} onPress={openSheet}>
          <Ionicons name="swap-vertical" size={16} color="#E5005F" />
          <Text fontSize={13} fontWeight="600" color="#1A1A2E">
            {activeSortLabel}
          </Text>
          <Ionicons name="chevron-down" size={14} color="#999" />
        </Pressable>

        {!isLoading && products.length > 0 && (
          <Text fontSize={12} color="#999">
            {products.length} products
          </Text>
        )}
      </View>

      {/* Content */}
      {isLoading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#E5005F" />
        </View>
      ) : isError ? (
        <View style={styles.center}>
          <Text color="#999">Failed to load products.</Text>
        </View>
      ) : products.length === 0 ? (
        <View style={styles.center}>
          <Ionicons name="cube-outline" size={48} color="#ccc" />
          <Text color="#666" mt="$2">
            No products found
          </Text>
        </View>
      ) : (
        <FlatList
          data={products}
          keyExtractor={(item: any) => String(item.id)}
          numColumns={2}
          contentContainerStyle={styles.listContent}
          columnWrapperStyle={styles.columnWrapper}
          showsVerticalScrollIndicator={false}
          renderItem={({ item }: any) => (
            <ProductCard
              name={item.ProductName}
              price={String(
                item.storefront_price ??
                  item.ProductSalePrice ??
                  item.ProductRegularPrice,
              )}
              image={item.ViewProductImage}
              slug={item.ProductSlug}
              variant="grid"
            />
          )}
        />
      )}

      {/* Animated Sort Bottom Sheet */}
      {showSort && (
        <View style={StyleSheet.absoluteFill} pointerEvents="box-none">
          <Animated.View
            style={[styles.backdrop, { opacity: backdropOpacity }]}
          >
            <Pressable
              style={StyleSheet.absoluteFill}
              onPress={() => closeSheet()}
            />
          </Animated.View>

          <Animated.View
            style={[
              styles.sheet,
              { transform: [{ translateY: sheetTranslateY }] },
            ]}
          >
            <View style={styles.sheetHandle} />
            <Text
              fontSize="$5"
              fontWeight="bold"
              color="#1A1A2E"
              style={{ textAlign: "center", marginBottom: 16 }}
            >
              Sort By
            </Text>
            {SORT_OPTIONS.map((option) => {
              const isActive = sort === option.key;
              return (
                <Pressable
                  key={option.key}
                  style={[
                    styles.sortOption,
                    isActive && styles.sortOptionActive,
                  ]}
                  onPress={() =>
                    closeSheet(() => setSort(option.key))
                  }
                >
                  <View style={{ flexDirection: "row", alignItems: "center", gap: 10 }}>
                    <Ionicons
                      name={option.icon as any}
                      size={18}
                      color={isActive ? "#E5005F" : "#888"}
                    />
                    <Text
                      fontSize={15}
                      fontWeight={isActive ? "700" : "400"}
                      color={isActive ? "#E5005F" : "#1A1A2E"}
                    >
                      {option.label}
                    </Text>
                  </View>
                  {isActive && (
                    <Ionicons
                      name="checkmark-circle"
                      size={20}
                      color="#E5005F"
                    />
                  )}
                </Pressable>
              );
            })}
          </Animated.View>
        </View>
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
  toolbar: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F5F5FA",
    backgroundColor: "#FAFAFE",
  },
  sortButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#fff",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#EAEAF0",
  },
  center: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#fff",
  },
  listContent: {
    padding: 16,
    paddingBottom: 100,
  },
  columnWrapper: {
    justifyContent: "space-between",
    marginBottom: 16,
  },
  // Bottom sheet
  backdrop: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(0,0,0,0.45)",
  },
  sheet: {
    position: "absolute",
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: "#fff",
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 40,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
    elevation: 20,
  },
  sheetHandle: {
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: "#DDD",
    alignSelf: "center",
    marginBottom: 14,
  },
  sortOption: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingVertical: 14,
    paddingHorizontal: 12,
    borderRadius: 12,
    marginBottom: 2,
  },
  sortOptionActive: {
    backgroundColor: "#FFF5F8",
  },
});
