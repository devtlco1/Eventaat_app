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
import type { MobileBranch, MobileRestaurantDetails } from "../api/types";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<ExploreStackParamList, "RestaurantDetails">;

/** Returns a human-friendly booking hours string, or null if not available. */
function bookingHours(branch: MobileBranch): string | null {
  const avail = branch.booking_availability;
  if (!avail || !avail.is_booking_enabled) return null;
  const { open_time, close_time } = avail;
  if (!open_time && !close_time) return null;
  const o = open_time ? open_time.slice(0, 5) : "—";
  const c = close_time ? close_time.slice(0, 5) : "—";
  return `${o} – ${c}`;
}

function tableCount(branch: MobileBranch): number {
  return (branch.seating_areas ?? []).reduce(
    (sum, sa) => sum + (sa.tables ?? []).length,
    0,
  );
}

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

      <View style={styles.header}>
        <Text style={styles.title}>{data?.name ?? "Restaurant"}</Text>
        {branches.length > 0 && (
          <Text style={styles.subtitle}>
            {branches.length} location{branches.length !== 1 ? "s" : ""}
          </Text>
        )}
      </View>

      <Button
        title="Book a table"
        onPress={() => navigation.navigate("CreateBooking", { restaurantSlug: slug })}
      />

      {branches.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Locations</Text>
          {branches.map((b) => {
            const hours = bookingHours(b);
            const tables = tableCount(b);
            const unavailable =
              b.booking_availability !== null &&
              b.booking_availability?.is_booking_enabled === false;

            return (
              <Card key={b.id} style={styles.branchCard}>
                <Text style={styles.cardTitle}>{b.name}</Text>

                {unavailable ? (
                  <View style={styles.tag}>
                    <Text style={styles.tagText}>Booking unavailable</Text>
                  </View>
                ) : (
                  <View style={styles.metaRow}>
                    {hours ? (
                      <View style={styles.pill}>
                        <Text style={styles.pillText}>🕐 {hours}</Text>
                      </View>
                    ) : null}
                    {tables > 0 ? (
                      <View style={styles.pill}>
                        <Text style={styles.pillText}>
                          🪑 {tables} table{tables !== 1 ? "s" : ""}
                        </Text>
                      </View>
                    ) : null}
                  </View>
                )}

                {(b.seating_areas ?? []).length > 0 && (
                  <View style={styles.areas}>
                    {b.seating_areas.map((sa) => (
                      <Text key={sa.id} style={styles.areaChip}>
                        {sa.name}
                        {sa.type ? ` · ${sa.type}` : ""}
                      </Text>
                    ))}
                  </View>
                )}
              </Card>
            );
          })}
        </View>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: colors.surface },
  container: { padding: spacing.lg, gap: spacing.lg, paddingBottom: 100 },
  header: { gap: 4 },
  title: { ...typography.xxl, fontWeight: "800", color: colors.text },
  subtitle: { ...typography.base, color: colors.textSecondary },
  section: { gap: spacing.sm },
  sectionTitle: { ...typography.md, fontWeight: "700", color: colors.text },
  branchCard: { gap: spacing.sm },
  cardTitle: { ...typography.base, fontWeight: "700", color: colors.text },
  metaRow: { flexDirection: "row", flexWrap: "wrap", gap: spacing.xs },
  pill: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    backgroundColor: colors.surface,
    borderRadius: radii.full,
    borderWidth: 1,
    borderColor: colors.border,
  },
  pillText: { ...typography.xs, color: colors.textSecondary },
  tag: {
    alignSelf: "flex-start",
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    backgroundColor: colors.warningBg,
    borderRadius: radii.xs,
  },
  tagText: { ...typography.xs, color: colors.warning, fontWeight: "600" },
  areas: { flexDirection: "row", flexWrap: "wrap", gap: spacing.xs },
  areaChip: {
    ...typography.xs,
    color: colors.textSecondary,
    backgroundColor: colors.neutralBg,
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    borderRadius: radii.full,
  },
});
