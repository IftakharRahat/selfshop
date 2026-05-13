import { View, ScrollView, StyleSheet, Pressable, Share } from "react-native";
import { Text } from "tamagui";
import { Stack, router } from "expo-router";
import { useQuery } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";

const TAKA = "\u09F3";

function positiveNumber(value: unknown): number {
  const parsed = Number(value ?? 0);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  const safeNum = Number.isFinite(num) ? num : 0;
  return `${TAKA}${safeNum.toLocaleString("en-BD", {
    maximumFractionDigits: 0,
  })}`;
}

function MenuItem({
  icon,
  label,
  subtitle,
  badge,
  onPress,
}: {
  icon: string;
  label: string;
  subtitle: string;
  badge?: number;
  onPress?: () => void;
}) {
  return (
    <Pressable
      style={({ pressed }) => [
        styles.menuItem,
        pressed && { backgroundColor: "#FAFAFA" },
      ]}
      onPress={onPress}
    >
      <View style={styles.menuItemIcon}>
        <Ionicons name={icon as any} size={20} color="#E5005F" />
      </View>
      <View style={styles.menuItemContent}>
        <Text fontSize="$4" fontWeight="600" color="#1A1A2E">
          {label}
        </Text>
        <Text fontSize="$2" color="#8E8E93">
          {subtitle}
        </Text>
      </View>
      <View style={styles.menuItemRight}>
        {badge !== undefined && (
          <View style={styles.menuBadge}>
            <Text fontSize={10} fontWeight="bold" color="#fff">
              {badge}
            </Text>
          </View>
        )}
        <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
      </View>
    </Pressable>
  );
}

