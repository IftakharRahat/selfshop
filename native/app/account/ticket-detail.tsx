import { useEffect, useState, useRef } from "react";
import {
  View,
  ScrollView,
  TextInput,
  StyleSheet,
  Pressable,
  ActivityIndicator,
  Keyboard,
  Platform,
} from "react-native";
import { Text } from "tamagui";
import { Stack, useLocalSearchParams } from "expo-router";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import apiClient from "@/lib/api-client";

const STATUS_CONFIG: Record<string, { color: string; bg: string; label: string }> = {
  open: { color: "#E5005F", bg: "#FDF2F8", label: "Open" },
  in_progress: { color: "#2196F3", bg: "#E3F2FD", label: "In Progress" },
  resolved: { color: "#4CAF50", bg: "#E8F5E9", label: "Resolved" },
  closed: { color: "#8E8E93", bg: "#F5F5F5", label: "Closed" },
};

export default function TicketDetailScreen() {
  const { ticketId } = useLocalSearchParams<{ ticketId: string }>();
  const queryClient = useQueryClient();
  const scrollRef = useRef<ScrollView>(null);
  const [replyText, setReplyText] = useState("");
  const [keyboardHeight, setKeyboardHeight] = useState(0);
  const [replyBarHeight, setReplyBarHeight] = useState(88);
  const insets = useSafeAreaInsets();

  const ticketQuery = useQuery({
    queryKey: ["ticket", ticketId],
    queryFn: async () => {
      const { data } = await apiClient.get(`/view-tikit/${ticketId}`);
      return data?.data ?? data;
    },
    enabled: !!ticketId,
  });

  const replyMutation = useMutation({
    mutationFn: () =>
      apiClient.post(`/replay-tikit/${ticketId}`, {
        replay: replyText,
      }),
    onSuccess: () => {
      setReplyText("");
      queryClient.invalidateQueries({ queryKey: ["ticket", ticketId] });
      queryClient.invalidateQueries({ queryKey: ["tickets"] });
      setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 300);
    },
  });

  const ticket = ticketQuery.data?.ticket ?? ticketQuery.data;
  const replies = ticketQuery.data?.replays ?? ticket?.replies ?? [];
  const composerBottom = keyboardHeight > 0 ? keyboardHeight + 8 : Math.max(insets.bottom, 14);
  const scrollBottomPadding = replyBarHeight + composerBottom + 20;

  useEffect(() => {
    const showEvent = Platform.OS === "ios" ? "keyboardWillShow" : "keyboardDidShow";
    const hideEvent = Platform.OS === "ios" ? "keyboardWillHide" : "keyboardDidHide";
    const showSub = Keyboard.addListener(showEvent, (event) => {
      setKeyboardHeight(Math.max(event.endCoordinates.height - insets.bottom, 0));
      setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 80);
    });
    const hideSub = Keyboard.addListener(hideEvent, () => setKeyboardHeight(0));

    return () => {
      showSub.remove();
      hideSub.remove();
    };
  }, [insets.bottom]);

  if (ticketQuery.isLoading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#E5005F" />
      </View>
    );
  }

  if (!ticket) {
    return (
      <>
        <Stack.Screen options={{ headerShown: true, title: "Ticket" }} />
        <View style={styles.emptyState}>
          <Text fontSize="$4" color="#8E8E93">Ticket not found</Text>
        </View>
      </>
    );
  }

  const status = STATUS_CONFIG[ticket.status] ?? STATUS_CONFIG.open;
  const canReply = String(ticket.status ?? "").toLowerCase() !== "closed";

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: ticket.ticketNumber ?? ticket.ticket_number ?? "Ticket Details",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#fff" },
        }}
      />
      <View style={styles.wrapper}>
        <ScrollView
          ref={scrollRef}
          style={styles.container}
          contentContainerStyle={[
            styles.scrollContent,
            { paddingBottom: canReply ? scrollBottomPadding : Math.max(insets.bottom, 18) },
          ]}
          keyboardDismissMode={Platform.OS === "ios" ? "interactive" : "on-drag"}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          {/* Ticket Info */}
          <View style={styles.ticketInfo}>
            <View style={[styles.statusBadge, { backgroundColor: status.bg }]}>
              <Text fontSize="$3" fontWeight="bold" style={{ color: status.color }}>
                {status.label}
              </Text>
            </View>
            <Text fontSize="$5" fontWeight="bold" color="#1A1A2E" mt="$2">
              {ticket.subject}
            </Text>
            <Text fontSize="$2" color="#8E8E93" mt="$1">
              {new Date(ticket.createdAt ?? ticket.created_at).toLocaleDateString("en-BD", {
                day: "numeric",
                month: "long",
                year: "numeric",
              })}
            </Text>
          </View>

          {/* Original Message */}
          <View style={styles.messageSection}>
            <View style={[styles.messageBubble, styles.userBubble]}>
              <Text fontSize="$2" color="#E5005F" fontWeight="600" mb="$1">
                You
              </Text>
              <Text fontSize="$3" color="#1A1A2E">
                {ticket.message}
              </Text>
            </View>

            {/* Replies */}
            {replies.map((reply: any) => {
              const isStaff = (reply.isStaffReply ?? reply.is_staff_reply) ?? reply.type === "Admin";
              return (
                <View
                  key={reply.id}
                  style={[
                    styles.messageBubble,
                    isStaff ? styles.staffBubble : styles.userBubble,
                  ]}
                >
                  <Text
                    fontSize="$2"
                    color={isStaff ? "#2196F3" : "#E5005F"}
                    fontWeight="600"
                    mb="$1"
                  >
                    {isStaff ? (reply.user?.name ?? reply.users?.name ?? "Support") : "You"}
                  </Text>
                  <Text fontSize="$3" color="#1A1A2E">
                    {reply.message ?? reply.replay}
                  </Text>
                  <Text fontSize={10} color="#C7C7CC" mt="$1">
                    {new Date(reply.createdAt ?? reply.created_at).toLocaleString("en-BD", {
                      day: "numeric",
                      month: "short",
                      hour: "2-digit",
                      minute: "2-digit",
                    })}
                  </Text>
                </View>
              );
            })}
          </View>

        </ScrollView>

        {/* Reply Input */}
        {canReply && (
          <View
            onLayout={(event) => setReplyBarHeight(event.nativeEvent.layout.height)}
            style={[
              styles.replyBar,
              { bottom: composerBottom },
            ]}
          >
            <TextInput
              style={styles.replyInput}
              value={replyText}
              onChangeText={setReplyText}
              placeholder="Type your reply..."
              placeholderTextColor="#C7C7CC"
              multiline
              textAlignVertical="top"
              onFocus={() => setTimeout(() => scrollRef.current?.scrollToEnd({ animated: true }), 120)}
            />
            <Pressable
              style={({ pressed }) => [
                styles.sendButton,
                (!replyText.trim() || replyMutation.isPending) && { opacity: 0.4 },
                pressed && { opacity: 0.7 },
              ]}
              onPress={() => replyMutation.mutate()}
              disabled={!replyText.trim() || replyMutation.isPending}
            >
              {replyMutation.isPending ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Ionicons name="send" size={18} color="#fff" />
              )}
            </Pressable>
          </View>
        )}
      </View>
    </>
  );
}

