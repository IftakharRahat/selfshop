import { useState, useMemo } from "react";
import {
  View, ScrollView, Pressable, StyleSheet, ActivityIndicator, Linking,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";

/* ── Types ── */
interface PackagePlan {
  id: number;
  package_name: string;
  price: number;
  discount_price?: number;
  validity?: number | string;
  status?: string;
}

interface PackageInvoice {
  id: number;
  invoiceID: string;
  package_id?: number;
  amount?: number;
  payable_amount?: number;
  status?: string;
}

/* ── Feature Matrix ── */
type Feature = { label: string; enabled: boolean };
const FEATURES: Record<"basic" | "standard", Feature[]> = {
  basic: [
    { label: "Dashboard access", enabled: true },
    { label: "Order management", enabled: true },
    { label: "Referral income", enabled: true },
    { label: "Free video course", enabled: true },
    { label: "Ticketing system", enabled: true },
    { label: "Product request", enabled: true },
    { label: "Order analytics", enabled: true },
    { label: "Winning Product", enabled: true },
    { label: "Bulk Wholesale Order", enabled: true },
    { label: "Sales Bonus Campaign", enabled: true },
    { label: "Livechat Support", enabled: true },
    { label: "Ecommerce Website", enabled: false },
    { label: "Free .com domain", enabled: false },
    { label: "Free One Year Hosting", enabled: false },
    { label: "Technical support", enabled: false },
  ],
  standard: [
    { label: "Dashboard access", enabled: true },
    { label: "Order management", enabled: true },
    { label: "Referral income", enabled: true },
    { label: "Free video course", enabled: true },
    { label: "Ticketing system", enabled: true },
    { label: "Product request", enabled: true },
    { label: "Order analytics", enabled: true },
    { label: "Winning Product", enabled: true },
    { label: "Bulk Wholesale Order", enabled: true },
    { label: "Sales Bonus Campaign", enabled: true },
    { label: "Livechat Support", enabled: true },
    { label: "Ecommerce Website", enabled: true },
    { label: "Free .com domain", enabled: true },
    { label: "Free One Year Hosting", enabled: true },
    { label: "Technical support", enabled: true },
  ],
};

/* ── Helpers ── */
const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";

const fmt = (n: number) =>
  n.toLocaleString("en-BD", { maximumFractionDigits: 0 });

const planType = (p: PackagePlan): "basic" | "standard" => {
  const name = (p?.package_name ?? "").toLowerCase();
  return name.includes("standard") || name.includes("premium")
    ? "standard"
    : "basic";
};

/* ── Screen ── */
export default function PricingScreen() {
  const insets = useSafeAreaInsets();

  const { data, isLoading } = useQuery({
    queryKey: ["pricing-packages"],
    queryFn: async () => {
      const res = await apiClient.get("/our-packages");
      return res.data as {
        status: boolean;
        data: { packages: PackagePlan[]; invoice: PackageInvoice | null };
      };
    },
  });

  const purchaseMutation = useMutation({
    mutationFn: async (payload: { package_id: number; amount: number }) => {
      const res = await apiClient.post("/purchese-package", payload);
      return res.data as {
        status: boolean;
        data: { invoice: PackageInvoice };
      };
    },
    onSuccess: (result) => {
      const inv = result?.data?.invoice;
      if (inv?.invoiceID) {
        toast.success("Invoice created!");
        router.push({
          pathname: "/invoice",
          params: {
            invoice_id: String(inv.id),
            invoiceID: inv.invoiceID,
            package_id: String(inv.package_id || ""),
            package_name: selected?.package_name || "",
            amount: String(inv.payable_amount || inv.amount || payable),
          },
        });
      }
    },
    onError: () => {
      toast.error("Failed to create invoice. Please try again.");
    },
  });

  const packages = data?.data?.packages ?? [];
  const [selectedId, setSelectedId] = useState<number | null>(null);

  const selected = useMemo(() => {
    if (!packages.length) return null;
    if (!selectedId) return packages[0];
    return packages.find((p) => p.id === selectedId) ?? packages[0];
  }, [packages, selectedId]);

  const handlePurchase = () => {
    if (!selected) return;
    const discount = Number(selected.discount_price || 0);
    const regular = Number(selected.price || 0);
    const amount = discount > 0 ? discount : regular;
    purchaseMutation.mutate({ package_id: selected.id, amount });
  };

  /* ── Loading ── */
  if (isLoading) {
    return (
      <View style={[s.center, { paddingTop: insets.top + 60 }]}>
        <ActivityIndicator size="large" color={ACCENT} />
        <Text mt="$3" color={GREY}>Loading packages...</Text>
      </View>
    );
  }

  if (!packages.length || !selected) {
    return (
      <View style={[s.center, { paddingTop: insets.top + 60 }]}>
        <Ionicons name="alert-circle-outline" size={48} color={GREY} />
        <Text mt="$3" color={GREY} text="center">
          No packages available right now.{"\n"}Please contact support.
        </Text>
      </View>
    );
  }

  const type = planType(selected);
  const features = FEATURES[type];
  const regularPrice = Number(selected.price || 0);
  const discountPrice = Number(selected.discount_price || 0);
  const payable = discountPrice > 0 ? discountPrice : regularPrice;
  const included = features.filter((f) => f.enabled);
  const excluded = features.filter((f) => !f.enabled);
  const validity = Number(selected.validity ?? 12);

  return (
    <View style={[s.root, { paddingTop: insets.top }]}>
      {/* ── Premium Header ── */}
      <View style={s.heroHeader}>
        {/* Top row — back + close */}
        <View style={s.heroTopRow}>
          <Pressable onPress={() => router.back()} style={s.heroBackBtn} hitSlop={12}>
            <Ionicons name="arrow-back" size={20} color="#fff" />
          </Pressable>
        </View>

        {/* Badge */}
        <View style={s.heroBadge}>
          <Ionicons name="diamond" size={16} color="#a78bfa" />
        </View>

        {/* Title & subtitle */}
        <Text fontSize="$6" fontWeight="800" color="#fff" mt="$2" text="center">
          Unlock Full Access
        </Text>
        <Text fontSize="$3" color="#a5b4fc" mt="$1" text="center">
          Select your plan and start reselling today
        </Text>

        {/* ── Plan Toggle inside header ── */}
        <View style={s.toggle}>
          {packages.slice(0, 2).map((plan) => {
            const isActive = plan.id === selected.id;
            return (
              <Pressable
                key={plan.id}
                style={[s.toggleBtn, isActive && s.toggleBtnActive]}
                onPress={() => setSelectedId(plan.id)}
              >
                <Text
                  fontSize="$3"
                  fontWeight="600"
                  color={isActive ? DARK : "rgba(255,255,255,0.6)"}
                >
                  {plan.package_name}
                </Text>
              </Pressable>
            );
          })}
        </View>
      </View>

      <ScrollView
        contentContainerStyle={[s.scroll, { paddingBottom: insets.bottom + 100 }]}
        showsVerticalScrollIndicator={false}
      >

        {/* ── Pricing Card ── */}
        <View style={s.card}>
          {/* Gradient Header */}
          <View style={s.cardHeader}>
            <Text fontSize="$2" color="#a5b4fc" fontWeight="500" textTransform="uppercase" letterSpacing={1}>
              {selected.package_name} Plan
            </Text>
            <View style={s.priceRow}>
              {discountPrice > 0 && (
                <Text fontSize="$4" color="#818cf880" textDecorationLine="line-through" mr="$2">
                  ৳{fmt(regularPrice)}
                </Text>
              )}
              <Text fontSize="$9" fontWeight="bold" color="#fff">
                ৳{fmt(payable)}
              </Text>
              <Text fontSize="$3" color="#a5b4fc" ml="$1">
                / {validity} month{validity > 1 ? "s" : ""}
              </Text>
            </View>
          </View>

          {/* Included Features */}
          {included.length > 0 && (
            <View style={s.featureSection}>
              <Text fontSize={11} fontWeight="700" color={GREY} textTransform="uppercase" letterSpacing={1} mb="$2">
                What's included
              </Text>
              {included.map((f) => (
                <View key={f.label} style={s.featureRow}>
                  <View style={[s.featureIcon, { backgroundColor: "#ecfdf5" }]}>
                    <Ionicons name="checkmark-circle" size={16} color="#10b981" />
                  </View>
                  <Text fontSize="$3" color={DARK}>{f.label}</Text>
                </View>
              ))}
            </View>
          )}

          {/* Excluded Features */}
          {excluded.length > 0 && (
            <View style={[s.featureSection, { borderTopWidth: 1, borderTopColor: "#f3f4f6" }]}>
              <Text fontSize={11} fontWeight="700" color={GREY} textTransform="uppercase" letterSpacing={1} mb="$2">
                Not included
              </Text>
              {excluded.map((f) => (
                <View key={f.label} style={s.featureRow}>
                  <View style={[s.featureIcon, { backgroundColor: "#f9fafb" }]}>
                    <Ionicons name="close-circle" size={16} color="#d1d5db" />
                  </View>
                  <Text fontSize="$3" color="#9ca3af">{f.label}</Text>
                </View>
              ))}
            </View>
          )}
        </View>

        {/* ── Support Banner ── */}
        <View style={s.supportBanner}>
          <Text fontSize="$2" color={GREY}>Need help with payment?</Text>
          <Pressable
            style={s.supportBtn}
            onPress={() => {
              const url = process.env.EXPO_PUBLIC_WEB_URL || "https://selfshop.com.bd";
              Linking.openURL(`${url}/support`);
            }}
          >
            <Ionicons name="headset" size={14} color="#fff" />
            <Text fontSize="$2" fontWeight="600" color="#fff" ml="$1">Support</Text>
          </Pressable>
        </View>
      </ScrollView>

      {/* ── Sticky Bottom CTA ── */}
      <View style={[s.bottomCta, { paddingBottom: Math.max(insets.bottom, 16) }]}>
        <View style={s.ctaInner}>
          {/* Left — Plan & Price */}
          <View style={s.ctaInfo}>
            <Text fontSize={11} color={GREY} fontWeight="500" textTransform="uppercase" letterSpacing={0.5}>
              {selected.package_name} Plan
            </Text>
            <View style={{ flexDirection: "row", alignItems: "baseline", gap: 4 }}>
              {discountPrice > 0 && (
                <Text fontSize="$2" color="#c4c4c4" textDecorationLine="line-through">
                  ৳{fmt(regularPrice)}
                </Text>
              )}
              <Text fontSize="$6" fontWeight="800" color={DARK}>
                ৳{fmt(payable)}
              </Text>
              <Text fontSize="$2" color={GREY} fontWeight="500">
                /{validity}mo
              </Text>
            </View>
          </View>

          {/* Right — Action Button */}
          <Pressable
            style={({ pressed }) => [s.ctaBtn, pressed && s.ctaBtnPressed]}
            onPress={handlePurchase}
            disabled={purchaseMutation.isPending}
          >
            {purchaseMutation.isPending ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <Text fontSize="$3" fontWeight="700" color="#fff">
                  Pay Now
                </Text>
                <View style={s.ctaArrow}>
                  <Ionicons name="arrow-forward" size={14} color={ACCENT} />
                </View>
              </>
            )}
          </Pressable>
        </View>
      </View>
    </View>
  );
}

