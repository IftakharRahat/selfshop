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
  company_name: string;
  business_type?: string | null;
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
      if (contactEmail) formData.append("contact_email", contactEmail);
      if (contactPhone) formData.append("contact_phone", contactPhone);
      if (country) formData.append("country", country);
      if (city) formData.append("city", city);
      if (addressLine1) formData.append("address_line_1", addressLine1);

      if (logoUri) {
        const filename = logoUri.split("/").pop() ?? "logo.jpg";
        formData.append("logo_path", { uri: logoUri, name: filename, type: "image/jpeg" } as any);
      }
      if (bannerUri) {
        const filename = bannerUri.split("/").pop() ?? "banner.jpg";
        formData.append("banner_path", { uri: bannerUri, name: filename, type: "image/jpeg" } as any);
      }

      const { data } = await apiClient.post("/vendor/profile", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      return data;
    },
    onSuccess: () => {
      toast.success("Profile saved successfully");
      queryClient.invalidateQueries({ queryKey: ["vendor-profile"] });
      setLogoUri(null);
      setBannerUri(null);
    },
    onError: () => toast.error("Failed to save profile"),
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
    { label: "Contact Email", value: contactEmail, onChange: setContactEmail, placeholder: "email@example.com", keyboard: "email-address" },
    { label: "Contact Phone", value: contactPhone, onChange: setContactPhone, placeholder: "01XXXXXXXXX", keyboard: "phone-pad" },
    { label: "Country", value: country, onChange: setCountry, placeholder: "Bangladesh" },
    { label: "City", value: city, onChange: setCity, placeholder: "Dhaka" },
    { label: "Address", value: addressLine1, onChange: setAddressLine1, placeholder: "Full address" },
  ];

  if (isLoading) {
    return (
      <View style={[styles.container, { paddingTop: insets.top }]}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
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
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Shop Profile</Text>
        <View style={{ width: 32 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Status Badge */}
        <View style={styles.statusRow}>
          <View style={[styles.statusBadge, { backgroundColor: sc.bg }]}>
            <Text style={[styles.statusText, { color: sc.text }]}>
              {(vendor?.status ?? "pending").charAt(0).toUpperCase() + (vendor?.status ?? "pending").slice(1)}
            </Text>
          </View>
          {vendor?.is_verified_badge && (
            <View style={[styles.statusBadge, { backgroundColor: "#DBEAFE" }]}>
              <Ionicons name="checkmark-circle" size={12} color="#1D4ED8" />
              <Text style={[styles.statusText, { color: "#1D4ED8" }]}>Verified</Text>
            </View>
          )}
        </View>

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
  statusRow: { flexDirection: "row", gap: 8, marginBottom: 16 },
  statusBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: { fontSize: 12, fontWeight: "600" },
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
