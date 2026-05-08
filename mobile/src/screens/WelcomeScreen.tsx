import React from "react";
import { Image, Pressable, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { artboard } from "../utils/artboard";

/**
 * Image-based Welcome screen.
 *
 * The full-screen PNG encodes all visual elements (blobs, pills, heading,
 * subtitle, button graphics). Two transparent Pressables are overlaid at
 * the button positions using artboard-space coordinates (375×812), converted
 * to real screen pixels via artboard().rect().
 *
 * Design coordinates (375×812 artboard, measured from welcome.png pixel data):
 *   "Let's Get Started" button  → x=24, y=651, w=327, h=48
 *   "Sign In" row               → x=40, y=716, w=295, h=36
 */

// Set to true to show semi-transparent overlays on Pressables for visual QA.
const DEBUG_TOUCH_AREAS = false;

type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

export function WelcomeScreen({ navigation }: Props) {
  const ab = artboard();

  const debugStyle = DEBUG_TOUCH_AREAS
    ? { backgroundColor: "rgba(255,0,0,0.3)", borderWidth: 2, borderColor: "red" }
    : undefined;

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/welcome.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Transparent hit area — "Let's Get Started" */}
      <Pressable
        style={[ab.rect(24, 651, 327, 48), debugStyle]}
        onPress={() => navigation.navigate("Onboarding")}
        accessibilityRole="button"
        accessibilityLabel="Let's Get Started"
      />

      {/* Transparent hit area — "Already have an account? Sign In" */}
      <Pressable
        style={[ab.rect(40, 716, 295, 36), debugStyle]}
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
});
