import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ScrollView, StyleSheet, Text, View } from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { ExploreStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { LoadingState } from "../components/LoadingState";
import { getRestaurant } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileRestaurantDetails } from "../api/types";
import { formatBookingAvailabilitySummary } from "../booking/availabilityChecks";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<ExploreStackParamList, "RestaurantDetails">;

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
      navigation.setOptions({ title: res.name ?? "Restaurant" });
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
    return <LoadingState message="Loading restaurant…" />;
  }

  return (
    <ScrollView
      style={styles.scroll}
      contentContainerStyle={styles.container}
      showsVerticalScrollIndicator={false}
    >
      <ErrorBanner message={error} />

      <Text style={styles.title}>{data?.name ?? "Restaurant"}</Text>
      <Text style={styles.subtitle}>
        {branches.length} active branch{branches.length !== 1 ? "es" : ""}
      </Text>

      <View style={styles.infoBox}>
        <Text style={styles.infoText}>
          Table selection is optional — you can create a booking without choosing a table.
        </Text>
      </View>

      <Button
        title="Book a table"
        onPress={() => navigation.navigate("CreateBooking", { restaurantSlug: slug })}
      />

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Branches</Text>
        {branches.length === 0 ? (
          <Text style={styles.muted}>No active branches.</Text>
        ) : (
          branches.map((b) => (
            <Card key={b.id} style={styles.branchCard}>
              <Text style={styles.cardTitle}>{b.name}</Text>

              {formatBookingAvailabilitySummary(b.booking_availability ?? null).map(
                (line, idx) => (
                  <Text key={idx} style={styles.muted}>
                    {line}
                  </Text>
                )
              )}

              {(b.seating_areas ?? []).length > 0 && (
                <View style={styles.subsection}>
                  <Text style={styles.subTitle}>Seating areas</Text>
                  {b.seating_areas.map((sa) => (
                    <View key={sa.id} style={styles.subcard}>
                      <Text style={styles.cardTitle}>
                        {sa.name}
                        {sa.type ? ` · ${sa.type}` : ""}
                      </Text>
                      {(sa.tables ?? []).length === 0 ? (
                        <Text style={styles.muted}>No active tables.</Text>
                      ) : (
                        (sa.tables ?? []).map((t) => (
                          <View key={t.id} style={styles.tableRow}>
                            <Text style={styles.tableLabel}>{t.label}</Text>
                            <Text style={styles.muted}>capacity {t.capacity}</Text>
                          </View>
                        ))
                      )}
                    </View>
                  ))}
                </View>
              )}
            </Card>
          ))
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: colors.surface },
  container: { padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xxl },
  title: { ...typography.xl, fontWeight: "800", color: colors.text },
  subtitle: { ...typography.base, color: colors.textSecondary },
  infoBox: {
    backgroundColor: colors.infoBg,
    borderColor: colors.infoBorder,
    borderWidth: 1,
    padding: spacing.md,
    borderRadius: radii.input,
  },
  infoText: { ...typography.sm, color: colors.info },
  section: { gap: spacing.sm },
  subsection: { marginTop: spacing.sm, gap: spacing.sm },
  sectionTitle: { ...typography.md, fontWeight: "700", color: colors.text },
  subTitle: { ...typography.sm, fontWeight: "700", color: colors.text },
  branchCard: { gap: spacing.sm },
  subcard: {
    borderWidth: 1,
    borderColor: colors.surface,
    borderRadius: radii.sm,
    padding: spacing.sm,
    backgroundColor: colors.surface,
    gap: 4,
  },
  cardTitle: { ...typography.base, fontWeight: "700", color: colors.text },
  muted: { ...typography.sm, color: colors.textSecondary },
  tableRow: { flexDirection: "row", justifyContent: "space-between", gap: spacing.sm },
  tableLabel: { ...typography.sm, fontWeight: "600", color: colors.text },
});
