import { useState } from "react";
import {
  ScrollView,
  StyleSheet,
  View,
  TextInput,
  Pressable,
  Linking,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { useQuery } from "@tanstack/react-query";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

const API_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

/* ── Build contact methods dynamically from API data ── */
function buildContactMethods(data: any) {
  const methods: {
    icon: keyof typeof Ionicons.glyphMap;
    label: string;
    value: string;
    color: string;
    action: string;
  }[] = [];

  if (data?.wp_number || data?.wp_link) {
    const num = (data.wp_number || "").replace(/\D/g, "");
    methods.push({
      icon: "logo-whatsapp",
      label: "WhatsApp",
      value: data.wp_number || num,
      color: "#25D366",
      action: data.wp_link || `https://wa.me/${num}`,
    });
  }

  if (data?.phone_one) {
    methods.push({
      icon: "call-outline",
      label: "Phone",
      value: data.phone_one,
      color: "#2563EB",
      action: `tel:${data.phone_one}`,
    });
  }

  if (data?.email) {
    methods.push({
      icon: "mail-outline",
      label: "Email",
      value: data.email,
      color: ACCENT,
      action: `mailto:${data.email}`,
    });
  }

  if (data?.facebook) {
    methods.push({
      icon: "logo-facebook",
      label: "Facebook",
      value: "Facebook Page",
      color: "#1877F2",
      action: data.facebook,
    });
  }

  if (data?.instagram) {
    methods.push({
      icon: "logo-instagram",
      label: "Instagram",
      value: "Instagram",
      color: "#E4405F",
      action: data.instagram,
    });
  }

  if (data?.youtube) {
    methods.push({
      icon: "logo-youtube",
      label: "YouTube",
      value: "YouTube Channel",
      color: "#FF0000",
      action: data.youtube,
    });
  }

  if (data?.tiktok) {
    methods.push({
      icon: "logo-tiktok",
      label: "TikTok",
      value: "TikTok",
      color: "#010101",
      action: data.tiktok,
    });
  }

  if (data?.messanger_link) {
    methods.push({
      icon: "chatbubble-ellipses-outline",
      label: "Messenger",
      value: "Messenger",
      color: "#0084FF",
      action: data.messanger_link,
    });
  }

  return methods;
}

/* ── Hardcoded fallback if API fails ── */
const FALLBACK_METHODS = [
  { icon: "logo-whatsapp" as const, label: "WhatsApp", value: "+8801976367981", color: "#25D366", action: "https://wa.me/8801976367981" },
  { icon: "call-outline" as const, label: "Phone", value: "+8801976367981", color: "#2563EB", action: "tel:+8801976367981" },
  { icon: "mail-outline" as const, label: "Email", value: "contact@selfshop.com.bd", color: ACCENT, action: "mailto:contact@selfshop.com.bd" },
  { icon: "logo-facebook" as const, label: "Facebook", value: "SelfShop BD", color: "#1877F2", action: "https://facebook.com/selfshopbd" },
];

export default function ContactScreen() {
  const [form, setForm] = useState({ name: "", email: "", title: "", message: "" });
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const contactQuery = useQuery({
    queryKey: ["contact-info"],
    queryFn: async () => {
      try {
        const { data } = await apiClient.get("/contact-info");
        return data?.data ?? data ?? null;
      } catch {
        return null;
      }
    },
  });

  const contactData = contactQuery.data;
  const contactMethods = contactData ? buildContactMethods(contactData) : FALLBACK_METHODS;
  const address = contactData?.address || "Momotaz Plaza, 6th Floor, Flat-C, PTI More, College Road, Sadar Lakshmipur, Lakshmipur.";

  const validate = () => {
    const e: Record<string, string> = {};
    if (form.name.trim().length < 2) e.name = "Name must be at least 2 characters";
    if (!/\S+@\S+\.\S+/.test(form.email)) e.email = "Please enter a valid email";
    if (form.title.trim().length < 3) e.title = "Title must be at least 3 characters";
    if (form.message.trim().length < 10) e.message = "Message must be at least 10 characters";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;
    setSubmitting(true);
    try {
      await new Promise((r) => setTimeout(r, 1500));
      toast.success("Message sent successfully!");
      setForm({ name: "", email: "", title: "", message: "" });
    } catch {
      toast.error("Could not send message. Please try again.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <>
      <Stack.Screen options={{ headerShown: true, title: "Contact & Support", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
      <KeyboardAvoidingView style={{ flex: 1 }} behavior={Platform.OS === "ios" ? "padding" : undefined}>
        <ScrollView style={styles.container} showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
          {/* Quick Contact Methods */}
          <View style={styles.methodsGrid}>
            {contactMethods.map((m) => (
              <Pressable
                key={m.label}
                style={({ pressed }) => [styles.methodCard, pressed && { opacity: 0.8 }]}
                onPress={() => Linking.openURL(m.action)}
              >
                <View style={[styles.methodIcon, { backgroundColor: `${m.color}15` }]}>
                  <Ionicons name={m.icon as any} size={22} color={m.color} />
                </View>
                <Text style={styles.methodLabel}>{m.label}</Text>
                <Text style={styles.methodValue} numberOfLines={1}>{m.value}</Text>
              </Pressable>
            ))}
          </View>

          {/* Office Address */}
          <View style={styles.addressCard}>
            <View style={styles.addressIcon}>
              <Ionicons name="location" size={20} color={ACCENT} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.addressTitle}>Office Address</Text>
              <Text style={styles.addressText}>{address}</Text>
            </View>
          </View>

          {/* Contact Form */}
          <View style={styles.formCard}>
            <Text style={styles.formTitle}>Send us a Message</Text>
            <Text style={styles.formSub}>We'll get back to you within 24 hours</Text>

            <View style={styles.formRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.inputLabel}>Your Name</Text>
                <TextInput
                  style={[styles.input, errors.name ? styles.inputError : undefined]}
                  placeholder="Enter name"
                  placeholderTextColor="#9CA3AF"
                  value={form.name}
                  onChangeText={(v) => { setForm((p) => ({ ...p, name: v })); setErrors((e) => ({ ...e, name: "" })); }}
                />
                {errors.name ? <Text style={styles.errorText}>{errors.name}</Text> : null}
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.inputLabel}>Email</Text>
                <TextInput
                  style={[styles.input, errors.email ? styles.inputError : undefined]}
                  placeholder="Enter email"
                  placeholderTextColor="#9CA3AF"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  value={form.email}
                  onChangeText={(v) => { setForm((p) => ({ ...p, email: v })); setErrors((e) => ({ ...e, email: "" })); }}
                />
                {errors.email ? <Text style={styles.errorText}>{errors.email}</Text> : null}
              </View>
            </View>

            <Text style={styles.inputLabel}>Title</Text>
            <TextInput
              style={[styles.input, errors.title ? styles.inputError : undefined]}
              placeholder="Enter title"
              placeholderTextColor="#9CA3AF"
              value={form.title}
              onChangeText={(v) => { setForm((p) => ({ ...p, title: v })); setErrors((e) => ({ ...e, title: "" })); }}
            />
            {errors.title ? <Text style={styles.errorText}>{errors.title}</Text> : null}

            <Text style={[styles.inputLabel, { marginTop: 12 }]}>Message</Text>
            <TextInput
              style={[styles.input, styles.textArea, errors.message ? styles.inputError : undefined]}
              placeholder="Enter your message"
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={5}
              textAlignVertical="top"
              value={form.message}
              onChangeText={(v) => { setForm((p) => ({ ...p, message: v })); setErrors((e) => ({ ...e, message: "" })); }}
            />
            {errors.message ? <Text style={styles.errorText}>{errors.message}</Text> : null}

            <Pressable
              style={({ pressed }) => [styles.submitButton, pressed && { opacity: 0.85 }, submitting && { opacity: 0.6 }]}
              onPress={handleSubmit}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <>
                  <Ionicons name="send" size={16} color="#fff" />
                  <Text style={styles.submitButtonText}>Send Message</Text>
                </>
              )}
            </Pressable>
          </View>

          <View style={{ height: 40 }} />
        </ScrollView>
      </KeyboardAvoidingView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  methodsGrid: { flexDirection: "row", flexWrap: "wrap", gap: 10, padding: 16 },
  methodCard: {
    width: "47%",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    alignItems: "center",
  },
  methodIcon: { width: 44, height: 44, borderRadius: 14, justifyContent: "center", alignItems: "center", marginBottom: 8 },
  methodLabel: { fontSize: 13, fontWeight: "700", color: "#1A1A2E" },
  methodValue: { fontSize: 10, color: "#6B7280", marginTop: 2 },

  addressCard: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 12,
    marginHorizontal: 16,
    marginBottom: 12,
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  addressIcon: { width: 40, height: 40, borderRadius: 12, backgroundColor: "#FDF2F8", justifyContent: "center", alignItems: "center" },
  addressTitle: { fontSize: 13, fontWeight: "700", color: "#1A1A2E" },
  addressText: { fontSize: 12, color: "#6B7280", lineHeight: 18, marginTop: 2 },

  formCard: {
    marginHorizontal: 16,
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  formTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E" },
  formSub: { fontSize: 12, color: "#9CA3AF", marginBottom: 14 },
  formRow: { flexDirection: "row", gap: 10 },

  inputLabel: { fontSize: 12, fontWeight: "600", color: "#374151", marginBottom: 4, marginTop: 8 },
  input: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: "#1A1A2E",
  },
  inputError: { borderColor: "#EF4444" },
  textArea: { height: 100, textAlignVertical: "top" },
  errorText: { fontSize: 10, color: "#EF4444", marginTop: 2 },

  submitButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    backgroundColor: ACCENT,
    borderRadius: 12,
    paddingVertical: 14,
    marginTop: 16,
  },
  submitButtonText: { fontSize: 14, fontWeight: "700", color: "#fff" },
});
