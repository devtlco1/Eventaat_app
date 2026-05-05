import React, { useState } from "react";
import { StyleSheet, Text, TextInput, View } from "react-native";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = {
  value: string;
  onChangeText: (text: string) => void;
  error?: string | null;
  label?: string;
};

/**
 * A phone input row for Iraqi numbers.
 * Shows a fixed 🇮🇶 +964 prefix badge aligned exactly with the text input.
 * The caller is responsible for normalizing the value before submitting.
 */
export function IraqPhoneInput({ value, onChangeText, error, label = "Phone" }: Props) {
  const [focused, setFocused] = useState(false);

  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>{label}</Text>
      <View style={styles.row}>
        <View style={styles.prefix}>
          <Text style={styles.prefixFlag}>🇮🇶</Text>
          <Text style={styles.prefixText}>+964</Text>
        </View>
        <TextInput
          style={[
            styles.input,
            focused && styles.inputFocused,
            error ? styles.inputError : null,
          ]}
          value={value}
          onChangeText={onChangeText}
          placeholder="0770 000 1781"
          placeholderTextColor={colors.textMuted}
          keyboardType="phone-pad"
          autoCapitalize="none"
          onFocus={() => setFocused(true)}
          onBlur={() => setFocused(false)}
        />
      </View>
      {error ? <Text style={styles.errorText}>{error}</Text> : null}
    </View>
  );
}

const INPUT_HEIGHT = 48;

const styles = StyleSheet.create({
  wrapper: { gap: 6 },
  label: {
    ...typography.base,
    color: colors.text,
    fontWeight: "600",
  },
  row: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.sm,
  },
  prefix: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.xs,
    height: INPUT_HEIGHT,
    paddingHorizontal: spacing.md,
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    backgroundColor: colors.surface,
  },
  prefixFlag: { fontSize: 16 },
  prefixText: {
    ...typography.base,
    fontWeight: "600",
    color: colors.text,
  },
  input: {
    flex: 1,
    height: INPUT_HEIGHT,
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    paddingHorizontal: 14,
    ...typography.md,
    color: colors.text,
    backgroundColor: colors.background,
  },
  inputFocused: {
    borderColor: colors.borderFocus,
    borderWidth: 1.5,
  },
  inputError: {
    borderColor: colors.dangerBorder,
    borderWidth: 1.5,
  },
  errorText: {
    ...typography.sm,
    color: colors.danger,
  },
});
