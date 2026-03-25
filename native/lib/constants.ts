/**
 * SelfShop brand colors & navigation theme.
 *
 * Primary:   #E5005F  (hot-pink / magenta)
 * Secondary: #3257D9  (blue)
 */

export const BRAND = {
  primary: "#E5005F",
  primaryLight: "rgba(229, 0, 95, 0.12)",
  primaryBg: "#FDF2F8",
  secondary: "#3257D9",
  secondaryBg: "#F2F4FF",
} as const;

export const NAV_THEME = {
  light: {
    background: "hsl(0 0% 100%)",
    border: "hsl(220 13% 91%)",
    card: "hsl(0 0% 100%)",
    notification: "hsl(0 84.2% 60.2%)",
    primary: "#E5005F",
    text: "hsl(222.2 84% 4.9%)",
  },
  dark: {
    background: "hsl(222.2 84% 4.9%)",
    border: "hsl(217.2 32.6% 17.5%)",
    card: "hsl(222.2 84% 4.9%)",
    notification: "hsl(0 72% 51%)",
    primary: "#E5005F",
    text: "hsl(210 40% 98%)",
  },
};
