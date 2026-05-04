import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { ExploreStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { LoadingState } from "../components/LoadingState";
import { TextField } from "../components/TextField";
import { createBooking, getRestaurant, listRestaurants } from "../api/endpoints";
import { getErrorMessage, getValidationErrors, isAuthError } from "../api/errors";
import { DateTimeField, formatStartsAt } from "../components/DateTimeField";
import type {
  MobileBranch,
  MobileRestaurantDetails,
  MobileRestaurantListItem,
  MobileRestaurantTable,
  MobileSeatingArea,
} from "../api/types";
import {
  formatBookingAvailabilitySummary,
  validateClientBranchAvailability,
} from "../booking/availabilityChecks";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<ExploreStackParamList, "CreateBooking">;

function pickFirst<T>(arr: T[]): T | null {
  return arr.length ? arr[0] : null;
}

function ChoiceTile({
  title,
  subtitle,
  selected,
  onPress,
}: {
  title: string;
  subtitle?: string;
  selected: boolean;
  onPress: () => void;
}) {
  return (
    <Pressable
      style={[styles.choice, selected && styles.choiceSelected]}
      onPress={onPress}
    >
      <Text style={styles.choiceTitle}>{title}</Text>
      {subtitle ? <Text style={styles.muted}>{subtitle}</Text> : null}
    </Pressable>
  );
}

export function CreateBookingScreen({ route, navigation }: Props) {
  const { token, logout } = useAuth();
  const restaurantSlug = route.params.restaurantSlug;

  const [restaurants, setRestaurants] = useState<MobileRestaurantListItem[]>([]);
  const [restaurant, setRestaurant] = useState<MobileRestaurantListItem | null>(null);
  const [details, setDetails] = useState<MobileRestaurantDetails | null>(null);

  const [branch, setBranch] = useState<MobileBranch | null>(null);
  const [seatingArea, setSeatingArea] = useState<MobileSeatingArea | null>(null);
  const [table, setTable] = useState<MobileRestaurantTable | null>(null);

  const [startsAtDate, setStartsAtDate] = useState<Date | null>(null);
  const [partySize, setPartySize] = useState("2");
  const [customerNote, setCustomerNote] = useState("");

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const branches = useMemo(() => details?.branches ?? [], [details]);
  const seatingAreas = useMemo(() => branch?.seating_areas ?? [], [branch]);
  const tables = useMemo(() => seatingArea?.tables ?? [], [seatingArea]);

  const loadRestaurants = useCallback(async () => {
    if (!token) return;
    setIsLoading(true);
    setError(null);
    try {
      const res = await listRestaurants(token);
      const list = res.data ?? [];
      setRestaurants(list);

      let selected = restaurant;
      if (!selected && restaurantSlug) {
        selected = list.find((r) => r.slug === restaurantSlug) ?? null;
      }
      if (!selected) selected = pickFirst(list);
      setRestaurant(selected);
    } catch (e) {
      if (isAuthError(e)) {
        await logout();
        return;
      }
      setError(getErrorMessage(e));
    } finally {
      setIsLoading(false);
    }
  }, [logout, restaurant, restaurantSlug, token]);

  const loadRestaurantDetails = useCallback(
    async (slug: string) => {
      if (!token) return;
      setIsLoading(true);
      setError(null);
      try {
        const res = await getRestaurant(token, slug);
        setDetails(res);

        const firstBranch = pickFirst(res.branches ?? []);
        setBranch(firstBranch);
        setSeatingArea(firstBranch ? pickFirst(firstBranch.seating_areas ?? []) : null);
        setTable(null);
      } catch (e) {
        if (isAuthError(e)) {
          await logout();
          return;
        }
        setError(getErrorMessage(e));
      } finally {
        setIsLoading(false);
      }
    },
    [logout, token]
  );

  useEffect(() => {
    void loadRestaurants();
  }, [loadRestaurants]);

  useEffect(() => {
    if (!restaurant?.slug) return;
    setDetails(null);
    setBranch(null);
    setSeatingArea(null);
    setTable(null);
    void loadRestaurantDetails(restaurant.slug);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [restaurant?.slug]);

  const onSelectBranch = (b: MobileBranch) => {
    setBranch(b);
    setSeatingArea(pickFirst(b.seating_areas ?? []));
    setTable(null);
  };

  const validateLocal = (): boolean => {
    const errs: Record<string, string> = {};
    if (!restaurant) errs.restaurant_id = "Restaurant is required.";
    if (!branch) errs.branch_id = "Branch is required.";
    if (branch?.booking_availability && !branch.booking_availability.is_booking_enabled) {
      errs.branch_id = "Booking is currently disabled for this branch.";
    }
    if (!startsAtDate) errs.starts_at = "Please pick a date and time.";
    if (startsAtDate && branch?.booking_availability?.is_booking_enabled) {
      const r = validateClientBranchAvailability(branch.booking_availability, startsAtDate);
      if (!r.ok) errs.starts_at = r.message;
    }
    const ps = Number(partySize);
    if (!partySize.trim() || Number.isNaN(ps) || ps < 1) {
      errs.party_size = "Party size must be at least 1.";
    }
    setFieldErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const onSubmit = async () => {
    if (!token) return;
    setError(null);
    setFieldErrors({});
    if (!validateLocal()) return;
    if (!restaurant || !branch || !startsAtDate) return;

    setIsSubmitting(true);
    try {
      const res = await createBooking(token, {
        restaurant_id: restaurant.id,
        branch_id: branch.id,
        seating_area_id: seatingArea?.id ?? null,
        restaurant_table_id: table?.id ?? null,
        starts_at: formatStartsAt(startsAtDate),
        party_size: Number(partySize),
        customer_note: customerNote.trim() ? customerNote.trim() : null,
      });

      Alert.alert("Booking created", "Your booking has been submitted.");
      navigation.replace("BookingDetails", { bookingId: res.booking.id });
    } catch (e) {
      if (isAuthError(e)) {
        await logout();
        return;
      }
      const errors = getValidationErrors(e);
      if (errors) {
        const mapped: Record<string, string> = {};
        for (const [k, v] of Object.entries(errors)) {
          mapped[k] = v?.[0] ?? "Invalid value.";
        }
        setFieldErrors(mapped);
        const top =
          mapped.restaurant_table_id ??
          mapped.party_size ??
          mapped.starts_at ??
          mapped.branch_id ??
          mapped.restaurant_id ??
          null;
        setError(top ?? "Please fix the highlighted fields.");
      } else {
        setError(getErrorMessage(e));
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  if (isLoading && restaurants.length === 0) {
    return <LoadingState message="Loading restaurants…" />;
  }

  return (
    <ScrollView
      style={styles.scroll}
      contentContainerStyle={styles.container}
      showsVerticalScrollIndicator={false}
    >
      <ErrorBanner message={error} />

      <Text style={styles.sectionTitle}>Restaurant</Text>
      {restaurants.length === 0 ? (
        <Text style={styles.muted}>No restaurants available.</Text>
      ) : (
        restaurants.map((r) => (
          <ChoiceTile
            key={r.id}
            title={r.name}
            subtitle={`${r.active_branches_count} branch${r.active_branches_count !== 1 ? "es" : ""}`}
            selected={restaurant?.id === r.id}
            onPress={() => setRestaurant(r)}
          />
        ))
      )}
      {fieldErrors.restaurant_id ? (
        <Text style={styles.fieldError}>{fieldErrors.restaurant_id}</Text>
      ) : null}

      <Text style={styles.sectionTitle}>Branch</Text>
      {branches.length === 0 ? (
        <Text style={styles.muted}>
          {details ? "No active branches." : "Loading branches…"}
        </Text>
      ) : (
        branches.map((b) => (
          <ChoiceTile
            key={b.id}
            title={b.name}
            selected={branch?.id === b.id}
            onPress={() => onSelectBranch(b)}
          />
        ))
      )}
      {fieldErrors.branch_id ? (
        <Text style={styles.fieldError}>{fieldErrors.branch_id}</Text>
      ) : null}

      {branch ? (
        <Card style={styles.availabilityCard}>
          <Text style={styles.subTitle}>Availability</Text>
          {formatBookingAvailabilitySummary(branch.booking_availability ?? null).map((line, idx) => (
            <Text key={idx} style={styles.muted}>
              {line}
            </Text>
          ))}
        </Card>
      ) : null}

      <Text style={styles.sectionTitle}>Seating area (optional)</Text>
      <ChoiceTile
        title="No preference"
        selected={!seatingArea}
        onPress={() => {
          setSeatingArea(null);
          setTable(null);
        }}
      />
      {seatingAreas.map((sa) => (
        <ChoiceTile
          key={sa.id}
          title={sa.name + (sa.type ? ` · ${sa.type}` : "")}
          subtitle={`${(sa.tables ?? []).length} table${(sa.tables ?? []).length !== 1 ? "s" : ""}`}
          selected={seatingArea?.id === sa.id}
          onPress={() => {
            setSeatingArea(sa);
            setTable(null);
          }}
        />
      ))}

      <Text style={styles.sectionTitle}>Table (optional)</Text>
      <ChoiceTile
        title="No specific table"
        selected={!table}
        onPress={() => setTable(null)}
      />
      {tables.map((t) => (
        <ChoiceTile
          key={t.id}
          title={`${t.label} · capacity ${t.capacity}`}
          selected={table?.id === t.id}
          onPress={() => setTable(t)}
        />
      ))}
      {fieldErrors.restaurant_table_id ? (
        <Text style={styles.fieldError}>{fieldErrors.restaurant_table_id}</Text>
      ) : null}

      <DateTimeField
        label="Date & time"
        value={startsAtDate}
        onChange={setStartsAtDate}
        error={fieldErrors.starts_at ?? null}
      />

      <TextField
        label="Party size"
        value={partySize}
        onChangeText={setPartySize}
        keyboardType="number-pad"
        placeholder="2"
        error={fieldErrors.party_size}
      />

      <TextField
        label="Note (optional)"
        value={customerNote}
        onChangeText={setCustomerNote}
        placeholder="Any special requests"
        autoCapitalize="sentences"
      />

      <Button
        title="Create booking"
        onPress={onSubmit}
        loading={isSubmitting}
        disabled={isSubmitting}
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: colors.surface },
  container: { padding: spacing.lg, gap: spacing.md, paddingBottom: spacing.xxl },
  sectionTitle: { ...typography.md, fontWeight: "700", color: colors.text },
  subTitle: { ...typography.sm, fontWeight: "700", color: colors.text },
  choice: {
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radii.input,
    padding: spacing.md,
    backgroundColor: colors.background,
    gap: 4,
  },
  choiceSelected: {
    borderColor: colors.primary,
    borderWidth: 1.5,
  },
  choiceTitle: { ...typography.base, fontWeight: "700", color: colors.text },
  availabilityCard: { gap: 6 },
  muted: { ...typography.sm, color: colors.textSecondary },
  fieldError: { ...typography.sm, color: colors.danger },
});
