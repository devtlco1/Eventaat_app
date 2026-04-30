import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
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
import { listMyBookings } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileBooking } from "../api/types";

function formatBookingRow(b: MobileBooking): string {
  const r = b.restaurant?.name ?? "Restaurant";
  const br = b.branch?.name ?? "Branch";
  return `${r} — ${br}`;
}

function statusLabel(status: string | null): string {
  if (!status) return "Unknown";
  const map: Record<string, string> = {
    pending: "Pending",
    accepted: "Accepted",
    rejected: "Rejected",
    cancelled: "Cancelled",
    arrived: "Arrived",
    seated: "Seated",
    completed: "Completed",
    no_show: "No-show",
  };
  return map[status] ?? status;
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
      <View style={styles.row}>
        <Text style={styles.name}>{formatBookingRow(item)}</Text>
        <Text style={styles.status}>{statusLabel(item.status)}</Text>
      </View>
      <Text style={styles.meta}>
        Starts at: {item.starts_at ? new Date(item.starts_at).toLocaleString() : "-"}
      </Text>
      <Text style={styles.meta}>Party size: {item.party_size}</Text>
    </TouchableOpacity>
  );

  const empty = useMemo(() => items.length === 0, [items.length]);

  if (isLoading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
        <Text style={styles.centerText}>Loading bookings…</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ErrorBanner message={error} />

      {empty ? (
        <View style={styles.center}>
          <Text style={styles.centerText}>No bookings yet.</Text>
        </View>
      ) : (
        <FlatList
          data={items}
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
  row: { flexDirection: "row", justifyContent: "space-between", gap: 10 },
  name: { fontSize: 14, fontWeight: "700", color: "#111827", flex: 1 },
  status: { color: "#111827", fontWeight: "700" },
  meta: { color: "#4B5563" },
  center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8, padding: 16 },
  centerText: { color: "#4B5563" },
});

