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

import { register } from "@/lib/auth-client";
import { useQueryClient } from "@tanstack/react-query";

const { width } = Dimensions.get("window");

export default function Register() {
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    c_password: "",
    refer_by: "",
  });
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [showReferral, setShowReferral] = useState(false);
  const queryClient = useQueryClient();

  async function handleSignUp() {
    if (!form.name || !form.email || !form.password) {
      setError("Please fill in all fields");
      return;
    }
    if (form.password !== form.c_password) {
      setError("Passwords do not match");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      await register(form.name, form.email, form.password, form.c_password);
      queryClient.invalidateQueries({ queryKey: ["auth-token"] });
      queryClient.invalidateQueries({ queryKey: ["user-profile"] });
      setForm({ name: "", email: "", password: "", c_password: "", refer_by: "" });
      router.replace("/");
    } catch (err: any) {
      const message =
        err?.response?.data?.message || err?.message || "Failed to create account";
      setError(message);
    } finally {
      setIsLoading(false);
    }
  }

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
        {/* Logo Area */}
        <View style={styles.logoContainer}>
          <View style={styles.logoBg}>
            <Image
              source={require("@/assets/images/reseller_app_logo.jpeg")}
              style={styles.logo}
              resizeMode="contain"
            />
          </View>
        </View>

        {/* Welcome Text */}
        <Text
          fontSize="$8"
          fontWeight="bold"
          color="#1A1A2E"
          text="center"
          mt="$4"
        >
          Create Account
        </Text>
        <Text fontSize="$4" color="#8E8E93" text="center" mt="$2">
          Sign up to get started!
        </Text>
        <Pressable onPress={() => router.back()}>
          <Text fontSize="$4" text="center" mt="$1">
            Already have an account?{" "}
            <Text color="#E5005F" fontWeight="600">
              Sign in
            </Text>
          </Text>
        </Pressable>

        {/* Error Message */}
        {error && (
          <View style={styles.errorContainer}>
            <Text fontSize="$3" color="#DC2626">
              {error}
            </Text>
          </View>
        )}

        {/* Form */}
        <View style={styles.formContainer}>
          {/* Name */}
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Full Name"
              placeholderTextColor="#C7C7CC"
              value={form.name}
              onChangeText={(val) => setForm((prev) => ({ ...prev, name: val }))}
              autoCapitalize="words"
            />
          </View>

          {/* Phone Number */}
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Phone Number"
              placeholderTextColor="#C7C7CC"
              value={form.email}
              onChangeText={(val) => setForm((prev) => ({ ...prev, email: val }))}
              keyboardType="phone-pad"
              autoCapitalize="none"
            />
          </View>

          {/* Password */}
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Password"
              placeholderTextColor="#C7C7CC"
              value={form.password}
              onChangeText={(val) =>
                setForm((prev) => ({ ...prev, password: val }))
              }
              secureTextEntry={!showPassword}
              autoCapitalize="none"
            />
            <Pressable
              style={styles.eyeIcon}
              onPress={() => setShowPassword(!showPassword)}
            >
              <Ionicons
                name={showPassword ? "eye-off-outline" : "eye-outline"}
                size={20}
                color="#C7C7CC"
              />
            </Pressable>
          </View>

          {/* Confirm Password */}
          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Confirm Password"
              placeholderTextColor="#C7C7CC"
              value={form.c_password}
              onChangeText={(val) =>
                setForm((prev) => ({ ...prev, c_password: val }))
              }
              secureTextEntry={!showConfirm}
              autoCapitalize="none"
            />
            <Pressable
              style={styles.eyeIcon}
              onPress={() => setShowConfirm(!showConfirm)}
            >
              <Ionicons
                name={showConfirm ? "eye-off-outline" : "eye-outline"}
                size={20}
                color="#C7C7CC"
              />
            </Pressable>
          </View>

          {/* Referral Code */}
          {!showReferral ? (
            <Pressable onPress={() => setShowReferral(true)}>
              <Text fontSize="$3" color="#E5005F" fontWeight="600">
                Have a refer code?
              </Text>
            </Pressable>
          ) : (
            <View style={styles.inputContainer}>
              <TextInput
                style={styles.input}
                placeholder="Enter refer code..."
                placeholderTextColor="#C7C7CC"
                value={form.refer_by}
                onChangeText={(val) =>
                  setForm((prev) => ({ ...prev, refer_by: val }))
                }
                autoCapitalize="none"
              />
            </View>
          )}
        </View>

        {/* Sign Up Button */}
        <Pressable
          style={({ pressed }) => [
            styles.signUpButton,
            pressed && styles.buttonPressed,
            isLoading && styles.buttonDisabled,
          ]}
          onPress={handleSignUp}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text fontSize="$5" fontWeight="bold" color="#fff">
              Create Account
            </Text>
          )}
        </Pressable>

        {/* OR Divider */}
        <View style={styles.divider}>
          <View style={styles.dividerLine} />
          <Text fontSize="$3" color="#C7C7CC" mx="$3">
            OR
          </Text>
          <View style={styles.dividerLine} />
        </View>

        {/* Social Login Buttons */}
        <Pressable
          style={({ pressed }) => [
            styles.socialButton,
            pressed && { opacity: 0.8 },
          ]}
        >
          <Ionicons name="logo-google" size={22} color="#EA4335" />
          <Text fontSize="$4" fontWeight="600" color="#1A1A2E" ml="$3">
            Continue with Google
          </Text>
        </Pressable>

        <Pressable
          style={({ pressed }) => [
            styles.socialButton,
            pressed && { opacity: 0.8 },
          ]}
        >
          <Ionicons name="logo-apple" size={22} color="#000" />
          <Text fontSize="$4" fontWeight="600" color="#1A1A2E" ml="$3">
            Continue with Apple
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
  logoContainer: {
    alignItems: "center",
    marginTop: 60,
  },
  logoBg: {
    width: width * 0.35,
    height: width * 0.35,
    borderRadius: width * 0.175,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  logo: {
    width: width * 0.22,
    height: width * 0.22,
  },
  errorContainer: {
    backgroundColor: "rgba(220, 38, 38, 0.08)",
    borderRadius: 12,
    padding: 12,
    marginTop: 16,
  },
  formContainer: {
    marginTop: 28,
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
  signUpButton: {
    backgroundColor: "#E5005F",
    paddingVertical: 16,
    borderRadius: 30,
    alignItems: "center",
    marginTop: 24,
  },
  buttonPressed: {
    opacity: 0.85,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  divider: {
    flexDirection: "row",
    alignItems: "center",
    marginTop: 24,
    marginBottom: 20,
  },
  dividerLine: {
    flex: 1,
    height: 1,
    backgroundColor: "#EFEFEF",
  },
  socialButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#EFEFEF",
    backgroundColor: "#FAFAFA",
    marginBottom: 12,
  },
});
