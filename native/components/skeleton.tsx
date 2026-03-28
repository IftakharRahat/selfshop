import { useEffect, useRef } from "react";
import { View, Animated, StyleSheet, type ViewStyle } from "react-native";

const SHIMMER_DURATION = 1200;

/**
 * A single shimmer bar/block — the building block for skeleton screens.
 */
export function SkeletonBlock({
  width = "100%",
  height = 14,
  borderRadius = 8,
  style,
}: {
  width?: number | string;
  height?: number | string;
  borderRadius?: number;
  style?: ViewStyle;
}) {
  const opacity = useRef(new Animated.Value(0.35)).current;

  useEffect(() => {
    const anim = Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, { toValue: 0.7, duration: SHIMMER_DURATION, useNativeDriver: true }),
        Animated.timing(opacity, { toValue: 0.35, duration: SHIMMER_DURATION, useNativeDriver: true }),
      ])
    );
    anim.start();
    return () => anim.stop();
  }, []);

  return (
    <Animated.View
      style={[
        {
          width: width as any,
          height: height as any,
          borderRadius,
          backgroundColor: "#E5E5EA",
          opacity,
        },
        style,
      ]}
    />
  );
}

/* ═══ PRESET SKELETON SCREENS ═══ */

const ACCENT_BG = "#F5F5FA";

/** Home screen skeleton — banner + categories + product rows */
export function HomeSkeleton() {
  return (
    <View style={p.container}>
      {/* Header */}
      <View style={p.hPadding}>
        <SkeletonBlock width={120} height={28} borderRadius={6} />
      </View>
      {/* Search bar */}
      <View style={[p.hPadding, { marginTop: 14 }]}>
        <SkeletonBlock height={44} borderRadius={12} />
      </View>
      {/* Banner */}
      <View style={{ marginTop: 16, paddingHorizontal: 16 }}>
        <SkeletonBlock height={160} borderRadius={16} />
      </View>
      {/* Category chips */}
      <View style={[p.row, { marginTop: 20, paddingHorizontal: 16, gap: 8 }]}>
        {Array.from({ length: 5 }).map((_, i) => (
          <View key={i} style={{ alignItems: "center", gap: 6 }}>
            <SkeletonBlock width={56} height={56} borderRadius={28} />
            <SkeletonBlock width={48} height={10} />
          </View>
        ))}
      </View>
      {/* Section */}
      <View style={[p.hPadding, { marginTop: 24 }]}>
        <SkeletonBlock width={140} height={18} borderRadius={6} />
      </View>
      {/* Product row */}
      <View style={[p.row, { marginTop: 12, paddingHorizontal: 16, gap: 12 }]}>
        {Array.from({ length: 2 }).map((_, i) => (
          <View key={i} style={p.gridCard}>
            <SkeletonBlock width="100%" height={130} borderRadius={10} />
            <SkeletonBlock width="75%" height={12} style={{ marginTop: 10, marginLeft: 10 }} />
            <SkeletonBlock width="50%" height={12} style={{ marginTop: 6, marginLeft: 10 }} />
            <SkeletonBlock width="35%" height={16} style={{ marginTop: 8, marginLeft: 10, marginBottom: 10 }} />
          </View>
        ))}
      </View>
      {/* Second section */}
      <View style={[p.hPadding, { marginTop: 24 }]}>
        <SkeletonBlock width={160} height={18} borderRadius={6} />
      </View>
      <View style={[p.row, { marginTop: 12, paddingHorizontal: 16, gap: 12 }]}>
        {Array.from({ length: 2 }).map((_, i) => (
          <View key={i} style={p.gridCard}>
            <SkeletonBlock width="100%" height={130} borderRadius={10} />
            <SkeletonBlock width="70%" height={12} style={{ marginTop: 10, marginLeft: 10 }} />
            <SkeletonBlock width="40%" height={12} style={{ marginTop: 6, marginLeft: 10 }} />
            <SkeletonBlock width="30%" height={16} style={{ marginTop: 8, marginLeft: 10, marginBottom: 10 }} />
          </View>
        ))}
      </View>
    </View>
  );
}

