import React from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  TouchableOpacity,
  ActivityIndicator,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { toast } from "sonner-native";
import { BRAND } from "@/lib/constants";
import apiClient from "@/lib/api-client";

interface NotificationItem {
  id: string;
  title: string;
  message: string;
  type: string;
  action_url?: string | null;
  is_read: boolean;
  read_at?: string | null;
  created_at?: string | null;
}

const TYPE_ICONS: Record<string, { icon: keyof typeof Ionicons.glyphMap; color: string }> = {
  info: { icon: "information-circle", color: "#3b82f6" },
  success: { icon: "checkmark-circle", color: "#10b981" },
  warning: { icon: "warning", color: "#f59e0b" },
  error: { icon: "alert-circle", color: "#ef4444" },
  order: { icon: "clipboard", color: "#6366f1" },
};

export default function NotificationsScreen() {
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  const { data, isLoading, isError, refetch, isRefetching } = useQuery({
    queryKey: ["vendor-notifications"],
    queryFn: async () => {
      const { data } = await apiClient.get("/vendor/notifications", { params: { per_page: 50 } });
      return data?.data as { notifications: NotificationItem[]; unread_count: number };
    },
  });

  const markReadMutation = useMutation({
    mutationFn: async (id: string) => {
      await apiClient.post(`/vendor/notifications/${id}/read`);
    },
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ["vendor-notifications"] }),
  });

  const markAllReadMutation = useMutation({
    mutationFn: async () => {
      await apiClient.post("/vendor/notifications/read-all");
    },
    onSuccess: () => {
      toast.success("All notifications marked as read");
      queryClient.invalidateQueries({ queryKey: ["vendor-notifications"] });
    },
  });

  const notifications = data?.notifications ?? [];
  const unreadCount = data?.unread_count ?? 0;

  const getTimeAgo = (dateStr?: string | null) => {
    if (!dateStr) return "";
    const diff = Date.now() - new Date(dateStr).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 60) return `${mins}m ago`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
  };

  return (
    <View style={[styles.container, { paddingTop: insets.top }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={22} color="#1a1a2e" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Notifications</Text>
        {unreadCount > 0 ? (
          <TouchableOpacity onPress={() => markAllReadMutation.mutate()} disabled={markAllReadMutation.isPending}>
            <Text style={styles.markAllText}>Read All</Text>
          </TouchableOpacity>
        ) : (
          <View style={{ width: 60 }} />
        )}
      </View>

      {unreadCount > 0 && (
        <View style={styles.unreadBanner}>
          <Ionicons name="notifications" size={14} color={BRAND.primary} />
          <Text style={styles.unreadBannerText}>{unreadCount} unread notification{unreadCount > 1 ? "s" : ""}</Text>
        </View>
      )}

      {isLoading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={BRAND.primary} />
        </View>
      ) : isError ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="alert-circle-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>Failed to load notifications</Text>
        </View>
      ) : notifications.length === 0 ? (
        <View style={styles.emptyContainer}>
          <Ionicons name="notifications-off-outline" size={48} color="#d1d5db" />
          <Text style={styles.emptyText}>No notifications yet</Text>
        </View>
      ) : (
        <FlatList
          data={notifications}
          keyExtractor={(item) => item.id}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={BRAND.primary} />
          }
          renderItem={({ item }) => {
            const typeConfig = TYPE_ICONS[item.type] ?? TYPE_ICONS.info;
            return (
              <TouchableOpacity
                style={[styles.notifCard, !item.is_read && styles.notifCardUnread]}
                onPress={() => {
                  if (!item.is_read) markReadMutation.mutate(item.id);
                }}
                activeOpacity={0.7}
              >
                <View style={[styles.notifIcon, { backgroundColor: typeConfig.color + "15" }]}>
                  <Ionicons name={typeConfig.icon} size={20} color={typeConfig.color} />
                </View>
                <View style={styles.notifContent}>
                  <View style={styles.notifTitleRow}>
                    <Text style={styles.notifTitle} numberOfLines={1}>{item.title}</Text>
                    {!item.is_read && <View style={styles.unreadDot} />}
                  </View>
                  <Text style={styles.notifMessage} numberOfLines={2}>{item.message}</Text>
                  <Text style={styles.notifTime}>{getTimeAgo(item.created_at)}</Text>
                </View>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={<View style={{ height: 20 }} />}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#f9fafb" },
  header: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: "#fff",
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  backBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  headerTitle: { fontSize: 17, fontWeight: "600", color: "#1a1a2e" },
  markAllText: { fontSize: 13, fontWeight: "600", color: BRAND.primary },
  unreadBanner: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: BRAND.primaryBg,
    paddingHorizontal: 16,
    paddingVertical: 8,
  },
  unreadBannerText: { fontSize: 12, fontWeight: "500", color: BRAND.primary },
  listContent: { padding: 12, gap: 8 },
  notifCard: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: "#f3f4f6",
    gap: 12,
  },
  notifCardUnread: {
    backgroundColor: "#FAFAFF",
    borderColor: BRAND.primaryLight,
  },
  notifIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: "center",
    justifyContent: "center",
  },
  notifContent: { flex: 1 },
  notifTitleRow: { flexDirection: "row", alignItems: "center", justifyContent: "space-between" },
  notifTitle: { fontSize: 14, fontWeight: "600", color: "#1a1a2e", flex: 1 },
  unreadDot: { width: 8, height: 8, borderRadius: 4, backgroundColor: BRAND.primary, marginLeft: 8 },
  notifMessage: { fontSize: 12, color: "#6b7280", marginTop: 3, lineHeight: 17 },
  notifTime: { fontSize: 10, color: "#9ca3af", marginTop: 4 },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  emptyContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
});
