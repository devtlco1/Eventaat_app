/**
 * Visual reference: Figma file rnmu585nuu0fVUBOe6pWpQ (Dinevia community copy)
 * — adapted for Eventaat; values are approximate from frame metadata.
 */
import { I18nManager } from "react-native";

export const colors = {
  background: "#FFFFFF",
  surface: "#F9FAFB",
  surfaceElevated: "#FFFFFF",
  text: "#111827",
  textSecondary: "#6B7280",
  textMuted: "#9CA3AF",
  border: "#E5E7EB",
  borderInput: "#D1D5DB",
  borderFocus: "#111827",
  primary: "#111827",
  onPrimary: "#FFFFFF",
  accent: "#EA580C",
  onAccent: "#FFFFFF",
  splashAccent: "#F97316",
  splashMuted: "#FFEDD5",
  cardShadow: "rgba(17, 24, 39, 0.06)",

  // Semantic
  success: "#065F46",
  successBg: "#D1FAE5",
  successBorder: "#A7F3D0",
  warning: "#92400E",
  warningBg: "#FEF3C7",
  warningBorder: "#FDE68A",
  danger: "#991B1B",
  dangerBg: "#FEE2E2",
  dangerBorder: "#FCA5A5",
  info: "#1E40AF",
  infoBg: "#DBEAFE",
  infoBorder: "#BFDBFE",
  neutral: "#374151",
  neutralBg: "#E5E7EB",
  neutralBorder: "#D1D5DB",
};

export const radii = {
  xs: 6,
  sm: 8,
  input: 12,
  button: 12,
  card: 16,
  full: 999,
};

export const spacing = {
  xs: 4,
  sm: 8,
  md: 12,
  lg: 16,
  xl: 20,
  xxl: 24,
  screenHorizontal: 20,
  fieldGap: 16,
  sectionGap: 24,
};

export const typography = {
  xs: { fontSize: 12, lineHeight: 16 },
  sm: { fontSize: 13, lineHeight: 18 },
  base: { fontSize: 15, lineHeight: 22 },
  md: { fontSize: 16, lineHeight: 24 },
  lg: { fontSize: 17, lineHeight: 26 },
  xl: { fontSize: 20, lineHeight: 28 },
  xxl: { fontSize: 24, lineHeight: 32 },
  display: { fontSize: 26, lineHeight: 34 },
  hero: { fontSize: 44, lineHeight: 52 },
};

export const shadows = {
  sm: {
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  md: {
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 2,
  },
  lg: {
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 4,
  },
};

export const isRTL = I18nManager.isRTL;
export const rtl = {
  flexDirection: (isRTL ? "row-reverse" : "row") as "row" | "row-reverse",
  textAlign: (isRTL ? "right" : "left") as "left" | "right",
  marginStart: (value: number) => ({ marginStart: value }),
  marginEnd: (value: number) => ({ marginEnd: value }),
  paddingStart: (value: number) => ({ paddingStart: value }),
  paddingEnd: (value: number) => ({ paddingEnd: value }),
};
