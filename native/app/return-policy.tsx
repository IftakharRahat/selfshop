import { ScrollView, StyleSheet, View, Linking, Pressable } from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

const ACCENT = "#E5005F";

function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {children}
    </View>
  );
}

function BulletList({ items }: { items: string[] }) {
  return (
    <View style={{ gap: 6 }}>
      {items.map((item, i) => (
        <View key={i} style={styles.bulletRow}>
          <Text style={styles.bullet}>•</Text>
          <Text style={styles.bulletText}>{item}</Text>
        </View>
      ))}
    </View>
  );
}

function InfoBox({ text, color }: { text: string; color: string }) {
  return (
    <View style={[styles.infoBox, { borderLeftColor: color, backgroundColor: `${color}10` }]}>
      <Text style={[styles.infoBoxText, { color }]}>{text}</Text>
    </View>
  );
}

export default function ReturnPolicyScreen() {
  return (
    <>
      <Stack.Screen
        options={{ headerShown: true, title: "Return Policy", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <View style={styles.headerCard}>
          <Text style={styles.headerTitle}>Return & Refund Policy</Text>
          <Text style={styles.headerSub}>
            Thank you for shopping with SelfShop! If you are not completely satisfied with your
            purchase, you may request a return or refund in accordance with the policy below.
          </Text>
        </View>

        <View style={styles.content}>
          <Section title="1. Return Policy">
            <Text style={styles.bodyText}>
              You may apply for a return within <Text style={{ fontWeight: "700" }}>7 days</Text> of receiving your product.
            </Text>
            <Text style={[styles.bodyText, { marginTop: 8, marginBottom: 4 }]}>Returns are only accepted under these conditions:</Text>
            <BulletList items={[
              "Wrong product delivered",
              "Damaged or defective product",
              "Product not as described",
            ]} />
            <Text style={[styles.bodyText, { marginTop: 8 }]}>
              The return process usually takes <Text style={{ fontWeight: "700" }}>7–10 working days</Text> to complete after approval.
            </Text>
            <InfoBox
              text="⚠️ Returned items must be unused, undamaged, and in their original packaging."
              color="#D97706"
            />
          </Section>

          <Section title="2. Refund Policy">
            <BulletList items={[
              "Refunds are issued only after the returned product has been inspected and approved",
              "Refund process typically completed within 7–10 working days after approval",
              "Refunds will be made using the same payment method used during purchase",
            ]} />
            <InfoBox
              text="For Cash on Delivery (COD) orders, refunds will be processed via bank transfer or mobile financial services."
              color="#2563EB"
            />
          </Section>

          <Section title="3. Cancellation Policy">
            <BulletList items={[
              "You may cancel your order anytime before confirmation or shipment",
              "Once the order has been shipped, cancellation requests will not be accepted",
            ]} />
          </Section>

          <Section title="4. No Return / No Refund">
            <InfoBox
              text="For certain products, a 'No Return / No Refund' policy may apply. This will be clearly mentioned in the product description."
              color="#DC2626"
            />
          </Section>

          {/* Contact */}
          <View style={styles.contactCard}>
            <Text style={styles.contactTitle}>Contact Us</Text>
            <Text style={styles.contactSub}>For questions regarding returns or refunds:</Text>
            <View style={{ gap: 6, marginTop: 8 }}>
              <Pressable style={styles.contactRow} onPress={() => Linking.openURL("mailto:support@selfshop.com")}>
                <Ionicons name="mail-outline" size={16} color={ACCENT} />
                <Text style={styles.contactLink}>support@selfshop.com</Text>
              </Pressable>
              <Pressable style={styles.contactRow} onPress={() => Linking.openURL("tel:+8801976367981")}>
                <Ionicons name="call-outline" size={16} color={ACCENT} />
                <Text style={styles.contactLink}>+8801976367981</Text>
              </Pressable>
              <Pressable style={styles.contactRow} onPress={() => Linking.openURL("https://selfshop.com.bd")}>
                <Ionicons name="globe-outline" size={16} color={ACCENT} />
                <Text style={styles.contactLink}>selfshop.com.bd</Text>
              </Pressable>
            </View>
          </View>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  headerCard: {
    margin: 16, padding: 20, backgroundColor: "#fff", borderRadius: 16,
    borderWidth: 1, borderColor: "#F0F0F5", alignItems: "center",
  },
  headerTitle: { fontSize: 22, fontWeight: "800", color: "#1A1A2E", marginBottom: 8 },
  headerSub: { fontSize: 12, color: "#6B7280", lineHeight: 18, textAlign: "center" },
  content: { paddingHorizontal: 16, gap: 12 },
  section: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: "#F0F0F5",
  },
  sectionTitle: { fontSize: 15, fontWeight: "700", color: "#1A1A2E", marginBottom: 10, borderBottomWidth: 1, borderBottomColor: "#F0F0F5", paddingBottom: 8 },
  bodyText: { fontSize: 13, color: "#4B5563", lineHeight: 20 },
  bulletRow: { flexDirection: "row", gap: 6 },
  bullet: { fontSize: 13, color: "#9CA3AF", marginTop: 1 },
  bulletText: { fontSize: 12, color: "#4B5563", lineHeight: 18, flex: 1 },
  infoBox: { borderLeftWidth: 4, borderRadius: 8, padding: 12, marginTop: 10 },
  infoBoxText: { fontSize: 12, fontWeight: "600", lineHeight: 18 },
  contactCard: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: "#F0F0F5", alignItems: "center",
  },
  contactTitle: { fontSize: 15, fontWeight: "700", color: "#1A1A2E", marginBottom: 4 },
  contactSub: { fontSize: 12, color: "#6B7280" },
  contactRow: { flexDirection: "row", alignItems: "center", gap: 6 },
  contactLink: { fontSize: 13, color: ACCENT, fontWeight: "500" },
});
