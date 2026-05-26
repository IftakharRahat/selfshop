import { useCallback, useEffect, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  Image,
  Dimensions,
  RefreshControl,
} from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import apiClient from "@/lib/api-client";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const TAKA = "\u09F3";
const CARD_GAP = 12;
const CARD_WIDTH = (width - 32 - CARD_GAP) / 2;

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

function formatBDT(num: number): string {
  const value = Number(num ?? 0);
  return (Number.isFinite(value) ? value : 0).toLocaleString("en-BD", {
    maximumFractionDigits: 0,
  });
}

function pad(n: number): string {
  return n.toString().padStart(2, "0");
}

interface TimeLeft {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
}

function useCountdown(endTime: string | null): TimeLeft {
  const calculate = useCallback((): TimeLeft => {
    if (!endTime) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    const diff = new Date(endTime).getTime() - Date.now();
    if (diff <= 0) return { days: 0, hours: 0, minutes: 0, seconds: 0 };
    return {
      days: Math.floor(diff / (1000 * 60 * 60 * 24)),
      hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
      minutes: Math.floor((diff / (1000 * 60)) % 60),
      seconds: Math.floor((diff / 1000) % 60),
    };
  }, [endTime]);

  const [timeLeft, setTimeLeft] = useState<TimeLeft>(calculate);

  useEffect(() => {
    setTimeLeft(calculate());
    const timer = setInterval(() => setTimeLeft(calculate()), 1000);
    return () => clearInterval(timer);
  }, [calculate]);

  return timeLeft;
}

