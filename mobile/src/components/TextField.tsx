import React from "react";
import { StyleSheet, Text, TextInput, View } from "react-native";
import { colors, radii } from "../theme/tokens";

export function TextField({
  label,
  value,
  onChangeText,
  placeholder,
  keyboardType,
  autoCapitalize = "none",
  secureTextEntry,
}: {
  label: string;
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
  keyboardType?: "default" | "phone-pad" | "number-pad";
  autoCapitalize?: "none" | "sentences" | "words" | "characters";
  secureTextEntry?: boolean;
}) {
  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        style={styles.input}
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        keyboardType={keyboardType}
        autoCapitalize={autoCapitalize}
        secureTextEntry={secureTextEntry}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    gap: 8,
  },
  label: {
    color: colors.text,
    fontWeight: "600",
    fontSize: 15,
  },
  input: {
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 16,
    color: colors.text,
    backgroundColor: colors.background,
    minHeight: 48,
  },
});

