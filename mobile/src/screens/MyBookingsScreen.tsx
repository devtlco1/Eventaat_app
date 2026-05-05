import React, { useCallback, useMemo, useState } from "react";
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { useFocusEffect, useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { BookingsStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { EmptyState } from "../components/EmptyState";
import { ErrorBanner } from "../components/ErrorBanner";
import { LoadingState } from "../components/LoadingState";
import { StatusBadge } from "../components/StatusBadge";
import { listMyBookings } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileBooking } from "../api/types";
import { colors, spacing, typography } from "../theme/tokens";

function parseMs(iso: string | null): number | null {
  if (!iso) return null;
  const ms = Date.parse(iso);
  return Number.isFinite(ms) ? ms : null;
}

const STATUS_RANK: Record<string, number> = {
  pending: 1,
  accepted: 2,
  arrived: 3,
  seated: 4,
  completed: 5,
  no_show: 6,
  cancelled: 7,
  rejected: 8,
};

function statusRank(status: string | null): number {
  if (!status) return 99;
  return STATUS_RANK[status] ?? 50;
}

export function MyBookingsScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<BookingsStackParamList>>();
  const { token, logout } = useAuth();

  const [items, setItems] = useState<MobileBooking[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(
    async (opts: { refreshing?: boolean } = {}) => {
      if (!token) return;
      if (opts.refreshing) setIsRefreshing(true);
      else setIsLoading(true);
      setError(null);

      try {
        const res = await listMyBookings(token);
        setItems(res.data ?? []);
      } catch (e) {
        if (isAuthError(e)) {
          await logout();
          return;
        }
        setError(getErrorMessage(e));
      } finally {
        setIsLoading(false);
        setIsRefreshing(false);
      }
    },
    [logout, token]
  );

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load])
  );

  const sorted = useMemo(() => {
    return [...items].sort((a, b) => {
      // Primary: status priority
      const rankDiff = statusRank(a.status) - statusRank(b.status);
      if (rankDiff !== 0) return rankDiff;
      // Secondary: starts_at ascending (upcoming first within same status)
      const ams = parseMs(a.starts_at) ?? 0;
      const bms = parseMs(b.starts_at) ?? 0;
      return ams - bms;
    });
  }, [items]);

  const renderItem = ({ item }: { item: MobileBooking }) => (
    <Card onPress={() => navigation.navigate("BookingDetails", { bookingId: item.id })}>
      <View style={styles.rowTop}>
        <View style={styles.flex}>
          <Text style={styles.name}>
            {item.restaurant?.name ?? "Restaurant"} — {item.branch?.name ?? "Branch"}
          </Text>
          <Text style={styles.meta}>
            {item.starts_at ? new Date(item.starts_at).toLocaleString() : "—"}
          </Text>
          <Text style={styles.meta}>Party of {item.party_size}</Text>
        </View>
        <StatusBadge status={item.status} />
      </View>
    </Card>
  );

  if (isLoading) {
    return <LoadingState message="Loading bookings…" />;
  }

  return (
    <View style={styles.container}>
      <ErrorBanner message={error} />

      {sorted.length === 0 ? (
        <EmptyState
          title="No bookings yet"
          subtitle="Head to the Explore tab to find a restaurant and book a table."
        />
      ) : (
        <FlatList
          data={sorted}
          keyExtractor={(b) => String(b.id)}
          renderItem={renderItem}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl refreshing={isRefreshing} onRefresh={() => load({ refreshing: true })} />
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.surface },
  list: { padding: spacing.lg, paddingBottom: 100, gap: spacing.sm },
  rowTop: { flexDirection: "row", gap: spacing.sm, alignItems: "flex-start" },
  flex: { flex: 1, gap: 4 },
  name: { ...typography.base, fontWeight: "700", color: colors.text },
  meta: { ...typography.sm, color: colors.textSecondary },
});
