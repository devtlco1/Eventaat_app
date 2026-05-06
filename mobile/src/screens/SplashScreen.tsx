import React from "react";
import { Image, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";

// TODO: Temporary long splash duration (2000ms) for visual QA.
//       Reduce later to ~800ms or replace with persisted onboarding/auth state check.
export function SplashScreen() {
  return (
    <View style={styles.root}>
      <StatusBar style="light" />
      <Image
        source={require("../../assets/onboarding/splash.png")}
        style={StyleSheet.absoluteFill}
        resizeMode="cover"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: "#5B4CBD",
  },
});
