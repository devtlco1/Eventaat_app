import React from "react";
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  type StyleProp,
  type ViewStyle,
} from "react-native";
import { colors, radii, typography } from "../theme/tokens";

export type ButtonVariant = "primary" | "accent" | "outline" | "ghost" | "danger";

type Props = {
  title: string;
  onPress: () => void;
  variant?: ButtonVariant;
  disabled?: boolean;
  loading?: boolean;
  style?: StyleProp<ViewStyle>;
};

export function Button({
  title,
  onPress,
  variant = "primary",
  disabled = false,
  loading = false,
  style,
}: Props) {
  const isDisabled = disabled || loading;

  return (
    <Pressable
      style={[
        styles.base,
        variantStyles[variant].container,
        isDisabled && styles.disabled,
        style,
      ]}
      onPress={onPress}
      disabled={isDisabled}
    >
      {loading ? (
        <ActivityIndicator
          color={variant === "outline" || variant === "ghost" ? colors.text : "#fff"}
          size="small"
        />
      ) : (
        <Text style={[styles.label, variantStyles[variant].label]}>{title}</Text>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 48,
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderRadius: radii.button,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
    gap: 8,
  },
  label: {
    ...typography.md,
    fontWeight: "600",
  },
  disabled: {
    opacity: 0.5,
  },
});

const variantStyles = {
  primary: StyleSheet.create({
    container: { backgroundColor: colors.primary },
    label: { color: colors.onPrimary },
  }),
  accent: StyleSheet.create({
    container: { backgroundColor: colors.accent },
    label: { color: colors.onAccent },
  }),
  outline: StyleSheet.create({
    container: {
      backgroundColor: "transparent",
      borderWidth: 1.5,
      borderColor: colors.primary,
    },
    label: { color: colors.primary },
  }),
  ghost: StyleSheet.create({
    container: { backgroundColor: "transparent" },
    label: { color: colors.primary },
  }),
  danger: StyleSheet.create({
    container: { backgroundColor: colors.dangerBg, borderWidth: 1, borderColor: colors.dangerBorder },
    label: { color: colors.danger },
  }),
};
