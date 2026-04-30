import React, { useState } from "react";
import {
  View, Text, StyleSheet, TextInput, TouchableOpacity,
  KeyboardAvoidingView, Platform, ScrollView, ActivityIndicator,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import axios from "axios";

const API_URL = process.env.EXPO_PUBLIC_API_URL ?? "";

export default function ForgotPasswordScreen() {
  const insets = useSafeAreaInsets();

  // Step 1: Send OTP
  const [phone, setPhone] = useState("");
  const [isSendingOtp, setIsSendingOtp] = useState(false);
  const [otpSent, setOtpSent] = useState(false);

  // Step 2: Verify OTP + Reset
  const [otp, setOtp] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showNewPw, setShowNewPw] = useState(false);
  const [showConfirmPw, setShowConfirmPw] = useState(false);
  const [isResetting, setIsResetting] = useState(false);

  const handleSendOtp = async () => {
    if (!phone.trim()) { toast.error("Please enter your phone number"); return; }
    setIsSendingOtp(true);
    try {
      const { data } = await axios.post(`${API_URL}/forgot-password`, { phone: phone.trim() });
      if (data.status) {
        toast.success(data.message || "OTP sent to your phone");
        setOtpSent(true);
      } else {
        toast.error(data.message || "Failed to send OTP");
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || "Failed to send OTP. Check your number.";
      toast.error(msg);
    } finally {
      setIsSendingOtp(false);
    }
  };

  const handleResetPassword = async () => {
    if (!otp.trim()) { toast.error("Please enter the OTP"); return; }
    if (newPassword.length < 8) { toast.error("Password must be at least 8 characters"); return; }
    if (newPassword !== confirmPassword) { toast.error("Passwords do not match"); return; }

    setIsResetting(true);
    try {
      const { data } = await axios.post(`${API_URL}/reset-password`, {
        phone: phone.trim(),
        otp: otp.trim(),
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      if (data.status) {
        toast.success(data.message || "Password reset successfully!");
        router.replace("/login");
      } else {
        toast.error(data.message || "Invalid OTP");
      }
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Failed to reset password");
    } finally {
      setIsResetting(false);
    }
  };

  const passwordsMatch = confirmPassword.length === 0 || newPassword === confirmPassword;

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === "ios" ? "padding" : undefined}>
      <ScrollView
        contentContainerStyle={[styles.scrollContent, { paddingTop: insets.top + 20, paddingBottom: 40 }]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {/* Back button */}
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/login")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={20} color="#6b7280" />
        </TouchableOpacity>

        {/* Branding */}
        <View style={styles.brandingSection}>
          <View style={styles.logoWrap}>
            <Ionicons name={otpSent ? "key" : "lock-closed"} size={32} color="#fff" />
          </View>
          <Text style={styles.title}>{otpSent ? "Reset Password" : "Forgot Password?"}</Text>
          <Text style={styles.subtitle}>
            {otpSent
              ? "Enter the OTP sent to your phone and set a new password."
              : "Enter your registered phone number and we'll send you an OTP."}
          </Text>
        </View>

        {!otpSent ? (
          /* ── Step 1: Enter Phone ── */
          <View style={styles.form}>
            <View style={styles.inputGroup}>
              <Text style={styles.label}>Phone Number</Text>
              <View style={styles.inputWrap}>
                <Ionicons name="call-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="01XXXXXXXXX"
                  placeholderTextColor="#9ca3af"
                  value={phone}
                  onChangeText={setPhone}
                  keyboardType="phone-pad"
                  autoCapitalize="none"
                />
              </View>
            </View>

            <TouchableOpacity
              style={[styles.primaryBtn, (isSendingOtp || !phone.trim()) && styles.btnDisabled]}
              onPress={handleSendOtp}
              disabled={isSendingOtp || !phone.trim()}
              activeOpacity={0.8}
            >
              {isSendingOtp ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.primaryBtnText}>Send OTP</Text>
              )}
            </TouchableOpacity>
          </View>
        ) : (
          /* ── Step 2: OTP + New Password ── */
          <View style={styles.form}>
            {/* OTP sent banner */}
            <View style={styles.successBanner}>
              <Ionicons name="checkmark-circle" size={16} color="#065F46" />
              <View style={{ flex: 1 }}>
                <Text style={styles.successText}>OTP sent to {phone}</Text>
                <Text style={styles.successSub}>Check your SMS inbox for the 6-digit code</Text>
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>OTP Code</Text>
              <View style={styles.inputWrap}>
                <Ionicons name="keypad-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={[styles.input, { letterSpacing: 6, textAlign: "center", fontWeight: "700" }]}
                  placeholder="000000"
                  placeholderTextColor="#9ca3af"
                  value={otp}
                  onChangeText={(t) => setOtp(t.replace(/\D/g, ""))}
                  keyboardType="number-pad"
                  maxLength={6}
                />
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>New Password <Text style={styles.labelHint}>(min 8 characters)</Text></Text>
              <View style={styles.inputWrap}>
                <Ionicons name="lock-closed-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="Enter new password"
                  placeholderTextColor="#9ca3af"
                  value={newPassword}
                  onChangeText={setNewPassword}
                  secureTextEntry={!showNewPw}
                />
                <TouchableOpacity onPress={() => setShowNewPw(!showNewPw)}>
                  <Ionicons name={showNewPw ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                </TouchableOpacity>
              </View>
            </View>

            <View style={styles.inputGroup}>
              <Text style={styles.label}>Confirm Password</Text>
              <View style={[styles.inputWrap, !passwordsMatch && styles.inputError]}>
                <Ionicons name="lock-closed-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="Confirm new password"
                  placeholderTextColor="#9ca3af"
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry={!showConfirmPw}
                />
                <TouchableOpacity onPress={() => setShowConfirmPw(!showConfirmPw)}>
                  <Ionicons name={showConfirmPw ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                </TouchableOpacity>
              </View>
              {!passwordsMatch && <Text style={styles.errorText}>Passwords do not match</Text>}
            </View>

            <TouchableOpacity
              style={[styles.primaryBtn, (isResetting || !passwordsMatch) && styles.btnDisabled]}
              onPress={handleResetPassword}
              disabled={isResetting || !passwordsMatch}
              activeOpacity={0.8}
            >
              {isResetting ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.primaryBtnText}>Reset Password</Text>
              )}
            </TouchableOpacity>

            <TouchableOpacity onPress={() => { setOtpSent(false); setOtp(""); setNewPassword(""); setConfirmPassword(""); }}>
              <Text style={styles.changePhoneLink}>← Change phone number</Text>
            </TouchableOpacity>
          </View>
        )}

        {/* Back to login */}
        <View style={styles.footerSection}>
          <Text style={styles.footerText}>Remember your password?</Text>
          <TouchableOpacity onPress={() => router.replace("/login")}>
            <Text style={styles.footerLink}>Back to Login</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff" },
  scrollContent: { flexGrow: 1, paddingHorizontal: 24 },
  backBtn: {
    width: 40, height: 40, borderRadius: 20, backgroundColor: "#f3f4f6",
    alignItems: "center", justifyContent: "center", marginBottom: 8,
  },
  brandingSection: { alignItems: "center", marginTop: 8, marginBottom: 32 },
  logoWrap: {
    width: 72, height: 72, borderRadius: 20, backgroundColor: BRAND.primary,
    alignItems: "center", justifyContent: "center", marginBottom: 16,
  },
  title: { fontSize: 22, fontWeight: "700", color: "#1a1a2e" },
  subtitle: { fontSize: 14, color: "#6b7280", marginTop: 6, textAlign: "center", paddingHorizontal: 20 },
  form: { gap: 16 },
  inputGroup: { gap: 6 },
  label: { fontSize: 13, fontWeight: "600", color: "#374151", marginLeft: 2 },
  labelHint: { fontWeight: "400", color: "#9ca3af" },
  inputWrap: {
    flexDirection: "row", alignItems: "center", backgroundColor: "#f9fafb",
    borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 12,
    paddingHorizontal: 14, paddingVertical: 12, gap: 10,
  },
  inputError: { borderColor: "#ef4444" },
  input: { flex: 1, fontSize: 15, color: "#1a1a2e" },
  errorText: { fontSize: 11, color: "#ef4444", marginLeft: 2 },
  primaryBtn: {
    backgroundColor: BRAND.primary, borderRadius: 12,
    paddingVertical: 15, alignItems: "center", justifyContent: "center", marginTop: 4,
  },
  btnDisabled: { opacity: 0.6 },
  primaryBtnText: { fontSize: 16, fontWeight: "600", color: "#fff" },
  successBanner: {
    flexDirection: "row", alignItems: "center", gap: 10,
    backgroundColor: "#D1FAE5", borderWidth: 1, borderColor: "#A7F3D0",
    borderRadius: 12, padding: 14,
  },
  successText: { fontSize: 13, fontWeight: "600", color: "#065F46" },
  successSub: { fontSize: 11, color: "#047857", marginTop: 2 },
  changePhoneLink: { fontSize: 13, color: "#6b7280", textAlign: "center", paddingVertical: 4 },
  footerSection: {
    flexDirection: "row", justifyContent: "center", alignItems: "center",
    gap: 6, marginTop: 28,
  },
  footerText: { fontSize: 14, color: "#6b7280" },
  footerLink: { fontSize: 14, fontWeight: "600", color: BRAND.primary },
});
