import { useCallback, useMemo, useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  RefreshControl,
  Dimensions,
  ActivityIndicator,
  Share,
  Image,
} from "react-native";
import * as Clipboard from "expo-clipboard";
import { Text } from "tamagui";
import { router } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { LinearGradient } from "expo-linear-gradient";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import { useSession, logout } from "@/lib/auth-client";
import apiClient from "@/lib/api-client";
import { DashboardSkeleton } from "@/components/skeleton";

const { width } = Dimensions.get("window");

const IMAGE_BASE =
  (process.env.EXPO_PUBLIC_API_URL || "").replace(/\/api\/?$/, "") ||
  "https://api.selfshop.com.bd";

function resolveImageUrl(path?: string | null): string | null {
  if (!path || path.trim().length < 2) return null;
  const p = path.trim();
  if (p.startsWith("http")) return p;
  const clean = p.replace(/^\//, "");
  if (clean.startsWith("public/")) return `${IMAGE_BASE}/${clean.replace(/^public\/?/, "")}`;
  if (clean.startsWith("storage/") || clean.startsWith("images/")) return `${IMAGE_BASE}/${clean}`;
  return `${IMAGE_BASE}/storage/${clean}`;
}

/* ── Quick Action items ── */
const QUICK_ACTIONS = [
  { icon: "cube-outline" as const, label: "Orders", route: "/account/orders", color: "#E5005F" },
  { icon: "storefront-outline" as const, label: "My Shop", route: "/account/my-shop", color: "#7C3AED" },
  { icon: "wallet-outline" as const, label: "Withdraw", route: "/account/withdraw", color: "#059669" },
  { icon: "people-outline" as const, label: "Referral", route: "/account/referral", color: "#D97706" },
  { icon: "search-outline" as const, label: "Track", route: "/account/track-order", color: "#2563EB" },
  { icon: "swap-horizontal-outline" as const, label: "Transfer", route: "/account/balance-transfer", color: "#DC2626" },
  { icon: "trending-up-outline" as const, label: "Income", route: "/account/order-income", color: "#0891B2" },
  { icon: "shield-checkmark-outline" as const, label: "Fraud", route: "/account/fraud-checker", color: "#7C3AED" },
  { icon: "add-circle-outline" as const, label: "Request", route: "/account/product-request", color: "#EA580C" },
] as const;

/* ── Status pill colors ── */
const STATUS_COLORS: Record<string, { bg: string; text: string }> = {
  Pending: { bg: "#FEF3C7", text: "#92400E" },
  Accepted: { bg: "#D1FAE5", text: "#065F46" },
  Confirmed: { bg: "#DBEAFE", text: "#1E40AF" },
  Processing: { bg: "#E0E7FF", text: "#3730A3" },
  Ontheway: { bg: "#CFFAFE", text: "#155E75" },
  "On the way": { bg: "#CFFAFE", text: "#155E75" },
  Delivered: { bg: "#D1FAE5", text: "#065F46" },
  Canceled: { bg: "#FEE2E2", text: "#991B1B" },
  Rejected: { bg: "#FEE2E2", text: "#991B1B" },
  Return: { bg: "#F3F4F6", text: "#374151" },
};

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  const safeNum = Number.isFinite(num) ? num : 0;
  return `৳${safeNum.toLocaleString("en-BD", {
    maximumFractionDigits: 0,
  })}`;
}

function formatDate(value?: string | null): string {
  if (!value) return "Not set";
  const date = new Date(value);
  if (isNaN(date.getTime())) return value;
  return date.toLocaleDateString("en-BD", { month: "short", day: "numeric" });
}

function formatTargetValue(value: number | string | undefined, type?: string): string {
  const parsed = Number(value ?? 0);
  if (type === "amount") {
    return formatCurrency(parsed);
  }
  return `${parsed.toLocaleString()} Qty`;
}

