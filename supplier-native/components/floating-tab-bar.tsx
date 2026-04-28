import React, { useRef } from "react";
import {
  View,
  TouchableOpacity,
  StyleSheet,
  Platform,
  Animated,
  Text as RNText,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import * as Haptics from "expo-haptics";
import type { BottomTabBarProps } from "@react-navigation/bottom-tabs";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { BRAND } from "@/lib/constants";

/* ── Tab configuration — 5 tabs: Dashboard, Order, [Product], Earning, Profile ── */
const TAB_ICONS: Record<string, { active: string; inactive: string }> = {
  index: { active: "grid", inactive: "grid-outline" },
  orders: { active: "clipboard", inactive: "clipboard-outline" },
  products: { active: "cube", inactive: "cube-outline" },
  earning: { active: "wallet", inactive: "wallet-outline" },
  account: { active: "person", inactive: "person-outline" },
};

const TAB_LABELS: Record<string, string> = {
  index: "Dashboard",
  orders: "Order",
  products: "Product",
  earning: "Earning",
  account: "Profile",
};

const INACTIVE_COLOR = "#ACACAC";
const CENTER_TAB_NAME = "products";
const CENTER_BUTTON_SIZE = 50;
const BAR_HEIGHT = 60;

export const TAB_BAR_HEIGHT = BAR_HEIGHT + 20;

/* ── Regular Tab ── */
interface AnimatedTabProps {
  route: BottomTabBarProps["state"]["routes"][0];
  index: number;
  isFocused: boolean;
  onPress: () => void;
  onLongPress: () => void;
}

function AnimatedTab({
  route,
  isFocused,
  onPress,
  onLongPress,
}: AnimatedTabProps) {
  const scale = useRef(new Animated.Value(1)).current;

  const icons = TAB_ICONS[route.name] ?? {
    active: "ellipse",
    inactive: "ellipse-outline",
  };
  const label = TAB_LABELS[route.name] ?? route.name;
  const iconName = isFocused ? icons.active : icons.inactive;

  const handlePress = () => {
    Animated.sequence([
      Animated.spring(scale, {
        toValue: 0.85,
        damping: 12,
        stiffness: 400,
        useNativeDriver: true,
      }),
      Animated.spring(scale, {
        toValue: 1,
        damping: 15,
        stiffness: 200,
        useNativeDriver: true,
      }),
    ]).start();

    if (Platform.OS !== "web") {
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    }

    onPress();
  };

  return (
    <TouchableOpacity
      accessibilityRole="button"
      accessibilityState={isFocused ? { selected: true } : {}}
      onPress={handlePress}
      onLongPress={onLongPress}
      style={styles.tabButton}
      activeOpacity={0.7}
    >
      <Animated.View style={[styles.tabContent, { transform: [{ scale }] }]}>
        <Ionicons
          name={iconName as any}
          size={22}
          color={isFocused ? BRAND.primary : INACTIVE_COLOR}
        />
        <RNText
          style={[
            styles.label,
            {
              color: isFocused ? BRAND.primary : INACTIVE_COLOR,
              fontWeight: isFocused ? "600" : "400",
            },
          ]}
          numberOfLines={1}
        >
          {label}
        </RNText>
      </Animated.View>
    </TouchableOpacity>
  );
}

/* ── Center Product Button ── */
interface CenterButtonProps {
  isFocused: boolean;
  onPress: () => void;
  onLongPress: () => void;
}

function CenterProductButton({
  isFocused,
  onPress,
  onLongPress,
}: CenterButtonProps) {
  const scale = useRef(new Animated.Value(1)).current;

  const handlePress = () => {
    Animated.sequence([
      Animated.spring(scale, {
        toValue: 0.9,
        damping: 12,
        stiffness: 400,
        useNativeDriver: true,
      }),
      Animated.spring(scale, {
        toValue: 1,
        damping: 15,
        stiffness: 200,
        useNativeDriver: true,
      }),
    ]).start();

    if (Platform.OS !== "web") {
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Medium);
    }

    onPress();
  };

  return (
    <View style={styles.centerWrapper}>
      <TouchableOpacity
        accessibilityRole="button"
        accessibilityLabel="Product"
        accessibilityState={isFocused ? { selected: true } : {}}
        onPress={handlePress}
        onLongPress={onLongPress}
        activeOpacity={0.85}
      >
        <Animated.View
          style={[
            styles.centerButton,
            {
              transform: [{ scale }],
              backgroundColor: isFocused ? BRAND.primary : "#1C1C1E",
            },
          ]}
        >
          <Ionicons name="cube" size={24} color="#fff" />
        </Animated.View>
      </TouchableOpacity>

      <RNText
        style={[
          styles.centerLabel,
          {
            color: isFocused ? BRAND.primary : INACTIVE_COLOR,
            fontWeight: isFocused ? "600" : "400",
          },
        ]}
      >
        Product
      </RNText>
    </View>
  );
}

