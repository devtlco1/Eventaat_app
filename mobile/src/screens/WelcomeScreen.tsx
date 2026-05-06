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
 * Design coordinates (375×812 artboard, measured from PNG):
 *   "Let's Get Started" button  → y=585, h=52, x=24, w=327
 *   "Sign In" row               → y=652, h=36, x=40, w=295
 */
type Props = NativeStackScreenProps<AuthStackParamList, "Welcome">;

export function WelcomeScreen({ navigation }: Props) {
  const ab = artboard();

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/onboarding/welcome.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Transparent hit area — "Let's Get Started" */}
      <Pressable
        style={ab.rect(24, 541, 327, 52)}
        onPress={() => navigation.navigate("Onboarding")}
        accessibilityRole="button"
        accessibilityLabel="Let's Get Started"
      />

      {/* Transparent hit area — "Already have an account? Sign In" */}
      <Pressable
        style={ab.rect(40, 608, 295, 36)}
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
