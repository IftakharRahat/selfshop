import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
  Modal,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

export default function NotificationsScreen() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [selectedItem, setSelectedItem] = useState<any>(null);

  /* ── Query ── */
  const notifQuery = useQuery({
    queryKey: ["notifications", page],
    queryFn: async () => {
      const { data } = await apiClient.get(`/user-notification?per_page=20&page=${page}`);
      return data;
    },
  });

  /* ── Mutations ── */
  const markReadMutation = useMutation({
    mutationFn: async (id: number) => {
      const { data } = await apiClient.post(`/user-notification/${id}/read`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
      queryClient.invalidateQueries({ queryKey: ["notifications-count"] });
    },
  });

  const markAllMutation = useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.post("/user-notification/read-all");
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["notifications"] });
      queryClient.invalidateQueries({ queryKey: ["notifications-count"] });
    },
  });

  /* ── Derived ── */
  const notifications: any[] = notifQuery.data?.data ?? [];
  const unreadCount = notifQuery.data?.unread_count ?? 0;
  const lastPage = notifQuery.data?.last_page ?? 1;

  /* ── Handlers ── */
  const handleItemPress = (item: any) => {
    if (!item.is_read) {
      markReadMutation.mutate(item.id);
    }
    setSelectedItem(item);
  };

  const onRefresh = useCallback(() => {
    setPage(1);
    queryClient.invalidateQueries({ queryKey: ["notifications"] });
  }, [queryClient]);

  const loadMore = () => {
    if (page < lastPage && !notifQuery.isFetching) {
      setPage((p) => p + 1);
    }
  };

  /* ── Render Item ── */
  const renderItem = ({ item }: { item: any }) => {
    const isReview = item.meta?.type === "review_prompt" || item.title === "Rate Your Product";
    const bodyText = item.message || item.description || "";
    const timeStr = item.created_at ? new Date(item.created_at).toLocaleString() : "";

    return (
      <Pressable
        style={({ pressed }) => [
          styles.notifCard,
          !item.is_read && (isReview ? styles.notifUnreadReview : styles.notifUnread),
          pressed && { opacity: 0.85 },
        ]}
        onPress={() => handleItemPress(item)}
      >
        <View style={styles.notifIconWrapper}>
          <Ionicons
            name={isReview ? "star" : "notifications-outline"}
            size={18}
            color={isReview ? "#D97706" : ACCENT}
          />
        </View>
        <View style={styles.notifContent}>
          <Text style={styles.notifTitle} numberOfLines={1}>
            {item.title}
          </Text>
          {bodyText ? (
            <Text style={styles.notifBody} numberOfLines={2}>
              {bodyText}
            </Text>
          ) : null}
          <Text style={styles.notifTime}>{timeStr}</Text>
        </View>
        {!item.is_read && <View style={styles.unreadDot} />}
      </Pressable>
    );
  };

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Notifications",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
          headerRight: () =>
            unreadCount > 0 ? (
              <Pressable
                onPress={() => markAllMutation.mutate()}
                disabled={markAllMutation.isPending}
                style={{ marginRight: 4 }}
              >
                <Text fontSize="$2" color={ACCENT} fontWeight="600">
                  {markAllMutation.isPending ? "..." : "Mark all read"}
                </Text>
              </Pressable>
            ) : null,
        }}
      />
      <FlatList
        data={notifications}
        renderItem={renderItem}
        keyExtractor={(item) => String(item.id)}
        style={styles.container}
        contentContainerStyle={[
          styles.listContent,
          notifications.length === 0 && { flex: 1 },
        ]}
        refreshControl={
          <RefreshControl
            refreshing={notifQuery.isRefetching}
            onRefresh={onRefresh}
            tintColor={ACCENT}
          />
        }
        onEndReached={loadMore}
        onEndReachedThreshold={0.3}
        ListEmptyComponent={
          notifQuery.isLoading ? (
            <View style={styles.emptyState}>
              <ActivityIndicator size="large" color={ACCENT} />
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="notifications-off-outline" size={48} color="#D1D5DB" />
              <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                No notifications yet
              </Text>
            </View>
          )
        }
        ListFooterComponent={
          notifQuery.isFetching && page > 1 ? (
            <ActivityIndicator size="small" color={ACCENT} style={{ marginVertical: 16 }} />
          ) : null
        }
      />

      {/* ── Detail Modal ── */}
      <Modal
        visible={!!selectedItem}
        transparent
        animationType="fade"
        onRequestClose={() => setSelectedItem(null)}
      >
        <Pressable
          style={styles.modalOverlay}
          onPress={() => setSelectedItem(null)}
        >
          <View style={styles.modalCard} onStartShouldSetResponder={() => true}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Notification</Text>
              <Pressable onPress={() => setSelectedItem(null)}>
                <Ionicons name="close" size={22} color="#6B7280" />
              </Pressable>
            </View>

            <View style={styles.modalBody}>
              <Text style={styles.modalItemTitle}>{selectedItem?.title}</Text>
              {(selectedItem?.message || selectedItem?.description) && (
                <Text style={styles.modalBodyText}>
                  {selectedItem?.message || selectedItem?.description}
                </Text>
              )}
              {selectedItem?.created_at && (
                <Text style={styles.modalTime}>
                  {new Date(selectedItem.created_at).toLocaleString()}
                </Text>
              )}
            </View>

            <View style={styles.modalFooter}>
              <Pressable
                style={({ pressed }) => [
                  styles.modalDoneButton,
                  pressed && { opacity: 0.85 },
                ]}
                onPress={() => setSelectedItem(null)}
              >
                <Text fontSize="$3" fontWeight="600" color="#fff">
                  Done
                </Text>
              </Pressable>
            </View>
          </View>
        </Pressable>
      </Modal>
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  listContent: { padding: 16, gap: 8 },
  notifCard: {
    flexDirection: "row",
    alignItems: "flex-start",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    gap: 12,
  },
  notifUnread: {
    backgroundColor: "#EEF2FF",
    borderColor: "#E0E7FF",
  },
  notifUnreadReview: {
    backgroundColor: "#FFFBEB",
    borderColor: "#FEF3C7",
  },
  notifIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginTop: 2,
  },
  notifContent: { flex: 1 },
  notifTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1A1A2E",
    marginBottom: 2,
  },
  notifBody: {
    fontSize: 12,
    color: "#6B7280",
    lineHeight: 17,
    marginBottom: 4,
  },
  notifTime: {
    fontSize: 11,
    color: "#9CA3AF",
  },
  unreadDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: ACCENT,
    marginTop: 6,
  },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
  },

  /* Modal */
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.4)",
    justifyContent: "center",
    alignItems: "center",
    padding: 24,
  },
  modalCard: {
    width: "100%",
    maxWidth: 380,
    backgroundColor: "#fff",
    borderRadius: 20,
    overflow: "hidden",
  },
  modalHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F5",
  },
  modalTitle: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  modalBody: {
    paddingHorizontal: 20,
    paddingVertical: 20,
    gap: 8,
  },
  modalItemTitle: {
    fontSize: 17,
    fontWeight: "700",
    color: "#1A1A2E",
  },
  modalBodyText: {
    fontSize: 14,
    color: "#4B5563",
    lineHeight: 20,
  },
  modalTime: {
    fontSize: 12,
    color: "#9CA3AF",
    marginTop: 4,
  },
  modalFooter: {
    paddingHorizontal: 20,
    paddingVertical: 14,
    borderTopWidth: 1,
    borderTopColor: "#F0F0F5",
    alignItems: "flex-end",
  },
  modalDoneButton: {
    backgroundColor: "#1A1A2E",
    paddingHorizontal: 24,
    paddingVertical: 10,
    borderRadius: 12,
  },
});
