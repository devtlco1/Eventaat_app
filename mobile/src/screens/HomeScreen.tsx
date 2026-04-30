import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { PrimaryButton } from "../components/PrimaryButton";
import { useAuth } from "../auth/AuthContext";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";

export function HomeScreen() {
  const { me, logout } = useAuth();
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Welcome{me?.name ? `, ${me.name}` : ""}</Text>
      <Text style={styles.subtitle}>Choose what you want to do.</Text>

      <PrimaryButton
        title="Restaurants"
        onPress={() => navigation.navigate("Restaurants")}
      />
      <PrimaryButton
        title="My bookings"
        onPress={() => navigation.navigate("MyBookings")}
      />
      <PrimaryButton
        title="Profile"
        onPress={() => navigation.navigate("Profile")}
      />

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

