import { useState, useRef, useEffect, useCallback, type Dispatch, type SetStateAction } from "react";
import {
  View, ScrollView, Pressable, StyleSheet, ActivityIndicator,
  TextInput, Image, Modal, Platform, KeyboardAvoidingView, Alert,
  Animated, Easing,
} from "react-native";
import { Text } from "tamagui";
import { router, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";
import { WebView } from "react-native-webview";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const BG = "#F5F5FA";
const ORDER_CREATION_TIMEOUT_MS = 60000;

const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImg(path?: string | null): string | undefined {
  if (!path || path.trim().length < 2) return undefined;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

const fmt = (n: number, d = 0) =>
  n.toLocaleString("en-BD", { minimumFractionDigits: d, maximumFractionDigits: d });

type DeliveryZone = "inside" | "near" | "outside" | null;

type OnlinePaymentReference = {
  orderId?: number;
  transactionId?: string;
};

type OrderSuccessState = {
  visible: boolean;
  method: "account" | "ssl";
  orderId?: string;
  amount: number;
};

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

function createCheckoutRequestId() {
  return `checkout_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
}

/* ─── Reusable animated loaders ─── */

function PulseLoader({
  icon,
  title,
  subtitle,
  accentColor = ACCENT,
  showShimmer = true,
}: {
  icon: keyof typeof Ionicons.glyphMap;
  title: string;
  subtitle?: string;
  accentColor?: string;
  showShimmer?: boolean;
}) {
  const pulse = useRef(new Animated.Value(1)).current;
  const shimmer = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const pulseLoop = Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, { toValue: 1.12, duration: 800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
        Animated.timing(pulse, { toValue: 1, duration: 800, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
      ]),
    );
    pulseLoop.start();
    return () => pulseLoop.stop();
  }, [pulse]);

  useEffect(() => {
    if (!showShimmer) return;
    const shimmerLoop = Animated.loop(
      Animated.timing(shimmer, { toValue: 1, duration: 1500, easing: Easing.linear, useNativeDriver: false }),
    );
    shimmerLoop.start();
    return () => shimmerLoop.stop();
  }, [shimmer, showShimmer]);

  const shimmerTranslateX = shimmer.interpolate({ inputRange: [0, 1], outputRange: [-140, 140] });

  return (
    <View style={loaderSt.root}>
      <Animated.View style={[loaderSt.iconRing, { borderColor: accentColor + "30", transform: [{ scale: pulse }] }]}>
        <View style={[loaderSt.iconCircle, { backgroundColor: accentColor + "14" }]}>
          <Ionicons name={icon} size={32} color={accentColor} />
        </View>
      </Animated.View>

      <Text fontSize="$4" fontWeight="700" color={DARK} mt="$4">{title}</Text>
      {subtitle ? <Text fontSize="$2" color={GREY} mt="$1.5" px="$4" style={{ textAlign: "center" }}>{subtitle}</Text> : null}

      {showShimmer && (
        <View style={loaderSt.shimmerTrack}>
          <Animated.View style={[loaderSt.shimmerBar, { backgroundColor: accentColor, transform: [{ translateX: shimmerTranslateX }] }]} />
        </View>
      )}
    </View>
  );
}

function DotPulse({ color = "#fff" }: { color?: string }) {
  const anims = [useRef(new Animated.Value(0.3)).current, useRef(new Animated.Value(0.3)).current, useRef(new Animated.Value(0.3)).current];

  useEffect(() => {
    const animations = anims.map((a, i) =>
      Animated.loop(
        Animated.sequence([
          Animated.delay(i * 180),
          Animated.timing(a, { toValue: 1, duration: 320, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
          Animated.timing(a, { toValue: 0.3, duration: 320, easing: Easing.inOut(Easing.ease), useNativeDriver: true }),
        ]),
      ),
    );
    animations.forEach((a) => a.start());
    return () => animations.forEach((a) => a.stop());
  }, []);

  return (
    <View style={{ flexDirection: "row", gap: 5, alignItems: "center", height: 22 }}>
      {anims.map((a, i) => (
        <Animated.View key={i} style={{ width: 7, height: 7, borderRadius: 4, backgroundColor: color, opacity: a, transform: [{ scale: a.interpolate({ inputRange: [0.3, 1], outputRange: [0.8, 1.2] }) }] }} />
      ))}
    </View>
  );
}

const loaderSt = StyleSheet.create({
  root: { flex: 1, justifyContent: "center", alignItems: "center", paddingBottom: 40 },
  iconRing: {
    width: 88, height: 88, borderRadius: 44, borderWidth: 3,
    justifyContent: "center", alignItems: "center",
  },
  iconCircle: {
    width: 64, height: 64, borderRadius: 32,
    justifyContent: "center", alignItems: "center",
  },
  shimmerTrack: {
    width: 140, height: 4, borderRadius: 2,
    backgroundColor: "#E5E5EA", overflow: "hidden", marginTop: 20,
  },
  shimmerBar: {
    width: 60, height: 4, borderRadius: 2, opacity: 0.7,
  },
});

function getQueryParam(url: string, key: string): string | null {
  const match = url.match(new RegExp(`[?&]${key}=([^&#]+)`, "i"));
  return match ? decodeURIComponent(match[1]) : null;
}

function extractOrders(response: any): any[] {
  const ordersList =
    response?.data?.data ?? response?.data ?? (Array.isArray(response) ? response : []);
  return Array.isArray(ordersList) ? ordersList : [];
}

export default function OrderConfirmationScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const webViewRef = useRef<WebView>(null);
  const onlinePaymentRef = useRef<OnlinePaymentReference>({});
  const paymentHandledRef = useRef(false);
  const checkoutTimingRef = useRef<{ tapAt?: number; apiStartAt?: number }>({});
  const checkoutInFlightRef = useRef(false);
  const orderCompletedRef = useRef(false);
  const checkoutRequestIdRef = useRef(createCheckoutRequestId());
  const params = useLocalSearchParams<{ cartIds?: string | string[] }>();
  const cartIdsParam = Array.isArray(params.cartIds) ? params.cartIds[0] : params.cartIds;
  const checkoutCartIds = (cartIdsParam ?? "")
    .split(",")
    .map((id) => Number(id))
    .filter((id) => Number.isFinite(id) && id > 0);

  // ── Cart items ──
  const { data: cartData, isLoading: cartLoading, isFetching: cartFetching } = useQuery({
    queryKey: ["cart-items"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-cart-content");
      return data?.data ?? [];
    },
  });
  const allCartItems: any[] = cartData ?? [];
  const cartItems: any[] = checkoutCartIds.length > 0
    ? allCartItems.filter((item: any) => checkoutCartIds.includes(Number(item.id)))
    : allCartItems;
  const [deletingCartIds, setDeletingCartIds] = useState<Record<number, boolean>>({});
  const [confirmingDeleteIds, setConfirmingDeleteIds] = useState<Record<number, boolean>>({});
  const hasPendingDelete = Object.keys(deletingCartIds).length > 0 || Object.keys(confirmingDeleteIds).length > 0;
  const setCartIdFlag = useCallback((
    setter: Dispatch<SetStateAction<Record<number, boolean>>>,
    cartId: number,
    value: boolean,
  ) => {
    setter((prev) => {
      const key = Number(cartId);
      if (value) return { ...prev, [key]: true };
      const next = { ...prev };
      delete next[key];
      return next;
    });
  }, []);
  const removeCartItemFromCache = useCallback((cartId: number) => {
    queryClient.setQueryData<any[]>(["cart-items"], (oldItems) => (
      Array.isArray(oldItems)
        ? oldItems.filter((item: any) => Number(item.id) !== Number(cartId))
        : oldItems
    ));
  }, [queryClient]);

  // ── Remove item from cart ──
  const deleteMutation = useMutation({
    mutationFn: async (cartId: number) => {
      const { data } = await apiClient.post("/user-destroy-cart", { cart_id: cartId, id: cartId });
      return data;
    },
    onMutate: async (cartId) => {
      setCartIdFlag(setDeletingCartIds, cartId, true);
      await queryClient.cancelQueries({ queryKey: ["cart-items"] });
      const previousCartItems = queryClient.getQueryData<any[]>(["cart-items"]);
      removeCartItemFromCache(cartId);
      return { previousCartItems };
    },
    onSuccess: (data) => {
      if (Array.isArray(data?.data)) {
        queryClient.setQueryData(["cart-items"], data.data);
      }
      toast.success(data?.message || "Item removed");
    },
    onError: (error: any, cartId, context) => {
      if (error?.response?.status === 404) {
        removeCartItemFromCache(cartId);
        toast.success("Item removed");
        return;
      }
      if (context?.previousCartItems) {
        queryClient.setQueryData(["cart-items"], context.previousCartItems);
      }
      toast.error(error?.response?.data?.message || "Failed to remove item");
    },
    onSettled: (_data, _error, cartId) => {
      if (cartId !== undefined) setCartIdFlag(setDeletingCartIds, cartId, false);
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
    },
  });

  const handleRemoveItem = (cartId: number, itemName: string) => {
    if (deletingCartIds[Number(cartId)] || confirmingDeleteIds[Number(cartId)]) return;
    setCartIdFlag(setConfirmingDeleteIds, cartId, true);
    Alert.alert("Remove Item", `Remove "${itemName}" from your order?`, [
      {
        text: "Cancel",
        style: "cancel",
        onPress: () => setCartIdFlag(setConfirmingDeleteIds, cartId, false),
      },
      {
        text: "Remove",
        style: "destructive",
        onPress: () => {
          setCartIdFlag(setConfirmingDeleteIds, cartId, false);
          deleteMutation.mutate(cartId);
        },
      },
    ], {
      cancelable: true,
      onDismiss: () => setCartIdFlag(setConfirmingDeleteIds, cartId, false),
    });
  };

  // Redirect if cart empty
  useEffect(() => {
    if (orderCompletedRef.current || checkoutInFlightRef.current) return;
    if (!cartLoading && !cartFetching && !hasPendingDelete && cartItems.length === 0) {
      router.replace("/(tabs)");
    }
  }, [cartFetching, cartLoading, cartItems.length, hasPendingDelete]);

  // ── Basic info (delivery charges) ──
  const { data: basicInfo } = useQuery({
    queryKey: ["basic-info"],
    queryFn: async () => {
      const { data } = await apiClient.get("/basic-info");
      return data?.data ?? data;
    },
    staleTime: 10 * 60 * 1000,
  });

  const insideDhakaCharge = Number(basicInfo?.inside_dhaka_charge) || 60;
  const nearDhakaCharge = Number(basicInfo?.near_dhaka_charge) || 100;
  const outsideDhakaCharge = Number(basicInfo?.outside_dhaka_charge) || 130;

  // ── Saved addresses ──
  const { data: savedAddressData } = useQuery({
    queryKey: ["shipping-addresses"],
    queryFn: async () => {
      const { data } = await apiClient.get("/shipping-addresses");
      return data?.data ?? [];
    },
  });
  const savedAddresses: any[] = savedAddressData ?? [];

  // ── Form state ──
  const [customerData, setCustomerData] = useState({ name: "", address: "", phone: "", note: "" });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [deliveryZone, setDeliveryZone] = useState<DeliveryZone>(null);
  const [advanceDelivery, setAdvanceDelivery] = useState<"yes" | "no">("no");
  const [paymentMethod, setPaymentMethod] = useState<"account" | "ssl">("account");
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [showZonePicker, setShowZonePicker] = useState(false);

  // ── CarryBee city/zone/area for courier routing ──
  const [selectedCityId, setSelectedCityId] = useState<number | null>(null);
  const [selectedZoneId, setSelectedZoneId] = useState<number | null>(null);
  const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null);
  const [showCityPicker, setShowCityPicker] = useState(false);
  const [showCbZonePicker, setShowCbZonePicker] = useState(false);
  const [showAreaPicker, setShowAreaPicker] = useState(false);
  const [orderSuccess, setOrderSuccess] = useState<OrderSuccessState>({
    visible: false,
    method: "account",
    amount: 0,
  });

  const { data: citiesData } = useQuery({
    queryKey: ["carrybee-cities"],
    queryFn: async () => { const { data } = await apiClient.get("/carrybee/cities"); return data?.data?.cities ?? []; },
    staleTime: 30 * 60 * 1000,
  });
  const cities: { id: number; name: string }[] = citiesData ?? [];

  const { data: zonesData } = useQuery({
    queryKey: ["carrybee-zones", selectedCityId],
    queryFn: async () => { const { data } = await apiClient.get(`/carrybee/cities/${selectedCityId}/zones`); return data?.data?.zones ?? []; },
    enabled: !!selectedCityId,
  });
  const cbZones: { id: number; name: string }[] = zonesData ?? [];

  const { data: areasData } = useQuery({
    queryKey: ["carrybee-areas", selectedCityId, selectedZoneId],
    queryFn: async () => { const { data } = await apiClient.get(`/carrybee/cities/${selectedCityId}/zones/${selectedZoneId}/areas`); return data?.data?.areas ?? []; },
    enabled: !!selectedCityId && !!selectedZoneId,
  });
  const cbAreas: { id: number; name: string }[] = areasData ?? [];

  const selectedCityName = cities.find(c => c.id === selectedCityId)?.name ?? "";
  const selectedZoneName = cbZones.find(z => z.id === selectedZoneId)?.name ?? "";
  const selectedAreaName = cbAreas.find(a => a.id === selectedAreaId)?.name ?? "";

  // WebView for SSLCommerz
  const [gatewayUrl, setGatewayUrl] = useState<string | null>(null);
  const [webViewLoading, setWebViewLoading] = useState(true);
  const [paymentVerificationLoading, setPaymentVerificationLoading] = useState(false);

  // ── Calculations ──
  const deliveryCharge = deliveryZone === "inside" ? insideDhakaCharge
    : deliveryZone === "near" ? nearDhakaCharge
    : deliveryZone === "outside" ? outsideDhakaCharge : 0;

  const deliveryZoneLabel = deliveryZone === "inside" ? "Inside Dhaka"
    : deliveryZone === "near" ? "Surrounding Dhaka"
    : deliveryZone === "outside" ? "Outside Dhaka" : null;

  const subtotal = cartItems.reduce(
    (total: number, item: any) => total + parseFloat(item.price || "0") * (item.qty || 0), 0
  );

  const totalProfit = cartItems.reduce((total: number, item: any) => {
    const sp = parseFloat(item.options?.selling_price || item.selling_price || item.price || "0");
    const cp = parseFloat(item.price || "0");
    return total + (sp - cp) * (item.qty || 0);
  }, 0);

  const grandTotal = advanceDelivery === "yes" || !deliveryZone
    ? subtotal + totalProfit
    : subtotal + totalProfit + deliveryCharge;

  const showOrderSuccess = (method: "account" | "ssl", orderId?: string) => {
    orderCompletedRef.current = true;
    setOrderSuccess({
      visible: true,
      method,
      orderId,
      amount: grandTotal,
    });
  };

  const closeSuccessModal = () => {
    setOrderSuccess((prev) => ({ ...prev, visible: false }));
  };

  const goToOrdersAfterSuccess = () => {
    closeSuccessModal();
    router.replace("/account/orders" as any);
  };

  const continueShoppingAfterSuccess = () => {
    closeSuccessModal();
    router.replace("/(tabs)" as any);
  };

  // ── Handlers ──
  const handleInputChange = (field: string, value: string) => {
    setCustomerData(prev => ({ ...prev, [field]: value }));
    if (errors[field]) setErrors(prev => { const n = { ...prev }; delete n[field]; return n; });
  };

  const selectSavedAddress = (addr: any) => {
    setCustomerData(prev => ({
      ...prev,
      name: addr.name || "",
      address: addr.address || "",
      phone: addr.phone || "",
    }));
    // Auto-fill CarryBee dropdowns from saved address
    if (addr.city_id) {
      setSelectedCityId(addr.city_id);
      setSelectedZoneId(addr.zone_id ?? null);
      setSelectedAreaId(addr.area_id ?? null);
    }
    setErrors({});
    toast.success("Address loaded");
  };

  const validateForm = (): boolean => {
    const errs: Record<string, string> = {};
    if (!customerData.name || customerData.name.length < 3) errs.name = "Name must be at least 3 characters";
    if (!customerData.address || customerData.address.length < 5) errs.address = "Address must be at least 5 characters";
    if (!/^01[0-9]{9}$/.test(customerData.phone)) errs.phone = "Invalid phone (11 digits, starts with 01)";
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  // ── Create order mutation ──
  const createOrderMutation = useMutation({
    mutationFn: async (formData: FormData) => {
      const apiStartAt = Date.now();
      checkoutTimingRef.current.apiStartAt = apiStartAt;
      console.log("[CheckoutTiming] /order-now request started", {
        checkoutRequestId: checkoutRequestIdRef.current,
        paymentMethod,
        deliveryCharge,
        itemCount: cartItems.length,
        checkoutCartIds: checkoutCartIds.length,
        timeoutMs: ORDER_CREATION_TIMEOUT_MS,
        msAfterTap: checkoutTimingRef.current.tapAt ? apiStartAt - checkoutTimingRef.current.tapAt : null,
      });

      const { data } = await apiClient.post("/order-now", formData, {
        headers: { "Content-Type": "multipart/form-data" },
        timeout: ORDER_CREATION_TIMEOUT_MS,
      });

      console.log("[CheckoutTiming] /order-now response received", {
        checkoutRequestId: checkoutRequestIdRef.current,
        durationMs: Date.now() - apiStartAt,
        totalSinceTapMs: checkoutTimingRef.current.tapAt ? Date.now() - checkoutTimingRef.current.tapAt : null,
        status: data?.status,
        sslRedirect: Boolean(data?.ssl_redirect),
        orderCount: Array.isArray(data?.orders) ? data.orders.length : data?.order_id ? 1 : 0,
      });

      return data;
    },
    onSuccess: (result) => {
      console.log("[CheckoutTiming] mutation onSuccess", {
        checkoutRequestId: checkoutRequestIdRef.current,
        totalSinceTapMs: checkoutTimingRef.current.tapAt ? Date.now() - checkoutTimingRef.current.tapAt : null,
        msAfterApiStart: checkoutTimingRef.current.apiStartAt ? Date.now() - checkoutTimingRef.current.apiStartAt : null,
        status: result?.status,
        sslRedirect: Boolean(result?.ssl_redirect),
      });

      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
      queryClient.invalidateQueries({ queryKey: ["order-count"] });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
      queryClient.invalidateQueries({ queryKey: ["user-profile"] });

      // Handle SSLCommerz redirect
      if (result?.ssl_redirect && result?.gateway_url) {
        onlinePaymentRef.current = {
          orderId: Number(result.order_id) || undefined,
          transactionId: result.transaction_id,
        };
        paymentHandledRef.current = false;
        setGatewayUrl(result.gateway_url);
        setWebViewLoading(true);
        return;
      }

      if (result?.status === true || result?.status === "success") {
        const createdOrderId = result?.order_id ?? result?.orders?.[0]?.order_id;
        showOrderSuccess("account", createdOrderId ? String(createdOrderId) : undefined);
        return;
      }

      toast.error(result?.message || "Failed to place order. Please try again.");
    },
    onError: (error: any) => {
      console.warn("[CheckoutTiming] mutation onError", {
        checkoutRequestId: checkoutRequestIdRef.current,
        totalSinceTapMs: checkoutTimingRef.current.tapAt ? Date.now() - checkoutTimingRef.current.tapAt : null,
        msAfterApiStart: checkoutTimingRef.current.apiStartAt ? Date.now() - checkoutTimingRef.current.apiStartAt : null,
        code: error?.code,
        status: error?.response?.status,
        message: error?.response?.data?.message || error?.message,
      });

      const message = error?.response?.data?.message;
      const isTimeout = error?.code === "ECONNABORTED";
      toast.error(
        message ||
          (isTimeout
            ? "Order is taking longer than expected. Please check My Orders shortly."
            : "Failed to place order. Please try again.")
      );
    },
    onSettled: () => {
      checkoutInFlightRef.current = false;
      checkoutRequestIdRef.current = createCheckoutRequestId();
    },
  });

  // Save address mutation
  const saveAddressMutation = useMutation({
    mutationFn: async (addr: { label: string; name: string; address: string; phone: string }) => {
      const { data } = await apiClient.post("/shipping-addresses", addr);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["shipping-addresses"] });
      toast.success("Address saved!");
    },
    onError: () => toast.error("Failed to save address"),
  });

  const handleConfirmOrder = () => {
    const tapAt = Date.now();
    if (checkoutInFlightRef.current || createOrderMutation.isPending) {
      console.log("[CheckoutTiming] duplicate confirm ignored", {
        checkoutRequestId: checkoutRequestIdRef.current,
        totalSinceTapMs: checkoutTimingRef.current.tapAt ? tapAt - checkoutTimingRef.current.tapAt : null,
      });
      return;
    }

    checkoutTimingRef.current = { tapAt };
    console.log("[CheckoutTiming] confirm tapped", {
      checkoutRequestId: checkoutRequestIdRef.current,
      paymentMethod,
      deliveryCharge,
      deliveryZone,
      advanceDelivery,
      itemCount: cartItems.length,
      checkoutCartIds: checkoutCartIds.length,
    });

    if (!validateForm()) return;
    if (!deliveryZone) {
      toast.error("Please select a delivery zone");
      return;
    }
    if (!agreedToTerms) {
      toast.error("Please agree to Terms & Conditions");
      return;
    }

    checkoutInFlightRef.current = true;

    const formData = new FormData();
    formData.append("customerName", customerData.name);
    formData.append("customerPhone", customerData.phone);
    formData.append("customerAddress", customerData.address);
    formData.append("subTotal", subtotal.toString());
    formData.append("deliveryCharge", deliveryCharge.toString());
    formData.append("delivery_zone", deliveryZoneLabel!);
    formData.append("advance_delivery", advanceDelivery);
    formData.append("balance_from", paymentMethod === "account" ? "from_account" : "online_pay");
    formData.append("checkout_request_id", checkoutRequestIdRef.current);
    if (checkoutCartIds.length > 0) formData.append("cart_ids", checkoutCartIds.join(","));
    if (selectedCityId) formData.append("city_id", selectedCityId.toString());
    if (selectedZoneId) formData.append("zone_id", selectedZoneId.toString());
    if (customerData.note) formData.append("customerNote", customerData.note);

    createOrderMutation.mutate(formData);
  };

  const verifyOnlineOrderFinalized = async (successUrl: string) => {
    if (paymentHandledRef.current) return;
    paymentHandledRef.current = true;
    setPaymentVerificationLoading(true);

    const orderIdFromUrl = Number(getQueryParam(successUrl, "order_id"));
    const orderId = Number.isFinite(orderIdFromUrl) && orderIdFromUrl > 0
      ? orderIdFromUrl
      : onlinePaymentRef.current.orderId;
    const transactionId = getQueryParam(successUrl, "tran_id") || onlinePaymentRef.current.transactionId;

    try {
      if (!orderId && !transactionId) {
        toast.error("Payment returned without an order reference. Please check My Orders shortly.");
        setGatewayUrl(null);
        router.replace("/account/orders" as any);
        return;
      }

      for (let attempt = 0; attempt < 6; attempt += 1) {
        if (attempt > 0) await delay(1200);

        const params = new URLSearchParams({ page: "1" });
        if (orderId) {
          params.set("search", String(orderId));
        } else if (transactionId) {
          params.set("search", transactionId);
        }

        const { data } = await apiClient.get(`/order-data/all?${params.toString()}`);
        const orders = extractOrders(data);
        const finalizedOrder = orders.find((order: any) => {
          const matchesOrder = orderId ? Number(order.id) === Number(orderId) : true;
          const matchesTransaction = transactionId
            ? String(order.transaction_id ?? "") === String(transactionId)
            : true;
          const visibleStatus = String(order.status ?? "").toLowerCase() !== "pending payment";

          return matchesOrder && matchesTransaction && visibleStatus;
        });

        if (finalizedOrder) {
          setGatewayUrl(null);
          queryClient.invalidateQueries({ queryKey: ["cart-items"] });
          queryClient.invalidateQueries({ queryKey: ["order-count"] });
          queryClient.invalidateQueries({ queryKey: ["orders"] });
          showOrderSuccess("ssl", String(finalizedOrder.id ?? orderId ?? ""));
          return;
        }
      }

      toast.error("Payment received, but the order is still finalizing. Please check My Orders shortly.");
      setGatewayUrl(null);
      queryClient.invalidateQueries({ queryKey: ["order-count"] });
      queryClient.invalidateQueries({ queryKey: ["orders"] });
      router.replace("/account/orders" as any);
    } catch (error) {
      paymentHandledRef.current = false;
      toast.error("Could not verify the order yet. Please check My Orders shortly.");
    } finally {
      setPaymentVerificationLoading(false);
    }
  };

  // WebView navigation for SSLCommerz
  const handleNavChange = (navState: { url: string }) => {
    const url = navState.url.toLowerCase();
    const isSuccess = url.includes("payment=success") || url.includes("/payment/success") || url.includes("status=success") || url.includes("/order-received");
    const isFail = url.includes("payment=failed") || url.includes("payment=error") || url.includes("/payment/fail");
    const isCancel = url.includes("payment=canceled") || url.includes("payment=cancelled") || url.includes("/payment/cancel");

    if (isSuccess) {
      void verifyOnlineOrderFinalized(navState.url);
    } else if (isFail) {
      toast.error("Payment failed. Please try again.");
      paymentHandledRef.current = false;
      setPaymentVerificationLoading(false);
      setGatewayUrl(null);
    } else if (isCancel) {
      toast.info("Payment cancelled.");
      paymentHandledRef.current = false;
      setPaymentVerificationLoading(false);
      setGatewayUrl(null);
    }
  };

  // ── Loading ──
  if (cartLoading) {
    return (
      <View style={[st.center, { paddingTop: insets.top }]}>
        <PulseLoader
          icon="cart-outline"
          title="Preparing your order"
          subtitle="Loading cart items and delivery options…"
        />
      </View>
    );
  }

  const deliveryZones = [
    { value: "inside" as const, label: "Inside Dhaka", charge: insideDhakaCharge },
    { value: "near" as const, label: "Surrounding Dhaka", charge: nearDhakaCharge },
    { value: "outside" as const, label: "Outside Dhaka", charge: outsideDhakaCharge },
  ];

  return (
    <View style={[st.root, { paddingTop: insets.top }]}>
      {/* Header */}
      <View style={st.header}>
        <Pressable onPress={() => router.back()} hitSlop={12}>
          <Ionicons name="arrow-back" size={22} color={DARK} />
        </Pressable>
        <Text fontSize="$5" fontWeight="bold" color={DARK}>Confirm Order</Text>
        <View style={{ width: 22 }} />
      </View>

      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === "ios" ? "padding" : undefined}>
        <ScrollView
          showsVerticalScrollIndicator={false}
          contentContainerStyle={{ padding: 16, paddingBottom: insets.bottom + 200 }}
          keyboardShouldPersistTaps="handled"
        >
          {/* ═══ ORDER SUMMARY ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">
              Order Items ({cartItems.length})
            </Text>
            {cartItems.map((item: any) => {
              const isDeleting = Boolean(deletingCartIds[Number(item.id)] || confirmingDeleteIds[Number(item.id)]);
              return (
                <View key={item.id} style={st.orderItem}>
                <Image source={{ uri: resolveImg(item.image) }} style={st.orderItemImg} resizeMode="cover" />
                <View style={{ flex: 1 }}>
                  <Text fontSize={13} fontWeight="600" color={DARK} numberOfLines={1}>{item.name}</Text>
                  {(item.color || item.size) && (
                    <Text fontSize={10} color={GREY}>
                      {[item.color, item.size !== "Default" && item.size].filter(Boolean).join(" · ")}
                    </Text>
                  )}
                  <View style={{ flexDirection: "row", alignItems: "center", justifyContent: "space-between", marginTop: 4 }}>
                    <Text fontSize={12} color={GREY}>৳{fmt(parseFloat(item.price || "0"))} × {item.qty}</Text>
                    <Text fontSize={14} fontWeight="700" color={DARK}>
                      ৳{fmt(parseFloat(item.price || "0") * (item.qty || 0))}
                    </Text>
                  </View>
                </View>
                <Pressable
                  style={[st.removeItemBtn, isDeleting && { opacity: 0.45 }]}
                  onPress={() => handleRemoveItem(item.id, item.name)}
                  disabled={isDeleting}
                >
                  <Ionicons name="close-circle" size={22} color="#EF4444" />
                </Pressable>
                </View>
              );
            })}
          </View>

          {/* ═══ CUSTOMER DETAILS ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Customer Details</Text>

            {/* Saved Addresses */}
            {savedAddresses.length > 0 && (
              <View style={{ marginBottom: 12 }}>
                <Text fontSize={11} fontWeight="600" color={GREY} mb="$1">SAVED ADDRESSES</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ gap: 8 }}>
                  {savedAddresses.map((addr: any) => (
                    <Pressable
                      key={addr.id}
                      style={st.savedAddrChip}
                      onPress={() => selectSavedAddress(addr)}
                    >
                      <Ionicons name="location" size={14} color={ACCENT} />
                      <View style={{ flex: 1 }}>
                        <Text fontSize={11} fontWeight="600" color={DARK} numberOfLines={1}>
                          {addr.label || `${addr.name} - ${addr.phone}`}
                        </Text>
                        <Text fontSize={10} color={GREY} numberOfLines={1}>{addr.address}</Text>
                      </View>
                    </Pressable>
                  ))}
                </ScrollView>
              </View>
            )}

            {/* Form Fields */}
            {(["name", "phone", "address"] as const).map((field) => (
              <View key={field} style={{ marginBottom: 12 }}>
                <Text fontSize={12} fontWeight="600" color={DARK} mb="$1" style={{ textTransform: "capitalize" }}>
                  Customer {field}
                </Text>
                <TextInput
                  style={[st.input, errors[field] && { borderColor: "#EF4444" }]}
                  value={customerData[field]}
                  onChangeText={(v) => handleInputChange(field, v)}
                  placeholder={`Enter customer ${field}`}
                  placeholderTextColor="#aaa"
                  keyboardType={field === "phone" ? "phone-pad" : "default"}
                  multiline={field === "address"}
                  numberOfLines={field === "address" ? 2 : 1}
                />
                {errors[field] && <Text fontSize={11} color="#EF4444" mt="$0.5">{errors[field]}</Text>}
              </View>
            ))}

            {/* Note */}
            <View style={{ marginBottom: 12 }}>
              <Text fontSize={12} fontWeight="600" color={DARK} mb="$1">Custom Note (Optional)</Text>
              <TextInput
                style={[st.input, { height: 70, textAlignVertical: "top" }]}
                value={customerData.note}
                onChangeText={(v) => handleInputChange("note", v)}
                placeholder="Enter custom note"
                placeholderTextColor="#aaa"
                multiline
                numberOfLines={3}
              />
            </View>

            {/* Save address button */}
            {customerData.name && customerData.address && customerData.phone && (
              <Pressable
                style={st.saveAddrBtn}
                onPress={() => {
                  const label = `${customerData.name} - ${customerData.phone}`;
                  saveAddressMutation.mutate({
                    label,
                    ...customerData,
                    city_id: selectedCityId,
                    zone_id: selectedZoneId,
                    area_id: selectedAreaId,
                  } as any);
                }}
                disabled={saveAddressMutation.isPending}
              >
                <Ionicons name="bookmark-outline" size={14} color={ACCENT} />
                <Text fontSize={12} fontWeight="600" color={ACCENT}>Save this address</Text>
              </Pressable>
            )}

            {/* ═══ DELIVERY CITY & ZONE (CarryBee) ═══ */}
            <View style={[st.section, { marginTop: 6 }]}>
              <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Delivery City & Zone</Text>

              {/* City Picker */}
              <Text fontSize={12} fontWeight="600" color={DARK} mb="$1">City / District</Text>
              <Pressable style={st.zonePicker} onPress={() => setShowCityPicker(!showCityPicker)}>
                <Text fontSize={14} fontWeight="500" color={selectedCityId ? DARK : GREY}>
                  {selectedCityName || "Select City"}
                </Text>
                <Ionicons name={showCityPicker ? "chevron-up" : "chevron-down"} size={20} color={GREY} />
              </Pressable>
              {showCityPicker && (
                <View style={st.zoneOptions}>
                  <ScrollView style={{ maxHeight: 200 }} nestedScrollEnabled>
                    {cities.map((c) => (
                      <Pressable
                        key={c.id}
                        style={[st.zoneOption, selectedCityId === c.id && { backgroundColor: "#FFF0F5" }]}
                        onPress={() => { setSelectedCityId(c.id); setSelectedZoneId(null); setSelectedAreaId(null); setShowCityPicker(false); }}
                      >
                        <Text fontSize={14} fontWeight="500" color={selectedCityId === c.id ? ACCENT : DARK}>{c.name}</Text>
                      </Pressable>
                    ))}
                  </ScrollView>
                </View>
              )}

              {/* Zone Picker */}
              {selectedCityId && (
                <View style={{ marginTop: 10 }}>
                  <Text fontSize={12} fontWeight="600" color={DARK} mb="$1">Zone</Text>
                  <Pressable style={st.zonePicker} onPress={() => setShowCbZonePicker(!showCbZonePicker)}>
                    <Text fontSize={14} fontWeight="500" color={selectedZoneId ? DARK : GREY}>
                      {selectedZoneName || "Select Zone"}
                    </Text>
                    <Ionicons name={showCbZonePicker ? "chevron-up" : "chevron-down"} size={20} color={GREY} />
                  </Pressable>
                  {showCbZonePicker && (
                    <View style={st.zoneOptions}>
                      <ScrollView style={{ maxHeight: 200 }} nestedScrollEnabled>
                        {cbZones.map((z) => (
                          <Pressable
                            key={z.id}
                            style={[st.zoneOption, selectedZoneId === z.id && { backgroundColor: "#FFF0F5" }]}
                            onPress={() => { setSelectedZoneId(z.id); setSelectedAreaId(null); setShowCbZonePicker(false); }}
                          >
                            <Text fontSize={14} fontWeight="500" color={selectedZoneId === z.id ? ACCENT : DARK}>{z.name}</Text>
                          </Pressable>
                        ))}
                      </ScrollView>
                    </View>
                  )}
                </View>
              )}

              {/* Area Picker */}
              {selectedZoneId && (
                <View style={{ marginTop: 10 }}>
                  <Text fontSize={12} fontWeight="600" color={DARK} mb="$1">Area (Optional)</Text>
                  <Pressable style={st.zonePicker} onPress={() => setShowAreaPicker(!showAreaPicker)}>
                    <Text fontSize={14} fontWeight="500" color={selectedAreaId ? DARK : GREY}>
                      {selectedAreaName || "Select Area"}
                    </Text>
                    <Ionicons name={showAreaPicker ? "chevron-up" : "chevron-down"} size={20} color={GREY} />
                  </Pressable>
                  {showAreaPicker && (
                    <View style={st.zoneOptions}>
                      <ScrollView style={{ maxHeight: 200 }} nestedScrollEnabled>
                        {cbAreas.map((a) => (
                          <Pressable
                            key={a.id}
                            style={[st.zoneOption, selectedAreaId === a.id && { backgroundColor: "#FFF0F5" }]}
                            onPress={() => { setSelectedAreaId(a.id); setShowAreaPicker(false); }}
                          >
                            <Text fontSize={14} fontWeight="500" color={selectedAreaId === a.id ? ACCENT : DARK}>{a.name}</Text>
                          </Pressable>
                        ))}
                      </ScrollView>
                    </View>
                  )}
                </View>
              )}

              <Text fontSize={11} color={GREY} mt="$2">Selecting city & zone helps ensure accurate courier delivery</Text>
            </View>
          </View>

          {/* ═══ DELIVERY ZONE ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Delivery Zone</Text>
            <Pressable style={st.zonePicker} onPress={() => setShowZonePicker(!showZonePicker)}>
              <Text fontSize={14} fontWeight="500" color={deliveryZone ? DARK : GREY}>
                {deliveryZoneLabel ? `${deliveryZoneLabel} — ৳${fmt(deliveryCharge)}` : "Choose Delivery Zone"}
              </Text>
              <Ionicons name={showZonePicker ? "chevron-up" : "chevron-down"} size={20} color={GREY} />
            </Pressable>

            {showZonePicker && (
              <View style={st.zoneOptions}>
                {deliveryZones.map((zone) => (
                  <Pressable
                    key={zone.value}
                    style={[st.zoneOption, deliveryZone === zone.value && { backgroundColor: "#FFF0F5" }]}
                    onPress={() => { setDeliveryZone(zone.value); setShowZonePicker(false); }}
                  >
                    <Text fontSize={14} fontWeight="500" color={deliveryZone === zone.value ? ACCENT : DARK}>
                      {zone.label}
                    </Text>
                    <Text fontSize={12} fontWeight="700" color={deliveryZone === zone.value ? ACCENT : GREY}>
                      ৳{fmt(zone.charge)}
                    </Text>
                  </Pressable>
                ))}
              </View>
            )}
          </View>

          {/* ═══ ADVANCE DELIVERY ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">
              Did customer pay advance delivery charge?
            </Text>
            <View style={{ flexDirection: "row", gap: 10 }}>
              {(["yes", "no"] as const).map((val) => (
                <Pressable
                  key={val}
                  style={[
                    st.radioBtn,
                    advanceDelivery === val && {
                      borderColor: val === "yes" ? "#059669" : ACCENT,
                      backgroundColor: val === "yes" ? "#ECFDF5" : "#FFF0F5",
                    },
                  ]}
                  onPress={() => setAdvanceDelivery(val)}
                >
                  <View style={[st.radioDot, advanceDelivery === val && { backgroundColor: val === "yes" ? "#059669" : ACCENT }]} />
                  <Text fontSize={14} fontWeight="600" color={DARK} style={{ textTransform: "capitalize" }}>
                    {val}
                  </Text>
                </Pressable>
              ))}
            </View>
            {deliveryZone && (
              <View style={[st.infoBox, { backgroundColor: advanceDelivery === "yes" ? "#ECFDF5" : "#FFFBEB", marginTop: 10 }]}>
                <Ionicons
                  name={advanceDelivery === "yes" ? "checkmark-circle" : "information-circle"}
                  size={16}
                  color={advanceDelivery === "yes" ? "#059669" : "#D97706"}
                />
                <Text fontSize={12} fontWeight="600" color={advanceDelivery === "yes" ? "#059669" : "#D97706"}>
                  {advanceDelivery === "yes"
                    ? `Customer paid ৳${fmt(deliveryCharge)} advance delivery`
                    : `৳${fmt(deliveryCharge)} delivery charge will be added to total`}
                </Text>
              </View>
            )}
          </View>

          {/* ═══ PAYMENT METHOD ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Pay delivery fee via</Text>
            <View style={{ flexDirection: "row", gap: 10 }}>
              {([
                { key: "account" as const, label: "Account Wallet", icon: "wallet" as const },
                { key: "ssl" as const, label: "SSL Commerz", icon: "card" as const },
              ]).map((pm) => (
                <Pressable
                  key={pm.key}
                  style={[
                    st.radioBtn, { flex: 1 },
                    paymentMethod === pm.key && { borderColor: ACCENT, backgroundColor: "#FFF0F5" },
                  ]}
                  onPress={() => setPaymentMethod(pm.key)}
                >
                  <Ionicons name={pm.icon} size={16} color={paymentMethod === pm.key ? ACCENT : GREY} />
                  <Text fontSize={12} fontWeight="600" color={paymentMethod === pm.key ? ACCENT : DARK}>
                    {pm.label}
                  </Text>
                </Pressable>
              ))}
            </View>
          </View>

          {/* ═══ PRICE SUMMARY ═══ */}
          <View style={st.section}>
            <Text fontSize="$4" fontWeight="700" color={DARK} mb="$2">Order Summary</Text>
            <View style={st.summaryRow}>
              <Text fontSize={13} color={GREY}>Subtotal</Text>
              <Text fontSize={13} fontWeight="600" color={DARK}>৳{fmt(subtotal)}</Text>
            </View>
            {totalProfit > 0 && (
              <View style={st.summaryRow}>
                <Text fontSize={13} color="#059669">Profit amount</Text>
                <Text fontSize={13} fontWeight="600" color="#059669">৳{fmt(totalProfit)}</Text>
              </View>
            )}
            <View style={st.summaryRow}>
              <Text fontSize={13} color={GREY}>Delivery Charge</Text>
              {!deliveryZone ? (
                <Text fontSize={12} color={GREY} fontStyle="italic">Select a zone</Text>
              ) : advanceDelivery === "yes" ? (
                <Text fontSize={12} fontWeight="600" color="#059669">Paid by customer</Text>
              ) : (
                <Text fontSize={13} fontWeight="600" color={DARK}>৳{fmt(deliveryCharge)}</Text>
              )}
            </View>
            <View style={[st.summaryRow, { borderTopWidth: 1.5, borderTopColor: "#E5E5EA", paddingTop: 10, marginTop: 4 }]}>
              <Text fontSize={16} fontWeight="800" color={DARK}>Total</Text>
              <Text fontSize={20} fontWeight="800" color={ACCENT}>৳{fmt(grandTotal)}</Text>
            </View>
            {deliveryZone && (
              <View style={[st.infoBox, { backgroundColor: "#FFF0F5", marginTop: 10 }]}>
                <Ionicons name="information-circle" size={14} color={ACCENT} />
                <Text fontSize={11} fontWeight="600" color={ACCENT}>
                  Pay ৳{fmt(deliveryCharge)} delivery fee to confirm order
                </Text>
              </View>
            )}
          </View>

          {/* ═══ TERMS ═══ */}
          <Pressable style={st.termsRow} onPress={() => setAgreedToTerms(!agreedToTerms)}>
            <View style={[st.checkbox, agreedToTerms && { backgroundColor: ACCENT, borderColor: ACCENT }]}>
              {agreedToTerms && <Ionicons name="checkmark" size={14} color="#fff" />}
            </View>
            <Text fontSize={12} color={GREY} style={{ flex: 1 }}>
              I have read and agree to the Terms & Conditions, Privacy Policy, and Return & Refund Policy.
            </Text>
          </Pressable>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* ═══ STICKY CTA ═══ */}
      <View style={[st.bottomCta, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        <Pressable
          style={({ pressed }) => [
            st.confirmBtn,
            (!agreedToTerms || !deliveryZone) && { opacity: 0.5 },
            pressed && { opacity: 0.85, transform: [{ scale: 0.98 }] },
          ]}
          onPress={handleConfirmOrder}
          disabled={!agreedToTerms || !deliveryZone || createOrderMutation.isPending}
        >
          {createOrderMutation.isPending ? (
            <DotPulse />
          ) : (
            <>
              <Ionicons name="bag-check-outline" size={20} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff">
                {deliveryZone ? `Pay ৳${fmt(deliveryCharge)} & Confirm` : "Confirm Order"}
              </Text>
            </>
          )}
        </Pressable>
      </View>

      {/* ═══ SSLCommerz WebView Modal ═══ */}
      <Modal
        visible={!!gatewayUrl}
        animationType="slide"
        presentationStyle="fullScreen"
        onRequestClose={() => setGatewayUrl(null)}
      >
        <View style={[st.webViewRoot, { paddingTop: insets.top }]}>
          <View style={st.webViewHeader}>
            <Pressable onPress={() => setGatewayUrl(null)} style={st.webViewCloseBtn} hitSlop={12}>
              <Ionicons name="close" size={20} color="#fff" />
            </Pressable>
            <View style={{ flexDirection: "row", alignItems: "center" }}>
              <Ionicons name="lock-closed" size={12} color="#4ade80" />
              <Text fontSize="$3" fontWeight="600" color="#fff" ml="$1">Secure Payment</Text>
            </View>
            <View style={{ width: 36 }} />
          </View>

          {(webViewLoading || paymentVerificationLoading) && (
            <View style={st.webViewLoader}>
              {paymentVerificationLoading ? (
                <PulseLoader
                  icon="checkmark-circle-outline"
                  title="Verifying your order"
                  subtitle="Confirming payment with the server — please wait…"
                  accentColor="#10b981"
                />
              ) : (
                <PulseLoader
                  icon="shield-checkmark-outline"
                  title="Connecting to payment"
                  subtitle="Opening secure payment gateway…"
                />
              )}
            </View>
          )}

          {gatewayUrl && (
            Platform.OS === "ios" ? (
              <KeyboardAvoidingView style={{ flex: 1 }} behavior="padding" keyboardVerticalOffset={insets.top + 44}>
                <WebView
                  ref={webViewRef}
                  source={{ uri: gatewayUrl }}
                  style={{ flex: 1, backgroundColor: "#fff" }}
                  onLoadEnd={() => setWebViewLoading(false)}
                  onNavigationStateChange={handleNavChange}
                  javaScriptEnabled
                  domStorageEnabled
                  startInLoadingState={false}
                  sharedCookiesEnabled
                  setSupportMultipleWindows={false}
                />
              </KeyboardAvoidingView>
            ) : (
              <WebView
                ref={webViewRef}
                source={{ uri: gatewayUrl }}
                style={{ flex: 1, backgroundColor: "#fff" }}
                onLoadEnd={() => setWebViewLoading(false)}
                onNavigationStateChange={handleNavChange}
                javaScriptEnabled
                domStorageEnabled
                startInLoadingState={false}
                scalesPageToFit={false}
                sharedCookiesEnabled
                androidLayerType="hardware"
                overScrollMode="never"
                setSupportMultipleWindows={false}
                injectedJavaScript={`
                  (function(){
                    var meta = document.createElement('meta');
                    meta.name = 'viewport';
                    meta.content = 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0';
                    document.head.appendChild(meta);
                  })();
                  true;
                `}
              />
            )
          )}
        </View>
      </Modal>

      <Modal
        visible={orderSuccess.visible}
        transparent
        animationType="fade"
        onRequestClose={goToOrdersAfterSuccess}
      >
        <View style={st.successOverlay}>
          <View style={[st.successSheet, { paddingBottom: Math.max(insets.bottom, 18) }]}>
            <View style={st.successHandle} />
            <View style={st.successHero}>
              <View style={st.successGlow} />
              <View style={st.successIconWrap}>
                <Ionicons name="checkmark" size={34} color="#fff" />
              </View>
            </View>

            <Text fontSize="$6" fontWeight="800" color={DARK} mt="$3" style={{ textAlign: "center" }}>
              Order confirmed
            </Text>
            <Text fontSize="$3" color={GREY} mt="$1" px="$3" lineHeight={20} style={{ textAlign: "center" }}>
              Your order has been placed successfully. You can track the latest status from My Orders.
            </Text>

            <View style={st.successSummaryCard}>
              <View style={st.successSummaryRow}>
                <View style={st.successSummaryIcon}>
                  <Ionicons
                    name={orderSuccess.method === "account" ? "wallet" : "card"}
                    size={18}
                    color={ACCENT}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text fontSize={11} color={GREY} textTransform="uppercase" letterSpacing={0.5}>
                    Payment method
                  </Text>
                  <Text fontSize="$3" fontWeight="700" color={DARK} mt="$0.5">
                    {orderSuccess.method === "account" ? "Account Wallet" : "SSL Commerz"}
                  </Text>
                </View>
                <View style={st.successPill}>
                  <Ionicons name="shield-checkmark" size={13} color="#047857" />
                  <Text fontSize={11} fontWeight="800" color="#047857">Confirmed</Text>
                </View>
              </View>

              <View style={st.successDivider} />

              <View style={st.successAmountRow}>
                <Text fontSize={13} color={GREY}>Order total</Text>
                <Text fontSize="$6" fontWeight="800" color={ACCENT}>৳{fmt(orderSuccess.amount)}</Text>
              </View>
              {orderSuccess.orderId ? (
                <View style={st.successAmountRow}>
                  <Text fontSize={13} color={GREY}>Order ID</Text>
                  <Text fontSize="$3" fontWeight="700" color={DARK}>#{orderSuccess.orderId}</Text>
                </View>
              ) : null}
            </View>

            <Pressable
              style={({ pressed }) => [st.successPrimaryBtn, pressed && { opacity: 0.86 }]}
              onPress={goToOrdersAfterSuccess}
            >
              <Ionicons name="receipt-outline" size={18} color="#fff" />
              <Text fontSize="$3" fontWeight="800" color="#fff">View My Orders</Text>
            </Pressable>
            <Pressable
              style={({ pressed }) => [st.successSecondaryBtn, pressed && { opacity: 0.75 }]}
              onPress={continueShoppingAfterSuccess}
            >
              <Ionicons name="storefront-outline" size={17} color={ACCENT} />
              <Text fontSize="$3" fontWeight="700" color={ACCENT}>Continue Shopping</Text>
            </Pressable>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const st = StyleSheet.create({
  root: { flex: 1, backgroundColor: BG },
  center: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: BG },

  header: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingVertical: 12,
    backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f0f0f0",
  },

  section: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16, marginBottom: 12,
    shadowColor: "#000", shadowOpacity: 0.03, shadowRadius: 6,
    shadowOffset: { width: 0, height: 2 }, elevation: 1,
  },

  // Order items
  orderItem: {
    flexDirection: "row", gap: 10, paddingVertical: 8,
    borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
    alignItems: "center",
  },
  removeItemBtn: {
    padding: 4,
    marginLeft: 4,
  },
  orderItemImg: {
    width: 50, height: 50, borderRadius: 10, backgroundColor: BG,
  },

  // Saved addresses
  savedAddrChip: {
    flexDirection: "row", alignItems: "center", gap: 6,
    paddingHorizontal: 12, paddingVertical: 10,
    borderWidth: 1, borderColor: "#E5E5EA", borderRadius: 10,
    backgroundColor: "#FAFAFA", minWidth: 180, maxWidth: 260,
  },

  // Inputs
  input: {
    borderWidth: 1, borderColor: "#E5E5EA", borderRadius: 12,
    paddingHorizontal: 14, paddingVertical: 12, fontSize: 14,
    backgroundColor: "#FAFAFA", color: DARK,
  },

  saveAddrBtn: {
    flexDirection: "row", alignItems: "center", gap: 6,
    borderWidth: 1, borderColor: "#fce7f3", borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 10, backgroundColor: "#FFF0F5",
  },

  // Delivery zone
  zonePicker: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    borderWidth: 1, borderColor: "#E5E5EA", borderRadius: 12,
    paddingHorizontal: 14, paddingVertical: 14, backgroundColor: "#FAFAFA",
  },
  zoneOptions: {
    marginTop: 6, borderWidth: 1, borderColor: "#E5E5EA",
    borderRadius: 12, overflow: "hidden", backgroundColor: "#fff",
  },
  zoneOption: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 14, paddingVertical: 14,
    borderBottomWidth: 1, borderBottomColor: "#F5F5F5",
  },

  // Radio buttons
  radioBtn: {
    flexDirection: "row", alignItems: "center", gap: 8, flex: 1,
    borderWidth: 1.5, borderColor: "#E5E5EA", borderRadius: 12,
    paddingHorizontal: 14, paddingVertical: 12,
  },
  radioDot: {
    width: 16, height: 16, borderRadius: 8,
    borderWidth: 2, borderColor: "#D1D5DB",
  },

  // Info box
  infoBox: {
    flexDirection: "row", alignItems: "center", gap: 6,
    borderRadius: 10, paddingHorizontal: 12, paddingVertical: 10,
  },

  // Summary
  summaryRow: {
    flexDirection: "row", justifyContent: "space-between", alignItems: "center",
    marginBottom: 8,
  },

  // Terms
  termsRow: {
    flexDirection: "row", alignItems: "flex-start", gap: 10,
    paddingHorizontal: 4, marginBottom: 12,
  },
  checkbox: {
    width: 22, height: 22, borderRadius: 6, borderWidth: 2, borderColor: "#D1D5DB",
    justifyContent: "center", alignItems: "center", marginTop: 2,
  },

  // Bottom CTA
  bottomCta: {
    position: "absolute", bottom: 0, left: 0, right: 0,
    backgroundColor: "#fff", paddingHorizontal: 16, paddingTop: 14,
    shadowColor: "#000", shadowOpacity: 0.08, shadowRadius: 12,
    shadowOffset: { width: 0, height: -4 }, elevation: 10,
  },
  confirmBtn: {
    flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 8,
    backgroundColor: ACCENT, borderRadius: 14, paddingVertical: 16,
    shadowColor: ACCENT, shadowOpacity: 0.3, shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 }, elevation: 4,
  },

  // Success confirmation
  successOverlay: {
    flex: 1,
    justifyContent: "flex-end",
    backgroundColor: "rgba(17,17,24,0.56)",
  },
  successSheet: {
    backgroundColor: "#fff",
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    paddingHorizontal: 20,
    paddingTop: 12,
    shadowColor: "#000",
    shadowOpacity: 0.18,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: -6 },
    elevation: 16,
  },
  successHandle: {
    width: 42,
    height: 4,
    borderRadius: 2,
    backgroundColor: "#E5E7EB",
    alignSelf: "center",
    marginBottom: 18,
  },
  successHero: {
    height: 96,
    alignItems: "center",
    justifyContent: "center",
  },
  successGlow: {
    position: "absolute",
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: "#ECFDF5",
  },
  successIconWrap: {
    width: 68,
    height: 68,
    borderRadius: 34,
    backgroundColor: "#10B981",
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 6,
    borderColor: "#D1FAE5",
  },
  successSummaryCard: {
    marginTop: 18,
    marginBottom: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#EEF0F4",
    backgroundColor: "#FAFAFC",
    padding: 14,
  },
  successSummaryRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
  },
  successSummaryIcon: {
    width: 42,
    height: 42,
    borderRadius: 12,
    backgroundColor: "#FFF0F5",
    alignItems: "center",
    justifyContent: "center",
  },
  successPill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 999,
    backgroundColor: "#D1FAE5",
  },
  successDivider: {
    height: 1,
    backgroundColor: "#ECEEF3",
    marginVertical: 12,
  },
  successAmountRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 6,
  },
  successPrimaryBtn: {
    height: 52,
    borderRadius: 14,
    backgroundColor: ACCENT,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    shadowColor: ACCENT,
    shadowOpacity: 0.25,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 5 },
    elevation: 4,
  },
  successSecondaryBtn: {
    height: 48,
    borderRadius: 14,
    backgroundColor: "#FFF0F5",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 7,
    marginTop: 10,
    borderWidth: 1,
    borderColor: "#FAD6E6",
  },

  // WebView
  webViewRoot: { flex: 1, backgroundColor: DARK },
  webViewHeader: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 12, paddingVertical: 10, backgroundColor: DARK,
  },
  webViewCloseBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: "rgba(255,255,255,0.12)",
    justifyContent: "center", alignItems: "center",
  },
  webViewLoader: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center", alignItems: "center",
    backgroundColor: "#fff", zIndex: 10,
  },
});
