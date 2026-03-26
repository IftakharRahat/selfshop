import { ScrollView, StyleSheet, View, Linking, Pressable } from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

const ACCENT = "#E5005F";

const FEATURES = [
  { icon: "cube-outline", title: "Wide Product Range", desc: "Thousands of products from trusted suppliers" },
  { icon: "shield-checkmark-outline", title: "Quality Assurance", desc: "Every product verified before listing" },
  { icon: "rocket-outline", title: "Dropshipping Ready", desc: "Start selling with zero inventory" },
  { icon: "cash-outline", title: "Competitive Pricing", desc: "Best B2B wholesale prices for resellers" },
  { icon: "people-outline", title: "32,000+ Users", desc: "Join a growing community of entrepreneurs" },
  { icon: "headset-outline", title: "Dedicated Support", desc: "Expert team to help you succeed" },
];

export default function AboutUsScreen() {
  return (
    <>
      <Stack.Screen
        options={{ headerShown: true, title: "About Us", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Hero */}
        <View style={styles.heroCard}>
          <View style={styles.heroIconBg}>
            <Ionicons name="storefront" size={28} color="#fff" />
          </View>
          <Text style={styles.heroTitle}>About SelfShop</Text>
          <Text style={styles.heroDesc}>
            SelfShop is an innovative online platform dedicated to empowering entrepreneurs,
            dropshippers, and resellers with high-quality products and an exceptional shopping
            experience. Established with the vision of transforming the eCommerce landscape,
            SelfShop has rapidly grown to a community of over 32,000 users who leverage our
            platform to build and expand their businesses.
          </Text>
        </View>

        {/* Mission */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Ionicons name="flag" size={18} color={ACCENT} />
            <Text style={styles.sectionTitle}>Our Mission</Text>
          </View>
          <Text style={styles.bodyText}>
            We envision a world where anyone can start and grow their own online business,
            regardless of their background or experience. Our mission is to provide the tools,
            products, and support needed to make entrepreneurship accessible to everyone in Bangladesh.
          </Text>
        </View>

        {/* What We Offer */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionHeader}>
            <Ionicons name="sparkles" size={18} color={ACCENT} />
            <Text style={styles.sectionTitle}>What We Offer</Text>
          </View>
          {FEATURES.map((f) => (
            <View key={f.title} style={styles.featureRow}>
              <View style={styles.featureIcon}>
                <Ionicons name={f.icon as any} size={20} color={ACCENT} />
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.featureTitle}>{f.title}</Text>
                <Text style={styles.featureDesc}>{f.desc}</Text>
              </View>
            </View>
          ))}
        </View>

        {/* Contact */}
        <View style={styles.contactCard}>
          <Text style={styles.contactTitle}>Get in Touch</Text>
          <Pressable style={styles.contactRow} onPress={() => Linking.openURL("mailto:support@selfshop.com")}>
            <Ionicons name="mail-outline" size={18} color={ACCENT} />
            <Text style={styles.contactText}>support@selfshop.com</Text>
          </Pressable>
          <Pressable style={styles.contactRow} onPress={() => Linking.openURL("tel:+8801976367981")}>
            <Ionicons name="call-outline" size={18} color={ACCENT} />
            <Text style={styles.contactText}>+8801976367981</Text>
          </Pressable>
          <Pressable style={styles.contactRow} onPress={() => Linking.openURL("https://selfshop.com.bd")}>
            <Ionicons name="globe-outline" size={18} color={ACCENT} />
            <Text style={styles.contactText}>selfshop.com.bd</Text>
          </Pressable>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  heroCard: {
    margin: 16,
    padding: 24,
    backgroundColor: ACCENT,
    borderRadius: 20,
    alignItems: "center",
  },
  heroIconBg: {
    width: 56,
    height: 56,
    borderRadius: 18,
    backgroundColor: "rgba(255,255,255,0.2)",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 16,
  },
  heroTitle: { fontSize: 24, fontWeight: "800", color: "#fff", marginBottom: 12 },
  heroDesc: { fontSize: 13, color: "rgba(255,255,255,0.9)", lineHeight: 20, textAlign: "center" },

  sectionCard: {
    marginHorizontal: 16,
    marginBottom: 12,
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  sectionHeader: { flexDirection: "row", alignItems: "center", gap: 8, marginBottom: 12 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E" },
  bodyText: { fontSize: 13, color: "#4B5563", lineHeight: 20 },

  featureRow: { flexDirection: "row", alignItems: "center", gap: 12, marginBottom: 12 },
  featureIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  featureTitle: { fontSize: 14, fontWeight: "600", color: "#1A1A2E" },
  featureDesc: { fontSize: 12, color: "#6B7280", marginTop: 1 },

  contactCard: {
    marginHorizontal: 16,
    marginBottom: 12,
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    alignItems: "center",
  },
  contactTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E", marginBottom: 12 },
  contactRow: { flexDirection: "row", alignItems: "center", gap: 8, marginBottom: 8 },
  contactText: { fontSize: 13, color: ACCENT, fontWeight: "500" },
});
