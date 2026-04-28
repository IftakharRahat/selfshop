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
import { login, useSession } from "@/lib/auth-client";
import { queryClient } from "@/lib/query-client";

export default function LoginScreen() {
  const insets = useSafeAreaInsets();
  const { signIn } = useSession();
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleLogin = async () => {
    if (!phone.trim() || !password.trim()) {
      toast.error("Please enter phone and password");
      return;
    }

    setIsSubmitting(true);
    try {
      const session = await login(phone.trim(), password);
      queryClient.clear();
      signIn(session);
      toast.success("Welcome back!");
    } catch (err: any) {
      const status = err?.response?.status;
      const message = err?.response?.data?.message ?? err?.message ?? "Login failed";
      if (status === 401) {
        toast.error("Invalid credentials. Please check your phone/email and password.");
      } else {
        toast.error(message);
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior="padding"
    >
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingTop: insets.top + 20, paddingBottom: 40 }]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >


        {/* Logo / Branding */}
        <View style={styles.brandingSection}>
          <View style={styles.logoWrap}>
            <Ionicons name="storefront" size={36} color="#fff" />
          </View>
          <Text style={styles.title}>SelfShop Supplier</Text>
          <Text style={styles.subtitle}>Sign in to manage your store</Text>
        </View>

        {/* Form */}
        <View style={styles.form}>
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Phone / Email</Text>
            <View style={styles.inputWrap}>
              <Ionicons name="call-outline" size={18} color="#9ca3af" />
              <TextInput
                style={styles.input}
                placeholder="Enter phone or email"
                placeholderTextColor="#9ca3af"
                value={phone}
                onChangeText={setPhone}
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
              />
            </View>
          </View>

          <View style={styles.inputGroup}>
            <Text style={styles.label}>Password</Text>
            <View style={styles.inputWrap}>
              <Ionicons name="lock-closed-outline" size={18} color="#9ca3af" />
              <TextInput
                style={styles.input}
                placeholder="Enter password"
                placeholderTextColor="#9ca3af"
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPassword}
              />
              <TouchableOpacity onPress={() => setShowPassword(!showPassword)}>
                <Ionicons
                  name={showPassword ? "eye-off-outline" : "eye-outline"}
                  size={20}
                  color="#9ca3af"
                />
              </TouchableOpacity>
            </View>
          </View>

          <TouchableOpacity
            style={[styles.loginBtn, isSubmitting && styles.loginBtnDisabled]}
            onPress={handleLogin}
            disabled={isSubmitting}
            activeOpacity={0.8}
          >
            {isSubmitting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <Text style={styles.loginBtnText}>Sign In</Text>
            )}
          </TouchableOpacity>
        </View>

        {/* Register link */}
        <View style={styles.registerSection}>
          <Text style={styles.registerText}>Don't have a supplier account?</Text>
          <TouchableOpacity onPress={() => router.replace("/register")}>
            <Text style={styles.registerLink}>Register Now</Text>
          </TouchableOpacity>
        </View>
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
  brandingSection: { alignItems: "center", marginTop: 20, marginBottom: 36 },
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
  subtitle: { fontSize: 14, color: "#6b7280", marginTop: 4 },
  form: { gap: 18 },
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
  loginBtn: {
    backgroundColor: BRAND.primary,
    borderRadius: 12,
    paddingVertical: 15,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 8,
  },
  loginBtnDisabled: { opacity: 0.6 },
  loginBtnText: { fontSize: 16, fontWeight: "600", color: "#fff" },
  registerSection: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 6,
    marginTop: 28,
  },
  registerText: { fontSize: 14, color: "#6b7280" },
  registerLink: { fontSize: 14, fontWeight: "600", color: BRAND.primary },
});
