import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { EmptyState } from "../components/EmptyState";
import { LoadingState } from "../components/LoadingState";
import { StatusBadge } from "../components/StatusBadge";
import { listMyBookings } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileBooking } from "../api/types";

function formatBookingRow(b: MobileBooking): string {
  const r = b.restaurant?.name ?? "Restaurant";
  const br = b.branch?.name ?? "Branch";
  return `${r} — ${br}`;
}

function parseMs(iso: string | null): number | null {
  if (!iso) return null;
  const ms = Date.parse(iso);
  return Number.isFinite(ms) ? ms : null;
}

export function MyBookingsScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
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

  useEffect(() => {
    void load();
  }, [load]);

  const renderItem = ({ item }: { item: MobileBooking }) => (
    <TouchableOpacity
      style={styles.card}
      onPress={() => navigation.navigate("BookingDetails", { bookingId: item.id })}
    >
      <View style={styles.rowTop}>
        <Text style={styles.name}>{formatBookingRow(item)}</Text>
        <StatusBadge status={item.status} />
      </View>
      <Text style={styles.meta}>
        Starts at: {item.starts_at ? new Date(item.starts_at).toLocaleString() : "-"}
      </Text>
      <Text style={styles.meta}>Party size: {item.party_size}</Text>
    </TouchableOpacity>
  );

  const empty = useMemo(() => items.length === 0, [items.length]);

  const sorted = useMemo(() => {
    const now = Date.now();
    const arr = [...items];
    arr.sort((a, b) => {
      const ams = parseMs(a.starts_at);
      const bms = parseMs(b.starts_at);
      if (ams === null || bms === null) return 0;

      const aUpcoming = ams >= now;
      const bUpcoming = bms >= now;

      if (aUpcoming !== bUpcoming) return aUpcoming ? -1 : 1;
      return aUpcoming ? ams - bms : bms - ams;
    });
    return arr;
  }, [items]);

  if (isLoading) {
    return <LoadingState message="Loading bookings…" />;
  }

  return (
    <View style={styles.container}>
      <ErrorBanner message={error} />

      {empty ? (
        <EmptyState title="No bookings yet" subtitle="Create your first booking from Restaurants." />
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
  container: { flex: 1, padding: 16, gap: 12 },
  list: { paddingBottom: 24, gap: 10 },
  card: {
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "white",
    gap: 6,
  },
  rowTop: { flexDirection: "row", justifyContent: "space-between", gap: 10, alignItems: "flex-start" },
  name: { fontSize: 14, fontWeight: "700", color: "#111827", flex: 1 },
  meta: { color: "#4B5563" },
});

