import { useState, useMemo, useRef } from "react";
import {
  View, ScrollView, Pressable, StyleSheet, ActivityIndicator, Linking, Modal, Platform,
  KeyboardAvoidingView,
} from "react-native";
import { Text } from "tamagui";
import { useLocalSearchParams, router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";
import { WebView } from "react-native-webview";

import apiClient from "@/lib/api-client";

/* ── Helpers ── */
const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";
const GREEN = "#059669";

const fmt = (n: number) =>
  n.toLocaleString("en-BD", { maximumFractionDigits: 0 });

/* ── Screen ── */
export default function InvoiceScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const params = useLocalSearchParams<{
    invoice_id?: string;
    invoiceID?: string;
    package_id?: string;
    package_name?: string;
    amount?: string;
  }>();

  const invoiceDbId = Number(params.invoice_id || 0);
  const invoiceCode = params.invoiceID || "";
  const packageName = params.package_name || "Selected Package";
  const paramAmount = Number(params.amount || 0);

  const [copied, setCopied] = useState(false);
  const [gatewayUrl, setGatewayUrl] = useState<string | null>(null);
  const [webViewLoading, setWebViewLoading] = useState(true);
  const webViewRef = useRef<WebView>(null);

  // Fetch latest invoice data from API
  const { data: pricingData } = useQuery({
    queryKey: ["pricing-packages"],
    queryFn: async () => {
      const res = await apiClient.get("/our-packages");
      return res.data;
    },
  });

  const currentInvoice = useMemo(() => {
    const apiInvoice = pricingData?.data?.invoice;
    return {
      id: invoiceDbId || apiInvoice?.id || 0,
      invoiceID: invoiceCode || apiInvoice?.invoiceID || "",
      amount: paramAmount || Number(apiInvoice?.payable_amount || apiInvoice?.amount || 0),
    };
  }, [pricingData, invoiceDbId, invoiceCode, paramAmount]);

  // Initiate payment mutation
  const paymentMutation = useMutation({
    mutationFn: async (invoice_id: number) => {
      const res = await apiClient.post("/package-payment/initiate", { invoice_id });
      return res.data as {
        status: boolean;
        data: { gateway_url: string; tran_id: string };
      };
    },
    onSuccess: (result) => {
      const url = result?.data?.gateway_url;
      if (url) {
        setGatewayUrl(url);
        setWebViewLoading(true);
      } else {
        toast.error("Could not get payment link. Please try again.");
      }
    },
    onError: () => {
      toast.error("Failed to initiate payment. Please try again.");
    },
  });

  const handleCopy = () => {
    if (!currentInvoice.invoiceID) return;
    setCopied(true);
    toast.success(`Invoice ID: ${currentInvoice.invoiceID}`);
    setTimeout(() => setCopied(false), 2000);
  };

  const handlePayNow = () => {
    if (!currentInvoice.id) {
      toast.error("No invoice found");
      return;
    }
    paymentMutation.mutate(currentInvoice.id);
  };

  const handleCloseWebView = () => {
    setGatewayUrl(null);
    setWebViewLoading(true);
    // Refresh data — payment might have completed
    queryClient.invalidateQueries({ queryKey: ["pricing-packages"] });
    queryClient.invalidateQueries({ queryKey: ["reseller-profile"] });
  };

  // Detect SSLCommerz success/fail/cancel URL patterns.
  // The backend redirects to the frontend site with a `payment=` query param
  // (e.g. ?payment=success, ?payment=canceled, ?payment=failed, ?payment=error).
  // We also keep legacy checks for /payment/… paths and status= params.
  const handleNavigationChange = (navState: { url: string }) => {
    const url = navState.url.toLowerCase();

    const isSuccess =
      url.includes("payment=success") ||
      url.includes("payment=already_paid") ||
      url.includes("/payment/success") ||
      url.includes("status=success");

    const isFail =
      url.includes("payment=failed") ||
      url.includes("payment=error") ||
      url.includes("/payment/fail") ||
      url.includes("status=fail");

    const isCancel =
      url.includes("payment=canceled") ||
      url.includes("payment=cancelled") ||
      url.includes("/payment/cancel") ||
      url.includes("status=cancel");

    if (isSuccess) {
      toast.success("Payment successful! 🎉 Your account is now active.");
      handleCloseWebView();
      router.replace("/");
    } else if (isFail) {
      toast.error("Payment failed. Please try again.");
      handleCloseWebView();
    } else if (isCancel) {
      toast.info("Payment cancelled.");
      handleCloseWebView();
    }
  };

  // No invoice
  if (!currentInvoice.id && !currentInvoice.invoiceID) {
    return (
      <View style={[s.center, { paddingTop: insets.top + 60 }]}>
        <Ionicons name="receipt-outline" size={48} color={GREY} />
        <Text mt="$3" color={GREY} text="center">
          No active invoice found.{"\n"}Please select a package first.
        </Text>
        <Pressable style={s.linkBtn} onPress={() => router.replace("/pricing")}>
          <Text color={ACCENT} fontWeight="600">Go to Pricing</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <View style={[s.root, { paddingTop: insets.top }]}>
      {/* ── Header ── */}
      <View style={s.header}>
        <Pressable onPress={() => router.back()} hitSlop={12}>
          <Ionicons name="arrow-back" size={22} color={DARK} />
        </Pressable>
        <Text fontSize="$5" fontWeight="bold" color={DARK}>Invoice</Text>
        <View style={{ width: 22 }} />
      </View>

      <ScrollView
        contentContainerStyle={[s.scroll, { paddingBottom: insets.bottom + 100 }]}
        showsVerticalScrollIndicator={false}
      >
        {/* ── Success Icon ── */}
        <View style={s.successIcon}>
          <Ionicons name="checkmark-circle" size={56} color={GREEN} />
        </View>

        <Text fontSize="$4" color={DARK} text="center" mt="$3" fontWeight="500">
          Invoice generated successfully!
        </Text>
        <Text fontSize="$3" color={GREY} text="center" mt="$1" px="$4">
          Choose a payment method to complete your package activation. Your account will activate automatically after payment.
        </Text>

        {/* ── Invoice Card ── */}
        <View style={s.invoiceCard}>
          {/* Invoice ID row */}
          <View style={s.invoiceIdRow}>
            <View style={{ flex: 1 }}>
              <Text fontSize={11} color={GREY} textTransform="uppercase" letterSpacing={0.5}>
                Invoice ID
              </Text>
              <Text fontSize="$6" fontWeight="800" color={ACCENT} mt="$1">
                {currentInvoice.invoiceID}
              </Text>
            </View>
            <Pressable
              style={({ pressed }) => [s.copyBtn, pressed && { opacity: 0.7 }]}
              onPress={handleCopy}
            >
              <Ionicons
                name={copied ? "checkmark" : "copy-outline"}
                size={16}
                color="#fff"
              />
              <Text fontSize="$2" fontWeight="600" color="#fff" ml="$1">
                {copied ? "Copied" : "Copy"}
              </Text>
            </Pressable>
          </View>

          {/* Details grid */}
          <View style={s.detailsGrid}>
            <View style={s.detailCard}>
              <Text fontSize={11} color={GREY}>Package</Text>
              <Text fontSize="$3" fontWeight="700" color={DARK} mt="$1">
                {packageName}
              </Text>
            </View>
            <View style={s.detailCard}>
              <Text fontSize={11} color={GREY}>Payable</Text>
              <Text fontSize="$3" fontWeight="700" color={DARK} mt="$1">
                ৳{fmt(currentInvoice.amount)}
              </Text>
            </View>
          </View>
        </View>

        {/* ── Support Banner ── */}
        <View style={s.supportBanner}>
          <View style={s.supportTextCol}>
            <Text fontSize="$2" fontWeight="600" color="#fff">
              Need help with payment?
            </Text>
            <Text fontSize={11} color="rgba(255,255,255,0.7)" mt="$0.5">
              Our team is available 24/7
            </Text>
          </View>
          <Pressable
            style={s.supportBtn}
            onPress={() => {
              const base = process.env.EXPO_PUBLIC_WEB_URL || "https://selfshop.com.bd";
              Linking.openURL(`${base}/support`);
            }}
          >
            <Ionicons name="headset" size={14} color={GREEN} />
            <Text fontSize="$2" fontWeight="600" color={GREEN} ml="$1">Support</Text>
          </Pressable>
        </View>
      </ScrollView>

      {/* ── Sticky Pay Button ── */}
      <View style={[s.bottomCta, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        <Pressable
          style={({ pressed }) => [s.payBtn, pressed && { transform: [{ scale: 0.97 }] }]}
          onPress={handlePayNow}
          disabled={paymentMutation.isPending}
        >
          {paymentMutation.isPending ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <>
              <Ionicons name="shield-checkmark" size={20} color="#fff" />
              <Text fontSize="$4" fontWeight="bold" color="#fff" ml="$2">
                Pay Now — ৳{fmt(currentInvoice.amount)}
              </Text>
            </>
          )}
        </Pressable>
        <View style={s.secureRow}>
          <Ionicons name="lock-closed" size={12} color={GREY} />
          <Text fontSize={11} color={GREY} ml="$1">
            Secured by SSLCommerz · 256-bit encryption
          </Text>
        </View>
      </View>

      {/* ── Payment WebView Modal ── */}
      <Modal
        visible={!!gatewayUrl}
        animationType="slide"
        presentationStyle="fullScreen"
        onRequestClose={handleCloseWebView}
      >
        <View style={[s.webViewRoot, { paddingTop: insets.top }]}>
          {/* WebView header */}
          <View style={s.webViewHeader}>
            <Pressable onPress={handleCloseWebView} style={s.webViewCloseBtn} hitSlop={12}>
              <Ionicons name="close" size={20} color="#fff" />
            </Pressable>
            <View style={s.webViewTitleRow}>
              <Ionicons name="lock-closed" size={12} color="#4ade80" />
              <Text fontSize="$3" fontWeight="600" color="#fff" ml="$1" numberOfLines={1}>
                Secure Payment
              </Text>
            </View>
            <View style={{ width: 36 }} />
          </View>

          {/* Loading overlay */}
          {webViewLoading && (
            <View style={s.webViewLoader}>
              <ActivityIndicator size="large" color={GREEN} />
              <Text mt="$2" color={GREY} fontSize="$3">Loading payment gateway...</Text>
            </View>
          )}

          {/* WebView */}
          {gatewayUrl && (
            Platform.OS === "ios" ? (
              <KeyboardAvoidingView style={{ flex: 1 }} behavior="padding" keyboardVerticalOffset={insets.top + 44}>
                <WebView
                  ref={webViewRef}
                  source={{ uri: gatewayUrl }}
                  style={{ flex: 1, backgroundColor: "#1A1A2E" }}
                  onLoadEnd={() => setWebViewLoading(false)}
                  onNavigationStateChange={handleNavigationChange}
                  javaScriptEnabled
                  domStorageEnabled
                  startInLoadingState={false}
                  allowsInlineMediaPlayback
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
                onNavigationStateChange={handleNavigationChange}
                javaScriptEnabled
                domStorageEnabled
                startInLoadingState={false}
                scalesPageToFit={false}
                allowsInlineMediaPlayback
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

/* ── Styles ── */
const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#F5F5FA" },
  center: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F5F5FA" },

  header: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingVertical: 12,
    backgroundColor: "#fff", borderBottomWidth: 1, borderBottomColor: "#f0f0f0",
  },

  scroll: { padding: 20 },
  successIcon: { alignItems: "center", marginTop: 12 },

  invoiceCard: {
    backgroundColor: "#fff", borderRadius: 20, padding: 20, marginTop: 20,
    borderWidth: 1, borderColor: "#fce7f3",
    shadowColor: "#000", shadowOpacity: 0.05, shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 }, elevation: 3,
  },
  invoiceIdRow: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingBottom: 16, borderBottomWidth: 1, borderBottomColor: "#fce7f3",
  },
  copyBtn: {
    flexDirection: "row", alignItems: "center",
    backgroundColor: ACCENT, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 10,
  },
  detailsGrid: {
    flexDirection: "row", gap: 12, marginTop: 16,
  },
  detailCard: {
    flex: 1, backgroundColor: "#fdf2f8", borderRadius: 14, padding: 14,
    borderWidth: 1, borderColor: "#fce7f3",
  },

  supportBanner: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    backgroundColor: "#1e1b4b", borderRadius: 16, padding: 16, marginTop: 16,
  },
  supportTextCol: { flex: 1 },
  supportBtn: {
    flexDirection: "row", alignItems: "center",
    backgroundColor: "#fff", borderRadius: 12, paddingHorizontal: 14, paddingVertical: 10,
  },

  linkBtn: { marginTop: 16, padding: 12 },

  bottomCta: {
    position: "absolute", bottom: 0, left: 0, right: 0,
    backgroundColor: "#fff", paddingHorizontal: 16, paddingTop: 14,
    shadowColor: "#000", shadowOpacity: 0.08, shadowRadius: 16,
    shadowOffset: { width: 0, height: -6 }, elevation: 12,
  },
  payBtn: {
    flexDirection: "row", alignItems: "center", justifyContent: "center",
    backgroundColor: GREEN, borderRadius: 16, paddingVertical: 16,
    shadowColor: GREEN, shadowOpacity: 0.3, shadowRadius: 10,
    shadowOffset: { width: 0, height: 4 }, elevation: 6,
  },
  secureRow: {
    flexDirection: "row", alignItems: "center", justifyContent: "center",
    marginTop: 10,
  },

  /* WebView modal */
  webViewRoot: { flex: 1, backgroundColor: "#1A1A2E" },
  webViewHeader: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 12, paddingVertical: 10,
    backgroundColor: "#1A1A2E",
  },
  webViewCloseBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: "rgba(255,255,255,0.12)",
    justifyContent: "center", alignItems: "center",
  },
  webViewTitleRow: {
    flexDirection: "row", alignItems: "center",
  },
  webViewLoader: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center", alignItems: "center",
    backgroundColor: "#fff", zIndex: 10,
  },
});
