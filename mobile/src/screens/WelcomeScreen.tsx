import React from "react";
import { Dimensions, Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";

/**
 * Image-based Welcome screen.
 *
 * The full-screen PNG (375×812 @3x) contains all visual elements.
 * Two transparent Pressable hit-areas are overlaid at the button positions
 * in the design, using percentage-based positioning so they scale with the
 * image under resizeMode="cover" on all modern iPhones.
 *
 * Button positions calibrated from the 375×812 Figma/Locofy export:
 *   - "Let's Get Started" button: ~72–79 % from screen top
 *   - "Sign In" link:             ~82–87 % from screen top
 */

const { width: W } = Dimensions.get("window");

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

export function WelcomeScreen({ navigation }: Props) {
  return (
    <View style={styles.root}>
      <StatusBar style="dark" />
      <Image
        source={require("../../assets/onboarding/welcome.png")}
        style={StyleSheet.absoluteFill}
        resizeMode="cover"
      />

      {/* Transparent hit area — "Let's Get Started" */}
      <Pressable
        style={styles.ctaBtn}
        onPress={() => navigation.navigate("Onboarding")}
        accessibilityRole="button"
        accessibilityLabel="Let's Get Started"
      />

      {/* Transparent hit area — "Sign In" */}
      <Pressable
        style={styles.signInBtn}
        onPress={() => navigation.navigate("PhoneEntry")}
        accessibilityRole="button"
        accessibilityLabel="Sign In"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: "#FFFFFF",
  },

  // Covers the full-width pill button at ~72 % from top
  ctaBtn: {
    position: "absolute",
    top: "72%",
    left: 24,
    right: 24,
    height: 58,
  },

  // Covers the "Already have an account? Sign In" row at ~82 % from top
  signInBtn: {
    position: "absolute",
    top: "82%",
    left: 40,
    right: 40,
    height: 40,
  },
});
