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
 * Scale factors anchored to Locofy base design (375 × 812).
 * All blob positions and sizes are converted from those coordinates.
 */
const sX = W / 375;
const sY = H / 812;

const PURPLE = "#5B4CBD";
const ORANGE = "#EA580C";

/**
 * Height of the hero spacer that positions the pill badges.
 * Locofy vectorParent = 450 px on 812 px screen.
 */
const HERO_H = 450 * sY;

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

/**
 * Welcome landing screen.
 *
 * Visual reference: Locofy project 3DLfwlPhWxW2D7DMuFiSefj5zoe — Welcome frame.
 *
 * Key approach:
 *   • Background shapes use borderRadius:9999 (true ovals/circles, NOT rectangles).
 *   • They are absolutely positioned on a full-screen layer behind all content.
 *   • Pills are inside the heroSpacer View, positioned proportionally.
 *   • Content section below the hero spacer holds heading / subtitle / CTA / footer.
 */
export function WelcomeScreen({ navigation }: Props) {
  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>

      {/*
       * ── Absolute background layer ────────────────────────────────────────────
       *
       * Blob positions/sizes converted from Locofy vectorParent coordinates
       * (absolute, top:0, left:0, right:0, height:450 on 375×812 base).
       *
       * CRITICAL: borderRadius:9999 → produces smooth organic circles/ovals.
       * Using a small fixed borderRadius on large Views causes the "rectangle"
       * appearance seen in the previous version.
       *
       * pointerEvents="none" so taps pass through to the foreground layout.
       */}
      <View style={StyleSheet.absoluteFill} pointerEvents="none">

        {/*
         * Shape 1 — frameChild
         * Locofy: 296×328, Color.colorGainsboro (#DCDCDC), top:-51, left:-77
         * Large circle anchored to upper-left, mostly off-screen.
         */}
        <View style={[styles.blob, {
          width: 296 * sX,
          height: 296 * sX,   // square → perfect circle
          left:  -77 * sX,
          top:   -51 * sX,
          backgroundColor: "#DCDCDC",
        }]} />

        {/*
         * Shape 2 — frameItem
         * Locofy: 228×233, dark tint (appears mid-grey on white), top:-100, left:213
         * Medium circle anchored to upper-right, mostly off-screen top.
         */}
        <View style={[styles.blob, {
          width: 228 * sX,
          height: 228 * sX,
          left:  213 * sX,
          top:  -100 * sX,
          backgroundColor: "#D0D0D0",
        }]} />

        {/*
         * Shape 3 — rectangleIcon
         * Locofy: 309×317, Color.colorGainsboro, top:133, left:184
         * Large circle at center-right, partially off-screen right edge.
         */}
        <View style={[styles.blob, {
          width: 309 * sX,
          height: 309 * sX,
          left:  184 * sX,
          top:   133 * sY,
          backgroundColor: "#DCDCDC",
        }]} />

        {/*
         * Shape 4 — frameInner
         * Locofy: 135×135, Color.colorGray300 (#D1D5DB), top:445, left:138
         * Small circle that straddles the hero/content boundary.
         * Visible as a decorative element in the lower portion of the screen.
         */}
        <View style={[styles.blob, {
          width: 135 * sX,
          height: 135 * sX,
          left:  138 * sX,
          top:   445 * sY,
          backgroundColor: "#D1D5DB",
        }]} />

      </View>

      {/* ── Foreground layout ─────────────────────────────────────────────────── */}
      <View style={styles.layout}>

        {/*
         * Hero spacer — gives the pill badges a coordinate system and pushes
         * the content section below the blob area.
         * Height = 450 * sY, proportional to Locofy vectorParent.
         * No overflow:hidden so pills aren't clipped at the boundary.
         */}
        <View style={{ height: HERO_H }}>

          {/*
           * #Restaurant pill
           * Locofy: inside welcomeInner (y_top≈155, x=160 from screen left).
           * Position relative to heroSpacer using design proportions:
           *   vertical   = (155 / 450) = 34.4 % of HERO_H
           *   horizontal = 160 * sX
           */}
          <View style={[styles.pillBase, styles.pillPurple, {
            top:  HERO_H * 0.344,
            left: 160 * sX,
          }]}>
            <Text style={styles.pillText}>#Restaurant</Text>
          </View>

          {/*
           * #Food pill
           * Locofy: top of frameParent (y≈378), left ≈ 147 in design units.
           * Position relative to heroSpacer:
           *   vertical   = (378 / 450) = 84 % of HERO_H
           *   horizontal = 147 * sX
           */}
          <View style={[styles.pillBase, styles.pillOrange, {
            top:  HERO_H * 0.84,
            left: 147 * sX,
          }]}>
            <Text style={styles.pillText}>#Food</Text>
          </View>

        </View>

        {/* ── Content section ──────────────────────────────────────────────── */}
        <View style={styles.content}>

          {/*
           * Headline — 24 px / weight 600 (Locofy FrameComponent spec).
           * "Dining Experience" rendered inline in orange; no explicit \n so
           * text wraps naturally at the container width.
           */}
          <Text style={styles.heading}>
            <Text style={styles.headingGray}>{"Elevate Your "}</Text>
            <Text style={styles.headingOrange}>{"Dining Experience"}</Text>
            <Text style={styles.headingGray}>{" Here!"}</Text>
          </Text>

          {/* Subtitle — real Eventaat copy, grey, centered */}
          <Text style={styles.subtitle}>
            Discover the best restaurants and reserve your perfect table in seconds.
          </Text>

          {/*
           * CTA button — Locofy Button1: borderRadius:78, paddingH:32,
           * fontWeight:"500", fontSize:16.
           */}
          <Pressable
            style={({ pressed }) => [styles.btn, pressed && styles.btnPressed]}
            onPress={() => navigation.navigate("Onboarding")}
            android_ripple={{ color: "rgba(255,255,255,0.25)" }}
          >
            <Text style={styles.btnText}>{"Let's Get Started"}</Text>
          </Pressable>

          {/*
           * Sign-in footer — Locofy signIn2: textDecorationLine:"underline",
           * color: Color.mainPurple (#5B4CBD).
           */}
          <View style={styles.footerRow}>
            <Text style={styles.footerMuted}>{"Already have an account? "}</Text>
            <Pressable onPress={() => navigation.navigate("PhoneEntry")} hitSlop={12}>
              <Text style={styles.footerLink}>{"Sign In"}</Text>
            </Pressable>
          </View>

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

  layout: {
    flex: 1,
  },

  // ── Background blobs ──────────────────────────────────────────────────────

  /**
   * Base style for all blob shapes.
   * borderRadius:9999 is the critical property — it converts any rectangle
   * into a smooth ellipse/circle, matching the Figma organic blob aesthetic.
   */
  blob: {
    position: "absolute",
    borderRadius: 9999,
  },

  // ── Pills ─────────────────────────────────────────────────────────────────

  /**
   * Locofy wrapperBorder spec:
   *   borderRadius:160, borderWidth:3, borderColor:white,
   *   paddingHorizontal:12, paddingVertical:6
   */
  pillBase: {
    position: "absolute",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 160,
    borderWidth: 3,
    borderColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
  },
  pillPurple: { backgroundColor: PURPLE },
  pillOrange: { backgroundColor: ORANGE },
  pillText: {
    fontSize: 14,
    fontWeight: "600",
    lineHeight: 21,
    color: "#FFFFFF",
  },

  // ── Content ───────────────────────────────────────────────────────────────

  content: {
    flex: 1,
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 12,
    gap: 14,
  },

  heading: {
    fontSize: 24,
    fontWeight: "600",
    lineHeight: 34,
    letterSpacing: -0.3,
    textAlign: "center",
  },
  headingGray:   { color: "#111827" },
  headingOrange: { color: ORANGE },

  subtitle: {
    fontSize: 14,
    lineHeight: 21,
    color: "#9CA3AF",
    textAlign: "center",
  },

  btn: {
    height: 52,
    borderRadius: 78,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 32,
  },
  btnPressed: { opacity: 0.88 },
  btnText: {
    fontSize: 16,
    fontWeight: "500",
    lineHeight: 24,
    color: "#FFFFFF",
  },

  footerRow: {
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
  },
  footerMuted: {
    fontSize: 15,
    color: "#6B7280",
  },
  /** Locofy signIn2: underline + mainPurple color. */
  footerLink: {
    fontSize: 15,
    fontWeight: "600",
    color: PURPLE,
    textDecorationLine: "underline",
  },
});
