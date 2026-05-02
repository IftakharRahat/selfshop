import React, { useState, useEffect, useRef } from "react";
import {
  View,
  Text,
  Image,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  ScrollView,
  ActivityIndicator,
  FlatList,
  Animated,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import { register, useSession } from "@/lib/auth-client";
import { queryClient } from "@/lib/query-client";
import apiClient from "@/lib/api-client";
import DraggableBottomSheet from "@/components/DraggableBottomSheet";

const BUSINESS_TYPES = ["Manufacturer", "Wholesaler", "Distributor", "Importer", "Exporter", "Service"];

export default function RegisterScreen() {
  const insets = useSafeAreaInsets();
  const { signIn } = useSession();
  const [step, setStep] = useState(1);
  const fadeAnim = useRef(new Animated.Value(1)).current;

  // Step 1 fields
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [companyName, setCompanyName] = useState("");
  const [showPassword, setShowPassword] = useState(false);

  // Step 2 fields
  const [businessType, setBusinessType] = useState("");
  const [pickupAddress, setPickupAddress] = useState("");
  const [selectedCityId, setSelectedCityId] = useState<number | null>(null);
  const [selectedCityName, setSelectedCityName] = useState("");
  const [selectedZoneId, setSelectedZoneId] = useState<number | null>(null);
  const [selectedZoneName, setSelectedZoneName] = useState("");
  const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null);
  const [selectedAreaName, setSelectedAreaName] = useState("");
  const [dropdownType, setDropdownType] = useState<"city" | "zone" | "area" | null>(null);

  const [isSubmitting, setIsSubmitting] = useState(false);

  // CarryBee API
  const { data: citiesData, isLoading: citiesLoading } = useQuery({
    queryKey: ["carrybee-cities"],
    queryFn: async () => { const { data } = await apiClient.get("/carrybee/cities"); return data?.data?.cities as { id: number; name: string }[]; },
  });
  const { data: zonesData, isLoading: zonesLoading } = useQuery({
    queryKey: ["carrybee-zones", selectedCityId],
    queryFn: async () => { const { data } = await apiClient.get(`/carrybee/cities/${selectedCityId}/zones`); return data?.data?.zones as { id: number; name: string }[]; },
    enabled: !!selectedCityId,
  });
  const { data: areasData, isLoading: areasLoading } = useQuery({
    queryKey: ["carrybee-areas", selectedCityId, selectedZoneId],
    queryFn: async () => { const { data } = await apiClient.get(`/carrybee/cities/${selectedCityId}/zones/${selectedZoneId}/areas`); return data?.data?.areas as { id: number; name: string }[]; },
    enabled: !!selectedCityId && !!selectedZoneId,
  });

  useEffect(() => { setSelectedZoneId(null); setSelectedZoneName(""); setSelectedAreaId(null); setSelectedAreaName(""); }, [selectedCityId]);
  useEffect(() => { setSelectedAreaId(null); setSelectedAreaName(""); }, [selectedZoneId]);

  const cities = citiesData ?? [];
  const zones = zonesData ?? [];
  const areas = areasData ?? [];

  // Animated step transition
  const animateToStep = (newStep: number) => {
    Animated.timing(fadeAnim, { toValue: 0, duration: 120, useNativeDriver: true }).start(() => {
      setStep(newStep);
      Animated.timing(fadeAnim, { toValue: 1, duration: 200, useNativeDriver: true }).start();
    });
  };

  const goNext = () => {
    if (!name.trim() || !phone.trim() || !password.trim() || !companyName.trim()) {
      toast.error("Please fill in all required fields");
      return;
    }
    if (password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }
    animateToStep(2);
  };

  const goBack = () => animateToStep(1);

  const handleRegister = async () => {
    setIsSubmitting(true);
    try {
      const session = await register({
        name: name.trim(), phone: phone.trim(), password,
        company_name: companyName.trim(),
        business_type: businessType || undefined,
        pickup_city_id: selectedCityId ?? undefined,
        pickup_zone_id: selectedZoneId ?? undefined,
        pickup_area_id: selectedAreaId ?? undefined,
        pickup_address: pickupAddress.trim() || undefined,
      });
      queryClient.clear();
      signIn(session);
      toast.success("Registration successful! Welcome aboard.");
    } catch (err: any) {
      const message =
        err?.response?.data?.message ??
        (err?.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(", ")
          : err?.message ?? "Registration failed");
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  // Dropdown helpers
  const getDropdownItems = () => dropdownType === "city" ? cities : dropdownType === "zone" ? zones : dropdownType === "area" ? areas : [];
  const getDropdownTitle = () => dropdownType === "city" ? "Select City" : dropdownType === "zone" ? "Select Zone" : dropdownType === "area" ? "Select Area" : "";
  const isDropdownLoading = () => dropdownType === "city" ? citiesLoading : dropdownType === "zone" ? zonesLoading : dropdownType === "area" ? areasLoading : false;
  const handleDropdownSelect = (item: { id: number; name: string }) => {
    if (dropdownType === "city") { setSelectedCityId(item.id); setSelectedCityName(item.name); }
    else if (dropdownType === "zone") { setSelectedZoneId(item.id); setSelectedZoneName(item.name); }
    else if (dropdownType === "area") { setSelectedAreaId(item.id); setSelectedAreaName(item.name); }
    setDropdownType(null);
  };

  return (
    <KeyboardAvoidingView style={styles.container} behavior="padding">
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingTop: insets.top + 16, paddingBottom: 40 }]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {/* Header */}
        <View style={styles.topRow}>
          <TouchableOpacity style={styles.backBtn} onPress={step === 1 ? () => router.replace("/login") : goBack}>
            <Ionicons name="arrow-back" size={22} color="#6b7280" />
          </TouchableOpacity>
          <View style={styles.stepIndicator}>
            <View style={[styles.stepDot, step >= 1 && styles.stepDotActive]} />
            <View style={[styles.stepBar, step >= 2 && styles.stepBarActive]} />
            <View style={[styles.stepDot, step >= 2 && styles.stepDotActive]} />
          </View>
          <Text style={styles.stepLabel}>Step {step} of 2</Text>
        </View>

        {/* Branding */}
        <View style={styles.brandingSection}>
          <View style={styles.logoWrap}>
            <Image
              source={require("@/assets/images/supplier_app_logo.png")}
              style={styles.logoImage}
              resizeMode="contain"
            />
          </View>
          <Text style={styles.title}>{step === 1 ? "Create your account" : "Business details"}</Text>
          <Text style={styles.subtitle}>
            {step === 1 ? "Let's start with your basic information" : "Tell us about your business and pickup location"}
          </Text>
        </View>

        {/* Animated content */}
        <Animated.View style={[styles.form, { opacity: fadeAnim }]}>
          {step === 1 ? (
            <>
              {/* Name */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Full Name *</Text>
                <View style={styles.inputWrap}>
                  <Ionicons name="person-outline" size={18} color="#9ca3af" />
                  <TextInput style={styles.input} placeholder="Your name" placeholderTextColor="#9ca3af" value={name} onChangeText={setName} autoCapitalize="words" />
                </View>
              </View>
              {/* Phone */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Phone *</Text>
                <View style={styles.inputWrap}>
                  <Ionicons name="call-outline" size={18} color="#9ca3af" />
                  <TextInput style={styles.input} placeholder="01XXXXXXXXX" placeholderTextColor="#9ca3af" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
                </View>
              </View>
              {/* Company */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Company Name *</Text>
                <View style={styles.inputWrap}>
                  <Ionicons name="storefront-outline" size={18} color="#9ca3af" />
                  <TextInput style={styles.input} placeholder="Your company or shop name" placeholderTextColor="#9ca3af" value={companyName} onChangeText={setCompanyName} />
                </View>
              </View>
              {/* Password */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Password *</Text>
                <View style={styles.inputWrap}>
                  <Ionicons name="lock-closed-outline" size={18} color="#9ca3af" />
                  <TextInput style={styles.input} placeholder="Min 6 characters" placeholderTextColor="#9ca3af" value={password} onChangeText={setPassword} secureTextEntry={!showPassword} autoCapitalize="none" />
                  <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                    <Ionicons name={showPassword ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                  </TouchableOpacity>
                </View>
              </View>

              <TouchableOpacity style={styles.primaryBtn} onPress={goNext} activeOpacity={0.8}>
                <Text style={styles.primaryBtnText}>Continue</Text>
                <Ionicons name="arrow-forward" size={18} color="#fff" />
              </TouchableOpacity>

              <View style={styles.loginSection}>
                <Text style={styles.loginText}>Already have an account?</Text>
                <TouchableOpacity onPress={() => router.replace("/login")}>
                  <Text style={styles.loginLink}>Sign In</Text>
                </TouchableOpacity>
              </View>
            </>
          ) : (
            <>
              {/* Business Type */}
              <View style={styles.inputGroup}>
                <Text style={styles.label}>Business Type</Text>
                <Text style={styles.helperText}>What type of business do you run?</Text>
                <View style={styles.chipRow}>
                  {BUSINESS_TYPES.map((bt) => (
                    <TouchableOpacity key={bt} style={[styles.btChip, businessType === bt && styles.btChipActive]} onPress={() => setBusinessType(businessType === bt ? "" : bt)}>
                      <Text style={[styles.btChipText, businessType === bt && styles.btChipTextActive]}>{bt}</Text>
                    </TouchableOpacity>
                  ))}
                </View>
              </View>

              {/* Pickup Point */}
              <View style={styles.pickupSection}>
                <View style={styles.pickupHeader}>
                  <Ionicons name="location" size={16} color={BRAND.primary} />
                  <Text style={styles.pickupTitle}>Pickup Point</Text>
                </View>
                <Text style={styles.pickupSubtitle}>Select the nearest pickup location for courier pickups</Text>

                <TouchableOpacity style={styles.dropdownBtn} onPress={() => setDropdownType("city")}>
                  <Ionicons name="business-outline" size={16} color="#9ca3af" />
                  <Text style={[styles.dropdownText, selectedCityName ? styles.dropdownTextSelected : null]}>
                    {selectedCityName || (citiesLoading ? "Loading cities..." : "Select city")}
                  </Text>
                  <Ionicons name="chevron-down" size={16} color="#9ca3af" />
                </TouchableOpacity>

                <TouchableOpacity style={[styles.dropdownBtn, !selectedCityId && styles.dropdownDisabled]} onPress={() => selectedCityId && setDropdownType("zone")} disabled={!selectedCityId}>
                  <Ionicons name="map-outline" size={16} color="#9ca3af" />
                  <Text style={[styles.dropdownText, selectedZoneName ? styles.dropdownTextSelected : null]}>
                    {selectedZoneName || (!selectedCityId ? "Select city first" : zonesLoading ? "Loading..." : "Select zone")}
                  </Text>
                  <Ionicons name="chevron-down" size={16} color="#9ca3af" />
                </TouchableOpacity>

                <TouchableOpacity style={[styles.dropdownBtn, !selectedZoneId && styles.dropdownDisabled]} onPress={() => selectedZoneId && setDropdownType("area")} disabled={!selectedZoneId}>
                  <Ionicons name="navigate-outline" size={16} color="#9ca3af" />
                  <Text style={[styles.dropdownText, selectedAreaName ? styles.dropdownTextSelected : null]}>
                    {selectedAreaName || (!selectedZoneId ? "Select zone first" : areasLoading ? "Loading..." : "Select area")}
                  </Text>
                  <Ionicons name="chevron-down" size={16} color="#9ca3af" />
                </TouchableOpacity>

                <View style={styles.inputWrap}>
                  <Ionicons name="home-outline" size={16} color="#9ca3af" />
                  <TextInput style={styles.input} value={pickupAddress} onChangeText={setPickupAddress} placeholder="Full pickup address" placeholderTextColor="#9ca3af" />
                </View>
              </View>

              <TouchableOpacity
                style={[styles.primaryBtn, isSubmitting && { opacity: 0.6 }]}
                onPress={handleRegister}
                disabled={isSubmitting}
                activeOpacity={0.8}
              >
                {isSubmitting ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <>
                    <Text style={styles.primaryBtnText}>Create Account</Text>
                    <Ionicons name="checkmark-circle" size={18} color="#fff" />
                  </>
                )}
              </TouchableOpacity>



            </>
          )}
        </Animated.View>
      </ScrollView>

      {/* Dropdown Bottom Sheet */}
      <DraggableBottomSheet visible={dropdownType !== null} onClose={() => setDropdownType(null)} title={getDropdownTitle()} heightFraction={0.55}>
        {isDropdownLoading() ? (
          <ActivityIndicator size="large" color={BRAND.primary} style={{ paddingVertical: 40 }} />
        ) : getDropdownItems().length === 0 ? (
          <Text style={styles.emptyDropdown}>No options available</Text>
        ) : (
          <FlatList
            data={getDropdownItems()}
            keyExtractor={(item) => item.id.toString()}
            showsVerticalScrollIndicator={false}
            renderItem={({ item }) => {
              const isSelected =
                (dropdownType === "city" && selectedCityId === item.id) ||
                (dropdownType === "zone" && selectedZoneId === item.id) ||
                (dropdownType === "area" && selectedAreaId === item.id);
              return (
                <TouchableOpacity style={[styles.dropdownItem, isSelected && styles.dropdownItemSelected]} onPress={() => handleDropdownSelect(item)}>
                  <Text style={[styles.dropdownItemText, isSelected && styles.dropdownItemTextSelected]}>{item.name}</Text>
                  {isSelected && <Ionicons name="checkmark-circle" size={18} color={BRAND.primary} />}
                </TouchableOpacity>
              );
            }}
          />
        )}
      </DraggableBottomSheet>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  scrollContent: { flexGrow: 1, paddingHorizontal: 24, paddingBottom: 40 },
  // Header
  topRow: { flexDirection: "row", alignItems: "center", gap: 12, marginBottom: 8 },
  backBtn: { width: 38, height: 38, borderRadius: 19, backgroundColor: "#f3f4f6", alignItems: "center", justifyContent: "center" },
  stepIndicator: { flexDirection: "row", alignItems: "center", flex: 1, gap: 0 },
  stepDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: "#e5e7eb" },
  stepDotActive: { backgroundColor: BRAND.primary },
  stepBar: { flex: 1, height: 3, backgroundColor: "#e5e7eb", borderRadius: 1.5 },
  stepBarActive: { backgroundColor: BRAND.primary },
  stepLabel: { fontSize: 12, fontWeight: "500", color: "#9ca3af" },
  // Branding
  brandingSection: { alignItems: "center", marginTop: 12, marginBottom: 24 },
  logoWrap: { width: 72, height: 72, borderRadius: 18, overflow: "hidden", marginBottom: 14 },
  logoImage: { width: 72, height: 72 },
  title: { fontSize: 22, fontWeight: "700", color: "#1a1a2e" },
  subtitle: { fontSize: 13, color: "#6b7280", marginTop: 4, textAlign: "center" },
  // Form
  form: { gap: 16 },
  inputGroup: { gap: 6 },
  label: { fontSize: 13, fontWeight: "600", color: "#374151", marginLeft: 2 },
  helperText: { fontSize: 11, color: "#9ca3af", marginLeft: 2, marginTop: -2 },
  inputWrap: { flexDirection: "row", alignItems: "center", backgroundColor: "#f9fafb", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, gap: 10 },
  input: { flex: 1, fontSize: 15, color: "#1a1a2e" },
  // Buttons
  primaryBtn: { flexDirection: "row", backgroundColor: BRAND.primary, borderRadius: 12, paddingVertical: 15, alignItems: "center", justifyContent: "center", marginTop: 8, gap: 8 },
  primaryBtnText: { fontSize: 16, fontWeight: "600", color: "#fff" },
  skipBtn: { alignItems: "center", paddingVertical: 10 },
  skipBtnText: { fontSize: 14, fontWeight: "500", color: "#9ca3af" },
  loginSection: { flexDirection: "row", justifyContent: "center", alignItems: "center", gap: 6, marginTop: 20 },
  loginText: { fontSize: 14, color: "#6b7280" },
  loginLink: { fontSize: 14, fontWeight: "600", color: BRAND.primary },
  // Chips
  chipRow: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  btChip: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, backgroundColor: "#f3f4f6", borderWidth: 1, borderColor: "#e5e7eb" },
  btChipActive: { backgroundColor: BRAND.primary, borderColor: BRAND.primary },
  btChipText: { fontSize: 13, fontWeight: "500", color: "#6b7280" },
  btChipTextActive: { color: "#fff" },
  // Pickup
  pickupSection: { backgroundColor: "#f9fafb", borderRadius: 14, padding: 14, borderWidth: 1, borderColor: "#e5e7eb", gap: 10 },
  pickupHeader: { flexDirection: "row", alignItems: "center", gap: 6 },
  pickupTitle: { fontSize: 14, fontWeight: "600", color: "#1a1a2e" },
  pickupSubtitle: { fontSize: 11, color: "#9ca3af", marginTop: -4 },
  dropdownBtn: { flexDirection: "row", alignItems: "center", backgroundColor: "#fff", borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 10, paddingHorizontal: 12, paddingVertical: 12, gap: 8 },
  dropdownDisabled: { opacity: 0.5 },
  dropdownText: { flex: 1, fontSize: 13, color: "#9ca3af" },
  dropdownTextSelected: { color: "#1a1a2e", fontWeight: "500" },
  // Bottom sheet items
  emptyDropdown: { fontSize: 13, color: "#9ca3af", textAlign: "center", paddingVertical: 30 },
  dropdownItem: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", paddingVertical: 13, paddingHorizontal: 4, borderBottomWidth: 1, borderBottomColor: "#f3f4f6" },
  dropdownItemSelected: { backgroundColor: BRAND.primaryLight, borderRadius: 8, paddingHorizontal: 8 },
  dropdownItemText: { fontSize: 14, color: "#374151" },
  dropdownItemTextSelected: { fontWeight: "600", color: BRAND.primary },
});
