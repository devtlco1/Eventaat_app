import React, { useState } from "react";
import { Image, Pressable, StyleSheet, Text, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Image-based Onboarding screen (3 slides).
 *
 * PNG provides visual chrome (phone mockup, title text, dots, nav buttons).
 * The PNG subtitle text is lorem ipsum — a native Text overlay covers it with
 * real copy. Transparent Pressables overlay interactive positions (375×812):
 *
 *   Skip text       → x=299  y=5   w=76  h=44  (slides 1+2 only)
 *   Back circle     → x=0    y=674 w=76  h=60  (slides 2+3 only)
 *   Next circle     → x=299  y=674 w=76  h=60  (all slides)
 *   Subtitle cover  → x=0    y=626 w=375 h=85  (white box + real copy)
 *
 * Last slide Next → LocationPermission. Skip always → LocationPermission.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const SLIDES = [
  require("../../assets/auth-final/onboarding-1.png"),
  require("../../assets/auth-final/onboarding-2.png"),
  require("../../assets/auth-final/onboarding-3.png"),
];

const SUBTITLES = [
  "Browse nearby restaurants and discover exclusive dining offers.",
  "Save your favourite restaurants and find them again anytime.",
  "Contact restaurants for questions and special requests.",
];

type Props = NativeStackScreenProps<AuthStackParamList, "Onboarding">;

export function OnboardingScreen({ navigation }: Props) {
  const [index, setIndex] = useState(0);
  const ab = artboard();

  const isFirst = index === 0;
  const isLast = index === SLIDES.length - 1;

  const advance = () => navigation.navigate("LocationPermission");

  const onNext = () => {
    if (!isLast) {
      setIndex(index + 1);
    } else {
      advance();
    }
  };

  const onBack = () => {
    if (!isFirst) setIndex(index - 1);
  };

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={SLIDES[index]}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* White cover — hides the lorem ipsum subtitle baked into the PNG */}
      <View
        style={[
          ab.rect(0, 626, 375, 85),
          styles.subtitleCover,
          DEBUG_TOUCH_AREAS && styles.debugCover,
        ]}
        pointerEvents="none"
      >
        <Text style={styles.subtitleText}>{SUBTITLES[index]}</Text>
      </View>

      {/* Skip — top-right; hidden on last slide */}
      {!isLast && (
        <Pressable
          style={[ab.rect(299, 5, 76, 44), DEBUG_TOUCH_AREAS && styles.debug]}
          onPress={advance}
          hitSlop={12}
          accessibilityRole="button"
          accessibilityLabel="Skip"
        />
      )}

      {/* Back circle — bottom-left; hidden on first slide */}
      {!isFirst && (
        <Pressable
          style={[ab.rect(0, 674, 76, 60), DEBUG_TOUCH_AREAS && styles.debug]}
          onPress={onBack}
          accessibilityRole="button"
          accessibilityLabel="Back"
        />
      )}

      {/* Next / Get Started circle — bottom-right; always visible */}
      <Pressable
        style={[ab.rect(299, 674, 76, 60), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={onNext}
        accessibilityRole="button"
        accessibilityLabel={isLast ? "Get Started" : "Next"}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#F5F5F5" },

  subtitleCover: {
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 24,
  },
  subtitleText: {
    fontSize: 14,
    lineHeight: 22,
    color: "#6B7280",
    textAlign: "center",
  },

  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
  debugCover: { backgroundColor: "rgba(0,128,255,0.15)" },
});
