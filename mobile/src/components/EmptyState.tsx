import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { colors, spacing, typography } from "../theme/tokens";
import { Button } from "./Button";

type Props = {
  title: string;
  subtitle?: string;
  icon?: React.ReactNode;
  actionLabel?: string;
  onAction?: () => void;
};

export function EmptyState({ title, subtitle, icon, actionLabel, onAction }: Props) {
  return (
    <View style={styles.center}>
      {icon ? <View style={styles.iconSlot}>{icon}</View> : null}
      <Text style={styles.title}>{title}</Text>
      {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
      {actionLabel && onAction ? (
        <Button
          title={actionLabel}
          onPress={onAction}
          variant="outline"
          style={styles.action}
        />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  center: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    gap: spacing.sm,
    padding: spacing.lg,
  },
  iconSlot: {
    marginBottom: spacing.sm,
  },
  title: {
    ...typography.md,
    fontWeight: "700",
    color: colors.text,
    textAlign: "center",
  },
  subtitle: {
    ...typography.base,
    color: colors.textSecondary,
    textAlign: "center",
  },
  action: {
    marginTop: spacing.md,
    alignSelf: "center",
    paddingHorizontal: spacing.xxl,
  },
});
