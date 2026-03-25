import { useCallback } from "react";
import {
  View,
  FlatList,
  StyleSheet,
  ActivityIndicator,
  RefreshControl,
} from "react-native";
import { Text } from "tamagui";
import { Stack } from "expo-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { Ionicons } from "@expo/vector-icons";

import apiClient from "@/lib/api-client";

const ACCENT = "#E5005F";

export default function TeamMembersScreen() {
  const queryClient = useQueryClient();

  const teamQuery = useQuery({
    queryKey: ["team-members"],
    queryFn: async () => {
      const { data } = await apiClient.get("/teams");
      return data?.data ?? data ?? [];
    },
  });

  const members: any[] = Array.isArray(teamQuery.data) ? teamQuery.data : [];

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["team-members"] });
  }, [queryClient]);

  const renderItem = ({ item }: { item: any }) => (
    <View style={styles.card}>
      <View style={styles.avatar}>
        <Text style={styles.avatarText}>
          {(item.name ?? "U").charAt(0).toUpperCase()}
        </Text>
      </View>
      <View style={styles.info}>
        <Text style={styles.name} numberOfLines={1}>
          {item.name ?? "Unknown"}
        </Text>
        {item.email && (
          <Text style={styles.email} numberOfLines={1}>
            {item.email}
          </Text>
        )}
        {item.phone && (
          <Text style={styles.phone}>{item.phone}</Text>
        )}
      </View>
      <View style={styles.statusContainer}>
        {item.status && (
          <View style={[
            styles.statusBadge,
            item.status === "Active" || item.status === "active"
              ? { backgroundColor: "#D1FAE5" }
              : { backgroundColor: "#F3F4F6" },
          ]}>
            <Text style={[
              styles.statusText,
              item.status === "Active" || item.status === "active"
                ? { color: "#065F46" }
                : { color: "#6B7280" },
            ]}>
              {item.status}
            </Text>
          </View>
        )}
      </View>
    </View>
  );

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Team Members",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <FlatList
        data={members}
        renderItem={renderItem}
        keyExtractor={(item, i) => String(item.id ?? i)}
        style={styles.container}
        contentContainerStyle={[
          styles.listContent,
          members.length === 0 && { flex: 1 },
        ]}
        refreshControl={
          <RefreshControl
            refreshing={teamQuery.isRefetching}
            onRefresh={onRefresh}
            tintColor={ACCENT}
          />
        }
        ListEmptyComponent={
          teamQuery.isLoading ? (
            <View style={styles.emptyState}>
              <ActivityIndicator size="large" color={ACCENT} />
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="people-outline" size={48} color="#D1D5DB" />
              <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                No team members yet
              </Text>
            </View>
          )
        }
      />
    </>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F8F8FA" },
  listContent: { padding: 16, gap: 10, paddingBottom: 40 },
  emptyState: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingBottom: 60,
  },
  card: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
    gap: 12,
  },
  avatar: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
  },
  avatarText: {
    fontSize: 18,
    fontWeight: "700",
    color: ACCENT,
  },
  info: { flex: 1 },
  name: {
    fontSize: 15,
    fontWeight: "600",
    color: "#1A1A2E",
  },
  email: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 2,
  },
  phone: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 1,
  },
  statusContainer: {},
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 11,
    fontWeight: "600",
  },
});
