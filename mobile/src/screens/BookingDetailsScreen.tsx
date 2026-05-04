import React, { useCallback, useEffect, useMemo, useState } from "react";
import { Alert, ScrollView, StyleSheet, Text, View } from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";

import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { Button } from "../components/Button";
import { Divider } from "../components/Divider";
import { ErrorBanner } from "../components/ErrorBanner";
import { LoadingState } from "../components/LoadingState";
import { StatusBadge } from "../components/StatusBadge";
import { cancelBooking, getBooking } from "../api/endpoints";
import { getErrorMessage, isAuthError, getValidationErrors } from "../api/errors";
import type { MobileBooking } from "../api/types";
import { colors, spacing, typography } from "../theme/tokens";

type BookingDetailsRoute = { BookingDetails: { bookingId: number } };
type Props = NativeStackScreenProps<BookingDetailsRoute, "BookingDetails">;

function fmt(iso: string | null): string {
  if (!iso) return "—";
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return iso;
  }
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoKey}>{label}</Text>
      <Text style={styles.infoVal}>{value}</Text>
    </View>
  );
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
    Alert.alert(
      "Cancel booking",
      "Are you sure you want to cancel this booking?",
      [
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
              Alert.alert("Cancelled", "Your booking has been cancelled.");
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
      ]
    );
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
    <ScrollView
      style={styles.scroll}
      contentContainerStyle={styles.container}
      showsVerticalScrollIndicator={false}
    >
      <ErrorBanner message={error} />

      <View style={styles.headerRow}>
        <View style={styles.flex}>
          <Text style={styles.title}>Booking #{data.id}</Text>
          <Text style={styles.subtitle}>{data.restaurant?.name ?? "—"}</Text>
        </View>
        <StatusBadge status={data.status} />
      </View>

      <Card>
        <InfoRow label="Restaurant" value={data.restaurant?.name ?? "—"} />
        <InfoRow label="Branch" value={data.branch?.name ?? "—"} />
        <InfoRow label="Starts at" value={fmt(data.starts_at)} />
        <InfoRow label="Party size" value={String(data.party_size)} />
        <InfoRow label="Seating area" value={data.seating_area?.name ?? "—"} />
        <InfoRow label="Table" value={data.table?.label ?? "—"} />
      </Card>

      <Divider />

      <Text style={styles.sectionTitle}>Timeline</Text>
      <Card>
        <InfoRow label="Pending" value={fmt(data.created_at)} />
        <InfoRow label="Accepted" value={fmt(data.accepted_at)} />
        <InfoRow label="Arrived" value={fmt(data.arrived_at)} />
        <InfoRow label="Seated" value={fmt(data.seated_at)} />
        <InfoRow label="Completed" value={fmt(data.completed_at)} />
        <InfoRow label="No-show" value={fmt(data.no_show_at)} />
        <InfoRow label="Cancelled" value={fmt(data.cancelled_at)} />
        <InfoRow label="Rejected" value={fmt(data.rejected_at)} />
      </Card>

      {canAttemptCancel ? (
        <Button
          title={isCancelling ? "Cancelling…" : "Cancel booking"}
          onPress={onCancel}
          variant="danger"
          loading={isCancelling}
        />
      ) : (
        <Text style={styles.muted}>Cancellation is not available at this stage.</Text>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: colors.surface },
  container: { padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xxl },
  headerRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    gap: spacing.md,
    alignItems: "flex-start",
  },
  flex: { flex: 1, gap: 4 },
  title: { ...typography.xl, fontWeight: "800", color: colors.text },
  subtitle: { ...typography.base, color: colors.textSecondary },
  sectionTitle: { ...typography.md, fontWeight: "700", color: colors.text },
  infoRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    gap: spacing.sm,
  },
  infoKey: { ...typography.base, fontWeight: "600", color: colors.text },
  infoVal: { ...typography.base, color: colors.textSecondary, flexShrink: 1, textAlign: "right" },
  muted: { ...typography.sm, color: colors.textSecondary },
  notFound: { flex: 1, padding: spacing.lg, justifyContent: "center" },
});