/** Product detail skeleton — image + title + price + description */
export function ProductDetailSkeleton() {
  return (
    <View style={p.container}>
      {/* Hero image */}
      <SkeletonBlock width="100%" height={360} borderRadius={0} />
      {/* Thumbnails */}
      <View style={[p.row, { paddingHorizontal: 16, paddingVertical: 10, gap: 6 }]}>
        {Array.from({ length: 5 }).map((_, i) => (
          <SkeletonBlock key={i} width={52} height={52} borderRadius={8} />
        ))}
      </View>
      {/* Info card */}
      <View style={{ paddingHorizontal: 20, paddingTop: 16 }}>
        <SkeletonBlock width={80} height={22} borderRadius={6} />
        <SkeletonBlock width="90%" height={14} style={{ marginTop: 12 }} />
        <SkeletonBlock width="60%" height={14} style={{ marginTop: 6 }} />
        <SkeletonBlock width={120} height={26} borderRadius={6} style={{ marginTop: 14 }} />
        {/* Meta chips */}
        <View style={[p.row, { marginTop: 16, gap: 8 }]}>
          <SkeletonBlock width={90} height={30} borderRadius={8} />
          <SkeletonBlock width={70} height={30} borderRadius={8} />
          <SkeletonBlock width={80} height={30} borderRadius={8} />
        </View>
      </View>
      {/* Description card */}
      <View style={{ paddingHorizontal: 20, marginTop: 20 }}>
        <SkeletonBlock width={100} height={18} borderRadius={6} />
        <SkeletonBlock width="100%" height={12} style={{ marginTop: 12 }} />
        <SkeletonBlock width="95%" height={12} style={{ marginTop: 6 }} />
        <SkeletonBlock width="80%" height={12} style={{ marginTop: 6 }} />
        <SkeletonBlock width="70%" height={12} style={{ marginTop: 6 }} />
      </View>
    </View>
  );
}

/** Cart skeleton — item cards list */
export function CartSkeleton() {
  return (
    <View style={p.container}>
      <View style={[p.hPadding, { marginTop: 16 }]}>
        <SkeletonBlock width={80} height={24} borderRadius={6} />
      </View>
      {Array.from({ length: 3 }).map((_, i) => (
        <View key={i} style={[p.listCard, { marginTop: 14, marginHorizontal: 16 }]}>
          <SkeletonBlock width={80} height={80} borderRadius={10} />
          <View style={{ flex: 1, marginLeft: 12, gap: 6 }}>
            <SkeletonBlock width="85%" height={13} />
            <SkeletonBlock width="50%" height={13} />
            <SkeletonBlock width={60} height={18} borderRadius={4} style={{ marginTop: 4 }} />
          </View>
        </View>
      ))}
      {/* Summary */}
      <View style={[p.hPadding, { marginTop: 24 }]}>
        <SkeletonBlock width="100%" height={1} borderRadius={0} />
        <View style={[p.row, { justifyContent: "space-between", marginTop: 14 }]}>
          <SkeletonBlock width={80} height={14} />
          <SkeletonBlock width={60} height={14} />
        </View>
        <View style={[p.row, { justifyContent: "space-between", marginTop: 10 }]}>
          <SkeletonBlock width={100} height={18} />
          <SkeletonBlock width={80} height={18} />
        </View>
      </View>
    </View>
  );
}

