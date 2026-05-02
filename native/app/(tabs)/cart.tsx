import { useState, useCallback } from "react";
import {
  View,
  ScrollView,
  Image,
  Pressable,
  StyleSheet,

  RefreshControl,
  Alert,
} from "react-native";
import { Text } from "tamagui";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { router } from "expo-router";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";
import { CartSkeleton } from "@/components/skeleton";

const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";

/* ── Image URL helper ── */
const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string | undefined {
  if (!path || path.trim().length < 2) return undefined;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

function formatBDT(num: number, decimals = 2): string {
  return num.toLocaleString("en-BD", {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

export default function CartScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const { isActive: isResellerActive, isLoggedIn, isLoading: isAuthLoading } = useIsActiveReseller();

  // Fetch cart items
  const {
    data: cartData,
    isLoading,
    refetch,
  } = useQuery({
    queryKey: ["cart-items"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-cart-content");
      return data?.data ?? [];
    },
    enabled: isLoggedIn,
    staleTime: 30 * 1000,
  });

  const [refreshing, setRefreshing] = useState(false);
  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    await refetch();
    setRefreshing(false);
  }, [refetch]);

  const cartItems: any[] = cartData ?? [];
  const itemCount = cartItems.length;

  // Update cart item
  const updateMutation = useMutation({
    mutationFn: async ({ cartId, qty }: { cartId: number; qty: number }) => {
      const { data } = await apiClient.post("/user-update-cart", {
        cart_id: cartId,
        qty,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
    },
    onError: () => {
      toast.error("Failed to update item");
    },
  });

  // Delete cart item
  const deleteMutation = useMutation({
    mutationFn: async (cartId: number) => {
      const { data } = await apiClient.post("/user-destroy-cart", {
        cart_id: cartId,
      });
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
      toast.success("Item removed from cart");
    },
    onError: () => {
      toast.error("Failed to remove item");
    },
  });

  const handleUpdateQty = (cartId: number, newQty: number) => {
    if (newQty < 1) return;
    updateMutation.mutate({ cartId, qty: newQty });
  };

  const handleDelete = (cartId: number, itemName: string) => {
    Alert.alert(
      "Remove Item",
      `Remove "${itemName}" from your cart?`,
      [
        { text: "Cancel", style: "cancel" },
        {
          text: "Remove",
          style: "destructive",
          onPress: () => deleteMutation.mutate(cartId),
        },
      ]
    );
  };

  // Calculate totals
  const subtotal = cartItems.reduce(
    (total: number, item: any) => total + parseFloat(item.price || "0") * (item.qty || 0),
    0
  );

  const totalProfit = cartItems.reduce((total: number, item: any) => {
    const sellingPrice = parseFloat(item.options?.selling_price || item.selling_price || item.price || "0");
    const costPrice = parseFloat(item.price || "0");
    return total + (sellingPrice - costPrice) * (item.qty || 0);
  }, 0);

  // ── NOT LOGGED IN ──
  if (!isAuthLoading && !isLoggedIn) {
    return (
      <View style={s.container}>
        <View style={[s.header, { paddingTop: insets.top + 12 }]}>
          <Text fontSize="$7" fontWeight="bold" color={DARK}>Cart</Text>
        </View>
        <View style={s.emptyState}>
          <View style={s.emptyIcon}>
            <Ionicons name="log-in-outline" size={48} color={ACCENT} />
          </View>
          <Text fontSize="$5" fontWeight="bold" color={DARK} mt="$3">
            Login Required
          </Text>
          <Text fontSize="$3" color={GREY} mt="$1" textAlign="center" style={{ maxWidth: 260 }}>
            Please log in to view your cart and start ordering.
          </Text>
          <Pressable
            style={({ pressed }) => [s.loginBtn, pressed && { opacity: 0.85 }]}
            onPress={() => router.push("/login")}
          >
            <Ionicons name="log-in-outline" size={18} color="#fff" />
            <Text fontSize="$3" fontWeight="700" color="#fff">Log In</Text>
          </Pressable>
        </View>
      </View>
    );
  }

  // ── LOADING ──
  if (isLoading || isAuthLoading) {
    return (
      <View style={s.container}>
        <View style={[s.header, { paddingTop: insets.top + 12 }]}>
          <Text fontSize="$7" fontWeight="bold" color={DARK}>Cart</Text>
        </View>
        <CartSkeleton />
      </View>
    );
  }

  // ── EMPTY CART ──
  if (itemCount === 0) {
    return (
      <View style={s.container}>
        <View style={[s.header, { paddingTop: insets.top + 12 }]}>
          <Text fontSize="$7" fontWeight="bold" color={DARK}>Cart</Text>
        </View>
        <ScrollView
          contentContainerStyle={{ flex: 1 }}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={ACCENT} />}
        >
          <View style={s.emptyState}>
            <View style={s.emptyIcon}>
              <Ionicons name="cart-outline" size={48} color="#D8D8D8" />
            </View>
            <Text fontSize="$5" fontWeight="bold" color={DARK} mt="$3">
              Your cart is empty
            </Text>
            <Text fontSize="$3" color={GREY} mt="$1" textAlign="center" style={{ maxWidth: 260 }}>
              Browse products and add items to your cart to get started.
            </Text>
            <Pressable
              style={({ pressed }) => [s.browseBtn, pressed && { opacity: 0.85 }]}
              onPress={() => router.push("/(tabs)")}
            >
              <Text fontSize="$3" fontWeight="600" color={ACCENT}>Browse Products</Text>
            </Pressable>
          </View>
        </ScrollView>
      </View>
    );
  }

  // ── CART WITH ITEMS ──
  return (
    <View style={s.container}>
      {/* Header */}
      <View style={[s.header, { paddingTop: insets.top + 12 }]}>
        <View style={s.headerRow}>
          <Text fontSize="$7" fontWeight="bold" color={DARK}>Cart</Text>
          <View style={s.countBadge}>
            <Text fontSize={12} fontWeight="800" color="#fff">{itemCount}</Text>
          </View>
        </View>
      </View>

      {/* Cart Items */}
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 120 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={ACCENT} />}
      >
        {cartItems.map((item: any) => {
          const unitPrice = parseFloat(item.price || "0");
          const qty = item.qty || 0;
          const lineTotal = unitPrice * qty;
          const sellingPrice = parseFloat(item.options?.selling_price || item.selling_price || "0");
          const hasProfit = sellingPrice > unitPrice;
          const itemProfit = hasProfit ? (sellingPrice - unitPrice) * qty : 0;

          return (
            <View key={item.id} style={s.cartItem}>
              {/* Product Image */}
              <View style={s.itemImageWrap}>
                <Image
                  source={{ uri: resolveImageUrl(item.image) }}
                  style={s.itemImage}
                  resizeMode="cover"
                />
              </View>

              {/* Product Info */}
              <View style={s.itemInfo}>
                <Text fontSize={14} fontWeight="600" color={DARK} numberOfLines={2} lineHeight={18}>
                  {item.name}
                </Text>

                {/* Variant info (color, size) */}
                {(item.color || item.size) && (
                  <View style={s.variantRow}>
                    {item.color && (
                      <View style={s.variantChip}>
                        <Text fontSize={10} fontWeight="600" color="#666">{item.color}</Text>
                      </View>
                    )}
                    {item.size && item.size !== "Default" && (
                      <View style={s.variantChip}>
                        <Text fontSize={10} fontWeight="600" color="#666">{item.size}</Text>
                      </View>
                    )}
                  </View>
                )}

                {item.code && (
                  <Text fontSize={10} color={GREY} mt={2}>{item.code}</Text>
                )}

                {/* Price Row */}
                <View style={s.priceRow}>
                  {isResellerActive ? (
                    <Text fontSize={15} fontWeight="800" color={ACCENT}>
                      ৳{formatBDT(unitPrice)}
                    </Text>
                  ) : (
                    <Text fontSize={15} fontWeight="800" color="#999">***</Text>
                  )}

                  {isResellerActive && hasProfit && (
                    <View style={s.profitChip}>
                      <Text fontSize={10} fontWeight="700" color="#059669">
                        +৳{formatBDT(itemProfit, 0)} profit
                      </Text>
                    </View>
                  )}
                </View>

                {/* Selling price display */}
                {isResellerActive && hasProfit && (
                  <Text fontSize={10} color="#059669" fontWeight="600">
                    Selling: ৳{formatBDT(sellingPrice)} × {qty}
                  </Text>
                )}

                {/* Quantity Controls */}
                <View style={s.qtyRow}>
                  <View style={s.stepper}>
                    <Pressable
                      style={[s.stepBtn, qty <= 1 && s.stepBtnDisabled]}
                      onPress={() => handleUpdateQty(item.id, qty - 1)}
                      disabled={qty <= 1 || updateMutation.isPending}
                    >
                      <Ionicons name="remove" size={16} color={qty <= 1 ? "#ccc" : DARK} />
                    </Pressable>
                    <View style={s.stepVal}>
                      <Text fontSize="$4" fontWeight="800" color={DARK}>{qty}</Text>
                    </View>
                    <Pressable
                      style={s.stepBtn}
                      onPress={() => handleUpdateQty(item.id, qty + 1)}
                      disabled={updateMutation.isPending}
                    >
                      <Ionicons name="add" size={16} color={ACCENT} />
                    </Pressable>
                  </View>

                  {/* Line Total */}
                  {isResellerActive && (
                    <Text fontSize={15} fontWeight="800" color={DARK}>
                      ৳{formatBDT(lineTotal)}
                    </Text>
                  )}
                </View>
              </View>

              {/* Delete Button */}
              <Pressable
                style={s.deleteBtn}
                onPress={() => handleDelete(item.id, item.name)}
                disabled={deleteMutation.isPending}
              >
                <Ionicons name="trash-outline" size={18} color="#EF4444" />
              </Pressable>
            </View>
          );
        })}

        {/* ── Order Summary & Checkout (scrolls with content) ── */}
        <View style={s.summaryCard}>
          {/* Summary */}
          <View style={s.summarySection}>
            <View style={s.summaryRow}>
              <Text fontSize="$3" color={GREY}>
                Subtotal ({itemCount} {itemCount === 1 ? "item" : "items"})
              </Text>
              {isResellerActive ? (
                <Text fontSize="$4" fontWeight="700" color={DARK}>৳{formatBDT(subtotal)}</Text>
              ) : (
                <Text fontSize="$4" fontWeight="700" color="#999">***</Text>
              )}
            </View>

            {isResellerActive && totalProfit > 0 && (
              <View style={s.summaryRow}>
                <View style={s.profitLabel}>
                  <Ionicons name="trending-up" size={14} color="#059669" />
                  <Text fontSize="$2" fontWeight="600" color="#059669">Profit amount</Text>
                </View>
                <Text fontSize="$3" fontWeight="700" color="#059669">+৳{formatBDT(totalProfit)}</Text>
              </View>
            )}

            {isResellerActive && (
              <View style={[s.summaryRow, s.totalRow]}>
                <Text fontSize="$4" fontWeight="800" color={DARK}>Total</Text>
                <Text fontSize={22} fontWeight="800" color={ACCENT}>
                  ৳{formatBDT(subtotal + totalProfit)}
                </Text>
              </View>
            )}
          </View>

          {/* CTA Button */}
          {isResellerActive ? (
            <Pressable
              style={({ pressed }) => [s.checkoutBtn, pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] }]}
              onPress={() => router.push("/order-confirmation" as any)}
            >
              <Ionicons name="bag-check-outline" size={20} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff">Proceed to Checkout</Text>
            </Pressable>
          ) : (
            <Pressable
              style={({ pressed }) => [s.activateBtn, pressed && { opacity: 0.85 }]}
              onPress={() => router.push("/pricing")}
            >
              <Ionicons name="lock-closed" size={16} color="#fff" />
              <Text fontSize="$3" fontWeight="700" color="#fff">Activate Account to Checkout</Text>
            </Pressable>
          )}
        </View>
      </ScrollView>
    </View>
  );
}

