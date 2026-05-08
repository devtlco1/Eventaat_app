import React, { useState } from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Image-based Onboarding screen (3 slides).
 *
 * PNG provides all visual chrome. Transparent Pressables overlay interactive
 * positions (375×812 artboard space):
 *
 *   Skip text   → x=299  y=5   w=76  h=44  (slides 1+2 only)
 *   Back circle → x=0    y=674 w=76  h=60  (slides 2+3 only)
 *   Next circle → x=299  y=674 w=76  h=60  (all slides)
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
  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