/** Product grid skeleton (category/collection pages) — header + 2-col grid */
export function ProductGridSkeleton() {
  return (
    <View style={p.container}>
      {/* Header */}
      <View style={[p.row, { paddingHorizontal: 16, paddingTop: 16, gap: 12, alignItems: "center" }]}>
        <SkeletonBlock width={38} height={38} borderRadius={19} />
        <SkeletonBlock width={140} height={20} borderRadius={6} />
      </View>
      {/* Sort bar */}
      <View style={[p.row, { paddingHorizontal: 16, marginTop: 14, gap: 8 }]}>
        <SkeletonBlock width={80} height={32} borderRadius={16} />
        <SkeletonBlock width={90} height={32} borderRadius={16} />
        <SkeletonBlock width={70} height={32} borderRadius={16} />
      </View>
      {/* Grid */}
      <View style={[p.row, { flexWrap: "wrap", paddingHorizontal: 16, marginTop: 16, gap: 12 }]}>
        {Array.from({ length: 6 }).map((_, i) => (
          <View key={i} style={p.gridCard}>
            <SkeletonBlock width="100%" height={130} borderRadius={10} />
            <SkeletonBlock width="75%" height={12} style={{ marginTop: 10, marginLeft: 10 }} />
            <SkeletonBlock width="45%" height={12} style={{ marginTop: 6, marginLeft: 10 }} />
            <SkeletonBlock width="35%" height={16} style={{ marginTop: 8, marginLeft: 10, marginBottom: 10 }} />
          </View>
        ))}
      </View>
    </View>
  );
}

/** Orders list skeleton */
export function OrdersSkeleton() {
  return (
    <View style={p.container}>
      <View style={[p.hPadding, { marginTop: 16 }]}>
        <SkeletonBlock width={100} height={24} borderRadius={6} />
      </View>
      {/* Tab bar */}
      <View style={[p.row, { paddingHorizontal: 16, marginTop: 14, gap: 10 }]}>
        {Array.from({ length: 4 }).map((_, i) => (
          <SkeletonBlock key={i} width={72} height={34} borderRadius={17} />
        ))}
      </View>
      {/* Order cards */}
      {Array.from({ length: 4 }).map((_, i) => (
        <View key={i} style={[p.listCard, { marginTop: 12, marginHorizontal: 16, flexDirection: "column", gap: 10 }]}>
          <View style={[p.row, { justifyContent: "space-between" }]}>
            <SkeletonBlock width={120} height={14} />
            <SkeletonBlock width={70} height={22} borderRadius={6} />
          </View>
          <SkeletonBlock width="60%" height={12} />
          <View style={[p.row, { justifyContent: "space-between" }]}>
            <SkeletonBlock width={80} height={12} />
            <SkeletonBlock width={60} height={16} />
          </View>
        </View>
      ))}
    </View>
  );
}

/** Dashboard skeleton — KPI cards + quick actions + chart area */
export function DashboardSkeleton() {
  return (
    <View style={p.container}>
      {/* Header */}
      <View style={[p.hPadding, { marginTop: 12 }]}>
        <SkeletonBlock width={160} height={24} borderRadius={6} />
        <SkeletonBlock width={100} height={12} style={{ marginTop: 6 }} />
      </View>
      {/* KPI Cards */}
      <View style={[p.row, { flexWrap: "wrap", paddingHorizontal: 16, marginTop: 20, gap: 12 }]}>
        {Array.from({ length: 4 }).map((_, i) => (
          <View key={i} style={[p.kpiCard]}>
            <SkeletonBlock width={36} height={36} borderRadius={10} />
            <SkeletonBlock width="70%" height={12} style={{ marginTop: 10 }} />
            <SkeletonBlock width="50%" height={20} borderRadius={4} style={{ marginTop: 6 }} />
          </View>
        ))}
      </View>
      {/* Quick actions */}
      <View style={[p.hPadding, { marginTop: 24 }]}>
        <SkeletonBlock width={120} height={18} borderRadius={6} />
      </View>
      <View style={[p.row, { paddingHorizontal: 16, marginTop: 12, gap: 12 }]}>
        {Array.from({ length: 4 }).map((_, i) => (
          <View key={i} style={{ alignItems: "center", gap: 6 }}>
            <SkeletonBlock width={52} height={52} borderRadius={14} />
            <SkeletonBlock width={44} height={10} />
          </View>
        ))}
      </View>
      {/* Recent orders section */}
      <View style={[p.hPadding, { marginTop: 24 }]}>
        <SkeletonBlock width={130} height={18} borderRadius={6} />
      </View>
      {Array.from({ length: 3 }).map((_, i) => (
        <View key={i} style={[p.listCard, { marginTop: 10, marginHorizontal: 16, flexDirection: "column", gap: 8 }]}>
          <View style={[p.row, { justifyContent: "space-between" }]}>
            <SkeletonBlock width={100} height={13} />
            <SkeletonBlock width={60} height={20} borderRadius={6} />
          </View>
          <SkeletonBlock width="55%" height={12} />
        </View>
      ))}
    </View>
  );
}

