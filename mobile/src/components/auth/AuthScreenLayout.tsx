import React from "react";
import {
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { colors, spacing } from "../../theme/tokens";

export function AuthScreenLayout({
  title,
  subtitle,
  children,
  footer,
}: {
  title?: string;
  subtitle?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
}) {
  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>
      <KeyboardAvoidingView
        behavior={Platform.OS === "ios" ? "padding" : undefined}
        style={styles.flex}
      >
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <View>
            {title ? <Text style={styles.title}>{title}</Text> : null}
            {subtitle ? <Text style={styles.subtitle}>{subtitle}</Text> : null}
            <View style={styles.body}>{children}</View>
          </View>
          {footer ? <View style={styles.footer}>{footer}</View> : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.background,
  },
  flex: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: "space-between",
    paddingHorizontal: spacing.screenHorizontal,
    paddingTop: 8,
    paddingBottom: spacing.sectionGap,
  },
  title: {
    fontSize: 26,
    fontWeight: "700",
    color: colors.text,
    marginBottom: 8,
    letterSpacing: -0.3,
  },
  subtitle: {
    fontSize: 15,
    lineHeight: 22,
    color: colors.textSecondary,
    marginBottom: spacing.sectionGap,
  },
  body: {
    gap: spacing.fieldGap,
  },
  footer: {
    paddingTop: spacing.sectionGap,
    alignItems: "center",
  },
});
