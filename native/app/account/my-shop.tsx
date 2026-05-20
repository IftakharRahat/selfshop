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
import { Stack, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";


import { AppDialog, useAppDialog } from "@/components/app-dialog";
import apiClient from "@/lib/api-client";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

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

export default function MyShopScreen() {
  const queryClient = useQueryClient();
  const { dialog, showDialog, closeDialog } = useAppDialog();
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();

  /* ── Queries ── */
  const shopQuery = useQuery({
    queryKey: ["shop-products"],
    queryFn: async () => {
      const { data } = await apiClient.get("/shop-products");
      return data?.data ?? data ?? [];
    },
    enabled: isResellerActive,
  });

  const meQuery = useQuery({
    queryKey: ["me"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-profile");
      return data?.data ?? data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const userId = meQuery.data?.profile?.id ?? meQuery.data?.id;
  const shopUrl = userId ? `https://selfshop.com.bd/shop/${userId}` : "";

  /* ── Mutation ── */
  const removeMutation = useMutation({
    mutationFn: async (productId: number) => {
      const { data } = await apiClient.get(`/remove-from-shop/${productId}`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["shop-products"] });
    },
    onError: (err: any) => {
      showDialog({ tone: "error", title: "Could not remove product", message: err?.response?.data?.message ?? "Failed to remove product." });
    },
  });

  const shopProducts: any[] = Array.isArray(shopQuery.data) ? shopQuery.data : [];

  if (isSubscriptionLoading) {
    return (
      <>
        <Stack.Screen
          options={{
            headerShown: true,
            title: "My Shop",
            headerShadowVisible: false,
            headerStyle: { backgroundColor: "#F8F8FA" },
          }}
        />
        <View style={styles.emptyState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen
          options={{
            headerShown: true,
            title: "My Shop",
            headerShadowVisible: false,
            headerStyle: { backgroundColor: "#F8F8FA" },
          }}
        />
        <SubscriptionRequired
          title="Activate to Manage Shop"
          message="Activate your subscription to add products, manage your shop, and share your reseller storefront."
        />
      </>
    );
  }

  /* ── Handlers ── */
  const handleCopyLink = async () => {
    if (!shopUrl) return;
    try {
      await Share.share({ message: shopUrl });
    } catch {}
  };

  const handleShareLink = async () => {
    if (!shopUrl) return;
    try {
      await Share.share({ message: `Check out my shop: ${shopUrl}`, url: shopUrl });
    } catch {}
  };

  const handleRemove = (productId: number, productName: string) => {
    showDialog({
      tone: "danger",
      title: "Remove product",
      message: `Remove "${productName}" from your shop?`,
      actions: [
        { label: "Cancel", tone: "neutral" },
        { label: "Remove", tone: "danger", onPress: () => removeMutation.mutate(productId) },
      ],
    });
  };

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["shop-products"] });
  }, [queryClient]);

  /* ── Render Item ── */
  const renderProduct = ({ item }: { item: any }) => {
    const product = item.product;
    if (!product) return null;

    const imageUri = resolveImageUrl(product.image);

    return (
      <View style={styles.productCard}>
        <Pressable
          style={styles.productImageWrapper}
          onPress={() =>
            router.push({
              pathname: "/product-detail",
              params: { slug: product.slug },
            } as any)
          }
        >
          {imageUri ? (
            <Image
              source={{ uri: imageUri }}
              style={styles.productImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.productImagePlaceholder}>
              <Ionicons name="storefront-outline" size={32} color="#D1D5DB" />
            </View>
          )}
        </Pressable>

        <View style={styles.productInfo}>
          <Pressable
            onPress={() =>
              router.push({
                pathname: "/product-detail",
                params: { slug: product.slug },
              } as any)
            }
          >
            <Text style={styles.productName} numberOfLines={2}>
              {product.name}
            </Text>
          </Pressable>

          <View style={styles.productBottom}>
            <Text style={styles.productPrice}>
              {formatCurrency(product.regular_price)}
            </Text>
            <Pressable
              style={({ pressed }) => [
                styles.removeButton,
                pressed && { opacity: 0.6 },
              ]}
              onPress={() => handleRemove(item.product_id, product.name)}
              disabled={removeMutation.isPending}
            >
              <Ionicons name="trash-outline" size={15} color="#DC2626" />
            </Pressable>
          </View>
        </View>
      </View>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "My Shop",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <View style={styles.container}>
        {/* ── Shop Link Bar ── */}
        {!!shopUrl && (
          <View style={styles.linkBar}>
            <View style={styles.linkInfo}>
              <Ionicons name="link-outline" size={16} color="#2563EB" />
              <Text style={styles.linkText} numberOfLines={1}>
                {shopUrl}
              </Text>
            </View>
            <View style={styles.linkActions}>
              <Pressable
                style={[styles.linkButton, { backgroundColor: ACCENT, borderColor: ACCENT }]}
                onPress={() =>
                  router.push({
                    pathname: "/account/reseller-shop",
                    params: { userId: String(userId) },
                  } as any)
                }
              >
                <Ionicons name="eye-outline" size={16} color="#fff" />
              </Pressable>
              <Pressable style={styles.linkButton} onPress={handleCopyLink}>
                <Ionicons name="copy-outline" size={16} color="#374151" />
              </Pressable>
              <Pressable style={styles.linkButton} onPress={handleShareLink}>
                <Ionicons name="share-social-outline" size={16} color="#374151" />
              </Pressable>
            </View>
          </View>
        )}

        {/* ── Product Count ── */}
        {!shopQuery.isLoading && shopProducts.length > 0 && (
          <View style={styles.countBar}>
            <Text style={styles.countText}>
              {shopProducts.length} product{shopProducts.length !== 1 ? "s" : ""} in your shop
            </Text>
          </View>
        )}

        {/* ── Grid ── */}
        <FlatList
          data={shopProducts}
          renderItem={renderProduct}
          keyExtractor={(item) => String(item.id)}
          numColumns={2}
          columnWrapperStyle={styles.gridRow}
          contentContainerStyle={[
            styles.gridContent,
            shopProducts.length === 0 && { flex: 1 },
          ]}
          refreshControl={
            <RefreshControl
              refreshing={shopQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
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
                  Your shop is empty
                </Text>
                <Text fontSize="$2" color="#9CA3AF" mt="$1" style={{ textAlign: "center" }}>
                  Browse products and add them{"\n"}to start curating your shop
                </Text>
                <Pressable
                  style={({ pressed }) => [
                    styles.browseButton,
                    pressed && { opacity: 0.85 },
                  ]}
                  onPress={() => router.push("/(tabs)")}
                >
                  <Text fontSize="$3" fontWeight="600" color="#fff">
                    Browse Products
                  </Text>
                </Pressable>
              </View>
            )
          }
        />
      </View>
      <AppDialog state={dialog} onClose={closeDialog} />
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  /* ── Link Bar ── */
  linkBar: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginHorizontal: 16,
    marginTop: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: "#EFF6FF",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#DBEAFE",
  },
  linkInfo: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    flex: 1,
    marginRight: 8,
  },
  linkText: {
    fontSize: 12,
    color: "#2563EB",
    flex: 1,
  },
  linkActions: {
    flexDirection: "row",
    gap: 4,
  },
  linkButton: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },

  /* ── Count Bar ── */
  countBar: {
    paddingHorizontal: 16,
    paddingVertical: 10,
  },
  countText: {
    fontSize: 13,
    color: "#6B7280",
    fontWeight: "500",
  },

  /* ── Grid ── */
  gridContent: {
    padding: 16,
    paddingTop: 4,
  },
  gridRow: {
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
    marginBottom: 6,
  },
  productBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  productPrice: {
    fontSize: 14,
    fontWeight: "700",
    color: ACCENT,
  },
  removeButton: {
    width: 30,
    height: 30,
    borderRadius: 8,
    backgroundColor: "#FEF2F2",
    justifyContent: "center",
    alignItems: "center",
  },

  /* ── Empty State ── */
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
    paddingHorizontal: 32,
  },
  browseButton: {
    marginTop: 20,
    paddingHorizontal: 24,
    paddingVertical: 12,
    backgroundColor: ACCENT,
    borderRadius: 14,
  },
});