export default function SettingsScreen() {
  const announcementsQuery = useQuery({
    queryKey: ["announcements"],
    queryFn: async () => {
      const { data } = await apiClient.get("/announcements");
      return data?.data ?? data ?? { announcements: [] };
    },
  });

  const profileQuery = useQuery({
    queryKey: ["profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user");
      return data?.data ?? data;
    },
  });

  const basicInfoQuery = useQuery({
    queryKey: ["basic-info"],
    queryFn: async () => {
      const { data } = await apiClient.get("/basic-info");
      return data?.data ?? data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const announcements = announcementsQuery.data?.announcements ?? [];
  const referralCode = profileQuery.data?.my_referral_code ?? "";
  const referralSettings = profileQuery.data?.referral_settings ?? {};
  const basicInfoReferralAmount = positiveNumber(
    basicInfoQuery.data?.referral_bonus_amount ?? basicInfoQuery.data?.bonus_percent,
  );
  const personalReferralAmount = positiveNumber(
    referralSettings?.personal_referrer_bonus_amount ?? profileQuery.data?.bonus_percent,
  );
  const defaultReferralAmount = positiveNumber(referralSettings?.default_referrer_bonus_amount);
  const configuredReferralAmount = positiveNumber(referralSettings?.referrer_bonus_amount);
  const referrerBonusAmount =
    configuredReferralAmount || personalReferralAmount || defaultReferralAmount || basicInfoReferralAmount;
  const hasReferrerReward = referrerBonusAmount > 0;
  const inviteSubtitle = hasReferrerReward
    ? `You earn ${formatCurrency(referrerBonusAmount)} when they subscribe`
    : referralCode
      ? `Code: ${referralCode}`
      : "Share your referral link";

  const handleInviteFriends = async () => {
    if (!referralCode) {
      toast.error("Referral code not available");
      return;
    }
    const referralLink = `https://selfshop.com.bd/register?ref=${referralCode}`;
    try {
      const rewardLine = hasReferrerReward
        ? `Earn ${formatCurrency(referrerBonusAmount)} referral bonus when someone subscribes with my code.`
        : `Use my referral code: ${referralCode}`;
      await Share.share({
        message: `Join SelfShop and start your reselling business! ${rewardLine}\n\nReferral code: ${referralCode}\nSign up here: ${referralLink}`,
        url: referralLink,
        title: "Join SelfShop",
      });
    } catch {
      toast.error("Failed to share");
    }
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Settings",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8F8" },
        }}
      />
      <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
        {/* Account */}
        <View style={styles.menuGroup}>
          <Text fontSize="$3" fontWeight="bold" color="#8E8E93" mb="$2" ml="$1">
            Account
          </Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="lock-closed-outline"
              label="Change Password"
              subtitle="Update your account password"
              onPress={() => router.push("/account/change-password")}
            />
            <MenuItem
              icon="card-outline"
              label="Payment Methods"
              subtitle="Add or remove payment cards"
              onPress={() => router.push("/account/payment-methods" as any)}
            />
          </View>
        </View>

        {/* Reseller Tools */}
        <View style={styles.menuGroup}>
          <Text fontSize="$3" fontWeight="bold" color="#8E8E93" mb="$2" ml="$1">
            Reseller Tools
          </Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="cash-outline"
              label="Income History"
              subtitle="View your earnings"
              onPress={() => router.push("/account/income-history" as any)}
            />
            <MenuItem
              icon="trending-up-outline"
              label="Order Income"
              subtitle="Per-order profit breakdown"
              onPress={() => router.push("/account/order-income" as any)}
            />
            <MenuItem
              icon="shield-checkmark-outline"
              label="Fraud Checker"
              subtitle="Verify customer phone numbers"
              onPress={() => router.push("/account/fraud-checker" as any)}
            />
            <MenuItem
              icon="add-circle-outline"
              label="Product Request"
              subtitle="Request new products"
              onPress={() => router.push("/account/product-request" as any)}
            />
            <MenuItem
              icon="trophy-outline"
              label="Event Challenges"
              subtitle="Participate and earn rewards"
              onPress={() => router.push("/account/events" as any)}
            />
            <MenuItem
              icon="book-outline"
              label="Free Courses"
              subtitle="Learn and grow for free"
              onPress={() => router.push("/account/free-courses" as any)}
            />
            <MenuItem
              icon="people-outline"
              label="Team Members"
              subtitle="View your team"
              onPress={() => router.push("/account/team-members" as any)}
            />
          </View>
        </View>

        {/* Support */}
        <View style={styles.menuGroup}>
          <Text fontSize="$3" fontWeight="bold" color="#8E8E93" mb="$2" ml="$1">
            Support
          </Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="help-circle-outline"
              label="FAQ"
              subtitle="Frequently asked questions"
              onPress={() => router.push("/account/faq")}
            />
            <MenuItem
              icon="megaphone-outline"
              label="Announcements"
              subtitle="Latest news and updates"
              badge={announcements.length > 0 ? announcements.length : undefined}
              onPress={() => router.push("/account/announcements" as any)}
            />
            <MenuItem
              icon="chatbubble-ellipses-outline"
              label="Contact & Support"
              subtitle="Get help or send a message"
              onPress={() => router.push("/contact" as any)}
            />
          </View>
        </View>

        {/* Legal & Info */}
        <View style={styles.menuGroup}>
          <Text fontSize="$3" fontWeight="bold" color="#8E8E93" mb="$2" ml="$1">
            Legal & Info
          </Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="information-circle-outline"
              label="About Us"
              subtitle="Learn about SelfShop"
              onPress={() => router.push("/about-us" as any)}
            />
            <MenuItem
              icon="shield-outline"
              label="Privacy Policy"
              subtitle="How we handle your data"
              onPress={() => router.push("/privacy-policy" as any)}
            />
            <MenuItem
              icon="document-text-outline"
              label="Terms & Conditions"
              subtitle="Terms of service"
              onPress={() => router.push("/terms-and-conditions" as any)}
            />
            <MenuItem
              icon="arrow-undo-outline"
              label="Return Policy"
              subtitle="Returns and refunds"
              onPress={() => router.push("/return-policy" as any)}
            />
          </View>
        </View>

        {/* More */}
        <View style={styles.menuGroup}>
          <Text fontSize="$3" fontWeight="bold" color="#8E8E93" mb="$2" ml="$1">
            More
          </Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="star-outline"
              label="Rate Us"
              subtitle="Share your feedback"
            />
            <MenuItem
              icon="share-social-outline"
              label="Invite Friends"
              subtitle={inviteSubtitle}
              onPress={handleInviteFriends}
            />
          </View>
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F8F8F8",
  },
  menuGroup: {
    paddingHorizontal: 20,
    marginTop: 16,
  },
  menuCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F0",
  },
  menuItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#F5F5F5",
  },
  menuItemIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  menuItemContent: {
    flex: 1,
    gap: 1,
  },
  menuItemRight: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  menuBadge: {
    backgroundColor: "#E5005F",
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 6,
  },
});