export default function DashboardScreen() {
  const insets = useSafeAreaInsets();
  const { data: session, signOut, isLoading: isSessionLoading } = useSession();
  const queryClient = useQueryClient();
  const isLoggedIn = !!session?.user;

  /* ── Data Queries ── */
  const dashboardQuery = useQuery({
    queryKey: ["dashboard-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/dashboard-data");
      return data?.data ?? data;
    },
    enabled: isLoggedIn,
    staleTime: 2 * 60 * 1000,
  });

  const profileQuery = useQuery({
    queryKey: ["user-profile"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-profile");
      return data?.data ?? data;
    },
    enabled: isLoggedIn,
    staleTime: 5 * 60 * 1000,
  });

  const recentOrdersQuery = useQuery({
    queryKey: ["orders", "Pending", 1],
    queryFn: async () => {
      const { data } = await apiClient.get("/order-data/Pending?page=1");
      return data?.data?.data ?? data?.data ?? [];
    },
    enabled: isLoggedIn,
    staleTime: 60 * 1000,
  });

  const notificationsQuery = useQuery({
    queryKey: ["notifications-count"],
    queryFn: async () => {
      const { data } = await apiClient.get("/user-notification?unread_only=true&per_page=1");
      return data?.unread_count ?? 0;
    },
    enabled: isLoggedIn,
    staleTime: 30 * 1000,
  });

  /* ── Pull to refresh ── */
  const isRefreshing = dashboardQuery.isRefetching || profileQuery.isRefetching;
  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    queryClient.invalidateQueries({ queryKey: ["user-profile"] });
    queryClient.invalidateQueries({ queryKey: ["orders", "Pending", 1] });
    queryClient.invalidateQueries({ queryKey: ["notifications-count"] });
  }, [queryClient]);

  /* ── Derived data ── */
  const profile = profileQuery.data?.profile ?? profileQuery.data;
  const metrics = dashboardQuery.data;
  const recentOrdersRaw = recentOrdersQuery.data;
  const recentOrders = (
    Array.isArray(recentOrdersRaw) ? recentOrdersRaw
    : Array.isArray(recentOrdersRaw?.data) ? recentOrdersRaw.data
    : []
  ).slice(0, 5);
  const unreadNotifs = notificationsQuery.data ?? 0;
  const walletBalance = Number(
    metrics?.balance ?? metrics?.blance ?? profile?.account_balance ?? 0
  );

  /* ── Referral data ── */
  const referralCode = profile?.my_referral_code ?? "";
  const referralLink = referralCode
    ? `https://selfshop.com.bd/register?refer=${referralCode}`
    : "";
  const [codeCopied, setCodeCopied] = useState(false);

  const copyReferralCode = useCallback(async () => {
    if (!referralCode) return;
    await Clipboard.setStringAsync(referralCode);
    setCodeCopied(true);
    setTimeout(() => setCodeCopied(false), 2000);
  }, [referralCode]);

  const shareReferralLink = useCallback(async () => {
    if (!referralLink) return;
    try {
      await Share.share({
        message: `Join SelfShop and start your reselling business! Use my referral code: ${referralCode}\n\n${referralLink}`,
      });
    } catch {}
  }, [referralLink, referralCode]);

  const kpiCards = useMemo(() => [
    { title: "Total Sale", value: formatCurrency(metrics?.total_sales), icon: "trending-up-outline" as const, color: "#059669", bg: "#ECFDF5" },
    { title: "Total Profit", value: formatCurrency(metrics?.total_profit), icon: "cash-outline" as const, color: "#7C3AED", bg: "#F5F3FF" },
    { title: "Pending", value: formatCurrency(metrics?.pending_amount), icon: "time-outline" as const, color: "#D97706", bg: "#FFFBEB" },
    { title: "Balance", value: formatCurrency(walletBalance), icon: "wallet-outline" as const, color: "#2563EB", bg: "#EFF6FF" },
  ], [metrics, walletBalance]);

  const orderInsights = useMemo(() => [
    { title: "Shop Products", value: String(profileQuery.data?.shopproducts ?? 0), icon: "storefront-outline" as const },
    { title: "Total Orders", value: String(profileQuery.data?.totalorders ?? 0), icon: "receipt-outline" as const },
    { title: "Sold Amount", value: String(profileQuery.data?.soldamount ?? 0), icon: "cart-outline" as const },
    { title: "Pending", value: formatCurrency(metrics?.pending_amount), icon: "hourglass-outline" as const },
  ], [profileQuery.data, metrics]);

  const eventChallenges = useMemo(() => {
    const targets = Array.isArray(metrics?.active_sales_targets)
      ? metrics.active_sales_targets
      : [];
    const joined = targets.filter((item: any) => item?.participation?.joined);
    const available = targets.filter((item: any) => !item?.participation?.joined);
    return [...joined, ...available];
  }, [metrics]);

  if (isSessionLoading) {
    return <DashboardSkeleton />;
  }

  /* ── Guest View ── */
  if (!isLoggedIn) {
    return (
      <View style={styles.container}>
        <View style={styles.guestContent}>
          <Ionicons name="bar-chart-outline" size={80} color="#D8D8D8" />
          <Text fontSize="$5" fontWeight="bold" color="#1A1A2E" mt="$3">
            Reseller Dashboard
          </Text>
          <Text fontSize="$3" color="#8E8E93" mt="$1" style={{ textAlign: "center" }}>
            Sign in to view your dashboard
          </Text>
          <Pressable
            style={({ pressed }) => [styles.signInButton, pressed && { opacity: 0.85 }]}
            onPress={() => router.push("/login")}
          >
            <Text fontSize="$4" fontWeight="bold" color="#fff">
              Sign In
            </Text>
          </Pressable>
        </View>
      </View>
    );
  }

  /* ── Loading State ── */
  if (dashboardQuery.isLoading && profileQuery.isLoading) {
    return <DashboardSkeleton />;
  }

  return (
    <View style={styles.container}>
      <ScrollView
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={isRefreshing} onRefresh={onRefresh} tintColor="#E5005F" />
        }
      >
        {/* ─── Gradient Header ─── */}
        <LinearGradient
          colors={["#E5005F", "#B8004C", "#8C003A"]}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={[styles.headerGradient, { paddingTop: insets.top + 12 }]}
        >
          <View style={styles.headerTop}>
            <View style={styles.headerLeft}>
              <Text fontSize="$6" fontWeight="bold" color="#fff">
                Dashboard
              </Text>
              <Text fontSize="$2" color="rgba(255,255,255,0.7)" mt="$0.5">
                Welcome back, {profile?.name ?? session.user.name}
              </Text>
            </View>
            <View style={styles.headerRight}>
              <Pressable
                style={styles.notifButton}
                onPress={() => router.push("/account/notifications" as any)}
              >
                <Ionicons name="notifications-outline" size={22} color="#fff" />
                {unreadNotifs > 0 && (
                  <View style={styles.notifBadge}>
                    <Text style={styles.notifBadgeText}>
                      {unreadNotifs > 9 ? "9+" : unreadNotifs}
                    </Text>
                  </View>
                )}
              </Pressable>
              <Pressable style={styles.settingsButton} onPress={() => router.push("/account/settings")}>
                <Ionicons name="settings-outline" size={22} color="#fff" />
              </Pressable>
            </View>
          </View>

          {/* Wallet Balance Card */}
          <View style={styles.walletCard}>
            <View style={styles.walletLeft}>
              <Text fontSize="$2" color="rgba(255,255,255,0.6)">
                Wallet Balance
              </Text>
              <Text fontSize="$8" fontWeight="bold" color="#fff" mt="$0.5">
                {formatCurrency(walletBalance)}
              </Text>
              <Text fontSize="$1" color="rgba(255,255,255,0.5)" mt="$0.5">
                ID: #{profile?.id ?? "—"}
              </Text>
            </View>
            <View style={styles.walletIcon}>
              <Ionicons name="wallet" size={36} color="rgba(255,255,255,0.25)" />
            </View>
          </View>
        </LinearGradient>

        {/* ─── KPI Cards ─── */}
        <View style={styles.kpiSection}>
          <View style={styles.kpiGrid}>
            {kpiCards.map((card, i) => (
              <View key={i} style={[styles.kpiCard, { backgroundColor: card.bg }]}>
                <View style={[styles.kpiIconWrapper, { backgroundColor: `${card.color}15` }]}>
                  <Ionicons name={card.icon} size={20} color={card.color} />
                </View>
                <Text style={styles.kpiValue} numberOfLines={1}>
                  {card.value}
                </Text>
                <Text style={styles.kpiLabel}>{card.title}</Text>
              </View>
            ))}
          </View>
        </View>

        {/* ─── Referral Invite Banner ─── */}
        {referralCode ? (
          <View style={styles.sectionContainer}>
            <LinearGradient
              colors={["#FF6B35", "#E5005F"]}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
              style={styles.referralBanner}
            >
              {/* Decorative circles */}
              <View style={styles.referralDecor1} />
              <View style={styles.referralDecor2} />

              <View style={styles.referralHeader}>
                <View style={styles.referralIconCircle}>
                  <Ionicons name="gift" size={22} color="#E5005F" />
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.referralTitle}>Invite & Earn</Text>
                  <Text style={styles.referralSubtitle}>
                    Share your code and earn bonus on each referral!
                  </Text>
                </View>
              </View>

              {/* Referral Code */}
              <Pressable
                style={({ pressed }) => [
                  styles.referralCodeBox,
                  pressed && { opacity: 0.9 },
                ]}
                onPress={copyReferralCode}
              >
                <View style={styles.referralCodeLeft}>
                  <Text style={styles.referralCodeLabel}>Your Referral Code</Text>
                  <Text style={styles.referralCodeValue}>{referralCode}</Text>
                </View>
                <View style={styles.referralCopyBtn}>
                  <Ionicons
                    name={codeCopied ? "checkmark-circle" : "copy-outline"}
                    size={18}
                    color={codeCopied ? "#059669" : "#E5005F"}
                  />
                  <Text style={[styles.referralCopyText, codeCopied && { color: "#059669" }]}>
                    {codeCopied ? "Copied!" : "Copy"}
                  </Text>
                </View>
              </Pressable>

              {/* Share Button */}
              <Pressable
                style={({ pressed }) => [
                  styles.referralShareBtn,
                  pressed && { opacity: 0.9, transform: [{ scale: 0.98 }] },
                ]}
                onPress={shareReferralLink}
              >
                <Ionicons name="share-social" size={18} color="#E5005F" />
                <Text style={styles.referralShareText}>Share Referral Link</Text>
              </Pressable>
            </LinearGradient>
          </View>
        ) : null}

        {/* ─── Quick Actions ─── */}
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Quick Actions</Text>
          <View style={styles.quickActionsGrid}>
            {QUICK_ACTIONS.map((action, i) => (
              <Pressable
                key={i}
                style={({ pressed }) => [
                  styles.quickActionItem,
                  pressed && { opacity: 0.7, transform: [{ scale: 0.95 }] },
                ]}
                onPress={() => {
                  if (action.route) router.push(action.route as any);
                }}
              >
                <View style={[styles.quickActionIcon, { backgroundColor: `${action.color}12` }]}>
                  <Ionicons name={action.icon} size={24} color={action.color} />
                </View>
                <Text style={styles.quickActionLabel}>{action.label}</Text>
              </Pressable>
            ))}
          </View>
        </View>

        <EventChallengeSlider challenges={eventChallenges} />

        {/* ─── Recent Orders ─── */}
        <View style={styles.sectionContainer}>
          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionTitle}>Recent Orders</Text>
            <Pressable onPress={() => router.push("/account/orders")}>
              <Text fontSize="$3" color="#E5005F" fontWeight="600">
                See All
              </Text>
            </Pressable>
          </View>

          {recentOrdersQuery.isLoading ? (
            <View style={styles.loadingSmall}>
              <ActivityIndicator size="small" color="#E5005F" />
            </View>
          ) : recentOrders.length === 0 ? (
            <View style={styles.emptyOrders}>
              <Ionicons name="receipt-outline" size={40} color="#D1D5DB" />
              <Text fontSize="$3" color="#9CA3AF" mt="$2">
                No pending orders
              </Text>
            </View>
          ) : (
            recentOrders.map((order: any) => {
              const displayStatus =
                order.customer_status ?? order.display_status ?? order.status ?? "Pending";
              const statusStyle = STATUS_COLORS[displayStatus] ?? STATUS_COLORS.Pending;

              return (
                <Pressable
                  key={order.id}
                  style={({ pressed }) => [
                    styles.orderCard,
                    pressed && { backgroundColor: "#F9FAFB" },
                  ]}
                  onPress={() =>
                    router.push({
                      pathname: "/account/order-detail",
                      params: { invoiceID: order.invoiceID, id: order.id },
                    } as any)
                  }
                >
                  <View style={styles.orderLeft}>
                    {(() => {
                      const imgPath = order.orderproducts?.[0]?.product?.ViewProductImage;
                      const imgUri = resolveImageUrl(imgPath);

                      return imgUri ? (
                        <Image
                          source={{ uri: imgUri }}
                          style={styles.orderImage}
                          resizeMode="cover"
                        />
                      ) : (
                        <View style={styles.orderIconWrapper}>
                          <Ionicons name="cube-outline" size={20} color="#E5005F" />
                        </View>
                      );
                    })()}
                    <View style={styles.orderInfo}>
                      <Text style={styles.orderInvoice} numberOfLines={1}>
                        {order.invoiceID}
                      </Text>
                      <Text style={styles.orderCustomer} numberOfLines={1}>
                        {order.customers?.customerName ?? "Customer"}
                      </Text>
                      <Text style={styles.orderDate}>{order.orderDate}</Text>
                    </View>
                  </View>
                  <View style={styles.orderRight}>
                    <View style={[styles.statusPill, { backgroundColor: statusStyle.bg }]}>
                      <Text style={[styles.statusText, { color: statusStyle.text }]}>
                        {displayStatus}
                      </Text>
                    </View>
                    <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
                  </View>
                </Pressable>
              );
            })
          )}
        </View>

        {/* ─── Order Insights ─── */}
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Order Insights</Text>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.insightsScroll}
          >
            {orderInsights.map((insight, i) => (
              <View key={i} style={styles.insightCard}>
                <View style={styles.insightIconWrapper}>
                  <Ionicons name={insight.icon} size={20} color="#E5005F" />
                </View>
                <Text style={styles.insightValue}>{insight.value}</Text>
                <Text style={styles.insightLabel}>{insight.title}</Text>
              </View>
            ))}
          </ScrollView>
        </View>

        {/* ─── Account Actions ─── */}
        <View style={styles.sectionContainer}>
          <Text style={styles.sectionTitle}>Account</Text>
          <View style={styles.menuCard}>
            <MenuItem
              icon="person-outline"
              label="Profile"
              subtitle="View and edit your profile"
              onPress={() => router.push("/account/edit-profile")}
            />
            <MenuItem
              icon="location-outline"
              label="Addresses"
              subtitle="Manage delivery locations"
              onPress={() => router.push("/account/addresses")}
            />
            <MenuItem
              icon="chatbubble-outline"
              label="Support Tickets"
              subtitle="Get help with your orders"
              onPress={() => router.push("/account/tickets")}
            />
            <MenuItem
              icon="chatbubbles-outline"
              label="Live Chat"
              subtitle="Chat with us directly"
              onPress={() => router.push("/account/live-chat")}
            />
            <MenuItem
              icon="settings-outline"
              label="Settings"
              subtitle="Password, legal, and more"
              onPress={() => router.push("/account/settings")}
            />
          </View>
        </View>

        {/* ─── Sign Out ─── */}
        <View style={[styles.sectionContainer, { marginBottom: 0 }]}>
          <Pressable
            style={({ pressed }) => [
              styles.signOutButton,
              pressed && { opacity: 0.8 },
            ]}
            onPress={signOut}
          >
            <Ionicons name="log-out-outline" size={20} color="#DC2626" />
            <Text fontSize="$4" fontWeight="600" color="#DC2626" ml="$2">
              Sign Out
            </Text>
          </Pressable>
        </View>

        {/* Bottom spacing for floating tab bar */}
        <View style={{ height: 120 }} />
      </ScrollView>
    </View>
  );
}

