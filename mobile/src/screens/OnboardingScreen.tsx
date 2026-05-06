import React, { useState } from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";

/**
 * Image-based Onboarding screens (slides 1–3).
 *
 * Each slide is a full-screen PNG (375×812 @3x) that includes the phone
 * mockup, heading, subtitle, dot indicators, and nav-button visuals.
 * Transparent Pressable hit-areas are overlaid at the interactive element
 * positions, using percentage-based positioning so they scale correctly
 * with the image under resizeMode="cover" across iPhone sizes.
 *
 * Slide 1 — "Discover Dining Delights"               (onboarding-1.png)
 *   Interactive: Skip (top-right), Next (bottom-right)
 * Slide 2 — "Build Your Favourite Restaurant Collection" (onboarding-2.png)
 *   Interactive: Skip (top-right), Back (bottom-left), Next (bottom-right)
 * Slide 3 — "Connect Instantly: Chat & Call with Owners" (onboarding-3.png)
 *   Interactive: Back (bottom-left), Next→SignUp (bottom-right)
 *
 * Calibrated from the 375×812 Figma/Locofy exports:
 *   Skip text:         ~9–12 % from top, right edge
 *   Back/Next circles: ~88–94 % from top, left/right edges
 */

const SLIDES = [
  require("../../assets/onboarding/onboarding-1.png"),
  require("../../assets/onboarding/onboarding-2.png"),
  require("../../assets/onboarding/onboarding-3.png"),
];

type Props = NativeStackScreenProps<AuthStackParamList, "Onboarding">;

export function OnboardingScreen({ navigation }: Props) {
  const [index, setIndex] = useState(0);
  const isFirst = index === 0;
  const isLast = index === SLIDES.length - 1;

  const onNext = () => {
    if (!isLast) {
      setIndex(index + 1);
    } else {
      navigation.navigate("SignUp");
    }
  };

  const onBack = () => {
    if (!isFirst) setIndex(index - 1);
  };

  const onSkip = () => navigation.navigate("SignUp");

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={SLIDES[index]}
        style={StyleSheet.absoluteFill}
        resizeMode="cover"
      />

      {/* Skip — shown on slides 1 and 2; slide 3 image has no Skip text */}
      {!isLast && (
        <Pressable
          style={styles.skipArea}
          onPress={onSkip}
          hitSlop={16}
          accessibilityRole="button"
          accessibilityLabel="Skip"
        />
      )}

      {/* Back circle — shown on slides 2 and 3 */}
      {!isFirst && (
        <Pressable
          style={styles.backBtn}
          onPress={onBack}
          accessibilityRole="button"
          accessibilityLabel="Back"
        />
      )}

      {/* Next / Get Started circle — always visible */}
      <Pressable
        style={styles.nextBtn}
        onPress={onNext}
        accessibilityRole="button"
        accessibilityLabel={isLast ? "Get Started" : "Next"}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: "#F5F5F5",
  },

  // "Skip" text — top-right corner of slide image
  skipArea: {
    position: "absolute",
    top: "9%",
    right: 12,
    width: 64,
    height: 36,
  },

  // Back arrow circle — bottom-left of slide image
  backBtn: {
    position: "absolute",
    bottom: "6%",
    left: 16,
    width: 60,
    height: 60,
  },

  // Next / Get Started arrow circle — bottom-right of slide image
  nextBtn: {
    position: "absolute",
    bottom: "6%",
    right: 16,
    width: 60,
    height: 60,
  },
});
