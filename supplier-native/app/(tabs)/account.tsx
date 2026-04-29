import React from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { BRAND, SECTION_COLORS, CARD_SHADOW } from "@/lib/constants";
import { useSession } from "@/lib/auth-client";
import apiClient from "@/lib/api-client";
import { useQuery } from "@tanstack/react-query";

interface MenuItem {
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  subtitle?: string;
  onPress: () => void;
  color?: string;
}

interface VendorProfileBrief {
  status?: string;
  is_verified_badge?: boolean;
}

export default function AccountScreen() {
  const insets = useSafeAreaInsets();
  const { data: session, isLoading, signOut } = useSession();

  const { data: vendorData } = useQuery({
    queryKey: ["vendor-profile-brief"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/profile");
      return data?.data?.vendor as VendorProfileBrief | null;
    },
    enabled: !!session,
  });

  const vendorStatus = vendorData?.status ?? "pending";
  const isVerified = vendorData?.is_verified_badge ?? false;

  const handleLogout = () => {
    Alert.alert("Logout", "Are you sure you want to logout?", [
      { text: "Cancel", style: "cancel" },
      {
        text: "Logout",
        style: "destructive",
        onPress: async () => {
          await signOut();
          router.replace("/login");
        },
      },
    ]);
  };

  const menuSections: { title: string; color: string; items: MenuItem[] }[] = [
    {
      title: "Business",
      color: SECTION_COLORS.business,
      items: [
        { icon: "storefront-outline", label: "Shop Profile", subtitle: "Edit your store info & branding", onPress: () => router.push("/account/profile") },
        { icon: "shield-checkmark-outline", label: "KYC Documents", subtitle: "Upload verification documents", onPress: () => router.push("/account/kyc") },
        { icon: "notifications-outline", label: "Notifications", subtitle: "View all notifications", onPress: () => router.push("/account/notifications") },
        { icon: "key-outline", label: "Change Password", subtitle: "Update your account password", onPress: () => router.push("/account/change-password") },
      ],
    },
    {
      title: "Finance",
      color: SECTION_COLORS.finance,
      items: [
        { icon: "wallet-outline", label: "Earnings", subtitle: "View your earnings history", onPress: () => router.push("/account/earnings") },
        { icon: "cash-outline", label: "Payouts", subtitle: "Request and track payouts", onPress: () => router.push("/account/payouts") },
      ],
    },
    {
      title: "Management",
      color: SECTION_COLORS.management,
      items: [
        { icon: "layers-outline", label: "Inventory", subtitle: "Stock levels & alerts", onPress: () => router.push("/account/inventory") },
        { icon: "boat-outline", label: "Shipping Methods", subtitle: "Configure shipping rates", onPress: () => router.push("/account/shipping") },
      ],
    },
    {
      title: "Insights",
      color: SECTION_COLORS.insights,
      items: [
        { icon: "bar-chart-outline", label: "Reports", subtitle: "Sales and product analytics", onPress: () => router.push("/account/reports") },
        { icon: "star-outline", label: "Reviews", subtitle: "Customer product reviews", onPress: () => router.push("/account/reviews") },
        { icon: "pricetag-outline", label: "Discounts & Commissions", subtitle: "Category discounts & rates", onPress: () => router.push("/account/discounts") },
      ],
    },
  ];

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Account</Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
      >
        {/* Profile Card */}
        <View style={styles.profileCard}>
          <View style={styles.avatarWrap}>
            <Text style={styles.avatarText}>
              {session?.user?.name?.charAt(0)?.toUpperCase() ?? "S"}
            </Text>
          </View>
          <View style={styles.profileInfo}>
            {isLoading ? (
              <Text style={styles.profileName}>Loading...</Text>
            ) : session ? (
              <>
                <Text style={styles.profileName}>{session.user.name}</Text>
                <Text style={styles.profileEmail}>{session.user.email}</Text>
                {session.user.phone && (
                  <Text style={styles.profilePhone}>{session.user.phone}</Text>
                )}
                <View style={styles.statusRow}>
                  <View style={[styles.vendorStatusBadge, { backgroundColor: vendorStatus === "approved" ? "#D1FAE5" : "#FEF3C7" }]}>
                    <Text style={[styles.vendorStatusText, { color: vendorStatus === "approved" ? "#065F46" : "#92400E" }]}>
                      {vendorStatus.charAt(0).toUpperCase() + vendorStatus.slice(1)}
                    </Text>
                  </View>
                  {isVerified && (
                    <View style={[styles.vendorStatusBadge, { backgroundColor: "#DBEAFE" }]}>
                      <Ionicons name="checkmark-circle" size={10} color="#1D4ED8" />
                      <Text style={[styles.vendorStatusText, { color: "#1D4ED8" }]}>Verified</Text>
                    </View>
                  )}
                </View>
              </>
            ) : (
              <>
                <Text style={styles.profileName}>Not logged in</Text>
                <TouchableOpacity
                  style={styles.loginBtn}
                  onPress={() => router.push("/login")}
                >
                  <Text style={styles.loginBtnText}>Login</Text>
                </TouchableOpacity>
              </>
            )}
          </View>
        </View>

        {/* Menu Sections */}
        {menuSections.map((section) => (
          <View key={section.title} style={styles.menuSection}>
            <Text style={styles.sectionTitle}>{section.title}</Text>
            <View style={styles.menuCard}>
              {section.items.map((item, i) => (
                <TouchableOpacity
                  key={item.label}
                  style={[
                    styles.menuItem,
                    i < section.items.length - 1 && styles.menuItemBorder,
                  ]}
                  onPress={item.onPress}
                  activeOpacity={0.6}
                >
                  <View style={[styles.menuIconWrap, { backgroundColor: section.color + "15" }]}>
                    <Ionicons name={item.icon} size={20} color={section.color} />
                  </View>
                  <View style={styles.menuTextWrap}>
                    <Text style={styles.menuLabel}>{item.label}</Text>
                    {item.subtitle && (
                      <Text style={styles.menuSubtitle}>{item.subtitle}</Text>
                    )}
                  </View>
                  <Ionicons name="chevron-forward" size={16} color="#d1d5db" />
                </TouchableOpacity>
              ))}
            </View>
          </View>
        ))}

        {/* Logout */}
        {session && (
          <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
            <Ionicons name="log-out-outline" size={20} color="#ef4444" />
            <Text style={styles.logoutText}>Logout</Text>
          </TouchableOpacity>
        )}

        <View style={{ height: 100 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    paddingHorizontal: 20,
    paddingVertical: 14,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  headerTitle: { fontSize: 22, fontWeight: "700", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  profileCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    gap: 14,
    ...CARD_SHADOW,
  },
  avatarWrap: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
  },
  avatarText: { fontSize: 22, fontWeight: "700", color: "#fff" },
  profileInfo: { flex: 1 },
  profileName: { fontSize: 16, fontWeight: "600", color: "#1a1a2e" },
  profileEmail: { fontSize: 13, color: "#6b7280", marginTop: 2 },
  profilePhone: { fontSize: 12, color: "#9ca3af", marginTop: 1 },
  statusRow: { flexDirection: "row", gap: 6, marginTop: 6 },
  vendorStatusBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 3,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 8,
  },
  vendorStatusText: { fontSize: 10, fontWeight: "600" },
  loginBtn: {
    marginTop: 8,
    backgroundColor: BRAND.primary,
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
    alignSelf: "flex-start",
  },
  loginBtnText: { color: "#fff", fontWeight: "600", fontSize: 13 },
  menuSection: { marginTop: 20 },
  sectionTitle: {
    fontSize: 12,
    fontWeight: "600",
    color: "#9ca3af",
    textTransform: "uppercase",
    letterSpacing: 0.5,
    marginBottom: 8,
    marginLeft: 4,
  },
  menuCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    overflow: "hidden",
    ...CARD_SHADOW,
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    padding: 14,
    gap: 12,
  },
  menuItemBorder: {
    borderBottomWidth: 1,
    borderBottomColor: "#f9fafb",
  },
  menuIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
  },
  menuTextWrap: { flex: 1 },
  menuLabel: { fontSize: 14, fontWeight: "500", color: "#1a1a2e" },
  menuSubtitle: { fontSize: 11, color: "#9ca3af", marginTop: 1 },
  logoutBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    marginTop: 24,
    paddingVertical: 14,
    backgroundColor: "#FEF2F2",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#FEE2E2",
  },
  logoutText: { fontSize: 14, fontWeight: "600", color: "#ef4444" },
});
