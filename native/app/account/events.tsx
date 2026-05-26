import { useCallback, useState } from "react";
import {
  View,
  ScrollView,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";

import apiClient from "@/lib/api-client";
import { SubscriptionRequired } from "@/components/subscription-required";
import { useIsActiveReseller } from "@/hooks/useIsActiveReseller";

const ACCENT = "#E5005F";

const formatDate = (raw?: string | null): string => {
  if (!raw) return "Not set";
  const dt = new Date(raw);
  if (isNaN(dt.getTime())) return raw;
  return dt.toLocaleDateString();
};

const formatTargetValue = (value: number | string | undefined, type: string): string => {
  const parsed = Number(value ?? 0);
  if (type === "amount") return `৳${parsed.toLocaleString("en-BD", { minimumFractionDigits: 0 })}`;
  return `${parsed.toLocaleString()} Qty`;
};

export default function EventsScreen() {
  const queryClient = useQueryClient();
  const { isActive: isResellerActive, isLoading: isSubscriptionLoading } = useIsActiveReseller();

  const dashboardQuery = useQuery({
    queryKey: ["dashboard-data"],
    queryFn: async () => {
      const { data } = await apiClient.get("/dashboard-data");
      return data?.data ?? data;
    },
    enabled: isResellerActive,
  });

  const participateMut = useMutation({
    mutationFn: async (targetId: number) => {
      const { data } = await apiClient.post("/sales-targets/participate", { sales_target_id: targetId });
      return data;
    },
    onSuccess: (data: any) => {
      toast.success(data?.message || "Challenge joined successfully!");
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? "Could not join challenge.");
    },
  });

  const claimMut = useMutation({
    mutationFn: async (targetId: number) => {
      const { data } = await apiClient.post("/sales-targets/claim-reward", { sales_target_id: targetId });
      return data;
    },
    onSuccess: (data: any) => {
      toast.success(data?.message || "Reward claimed successfully!");
      queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
    },
    onError: (err: any) => {
      toast.error(err?.response?.data?.message ?? "Could not claim reward.");
    },
  });

  const [participatingId, setParticipatingId] = useState<number | null>(null);
  const [claimingId, setClaimingId] = useState<number | null>(null);

  const allTargets: any[] = dashboardQuery.data?.active_sales_targets ?? [];
  const participating = allTargets.filter((i: any) => i.participation?.joined);
  const available = allTargets.filter((i: any) => !i.participation?.joined);

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["dashboard-data"] });
  }, [queryClient]);

  const handleParticipate = async (id: number) => {
    setParticipatingId(id);
    try {
      await participateMut.mutateAsync(id);
    } finally {
      setParticipatingId(null);
    }
  };

  const handleClaim = async (id: number) => {
    setClaimingId(id);
    try {
      await claimMut.mutateAsync(id);
    } finally {
      setClaimingId(null);
    }
  };

  if (isSubscriptionLoading) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Event Challenges", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <View style={styles.emptyState}>
          <ActivityIndicator size="large" color={ACCENT} />
        </View>
      </>
    );
  }

  if (!isResellerActive) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Event Challenges", headerShadowVisible: false, headerStyle: { backgroundColor: "#F8F8FA" } }} />
        <SubscriptionRequired
          title="Activate to Join Events"
          message="Activate your subscription to access reseller challenges and rewards."
        />
      </>
    );
  }

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Event Challenges",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <ScrollView
        style={styles.container}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={dashboardQuery.isRefetching}
            onRefresh={onRefresh}
            tintColor={ACCENT}
          />
        }
      >
        {/* Header Info */}
        <View style={styles.headerCard}>
          <View style={styles.headerIconBg}>
            <Ionicons name="trophy" size={22} color={ACCENT} />
          </View>
          <View style={{ flex: 1, marginLeft: 12 }}>
            <Text style={{ fontSize: 16, fontWeight: "700", color: "#1A1A2E" }}>Event Challenges</Text>
            <Text style={{ fontSize: 12, color: "#6B7280", marginTop: 2 }}>Participate in challenges and earn rewards</Text>
          </View>
        </View>

        {dashboardQuery.isLoading ? (
          <View style={styles.loadingState}>
            <ActivityIndicator size="large" color={ACCENT} />
          </View>
        ) : allTargets.length === 0 ? (
          <View style={styles.emptyState}>
            <Ionicons name="calendar-outline" size={48} color="#D1D5DB" />
            <Text fontSize="$4" fontWeight="600" color="#6B7280" mt="$3">No active challenges right now</Text>
            <Text fontSize="$2" color="#9CA3AF" mt="$1">Check back soon for new exciting challenges!</Text>
          </View>
        ) : (
          <>
            {/* My Challenges */}
            {participating.length > 0 && (
              <View style={styles.section}>
                <View style={styles.sectionHeader}>
                  <View style={[styles.sectionDot, { backgroundColor: "#F59E0B" }]} />
                  <Text style={styles.sectionTitle}>My Challenges</Text>
                  <View style={styles.sectionBadge}>
                    <Text style={styles.sectionBadgeText}>{participating.length}</Text>
                  </View>
                </View>

                {participating.map((item: any) => (
                  <ChallengeCard
                    key={item.target.id}
                    item={item}
                    onClaim={handleClaim}
                    claimingId={claimingId}
                    isClaiming={claimMut.isPending}
                    type="participating"
                  />
                ))}
              </View>
            )}

            {/* Available Challenges */}
            {available.length > 0 && (
              <View style={styles.section}>
                <View style={styles.sectionHeader}>
                  <View style={[styles.sectionDot, { backgroundColor: ACCENT }]} />
                  <Text style={styles.sectionTitle}>Available Challenges</Text>
                  <View style={styles.sectionBadge}>
                    <Text style={styles.sectionBadgeText}>{available.length}</Text>
                  </View>
                </View>

                {available.map((item: any) => (
                  <ChallengeCard
                    key={item.target.id}
                    item={item}
                    onJoin={handleParticipate}
                    participatingId={participatingId}
                    isParticipating={participateMut.isPending}
                    type="available"
                  />
                ))}
              </View>
            )}
          </>
        )}

        <View style={{ height: 40 }} />
      </ScrollView>
    </>
  );
}

