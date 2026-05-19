import { useCallback, useState } from "react";
import {
  View,
  FlatList,
  ScrollView,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  RefreshControl,
} from "react-native";
import { Dialog, Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

function getNotificationTitle(item: any): string {
  return item?.title || "Notification";
}

function getNotificationBody(item: any): string {
  const title = getNotificationTitle(item).trim();
  const body = String(item?.message || item?.description || "").trim();
  return body && body !== title ? body : "";
}

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

      {/* Notification detail dialog */}
      <Dialog
        modal
        open={!!selectedItem}
        onOpenChange={(open) => {
          if (!open) setSelectedItem(null);
        }}
      >
        <Dialog.Portal>
          <Dialog.Overlay style={styles.dialogOverlay} />
          <View style={styles.dialogCenterer} pointerEvents="box-none">
            <Dialog.Content style={styles.dialogCard}>
              <View style={styles.dialogHeader}>
                <View style={styles.dialogIcon}>
                  <Ionicons name="notifications" size={22} color={ACCENT} />
                </View>
                <Pressable style={styles.dialogCloseButton} onPress={() => setSelectedItem(null)}>
                  <Ionicons name="close" size={20} color="#6B7280" />
                </Pressable>
              </View>

              <Dialog.Title asChild>
                <Text style={styles.dialogTitle}>{getNotificationTitle(selectedItem)}</Text>
              </Dialog.Title>

              <ScrollView style={styles.dialogBody} showsVerticalScrollIndicator={false}>
                {getNotificationBody(selectedItem) ? (
                  <Dialog.Description asChild>
                    <Text style={styles.dialogBodyText}>
                      {getNotificationBody(selectedItem)}
                    </Text>
                  </Dialog.Description>
                ) : null}
                {selectedItem?.created_at ? (
                  <Text style={styles.dialogTime}>
                    {new Date(selectedItem.created_at).toLocaleString()}
                  </Text>
                ) : null}
              </ScrollView>

              <Pressable
                style={({ pressed }) => [
                  styles.dialogDoneButton,
                  pressed && { opacity: 0.88 },
                ]}
                onPress={() => setSelectedItem(null)}
              >
                <Text fontSize="$3" fontWeight="800" color="#fff">
                  Done
                </Text>
              </Pressable>
            </Dialog.Content>
          </View>
        </Dialog.Portal>
      </Dialog>
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

  dialogOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: "rgba(17, 24, 39, 0.46)",
  },
  dialogCenterer: {
    ...StyleSheet.absoluteFillObject,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 22,
  },
  dialogCard: {
    width: "100%",
    maxWidth: 380,
    backgroundColor: "#fff",
    borderRadius: 24,
    padding: 20,
    shadowColor: "#111827",
    shadowOffset: { width: 0, height: 18 },
    shadowOpacity: 0.2,
    shadowRadius: 28,
    elevation: 12,
  },
  dialogHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 14,
  },
  dialogIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: "#FDF2F8",
    alignItems: "center",
    justifyContent: "center",
  },
  dialogCloseButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#F3F4F6",
  },
  dialogTitle: {
    fontSize: 20,
    fontWeight: "800",
    color: "#1A1A2E",
    marginBottom: 12,
  },
  dialogBody: {
    maxHeight: 300,
  },
  dialogBodyText: {
    fontSize: 14,
    color: "#6B7280",
    lineHeight: 20,
  },
  dialogTime: {
    fontSize: 12,
    color: "#9CA3AF",
    marginTop: 12,
  },
  dialogDoneButton: {
    minHeight: 48,
    borderRadius: 14,
    backgroundColor: ACCENT,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 20,
  },
});
