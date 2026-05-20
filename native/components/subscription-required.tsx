import { ActivityIndicator, Pressable, StyleSheet, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { router } from "expo-router";
import { Text } from "tamagui";

import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";
import { invoiceRoute } from "@/lib/subscription-routing";

interface SubscriptionRequiredProps {
  title?: string;
  message?: string;
  compact?: boolean;
}

const ACCENT = "#E5005F";
const DARK = "#1A1A2E";
const GREY = "#8E8E93";

export function SubscriptionRequired({
  title = "Activate Subscription",
  message = "Your account needs an active subscription to use this feature.",
  compact = false,
}: SubscriptionRequiredProps) {
  const { isLoading, isLoggedIn, pendingInvoice } = useIsActiveReseller();
  const pendingInvoiceRoute = invoiceRoute(pendingInvoice);

  if (isLoading) {
    return (
      <View style={[styles.container, compact && styles.compact]}>
        <ActivityIndicator color={ACCENT} />
      </View>
    );
  }

  if (!isLoggedIn) {
    return (
      <View style={[styles.container, compact && styles.compact]}>
        <View style={styles.iconCircle}>
          <Ionicons name="log-in-outline" size={34} color={ACCENT} />
        </View>
        <Text fontSize="$5" fontWeight="800" color={DARK} mt="$3" text="center">
          Login Required
        </Text>
        <Text fontSize="$3" color={GREY} mt="$1" text="center" style={styles.message}>
          Login to activate your subscription and unlock reseller tools.
        </Text>
        <Pressable style={styles.primaryButton} onPress={() => router.push("/login")}>
          <Ionicons name="log-in-outline" size={18} color="#fff" />
          <Text fontSize="$3" fontWeight="800" color="#fff">Login</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <View style={[styles.container, compact && styles.compact]}>
      <View style={styles.iconCircle}>
        <Ionicons name="lock-closed-outline" size={34} color={ACCENT} />
      </View>
      <Text fontSize="$5" fontWeight="800" color={DARK} mt="$3" text="center">
        {title}
      </Text>
      <Text fontSize="$3" color={GREY} mt="$1" text="center" style={styles.message}>
        {message}
      </Text>

      {pendingInvoiceRoute && (
        <Pressable style={styles.primaryButton} onPress={() => router.push(pendingInvoiceRoute as any)}>
          <Ionicons name="receipt-outline" size={18} color="#fff" />
          <Text fontSize="$3" fontWeight="800" color="#fff">Continue Payment</Text>
        </Pressable>
      )}

      <Pressable
        style={[pendingInvoiceRoute ? styles.secondaryButton : styles.primaryButton]}
        onPress={() => router.push("/pricing")}
      >
        <Ionicons name="card-outline" size={18} color={pendingInvoiceRoute ? ACCENT : "#fff"} />
        <Text fontSize="$3" fontWeight="800" color={pendingInvoiceRoute ? ACCENT : "#fff"}>
          Activate Subscription
        </Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 28,
    paddingBottom: 80,
    backgroundColor: "#fff",
  },
  compact: {
    minHeight: 320,
    paddingBottom: 28,
  },
  iconCircle: {
    width: 82,
    height: 82,
    borderRadius: 41,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#FFF0F5",
  },
  message: {
    maxWidth: 300,
    lineHeight: 20,
  },
  primaryButton: {
    marginTop: 24,
    minWidth: 220,
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: ACCENT,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
    gap: 8,
    paddingHorizontal: 18,
  },
  secondaryButton: {
    marginTop: 12,
    minWidth: 220,
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: "#fff",
    borderWidth: 1.5,
    borderColor: ACCENT,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
    gap: 8,
    paddingHorizontal: 18,
  },
});
