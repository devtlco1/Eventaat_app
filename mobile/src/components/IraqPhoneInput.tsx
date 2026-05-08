import React, { forwardRef, useState } from "react";
import {
  StyleSheet,
  Text,
  TextInput,
  type TextInputProps,
  View,
} from "react-native";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = {
  value: string;
  onChangeText: (text: string) => void;
  error?: string | null;
  label?: string;
  /**
   * "outline" (default) — bordered style used in app-wide contexts.
   * "filled"            — filled gray style used on auth screens.
   */
  variant?: "outline" | "filled";
  onSubmitEditing?: TextInputProps["onSubmitEditing"];
  returnKeyType?: TextInputProps["returnKeyType"];
  placeholder?: string;
};

/**
 * Phone input row for Iraqi numbers with a fixed 🇮🇶 +964 prefix.
 * Supports "outline" (default, bordered) and "filled" (auth-screen style) variants.
 * Exposes TextInput ref via forwardRef for focus-chaining in forms.
 */
export const IraqPhoneInput = forwardRef<TextInput, Props>(
  function IraqPhoneInput(
    {
      value,
      onChangeText,
      error,
      label = "Phone",
      variant = "outline",
      onSubmitEditing,
      returnKeyType,
      placeholder,
    },
    ref
  ) {
    const [focused, setFocused] = useState(false);
    const filled = variant === "filled";
    const defaultPlaceholder = placeholder ?? "0770 000 1781";

    return (
      <View style={styles.wrapper}>
        <Text style={styles.label}>{label}</Text>

        {filled ? (
          /* ── Filled variant: single pill row ── */
          <View style={[styles.filledRow, error ? styles.filledRowError : null]}>
            <View style={styles.filledPrefix}>
              <Text style={styles.prefixFlag}>🇮🇶</Text>
              <Text style={styles.prefixText}>+964</Text>
            </View>
            <View style={styles.filledDivider} />
            <TextInput
              ref={ref}
              style={styles.filledInput}
              value={value}
              onChangeText={onChangeText}
              placeholder={defaultPlaceholder}
              placeholderTextColor={colors.textMuted}
              keyboardType="phone-pad"
              autoCapitalize="none"
              onFocus={() => setFocused(true)}
              onBlur={() => setFocused(false)}
              onSubmitEditing={onSubmitEditing}
              returnKeyType={returnKeyType}
            />
          </View>
        ) : (
          /* ── Outline variant: separate badge + input ── */
          <View style={styles.outlineRow}>
            <View style={styles.outlinePrefix}>
              <Text style={styles.prefixFlag}>🇮🇶</Text>
              <Text style={styles.prefixText}>+964</Text>
            </View>
            <TextInput
              ref={ref}
              style={[
                styles.outlineInput,
                focused && styles.outlineInputFocused,
                error ? styles.outlineInputError : null,
              ]}
              value={value}
              onChangeText={onChangeText}
              placeholder={defaultPlaceholder}
              placeholderTextColor={colors.textMuted}
              keyboardType="phone-pad"
              autoCapitalize="none"
              onFocus={() => setFocused(true)}
              onBlur={() => setFocused(false)}
              onSubmitEditing={onSubmitEditing}
              returnKeyType={returnKeyType}
            />
          </View>
        )}

        {error ? <Text style={styles.errorText}>{error}</Text> : null}
      </View>
    );
  }
);

const INPUT_H = 52;

const styles = StyleSheet.create({
  wrapper: { gap: 6 },
  label: {
    ...typography.base,
    color: colors.text,
    fontWeight: "600",
  },
  prefixFlag: { fontSize: 16 },
  prefixText: {
    ...typography.base,
    fontWeight: "600",
    color: colors.text,
  },

  // ── Filled variant ────────────────────────────────────────────────────────
  filledRow: {
    flexDirection: "row",
    alignItems: "center",
    height: INPUT_H,
    backgroundColor: colors.inputBg,
    borderRadius: radii.input,
    overflow: "hidden",
  },
  filledRowError: {
    borderWidth: 1.5,
    borderColor: colors.dangerBorder,
  },
  filledPrefix: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.xs,
    paddingHorizontal: 14,
    height: INPUT_H,
  },
  filledDivider: {
    width: 1,
    height: 24,
    backgroundColor: colors.border,
  },
  filledInput: {
    flex: 1,
    height: INPUT_H,
    paddingHorizontal: 14,
    ...typography.md,
    color: colors.text,
  },

  // ── Outline variant ───────────────────────────────────────────────────────
  outlineRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.sm,
  },
  outlinePrefix: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.xs,
    height: INPUT_H,
    paddingHorizontal: spacing.md,
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    backgroundColor: colors.surface,
  },
  outlineInput: {
    flex: 1,
    height: INPUT_H,
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    paddingHorizontal: 14,
    ...typography.md,
    color: colors.text,
    backgroundColor: colors.background,
  },
  outlineInputFocused: {
    borderColor: colors.borderFocus,
    borderWidth: 1.5,
  },
  outlineInputError: {
    borderColor: colors.dangerBorder,
    borderWidth: 1.5,
  },

  errorText: {
    ...typography.sm,
    color: colors.danger,
  },
});