function ChallengeCard({
  item,
  onJoin,
  onClaim,
  participatingId,
  claimingId,
  isParticipating,
  isClaiming,
  type,
}: {
  item: any;
  onJoin?: (id: number) => void;
  onClaim?: (id: number) => void;
  participatingId?: number | null;
  claimingId?: number | null;
  isParticipating?: boolean;
  isClaiming?: boolean;
  type: "participating" | "available";
}) {
  const [expanded, setExpanded] = useState(false);
  const { target: t, progress, participation } = item;

  const rewardClaimed = Boolean(participation?.reward_claimed);
  const completed = Boolean(progress?.completed);
  const progressPercent = Math.max(0, Math.min(100, Number(progress?.progress_percent ?? 0)));
  const canClaim = completed && !rewardClaimed && type === "participating";
  const isThisClaiming = isClaiming && claimingId === t.id;
  const isThisParticipating = isParticipating && participatingId === t.id;

  const cardBg = rewardClaimed ? "#ECFDF5" : completed ? "#EFF6FF" : type === "available" ? "#FDF2F8" : "#FFFBEB";
  const cardBorder = rewardClaimed ? "#A7F3D0" : completed ? "#BFDBFE" : type === "available" ? "#FBCFE8" : "#FDE68A";
  const statusColor = rewardClaimed ? "#059669" : completed ? "#2563EB" : "#F59E0B";
  const statusLabel = rewardClaimed ? "Claimed" : completed ? "Completed!" : type === "available" ? "New" : "In Progress";
  const statusIcon = rewardClaimed ? "checkmark-circle" : completed ? "trophy" : type === "available" ? "rocket" : "flash";

  return (
    <View style={[styles.challengeCard, { backgroundColor: cardBg, borderColor: cardBorder }]}>
      {/* Top Row */}
      <View style={styles.challengeTop}>
        <View style={[styles.challengeIcon, { backgroundColor: statusColor }]}>
          <Ionicons name={statusIcon as any} size={16} color="#fff" />
        </View>
        <View style={{ flex: 1, marginLeft: 10 }}>
          <Text style={styles.challengeTitle} numberOfLines={1}>{t.title || "Sales Target"}</Text>
          <Text style={styles.challengeDate}>
            <Ionicons name="calendar-outline" size={10} color="#9CA3AF" />{" "}
            {formatDate(t.start_date)} – {formatDate(t.end_date)}
          </Text>
        </View>
        <View style={[styles.statusPill, { backgroundColor: `${statusColor}20` }]}>
          <Text style={[styles.statusPillText, { color: statusColor }]}>{statusLabel}</Text>
        </View>
      </View>

      {/* Progress Bar (only for participating) */}
      {type === "participating" && (
        <View style={styles.progressSection}>
          <View style={styles.progressLabels}>
            <Text style={styles.progressLabel}>
              {formatTargetValue(progress?.achieved, t.target_type ?? "")} / {formatTargetValue(progress?.target, t.target_type ?? "")}
            </Text>
            <Text style={styles.progressPercent}>{progressPercent.toFixed(1)}%</Text>
          </View>
          <View style={styles.progressTrack}>
            <View style={[styles.progressFill, { width: `${progressPercent}%`, backgroundColor: completed ? "#059669" : ACCENT }]} />
          </View>
        </View>
      )}

      {/* Actions */}
      <View style={styles.actionRow}>
        {canClaim && onClaim && (
          <Pressable
            style={({ pressed }) => [styles.claimButton, pressed && { opacity: 0.85 }]}
            onPress={() => onClaim(t.id)}
            disabled={!!isThisClaiming}
          >
            {isThisClaiming ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <>
                <Ionicons name="gift" size={14} color="#fff" />
                <Text style={styles.claimButtonText}>🎉 Grab Reward</Text>
              </>
            )}
          </Pressable>
        )}

        {type === "available" && onJoin && (
          <Pressable
            style={({ pressed }) => [styles.joinButton, pressed && { opacity: 0.85 }]}
            onPress={() => onJoin(t.id)}
            disabled={!!isThisParticipating}
          >
            {isThisParticipating ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <>
                <Ionicons name="rocket" size={14} color="#fff" />
                <Text style={styles.joinButtonText}>🚀 Join Now</Text>
              </>
            )}
          </Pressable>
        )}

        <Pressable
          style={styles.detailsButton}
          onPress={() => setExpanded(!expanded)}
        >
          <Text style={styles.detailsButtonText}>{expanded ? "Hide" : "Details"}</Text>
          <Ionicons name={expanded ? "chevron-up" : "chevron-down"} size={14} color="#6B7280" />
        </Pressable>
      </View>

      {/* Expanded */}
      {expanded && (
        <View style={styles.expandedContent}>
          {t.description && <Text style={styles.expandedDesc}>{t.description}</Text>}
          <View style={styles.infoGrid}>
            <InfoBlock label="Type" value={t.target_type ?? "—"} icon="flag-outline" />
            <InfoBlock label="Target" value={formatTargetValue(t.target_value, t.target_type ?? "")} icon="trophy-outline" />
            <InfoBlock label="Reward" value={`${t.reward_type ?? "—"}${t.reward_value ? ` (${Number(t.reward_value).toLocaleString()})` : ""}`} icon="gift-outline" />
            <InfoBlock label="Window" value={`${formatDate(t.start_date)} – ${formatDate(t.end_date)}`} icon="calendar-outline" />
          </View>
          {type === "participating" && (
            <View style={styles.infoGrid}>
              <InfoBlock label="Achieved" value={formatTargetValue(progress?.achieved, t.target_type ?? "")} icon="trending-up-outline" />
              <InfoBlock label="Remaining" value={formatTargetValue(progress?.remaining, t.target_type ?? "")} icon="hourglass-outline" />
            </View>
          )}
        </View>
      )}
    </View>
  );
}

