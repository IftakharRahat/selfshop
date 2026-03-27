/**
 * SelfShop Supplier brand colors & navigation theme.
 *
 * Primary:   #2d2a5d  (deep indigo — matching web vendor portal)
 * Secondary: #4f46e5  (vivid indigo)
 * Accent:    #10b981  (emerald green for success states)
 */

import { Platform } from "react-native";

export const BRAND = {
  primary: "#2d2a5d",
  primaryLight: "rgba(45, 42, 93, 0.10)",
  primaryBg: "#EEEDFA",
  secondary: "#4f46e5",
  secondaryBg: "#EEF2FF",
  accent: "#10b981",
  accentBg: "#ECFDF5",
} as const;

/** Reusable subtle card shadow — use with StyleSheet spread */
export const CARD_SHADOW = Platform.select({
  ios: {
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
  },
  android: {
    elevation: 3,
  },
}) as object;

/** Per-section icon colors for account menu */
export const SECTION_COLORS = {
  business: "#4f46e5",   // indigo
  finance: "#059669",    // emerald
  management: "#0891b2", // cyan
  insights: "#d97706",   // amber
} as const;

export const NAV_THEME = {
  light: {
    background: "hsl(0 0% 100%)",
    border: "hsl(220 13% 91%)",
    card: "hsl(0 0% 100%)",
    notification: "hsl(0 84.2% 60.2%)",
    primary: "#2d2a5d",
    text: "hsl(222.2 84% 4.9%)",
  },
  dark: {
    background: "hsl(222.2 84% 4.9%)",
    border: "hsl(217.2 32.6% 17.5%)",
    card: "hsl(222.2 84% 4.9%)",
    notification: "hsl(0 72% 51%)",
    primary: "#2d2a5d",
    text: "hsl(210 40% 98%)",
  },
};
