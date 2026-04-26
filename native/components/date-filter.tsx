import { Pressable, StyleSheet, ScrollView } from "react-native";
import { Text } from "tamagui";
import { Ionicons } from "@expo/vector-icons";

const ACCENT = "#E5005F";

export type DateFilterKey = "all" | "today" | "last7" | "thisMonth";

interface DateFilterOption {
  key: DateFilterKey;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
}

const DATE_OPTIONS: DateFilterOption[] = [
  { key: "all", label: "All Time", icon: "layers-outline" },
  { key: "today", label: "Today", icon: "today-outline" },
  { key: "last7", label: "Last 7 Days", icon: "calendar-outline" },
  { key: "thisMonth", label: "This Month", icon: "calendar-number-outline" },
];

function startOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

export function getDateRange(filter: DateFilterKey): { from: Date | null; to: Date | null } {
  const now = new Date();
  const todayStart = startOfDay(now);

  switch (filter) {
    case "today":
      return { from: todayStart, to: now };
    case "last7": {
      const from = new Date(todayStart);
      from.setDate(from.getDate() - 6);
      return { from, to: now };
    }
    case "thisMonth": {
      const from = new Date(now.getFullYear(), now.getMonth(), 1);
      return { from, to: now };
    }
    default:
      return { from: null, to: null };
  }
}

export function isWithinDateRange(
  dateValue: string | Date | undefined | null,
  filter: DateFilterKey,
): boolean {
  if (filter === "all") return true;
  if (!dateValue) return false;

  const parsed = typeof dateValue === "string" ? new Date(dateValue) : dateValue;
  if (isNaN(parsed.getTime())) return false;

  const { from, to } = getDateRange(filter);
  if (!from || !to) return true;
  return parsed >= from && parsed <= to;
}

interface DateFilterProps {
  value: DateFilterKey;
  onChange: (key: DateFilterKey) => void;
}

export default function DateFilter({ value, onChange }: DateFilterProps) {
  return (
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      style={{ flexGrow: 0 }}
      contentContainerStyle={styles.container}
    >
      {DATE_OPTIONS.map((opt) => {
        const active = opt.key === value;
        return (
          <Pressable
            key={opt.key}
            style={[styles.pill, active && styles.pillActive]}
            onPress={() => onChange(opt.key)}
          >
            <Ionicons
              name={opt.icon}
              size={14}
              color={active ? "#fff" : "#6B7280"}
            />
            <Text style={[styles.pillLabel, active && styles.pillLabelActive]}>
              {opt.label}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    gap: 8,
  },
  pill: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  pillActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  pillLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#374151",
  },
  pillLabelActive: {
    color: "#fff",
  },
});