function InfoBlock({ label, value, icon }: { label: string; value: string; icon: string }) {
  return (
    <View style={styles.infoBlock}>
      <View style={styles.infoBlockHeader}>
        <Ionicons name={icon as any} size={12} color="#9CA3AF" />
        <Text style={styles.infoBlockLabel}>{label}</Text>
      </View>
      <Text style={styles.infoBlockValue} numberOfLines={1}>{value}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },

  headerCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    margin: 16,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  headerIconBg: {
    width: 40,
    height: 40,
    borderRadius: 14,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },

  loadingState: { justifyContent: "center", alignItems: "center", paddingVertical: 60 },
  emptyState: {
    alignItems: "center",
    paddingVertical: 60,
    paddingHorizontal: 40,
    backgroundColor: "#fff",
    margin: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    borderStyle: "dashed",
  },

  section: { paddingHorizontal: 16, marginBottom: 16 },
  sectionHeader: { flexDirection: "row", alignItems: "center", marginBottom: 12 },
  sectionDot: { width: 8, height: 8, borderRadius: 4, marginRight: 8 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#1A1A2E", flex: 1 },
  sectionBadge: { backgroundColor: ACCENT, borderRadius: 10, paddingHorizontal: 8, paddingVertical: 2 },
  sectionBadgeText: { fontSize: 11, fontWeight: "700", color: "#fff" },

  challengeCard: {
    borderRadius: 16,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1.5,
  },
  challengeTop: { flexDirection: "row", alignItems: "flex-start" },
  challengeIcon: {
    width: 32,
    height: 32,
    borderRadius: 10,
    justifyContent: "center",
    alignItems: "center",
  },
  challengeTitle: { fontSize: 14, fontWeight: "700", color: "#1A1A2E" },
  challengeDate: { fontSize: 11, color: "#9CA3AF", marginTop: 2 },
  statusPill: { paddingHorizontal: 10, paddingVertical: 3, borderRadius: 10 },
  statusPillText: { fontSize: 10, fontWeight: "700" },

  progressSection: { marginTop: 12 },
  progressLabels: { flexDirection: "row", justifyContent: "space-between", marginBottom: 4 },
  progressLabel: { fontSize: 11, color: "#6B7280", fontWeight: "500" },
  progressPercent: { fontSize: 11, fontWeight: "700", color: "#1A1A2E" },
  progressTrack: { height: 8, borderRadius: 4, backgroundColor: "rgba(255,255,255,0.8)", overflow: "hidden" },
  progressFill: { height: "100%", borderRadius: 4 },

  actionRow: { flexDirection: "row", alignItems: "center", gap: 8, marginTop: 12 },
  claimButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#059669",
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
  },
  claimButtonText: { fontSize: 12, fontWeight: "700", color: "#fff" },
  joinButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: ACCENT,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
  },
  joinButtonText: { fontSize: 12, fontWeight: "700", color: "#fff" },
  detailsButton: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    backgroundColor: "#fff",
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 10,
  },
  detailsButtonText: { fontSize: 12, fontWeight: "500", color: "#6B7280" },

  expandedContent: {
    marginTop: 12,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: "rgba(0,0,0,0.06)",
  },
  expandedDesc: { fontSize: 13, color: "#374151", lineHeight: 18, marginBottom: 10 },
  infoGrid: { flexDirection: "row", flexWrap: "wrap", gap: 8, marginBottom: 8 },
  infoBlock: {
    flex: 1,
    minWidth: "45%",
    backgroundColor: "#fff",
    borderRadius: 10,
    padding: 10,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  infoBlockHeader: { flexDirection: "row", alignItems: "center", gap: 4, marginBottom: 4 },
  infoBlockLabel: { fontSize: 10, color: "#9CA3AF", fontWeight: "500" },
  infoBlockValue: { fontSize: 12, fontWeight: "600", color: "#1A1A2E" },
});