function EventChallengeSlider({ challenges }: { challenges: any[] }) {
  if (challenges.length === 0) return null;

  return (
    <View style={styles.sectionContainer}>
      <View style={styles.sectionHeaderRow}>
        <Text style={styles.sectionTitle}>Event Challenge</Text>
        <Pressable onPress={() => router.push("/account/events" as any)}>
          <Text fontSize="$3" color="#E5005F" fontWeight="600">
            View All
          </Text>
        </Pressable>
      </View>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.challengeSlider}
      >
        {challenges.map((item: any, index: number) => (
          <DashboardChallengeCard
            key={item?.target?.id ?? index}
            item={item}
          />
        ))}
      </ScrollView>
    </View>
  );
}

function DashboardChallengeCard({ item }: { item: any }) {
  const target = item?.target ?? {};
  const progress = item?.progress ?? {};
  const joined = Boolean(item?.participation?.joined);
  const completed = Boolean(progress?.completed);
  const progressPercent = Math.max(0, Math.min(100, Number(progress?.progress_percent ?? 0)));
  const statusLabel = completed ? "Completed" : joined ? "In Progress" : "New";
  const statusColor = completed ? "#059669" : joined ? "#F59E0B" : "#E5005F";
  const targetType = target?.target_type ?? "";
  const rewardValue = target?.reward_value
    ? Number(target.reward_value).toLocaleString("en-BD")
    : null;
  const rewardText = target?.reward_type
    ? `${target.reward_type}${rewardValue ? ` ${rewardValue}` : ""}`
    : "Reward available";

  return (
    <Pressable
      style={({ pressed }) => [
        styles.challengeCard,
        pressed && { opacity: 0.9, transform: [{ scale: 0.98 }] },
      ]}
      onPress={() => router.push("/account/events" as any)}
    >
      <LinearGradient
        colors={joined ? ["#FFF7ED", "#FDF2F8"] : ["#FFFFFF", "#FDF2F8"]}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.challengeCardGradient}
      >
        <View style={styles.challengeCardTop}>
          <View style={[styles.challengeIcon, { backgroundColor: `${statusColor}18` }]}>
            <Ionicons name={completed ? "trophy" : joined ? "flash" : "rocket"} size={18} color={statusColor} />
          </View>
          <View style={[styles.challengeStatusPill, { backgroundColor: `${statusColor}18` }]}>
            <Text style={[styles.challengeStatusText, { color: statusColor }]}>
              {statusLabel}
            </Text>
          </View>
        </View>

        <Text style={styles.challengeTitle} numberOfLines={1}>
          {target?.title || "Sales Target"}
        </Text>
        <Text style={styles.challengeDate} numberOfLines={1}>
          {formatDate(target?.start_date)} - {formatDate(target?.end_date)}
        </Text>

        <View style={styles.challengeMetaRow}>
          <View style={styles.challengeMetaItem}>
            <Text style={styles.challengeMetaLabel}>Target</Text>
            <Text style={styles.challengeMetaValue} numberOfLines={1}>
              {formatTargetValue(target?.target_value, targetType)}
            </Text>
          </View>
          <View style={styles.challengeMetaItem}>
            <Text style={styles.challengeMetaLabel}>Reward</Text>
            <Text style={styles.challengeMetaValue} numberOfLines={1}>
              {rewardText}
            </Text>
          </View>
        </View>

        {joined ? (
          <View style={styles.challengeProgressBlock}>
            <View style={styles.challengeProgressLabels}>
              <Text style={styles.challengeProgressLabel}>Progress</Text>
              <Text style={styles.challengeProgressPercent}>{progressPercent.toFixed(0)}%</Text>
            </View>
            <View style={styles.challengeProgressTrack}>
              <View
                style={[
                  styles.challengeProgressFill,
                  { width: `${progressPercent}%`, backgroundColor: statusColor },
                ]}
              />
            </View>
          </View>
        ) : (
          <View style={styles.challengeCtaRow}>
            <Text style={styles.challengeCtaText}>Tap to join from Event Challenges</Text>
            <Ionicons name="chevron-forward" size={16} color="#E5005F" />
          </View>
        )}
      </LinearGradient>
    </Pressable>
  );
}

