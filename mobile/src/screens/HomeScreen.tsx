import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { PrimaryButton } from "../components/PrimaryButton";
import { useAuth } from "../auth/AuthContext";

export function HomeScreen() {
  const { me, logout } = useAuth();

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Welcome{me?.name ? `, ${me.name}` : ""}</Text>
      <Text style={styles.subtitle}>
        Restaurant discovery will come next.
      </Text>

      <PrimaryButton title="Logout" onPress={logout} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    gap: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: "700",
    color: "#111827",
  },
  subtitle: {
    color: "#4B5563",
  },
});

