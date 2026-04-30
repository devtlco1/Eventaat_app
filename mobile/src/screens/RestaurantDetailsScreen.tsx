import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActivityIndicator, ScrollView, StyleSheet, Text, View } from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { getRestaurant } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileRestaurantDetails } from "../api/types";

type Props = NativeStackScreenProps<RootStackParamList, "RestaurantDetails">;

export function RestaurantDetailsScreen({ route, navigation }: Props) {
  const { slug } = route.params;
  const { token, logout } = useAuth();

  const [data, setData] = useState<MobileRestaurantDetails | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) return;
    setIsLoading(true);
    setError(null);
    try {
      const res = await getRestaurant(token, slug);
      setData(res);
      navigation.setOptions({ title: "Restaurant details" });
    } catch (e) {
      if (isAuthError(e)) {
        await logout();
        return;
      }
      setError(getErrorMessage(e));
    } finally {
      setIsLoading(false);
    }
  }, [logout, navigation, slug, token]);

  useEffect(() => {
    void load();
  }, [load]);

  const branches = useMemo(() => data?.branches ?? [], [data]);

  if (isLoading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
        <Text style={styles.centerText}>Loading…</Text>
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <ErrorBanner message={error} />

      <Text style={styles.title}>{data?.name ?? "Restaurant"}</Text>
      <Text style={styles.subtitle}>Active branches: {branches.length}</Text>

      <PrimaryButton
        title="Create booking"
        onPress={() => navigation.navigate("CreateBooking", { restaurantSlug: slug })}
      />

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Branches</Text>
        {branches.length === 0 ? (
          <Text style={styles.muted}>No active branches.</Text>
        ) : (
          branches.map((b) => (
            <View key={b.id} style={styles.card}>
              <Text style={styles.cardTitle}>{b.name}</Text>
              <Text style={styles.muted}>Code: {b.code}</Text>

              <View style={styles.subsection}>
                <Text style={styles.sectionTitle}>Seating areas</Text>
                {(b.seating_areas ?? []).length === 0 ? (
                  <Text style={styles.muted}>No seating areas.</Text>
                ) : (
                  b.seating_areas.map((sa) => (
                    <View key={sa.id} style={styles.subcard}>
                      <Text style={styles.cardTitle}>
                        {sa.name}
                        {sa.type ? ` (${sa.type})` : ""}
                      </Text>
                      <Text style={styles.muted}>Tables: {(sa.tables ?? []).length}</Text>
                      {(sa.tables ?? []).map((t) => (
                        <Text key={t.id} style={styles.muted}>
                          - {t.label} (cap {t.capacity})
                        </Text>
                      ))}
                    </View>
                  ))
                )}
              </View>
            </View>
          ))
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16, gap: 12 },
  title: { fontSize: 20, fontWeight: "800", color: "#111827" },
  subtitle: { color: "#4B5563" },
  section: { marginTop: 8, gap: 10 },
  subsection: { marginTop: 10, gap: 10 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#111827" },
  card: {
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "white",
    gap: 6,
  },
  subcard: {
    borderWidth: 1,
    borderColor: "#F3F4F6",
    borderRadius: 12,
    padding: 10,
    backgroundColor: "#FAFAFA",
    gap: 4,
  },
  cardTitle: { fontWeight: "700", color: "#111827" },
  muted: { color: "#4B5563" },
  center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8, padding: 16 },
  centerText: { color: "#4B5563" },
});