/** Categories skeleton — sidebar + content panel */
export function CategoriesSkeleton() {
  return (
    <View style={p.container}>
      {/* Header */}
      <View style={[p.hPadding, { paddingTop: 56, paddingBottom: 14, borderBottomWidth: 1, borderBottomColor: "#F0F0F5" }]}>
        <SkeletonBlock width={120} height={28} borderRadius={6} />
      </View>
      <View style={{ flex: 1, flexDirection: "row" }}>
        {/* Sidebar */}
        <View style={{ width: 86, backgroundColor: "#FAFAFA", borderRightWidth: 1, borderRightColor: "#F0F0F5", paddingTop: 8 }}>
          {Array.from({ length: 8 }).map((_, i) => (
            <View key={i} style={{ alignItems: "center", paddingVertical: 10, gap: 4 }}>
              <SkeletonBlock width={44} height={44} borderRadius={10} />
              <SkeletonBlock width={48} height={10} />
            </View>
          ))}
        </View>
        {/* Content */}
        <View style={{ flex: 1, padding: 14 }}>
          <SkeletonBlock width={120} height={20} borderRadius={6} style={{ marginBottom: 14 }} />
          {Array.from({ length: 3 }).map((_, i) => (
            <View key={i} style={{ marginBottom: 18 }}>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 8, marginBottom: 10 }}>
                <SkeletonBlock width={28} height={28} borderRadius={6} />
                <SkeletonBlock width={100} height={14} />
              </View>
              <View style={{ flexDirection: "row", flexWrap: "wrap", gap: 8 }}>
                {Array.from({ length: 3 }).map((_, j) => (
                  <View key={j} style={{ alignItems: "center", gap: 4, width: 60 }}>
                    <SkeletonBlock width={44} height={44} borderRadius={10} />
                    <SkeletonBlock width={40} height={10} />
                  </View>
                ))}
              </View>
            </View>
          ))}
        </View>
      </View>
    </View>
  );
}

/** Supplier grid skeleton — filter chips + 2-col supplier cards */
export function SupplierGridSkeleton() {
  const screenWidth = require("react-native").Dimensions.get("window").width;
  const cardW = (screenWidth - 48) / 2;

  const SkeletonCard = () => (
    <View style={{ width: cardW, backgroundColor: "#fff", borderRadius: 16, overflow: "hidden", borderWidth: 1, borderColor: "#F0F0F5" }}>
      {/* Banner */}
      <SkeletonBlock width="100%" height={64} borderRadius={0} />
      {/* Logo */}
      <View style={{ marginTop: -20, marginLeft: 12 }}>
        <SkeletonBlock width={40} height={40} borderRadius={12} />
      </View>
      {/* Name + meta */}
      <View style={{ padding: 10, paddingTop: 6, gap: 6 }}>
        <SkeletonBlock width="80%" height={13} />
        <SkeletonBlock width="60%" height={11} />
      </View>
    </View>
  );

  return (
    <View style={{ flex: 1, backgroundColor: "#F8F8FA" }}>
      {/* Filter chips */}
      <View style={[p.row, { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 8, gap: 8 }]}>
        {Array.from({ length: 3 }).map((_, i) => (
          <SkeletonBlock key={i} width={90} height={38} borderRadius={10} />
        ))}
      </View>
      {/* Supplier cards grid */}
      {Array.from({ length: 3 }).map((_, row) => (
        <View key={row} style={{ flexDirection: "row", justifyContent: "space-between", paddingHorizontal: 16, marginBottom: 12 }}>
          <SkeletonCard />
          <SkeletonCard />
        </View>
      ))}
    </View>
  );
}

