import React, { useState } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  TouchableOpacity,
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
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
  description?: string | null;
  type: string;
  action_url?: string | null;
  meta?: Record<string, unknown>;
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
  const [selectedNotification, setSelectedNotification] = useState<NotificationItem | null>(null);

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

  const handleNotificationPress = (item: NotificationItem) => {
    if (!item.is_read) {
      markReadMutation.mutate(item.id);
    }
    setSelectedNotification({ ...item, is_read: true });
  };

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

  const formatDateTime = (dateStr?: string | null) => {
    if (!dateStr) return "";
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return "";
    return date.toLocaleString();
  };

  const selectedTypeConfig = selectedNotification
    ? TYPE_ICONS[selectedNotification.type] ?? TYPE_ICONS.info
    : TYPE_ICONS.info;
  const selectedMessage = selectedNotification?.message || selectedNotification?.description || "";

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
                onPress={() => handleNotificationPress(item)}
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
                  <Text style={styles.notifMessage} numberOfLines={2}>{item.message || item.description}</Text>
                  <View style={styles.notifFooter}>
                    <Text style={styles.notifTime}>{getTimeAgo(item.created_at)}</Text>
                    <View style={styles.detailsHint}>
                      <Text style={styles.detailsHintText}>Details</Text>
                      <Ionicons name="chevron-forward" size={12} color={BRAND.primary} />
                    </View>
                  </View>
                </View>
              </TouchableOpacity>
            );
          }}
          ListFooterComponent={<View style={{ height: 20 }} />}
        />
      )}

      <Modal
        visible={!!selectedNotification}
        transparent
        animationType="fade"
        onRequestClose={() => setSelectedNotification(null)}
      >
        <Pressable style={styles.modalOverlay} onPress={() => setSelectedNotification(null)}>
          <Pressable style={styles.modalCard} onPress={(event) => event.stopPropagation()}>
            <View style={styles.modalHeader}>
              <View style={styles.modalHeaderLeft}>
                <View style={[styles.modalIcon, { backgroundColor: selectedTypeConfig.color + "15" }]}>
                  <Ionicons name={selectedTypeConfig.icon} size={20} color={selectedTypeConfig.color} />
                </View>
                <View>
                  <Text style={styles.modalEyebrow}>Notification</Text>
                  <Text style={styles.modalTitle} numberOfLines={1}>{selectedNotification?.type ?? "Info"}</Text>
                </View>
              </View>
              <TouchableOpacity onPress={() => setSelectedNotification(null)} style={styles.modalCloseBtn}>
                <Ionicons name="close" size={22} color="#6b7280" />
              </TouchableOpacity>
            </View>

            <ScrollView style={styles.modalScroll} contentContainerStyle={styles.modalBody}>
              <Text style={styles.modalItemTitle}>{selectedNotification?.title}</Text>
              {selectedMessage ? (
                <Text style={styles.modalMessage}>{selectedMessage}</Text>
              ) : (
                <Text style={styles.modalMessageMuted}>No additional message was provided.</Text>
              )}
              {selectedNotification?.created_at && (
                <View style={styles.modalMetaRow}>
                  <Ionicons name="time-outline" size={14} color="#9ca3af" />
                  <Text style={styles.modalTime}>{formatDateTime(selectedNotification.created_at)}</Text>
                </View>
              )}
            </ScrollView>

            <View style={styles.modalFooter}>
              <TouchableOpacity style={styles.modalDoneButton} onPress={() => setSelectedNotification(null)} activeOpacity={0.85}>
                <Text style={styles.modalDoneText}>Done</Text>
              </TouchableOpacity>
            </View>
          </Pressable>
        </Pressable>
      </Modal>
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
  notifFooter: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 4,
  },
  detailsHint: {
    flexDirection: "row",
    alignItems: "center",
    gap: 2,
  },
  detailsHintText: { fontSize: 11, fontWeight: "600", color: BRAND.primary },
  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center" },
  emptyContainer: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8 },
  emptyText: { fontSize: 14, color: "#9ca3af" },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(15, 23, 42, 0.46)",
    justifyContent: "center",
    alignItems: "center",
    padding: 22,
  },
  modalCard: {
    width: "100%",
    maxWidth: 390,
    maxHeight: "78%",
    backgroundColor: "#fff",
    borderRadius: 20,
    overflow: "hidden",
  },
  modalHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  modalHeaderLeft: { flexDirection: "row", alignItems: "center", gap: 10, flex: 1 },
  modalIcon: {
    width: 40,
    height: 40,
    borderRadius: 12,
    alignItems: "center",
    justifyContent: "center",
  },
  modalEyebrow: { fontSize: 11, fontWeight: "600", color: "#9ca3af", textTransform: "uppercase" },
  modalTitle: { fontSize: 15, fontWeight: "700", color: "#1a1a2e", textTransform: "capitalize" },
  modalCloseBtn: { width: 32, height: 32, alignItems: "center", justifyContent: "center" },
  modalScroll: { maxHeight: 300 },
  modalBody: { paddingHorizontal: 18, paddingVertical: 18, gap: 10 },
  modalItemTitle: { fontSize: 18, fontWeight: "700", color: "#1a1a2e", lineHeight: 24 },
  modalMessage: { fontSize: 14, color: "#4b5563", lineHeight: 21 },
  modalMessageMuted: { fontSize: 13, color: "#9ca3af", lineHeight: 19 },
  modalMetaRow: { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 2 },
  modalTime: { fontSize: 12, color: "#9ca3af" },
  modalFooter: {
    paddingHorizontal: 18,
    paddingVertical: 14,
    borderTopWidth: 1,
    borderTopColor: "#f3f4f6",
    alignItems: "flex-end",
  },
  modalDoneButton: {
    backgroundColor: BRAND.primary,
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 12,
  },
  modalDoneText: { fontSize: 13, fontWeight: "700", color: "#fff" },
});
