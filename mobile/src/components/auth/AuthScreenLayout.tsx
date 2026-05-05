import React from "react";
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Ionicons } from "@expo/vector-icons";
import { colors, spacing } from "../../theme/tokens";

const PURPLE = "#5B4CBD";

export function AuthScreenLayout({
  title,
  subtitle,
  children,
  footer,
  onBack,
}: {
  title?: string;
  subtitle?: React.ReactNode;
  children: React.ReactNode;
  footer?: React.ReactNode;
  /** If provided, renders a circle back-arrow button at the top. */
  onBack?: () => void;
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
          {/* Back button */}
          {onBack ? (
            <Pressable style={styles.backBtn} onPress={onBack} hitSlop={10}>
              <Ionicons name="arrow-back" size={20} color={colors.text} />
            </Pressable>
          ) : null}

          <View style={styles.header}>
            {title ? <Text style={styles.title}>{title}</Text> : null}
            {subtitle ? (
              typeof subtitle === "string" ? (
                <Text style={styles.subtitle}>{subtitle}</Text>
              ) : (
                <View style={styles.subtitleView}>{subtitle}</View>
              )
            ) : null}
          </View>

          <View style={styles.body}>{children}</View>

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
  flex: { flex: 1 },
  scrollContent: {
    flexGrow: 1,
    justifyContent: "space-between",
    paddingHorizontal: spacing.screenHorizontal + 4,
    paddingTop: 48,
    paddingBottom: spacing.sectionGap,
  },
  backBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    backgroundColor: colors.background,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 20,
  },
  header: {
    gap: 8,
    marginBottom: 32,
  },
  title: {
    fontSize: 28,
    fontWeight: "700",
    color: colors.text,
    letterSpacing: -0.5,
  },
  subtitle: {
    fontSize: 14,
    lineHeight: 22,
    color: colors.textSecondary,
  },
  subtitleView: {},
  body: {
    gap: spacing.fieldGap,
  },
  footer: {
    paddingTop: spacing.sectionGap,
    alignItems: "center",
  },
});