/* ── Menu Item component ── */
function MenuItem({
  icon,
  label,
  subtitle,
  onPress,
}: {
  icon: string;
  label: string;
  subtitle: string;
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
      <Ionicons name="chevron-forward" size={16} color="#C7C7CC" />
    </Pressable>
  );
}

/* ─── Styles ─── */
const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F8F8FA",
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F8F8FA",
  },
  guestContent: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 40,
    paddingBottom: 100,
  },
  signInButton: {
    backgroundColor: "#E5005F",
    paddingVertical: 14,
    paddingHorizontal: 48,
    borderRadius: 30,
    marginTop: 24,
    width: "100%",
    alignItems: "center",
  },

  /* ── Header ── */
  headerGradient: {
    paddingBottom: 24,
    paddingHorizontal: 20,
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
  },
  headerTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    marginBottom: 20,
  },
  headerLeft: {},
  headerRight: {
    flexDirection: "row",
    gap: 8,
  },
  notifButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "rgba(255,255,255,0.15)",
    justifyContent: "center",
    alignItems: "center",
  },
  notifBadge: {
    position: "absolute",
    top: -2,
    right: -2,
    backgroundColor: "#FF3B30",
    borderRadius: 10,
    minWidth: 18,
    height: 18,
    paddingHorizontal: 4,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 2,
    borderColor: "#E5005F",
  },
  notifBadgeText: {
    color: "#fff",
    fontSize: 10,
    fontWeight: "700",
  },
  settingsButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "rgba(255,255,255,0.15)",
    justifyContent: "center",
    alignItems: "center",
  },
  walletCard: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "rgba(255,255,255,0.1)",
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "rgba(255,255,255,0.15)",
  },
  walletLeft: {},
  walletIcon: {
    opacity: 0.8,
  },

  /* ── KPI Cards ── */
  kpiSection: {
    paddingHorizontal: 20,
    marginTop: -12,
  },
  kpiGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 12,
  },
  kpiCard: {
    width: (width - 52) / 2,
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: "rgba(0,0,0,0.04)",
  },
  kpiIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 12,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 10,
  },
  kpiValue: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 2,
  },
  kpiLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "500",
  },

  /* ── Quick Actions ── */
  sectionContainer: {
    paddingHorizontal: 20,
    marginTop: 24,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 14,
  },
  sectionHeaderRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 14,
  },
  quickActionsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 12,
  },
  quickActionItem: {
    width: (width - 64) / 3,
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 16,
    paddingVertical: 18,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  quickActionIcon: {
    width: 48,
    height: 48,
    borderRadius: 16,
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 8,
  },
  quickActionLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#374151",
  },

  /* ── Event Challenge ── */
  challengeSlider: {
    gap: 12,
  },
  challengeCard: {
    width: width - 40,
    borderRadius: 18,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0DCE7",
    backgroundColor: "#fff",
  },
  challengeCardGradient: {
    minHeight: 188,
    padding: 16,
  },
  challengeCardTop: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  challengeIcon: {
    width: 38,
    height: 38,
    borderRadius: 14,
    justifyContent: "center",
    alignItems: "center",
  },
  challengeStatusPill: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 12,
  },
  challengeStatusText: {
    fontSize: 11,
    fontWeight: "700",
  },
  challengeTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#1A1A2E",
  },
  challengeDate: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 4,
  },
  challengeMetaRow: {
    flexDirection: "row",
    gap: 10,
    marginTop: 14,
  },
  challengeMetaItem: {
    flex: 1,
    backgroundColor: "rgba(255,255,255,0.82)",
    borderRadius: 12,
    padding: 10,
    borderWidth: 1,
    borderColor: "rgba(229,0,95,0.08)",
  },
  challengeMetaLabel: {
    fontSize: 10,
    color: "#9CA3AF",
    fontWeight: "600",
    marginBottom: 3,
  },
  challengeMetaValue: {
    fontSize: 12,
    color: "#1A1A2E",
    fontWeight: "700",
  },
  challengeProgressBlock: {
    marginTop: 14,
  },
  challengeProgressLabels: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 6,
  },
  challengeProgressLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "600",
  },
  challengeProgressPercent: {
    fontSize: 11,
    color: "#1A1A2E",
    fontWeight: "800",
  },
  challengeProgressTrack: {
    height: 8,
    borderRadius: 4,
    backgroundColor: "rgba(255,255,255,0.9)",
    overflow: "hidden",
  },
  challengeProgressFill: {
    height: "100%",
    borderRadius: 4,
  },
  challengeCtaRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: "rgba(229,0,95,0.08)",
  },
  challengeCtaText: {
    flex: 1,
    fontSize: 12,
    color: "#E5005F",
    fontWeight: "700",
  },

  /* ── Order Insights ── */
  insightsScroll: {
    gap: 12,
  },
  insightCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    width: 130,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    alignItems: "center",
  },
  insightIconWrapper: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginBottom: 10,
  },
  insightValue: {
    fontSize: 20,
    fontWeight: "700",
    color: "#1A1A2E",
    marginBottom: 2,
  },
  insightLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "500",
    textAlign: "center",
  },

  /* ── Recent Orders ── */
  loadingSmall: {
    paddingVertical: 24,
    alignItems: "center",
  },
  emptyOrders: {
    alignItems: "center",
    paddingVertical: 32,
    backgroundColor: "#fff",
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  orderCard: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  orderLeft: {
    flexDirection: "row",
    alignItems: "center",
    flex: 1,
    marginRight: 12,
  },
  orderIconWrapper: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  orderImage: {
    width: 40,
    height: 40,
    borderRadius: 12,
    marginRight: 12,
    backgroundColor: "#F3F4F6",
  },
  orderInfo: {
    flex: 1,
  },
  orderInvoice: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  orderCustomer: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 1,
  },
  orderDate: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 2,
  },
  orderRight: {
    alignItems: "flex-end",
    gap: 6,
  },
  statusPill: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 11,
    fontWeight: "600",
  },

  /* ── Account Menu ── */
  menuCard: {
    backgroundColor: "#fff",
    borderRadius: 16,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#F0F0F5",
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

  /* ── Referral Banner ── */
  referralBanner: {
    borderRadius: 20,
    padding: 20,
    overflow: "hidden",
    position: "relative",
  },
  referralDecor1: {
    position: "absolute",
    top: -20,
    right: -20,
    width: 80,
    height: 80,
    borderRadius: 40,
    backgroundColor: "rgba(255,255,255,0.1)",
  },
  referralDecor2: {
    position: "absolute",
    bottom: -15,
    left: -15,
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: "rgba(255,255,255,0.08)",
  },
  referralHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginBottom: 16,
  },
  referralIconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "#fff",
    justifyContent: "center",
    alignItems: "center",
  },
  referralTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#fff",
  },
  referralSubtitle: {
    fontSize: 12,
    color: "rgba(255,255,255,0.8)",
    marginTop: 2,
  },
  referralCodeBox: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "rgba(255,255,255,0.95)",
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "rgba(255,255,255,0.3)",
  },
  referralCodeLeft: {
    flex: 1,
  },
  referralCodeLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "500",
    marginBottom: 2,
  },
  referralCodeValue: {
    fontSize: 18,
    fontWeight: "800",
    color: "#1A1A2E",
    letterSpacing: 1.5,
  },
  referralCopyBtn: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#FDF2F8",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
  },
  referralCopyText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#E5005F",
  },
  referralShareBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: "#fff",
    borderRadius: 14,
    paddingVertical: 14,
  },
  referralShareText: {
    fontSize: 15,
    fontWeight: "700",
    color: "#E5005F",
  },

  /* ── Sign Out ── */
  signOutButton: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 14,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#FECACA",
    backgroundColor: "#FEF2F2",
  },
});
