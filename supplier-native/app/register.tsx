import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  TextInput,
  TouchableOpacity,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  ActivityIndicator,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import { register } from "@/lib/auth-client";
import { queryClient } from "@/lib/query-client";

export default function RegisterScreen() {
  const insets = useSafeAreaInsets();
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [companyName, setCompanyName] = useState("");
  const [businessType, setBusinessType] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleRegister = async () => {
    if (!name.trim() || !phone.trim() || !password.trim() || !companyName.trim()) {
      toast.error("Please fill in all required fields");
      return;
    }

    if (password.length < 6) {
      toast.error("Password must be at least 6 characters");
      return;
    }

    setIsSubmitting(true);
    try {
      await register({
        name: name.trim(),
        phone: phone.trim(),
        password,
        company_name: companyName.trim(),
        business_type: businessType.trim() || undefined,
      });
      queryClient.clear();
      toast.success("Registration successful! Welcome aboard.");
      router.replace("/(tabs)");
    } catch (err: any) {
      const message =
        err?.response?.data?.message ??
        err?.response?.data?.errors
          ? Object.values(err.response.data.errors).flat().join(", ")
          : err?.message ?? "Registration failed";
      toast.error(message);
    } finally {
      setIsSubmitting(false);
    }
  };

  const fields: {
    label: string;
    value: string;
    onChange: (t: string) => void;
    icon: keyof typeof Ionicons.glyphMap;
    placeholder: string;
    required?: boolean;
    keyboard?: "default" | "phone-pad" | "email-address";
    secure?: boolean;
  }[] = [
    { label: "Full Name *", value: name, onChange: setName, icon: "person-outline", placeholder: "Your name", required: true },
    { label: "Phone *", value: phone, onChange: setPhone, icon: "call-outline", placeholder: "01XXXXXXXXX", required: true, keyboard: "phone-pad" },
    { label: "Company Name *", value: companyName, onChange: setCompanyName, icon: "storefront-outline", placeholder: "Your company or shop name", required: true },
    { label: "Business Type", value: businessType, onChange: setBusinessType, icon: "briefcase-outline", placeholder: "e.g. Manufacturer, Wholesaler" },
    { label: "Password *", value: password, onChange: setPassword, icon: "lock-closed-outline", placeholder: "Min 6 characters", required: true, secure: true },
  ];

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === "ios" ? "padding" : "height"}
    >
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingTop: insets.top + 20 }]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {/* Close button */}
        <TouchableOpacity
          style={styles.closeBtn}
          onPress={() => router.back()}
        >
          <Ionicons name="close" size={24} color="#6b7280" />
        </TouchableOpacity>

        {/* Branding */}
        <View style={styles.brandingSection}>
          <View style={styles.logoWrap}>
            <Ionicons name="storefront" size={36} color="#fff" />
          </View>
          <Text style={styles.title}>Become a Supplier</Text>
          <Text style={styles.subtitle}>Create your vendor account to start selling</Text>
        </View>

        {/* Form */}
        <View style={styles.form}>
          {fields.map((field) => (
            <View key={field.label} style={styles.inputGroup}>
              <Text style={styles.label}>{field.label}</Text>
              <View style={styles.inputWrap}>
                <Ionicons name={field.icon} size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder={field.placeholder}
                  placeholderTextColor="#9ca3af"
                  value={field.value}
                  onChangeText={field.onChange}
                  keyboardType={field.keyboard ?? "default"}
                  autoCapitalize={field.secure ? "none" : "words"}
                  autoCorrect={false}
                  secureTextEntry={field.secure && !showPassword}
                />
                {field.secure && (
                  <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                    <Ionicons
                      name={showPassword ? "eye-off-outline" : "eye-outline"}
                      size={20}
                      color="#9ca3af"
                    />
                  </TouchableOpacity>
                )}
              </View>
            </View>
          ))}

          <TouchableOpacity
            style={[styles.registerBtn, isSubmitting && styles.registerBtnDisabled]}
            onPress={handleRegister}
            disabled={isSubmitting}
            activeOpacity={0.8}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <Text style={styles.registerBtnText}>Create Account</Text>
            )}
          </TouchableOpacity>
        </View>

        {/* Login link */}
        <View style={styles.loginSection}>
          <Text style={styles.loginText}>Already have an account?</Text>
          <TouchableOpacity onPress={() => router.replace("/login")}>
            <Text style={styles.loginLink}>Sign In</Text>
          </TouchableOpacity>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  scrollContent: { flexGrow: 1, paddingHorizontal: 24, paddingBottom: 40 },
  closeBtn: {
    alignSelf: "flex-end",
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#f3f4f6",
    alignItems: "center",
    justifyContent: "center",
  },
  brandingSection: { alignItems: "center", marginTop: 12, marginBottom: 28 },
  logoWrap: {
    width: 72,
    height: 72,
    borderRadius: 20,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 16,
  },
  title: { fontSize: 24, fontWeight: "700", color: "#1a1a2e" },
  subtitle: { fontSize: 14, color: "#6b7280", marginTop: 4, textAlign: "center" },
  form: { gap: 16 },
  inputGroup: { gap: 6 },
  label: { fontSize: 13, fontWeight: "600", color: "#374151", marginLeft: 2 },
  inputWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    gap: 10,
  },
  input: { flex: 1, fontSize: 15, color: "#1a1a2e" },
  registerBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 12,
    paddingVertical: 15,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 8,
  },
  registerBtnDisabled: { opacity: 0.6 },
  registerBtnText: { fontSize: 16, fontWeight: "600", color: "#fff" },
  loginSection: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 6,
    marginTop: 24,
  },
  loginText: { fontSize: 14, color: "#6b7280" },
  loginLink: { fontSize: 14, fontWeight: "600", color: BRAND.primary },
});
