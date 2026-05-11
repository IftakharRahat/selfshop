import { Pressable, StyleSheet, ScrollView, View, Text as RNText } from "react-native";
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

const DATE_ONLY_RE = /^(\d{4})-(\d{2})-(\d{2})$/;
const LOCAL_DATE_TIME_RE = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/;
const TZ_RE = /(z|[+-]\d{2}:?\d{2})$/i;

function startOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

function endOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(23, 59, 59, 999);
  return d;
}

export function parseFilterDate(dateValue: string | Date | undefined | null): Date | null {
  if (!dateValue) return null;
  if (dateValue instanceof Date) return isNaN(dateValue.getTime()) ? null : dateValue;

  const value = dateValue.trim();
  const dateOnlyMatch = value.match(DATE_ONLY_RE);
  if (dateOnlyMatch) {
    const [, year, month, day] = dateOnlyMatch;
    return new Date(Number(year), Number(month) - 1, Number(day));
  }

  if (!TZ_RE.test(value)) {
    const localDateTimeMatch = value.match(LOCAL_DATE_TIME_RE);
    if (localDateTimeMatch) {
      const [, year, month, day, hour, minute, second = "0"] = localDateTimeMatch;
      return new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hour),
        Number(minute),
        Number(second),
      );
    }
  }

  const parsed = new Date(value);
  return isNaN(parsed.getTime()) ? null : parsed;
}

export function getDateRange(filter: DateFilterKey): { from: Date | null; to: Date | null } {
  const now = new Date();
  const todayStart = startOfDay(now);
  const todayEnd = endOfDay(now);

  switch (filter) {
    case "today":
      return { from: todayStart, to: todayEnd };
    case "last7": {
      const from = new Date(todayStart);
      from.setDate(from.getDate() - 6);
      return { from, to: todayEnd };
    }
    case "thisMonth": {
      const from = new Date(now.getFullYear(), now.getMonth(), 1);
      return { from, to: todayEnd };
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

  const parsed = parseFilterDate(dateValue);
  if (!parsed) return false;

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
      style={styles.scroll}
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
            <View style={styles.iconBox}>
              <Ionicons
                name={opt.icon}
                size={14}
                color={active ? "#fff" : "#6B7280"}
              />
            </View>
            <RNText style={[styles.pillLabel, active && styles.pillLabelActive]} numberOfLines={1}>
              {opt.label}
            </RNText>
          </Pressable>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: {
    flexGrow: 0,
    flexShrink: 0,
    height: 50,
    backgroundColor: "#F8F8FA",
  },
  container: {
    paddingHorizontal: 16,
    height: 50,
    alignItems: "center",
  },
  pill: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    flexShrink: 0,
    minWidth: 110,
    paddingHorizontal: 14,
    height: 34,
    marginRight: 8,
    borderRadius: 20,
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  pillActive: {
    backgroundColor: ACCENT,
    borderColor: ACCENT,
  },
  iconBox: {
    width: 16,
    height: 16,
    alignItems: "center",
    justifyContent: "center",
  },
  pillLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#374151",
    flexShrink: 0,
    lineHeight: 16,
    textAlignVertical: "center",
  },
  pillLabelActive: {
    color: "#fff",
  },
});
