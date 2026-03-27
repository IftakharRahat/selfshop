import React, { useEffect, useRef } from "react";
import { Animated, StyleSheet, View } from "react-native";

/* ── Shimmer skeleton primitive ── */

interface SkeletonProps {
  width: number | string;
  height: number;
  borderRadius?: number;
  style?: object;
}

export function Skeleton({ width, height, borderRadius = 8, style }: SkeletonProps) {
  const shimmer = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(shimmer, { toValue: 1, duration: 1000, useNativeDriver: true }),
        Animated.timing(shimmer, { toValue: 0, duration: 1000, useNativeDriver: true }),
      ]),
    ).start();
  }, [shimmer]);

  const opacity = shimmer.interpolate({
    inputRange: [0, 1],
    outputRange: [0.3, 0.7],
  });

  return (
    <Animated.View
      style={[
        {
          width: width as any,
          height,
          borderRadius,
          backgroundColor: "#e5e7eb",
          opacity,
        },
        style,
      ]}
    />
  );
}

/* ── Dashboard Skeleton ── */

export function DashboardSkeleton() {
  return (
    <View style={skeletonStyles.container}>
      {/* Stat cards row */}
      <View style={skeletonStyles.row}>
        {[1, 2].map((i) => (
          <Skeleton key={i} width="48%" height={100} borderRadius={12} />
        ))}
      </View>
      <View style={skeletonStyles.row}>
        {[3, 4].map((i) => (
          <Skeleton key={i} width="48%" height={100} borderRadius={12} />
        ))}
      </View>
      {/* Chart placeholder */}
      <Skeleton width="100%" height={180} borderRadius={12} style={{ marginTop: 12 }} />
      {/* Orders list */}
      <Skeleton width="100%" height={120} borderRadius={12} style={{ marginTop: 12 }} />
    </View>
  );
}

/* ── Product List Skeleton ── */

export function ProductListSkeleton() {
  return (
    <View style={skeletonStyles.container}>
      <Skeleton width="100%" height={44} borderRadius={10} />
      {[1, 2, 3, 4, 5].map((i) => (
        <View key={i} style={[skeletonStyles.row, { marginTop: 12 }]}>
          <Skeleton width={64} height={64} borderRadius={8} />
          <View style={{ flex: 1, marginLeft: 12, gap: 6 }}>
            <Skeleton width="80%" height={14} borderRadius={4} />
            <Skeleton width="50%" height={12} borderRadius={4} />
            <Skeleton width="30%" height={12} borderRadius={4} />
          </View>
        </View>
      ))}
    </View>
  );
}

/* ── Order List Skeleton ── */

export function OrderListSkeleton() {
  return (
    <View style={skeletonStyles.container}>
      {/* Status tabs */}
      <View style={[skeletonStyles.row, { gap: 8 }]}>
        {[1, 2, 3, 4].map((i) => (
          <Skeleton key={i} width={70} height={32} borderRadius={16} />
        ))}
      </View>
      {/* Order cards */}
      {[1, 2, 3, 4].map((i) => (
        <Skeleton key={i} width="100%" height={100} borderRadius={12} style={{ marginTop: 12 }} />
      ))}
    </View>
  );
}

/* ── Sub-Screen Skeleton (reports, earnings, inventory, shipping) ── */

export function SubScreenSkeleton() {
  return (
    <View style={skeletonStyles.container}>
      {/* Hero summary card */}
      <Skeleton width="100%" height={80} borderRadius={14} />
      {/* Stat grid */}
      <View style={[skeletonStyles.row, { marginTop: 12, gap: 8 }]}>
        {[1, 2, 3].map((i) => (
          <Skeleton key={i} width="31%" height={72} borderRadius={10} />
        ))}
      </View>
      {/* Section card with list rows */}
      <Skeleton width="100%" height={160} borderRadius={14} style={{ marginTop: 12 }} />
      <Skeleton width="100%" height={120} borderRadius={14} style={{ marginTop: 12 }} />
    </View>
  );
}

const skeletonStyles = StyleSheet.create({
  container: { padding: 16, gap: 4 },
  row: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
});
