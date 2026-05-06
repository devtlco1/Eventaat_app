import React, { useState } from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Image-based Onboarding screen (3 slides).
 *
 * Each slide PNG encodes all visual elements (mockup, heading, subtitle,
 * dot indicators, button graphics). Transparent Pressables are overlaid at
 * interactive positions using artboard-space coordinates (375×812).
 *
 * Design coordinates (375×812 artboard, measured from PNGs):
 *   Skip text   → y=49,  h=40, x=299, w=76  (slides 1+2 only)
 *   Back circle → y=718, h=60, x=0,   w=76  (slides 2+3 only)
 *   Next circle → y=718, h=60, x=299, w=76  (all slides)
 *
 * Slide 1 (onboarding-1): Skip + Next
 * Slide 2 (onboarding-2): Skip + Back + Next
 * Slide 3 (onboarding-3): Back + Next → navigates to SignUp
 */

const SLIDES = [
  require("../../assets/onboarding/onboarding-1.png"),
  require("../../assets/onboarding/onboarding-2.png"),
  require("../../assets/onboarding/onboarding-3.png"),
];

type Props = NativeStackScreenProps<AuthStackParamList, "Onboarding">;

export function OnboardingScreen({ navigation }: Props) {
  const [index, setIndex] = useState(0);
  const ab = artboard();

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
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Skip — top-right; hidden on last slide (slide 3 has no Skip text) */}
      {!isLast && (
        <Pressable
          style={ab.rect(299, 5, 76, 44)}
          onPress={onSkip}
          hitSlop={12}
          accessibilityRole="button"
          accessibilityLabel="Skip"
        />
      )}

      {/* Back circle — bottom-left; hidden on first slide */}
      {!isFirst && (
        <Pressable
          style={ab.rect(0, 674, 76, 60)}
          onPress={onBack}
          accessibilityRole="button"
          accessibilityLabel="Back"
        />
      )}

      {/* Next / Get Started circle — bottom-right; always visible */}
      <Pressable
        style={ab.rect(299, 674, 76, 60)}
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
});
