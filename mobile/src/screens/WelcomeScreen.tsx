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

/** Height of the decorative illustration area — top ~60 % of screen */
const BLOB_H = H * 0.60;
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
        {/* Large blob — wraps off left edge, vertical centre */}
        <View
          style={[
            styles.blob,
            {
              width: W * 1.25,
              height: W * 1.25,
              borderRadius: W * 0.625,
              left: -W * 0.65,
              top: BLOB_H * 0.1,
            },
          ]}
        />
        {/* Medium blob — top-right overflow */}
        <View
          style={[
            styles.blob,
            {
              width: W * 0.44,
              height: W * 0.44,
              borderRadius: W * 0.22,
              right: -W * 0.1,
              top: -W * 0.06,
            },
          ]}
        />
        {/* Large blob — right-centre overflow */}
        <View
          style={[
            styles.blob,
            {
              width: W * 1.05,
              height: W * 1.05,
              borderRadius: W * 0.525,
              right: -W * 0.58,
              top: BLOB_H * 0.22,
            },
          ]}
        />
        {/* Medium blob — bottom-left */}
        <View
          style={[
            styles.blob,
            {
              width: W * 0.60,
              height: W * 0.60,
              borderRadius: W * 0.30,
              left: -W * 0.14,
              bottom: -W * 0.16,
            },
          ]}
        />

        {/* Floating hashtag pills — centred in blob area */}
        <View style={[styles.pill, styles.pillPurple, { top: H * 0.34, left: W * 0.43 }]}>
          <Text style={styles.pillText}>#Restaurant</Text>
        </View>
        <View style={[styles.pill, styles.pillOrange, { top: H * 0.48, left: W * 0.28 }]}>
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

  // Blob area — absolutely-positioned blobs float inside a clipped container
  blobArea: {
    height: BLOB_H,
    overflow: "hidden",
    backgroundColor: "#FFFFFF",
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
    paddingTop: 20,
    gap: 14,
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
    marginTop: 4,
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
  },
  footerMuted: { fontSize: 15, color: "#6B7280" },
  footerLink: { fontSize: 15, fontWeight: "600", color: PURPLE },
});
