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

/**
 * Hero area height.
 * Locofy reference: vectorParent is 450 px on an 812 px screen ≈ 55 %.
 */
const HERO_H = H * 0.55;

const PURPLE = "#5B4CBD";
const ORANGE = "#EA580C";
const BLOB_COLOR = "#DEDEDE";

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

/**
 * Welcome landing screen.
 * Visual reference: Locofy project 3DLfwlPhWxW2D7DMuFiSefj5zoe – Welcome frame.
 *
 * Layout (top → bottom):
 *   [Hero ~55 %]   abstract grey blob shapes  +  #Restaurant pill (absolute)
 *   [Content ~45 %]  #Food pill · headline · subtitle · CTA · sign-in footer
 */
export function WelcomeScreen({ navigation }: Props) {
  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>

      {/* ── Hero / blob area ──────────────────────────────────────────────── */}
      <View style={styles.heroArea}>
        {/*
         * Abstract background shapes approximated with rounded Views.
         * Locofy uses SVG Rectangle assets; we keep the blob approach so no
         * additional dependency (react-native-svg) is required.
         *
         * Shape 1 – large gainsboro shape, upper-left (mostly off-screen left).
         * Locofy ref: Rectangle-34624910 (296 × 328, gainsboro).
         */}
        <View
          style={[
            styles.blob,
            {
              width: W * 0.88,
              height: W * 1.00,
              borderRadius: W * 0.18,
              left: -W * 0.42,
              top: -W * 0.18,
            },
          ]}
        />

        {/*
         * Shape 2 – medium darker shape, upper-right (mostly off-screen right).
         * Locofy ref: Rectangle-34624907 (228 × 233).
         */}
        <View
          style={[
            styles.blob,
            {
              width: W * 0.64,
              height: W * 0.68,
              borderRadius: W * 0.14,
              right: -W * 0.24,
              top: -W * 0.22,
              backgroundColor: "#C8C8C8",
            },
          ]}
        />

        {/*
         * Shape 3 – large gainsboro shape, right-centre (partially off-screen).
         * Locofy ref: rectangleIcon (309 × 317).
         */}
        <View
          style={[
            styles.blob,
            {
              width: W * 0.90,
              height: W * 0.92,
              borderRadius: W * 0.18,
              right: -W * 0.34,
              top: HERO_H * 0.26,
            },
          ]}
        />

        {/*
         * Shape 4 – small circle, lower-centre.
         * Locofy ref: frameInner (135 × 135, colorGray300, rotated 180°).
         */}
        <View
          style={[
            styles.blob,
            {
              width: 135,
              height: 135,
              borderRadius: 68,
              left: W * 0.34,
              top: HERO_H * 0.72,
              backgroundColor: "#D1D5DB",
            },
          ]}
        />

        {/* #Restaurant pill – upper-right quadrant of hero area */}
        <View
          style={[
            styles.pillBase,
            styles.pillAbsolute,
            styles.pillPurple,
            { top: HERO_H * 0.44, left: W * 0.44 },
          ]}
        >
          <Text style={styles.pillText}>#Restaurant</Text>
        </View>
      </View>

      {/* ── Content area ─────────────────────────────────────────────────── */}
      <View style={styles.content}>

        {/*
         * #Food pill – placed in the content section (not the hero).
         * Locofy: foodWrapper sits inside frameParent above FrameComponent,
         * right-aligned. We mirror this by right-justifying the row.
         */}
        <View style={styles.foodPillRow}>
          <View style={[styles.pillBase, styles.pillOrange]}>
            <Text style={styles.pillText}>#Food</Text>
          </View>
        </View>

        {/*
         * Headline – 24 px / weight 600 (matches Locofy FrameComponent).
         * "Dining Experience" renders inline in orange; the rest is gray.
         * No explicit \n — text wraps naturally at the container width.
         */}
        <Text style={styles.heading}>
          <Text style={styles.headingGray}>{"Elevate Your "}</Text>
          <Text style={styles.headingOrange}>{"Dining Experience"}</Text>
          <Text style={styles.headingGray}>{" Here!"}</Text>
        </Text>

        {/* Subtitle */}
        <Text style={styles.subtitle}>
          Discover the best restaurants and reserve your perfect table in seconds.
        </Text>

        {/*
         * CTA button.
         * Locofy Button1: borderRadius 78, paddingH 32, paddingV 12.
         */}
        <Pressable
          style={({ pressed }) => [
            styles.primaryBtn,
            pressed && styles.primaryBtnPressed,
          ]}
          onPress={() => navigation.navigate("Onboarding")}
          android_ripple={{ color: "rgba(255,255,255,0.25)" }}
        >
          <Text style={styles.primaryBtnText}>{"Let's Get Started"}</Text>
        </Pressable>

        {/*
         * Sign-in footer.
         * Locofy signIn2 style: textDecorationLine "underline", color mainPurple.
         */}
        <View style={styles.footerRow}>
          <Text style={styles.footerMuted}>{"Already have an account? "}</Text>
          <Pressable onPress={() => navigation.navigate("PhoneEntry")} hitSlop={12}>
            <Text style={styles.footerLink}>{"Sign In"}</Text>
          </Pressable>
        </View>

      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  // ── Hero area ──────────────────────────────────────────────────────────────

  heroArea: {
    height: HERO_H,
    overflow: "hidden",
    backgroundColor: "#FFFFFF",
  },

  // ── Blob shapes ────────────────────────────────────────────────────────────

  blob: {
    position: "absolute",
    backgroundColor: BLOB_COLOR,
  },

  // ── Pills ──────────────────────────────────────────────────────────────────

  /**
   * pillBase – shared style for every pill (both hero-absolute and inline).
   * Locofy wrapperBorder: borderRadius 160, borderWidth 3, borderColor white,
   * paddingH 12, paddingV 6.
   */
  pillBase: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 160,
    borderWidth: 3,
    borderColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
  },

  /** Applied only to hero-area pills that must be absolutely positioned. */
  pillAbsolute: {
    position: "absolute",
  },

  pillPurple: { backgroundColor: PURPLE },
  pillOrange: { backgroundColor: ORANGE },

  pillText: {
    fontSize: 14,
    fontWeight: "600",
    lineHeight: 21,
    color: "#FFFFFF",
  },

  // ── Content area ───────────────────────────────────────────────────────────

  content: {
    flex: 1,
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 8,
    gap: 14,
  },

  /**
   * Row that holds the #Food pill, right-aligned to mirror the Locofy layout
   * where foodWrapper sits at the right edge of the frameParent.
   */
  foodPillRow: {
    flexDirection: "row",
    justifyContent: "flex-end",
  },

  // ── Heading ────────────────────────────────────────────────────────────────

  heading: {
    fontSize: 24,
    fontWeight: "600",
    lineHeight: 34,
    letterSpacing: -0.3,
    textAlign: "center",
  },

  /** Plain text spans inside the heading inherit fontSize from the parent. */
  headingGray: {
    color: "#111827",
  },
  headingOrange: {
    color: ORANGE,
  },

  // ── Subtitle ───────────────────────────────────────────────────────────────

  subtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: "#9CA3AF",
    textAlign: "center",
  },

  // ── CTA button ─────────────────────────────────────────────────────────────

  primaryBtn: {
    height: 52,
    borderRadius: 78,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 32,
  },

  primaryBtnPressed: {
    opacity: 0.88,
  },

  primaryBtnText: {
    fontSize: 16,
    fontWeight: "500",
    lineHeight: 24,
    color: "#FFFFFF",
  },

  // ── Sign-in footer ─────────────────────────────────────────────────────────

  footerRow: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
  },

  footerMuted: {
    fontSize: 15,
    color: "#6B7280",
  },

  /**
   * Locofy signIn2: textDecorationLine "underline", color Color.mainPurple.
   * fontWeight "600" keeps it visually distinct from the muted text.
   */
  footerLink: {
    fontSize: 15,
    fontWeight: "600",
    color: PURPLE,
    textDecorationLine: "underline",
  },
});
