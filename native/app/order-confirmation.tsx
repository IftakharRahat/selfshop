import { useState, useRef, useEffect } from "react";
import {
  View, ScrollView, Pressable, StyleSheet, ActivityIndicator,
  TextInput, Image, Modal, Platform, KeyboardAvoidingView, Alert,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
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

export default function OrderConfirmationScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();
  const webViewRef = useRef<WebView>(null);

  // ── Cart items ──
  const { data: cartData, isLoading: cartLoading } = useQuery({
    queryKey: ["cart-items"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-cart-content");
      return data?.data ?? [];
    },
  });
  const cartItems: any[] = cartData ?? [];

  // Redirect if cart empty
  useEffect(() => {
    if (!cartLoading && cartItems.length === 0) {
      router.replace("/(tabs)");
    }
  }, [cartLoading, cartItems.length]);

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

  // WebView for SSLCommerz
  const [gatewayUrl, setGatewayUrl] = useState<string | null>(null);
  const [webViewLoading, setWebViewLoading] = useState(true);

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
      const { data } = await apiClient.post("/order-now", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: (result) => {
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });

      // Handle SSLCommerz redirect
      if (result?.ssl_redirect && result?.gateway_url) {
        setGatewayUrl(result.gateway_url);
        setWebViewLoading(true);
        return;
      }

      if (result?.status) {
        Alert.alert("Order Confirmed", "Your order has been placed successfully!", [
          { text: "OK", onPress: () => router.replace("/(tabs)") },
        ]);
      }
    },
    onError: () => {
      toast.error("Failed to place order. Please try again.");
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
    if (!validateForm()) return;
    if (!deliveryZone) {
      toast.error("Please select a delivery zone");
      return;
    }
    if (!agreedToTerms) {
      toast.error("Please agree to Terms & Conditions");
      return;
    }

    const formData = new FormData();
    formData.append("customerName", customerData.name);
    formData.append("customerPhone", customerData.phone);
    formData.append("customerAddress", customerData.address);
    formData.append("subTotal", subtotal.toString());
    formData.append("deliveryCharge", deliveryCharge.toString());
    formData.append("delivery_zone", deliveryZoneLabel!);
    formData.append("advance_delivery", advanceDelivery);
    formData.append("balance_from", paymentMethod === "account" ? "from_account" : "online_pay");
    if (customerData.note) formData.append("customerNote", customerData.note);

    createOrderMutation.mutate(formData);
  };

  // WebView navigation for SSLCommerz
  const handleNavChange = (navState: { url: string }) => {
    const url = navState.url.toLowerCase();
    const isSuccess = url.includes("payment=success") || url.includes("/payment/success") || url.includes("status=success");
    const isFail = url.includes("payment=failed") || url.includes("payment=error") || url.includes("/payment/fail");
    const isCancel = url.includes("payment=canceled") || url.includes("payment=cancelled") || url.includes("/payment/cancel");

    if (isSuccess) {
      toast.success("Payment successful! 🎉");
      setGatewayUrl(null);
      queryClient.invalidateQueries({ queryKey: ["cart-items"] });
      router.replace("/(tabs)");
    } else if (isFail) {
      toast.error("Payment failed. Please try again.");
      setGatewayUrl(null);
    } else if (isCancel) {
      toast.info("Payment cancelled.");
      setGatewayUrl(null);
    }
  };

  // ── Loading ──
  if (cartLoading) {
    return (
      <View style={[st.center, { paddingTop: insets.top + 60 }]}>
        <ActivityIndicator size="large" color={ACCENT} />
        <Text fontSize="$3" color={GREY} mt="$3">Loading order...</Text>
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
            {cartItems.map((item: any) => (
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
              </View>
            ))}
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
                  saveAddressMutation.mutate({ label, ...customerData });
                }}
                disabled={saveAddressMutation.isPending}
              >
                <Ionicons name="bookmark-outline" size={14} color={ACCENT} />
                <Text fontSize={12} fontWeight="600" color={ACCENT}>Save this address</Text>
              </Pressable>
            )}
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
            <ActivityIndicator size="small" color="#fff" />
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

          {webViewLoading && (
            <View style={st.webViewLoader}>
              <ActivityIndicator size="large" color={ACCENT} />
              <Text mt="$2" color={GREY} fontSize="$3">Loading payment gateway...</Text>
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
