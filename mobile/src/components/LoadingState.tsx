import React from "react";
import { ActivityIndicator, StyleSheet, Text, View } from "react-native";

export function LoadingState({ message = "Loading…" }: { message?: string }) {
  return (
    <View style={styles.center}>
      <ActivityIndicator />
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  center: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    gap: 10,
    padding: 16,
  },
  text: { color: "#4B5563" },
});

