import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { colors } from "../theme/tokens";

/**
 * Shown during Auth bootstrap only. Decorative blobs inspired by Figma Splash (1:2011).
 */
export function SplashScreen() {
  return (
    <SafeAreaView style={styles.safe} edges={["top", "bottom"]}>
      <View style={styles.blobTopLeft} />
      <View style={styles.blobTopRight} />
      <View style={styles.blobBottom} />
      <View style={styles.center}>
        <Text style={styles.wordmark}>Eventaat</Text>
        <Text style={styles.tagline}>Discover · Reserve · Enjoy</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.background,
    overflow: "hidden",
  },
  blobTopLeft: {
    position: "absolute",
    width: 220,
    height: 220,
    borderRadius: 110,
    backgroundColor: colors.splashMuted,
    opacity: 0.85,
    top: -40,
    left: -80,
  },
  blobTopRight: {
    position: "absolute",
    width: 140,
    height: 140,
    borderRadius: 70,
    backgroundColor: "#FFFBEB",
    top: 120,
    right: -30,
  },
  blobBottom: {
    position: "absolute",
    width: 280,
    height: 280,
    borderRadius: 140,
    backgroundColor: colors.splashMuted,
    opacity: 0.5,
    bottom: -120,
    right: -60,
  },
  center: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    paddingHorizontal: 32,
  },
  wordmark: {
    fontSize: 44,
    fontWeight: "800",
    color: colors.text,
    letterSpacing: -1,
    marginBottom: 12,
  },
  tagline: {
    fontSize: 17,
    color: colors.textSecondary,
    fontWeight: "500",
  },
});
