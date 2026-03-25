import { useState } from "react";
import { View, ScrollView, StyleSheet, Pressable } from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { Ionicons } from "@expo/vector-icons";

const FAQ_DATA = [
  {
    category: "Ordering & Payment",
    icon: "cart-outline" as const,
    faqs: [
      { question: "How do I place an order?", answer: "Browse our products, add items to your cart, and proceed to checkout. You can pay via Cash on Delivery, bKash, Nagad, or Bank Transfer." },
      { question: "What payment methods do you accept?", answer: "We accept Cash on Delivery (COD), bKash, Nagad, Bank Transfer, and Credit/Debit Cards." },
      { question: "Can I modify my order after placing it?", answer: "You can request modifications while your order is still in 'Pending' status. Contact our support team for assistance." },
    ],
  },
  {
    category: "Shipping & Delivery",
    icon: "bicycle-outline" as const,
    faqs: [
      { question: "How long does delivery take?", answer: "Standard delivery takes 2-5 business days within Dhaka and 5-7 business days for other districts." },
      { question: "How can I track my order?", answer: "Go to 'My Orders' in your account and click on the order to see real-time delivery updates." },
      { question: "Do you deliver outside Dhaka?", answer: "Yes, we deliver nationwide across Bangladesh." },
    ],
  },
  {
    category: "Returns & Refunds",
    icon: "refresh-outline" as const,
    faqs: [
      { question: "What is your return policy?", answer: "You can request returns within 7 days of delivery for defective or incorrect items." },
      { question: "When will I receive my refund?", answer: "Refunds are processed within 5-7 business days after the returned item is received and inspected." },
    ],
  },
  {
    category: "Account & Security",
    icon: "shield-checkmark-outline" as const,
    faqs: [
      { question: "How do I reset my password?", answer: "Click 'Forgot Password' on the login page, enter your email, and follow the reset link." },
      { question: "Is my payment information secure?", answer: "Yes, we use industry-standard encryption and secure payment gateways." },
    ],
  },
];

export default function FAQScreen() {
  const [expandedCategory, setExpandedCategory] = useState<number | null>(0);
  const [expandedFaq, setExpandedFaq] = useState<string | null>(null);

  return (
    <>
      <Stack.Screen options={{ headerShown: true, title: "FAQ", headerShadowVisible: false, headerStyle: { backgroundColor: "#fff" } }} />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        <View style={styles.headerSection}>
          <Text fontSize="$3" color="#8E8E93">Find answers to commonly asked questions</Text>
        </View>
        {FAQ_DATA.map((cat, catIndex) => (
          <View key={cat.category} style={styles.categorySection}>
            <Pressable style={({ pressed }) => [styles.categoryHeader, pressed && { opacity: 0.8 }]} onPress={() => setExpandedCategory(expandedCategory === catIndex ? null : catIndex)}>
              <View style={styles.categoryLeft}>
                <View style={styles.categoryIcon}><Ionicons name={cat.icon} size={20} color="#E5005F" /></View>
                <Text fontSize="$4" fontWeight="bold" color="#1A1A2E">{cat.category}</Text>
              </View>
              <Ionicons name={expandedCategory === catIndex ? "chevron-up" : "chevron-down"} size={18} color="#8E8E93" />
            </Pressable>
            {expandedCategory === catIndex && (
              <View style={styles.faqList}>
                {cat.faqs.map((faq) => {
                  const faqKey = `${catIndex}-${faq.question}`;
                  const isOpen = expandedFaq === faqKey;
                  return (
                    <Pressable key={faq.question} style={styles.faqItem} onPress={() => setExpandedFaq(isOpen ? null : faqKey)}>
                      <View style={styles.faqQuestion}>
                        <Text fontSize="$3" fontWeight="600" color="#1A1A2E" flex={1}>{faq.question}</Text>
                        <Ionicons name={isOpen ? "remove-circle-outline" : "add-circle-outline"} size={20} color="#E5005F" />
                      </View>
                      {isOpen && <Text fontSize="$3" color="#8E8E93" mt="$2" lineHeight={20}>{faq.answer}</Text>}
                    </Pressable>
                  );
                })}
              </View>
            )}
          </View>
        ))}
        <View style={{ height: 30 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8F8" },
  headerSection: { paddingHorizontal: 20, paddingVertical: 12 },
  categorySection: { marginHorizontal: 16, marginBottom: 10, backgroundColor: "#fff", borderRadius: 14, borderWidth: 1, borderColor: "#F0F0F0", overflow: "hidden" },
  categoryHeader: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", padding: 16 },
  categoryLeft: { flexDirection: "row", alignItems: "center", gap: 12 },
  categoryIcon: { width: 36, height: 36, borderRadius: 18, backgroundColor: "#FDF2F8", justifyContent: "center", alignItems: "center" },
  faqList: { borderTopWidth: 1, borderTopColor: "#F5F5F5" },
  faqItem: { paddingHorizontal: 16, paddingVertical: 14, borderBottomWidth: 1, borderBottomColor: "#F5F5F5" },
  faqQuestion: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", gap: 12 },
});
