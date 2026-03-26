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

export default function PrivacyPolicyScreen() {
  return (
    <>
      <Stack.Screen
        options={{ headerShown: true, title: "Privacy Policy", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <View style={styles.headerCard}>
          <Text style={styles.headerTitle}>Privacy Policy</Text>
          <Text style={styles.headerSub}>selfshop.com.bd</Text>
          <Text style={styles.headerDate}>Last updated: February 2026</Text>
        </View>

        <View style={styles.content}>
          <Text style={styles.bodyText}>
            selfshop.com.bd ("we", "us", "our") operates a business-to-business (B2B) online
            platform providing services primarily to companies, merchants, retailers, distributors
            and other business entities in Bangladesh and internationally.
          </Text>

          <Section title="1. Information We Collect">
            <Text style={styles.subHead}>A. Information you provide directly</Text>
            <BulletList items={[
              "Business contact details: name, job title, company name, email, phone, address",
              "Account and registration information: username, password, company details, TIN/VAT",
              "Payment-related information (handled by secure third-party processors)",
              "Communications: messages, support tickets, emails, chat records",
              "Uploaded business documents: trade license, authorization letters",
            ]} />
            <Text style={[styles.subHead, { marginTop: 12 }]}>B. Information collected automatically</Text>
            <BulletList items={[
              "Device & network info: IP address, browser type, OS, device identifiers",
              "Usage data: pages visited, time/date of access, features used, session duration",
              "Cookies and similar technologies",
            ]} />
            <Text style={[styles.subHead, { marginTop: 12 }]}>C. Information from third parties</Text>
            <BulletList items={[
              "Public business directories and company registries",
              "Payment gateway providers (transaction confirmation only)",
              "Business partners or referrers (with consent or legitimate interest)",
            ]} />
          </Section>

          <Section title="2. How We Use Your Information">
            <BulletList items={[
              "Create, administer and manage business accounts",
              "Provide, maintain, improve and personalize our B2B services",
              "Process orders, invoices, payments and deliveries",
              "Communicate about account, orders, support, and updates",
              "Verify business identity and prevent fraud",
              "Send service notices, legal notices and transactional emails",
              "Analyze platform usage and improve user experience",
              "Comply with legal obligations and enforce agreements",
            ]} />
          </Section>

          <Section title="3. Legal Basis for Processing">
            <BulletList items={[
              "Performance of a contract (your business agreement with us)",
              "Legitimate business interests (B2B management, fraud prevention)",
              "Compliance with legal obligations",
              "Consent (where specifically asked, e.g. marketing newsletters)",
            ]} />
          </Section>

          <Section title="4. Sharing of Information">
            <BulletList items={[
              "Service providers (hosting, email, payment processors, analytics)",
              "Logistics/delivery partners for order fulfillment",
              "Banks/payment gateways for transaction processing",
              "Law enforcement, court orders, or regulatory authorities",
              "In connection with mergers, acquisitions, or asset sales",
              "With your explicit consent or direction",
            ]} />
            <InfoBox text="✅ We do not sell personal information to third parties for their own marketing purposes." color="#059669" />
          </Section>

          <Section title="5. Data Storage & Transfers">
            <Text style={styles.bodyText}>
              Your data is primarily stored in secure servers. If data is transferred outside
              Bangladesh, we ensure appropriate safeguards are in place.
            </Text>
          </Section>

          <Section title="6. Data Retention">
            <View style={styles.retentionGrid}>
              {[
                { label: "Active Account", value: "During relationship + 2–5 years" },
                { label: "Marketing Data", value: "Until you unsubscribe" },
                { label: "Logs & Security", value: "6–24 months" },
              ].map((r) => (
                <View key={r.label} style={styles.retentionCard}>
                  <Text style={styles.retentionLabel}>{r.label}</Text>
                  <Text style={styles.retentionValue}>{r.value}</Text>
                </View>
              ))}
            </View>
          </Section>

          <Section title="7. Security">
            <Text style={styles.bodyText}>
              We implement commercially reasonable technical, administrative and physical security
              measures to protect personal information from unauthorized access, loss, misuse or alteration.
            </Text>
          </Section>

          <Section title="8. Your Rights">
            <BulletList items={[
              "Access your personal data",
              "Correct inaccurate data",
              "Request deletion (subject to legal retention obligations)",
              "Object to or restrict certain processing",
              "Withdraw consent (where applicable)",
              "Data portability (where technically feasible)",
            ]} />
          </Section>

          <Section title="9. Cookies & Tracking">
            <Text style={styles.bodyText}>
              We use cookies and similar technologies for essential functionality, performance,
              analytics and (if enabled) marketing. You can manage preferences via browser settings.
            </Text>
          </Section>

          <Section title="10. Children's Privacy">
            <Text style={styles.bodyText}>
              Our services are not directed to individuals under 18 years of age. We do not
              knowingly collect personal information from children.
            </Text>
          </Section>

          <Section title="11. Changes to This Policy">
            <Text style={styles.bodyText}>
              We may update this policy from time to time. The updated version will be posted
              with a revised "Last updated" date.
            </Text>
          </Section>

          {/* Contact */}
          <View style={styles.contactCard}>
            <Text style={styles.contactTitle}>12. Contact Us</Text>
            <Text style={styles.bodyText}>Privacy Officer — selfshop.com.bd</Text>
            <Pressable onPress={() => Linking.openURL("mailto:privacy@selfshop.com.bd")} style={{ marginTop: 8 }}>
              <Text style={{ fontSize: 13, color: ACCENT, fontWeight: "600" }}>📧 privacy@selfshop.com.bd</Text>
            </Pressable>
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
  headerTitle: { fontSize: 22, fontWeight: "800", color: "#1A1A2E" },
  headerSub: { fontSize: 12, color: "#9CA3AF", marginTop: 4 },
  headerDate: { fontSize: 11, color: "#9CA3AF", marginTop: 2 },
  content: { paddingHorizontal: 16, gap: 12 },
  section: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: "#F0F0F5",
  },
  sectionTitle: { fontSize: 15, fontWeight: "700", color: "#1A1A2E", marginBottom: 10, borderBottomWidth: 1, borderBottomColor: "#F0F0F5", paddingBottom: 8 },
  subHead: { fontSize: 13, fontWeight: "700", color: "#374151", marginBottom: 6 },
  bodyText: { fontSize: 13, color: "#4B5563", lineHeight: 20 },
  bulletRow: { flexDirection: "row", gap: 6 },
  bullet: { fontSize: 13, color: "#9CA3AF", marginTop: 1 },
  bulletText: { fontSize: 12, color: "#4B5563", lineHeight: 18, flex: 1 },
  infoBox: { borderLeftWidth: 4, borderRadius: 8, padding: 12, marginTop: 10 },
  infoBoxText: { fontSize: 12, fontWeight: "600" },
  retentionGrid: { flexDirection: "row", gap: 8, flexWrap: "wrap" },
  retentionCard: {
    flex: 1, minWidth: "30%", backgroundColor: "#F8F8FA", borderRadius: 10,
    padding: 10, alignItems: "center",
  },
  retentionLabel: { fontSize: 11, fontWeight: "700", color: "#1A1A2E", textAlign: "center" },
  retentionValue: { fontSize: 10, color: "#6B7280", marginTop: 4, textAlign: "center" },
  contactCard: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: "#F0F0F5", alignItems: "center",
  },
  contactTitle: { fontSize: 15, fontWeight: "700", color: "#1A1A2E", marginBottom: 8 },
});