const styles = StyleSheet.create({
  wrapper: { flex: 1, backgroundColor: "#F8F8F8" },
  container: { flex: 1 },
  scrollContent: {
    flexGrow: 1,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#fff",
  },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
  },
  ticketInfo: {
    backgroundColor: "#fff",
    paddingHorizontal: 20,
    paddingTop: 18,
    paddingBottom: 22,
    borderBottomWidth: 1,
    borderBottomColor: "#F0F0F0",
    alignItems: "flex-start",
  },
  statusBadge: {
    paddingHorizontal: 14,
    paddingVertical: 5,
    borderRadius: 12,
  },
  messageSection: {
    flexGrow: 1,
    paddingHorizontal: 16,
    paddingTop: 18,
    gap: 12,
  },
  messageBubble: {
    paddingHorizontal: 16,
    paddingVertical: 13,
    borderRadius: 18,
    maxWidth: "82%",
    minWidth: 96,
  },
  userBubble: {
    backgroundColor: "#FDF2F8",
    alignSelf: "flex-end",
    borderBottomRightRadius: 4,
    borderWidth: 1,
    borderColor: "#FCE7F3",
  },
  staffBubble: {
    backgroundColor: "#E3F2FD",
    alignSelf: "flex-start",
    borderBottomLeftRadius: 4,
    borderWidth: 1,
    borderColor: "#BBDEFB",
  },
  replyBar: {
    position: "absolute",
    left: 14,
    right: 14,
    flexDirection: "row",
    alignItems: "flex-end",
    backgroundColor: "#fff",
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: "#F0F0F0",
    borderWidth: 1,
    borderColor: "#F2F2F4",
    borderRadius: 26,
    gap: 12,
    shadowColor: "#000",
    shadowOpacity: 0.06,
    shadowRadius: 12,
    shadowOffset: { width: 0, height: -3 },
    elevation: 8,
  },
  replyInput: {
    flex: 1,
    minHeight: 46,
    maxHeight: 120,
    borderWidth: 1,
    borderColor: "#E8E8E8",
    borderRadius: 23,
    paddingHorizontal: 16,
    paddingTop: Platform.OS === "ios" ? 12 : 10,
    paddingBottom: Platform.OS === "ios" ? 12 : 9,
    fontSize: 16,
    color: "#1A1A2E",
    backgroundColor: "#FAFAFA",
    lineHeight: 20,
  },
  sendButton: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: "#E5005F",
    justifyContent: "center",
    alignItems: "center",
  },
});