export default function FlashSaleScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const { isActive: isResellerActive, isLoggedIn } = useIsActiveReseller();

  const flashQuery = useQuery({
    queryKey: ["flash-sale"],
    queryFn: async () => {
      const { data } = await apiClient.get("/flash-sale");
      return data?.data ?? data;
    },
  });

  const flashSale = flashQuery.data;
  const timeLeft = useCountdown(flashSale?.end_time ?? null);

  const isExpired =
    timeLeft.days === 0 &&
    timeLeft.hours === 0 &&
    timeLeft.minutes === 0 &&
    timeLeft.seconds === 0;

  const products: any[] = flashSale?.products ?? [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["flash-sale"] });
  }, [queryClient]);

  const renderProduct = ({ item: product }: { item: any }) => {
    const hasDiscount =
      product.discount_percentage > 0 && product.FlashPrice < product.SalePrice;
    const imageUri = resolveImageUrl(product.ViewProductImage);

    return (
      <Pressable
        style={({ pressed }) => [
          styles.productCard,
          pressed && { opacity: 0.85, transform: [{ scale: 0.97 }] },
        ]}
        onPress={() =>
          router.push({
            pathname: "/product-detail",
            params: { slug: product.ProductSlug },
          } as any)
        }
      >
        {/* Discount Badge */}
        {hasDiscount && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountText}>
              {Math.round(product.discount_percentage)}% OFF
            </Text>
          </View>
        )}

        {/* Product Image */}
        <View style={styles.imageWrapper}>
          {imageUri ? (
            <Image
              source={{ uri: imageUri }}
              style={styles.productImage}
              resizeMode="cover"
            />
          ) : (
            <View style={styles.imagePlaceholder}>
              <Ionicons name="image-outline" size={28} color="#D1D5DB" />
            </View>
          )}
        </View>

        {/* Product Info */}
        <View style={styles.productInfo}>
          <Text style={styles.productName} numberOfLines={2}>
            {product.ProductName}
          </Text>

          <View style={styles.priceRow}>
            {isResellerActive ? (
              <>
                <View style={styles.priceStack}>
                  <Text style={styles.flashPrice}>
                    {TAKA}{formatBDT(product.FlashPrice)}
                  </Text>
                  {hasDiscount && (
                    <Text style={styles.originalPrice}>
                      {TAKA}{formatBDT(product.SalePrice)}
                    </Text>
                  )}
                </View>
                {hasDiscount && (
                  <View style={styles.saveBadge}>
                    <Text style={styles.saveText}>
                      SAVE {Math.round(product.discount_percentage)}%
                    </Text>
                  </View>
                )}
              </>
            ) : (
              <View style={{ flexDirection: "row", alignItems: "center", gap: 4 }}>
                <Ionicons name="lock-closed" size={12} color="#E5005F" />
                <Text style={{ fontSize: 12, fontWeight: "700", color: "#E5005F" }}>
                  {isLoggedIn ? "Activate to see price" : "Login to see price"}
                </Text>
              </View>
            )}
          </View>
        </View>
      </Pressable>
    );
  };

  /* ── Loading ── */
  if (flashQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Flash Sale", headerShadowVisible: false }} />
        <View style={styles.loadingContainer}>
          <View style={styles.loadingSpinner}>
            <Ionicons name="flash" size={32} color={ACCENT} />
          </View>
          <Text fontSize="$3" color="#9CA3AF" mt="$3">
            Loading flash sale...
          </Text>
        </View>
      </>
    );
  }

  /* ── No active sale or expired ── */
  if (!flashSale || !flashQuery.data || isExpired) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Flash Sale", headerShadowVisible: false }} />
        <View style={styles.emptyState}>
          <Ionicons name="flash-outline" size={56} color="#D1D5DB" />
          <Text fontSize="$5" fontWeight="700" color="#374151" mt="$3">
            No Active Flash Sale
          </Text>
          <Text fontSize="$3" color="#9CA3AF" mt="$1" style={{ textAlign: "center" }}>
            Check back later for amazing deals!
          </Text>
          <Pressable
            style={({ pressed }) => [
              styles.backButton,
              pressed && { opacity: 0.85 },
            ]}
            onPress={() => router.back()}
          >
            <Text fontSize="$3" fontWeight="700" color={ACCENT}>
              Back to Home
            </Text>
          </Pressable>
        </View>
      </>
    );
  }

  const countdownUnits = [
    { value: timeLeft.days, label: "Days" },
    { value: timeLeft.hours, label: "Hours" },
    { value: timeLeft.minutes, label: "Min" },
    { value: timeLeft.seconds, label: "Sec" },
  ];

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: false,
        }}
      />
      <View style={styles.container}>
        <LinearGradient
          colors={["#b3003b", "#E5005F"]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={[styles.headerGradient, { paddingTop: insets.top + 12 }]}
        >
          <Pressable
            style={styles.backArrow}
            onPress={() => router.back()}
          >
            <Ionicons name="arrow-back" size={22} color="#fff" />
          </Pressable>

          <View style={styles.headerContent}>
            <View style={styles.headerTitleRow}>
              <View style={styles.headerIcon}>
                <Ionicons name="flash" size={22} color={ACCENT} />
              </View>
              <View style={styles.headerTextBlock}>
                <Text style={styles.headerTitle}>
                  {flashSale.title || "Flash Sale"}
                </Text>
                <Text style={styles.headerSubtitle}>
                  {products.length} products on sale
                </Text>
              </View>
            </View>

            <View style={styles.countdownPanel}>
              <Text style={styles.endsIn}>Ending in</Text>
              <View style={styles.countdownRow}>
                {countdownUnits.map((unit) => (
                  <View key={unit.label} style={styles.countdownUnit}>
                    <View style={styles.countdownBox}>
                      <Text style={styles.countdownValue}>
                        {pad(unit.value)}
                      </Text>
                    </View>
                    <Text style={styles.countdownLabel}>{unit.label}</Text>
                  </View>
                ))}
              </View>
            </View>
          </View>
        </LinearGradient>

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
              refreshing={flashQuery.isRefetching}
              onRefresh={onRefresh}
              tintColor={ACCENT}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyProducts}>
              <Ionicons name="flash-outline" size={40} color="#D1D5DB" />
              <Text fontSize="$3" color="#9CA3AF" mt="$2">
                No products in this flash sale yet
              </Text>
            </View>
          }
        />
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F5F5FA" },

  /* ── Loading ── */
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F5F5FA",
  },
  loadingSpinner: {
    width: 64,
    height: 64,
    borderRadius: 20,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },

  /* ── Empty ── */
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 40,
    paddingBottom: 80,
    backgroundColor: "#F5F5FA",
  },
  backButton: {
    marginTop: 20,
    paddingHorizontal: 20,
    paddingVertical: 10,
  },

  /* ── Header ── */
  headerGradient: {
    paddingBottom: 22,
    paddingHorizontal: 18,
  },
  backArrow: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: "rgba(255,255,255,0.15)",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 12,
  },
  headerContent: {},
  headerTitleRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginBottom: 18,
  },
  headerIcon: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
  },
  headerTextBlock: {
    flex: 1,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: "800",
    color: "#fff",
    fontStyle: "italic",
  },
  headerSubtitle: {
    fontSize: 12,
    color: "rgba(255,255,255,0.78)",
    fontWeight: "600",
    marginTop: 3,
  },

  /* ── Countdown ── */
  countdownPanel: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 10,
  },
  countdownRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  endsIn: {
    fontSize: 13,
    color: "#fff",
    fontWeight: "700",
  },
  countdownUnit: {
    alignItems: "center",
    minWidth: 42,
  },
  countdownBox: {
    backgroundColor: "#fff",
    borderRadius: 8,
    minWidth: 38,
    height: 34,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 6,
  },
  countdownValue: {
    fontSize: 16,
    fontWeight: "800",
    color: "#111827",
  },
  countdownLabel: {
    fontSize: 9,
    color: "rgba(255,255,255,0.82)",
    fontWeight: "700",
    marginTop: 3,
  },

  /* ── Grid ── */
  gridContent: {
    padding: 16,
    paddingBottom: 40,
  },
  gridRow: {
    justifyContent: "space-between",
    marginBottom: 12,
  },
  emptyProducts: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 60,
  },

  /* ── Product Card ── */
  productCard: {
    width: CARD_WIDTH,
    backgroundColor: "#fff",
    borderRadius: 12,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  discountBadge: {
    position: "absolute",
    top: 8,
    left: 8,
    zIndex: 10,
    backgroundColor: ACCENT,
    borderRadius: 6,
    paddingHorizontal: 7,
    paddingVertical: 2,
  },
  discountText: {
    fontSize: 10,
    fontWeight: "800",
    color: "#fff",
  },
  imageWrapper: {
    width: "100%",
    aspectRatio: 1,
    backgroundColor: "#F8F8FA",
  },
  productImage: {
    width: "100%",
    height: "100%",
  },
  imagePlaceholder: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  productInfo: {
    padding: 10,
    gap: 6,
    flex: 1,
  },
  productName: {
    fontSize: 13,
    fontWeight: "600",
    color: "#1A1A2E",
    lineHeight: 18,
    minHeight: 36,
  },
  priceRow: {
    flexDirection: "row",
    alignItems: "flex-end",
    justifyContent: "space-between",
    gap: 8,
  },
  priceStack: {
    flexShrink: 1,
  },
  flashPrice: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1A1A2E",
  },
  originalPrice: {
    fontSize: 12,
    color: "#9CA3AF",
    textDecorationLine: "line-through",
  },
  saveBadge: {
    backgroundColor: ACCENT,
    borderRadius: 4,
    paddingHorizontal: 6,
    paddingVertical: 2,
    alignSelf: "flex-end",
  },
  saveText: {
    fontSize: 10,
    fontWeight: "800",
    color: "#fff",
  },
});
