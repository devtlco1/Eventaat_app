import React from "react";
import {
  Dimensions,
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";

const { width: W, height: H } = Dimensions.get("window");
const BLOB_H = H * 0.52;
const BLOB_COLOR = "#DEDEDE";
const PURPLE = "#5B4CBD";
const ORANGE = "#EA580C";

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

/**
 * Welcome landing screen.
 * Matches Figma "Welcome" frame (node-id 1-1038).
 */
export function WelcomeScreen({ navigation }: Props) {
  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>
      {/* ── Decorative blob area ── */}
      <View style={styles.blobArea}>
        {/* Large blob — bottom-left */}
        <View style={[styles.blob, { width: 260, height: 260, borderRadius: 130, left: -80, bottom: -40 }]} />
        {/* Large blob — top-right */}
        <View style={[styles.blob, { width: 200, height: 200, borderRadius: 100, right: -60, top: -20 }]} />
        {/* Medium blob — top-left (slight overlap with top edge) */}
        <View style={[styles.blob, { width: 180, height: 180, borderRadius: 90, left: -30, top: 20 }]} />
        {/* Large blob — bottom-right */}
        <View style={[styles.blob, { width: 220, height: 220, borderRadius: 110, right: -40, bottom: 10 }]} />

        {/* Floating hashtag pills */}
        <View style={[styles.pill, styles.pillPurple, { top: BLOB_H * 0.42, left: W * 0.26 }]}>
          <Text style={styles.pillText}>#Restaurant</Text>
        </View>
        <View style={[styles.pill, styles.pillOrange, { top: BLOB_H * 0.68, left: W * 0.20 }]}>
          <Text style={styles.pillText}>#Food</Text>
        </View>
      </View>

      {/* ── Bottom content ── */}
      <View style={styles.content}>
        <Text style={styles.heading}>
          {"Elevate Your "}
          <Text style={styles.headingOrange}>{"Dining\nExperience"}</Text>
          {" Here!"}
        </Text>

        <Text style={styles.subtitle}>
          Discover the best restaurants and reserve your perfect table in seconds.
        </Text>

        <Pressable
          style={styles.primaryBtn}
          onPress={() => navigation.navigate("Onboarding")}
          android_ripple={{ color: "rgba(255,255,255,0.2)" }}
        >
          <Text style={styles.primaryBtnText}>Let's Get Started</Text>
        </Pressable>

        <View style={styles.footerRow}>
          <Text style={styles.footerMuted}>Already have an account? </Text>
          <Pressable onPress={() => navigation.navigate("PhoneEntry")} hitSlop={8}>
            <Text style={styles.footerLink}>Sign In</Text>
          </Pressable>
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#FFFFFF" },

  // Blob area
  blobArea: {
    height: BLOB_H,
    backgroundColor: "#FFFFFF",
    overflow: "hidden",
  },
  blob: {
    position: "absolute",
    backgroundColor: BLOB_COLOR,
  },
  pill: {
    position: "absolute",
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 999,
  },
  pillPurple: { backgroundColor: PURPLE },
  pillOrange: { backgroundColor: ORANGE },
  pillText: { fontSize: 14, fontWeight: "600", color: "#FFFFFF" },

  // Content
  content: {
    flex: 1,
    paddingHorizontal: 24,
    paddingTop: 24,
    gap: 16,
  },
  heading: {
    fontSize: 28,
    fontWeight: "800",
    color: "#111827",
    lineHeight: 38,
    letterSpacing: -0.5,
    textAlign: "center",
  },
  headingOrange: { color: ORANGE },
  subtitle: {
    fontSize: 14,
    lineHeight: 22,
    color: "#9CA3AF",
    textAlign: "center",
  },
  primaryBtn: {
    marginTop: 8,
    height: 52,
    borderRadius: 999,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
  },
  primaryBtnText: {
    fontSize: 16,
    fontWeight: "600",
    color: "#FFFFFF",
  },
  footerRow: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    marginTop: 4,
  },
  footerMuted: { fontSize: 15, color: "#6B7280" },
  footerLink: { fontSize: 15, fontWeight: "600", color: PURPLE },
});
