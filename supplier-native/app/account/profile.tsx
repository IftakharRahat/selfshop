import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
  Platform,
  Linking,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import * as ImagePicker from "expo-image-picker";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

const BASE_URL = process.env.EXPO_PUBLIC_API_URL?.replace("/api", "") ?? "";
function getImageUrl(path?: string | null) {
  if (!path) return null;
  if (path.startsWith("http")) return path;
  return `${BASE_URL}/storage/${path}`;
}

interface VendorProfile {
  id?: number;
  company_name: string;
  business_type?: string | null;
  slug?: string | null;
  contact_name?: string | null;
  contact_email?: string | null;
  contact_phone?: string | null;
  country?: string | null;
  city?: string | null;
  address_line_1?: string | null;
  status: string;
  is_verified_badge?: boolean;
  logo_path?: string | null;
  banner_path?: string | null;
  followers_count?: number;
  total_products?: number;
  avg_rating?: number;
}

export default function ProfileScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["vendor-profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/profile");
      return data?.data as { user: any; vendor: VendorProfile | null };
    },
  });

  const vendor = data?.vendor;
  const user = data?.user;

  // Form state
  const [companyName, setCompanyName] = useState("");
  const [businessType, setBusinessType] = useState("");
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [country, setCountry] = useState("");
  const [city, setCity] = useState("");
  const [addressLine1, setAddressLine1] = useState("");
  const [logoUri, setLogoUri] = useState<string | null>(null);
  const [bannerUri, setBannerUri] = useState<string | null>(null);

  useEffect(() => {
    if (vendor) {
      setCompanyName(vendor.company_name ?? "");
      setBusinessType(vendor.business_type ?? "");
      setContactName(vendor.contact_name ?? "");
      setContactEmail(vendor.contact_email ?? user?.email ?? "");
      setContactPhone(vendor.contact_phone ?? user?.phone ?? "");
      setCountry(vendor.country ?? "");
      setCity(vendor.city ?? "");
      setAddressLine1(vendor.address_line_1 ?? "");
    } else if (user) {
      setContactName(user.name ?? "");
      setContactEmail(user.email ?? "");
      setContactPhone(user.phone ?? "");
    }
  }, [vendor, user]);

  const saveMutation = useMutation({
    mutationFn: async () => {
      const formData = new FormData();
      formData.append("company_name", companyName);
      if (businessType) formData.append("business_type", businessType);
      if (contactName) formData.append("contact_name", contactName);
      // Only send contact_email if it looks like a valid email (not a phone number)
      if (contactEmail && contactEmail.includes("@")) {
        formData.append("contact_email", contactEmail);
      }
      if (contactPhone) formData.append("contact_phone", contactPhone);
      if (country) formData.append("country", country);
      if (city) formData.append("city", city);
      if (addressLine1) formData.append("address_line_1", addressLine1);

      // Helper: convert image URI to a proper file for both web and native
      const appendFile = async (key: string, uri: string, fallbackName: string) => {
        if (Platform.OS === "web") {
          // On web, fetch the blob from the data URI / object URL
          const resp = await fetch(uri);
          const blob = await resp.blob();
          const ext = blob.type.split("/")[1] || "jpg";
          formData.append(key, blob, `${fallbackName}.${ext}`);
        } else {
          // On native, React Native accepts { uri, name, type }
          const filename = uri.split("/").pop() ?? `${fallbackName}.jpg`;
          formData.append(key, { uri, name: filename, type: "image/jpeg" } as any);
        }
      };

      if (logoUri) await appendFile("logo_path", logoUri, "logo");
      if (bannerUri) await appendFile("banner_path", bannerUri, "banner");

      const { data } = await apiClient.post("/vendor/profile", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: (data) => {
      toast.success(data?.message || "Profile saved successfully");
      queryClient.invalidateQueries({ queryKey: ["vendor-profile"] });
      setLogoUri(null);
      setBannerUri(null);
    },
    onError: (err: any) => {
      const msg = err?.response?.data?.message || "Failed to save profile";
      const errors = err?.response?.data?.errors;
      if (errors) {
        // Show the first specific validation error
        const firstError = Object.values(errors).flat()[0];
        toast.error(String(firstError) || msg);
      } else {
        toast.error(msg);
      }
    },
  });

  const pickImage = async (type: "logo" | "banner") => {
    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ["images"],
      quality: 0.8,
      allowsEditing: true,
      aspect: type === "logo" ? [1, 1] : [4, 1],
    });
    if (!result.canceled && result.assets[0]) {
      if (type === "logo") setLogoUri(result.assets[0].uri);
      else setBannerUri(result.assets[0].uri);
    }
  };

  const existingLogoUrl = getImageUrl(vendor?.logo_path);
  const existingBannerUrl = getImageUrl(vendor?.banner_path);

  const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
    approved: { bg: "#D1FAE5", text: "#065F46" },
    pending: { bg: "#FEF3C7", text: "#92400E" },
    rejected: { bg: "#FEE2E2", text: "#991B1B" },
    suspended: { bg: "#FEE2E2", text: "#991B1B" },
  };

  const fields: { label: string; value: string; onChange: (t: string) => void; placeholder: string; keyboard?: "default" | "email-address" | "phone-pad" }[] = [
    { label: "Company Name *", value: companyName, onChange: setCompanyName, placeholder: "Your company name" },
    { label: "Business Type", value: businessType, onChange: setBusinessType, placeholder: "Manufacturer, Wholesaler..." },
    { label: "Contact Person", value: contactName, onChange: setContactName, placeholder: "Full name" },
    { label: "Contact Email", value: contactEmail, onChange: setContactEmail, placeholder: "email@example.com (optional)", keyboard: "email-address" },
    { label: "Contact Phone", value: contactPhone, onChange: setContactPhone, placeholder: "01XXXXXXXXX", keyboard: "phone-pad" },
    { label: "Country", value: country, onChange: setCountry, placeholder: "Bangladesh" },
    { label: "City", value: city, onChange: setCity, placeholder: "Dhaka" },
    { label: "Address", value: addressLine1, onChange: setAddressLine1, placeholder: "Full address" },
  ];

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
            <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
          </TouchableOpacity>
          <Text style={styles.headerTitle}>Shop Profile</Text>
          <View style={{ width: 32 }} />
        </View>
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      </View>
    );
  }

  const sc = STATUS_COLORS[vendor?.status ?? "pending"] ?? STATUS_COLORS.pending;

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Shop Profile</Text>
        <View style={{ width: 32 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* ── Vendor Hero Card ── */}
        {vendor && (
          <View style={styles.heroCard}>
            {/* Gradient Background */}
            <View style={styles.heroGradient}>
              {/* Decorative circles */}
              <View style={styles.heroCircle1} />
              <View style={styles.heroCircle2} />

              <View style={styles.heroContent}>
                {/* Avatar */}
                <View style={styles.heroAvatarWrap}>
                  {existingLogoUrl ? (
                    <Image source={{ uri: existingLogoUrl }} style={styles.heroAvatarImg} />
                  ) : (
                    <Text style={styles.heroAvatarText}>
                      {(vendor.company_name || "S").charAt(0).toUpperCase()}
                    </Text>
                  )}
                </View>

                {/* Info */}
                <View style={styles.heroInfo}>
                  {/* Badges row */}
                  <View style={styles.heroBadgesRow}>
                    {vendor.id != null && (
                      <View style={styles.heroBadgeSid}>
                        <Text style={styles.heroBadgeSidText}>
                          SID-{String(vendor.id).padStart(5, "0")}
                        </Text>
                      </View>
                    )}
                    <View style={[styles.heroBadgeStatus, {
                      backgroundColor: vendor.status === "approved" ? "rgba(52,211,153,0.2)"
                        : vendor.status === "rejected" ? "rgba(248,113,113,0.2)"
                        : "rgba(251,191,36,0.2)",
                    }]}>
                      <Text style={[styles.heroBadgeStatusText, {
                        color: vendor.status === "approved" ? "#6EE7B7"
                          : vendor.status === "rejected" ? "#FCA5A5"
                          : "#FCD34D",
                      }]}>
                        {(vendor.status ?? "pending").toUpperCase()}
                      </Text>
                    </View>
                    {vendor.is_verified_badge && (
                      <View style={styles.heroBadgeVerified}>
                        <Text style={styles.heroBadgeVerifiedText}>✓ Verified</Text>
                      </View>
                    )}
                  </View>

                  <Text style={styles.heroCompanyName} numberOfLines={1}>
                    {vendor.company_name || "Your Business"}
                  </Text>
                  {vendor.business_type ? (
                    <Text style={styles.heroBusinessType}>{vendor.business_type}</Text>
                  ) : null}
                  {vendor.slug ? (
                    <Text style={styles.heroSlug}>selfshop.com/supplier/{vendor.slug}</Text>
                  ) : null}
                </View>
              </View>

              {/* View Public Profile button */}
              {vendor.slug && (
                <TouchableOpacity
                  style={styles.heroViewBtn}
                  onPress={() => {
                    const url = `https://selfshop.com/supplier/${vendor.slug}`;
                    Linking.openURL(url).catch(() => {});
                  }}
                  activeOpacity={0.7}
                >
                  <Ionicons name="open-outline" size={14} color="#fff" />
                  <Text style={styles.heroViewBtnText}>View Public Profile</Text>
                </TouchableOpacity>
              )}
            </View>

            {/* Stats Bar */}
            <View style={styles.heroStatsBar}>
              <View style={styles.heroStatItem}>
                <View style={[styles.heroStatIcon, { backgroundColor: "#FDF2F8" }]}> 
                  <Ionicons name="people" size={18} color="#E5005F" />
                </View>
                <View>
                  <Text style={styles.heroStatValue}>{vendor.followers_count ?? 0}</Text>
                  <Text style={styles.heroStatLabel}>Followers</Text>
                </View>
              </View>
              <View style={styles.heroStatItem}>
                <View style={[styles.heroStatIcon, { backgroundColor: "#EEF2FF" }]}> 
                  <Ionicons name="cube" size={18} color="#4a45a0" />
                </View>
                <View>
                  <Text style={styles.heroStatValue}>{vendor.total_products ?? 0}</Text>
                  <Text style={styles.heroStatLabel}>Products</Text>
                </View>
              </View>
              <View style={styles.heroStatItem}>
                <View style={[styles.heroStatIcon, { backgroundColor: "#FFFBEB" }]}> 
                  <Ionicons name="star" size={18} color="#F59E0B" />
                </View>
                <View>
                  <Text style={styles.heroStatValue}>{vendor.avg_rating ?? 0}</Text>
                  <Text style={styles.heroStatLabel}>Avg Rating</Text>
                </View>
              </View>
            </View>
          </View>
        )}

        {/* ── Branding Section ── */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Shop Branding</Text>

          {/* Banner */}
          <Text style={styles.fieldLabel}>Cover Banner</Text>
          <TouchableOpacity style={styles.bannerWrap} onPress={() => pickImage("banner")} activeOpacity={0.7}>
            {bannerUri ? (
              <Image source={{ uri: bannerUri }} style={styles.bannerImage} />
            ) : existingBannerUrl ? (
              <Image source={{ uri: existingBannerUrl }} style={styles.bannerImage} />
            ) : (
              <View style={styles.bannerPlaceholder}>
                <Ionicons name="image-outline" size={28} color="#9ca3af" />
                <Text style={{ fontSize: 12, color: "#9ca3af", marginTop: 4 }}>Tap to upload banner</Text>
              </View>
            )}
            <View style={styles.editBadge}>
              <Ionicons name="camera" size={14} color="#fff" />
            </View>
          </TouchableOpacity>

          {/* Logo */}
          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Shop Logo</Text>
          <TouchableOpacity style={styles.logoWrap} onPress={() => pickImage("logo")} activeOpacity={0.7}>
            {logoUri ? (
              <Image source={{ uri: logoUri }} style={styles.logoImage} />
            ) : existingLogoUrl ? (
              <Image source={{ uri: existingLogoUrl }} style={styles.logoImage} />
            ) : (
              <View style={styles.logoPlaceholder}>
                <Ionicons name="storefront-outline" size={28} color="#9ca3af" />
              </View>
            )}
            <View style={[styles.editBadge, { bottom: 0, right: 0 }]}>
              <Ionicons name="camera" size={12} color="#fff" />
            </View>
          </TouchableOpacity>
        </View>

        {/* ── Business Info ── */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionTitle}>Business Information</Text>
          {fields.map((f) => (
            <View key={f.label} style={styles.inputGroup}>
              <Text style={styles.fieldLabel}>{f.label}</Text>
              <TextInput
                style={styles.input}
                value={f.value}
                onChangeText={f.onChange}
                placeholder={f.placeholder}
                placeholderTextColor="#9ca3af"
                keyboardType={f.keyboard ?? "default"}
                autoCapitalize={f.keyboard === "email-address" ? "none" : "words"}
              />
            </View>
          ))}
        </View>

        {/* Save button */}
        <TouchableOpacity
          style={[styles.saveBtn, saveMutation.isPending && { opacity: 0.6 }]}
          onPress={() => saveMutation.mutate()}
          disabled={saveMutation.isPending}
          activeOpacity={0.8}
        >
          {saveMutation.isPending ? (
            <ActivityIndicator color="#fff" size="small" />
          ) : (
            <Text style={styles.saveBtnText}>Save Profile</Text>
          )}
        </TouchableOpacity>

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },

  // ── Hero Card ──
  heroCard: { borderRadius: 16, overflow: "hidden", marginBottom: 16, backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb" },
  heroGradient: {
    backgroundColor: BRAND.primary,
    paddingHorizontal: 20, paddingTop: 24, paddingBottom: 20,
    position: "relative", overflow: "hidden",
  },
  heroCircle1: {
    position: "absolute", top: -30, right: -20,
    width: 100, height: 100, borderRadius: 50,
    backgroundColor: "rgba(255,255,255,0.05)",
  },
  heroCircle2: {
    position: "absolute", bottom: -20, left: 40,
    width: 60, height: 60, borderRadius: 30,
    backgroundColor: "rgba(255,255,255,0.05)",
  },
  heroContent: { flexDirection: "row", alignItems: "center", gap: 14 },
  heroAvatarWrap: {
    width: 64, height: 64, borderRadius: 16,
    backgroundColor: "rgba(255,255,255,0.1)",
    borderWidth: 2, borderColor: "rgba(255,255,255,0.2)",
    alignItems: "center", justifyContent: "center", overflow: "hidden",
  },
  heroAvatarImg: { width: "100%", height: "100%", resizeMode: "cover" },
  heroAvatarText: { fontSize: 26, fontWeight: "700", color: "rgba(255,255,255,0.6)" },
  heroInfo: { flex: 1 },
  heroBadgesRow: { flexDirection: "row", flexWrap: "wrap", gap: 5, marginBottom: 4 },
  heroBadgeSid: {
    backgroundColor: "rgba(255,255,255,0.1)",
    paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10,
  },
  heroBadgeSidText: { fontSize: 10, fontWeight: "600", color: "rgba(255,255,255,0.6)", letterSpacing: 1 },
  heroBadgeStatus: { paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10 },
  heroBadgeStatusText: { fontSize: 9, fontWeight: "800", letterSpacing: 1 },
  heroBadgeVerified: {
    backgroundColor: "rgba(56,189,248,0.2)",
    paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10,
  },
  heroBadgeVerifiedText: { fontSize: 9, fontWeight: "800", color: "#7DD3FC", letterSpacing: 1 },
  heroCompanyName: { fontSize: 20, fontWeight: "700", color: "#fff" },
  heroBusinessType: { fontSize: 13, color: "rgba(255,255,255,0.5)", marginTop: 1 },
  heroSlug: { fontSize: 11, color: "rgba(255,255,255,0.35)", marginTop: 3, fontFamily: Platform.OS === "ios" ? "Menlo" : "monospace" },
  heroViewBtn: {
    flexDirection: "row", alignItems: "center", gap: 6,
    alignSelf: "flex-start", marginTop: 14,
    backgroundColor: "rgba(255,255,255,0.1)",
    borderWidth: 1, borderColor: "rgba(255,255,255,0.2)",
    borderRadius: 10, paddingHorizontal: 14, paddingVertical: 8,
  },
  heroViewBtnText: { fontSize: 13, fontWeight: "500", color: "#fff" },
  // Stats bar
  heroStatsBar: {
    flexDirection: "row", justifyContent: "space-around", alignItems: "center",
    paddingVertical: 14, paddingHorizontal: 12,
  },
  heroStatItem: { flexDirection: "row", alignItems: "center", gap: 10 },
  heroStatIcon: {
    width: 36, height: 36, borderRadius: 10,
    alignItems: "center", justifyContent: "center",
  },
  heroStatValue: { fontSize: 16, fontWeight: "700", color: "#1a1a2e" },
  heroStatLabel: { fontSize: 10, color: "#6b7280" },

  // ── Sections ──
  sectionCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#f3f4f6",
  },
  sectionTitle: { fontSize: 15, fontWeight: "600", color: "#1a1a2e", marginBottom: 14 },
  fieldLabel: { fontSize: 12, fontWeight: "600", color: "#374151", marginBottom: 6, marginLeft: 2 },
  bannerWrap: {
    width: "100%",
    height: 100,
    borderRadius: 12,
    overflow: "hidden",
    backgroundColor: "#f3f4f6",
    position: "relative",
  },
  bannerImage: { width: "100%", height: "100%", resizeMode: "cover" },
  bannerPlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center" },
  logoWrap: {
    width: 80,
    height: 80,
    borderRadius: 40,
    overflow: "hidden",
    backgroundColor: "#f3f4f6",
    position: "relative",
    borderWidth: 2,
    borderColor: "#e5e7eb",
  },
  logoImage: { width: "100%", height: "100%", resizeMode: "cover" },
  logoPlaceholder: { width: "100%", height: "100%", alignItems: "center", justifyContent: "center" },
  editBadge: {
    position: "absolute",
    bottom: 4,
    right: 4,
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
  },
  inputGroup: { marginBottom: 14 },
  input: {
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 11,
    fontSize: 14,
    color: "#1a1a2e",
  },
  saveBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 12,
    paddingVertical: 15,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 4,
  },
  saveBtnText: { fontSize: 15, fontWeight: "600", color: "#fff" },
});
