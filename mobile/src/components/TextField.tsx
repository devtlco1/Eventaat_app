import React, { useState } from "react";
import { StyleSheet, Text, TextInput, View } from "react-native";
import { colors, radii, typography } from "../theme/tokens";

type Props = {
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  keyboardType?: "default" | "phone-pad" | "number-pad";
  autoCapitalize?: "none" | "sentences" | "words" | "characters";
  secureTextEntry?: boolean;
  error?: string | null;
  helperText?: string;
};

export function TextField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  autoCapitalize = "none",
  secureTextEntry,
  error,
  helperText,
}: Props) {
  const [focused, setFocused] = useState(false);

  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        style={[
          styles.input,
          focused && styles.inputFocused,
          error ? styles.inputError : null,
        ]}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={colors.textMuted}
        keyboardType={keyboardType}
        autoCapitalize={autoCapitalize}
        secureTextEntry={secureTextEntry}
        onFocus={() => setFocused(true)}
        onBlur={() => setFocused(false)}
      />
      {error ? (
        <Text style={styles.errorText}>{error}</Text>
      ) : helperText ? (
        <Text style={styles.helperText}>{helperText}</Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: 6,
  },
  label: {
    ...typography.base,
    color: colors.text,
    fontWeight: "600",
  },
  input: {
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    paddingHorizontal: 14,
    paddingVertical: 12,
    ...typography.md,
    color: colors.text,
    backgroundColor: colors.background,
    minHeight: 48,
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
  helperText: {
    ...typography.sm,
    color: colors.textSecondary,
  },
});
