import React, { useState } from "react";
import {
  View, Text, StyleSheet, TextInput, TouchableOpacity,
  ScrollView, ActivityIndicator, KeyboardAvoidingView, Platform,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND, CARD_SHADOW } from "@/lib/constants";
import apiClient from "@/lib/api-client";

export default function ChangePasswordScreen() {
  const insets = useSafeAreaInsets();

  const [currentPassword, setCurrentPassword] = useState("");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const passwordsMatch = confirmPassword.length === 0 || newPassword === confirmPassword;
  const canSubmit = currentPassword.trim().length > 0 && newPassword.length >= 8 && passwordsMatch && confirmPassword.length > 0;

  const handleChangePassword = async () => {
    if (!canSubmit) return;
    setIsSubmitting(true);
    try {
      const { data } = await apiClient.post("/vendor/change-password", {
        old_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      if (data.status) {
        toast.success(data.message || "Password changed successfully!");
        setCurrentPassword(""); setNewPassword(""); setConfirmPassword("");
        router.canGoBack() ? router.back() : router.replace("/(tabs)/account");
      } else {
        toast.error(data.message || "Failed to change password");
      }
    } catch (err: any) {
      const msg = err?.response?.data?.message || "Failed to change password";
      toast.error(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.canGoBack() ? router.back() : router.replace("/(tabs)/account")} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Change Password</Text>
        <View style={{ width: 32 }} />
      </View>

      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === "ios" ? "padding" : undefined}>
        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled" showsVerticalScrollIndicator={false}>

          {/* Info card */}
          <View style={styles.infoCard}>
            <View style={styles.infoIconWrap}>
              <Ionicons name="shield-checkmark" size={24} color={BRAND.primary} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.infoTitle}>Update your password</Text>
              <Text style={styles.infoSub}>Use a strong password with at least 8 characters</Text>
            </View>
          </View>

          {/* Form */}
          <View style={styles.formCard}>
            {/* Current Password */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>Current Password <Text style={styles.required}>*</Text></Text>
              <View style={styles.inputWrap}>
                <Ionicons name="lock-closed-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="Enter current password"
                  placeholderTextColor="#9ca3af"
                  value={currentPassword}
                  onChangeText={setCurrentPassword}
                  secureTextEntry={!showCurrent}
                />
                <TouchableOpacity onPress={() => setShowCurrent(!showCurrent)}>
                  <Ionicons name={showCurrent ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                </TouchableOpacity>
              </View>
            </View>

            {/* Divider */}
            <View style={styles.divider} />

            {/* New Password */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>New Password <Text style={styles.required}>*</Text> <Text style={styles.labelHint}>(min 8 characters)</Text></Text>
              <View style={styles.inputWrap}>
                <Ionicons name="key-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="Enter new password"
                  placeholderTextColor="#9ca3af"
                  value={newPassword}
                  onChangeText={setNewPassword}
                  secureTextEntry={!showNew}
                />
                <TouchableOpacity onPress={() => setShowNew(!showNew)}>
                  <Ionicons name={showNew ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                </TouchableOpacity>
              </View>
              {newPassword.length > 0 && newPassword.length < 8 && (
                <Text style={styles.errorText}>Password must be at least 8 characters</Text>
              )}
            </View>

            {/* Confirm Password */}
            <View style={styles.inputGroup}>
              <Text style={styles.label}>Confirm New Password <Text style={styles.required}>*</Text></Text>
              <View style={[styles.inputWrap, !passwordsMatch && styles.inputError]}>
                <Ionicons name="key-outline" size={18} color="#9ca3af" />
                <TextInput
                  style={styles.input}
                  placeholder="Confirm new password"
                  placeholderTextColor="#9ca3af"
                  value={confirmPassword}
                  onChangeText={setConfirmPassword}
                  secureTextEntry={!showConfirm}
                />
                <TouchableOpacity onPress={() => setShowConfirm(!showConfirm)}>
                  <Ionicons name={showConfirm ? "eye-off-outline" : "eye-outline"} size={20} color="#9ca3af" />
                </TouchableOpacity>
              </View>
              {!passwordsMatch && <Text style={styles.errorText}>Passwords do not match</Text>}
              {passwordsMatch && confirmPassword.length > 0 && (
                <View style={styles.matchRow}>
                  <Ionicons name="checkmark-circle" size={14} color="#065F46" />
                  <Text style={styles.matchText}>Passwords match</Text>
                </View>
              )}
            </View>

            {/* Submit */}
            <TouchableOpacity
              style={[styles.submitBtn, (!canSubmit || isSubmitting) && styles.btnDisabled]}
              onPress={handleChangePassword}
              disabled={!canSubmit || isSubmitting}
              activeOpacity={0.8}
            >
              {isSubmitting ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <>
                  <Ionicons name="shield-checkmark" size={18} color="#fff" />
                  <Text style={styles.submitBtnText}>Change Password</Text>
                </>
              )}
            </TouchableOpacity>
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row", alignItems: "center", justifyContent: "space-between",
    paddingHorizontal: 16, paddingVertical: 12, backgroundColor: "#fff",
    borderBottomWidth: 1, borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  scrollContent: { padding: 16 },
  // Info card
  infoCard: {
    flexDirection: "row", alignItems: "center", gap: 14,
    backgroundColor: "#EEF2FF", borderWidth: 1, borderColor: "#C7D2FE",
    borderRadius: 14, padding: 16, marginBottom: 16,
  },
  infoIconWrap: {
    width: 44, height: 44, borderRadius: 12, backgroundColor: "#fff",
    alignItems: "center", justifyContent: "center",
  },
  infoTitle: { fontSize: 14, fontWeight: "600", color: "#1a1a2e" },
  infoSub: { fontSize: 12, color: "#6b7280", marginTop: 2 },
  // Form card
  formCard: {
    backgroundColor: "#fff", borderRadius: 14, padding: 20, gap: 16,
    ...CARD_SHADOW,
  },
  inputGroup: { gap: 6 },
  label: { fontSize: 13, fontWeight: "600", color: "#374151", marginLeft: 2 },
  labelHint: { fontWeight: "400", color: "#9ca3af" },
  required: { color: "#ef4444" },
  inputWrap: {
    flexDirection: "row", alignItems: "center", backgroundColor: "#f9fafb",
    borderWidth: 1, borderColor: "#e5e7eb", borderRadius: 10,
    paddingHorizontal: 14, paddingVertical: 12, gap: 10,
  },
  inputError: { borderColor: "#ef4444", backgroundColor: "#FEF2F2" },
  input: { flex: 1, fontSize: 14, color: "#1a1a2e" },
  errorText: { fontSize: 11, color: "#ef4444", marginLeft: 2 },
  matchRow: { flexDirection: "row", alignItems: "center", gap: 4, marginLeft: 2 },
  matchText: { fontSize: 11, color: "#065F46" },
  divider: { height: 1, backgroundColor: "#f3f4f6", marginVertical: 4 },
  submitBtn: {
    flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 8,
    backgroundColor: BRAND.primary, borderRadius: 12,
    paddingVertical: 15, marginTop: 4,
  },
  btnDisabled: { opacity: 0.5 },
  submitBtnText: { fontSize: 15, fontWeight: "600", color: "#fff" },
});
