/**
 * HomeScreen — placeholder for a future curated home feed.
 *
 * NOT currently mounted in AppNavigator. Authenticated users land on
 * RestaurantListScreen (Explore tab). This file is kept for when a dedicated
 * home feed (featured restaurants, stories carousel, event highlights) is built.
 */
import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useAuth } from "../auth/AuthContext";
import { Button } from "../components/Button";
import { colors, spacing } from "../theme/tokens";

export function HomeScreen() {
  const { me, logout } = useAuth();
  const firstName = me?.name?.trim()?.split(/\s+/)[0];

  return (
    <SafeAreaView style={styles.safe} edges={["top"]}>
      <View style={styles.container}>
        <Text style={styles.greeting}>
          Hi{firstName ? `, ${firstName}` : ""}
        </Text>
        <Text style={styles.tagline}>
          Explore restaurants and manage your bookings.
        </Text>
        <Button title="Log out" onPress={logout} variant="outline" style={styles.btn} />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.surface },
  container: {
    flex: 1,
    padding: spacing.lg,
    justifyContent: "center",
    gap: spacing.md,
  },
  greeting: { fontSize: 26, fontWeight: "700", color: colors.text },
  tagline: { fontSize: 15, color: colors.textSecondary, lineHeight: 22 },
  btn: { marginTop: spacing.lg },
});