/** Supplier detail skeleton — banner + vendor info + category chips + product grid */
export function SupplierDetailSkeleton() {
  return (
    <View style={p.container}>
      {/* Banner */}
      <SkeletonBlock width="100%" height={140} borderRadius={0} />
      {/* Vendor card */}
      <View style={{ marginHorizontal: 16, marginTop: -20, backgroundColor: "#fff", borderRadius: 16, padding: 14, borderWidth: 1, borderColor: "#F0F0F0" }}>
        <View style={[p.row, { alignItems: "flex-start", gap: 12 }]}>
          <SkeletonBlock width={56} height={56} borderRadius={16} />
          <View style={{ flex: 1, gap: 6 }}>
            <SkeletonBlock width="70%" height={16} borderRadius={6} />
            <SkeletonBlock width="50%" height={11} />
            <SkeletonBlock width="35%" height={11} />
          </View>
        </View>
        {/* Follow row */}
        <View style={[p.row, { justifyContent: "space-between", marginTop: 14, paddingTop: 12, borderTopWidth: 1, borderTopColor: "#F0F0F5" }]}>
          <View style={{ alignItems: "center", gap: 4 }}>
            <SkeletonBlock width={40} height={16} borderRadius={4} />
            <SkeletonBlock width={50} height={10} />
          </View>
          <SkeletonBlock width={100} height={38} borderRadius={24} />
        </View>
      </View>
      {/* Category chips */}
      <View style={[p.row, { paddingHorizontal: 16, marginTop: 14, gap: 8 }]}>
        {Array.from({ length: 4 }).map((_, i) => (
          <SkeletonBlock key={i} width={70} height={34} borderRadius={10} />
        ))}
      </View>
      {/* Products header */}
      <View style={[p.row, { paddingHorizontal: 16, marginTop: 14, gap: 6 }]}>
        <SkeletonBlock width={100} height={16} borderRadius={6} />
        <SkeletonBlock width={30} height={14} />
      </View>
      {/* Product grid */}
      <View style={[p.row, { flexWrap: "wrap", paddingHorizontal: 16, marginTop: 12, gap: 12 }]}>
        {Array.from({ length: 4 }).map((_, i) => (
          <View key={i} style={p.gridCard}>
            <SkeletonBlock width="100%" height={130} borderRadius={0} />
            <View style={{ padding: 10, gap: 6 }}>
              <SkeletonBlock width="75%" height={12} />
              <SkeletonBlock width="45%" height={12} />
              <SkeletonBlock width="35%" height={16} borderRadius={4} />
            </View>
          </View>
        ))}
      </View>
    </View>
  );
}

/* ═══ SHARED PRESET STYLES ═══ */
const HALF_WIDTH = "47%";
const p = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#fff", paddingTop: 8 },
  hPadding: { paddingHorizontal: 16 },
  row: { flexDirection: "row", alignItems: "center" },
  gridCard: {
    width: HALF_WIDTH as any, borderRadius: 14, backgroundColor: "#fff",
    borderWidth: 1, borderColor: "#F0F0F0", overflow: "hidden",
  },
  listCard: {
    flexDirection: "row", backgroundColor: "#fff", borderRadius: 14,
    borderWidth: 1, borderColor: "#F0F0F0", padding: 14,
  },
  kpiCard: {
    width: HALF_WIDTH as any, borderRadius: 14,
    borderWidth: 1, borderColor: "#F0F0F0", padding: 14,
  },
});
