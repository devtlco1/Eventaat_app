import React, { useCallback, useEffect, useMemo, useState } from "react";
import { Alert, ScrollView, StyleSheet, Text, View } from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { LoadingState } from "../components/LoadingState";
import { StatusBadge } from "../components/StatusBadge";
import { cancelBooking, getBooking } from "../api/endpoints";
import { getErrorMessage, isAuthError, getValidationErrors } from "../api/errors";
import type { MobileBooking } from "../api/types";

type Props = NativeStackScreenProps<RootStackParamList, "BookingDetails">;

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
    return <LoadingState message="Loading booking…" />;
  }

  if (!data) {
    return (
      <View style={styles.notFound}>
        <ErrorBanner message={error ?? "Booking not found."} />
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <ErrorBanner message={error} />

      <View style={styles.headerRow}>
        <View style={{ flex: 1 }}>
          <Text style={styles.title}>Booking #{data.id}</Text>
          <Text style={styles.subtitle}>{data.restaurant?.name ?? "-"}</Text>
        </View>
        <StatusBadge status={data.status} />
      </View>

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
        <View style={styles.timeline}>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Pending</Text>
            <Text style={styles.timelineVal}>{fmt(data.created_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Accepted</Text>
            <Text style={styles.timelineVal}>{fmt(data.accepted_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Arrived</Text>
            <Text style={styles.timelineVal}>{fmt(data.arrived_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Seated</Text>
            <Text style={styles.timelineVal}>{fmt(data.seated_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Completed</Text>
            <Text style={styles.timelineVal}>{fmt(data.completed_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>No-show</Text>
            <Text style={styles.timelineVal}>{fmt(data.no_show_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Cancelled</Text>
            <Text style={styles.timelineVal}>{fmt(data.cancelled_at)}</Text>
          </View>
          <View style={styles.timelineRow}>
            <Text style={styles.timelineKey}>Rejected</Text>
            <Text style={styles.timelineVal}>{fmt(data.rejected_at)}</Text>
          </View>
        </View>
      </View>

      {canAttemptCancel ? (
        <PrimaryButton
          title={isCancelling ? "Cancelling..." : "Cancel booking"}
          onPress={onCancel}
          disabled={isCancelling}
        />
      ) : (
        <Text style={styles.muted}>Cancellation is not available for this booking status.</Text>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16, gap: 12 },
  headerRow: { flexDirection: "row", justifyContent: "space-between", gap: 12, alignItems: "flex-start" },
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
  notFound: { flex: 1, padding: 16, justifyContent: "center" },
  timeline: { borderWidth: 1, borderColor: "#E5E7EB", borderRadius: 12, padding: 12, backgroundColor: "white", gap: 8 },
  timelineRow: { flexDirection: "row", justifyContent: "space-between", gap: 10 },
  timelineKey: { fontWeight: "700", color: "#111827" },
  timelineVal: { color: "#4B5563", textAlign: "right", flexShrink: 1 },
});

