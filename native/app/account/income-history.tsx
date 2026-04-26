import { useCallback, useMemo, useState } from "react";
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
import DateFilter, { DateFilterKey, isWithinDateRange } from "@/components/date-filter";

const ACCENT = "#E5005F";

function formatCurrency(value: number | string | undefined): string {
  const num = Number(value ?? 0);
  return `৳${num.toLocaleString("en-BD")}`;
}

export default function IncomeHistoryScreen() {
  const queryClient = useQueryClient();

  const incomeQuery = useQuery({
    queryKey: ["income-history"],
    queryFn: async () => {
      const { data } = await apiClient.get("/income-history");
      return data?.data ?? data ?? [];
    },
  });

  const [dateFilter, setDateFilter] = useState<DateFilterKey>("all");

  const historyRaw: any[] = Array.isArray(incomeQuery.data) ? incomeQuery.data : [];

  const history = useMemo(
    () => historyRaw.filter((item) => isWithinDateRange(item.date ?? item.created_at, dateFilter)),
    [historyRaw, dateFilter],
  );

  const onRefresh = useCallback(() => {
    queryClient.invalidateQueries({ queryKey: ["income-history"] });
  }, [queryClient]);

  const renderItem = ({ item, index }: { item: any; index: number }) => (
    <View style={styles.card}>
      <View style={styles.cardLeft}>
        <View style={styles.iconWrapper}>
          <Ionicons name="cash-outline" size={18} color={ACCENT} />
        </View>
        <View style={{ flex: 1 }}>
          <Text style={styles.cardTitle} numberOfLines={1}>
            {item.message_for ?? item.type ?? "Income"}
          </Text>
          {item.message && (
            <Text style={styles.cardSubtitle} numberOfLines={2}>
              {item.message}
            </Text>
          )}
          <Text style={styles.cardDate}>
            {item.date ?? (item.created_at ? new Date(item.created_at).toLocaleDateString() : `#${index + 1}`)}
          </Text>
        </View>
      </View>
      <Text style={styles.cardAmount}>
        {formatCurrency(item.amount ?? item.income)}
      </Text>
    </View>
  );

  return (
    <>
      <Stack.Screen
        options={{
          headerShown: true,
          title: "Income History",
          headerShadowVisible: false,
          headerStyle: { backgroundColor: "#F8F8FA" },
        }}
      />
      <FlatList
        data={history}
        renderItem={renderItem}
        keyExtractor={(item, i) => String(item.id ?? i)}
        style={styles.container}
        contentContainerStyle={[
          styles.listContent,
          history.length === 0 && { flex: 1 },
        ]}
        ListHeaderComponent={
          <DateFilter value={dateFilter} onChange={setDateFilter} />
        }
        refreshControl={
          <RefreshControl
            refreshing={incomeQuery.isRefetching}
            onRefresh={onRefresh}
            tintColor={ACCENT}
          />
        }
        ListEmptyComponent={
          incomeQuery.isLoading ? (
            <View style={styles.emptyState}>
              <ActivityIndicator size="large" color={ACCENT} />
            </View>
          ) : (
            <View style={styles.emptyState}>
              <Ionicons name="cash-outline" size={48} color="#D1D5DB" />
              <Text fontSize="$4" fontWeight="600" color="#9CA3AF" mt="$3">
                No income history yet
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
    justifyContent: "space-between",
    alignItems: "flex-start",
    backgroundColor: "#fff",
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: "#F0F0F5",
  },
  cardLeft: {
    flexDirection: "row",
    alignItems: "flex-start",
    gap: 12,
    flex: 1,
    marginRight: 12,
  },
  iconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: "#FDF2F8",
    justifyContent: "center",
    alignItems: "center",
    marginTop: 2,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1A1A2E",
  },
  cardSubtitle: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 2,
    lineHeight: 17,
  },
  cardDate: {
    fontSize: 11,
    color: "#9CA3AF",
    marginTop: 3,
  },
  cardAmount: {
    fontSize: 15,
    fontWeight: "700",
    color: "#059669",
  },
});
