import { useCallback } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
  Dimensions,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const { width } = Dimensions.get("window");
const ACCENT = "#E5005F";
const TAKA = "\u09F3";

function positiveNumber(value: unknown): number {
  const numberValue = Number(value ?? 0);
  return Number.isFinite(numberValue) && numberValue > 0 ? numberValue : 0;
}

function formatPercent(value: number): string {
  const normalized = Number.isInteger(value)
    ? value.toString()
    : value.toFixed(2).replace(/\.?0+$/, "");
  return `${normalized}%`;
}

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `${TAKA}${num.toLocaleString("en-BD")}`;
}

const STAT_CONFIG = [
  { key: "referal_bonus", title: "Referral Bonus", icon: "gift-outline" as const, color: "#E5005F", bg: "#FDF2F8", isCurrency: true },
  { key: "my_referral", title: "My Referral", icon: "people-outline" as const, color: "#7C3AED", bg: "#F5F3FF", isCurrency: false },
  { key: "active_member", title: "Active Members", icon: "checkmark-circle-outline" as const, color: "#059669", bg: "#ECFDF5", isCurrency: false },
  { key: "paid_member", title: "Paid Members", icon: "card-outline" as const, color: "#2563EB", bg: "#EFF6FF", isCurrency: false },
];

export default function ReferralScreen() {
  const queryClient = useQueryClient();

  const referralQuery = useQuery({
    queryKey: ["referral-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/referral/data");
      return data?.data ?? data;
    },
    staleTime: 2 * 60 * 1000,
  });

  const basicInfoQuery = useQuery({
    queryKey: ["basic-info"],
    queryFn: async () => {
      const { data } = await apiClient.get("/basic-info");
      return data?.data ?? data;
    },
    staleTime: 5 * 60 * 1000,
  });

  const stats = referralQuery.data ?? {};
  const history: any[] = stats?.history?.data ?? [];
  const referralSettings = stats?.referral_settings ?? {};
  const basicInfoReferralPercent = positiveNumber(basicInfoQuery.data?.bonus_percent);
  const personalReferralPercent = positiveNumber(
    referralSettings?.personal_referrer_bonus_percent ?? stats?.bonus_percent,
  );
  const defaultReferralPercent = positiveNumber(referralSettings?.default_referrer_bonus_percent);
  const configuredReferralPercent = positiveNumber(referralSettings?.referrer_bonus_percent);
  const referrerBonusPercent =
    configuredReferralPercent || personalReferralPercent || defaultReferralPercent || basicInfoReferralPercent;
  const hasReferrerReward = referrerBonusPercent > 0;

  const isRefreshing = referralQuery.isRefetching || basicInfoQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["referral-data"] });
    queryClient.invalidateQueries({ queryKey: ["basic-info"] });
  }, [queryClient]);

  if (referralQuery.isLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Referral Income", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Referral Income",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor={ACCENT} />
        }
      >
        {hasReferrerReward ? (
          <View style={styles.earningBanner}>
            <View style={styles.earningIcon}>
              <Ionicons name="gift-outline" size={22} color={ACCENT} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.earningTitle}>
                You earn {formatPercent(referrerBonusPercent)}
              </Text>
              <Text style={styles.earningSubtitle}>
                When your invited user subscribes
              </Text>
            </View>
          </View>
        ) : null}

        {/* ── Stats Grid ── */}
        <View style={styles.statsGrid}>
          {STAT_CONFIG.map((stat) => (
            <View key={stat.key} style={[styles.statCard, { backgroundColor: stat.bg }]}>
              <View style={[styles.statIcon, { backgroundColor: `${stat.color}15` }]}>
                <Ionicons name={stat.icon} size={20} color={stat.color} />
              </View>
              <Text style={styles.statValue}>
                {stat.isCurrency
                  ? formatCurrency(stats[stat.key])
                  : String(stats[stat.key] ?? 0)}
              </Text>
              <Text style={styles.statLabel}>{stat.title}</Text>
            </View>
          ))}
        </View>

        {/* ── History ── */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Referral Income History</Text>

          {history.length === 0 ? (
            <View style={styles.emptyState}>
              <Ionicons name="people-outline" size={40} color="#D1D5DB" />
              <Text fontSize="$3" color="#9CA3AF" mt="$2">
                No referral income yet
              </Text>
            </View>
          ) : (
            history.map((item: any, index: number) => (
              <View key={item.id ?? index} style={styles.historyCard}>
                <View style={styles.historyTop}>
                  <Text style={styles.historyTitle} numberOfLines={1}>
                    {item.message_for}
                  </Text>
                  <Text style={styles.historyAmount}>
                    {formatCurrency(item.amount)}
                  </Text>
                </View>
                {item.message && (
                  <Text style={styles.historyMessage} numberOfLines={2}>
                    {item.message}
                  </Text>
                )}
                <View style={styles.historyBottom}>
                  <Text style={styles.historyMeta}>#{index + 1}</Text>
                  <Text style={styles.historyMeta}>{item.date}</Text>
                </View>
              </View>
            ))
          )}
        </View>

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F8F8FA",
  },
  earningBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    margin: 16,
    marginBottom: 0,
    padding: 16,
    borderRadius: 16,
    backgroundColor: "#FDF2F8",
    borderWidth: 1,
    borderColor: "#FBCFE8",
  },
  earningIcon: {
    width: 44,
    height: 44,
    borderRadius: 14,
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
  },
  earningTitle: {
    fontSize: 18,
    fontWeight: "800",
    color: "#1A1A2E",
  },
  earningSubtitle: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 2,
    lineHeight: 17,
  },
  statsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 12,
    padding: 16,
  },
  statCard: {
    width: (width - 44) / 2,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.04)",
  },
  statIcon: {
    width: 36,
    height: 36,
    borderRadius: 12,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 10,
  },
  statValue: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 2,
  },
  statLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "500",
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 8,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 14,
  },
  emptyState: {
    alignItems: "center",
    paddingVertical: 32,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  historyCard: {
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  historyTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  historyTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1A1A2E",
    flex: 1,
    marginRight: 8,
  },
  historyAmount: {
    fontSize: 14,
    fontWeight: "700",
    color: "#059669",
  },
  historyMessage: {
    fontSize: 12,
    color: "#6B7280",
    marginBottom: 6,
    lineHeight: 17,
  },
  historyBottom: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  historyMeta: {
    fontSize: 11,
    color: "#9CA3AF",
  },
});
