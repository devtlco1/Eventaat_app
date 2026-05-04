import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { radii, typography } from "../theme/tokens";

type BadgeVariant = "success" | "warning" | "danger" | "info" | "neutral";

const variantColors: Record<BadgeVariant, { bg: string; border: string; text: string }> = {
  success: { bg: "#D1FAE5", border: "#A7F3D0", text: "#065F46" },
  warning: { bg: "#FEF3C7", border: "#FDE68A", text: "#92400E" },
  danger: { bg: "#FEE2E2", border: "#FCA5A5", text: "#991B1B" },
  info: { bg: "#DBEAFE", border: "#BFDBFE", text: "#1E40AF" },
  neutral: { bg: "#E5E7EB", border: "#D1D5DB", text: "#374151" },
};

type Props = {
  label: string;
  variant?: BadgeVariant;
};

export function Badge({ label, variant = "neutral" }: Props) {
  const c = variantColors[variant];
  return (
    <View style={[styles.badge, { backgroundColor: c.bg, borderColor: c.border }]}>
      <Text style={[styles.text, { color: c.text }]}>{label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: radii.full,
    alignSelf: "flex-start",
  },
  text: {
    ...typography.xs,
    fontWeight: "700",
  },
});
