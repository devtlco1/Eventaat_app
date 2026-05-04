import React from "react";
import { Pressable, StyleSheet, Text, View } from "react-native";
import { colors, radii, spacing, typography } from "../theme/tokens";
import { Button } from "./Button";

type Props = {
  message: string | null;
  onRetry?: () => void;
};

export function ErrorBanner({ message, onRetry }: Props) {
  if (!message) return null;
  return (
    <View style={styles.box}>
      <Text style={styles.text}>{message}</Text>
      {onRetry ? (
        <Button
          title="Retry"
          onPress={onRetry}
          variant="danger"
          style={styles.retryBtn}
        />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  box: {
    backgroundColor: colors.dangerBg,
    borderColor: colors.dangerBorder,
    borderWidth: 1,
    padding: spacing.md,
    borderRadius: radii.sm,
    gap: spacing.sm,
  },
  text: {
    ...typography.base,
    color: colors.danger,
  },
  retryBtn: {
    alignSelf: "flex-start",
    minHeight: 36,
    paddingVertical: 6,
    paddingHorizontal: 14,
  },
});
