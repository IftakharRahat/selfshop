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

function StepCard({ step, text }: { step: string; text: string }) {
  return (
    <View style={styles.stepCard}>
      <View style={styles.stepBadge}>
        <Text style={styles.stepBadgeText}>{step}</Text>
      </View>
      <Text style={styles.stepText}>{text}</Text>
    </View>
  );
}

export default function TermsScreen() {
  return (
    <>
      <Stack.Screen
        options={{ headerShown: true, title: "Terms & Conditions", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <View style={styles.headerCard}>
          <Text style={styles.headerTitle}>Terms & Conditions</Text>
          <Text style={styles.headerDate}>Last updated: February 2026</Text>
        </View>

        <View style={styles.content}>
          {/* Delivery Policy */}
          <Section title="Delivery Policy">
            <Text style={styles.subHead}>Order Processing Time</Text>
            <BulletList items={[
              "Orders are typically processed within 1-2 business days",
              "Processing times may vary depending on product availability or peak seasons",
            ]} />

            <Text style={[styles.subHead, { marginTop: 12 }]}>Delivery Timeframe</Text>
            <BulletList items={[
              "We aim to deliver within 3 to 7 business days after processing",
              "Delivery times may vary due to location, courier service, or other factors",
            ]} />

            <Text style={[styles.subHead, { marginTop: 12 }]}>Delivery Methods</Text>
            <BulletList items={[
              "Standard Shipping: Estimated 3-7 business days",
              "Express Shipping: Faster delivery at additional charge (if available)",
            ]} />

            <Text style={[styles.subHead, { marginTop: 12 }]}>Shipping Address</Text>
            <BulletList items={[
              "Customers must provide accurate and complete shipping address",
              "SelfShop is not responsible for deliveries to incorrect addresses",
            ]} />

            <Text style={[styles.subHead, { marginTop: 12 }]}>Delivery Confirmation</Text>
            <BulletList items={[
              "Confirmation via email or SMS with tracking information",
              "Digital products delivered via email or SelfShop account",
            ]} />
          </Section>

          {/* Terms in Bangla */}
          <Section title="শর্তাবলী (Terms in Bangla)">
            <Text style={styles.bodyText}>
              একজন ড্রপ-শিপার হিসাবে আপনাকে নিজের মত করে আপনার ষ্টোরের জন্য প্রোডাক্ট Delivery & Return
              করার জন্য একটি পলিসি সেট করে নিতে হবে এবং তা প্রতিটি কাস্টমারকে অর্ডারের আগেই জানিয়ে দিতে হবে।
            </Text>

            <Text style={[styles.subHead, { marginTop: 14 }]}>প্রোডাক্ট ডেলিভারি</Text>
            <Text style={styles.bodyText}>
              ড্রপ-শপের সকল পার্সেল ক্লোজড বক্স ডেলিভারি হবে অর্থাৎ ডেলিভারির সময় আগে পেমেন্ট করে পার্সেল রিসিভ করতে হবে।
              পছন্দ না হলে ডেলিভারি চার্জ দিয়ে প্রোডাক্ট রিটার্ন করতে পারবে।
            </Text>

            <Text style={[styles.subHead, { marginTop: 14 }]}>রিটার্ন পলিসি</Text>
            <Text style={styles.bodyText}>
              প্রোডাক্ট ডেলিভারি পাবার পর বাসায় নিয়ে অবশ্যই ফুল আনবক্সিং ভিডিও করতে হবে।
              আমাদের দিক থেকে ভুল প্রোডাক্ট ডেলিভারি হলে আমরা নিজ খরচে রিপ্লেস করে দেবো।
            </Text>

            <InfoBox
              text="⚠️ প্রোডাক্ট পাঠানোর আগে অবশ্যই ভালভাবে প্যাকিং করতে হবে। রিসেলেবল কন্ডিশনে না থাকলে রিটার্ন রিকোয়েস্ট একসেপ্ট করা হবেনা।"
              color="#D97706"
            />
          </Section>

          {/* Warranty Process */}
          <Section title="ওয়ারেন্টি/রিটার্ন প্রসেস">
            <StepCard step="স্টেপ ১" text="প্রোডাক্ট আমাদের কাছে পাঠানোর পরে অবশ্যই বুকিং এর স্লিপ WhatsApp করবেন 01976367981" />
            <StepCard step="স্টেপ ২" text="প্রোডাক্ট চেক করা হবে এবং কাস্টমারের অভিযোগ সত্য প্রমাণিত হলে প্রয়োজনীয় পদক্ষেপ নেয়া হবে" />
            <StepCard step="স্টেপ ৩" text="ফল্ট না থাকলে কুরিয়ার ফী পাবার পর সেম প্রোডাক্ট আবার পাঠানো হবে" />
            <StepCard step="স্টেপ ৪" text="ফল্ট পাওয়া গেলে আমাদের নিজেদের খরচে রিপ্লেস করে পাঠানো হবে" />
          </Section>

          {/* Non-returnable */}
          <Section title="যেক্ষেত্রে রিটার্ন প্রযোজ্য নয়">
            <BulletList items={[
              "প্রোডাক্ট এ বার্ন বা ফিজিক্যাল ড্যামেজ হলে",
              "ইন্ট্যাক্ট সিল বা স্টিকার তুলে ফেলা হলে",
              "স্ক্র্যাচ বা দাগ থাকলে বা রিসেলেবল কন্ডিশনে না থাকলে",
              "এক্সেসরিস বা চার্জারের কোন ওয়ারেন্টি নেই",
              "গিফট আইটেম বা বিনামূল্যের পুরষ্কারের ওয়ারেন্টি নেই",
              "থার্ড পার্টি কম্প্যাটিবিলিটি ইস্যু",
            ]} />
          </Section>

          {/* Delivery Charges */}
          <Section title="ডেলিভারি চার্জ ও সময়">
            <Text style={styles.bodyText}>
              ঢাকার ভিতরে: কুরিয়ারে হ্যান্ডওভার করার ১-৩ দিনের মধ্যে। ঢাকার বাইরে: ২-৫ দিনে ডেলিভারি।
              রিটার্নের ক্ষেত্রে ৫-১৫ দিন সময় লাগতে পারে।
            </Text>
            <InfoBox
              text="⚠️ অর্ডার কনফার্ম করার পূর্বেই, প্রতিটি কাস্টোমারকে টার্মস জানাবেন। রিটার্ন রেট বেশী হলে একাউন্ট সাসপেন্ড হবে।"
              color="#DC2626"
            />
          </Section>

          {/* Subscription */}
          <Section title="Subscription পলিসি">
            <Text style={styles.bodyText}>
              একবার সাবস্ক্রিপশন ফি দিয়ে সাবস্ক্রিপশন নেওয়ার পর তা রিফান্ড যোগ্য নয়।
              সাবস্ক্রিপশনের মেয়াদ ১ বছর পর্যন্ত।
            </Text>
          </Section>

          {/* Contact */}
          <View style={styles.contactCard}>
            <Text style={styles.contactTitle}>Contact Us</Text>
            <Pressable onPress={() => Linking.openURL("tel:+8801976367981")}>
              <Text style={{ fontSize: 13, color: ACCENT, fontWeight: "600" }}>📞 01976367981</Text>
            </Pressable>
            <Pressable onPress={() => Linking.openURL("mailto:support@selfshop.com")} style={{ marginTop: 4 }}>
              <Text style={{ fontSize: 13, color: ACCENT, fontWeight: "600" }}>📧 support@selfshop.com</Text>
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
  headerDate: { fontSize: 11, color: "#9CA3AF", marginTop: 4 },
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
  infoBoxText: { fontSize: 12, fontWeight: "600", lineHeight: 18 },
  stepCard: {
    flexDirection: "row", alignItems: "flex-start", gap: 10,
    backgroundColor: "#F8F8FA", borderRadius: 10, padding: 12, marginBottom: 8,
  },
  stepBadge: { backgroundColor: ACCENT, borderRadius: 6, paddingHorizontal: 8, paddingVertical: 4 },
  stepBadgeText: { fontSize: 10, fontWeight: "800", color: "#fff" },
  stepText: { fontSize: 12, color: "#4B5563", lineHeight: 18, flex: 1 },
  contactCard: {
    backgroundColor: "#fff", borderRadius: 16, padding: 16,
    borderWidth: 1, borderColor: "#F0F0F5", alignItems: "center",
  },
  contactTitle: { fontSize: 15, fontWeight: "700", color: "#1A1A2E", marginBottom: 8 },
});
