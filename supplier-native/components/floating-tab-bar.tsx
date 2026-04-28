import React, { useRef, useEffect } from "react";
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Platform,
  Animated,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { router } from "expo-router";
import type { BottomTabBarProps } from "@react-navigation/bottom-tabs";
import { BRAND } from "@/lib/constants";

/* ── Tab configuration — 5 tabs matching the required order ── */
const TAB_CONFIG: {
  name: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  iconFocused: keyof typeof Ionicons.glyphMap;
}[] = [
  { name: "index", label: "Dashboard", icon: "grid-outline", iconFocused: "grid" },
  { name: "orders", label: "Order", icon: "clipboard-outline", iconFocused: "clipboard" },
  { name: "products", label: "Product", icon: "cube-outline", iconFocused: "cube" },
  { name: "earning", label: "Earning", icon: "wallet-outline", iconFocused: "wallet" },
  { name: "account", label: "Profile", icon: "person-outline", iconFocused: "person" },
];

/* ── Floating "+" button for quick product upload ── */
const FAB_SIZE = 54;

export default function FloatingTabBar({ state, navigation }: BottomTabBarProps) {
  const insets = useSafeAreaInsets();
  const fabScale = useRef(new Animated.Value(1)).current;
  const fabGlow = useRef(new Animated.Value(0)).current;

  // Subtle pulse animation for the FAB
  useEffect(() => {
    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(fabGlow, {
          toValue: 1,
          duration: 1800,
          useNativeDriver: true,
        }),
        Animated.timing(fabGlow, {
          toValue: 0,
          duration: 1800,
          useNativeDriver: true,
        }),
      ]),
    );
    pulse.start();
    return () => pulse.stop();
  }, [fabGlow]);

  const handleFabPress = () => {
    // Bounce animation
    Animated.sequence([
      Animated.timing(fabScale, { toValue: 0.85, duration: 80, useNativeDriver: true }),
      Animated.timing(fabScale, { toValue: 1.05, duration: 100, useNativeDriver: true }),
      Animated.timing(fabScale, { toValue: 1, duration: 80, useNativeDriver: true }),
    ]).start();
    router.push("/product/form");
  };

  return (
    <View style={[styles.wrapper, { paddingBottom: Math.max(insets.bottom, 6) }]}>
      {/* ── Main tab bar ── */}
      <View style={styles.bar}>
        {TAB_CONFIG.map((tab, index) => {
          const isFocused = state.index === index;

          const onPress = () => {
            const event = navigation.emit({
              type: "tabPress",
              target: state.routes[index].key,
              canPreventDefault: true,
            });

            if (!isFocused && !event.defaultPrevented) {
              navigation.navigate(state.routes[index].name);
            }
          };

          return (
            <TouchableOpacity
              key={tab.name}
              accessibilityRole="button"
              accessibilityLabel={tab.label}
              accessibilityState={isFocused ? { selected: true } : {}}
              onPress={onPress}
              style={styles.tab}
              activeOpacity={0.7}
            >
              <View style={[styles.iconWrap, isFocused && styles.iconWrapFocused]}>
                <Ionicons
                  name={isFocused ? tab.iconFocused : tab.icon}
                  size={22}
                  color={isFocused ? BRAND.primary : "#9ca3af"}
                />
              </View>
              <Text style={[styles.label, isFocused && styles.labelActive]}>
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>

      {/* ── Floating "+" button — positioned above the bar, between Earning and Profile ── */}
      <Animated.View
        style={[
          styles.fabContainer,
          {
            transform: [{ scale: fabScale }],
            // Position above the boundary between Earning (4th) and Profile (5th) tab
            // Each tab is 20% wide. The boundary is at 80% from left = 20% from right
            // Center the button there by offsetting by half the FAB width
            right: "7%",
          },
        ]}
      >
        {/* Glow ring behind the button */}
        <Animated.View
          style={[
            styles.fabGlow,
            {
              opacity: fabGlow.interpolate({
                inputRange: [0, 1],
                outputRange: [0.15, 0.35],
              }),
              transform: [
                {
                  scale: fabGlow.interpolate({
                    inputRange: [0, 1],
                    outputRange: [1, 1.25],
                  }),
                },
              ],
            },
          ]}
        />
        <TouchableOpacity
          style={styles.fab}
          onPress={handleFabPress}
          activeOpacity={0.8}
        >
          <Ionicons name="add" size={28} color="#fff" />
        </TouchableOpacity>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    alignItems: "center",
    paddingHorizontal: 12,
  },
  bar: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderRadius: 28,
    paddingVertical: 8,
    paddingHorizontal: 4,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.10,
        shadowRadius: 16,
      },
      android: {
        elevation: 12,
      },
    }),
    width: "100%",
  },
  tab: {
    flex: 1,
    alignItems: "center",
    paddingVertical: 4,
  },
  iconWrap: {
    width: 44,
    height: 36,
    borderRadius: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  iconWrapFocused: {
    backgroundColor: BRAND.primaryLight,
  },
  label: {
    fontSize: 10,
    fontWeight: "500",
    color: "#9ca3af",
    marginTop: 2,
  },
  labelActive: {
    color: BRAND.primary,
    fontWeight: "600",
  },

  /* ── Floating Action Button ── */
  fabContainer: {
    position: "absolute",
    bottom: 68, // Sits above the bar
    alignItems: "center",
    justifyContent: "center",
    // right is set dynamically inline
  },
  fabGlow: {
    position: "absolute",
    width: FAB_SIZE + 16,
    height: FAB_SIZE + 16,
    borderRadius: (FAB_SIZE + 16) / 2,
    backgroundColor: BRAND.primary,
  },
  fab: {
    width: FAB_SIZE,
    height: FAB_SIZE,
    borderRadius: FAB_SIZE / 2,
    backgroundColor: BRAND.primary,
    alignItems: "center",
    justifyContent: "center",
    ...Platform.select({
      ios: {
        shadowColor: BRAND.primary,
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.35,
        shadowRadius: 10,
      },
      android: {
        elevation: 8,
      },
    }),
    borderWidth: 3,
    borderColor: "#fff",
  },
});
