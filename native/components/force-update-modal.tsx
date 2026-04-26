import React from "react";
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Modal,
  Linking,
  Image,
  BackHandler,
} from "react-native";
import { BRAND } from "@/lib/constants";

interface ForceUpdateModalProps {
  visible: boolean;
  storeUrl: string;
}

/**
 * Full-screen, non-dismissible modal that blocks the app and
 * prompts the user to update via the Google Play Store.
 */
export function ForceUpdateModal({ visible, storeUrl }: ForceUpdateModalProps) {
  // Block Android hardware back button
  React.useEffect(() => {
    if (!visible) return;
    const sub = BackHandler.addEventListener("hardwareBackPress", () => true);
    return () => sub.remove();
  }, [visible]);

  const handleUpdate = () => {
    Linking.openURL(storeUrl).catch(() => {
      // Fallback: try market intent
      Linking.openURL(
        "market://details?id=com.selfshop.app"
      ).catch(() => {});
    });
  };

  return (
    <Modal
      visible={visible}
      animationType="fade"
      transparent
      statusBarTranslucent
      onRequestClose={() => {}}
    >
      <View style={styles.overlay}>
        <View style={styles.card}>
          {/* Icon */}
          <View style={styles.iconContainer}>
            <View style={styles.iconCircle}>
              <Text style={styles.iconEmoji}>🚀</Text>
            </View>
          </View>

          {/* Title */}
          <Text style={styles.title}>Update Required</Text>

          {/* Message */}
          <Text style={styles.message}>
            A new version of SelfShop is available. Please update to the latest
            version to continue using the app.
          </Text>

          {/* Divider */}
          <View style={styles.divider} />

          {/* CTA */}
          <TouchableOpacity
            style={styles.button}
            onPress={handleUpdate}
            activeOpacity={0.85}
          >
            <Text style={styles.buttonText}>Update Now</Text>
          </TouchableOpacity>

          <Text style={styles.footnote}>
            You will be redirected to the Google Play Store.
          </Text>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.65)",
    justifyContent: "center",
    alignItems: "center",
    padding: 24,
  },
  card: {
    width: "100%",
    maxWidth: 340,
    backgroundColor: "#fff",
    borderRadius: 24,
    padding: 32,
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.15,
    shadowRadius: 24,
    elevation: 12,
  },
  iconContainer: {
    marginBottom: 20,
  },
  iconCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: BRAND.primaryBg,
    justifyContent: "center",
    alignItems: "center",
  },
  iconEmoji: {
    fontSize: 32,
  },
  title: {
    fontSize: 22,
    fontWeight: "700",
    color: "#1e293b",
    marginBottom: 12,
    textAlign: "center",
    fontFamily: "InterBold",
  },
  message: {
    fontSize: 14,
    lineHeight: 22,
    color: "#64748b",
    textAlign: "center",
    marginBottom: 24,
    fontFamily: "Inter",
  },
  divider: {
    width: "100%",
    height: 1,
    backgroundColor: "#f1f5f9",
    marginBottom: 24,
  },
  button: {
    width: "100%",
    backgroundColor: BRAND.primary,
    paddingVertical: 16,
    borderRadius: 14,
    alignItems: "center",
    marginBottom: 12,
  },
  buttonText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "700",
    fontFamily: "InterBold",
  },
  footnote: {
    fontSize: 12,
    color: "#94a3b8",
    textAlign: "center",
    fontFamily: "Inter",
  },
});
