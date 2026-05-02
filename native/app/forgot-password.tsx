import { useState } from "react";
import {
  Image,
  Dimensions,
  StyleSheet,
  View,
  Pressable,
  ScrollView,
  TextInput,
  KeyboardAvoidingView,
  Platform,
  ActivityIndicator,
} from "react-native";
import { Text } from "tamagui";
import { router } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";

import {
  forgotPassword,
  verifyOtp,
  resetPassword,
} from "@/lib/auth-client";

const { width } = Dimensions.get("window");

type Step = "phone" | "otp" | "reset";

export default function ForgotPasswordScreen() {
  const [step, setStep] = useState<Step>("phone");
  const [phone, setPhone] = useState("");
  const [otp, setOtp] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  const titles: Record<Step, string> = {
    phone: "Forgot Password",
    otp: "Verify OTP",
    reset: "Set New Password",
  };

  const descriptions: Record<Step, string> = {
    phone: "Enter your registered phone number to receive a password reset OTP.",
    otp: `We've sent a 6-digit OTP to ${phone}. Please enter it below.`,
    reset: "Enter your new password below.",
  };

  async function handleSendOtp() {
    if (!phone.trim()) {
      setError("Please enter your phone number");
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      const res = await forgotPassword(phone.trim());
      if (res?.status) {
        toast.success(res.message || "OTP sent successfully!");
        setStep("otp");
      }
    } catch (err: any) {
      setError(err?.response?.data?.message || "Failed to send OTP. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  async function handleVerifyOtp() {
    if (!otp.trim() || otp.trim().length !== 6) {
      setError("Please enter the 6-digit OTP");
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      const res = await verifyOtp(phone.trim(), otp.trim());
      if (res?.status) {
        toast.success(res.message || "OTP verified!");
        setStep("reset");
      }
    } catch (err: any) {
      setError(err?.response?.data?.message || "Invalid OTP. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  async function handleResetPassword() {
    if (!password || !confirmPassword) {
      setError("Please fill in both fields");
      return;
    }
    if (password.length < 6) {
      setError("Password must be at least 6 characters");
      return;
    }
    if (password !== confirmPassword) {
      setError("Passwords do not match");
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      const res = await resetPassword(phone.trim(), otp.trim(), password, confirmPassword);
      if (res?.status) {
        toast.success(res.message || "Password reset successfully!");
        router.replace("/login");
      }
    } catch (err: any) {
      setError(err?.response?.data?.message || "Failed to reset password. Please try again.");
    } finally {
      setIsLoading(false);
    }
  }

  async function handleResendOtp() {
    try {
      const res = await forgotPassword(phone.trim());
      if (res?.status) {
        toast.success("OTP resent successfully!");
      }
    } catch (err: any) {
      toast.error(err?.response?.data?.message || "Failed to resend OTP.");
    }
  }

  function handleSubmit() {
    if (step === "phone") handleSendOtp();
    else if (step === "otp") handleVerifyOtp();
    else handleResetPassword();
  }

  const buttonLabel = step === "phone" ? "Send OTP" : step === "otp" ? "Verify OTP" : "Reset Password";

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === "ios" ? "padding" : "height"}
    >
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        {/* Back button */}
        <Pressable style={styles.backBtn} onPress={() => {
          if (step === "phone") router.back();
          else if (step === "otp") { setStep("phone"); setOtp(""); setError(null); }
          else { setStep("otp"); setError(null); }
        }}>
          <Ionicons name="arrow-back" size={22} color="#1A1A2E" />
        </Pressable>

        {/* Logo */}
        <View style={styles.logoContainer}>
          <View style={styles.logoBg}>
            <Image
              source={require("@/assets/images/reseller_app_logo.jpeg")}
              style={styles.logo}
              resizeMode="contain"
            />
          </View>
        </View>

        {/* Title */}
        <Text fontSize="$7" fontWeight="bold" color="#1A1A2E" text="center" mt="$4">
          {titles[step]}
        </Text>
        <Text fontSize="$3" color="#8E8E93" text="center" mt="$2" mx="$4" lineHeight={20}>
          {descriptions[step]}
        </Text>

        {/* Progress dots */}
        <View style={styles.stepDots}>
          {(["phone", "otp", "reset"] as Step[]).map((s, i) => (
            <View
              key={s}
              style={[
                styles.dot,
                (step === s || (["phone", "otp", "reset"].indexOf(step) > i))
                  && styles.dotActive,
              ]}
            />
          ))}
        </View>

        {/* Error */}
        {error && (
          <View style={styles.errorContainer}>
            <Text fontSize="$3" color="#DC2626">{error}</Text>
          </View>
        )}

        {/* Step 1: Phone */}
        {step === "phone" && (
          <View style={styles.formContainer}>
            <View style={styles.inputContainer}>
              <Ionicons name="call-outline" size={18} color="#C7C7CC" style={{ marginLeft: 16 }} />
              <TextInput
                style={styles.input}
                placeholder="Enter your registered phone number"
                placeholderTextColor="#C7C7CC"
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
                autoCapitalize="none"
              />
            </View>
          </View>
        )}

        {/* Step 2: OTP */}
        {step === "otp" && (
          <View style={styles.formContainer}>
            <View style={styles.inputContainer}>
              <Ionicons name="keypad-outline" size={18} color="#C7C7CC" style={{ marginLeft: 16 }} />
              <TextInput
                style={[styles.input, { letterSpacing: 8, fontSize: 20, textAlign: "center" }]}
                placeholder="000000"
                placeholderTextColor="#C7C7CC"
                value={otp}
                onChangeText={setOtp}
                keyboardType="number-pad"
                maxLength={6}
                autoCapitalize="none"
              />
            </View>
            <Pressable onPress={handleResendOtp} style={{ alignSelf: "center", marginTop: 8 }}>
              <Text fontSize="$3" color="#8E8E93">
                Didn't receive the OTP?{" "}
                <Text color="#E5005F" fontWeight="600">Resend OTP</Text>
              </Text>
            </Pressable>
          </View>
        )}

        {/* Step 3: New Password */}
        {step === "reset" && (
          <View style={styles.formContainer}>
            <View style={styles.inputContainer}>
              <TextInput
                style={styles.input}
                placeholder="New Password"
                placeholderTextColor="#C7C7CC"
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPassword}
                autoCapitalize="none"
              />
              <Pressable style={styles.eyeIcon} onPress={() => setShowPassword(!showPassword)}>
                <Ionicons name={showPassword ? "eye-off-outline" : "eye-outline"} size={20} color="#C7C7CC" />
              </Pressable>
            </View>
            <View style={styles.inputContainer}>
              <TextInput
                style={styles.input}
                placeholder="Confirm New Password"
                placeholderTextColor="#C7C7CC"
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                secureTextEntry={!showConfirm}
                autoCapitalize="none"
              />
              <Pressable style={styles.eyeIcon} onPress={() => setShowConfirm(!showConfirm)}>
                <Ionicons name={showConfirm ? "eye-off-outline" : "eye-outline"} size={20} color="#C7C7CC" />
              </Pressable>
            </View>
          </View>
        )}

        {/* Submit button */}
        <Pressable
          style={({ pressed }) => [
            styles.submitBtn,
            pressed && { opacity: 0.85 },
            isLoading && { opacity: 0.6 },
          ]}
          onPress={handleSubmit}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text fontSize="$5" fontWeight="bold" color="#fff">
              {buttonLabel}
            </Text>
          )}
        </Pressable>

        {/* Back to Login link */}
        <Pressable onPress={() => router.replace("/login")} style={{ alignSelf: "center", marginTop: 20 }}>
          <Text fontSize="$3" color="#8E8E93">
            Remember your password?{" "}
            <Text color="#E5005F" fontWeight="600">Back to Login</Text>
          </Text>
        </Pressable>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  scrollContent: {
    flexGrow: 1,
    paddingHorizontal: 32,
    paddingBottom: 40,
  },
  backBtn: {
    marginTop: 56,
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#F5F5F5",
    justifyContent: "center",
    alignItems: "center",
  },
  logoContainer: {
    alignItems: "center",
    marginTop: 24,
  },
  logoBg: {
    width: width * 0.3,
    height: width * 0.3,
    borderRadius: width * 0.15,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  logo: {
    width: width * 0.18,
    height: width * 0.18,
  },
  stepDots: {
    flexDirection: "row",
    justifyContent: "center",
    gap: 8,
    marginTop: 20,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: "#E0E0E0",
  },
  dotActive: {
    backgroundColor: "#E5005F",
    width: 24,
    borderRadius: 4,
  },
  errorContainer: {
    backgroundColor: "rgba(220, 38, 38, 0.08)",
    borderRadius: 12,
    padding: 12,
    marginTop: 16,
  },
  formContainer: {
    marginTop: 24,
    gap: 14,
  },
  inputContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F8F8F8",
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#EFEFEF",
  },
  input: {
    flex: 1,
    paddingVertical: 16,
    paddingHorizontal: 18,
    fontSize: 15,
    color: "#1A1A2E",
    fontFamily: "Inter",
  },
  eyeIcon: {
    paddingHorizontal: 16,
  },
  submitBtn: {
    backgroundColor: "#E5005F",
    paddingVertical: 16,
    borderRadius: 30,
    alignItems: "center",
    marginTop: 28,
  },
});
