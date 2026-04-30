import React, { useCallback, useEffect, useMemo, useState } from "react";
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, View } from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { cancelBooking, getBooking } from "../api/endpoints";
import { getErrorMessage, isAuthError, getValidationErrors } from "../api/errors";
import type { MobileBooking } from "../api/types";

type Props = NativeStackScreenProps<RootStackParamList, "BookingDetails">;

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

function fmt(iso: string | null): string {
  if (!iso) return "-";
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return iso;
  }
}

export function BookingDetailsScreen({ route, navigation }: Props) {
  const { bookingId } = route.params;
  const { token, logout } = useAuth();

  const [data, setData] = useState<MobileBooking | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isCancelling, setIsCancelling] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) return;
    setIsLoading(true);
    setError(null);
    try {
      const res = await getBooking(token, bookingId);
      setData(res);
      navigation.setOptions({ title: `Booking #${res.id}` });
    } catch (e) {
      if (isAuthError(e)) {
        await logout();
        return;
      }
      setError(getErrorMessage(e));
    } finally {
      setIsLoading(false);
    }
  }, [bookingId, logout, navigation, token]);

  useEffect(() => {
    void load();
  }, [load]);

  const canAttemptCancel = useMemo(() => {
    const s = data?.status ?? null;
    return s === "pending" || s === "accepted" || s === "arrived";
  }, [data?.status]);

  const onCancel = async () => {
    if (!token || !data) return;
    Alert.alert("Cancel booking", "Are you sure you want to cancel this booking?", [
      { text: "No", style: "cancel" },
      {
        text: "Yes, cancel",
        style: "destructive",
        onPress: async () => {
          setIsCancelling(true);
          setError(null);
          try {
            await cancelBooking(token, data.id);
            await load();
            Alert.alert("Cancelled", "Booking cancelled.");
          } catch (e) {
            if (isAuthError(e)) {
              await logout();
              return;
            }
            const errors = getValidationErrors(e);
            const statusErr = errors?.status?.[0] ?? null;
            setError(statusErr ?? getErrorMessage(e));
          } finally {
            setIsCancelling(false);
          }
        },
      },
    ]);
  };

  if (isLoading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
        <Text style={styles.centerText}>Loading booking…</Text>
      </View>
    );
  }

  if (!data) {
    return (
      <View style={styles.center}>
        <ErrorBanner message={error ?? "Booking not found."} />
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <ErrorBanner message={error} />

      <Text style={styles.title}>Booking #{data.id}</Text>
      <Text style={styles.subtitle}>Status: {statusLabel(data.status)}</Text>

      <View style={styles.card}>
        <Text style={styles.row}>
          <Text style={styles.k}>Restaurant: </Text>
          <Text style={styles.v}>{data.restaurant?.name ?? "-"}</Text>
        </Text>
        <Text style={styles.row}>
          <Text style={styles.k}>Branch: </Text>
          <Text style={styles.v}>{data.branch?.name ?? "-"}</Text>
        </Text>
        <Text style={styles.row}>
          <Text style={styles.k}>Starts at: </Text>
          <Text style={styles.v}>{fmt(data.starts_at)}</Text>
        </Text>
        <Text style={styles.row}>
          <Text style={styles.k}>Party size: </Text>
          <Text style={styles.v}>{data.party_size}</Text>
        </Text>
        <Text style={styles.row}>
          <Text style={styles.k}>Seating area: </Text>
          <Text style={styles.v}>{data.seating_area?.name ?? "-"}</Text>
        </Text>
        <Text style={styles.row}>
          <Text style={styles.k}>Table: </Text>
          <Text style={styles.v}>{data.table?.label ?? "-"}</Text>
        </Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Timeline</Text>
        <Text style={styles.muted}>Accepted: {fmt(data.accepted_at)}</Text>
        <Text style={styles.muted}>Arrived: {fmt(data.arrived_at)}</Text>
        <Text style={styles.muted}>Seated: {fmt(data.seated_at)}</Text>
        <Text style={styles.muted}>Completed: {fmt(data.completed_at)}</Text>
        <Text style={styles.muted}>No-show: {fmt(data.no_show_at)}</Text>
        <Text style={styles.muted}>Rejected: {fmt(data.rejected_at)}</Text>
        <Text style={styles.muted}>Cancelled: {fmt(data.cancelled_at)}</Text>
      </View>

      <PrimaryButton
        title={isCancelling ? "Cancelling..." : "Cancel booking"}
        onPress={onCancel}
        disabled={!canAttemptCancel || isCancelling}
      />
      {!canAttemptCancel ? (
        <Text style={styles.muted}>Cancellation is only allowed for pending/accepted/arrived bookings.</Text>
      ) : null}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16, gap: 12 },
  title: { fontSize: 20, fontWeight: "800", color: "#111827" },
  subtitle: { color: "#4B5563" },
  section: { gap: 6 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#111827" },
  card: {
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "white",
    gap: 6,
  },
  row: { color: "#111827" },
  k: { fontWeight: "700" },
  v: { color: "#111827" },
  muted: { color: "#4B5563" },
  center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8, padding: 16 },
  centerText: { color: "#4B5563" },
});

