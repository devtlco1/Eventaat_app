import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";

const PURPLE = "#5B4CBD";

/**
 * Shown while auth bootstraps. Full-purple screen with logo circle.
 * Matches Figma Splash frame (node-id 0-1).
 */
export function SplashScreen() {
  return (
    <View style={styles.container}>
      <View style={styles.logoCircle}>
        <Ionicons name="restaurant" size={40} color={PURPLE} />
      </View>
      <Text style={styles.title}>Table Reservation</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    gap: 16,
  },
  logoCircle: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
  },
  title: {
    fontSize: 24,
    fontWeight: "600",
    color: "#FFFFFF",
    letterSpacing: -0.3,
  },
});
