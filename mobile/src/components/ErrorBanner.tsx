import React from "react";
import { StyleSheet, Text, View } from "react-native";

export function ErrorBanner({ message }: { message: string | null }) {
  if (!message) return null;
  return (
    <View style={styles.box}>
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  box: {
    backgroundColor: "#FEE2E2",
    borderColor: "#FCA5A5",
    borderWidth: 1,
    padding: 12,
    borderRadius: 10,
  },
  text: {
    color: "#991B1B",
  },
});

