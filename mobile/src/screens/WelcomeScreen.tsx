import React from "react";
import {
  Dimensions,
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";
import Svg, { Path } from "react-native-svg";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";

const { width: W, height: H } = Dimensions.get("window");

/** Scale factors from Locofy base design: 375 × 812 logical pixels. */
const sX = W / 375;
const sY = H / 812;

const PURPLE = "#5B4CBD";
const ORANGE = "#EA580C";

/** Physical-screen y-coordinate where the hero ends. */
const HERO_H = 450 * sY;
/** Extra gap between hero bottom and content top — pulls text down ~30 logical px. */
const CONTENT_EXTRA_GAP = 30;

// ── SVG asset path data (inlined from /assets/*.svg) ─────────────────────────

// Rectangle-34624910.svg — organic rotated rhombus blob (viewBox 0 0 296 328)
const BLOB_LARGE =
  "M20.1581 170.019C-14.4278 121.839 -3.40777 54.744 44.7722 20.1581C92.9521 -14.4278 160.047 -3.4077 194.633 44.7722L275.655 157.64C310.241 205.819 299.221 272.914 251.041 307.5C202.861 342.086 135.766 331.066 101.18 282.886L20.1581 170.019Z";

// Rectangle-34624907.svg — rotated square blob (viewBox 0 0 221 220)
const BLOB_MED =
  "M92.3741 21.5318C122.136 -7.61025 169.886 -7.10807 199.028 22.6535C228.17 52.415 227.668 100.166 197.907 129.308L128.187 197.576C98.425 226.718 50.6742 226.216 21.5322 196.455C-7.60979 166.693 -7.10754 118.942 22.654 89.8005L92.3741 21.5318Z";

// Highlight-20.svg — white spiral stroke (viewBox 0 0 46 79)
const SPIRAL =
  "M19.7275 3.47642C36.9893 -2.20142 42.8386 13.8838 42.8749 27.5376C42.9292 47.9679 34.1569 77.3385 18.8686 75.9679C3.58031 74.5972 -2.21781 56.3809 6.12522 30.8933C12.4442 11.5891 25.5808 12.0781 30.7887 19.4247C36.6343 27.6709 35.1175 43.1203 31.3476 52.8863C27.0451 64.0319 20.756 65.1863 15.0073 61.744C7.86001 57.4644 9.84248 41.1114 14.2603 34.7079C18.6781 28.3045 24.3069 33.1957 25.5227 37.9565";

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

export function WelcomeScreen({ navigation }: Props) {
  const insets = useSafeAreaInsets();
  // Content starts at (HERO_H + CONTENT_EXTRA_GAP) from physical top.
  // CONTENT_EXTRA_GAP adds breathing room between the #Food pill and the headline.
  const contentPaddingTop = Math.max(0, HERO_H + CONTENT_EXTRA_GAP - insets.top);

  return (
    <View style={styles.root}>

      {/*
       * ── Full-screen background layer ─────────────────────────────────────
       * absoluteFill covers from physical y=0 (behind status bar) so blobs
       * bleed correctly through the status bar — matching Locofy vectorParent
       * anchored at top:0, left:0 of the full 375×812 frame.
       */}
      <View style={StyleSheet.absoluteFill} pointerEvents="none">

        {/* Shape 1 — frameChild: organic blob, top-left, mostly off-screen */}
        <Svg
          width={296 * sX}
          height={328 * sX}
          viewBox="0 0 296 328"
          style={{ position: "absolute", top: -51 * sY, left: -77 * sX }}
        >
          <Path d={BLOB_LARGE} fill="#DCDCDC" />
        </Svg>

        {/* Shape 2 — frameItem: medium blob, top-right, mostly off-screen top */}
        <Svg
          width={228 * sX}
          height={228 * sX}
          viewBox="0 0 221 220"
          style={{ position: "absolute", top: -100 * sY, left: 213 * sX }}
        >
          <Path d={BLOB_MED} fill="#000000" fillOpacity={0.12} />
        </Svg>

        {/* Shape 3 — rectangleIcon: large blob, center-right, bleeds off right */}
        <Svg
          width={309 * sX}
          height={309 * sX}
          viewBox="0 0 296 328"
          style={{ position: "absolute", top: 133 * sY, left: 184 * sX }}
        >
          <Path d={BLOB_LARGE} fill="#DCDCDC" />
        </Svg>

        {/* Shape 4 — frameInner: decorative circle near hero/content boundary */}
        <View
          style={{
            position: "absolute",
            width: 112 * sX,
            height: 112 * sX,
            borderRadius: 9999,
            backgroundColor: "#D1D5DB",
            opacity: 0.75,
            top: 455 * sY,
            left: 130 * sX,
          }}
        />

        {/* Spiral highlight 1 — upper-right, overlapping blob 1 edge */}
        <Svg
          width={46 * sX}
          height={79 * sX}
          viewBox="0 0 46 79"
          style={{ position: "absolute", top: 55 * sY, left: 244 * sX }}
        >
          <Path
            d={SPIRAL}
            stroke="#FFFFFF"
            strokeWidth={4.61}
            strokeLinecap="round"
            fill="none"
          />
        </Svg>

        {/* Spiral highlight 2 — left-center, overlapping blob 3 */}
        <Svg
          width={46 * sX}
          height={79 * sX}
          viewBox="0 0 46 79"
          style={{ position: "absolute", top: 316 * sY, left: 88 * sX }}
        >
          <Path
            d={SPIRAL}
            stroke="#FFFFFF"
            strokeWidth={4.61}
            strokeLinecap="round"
            fill="none"
          />
        </Svg>

        {/* ── Pill badges ────────────────────────────────────────────────── */}

        {/* #Restaurant — center-right of hero */}
        <View style={[styles.pillBase, styles.pillPurple, {
          top:  155 * sY,
          left: 160 * sX,
        }]}>
          <Text style={styles.pillText}>#Restaurant</Text>
        </View>

        {/* #Food — lower hero, above content boundary */}
        <View style={[styles.pillBase, styles.pillOrange, {
          top:  378 * sY,
          left: 147 * sX,
        }]}>
          <Text style={styles.pillText}>#Food</Text>
        </View>

      </View>

      {/* ── Foreground content ──────────────────────────────────────────────── */}
      <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>
        <View style={[styles.content, { paddingTop: contentPaddingTop }]}>

          {/*
           * Headline — explicit two-line break.
           * Line 1: "Elevate Your " (dark) + "Dining" (orange)
           * Line 2: "Experience" (orange) + " Here!" (dark)
           */}
          <Text style={styles.heading}>
            <Text style={styles.headingDark}>{"Elevate Your "}</Text>
            <Text style={styles.headingOrange}>{"Dining"}</Text>
            {"\n"}
            <Text style={styles.headingOrange}>{"Experience "}</Text>
            <Text style={styles.headingDark}>{"Here!"}</Text>
          </Text>

          <Text style={styles.subtitle}>
            {"Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt"}
          </Text>

          <Pressable
            style={({ pressed }) => [styles.btn, pressed && styles.btnPressed]}
            onPress={() => navigation.navigate("Onboarding")}
            android_ripple={{ color: "rgba(255,255,255,0.25)" }}
          >
            <Text style={styles.btnText}>{"Let's Get Started"}</Text>
          </Pressable>

          <View style={styles.footerRow}>
            <Text style={styles.footerMuted}>{"Already have an account? "}</Text>
            <Pressable onPress={() => navigation.navigate("PhoneEntry")} hitSlop={12}>
              <Text style={styles.footerLink}>{"Sign In"}</Text>
            </Pressable>
          </View>

        </View>
      </SafeAreaView>

    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  safe: {
    flex: 1,
  },

  // ── Pills ─────────────────────────────────────────────────────────────────

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
    paddingBottom: 28,
    gap: 16,
  },

  heading: {
    fontSize: 24,
    fontWeight: "600",
    lineHeight: 34,
    letterSpacing: -0.3,
    textAlign: "center",
  },
  headingDark:   { color: "#111827" },
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
  footerLink: {
    fontSize: 15,
    fontWeight: "600",
    color: PURPLE,
    textDecorationLine: "underline",
  },
});