/* ── Styles ── */
const s = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#F5F5FA" },
  center: { flex: 1, justifyContent: "center", alignItems: "center", backgroundColor: "#F5F5FA" },

  heroHeader: {
    backgroundColor: "#1e1b4b", paddingHorizontal: 20, paddingBottom: 20,
    borderBottomLeftRadius: 28, borderBottomRightRadius: 28,
    alignItems: "center",
  },
  heroTopRow: {
    width: "100%", flexDirection: "row", alignItems: "center",
    paddingTop: 8, marginBottom: 8,
  },
  heroBackBtn: {
    width: 36, height: 36, borderRadius: 18,
    backgroundColor: "rgba(255,255,255,0.12)",
    justifyContent: "center", alignItems: "center",
  },
  heroBadge: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: "rgba(167,139,250,0.15)",
    justifyContent: "center", alignItems: "center",
  },

  scroll: { padding: 16 },


  toggle: {
    flexDirection: "row", backgroundColor: "rgba(255,255,255,0.1)", borderRadius: 30,
    padding: 4, marginTop: 18, width: "100%",
  },
  toggleBtn: {
    flex: 1, paddingVertical: 10, borderRadius: 26, alignItems: "center",
  },
  toggleBtnActive: {
    backgroundColor: "#fff", shadowColor: "#000", shadowOpacity: 0.15,
    shadowRadius: 6, shadowOffset: { width: 0, height: 2 }, elevation: 4,
  },

  card: {
    borderRadius: 20, overflow: "hidden",
    backgroundColor: "#fff", shadowColor: "#000", shadowOpacity: 0.08,
    shadowRadius: 12, shadowOffset: { width: 0, height: 4 }, elevation: 4,
    marginBottom: 16,
  },
  cardHeader: {
    paddingVertical: 20, paddingHorizontal: 20, alignItems: "center",
    backgroundColor: "#1e1b4b",
  },
  priceRow: {
    flexDirection: "row", alignItems: "baseline", marginTop: 4,
  },

  featureSection: { paddingHorizontal: 20, paddingVertical: 14 },
  featureRow: {
    flexDirection: "row", alignItems: "center", gap: 10, marginBottom: 8,
  },
  featureIcon: {
    width: 24, height: 24, borderRadius: 12,
    justifyContent: "center", alignItems: "center",
  },

  supportBanner: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    backgroundColor: "#fff", borderRadius: 14, padding: 14,
    borderWidth: 1, borderColor: "#e5e7eb",
  },
  supportBtn: {
    flexDirection: "row", alignItems: "center",
    backgroundColor: DARK, borderRadius: 10, paddingHorizontal: 14, paddingVertical: 8,
  },

  bottomCta: {
    position: "absolute", bottom: 0, left: 0, right: 0,
    backgroundColor: "#fff", paddingHorizontal: 16, paddingTop: 14,
    shadowColor: "#000", shadowOpacity: 0.08, shadowRadius: 16,
    shadowOffset: { width: 0, height: -6 }, elevation: 12,
  },
  ctaInner: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
  },
  ctaInfo: { flex: 1, gap: 2 },
  ctaBtn: {
    flexDirection: "row", alignItems: "center", gap: 10,
    backgroundColor: ACCENT, borderRadius: 16, paddingVertical: 14, paddingHorizontal: 22,
    shadowColor: ACCENT, shadowOpacity: 0.35, shadowRadius: 12,
    shadowOffset: { width: 0, height: 4 }, elevation: 6,
  },
  ctaBtnPressed: {
    transform: [{ scale: 0.96 }], opacity: 0.9,
  },
  ctaArrow: {
    width: 24, height: 24, borderRadius: 12, backgroundColor: "#fff",
    justifyContent: "center", alignItems: "center",
  },
});
