import { useCallback } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  Dimensions,
  RefreshControl,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams, router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";
import { ProductCard } from "@/components/product-card";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const CARD_WIDTH = (width - 48) / 2;

export default function PublicShopScreen() {
  const { userId } = useLocalSearchParams<{ userId: string }>();
  const queryClient = useQueryClient();

  const shopQuery = useQuery({
    queryKey: ["reseller-shop", userId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/reseller-shop/${userId}`);
      return data?.data ?? data;
    },
    enabled: !!userId,
  });

  const shopName = shopQuery.data?.shop_name ?? "Shop";
  const products: any[] = shopQuery.data?.products ?? [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["reseller-shop", userId] });
  }, [queryClient, userId]);

  const renderProduct = ({ item }: { item: any }) => (
    <View style={{ width: CARD_WIDTH }}>
      <ProductCard
        name={item.name}
        price={String(item.regular_price ?? item.sale_price ?? 0)}
        image={item.image}
        slug={item.slug}
        variant="grid"
      />
    </View>
  );

  return (
    <>
      <Stack.Screen
        options={{ headerShown: true, title: shopName, headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }}
      />
      <View style={styles.container}>
        <FlatList
          data={products}
          renderItem={renderProduct}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.gridRow}
          contentContainerStyle={[styles.gridContent, products.length === 0 && { flex: 1 }]}
          refreshControl={<RefreshControl refreshing={shopQuery.isRefetching} onRefresh={onRefresh} tintColor={ACCENT} />}
          ListHeaderComponent={
            <View style={styles.shopHeader}>
              <View style={styles.shopIconBg}>
                <Ionicons name="storefront" size={28} color="#fff" />
              </View>
              <Text style={styles.shopName}>{shopName}</Text>
              <Text style={styles.shopCount}>
                {products.length} {products.length === 1 ? "product" : "products"} available
              </Text>
            </View>
          }
          ListEmptyComponent={
            shopQuery.isLoading ? (
              <View style={styles.emptyState}><ActivityIndicator size="large" color={ACCENT} /></View>
            ) : (
              <View style={styles.emptyState}>
                <Ionicons name="storefront-outline" size={48} color="#D1D5DB" />
                <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">
                  {shopQuery.isError ? "Shop not found" : "This shop has no products yet"}
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
  shopHeader: {
    alignItems: "center",
    paddingVertical: 24,
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  shopIconBg: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: ACCENT,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 12,
  },
  shopName: { fontSize: 22, fontWeight: "800", color: "#1A1A2E" },
  shopCount: { fontSize: 13, color: "#6B7280", marginTop: 4 },
  gridContent: { paddingBottom: 40 },
  gridRow: { paddingHorizontal: 16, justifyContent: "space-between", marginBottom: 12 },
  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", paddingBottom: 60 },
});
