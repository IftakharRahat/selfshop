import { useState } from "react";
import {
  Image,
  Dimensions,
  StyleSheet,
  View,
  Pressable,
  TextInput,
  Platform,
  ActivityIndicator,
} from "react-native";
import { KeyboardAwareScrollView } from "react-native-keyboard-controller";
import { Text } from "tamagui";
import { router, useLocalSearchParams } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

import { login } from "@/lib/auth-client";
import { useQueryClient } from "@tanstack/react-query";
import { fetchUserProfilePayload, invalidateSubscriptionAccessQueries } from "@/lib/subscription-api";
import { isSubscriptionActive, subscriptionDestinationFromProfile } from "@/lib/subscription-routing";

const { width } = Dimensions.get("window");

export default function Login() {
  const { returnTo } = useLocalSearchParams<{ returnTo?: string }>();
  const [form, setForm] = useState({ email: "", password: "" });
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [showPassword, setShowPassword] = useState(false);
  const queryClient = useQueryClient();

  async function handleSignIn() {
    if (!form.email || !form.password) {
      setError("Please fill in all fields");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const session = await login(form.email, form.password);
      invalidateSubscriptionAccessQueries(queryClient);
      const profilePayload = session.profilePayload?.subscription
        ? session.profilePayload
        : await queryClient.fetchQuery({
            queryKey: ["user-profile"],
            queryFn: fetchUserProfilePayload,
          });
      setForm({ email: "", password: "" });
      if (returnTo && isSubscriptionActive(profilePayload)) {
        router.replace(returnTo as any);
      } else {
        router.replace(subscriptionDestinationFromProfile(profilePayload) as any);
      }
    } catch (err: any) {
      const message =
        err?.response?.data?.message || err?.message || "Failed to sign in";
      setError(message);
    } finally {
      setIsLoading(false);
    }
  }

  return (
    <KeyboardAwareScrollView
      style={styles.container}
      contentContainerStyle={styles.scrollContent}
      showsVerticalScrollIndicator={false}
      keyboardShouldPersistTaps="handled"
      keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
      bottomOffset={24}
    >
        {/* Logo Area */}
        <View style={styles.logoContainer}>
          <View style={styles.logoBg}>
            <Image
              source={require("@/assets/images/selfshop_png.png")}
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
          Welcome Back
        </Text>
        <Text fontSize="$4" color="#8E8E93" text="center" mt="$2">
          Hello, sign in to continue!
        </Text>
        <Pressable onPress={() => router.push("/register")}>
          <Text fontSize="$4" text="center" mt="$1">
            Or{" "}
            <Text color="#E5005F" fontWeight="600">
              Create new account
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

          <View style={styles.inputContainer}>
            <TextInput
              style={styles.input}
              placeholder="Password"
              placeholderTextColor="#C7C7CC"
              value={form.password}
              onChangeText={(val) => setForm((prev) => ({ ...prev, password: val }))}
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
        </View>

        {/* Sign In Button */}
        <Pressable
          style={({ pressed }) => [
            styles.signInButton,
            pressed && styles.buttonPressed,
            isLoading && styles.buttonDisabled,
          ]}
          onPress={handleSignIn}
          disabled={isLoading}
        >
          {isLoading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text fontSize="$5" fontWeight="bold" color="#fff">
              Sign in
            </Text>
          )}
        </Pressable>

        {/* Forgot Password */}
        <Pressable style={styles.forgotPassword} onPress={() => router.push("/forgot-password")}>
          <Text fontSize="$3" color="#E5005F" fontWeight="600">
            Forgot Password?
          </Text>
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
    </KeyboardAwareScrollView>
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
    width: width * 0.45,
    height: width * 0.45,
    borderRadius: width * 0.225,
    backgroundColor: "#E5005F",
    justifyContent: "center",
    alignItems: "center",
  },
  logo: {
    width: width * 0.3,
    height: width * 0.3,
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
  signInButton: {
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
  forgotPassword: {
    alignItems: "center",
    marginTop: 16,
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