/* ── Main Tab Bar ── */
export default function FloatingTabBar({
  state,
  descriptors,
  navigation,
}: BottomTabBarProps) {
  const insets = useSafeAreaInsets();
  const bottomPadding = Math.max(insets.bottom, 4);

  const visibleRoutes = state.routes.filter((route) => {
    const { options } = descriptors[route.key];
    if (options.tabBarButton && options.tabBarButton({} as any) === null)
      return false;
    return !!TAB_ICONS[route.name];
  });

  const centerIndex = visibleRoutes.findIndex(
    (r) => r.name === CENTER_TAB_NAME
  );
  const leftTabs = visibleRoutes.slice(0, centerIndex);
  const rightTabs = visibleRoutes.slice(centerIndex + 1);
  const centerRoute = visibleRoutes[centerIndex];

  const makeHandlers = (route: (typeof visibleRoutes)[0]) => {
    const realIndex = state.routes.findIndex((r) => r.key === route.key);
    const isFocused = state.index === realIndex;
    const onPress = () => {
      const event = navigation.emit({
        type: "tabPress",
        target: route.key,
        canPreventDefault: true,
      });
      if (!isFocused && !event.defaultPrevented) {
        navigation.navigate(route.name);
      }
    };
    const onLongPress = () => {
      navigation.emit({ type: "tabLongPress", target: route.key });
    };
    return { realIndex, isFocused, onPress, onLongPress };
  };

  return (
    <View style={[styles.outerWrapper, { paddingBottom: bottomPadding }]}>
      <View style={styles.bar}>
        {/* Left tabs: Dashboard, Order */}
        <View style={styles.sideGroup}>
          {leftTabs.map((route) => {
            const h = makeHandlers(route);
            return (
              <AnimatedTab
                key={route.key}
                route={route}
                index={h.realIndex}
                isFocused={h.isFocused}
                onPress={h.onPress}
                onLongPress={h.onLongPress}
              />
            );
          })}
        </View>

        {/* Center spacer */}
        <View style={styles.centerSpacer} />

        {/* Right tabs: Earning, Profile */}
        <View style={styles.sideGroup}>
          {rightTabs.map((route) => {
            const h = makeHandlers(route);
            return (
              <AnimatedTab
                key={route.key}
                route={route}
                index={h.realIndex}
                isFocused={h.isFocused}
                onPress={h.onPress}
                onLongPress={h.onLongPress}
              />
            );
          })}
        </View>
      </View>

      {/* Center button — raised Product tab */}
      {centerRoute &&
        (() => {
          const h = makeHandlers(centerRoute);
          return (
            <CenterProductButton
              isFocused={h.isFocused}
              onPress={h.onPress}
              onLongPress={h.onLongPress}
            />
          );
        })()}
    </View>
  );
}

/* ── Styles ── */
const styles = StyleSheet.create({
  outerWrapper: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    alignItems: "center",
  },

  bar: {
    flexDirection: "row",
    alignItems: "center",
    width: "100%",
    height: BAR_HEIGHT,
    backgroundColor: "#fff",
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: -3 },
        shadowOpacity: 0.08,
        shadowRadius: 10,
      },
      android: {
        elevation: 12,
      },
    }),
  },

  sideGroup: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-evenly",
    height: BAR_HEIGHT,
  },

  centerSpacer: {
    width: CENTER_BUTTON_SIZE + 16,
  },

  /* ── Regular tab ── */
  tabButton: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    height: BAR_HEIGHT,
  },

  tabContent: {
    alignItems: "center",
    justifyContent: "center",
    gap: 3,
  },

  label: {
    fontSize: 10,
  },

  /* ── Center button ── */
  centerWrapper: {
    position: "absolute",
    /* Only poke out slightly — about 40% of button above the bar */
    bottom: BAR_HEIGHT - CENTER_BUTTON_SIZE * 0.6,
    alignSelf: "center",
    alignItems: "center",
  },

  centerButton: {
    width: CENTER_BUTTON_SIZE,
    height: CENTER_BUTTON_SIZE,
    borderRadius: CENTER_BUTTON_SIZE / 2,
    justifyContent: "center",
    alignItems: "center",
    ...Platform.select({
      ios: {
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 3 },
        shadowOpacity: 0.2,
        shadowRadius: 6,
      },
      android: {
        elevation: 8,
      },
    }),
  },

  centerLabel: {
    fontSize: 10,
    marginTop: 3,
  },
});
