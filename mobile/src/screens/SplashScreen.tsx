import React from "react";
import { Image, StyleSheet, View } from "react-native";
import { StatusBar } from "expo-status-bar";
import { artboard } from "../utils/artboard";

export function SplashScreen() {
  const ab = artboard();
  return (
    <View style={styles.root}>
      {/* Purple background is the fallback; image fills over it once loaded. */}
      <StatusBar style="light" />
      <Image
        source={require("../../assets/auth-final/splash.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
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
