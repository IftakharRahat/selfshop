import { useCallback } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
  Image,
  Share,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router, useLocalSearchParams } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const CARD_WIDTH = (width - 48) / 2;

/* ── Image URL helper ── */
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

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

export default function ResellerShopScreen() {
  const params = useLocalSearchParams<{ userId: string }>();
  const userId = params.userId;
  const queryClient = useQueryClient();

  const shopQuery = useQuery({
    queryKey: ["reseller-shop", userId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/reseller-shop/${userId}`);
      return data?.data ?? data;
    },
    enabled: !!userId,
  });

  const shopData = shopQuery.data;
  const shopName = shopData?.shop_name ?? "Shop";
  const products: any[] = shopData?.products ?? [];
  const shopUrl = `https://selfshop.com.bd/shop/${userId}`;

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["reseller-shop", userId] });
  }, [queryClient, userId]);

  const handleShare = async () => {
    try {
      await Share.share({
        message: `Check out ${shopName}: ${shopUrl}`,
        url: shopUrl,
      });
    } catch {}
  };

  const renderProduct = ({ item }: { item: any }) => {
    const imageUri = resolveImageUrl(item.image);

    return (
      <Pressable
        style={styles.productCard}
        onPress={() =>
          router.push({
            pathname: "/product-detail",
            params: { slug: item.slug },
          } as any)
        }
      >
        <View style={styles.productImageWrapper}>
          {imageUri ? (
            <Image
              source={{ uri: imageUri }}
              style={styles.productImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.productImagePlaceholder}>
              <Ionicons name="storefront-outline" size={28} color="#D1D5DB" />
            </View>
          )}
        </View>

        <View style={styles.productInfo}>
          <Text style={styles.productName} numberOfLines={2}>
            {item.name}
          </Text>
          <Text style={styles.productPrice}>
            {formatCurrency(item.regular_price)}
          </Text>
        </View>
      </Pressable>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: shopName,
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
          headerRight: () => (
            <Pressable onPress={handleShare} style={{ marginRight: 8, padding: 6 }}>
              <Ionicons name="share-social-outline" size={22} color="#374151" />
            </Pressable>
          ),
        }}
      />
      <FlatList
        data={products}
        renderItem={renderProduct}
        keyExtractor={(item) => String(item.id)}
        numColumns={2}
        columnWrapperStyle={products.length > 0 ? styles.gridRow : undefined}
        style={styles.container}
        contentContainerStyle={[
          styles.gridContent,
          products.length === 0 && { flex: 1 },
        ]}
        refreshControl={
          <RefreshControl
            refreshing={shopQuery.isRefetching}
            onRefresh={onRefresh}
            tintColor={ACCENT}
          />
        }
        ListHeaderComponent={
          <>
            {/* ── Hero Header ── */}
            <View style={styles.heroCard}>
              <View style={styles.heroIconWrap}>
                <Ionicons name="storefront" size={28} color="#fff" />
              </View>
              <Text style={styles.heroShopName}>{shopName}</Text>
              <Text style={styles.heroProductCount}>
                {products.length} product{products.length !== 1 ? "s" : ""} available
              </Text>

              {/* Share Bar */}
              <View style={styles.shareBar}>
                <View style={styles.shareUrlBox}>
                  <Ionicons name="link-outline" size={14} color="#6B7280" />
                  <Text style={styles.shareUrlText} numberOfLines={1}>
                    {shopUrl}
                  </Text>
                </View>
                <Pressable
                  style={({ pressed }) => [
                    styles.shareButton,
                    pressed && { opacity: 0.8 },
                  ]}
                  onPress={handleShare}
                >
                  <Ionicons name="share-social" size={16} color="#fff" />
                  <Text style={styles.shareButtonText}>Share</Text>
                </Pressable>
              </View>
            </View>
          </>
        }
        ListEmptyComponent={
          shopQuery.isLoading ? (
            <View style={styles.emptyState}>
              <ActivityIndicator size="large" color={ACCENT} />
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="storefront-outline" size={56} color="#D1D5DB" />
              <Text fontSize="$4" fontWeight="700" color="#9CA3AF" mt="$3">
                No products yet
              </Text>
              <Text fontSize="$2" color="#9CA3AF" mt="$1" style={{ textAlign: "center" }}>
                This shop hasn't added any{"\n"}products yet
              </Text>
            </View>
          )
        }
      />
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  /* ── Hero ── */
  heroCard: {
    backgroundColor: "#fff",
    marginHorizontal: 16,
    marginTop: 16,
    marginBottom: 8,
    borderRadius: 16,
    padding: 20,
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  heroIconWrap: {
    width: 56,
    height: 56,
    borderRadius: 16,
    backgroundColor: ACCENT,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 12,
  },
  heroShopName: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    textAlign: "center",
  },
  heroProductCount: {
    fontSize: 13,
    color: "#6B7280",
    marginTop: 4,
  },

  /* ── Share Bar ── */
  shareBar: {
    flexDirection: "row",
    alignItems: "center",
    marginTop: 16,
    gap: 8,
    width: "100%",
  },
  shareUrlBox: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#F3F4F6",
    borderRadius: 10,
    paddingHorizontal: 10,
    paddingVertical: 10,
  },
  shareUrlText: {
    fontSize: 12,
    color: "#6B7280",
    flex: 1,
  },
  shareButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: ACCENT,
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  shareButtonText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#fff",
  },

  /* ── Grid ── */
  gridContent: {
    paddingBottom: 40,
  },
  gridRow: {
    paddingHorizontal: 16,
    justifyContent: "space-between",
    marginBottom: 12,
  },

  /* ── Product Card ── */
  productCard: {
    width: CARD_WIDTH,
    backgroundColor: "#fff",
    borderRadius: 14,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  productImageWrapper: {
    width: "100%",
    aspectRatio: 1,
    backgroundColor: "#F8F8FA",
  },
  productImage: {
    width: "100%",
    height: "100%",
  },
  productImagePlaceholder: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  productInfo: {
    padding: 10,
  },
  productName: {
    fontSize: 13,
    fontWeight: "600",
    color: "#1A1A2E",
    lineHeight: 18,
    marginBottom: 4,
  },
  productPrice: {
    fontSize: 14,
    fontWeight: "700",
    color: ACCENT,
  },

  /* ── Empty State ── */
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
    paddingHorizontal: 32,
  },
});
