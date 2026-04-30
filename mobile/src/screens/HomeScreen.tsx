import React from "react";
import {
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useAuth } from "../auth/AuthContext";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { colors, radii, spacing } from "../theme/tokens";

export function HomeScreen() {
  const { me, logout } = useAuth();
  const navigation =
    useNavigation<NativeStackNavigationProp<RootStackParamList>>();

  const firstName = me?.name?.trim()?.split(/\s+/)[0];

  return (
    <SafeAreaView style={styles.safe} edges={["top"]}>
      <ScrollView
        contentContainerStyle={styles.scroll}
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.header}>
          <Text style={styles.greeting}>
            Hi{firstName ? `, ${firstName}` : ""}
          </Text>
          <Text style={styles.tagline}>
            Explore restaurants and manage your bookings.
          </Text>
        </View>

        <View style={styles.cards}>
          <HomeActionCard
            title="Restaurants"
            description="Browse and book a table"
            onPress={() => navigation.navigate("Restaurants")}
          />
          <HomeActionCard
            title="My bookings"
            description="View upcoming reservations"
            onPress={() => navigation.navigate("MyBookings")}
          />
          <HomeActionCard
            title="Profile"
            description="Account and preferences"
            onPress={() => navigation.navigate("Profile")}
          />
        </View>

        <Pressable style={styles.logout} onPress={logout}>
          <Text style={styles.logoutText}>Log out</Text>
        </Pressable>
      </ScrollView>
    </SafeAreaView>
  );
}

function HomeActionCard({
  title,
  description,
  onPress,
}: {
  title: string;
  description: string;
  onPress: () => void;
}) {
  return (
    <Pressable
      style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}
      onPress={onPress}
    >
      <View style={styles.cardIcon}>
        <Text style={styles.cardIconText}>{title.charAt(0)}</Text>
      </View>
      <View style={styles.cardText}>
        <Text style={styles.cardTitle}>{title}</Text>
        <Text style={styles.cardDesc}>{description}</Text>
      </View>
      <Text style={styles.chevron}>›</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: colors.surface,
  },
  scroll: {
    paddingHorizontal: spacing.screenHorizontal,
    paddingBottom: spacing.sectionGap * 2,
  },
  header: {
    marginBottom: spacing.sectionGap,
    paddingTop: 8,
  },
  greeting: {
    fontSize: 26,
    fontWeight: "700",
    color: colors.text,
    letterSpacing: -0.3,
  },
  tagline: {
    marginTop: 6,
    fontSize: 15,
    color: colors.textSecondary,
    lineHeight: 22,
  },
  cards: {
    gap: 12,
  },
  card: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: colors.surfaceElevated,
    borderRadius: radii.card,
    paddingVertical: 16,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: colors.border,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  cardPressed: {
    opacity: 0.92,
  },
  cardIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.splashMuted,
    alignItems: "center",
    justifyContent: "center",
    marginRight: 14,
  },
  cardIconText: {
    fontSize: 18,
    fontWeight: "700",
    color: colors.accent,
  },
  cardText: {
    flex: 1,
    gap: 2,
  },
  cardTitle: {
    fontSize: 17,
    fontWeight: "600",
    color: colors.text,
  },
  cardDesc: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  chevron: {
    fontSize: 28,
    color: colors.textMuted,
    marginLeft: 8,
    fontWeight: "300",
  },
  logout: {
    marginTop: spacing.sectionGap + 8,
    alignItems: "center",
    paddingVertical: 14,
  },
  logoutText: {
    fontSize: 16,
    fontWeight: "600",
    color: colors.accent,
  },
});
