import React from "react";
import { StyleSheet, View, type ViewStyle, type StyleProp } from "react-native";
import { colors, spacing } from "../theme/tokens";

type Props = {
  style?: StyleProp<ViewStyle>;
  spacing?: number;
};

export function Divider({ style, spacing: gap = spacing.md }: Props) {
  return (
    <View
      style={[styles.line, { marginVertical: gap }, style]}
    />
  );
}

const styles = StyleSheet.create({
  line: {
    height: StyleSheet.hairlineWidth,
    backgroundColor: colors.border,
  },
});