const s = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },

  // Header
  header: { paddingHorizontal: 20, paddingBottom: 12 },
  headerRow: { flexDirection: "row", alignItems: "center", gap: 10 },
  countBadge: {
    backgroundColor: ACCENT,
    borderRadius: 12,
    minWidth: 24,
    height: 24,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 6,
  },

  // Loading & Empty
  loadingState: { flex: 1, justifyContent: "center", alignItems: "center", paddingBottom: 100 },
  emptyState: { flex: 1, justifyContent: "center", alignItems: "center", paddingBottom: 100 },
  emptyIcon: {
    width: 88, height: 88, borderRadius: 44,
    backgroundColor: BG, justifyContent: "center", alignItems: "center",
  },
  loginBtn: {
    flexDirection: "row", alignItems: "center", gap: 8,
    marginTop: 24, backgroundColor: ACCENT, borderRadius: 14,
    paddingHorizontal: 28, paddingVertical: 14,
  },
  browseBtn: {
    marginTop: 20,
    borderWidth: 1.5, borderColor: ACCENT, borderRadius: 12,
    paddingHorizontal: 24, paddingVertical: 10,
  },

  // Cart Item
  cartItem: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 },
    elevation: 1,
  },
  itemImageWrap: {
    width: 80,
    height: 80,
    borderRadius: 12,
    overflow: "hidden",
    backgroundColor: BG,
    marginRight: 12,
  },
  itemImage: { width: "100%", height: "100%" },
  itemInfo: { flex: 1, justifyContent: "center" },

  // Variant
  variantRow: { flexDirection: "row", gap: 4, marginTop: 3 },
  variantChip: {
    backgroundColor: BG, borderRadius: 4,
    paddingHorizontal: 6, paddingVertical: 2,
  },

  // Price
  priceRow: { flexDirection: "row", alignItems: "center", gap: 8, marginTop: 4 },
  profitChip: {
    backgroundColor: "#ECFDF5",
    borderRadius: 6,
    paddingHorizontal: 6,
    paddingVertical: 2,
  },

  // Quantity
  qtyRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 8,
  },
  stepper: { flexDirection: "row", alignItems: "center", gap: 2 },
  stepBtn: {
    width: 32, height: 32, borderRadius: 10,
    borderWidth: 1.5, borderColor: "#E5E5EA",
    justifyContent: "center", alignItems: "center",
    backgroundColor: "#fff",
  },
  stepBtnDisabled: { borderColor: "#F0F0F5", backgroundColor: "#FAFAFA" },
  stepVal: {
    minWidth: 32, alignItems: "center", justifyContent: "center",
    paddingHorizontal: 4,
  },

  // Delete
  deleteBtn: {
    width: 36, height: 36, borderRadius: 18,
    justifyContent: "center", alignItems: "center",
    backgroundColor: "#FEF2F2",
    position: "absolute", top: 10, right: 10,
  },

  // Summary Card (inline in ScrollView)
  summaryCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    padding: 20,
    marginTop: 8,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 2 },
    elevation: 2,
  },
  summarySection: { marginBottom: 14 },
  summaryRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 6,
  },
  totalRow: {
    borderTopWidth: 1,
    borderTopColor: "#F0F0F5",
    paddingTop: 10,
    marginTop: 4,
    marginBottom: 0,
  },
  profitLabel: { flexDirection: "row", alignItems: "center", gap: 4 },

  // CTAs
  checkoutBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: ACCENT,
    borderRadius: 14,
    paddingVertical: 16,
    shadowColor: ACCENT,
    shadowOpacity: 0.3,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
    elevation: 4,
  },
  activateBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: ACCENT,
    borderRadius: 14,
    paddingVertical: 14,
  },
});
