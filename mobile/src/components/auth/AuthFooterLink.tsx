import React from "react";
import { Pressable, StyleSheet, Text } from "react-native";
import { colors } from "../../theme/tokens";

export function AuthFooterLink({
  label,
  linkLabel,
  onPress,
}: {
  label: string;
  linkLabel: string;
  onPress: () => void;
}) {
  return (
    <Pressable
      style={styles.row}
      onPress={onPress}
      hitSlop={{ top: 16, bottom: 16, left: 24, right: 24 }}
    >
      <Text style={styles.muted}>{label} </Text>
      <Text style={styles.link}>{linkLabel}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "center",
    alignItems: "center",
    paddingVertical: 4,
  },
  muted: {
    fontSize: 15,
    color: colors.textSecondary,
  },
  link: {
    fontSize: 15,
    fontWeight: "600",
    color: colors.primary,
  },
});
