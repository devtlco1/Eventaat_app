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
import { Ionicons } from "@expo/vector-icons";
import type { ExploreStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { LoadingState } from "../components/LoadingState";
import { TextField } from "../components/TextField";
import { DateTimeField, formatStartsAt } from "../components/DateTimeField";
import { createBooking, getRestaurant, listRestaurants } from "../api/endpoints";
import { getErrorMessage, getValidationErrors, isAuthError } from "../api/errors";
import { validateClientBranchAvailability } from "../booking/availabilityChecks";
import type {
  MobileBranch,
  MobileBranchBookingAvailability,
  MobileRestaurantDetails,
  MobileRestaurantListItem,
  MobileRestaurantTable,
  MobileSeatingArea,
} from "../api/types";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<ExploreStackParamList, "CreateBooking">;

function pickFirst<T>(arr: T[]): T | null {
  return arr.length ? arr[0] : null;
}

function bookingHoursLabel(avail: MobileBranchBookingAvailability | null): string | null {
  if (!avail?.open_time && !avail?.close_time) return null;
  const o = avail?.open_time ? avail.open_time.slice(0, 5) : "—";
  const c = avail?.close_time ? avail.close_time.slice(0, 5) : "—";
  return `${o} – ${c}`;
}

// ── Small chip component ──────────────────────────────────────────────────────

function Chip({
  label,
  selected,
  onPress,
  small,
}: {
  label: string;
  selected: boolean;
  onPress: () => void;
  small?: boolean;
}) {
  return (
    <Pressable
      style={[styles.chip, selected && styles.chipSelected, small && styles.chipSmall]}
      onPress={onPress}
      android_ripple={{ color: colors.surface }}
    >
      <Text style={[styles.chipText, selected && styles.chipTextSelected, small && styles.chipTextSmall]}>
        {label}
      </Text>
    </Pressable>
  );
}

// ── Party size stepper ────────────────────────────────────────────────────────

function PartyStepper({
  value,
  onChange,
  error,
}: {
  value: number;
  onChange: (n: number) => void;
  error?: string | null;
}) {
  const decrement = () => onChange(Math.max(1, value - 1));
  const increment = () => onChange(Math.min(20, value + 1));

  return (
    <View style={styles.stepperWrapper}>
      <Text style={styles.stepperLabel}>Party size</Text>
      <View style={styles.stepperRow}>
        <Pressable
          style={[styles.stepBtn, value <= 1 && styles.stepBtnDisabled]}
          onPress={decrement}
          disabled={value <= 1}
        >
          <Ionicons name="remove" size={20} color={value <= 1 ? colors.textMuted : colors.text} />
        </Pressable>
        <Text style={styles.stepValue}>{value}</Text>
        <Pressable
          style={[styles.stepBtn, value >= 20 && styles.stepBtnDisabled]}
          onPress={increment}
          disabled={value >= 20}
        >
          <Ionicons name="add" size={20} color={value >= 20 ? colors.textMuted : colors.text} />
        </Pressable>
      </View>
      {error ? <Text style={styles.fieldError}>{error}</Text> : null}
    </View>
  );
}

// ── Main screen ───────────────────────────────────────────────────────────────

export function CreateBookingScreen({ route, navigation }: Props) {
  const { token, logout } = useAuth();
  const restaurantSlug = route.params.restaurantSlug;
  const preselected = !!restaurantSlug;

  const [restaurants, setRestaurants] = useState<MobileRestaurantListItem[]>([]);
  const [restaurant, setRestaurant] = useState<MobileRestaurantListItem | null>(null);
  const [details, setDetails] = useState<MobileRestaurantDetails | null>(null);

  const [branch, setBranch] = useState<MobileBranch | null>(null);
  const [seatingArea, setSeatingArea] = useState<MobileSeatingArea | null>(null);
  const [table, setTable] = useState<MobileRestaurantTable | null>(null);

  const [startsAt, setStartsAt] = useState<Date | null>(null);
  const [partySize, setPartySize] = useState(2);
  const [customerNote, setCustomerNote] = useState("");

  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  const branches = useMemo(() => details?.branches ?? [], [details]);
  const seatingAreas = useMemo(() => branch?.seating_areas ?? [], [branch]);
  const tables = useMemo(() => seatingArea?.tables ?? [], [seatingArea]);
  const multipleBranches = branches.length > 1;

  // ── Loaders ───────────────────────────────────────────────────────────────

  const loadRestaurantDetails = useCallback(
    async (slug: string) => {
      if (!token) return;
      setIsLoading(true);
      setError(null);
      try {
        const res = await getRestaurant(token, slug);
        setDetails(res);
        const first = pickFirst(res.branches ?? []);
        setBranch(first);
        setSeatingArea(null); // no preference by default
        setTable(null);
      } catch (e) {
        if (isAuthError(e)) { await logout(); return; }
        setError(getErrorMessage(e));
      } finally {
        setIsLoading(false);
      }
    },
    [logout, token],
  );

  const loadRestaurants = useCallback(async () => {
    if (!token) return;
    setIsLoading(true);
    setError(null);
    try {
      const res = await listRestaurants(token);
      const list = res.data ?? [];
      setRestaurants(list);
      setRestaurant(pickFirst(list));
    } catch (e) {
      if (isAuthError(e)) { await logout(); return; }
      setError(getErrorMessage(e));
    } finally {
      setIsLoading(false);
    }
  }, [logout, token]);

  useEffect(() => {
    if (preselected) void loadRestaurantDetails(restaurantSlug!);
    else void loadRestaurants();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // When user picks from selector (non-preselected), load that restaurant's details
  useEffect(() => {
    if (preselected || !restaurant?.slug) return;
    setDetails(null); setBranch(null); setSeatingArea(null); setTable(null);
    void loadRestaurantDetails(restaurant.slug);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [restaurant?.slug]);

  // ── Branch / seating handlers ─────────────────────────────────────────────

  const onSelectBranch = (b: MobileBranch) => {
    setBranch(b);
    setSeatingArea(null);
    setTable(null);
  };

  const onSelectSeatingArea = (sa: MobileSeatingArea | null) => {
    setSeatingArea(sa);
    setTable(null);
  };

  // ── Validation ────────────────────────────────────────────────────────────

  const validate = (): boolean => {
    const errs: Record<string, string> = {};
    if (!preselected && !restaurant) errs.restaurant_id = "Select a restaurant.";
    if (!branch) errs.branch_id = "Branch is required.";
    if (branch?.booking_availability && !branch.booking_availability.is_booking_enabled) {
      errs.branch_id = "Booking is currently disabled for this branch.";
    }
    if (!startsAt) {
      errs.starts_at = "Select date and time.";
    } else if (branch?.booking_availability?.is_booking_enabled) {
      const r = validateClientBranchAvailability(branch.booking_availability, startsAt);
      if (!r.ok) errs.starts_at = r.message;
    }
    if (partySize < 1) errs.party_size = "Party size must be at least 1.";
    setFieldErrors(errs);
    return Object.keys(errs).length === 0;
  };

  // ── Submit ────────────────────────────────────────────────────────────────

  const resolvedId = preselected ? (details?.id ?? null) : (restaurant?.id ?? null);
  const resolvedName = preselected ? (details?.name ?? restaurantSlug) : (restaurant?.name ?? null);

  const onSubmit = async () => {
    if (!token) return;
    setError(null);
    setFieldErrors({});
    if (!validate() || !resolvedId || !branch || !startsAt) return;

    setIsSubmitting(true);
    try {
      const res = await createBooking(token, {
        restaurant_id: resolvedId,
        branch_id: branch.id,
        seating_area_id: seatingArea?.id ?? null,
        restaurant_table_id: table?.id ?? null,
        starts_at: formatStartsAt(startsAt),
        party_size: partySize,
        customer_note: customerNote.trim() || null,
      });
      Alert.alert("Booking submitted", "Your booking has been created.");
      navigation.replace("BookingDetails", { bookingId: res.booking.id });
    } catch (e) {
      if (isAuthError(e)) { await logout(); return; }
      const errs = getValidationErrors(e);
      if (errs) {
        const mapped: Record<string, string> = {};
        for (const [k, v] of Object.entries(errs)) mapped[k] = v?.[0] ?? "Invalid.";
        setFieldErrors(mapped);
        setError(
          mapped.restaurant_table_id ?? mapped.party_size ??
          mapped.starts_at ?? mapped.branch_id ??
          mapped.restaurant_id ?? "Please fix the highlighted fields.",
        );
      } else {
        setError(getErrorMessage(e));
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // ── Loading state ─────────────────────────────────────────────────────────

  if (isLoading) {
    return <LoadingState message={preselected ? "Loading restaurant…" : "Loading restaurants…"} />;
  }

  // ── Render helpers ────────────────────────────────────────────────────────

  const hours = branch ? bookingHoursLabel(branch.booking_availability ?? null) : null;
  const bookingUnavailable =
    branch?.booking_availability?.is_booking_enabled === false;

  // ── Render ────────────────────────────────────────────────────────────────

  return (
    <ScrollView
      style={styles.scroll}
      contentContainerStyle={styles.container}
      showsVerticalScrollIndicator={false}
      keyboardShouldPersistTaps="handled"
    >
      <ErrorBanner message={error} />

      {/* ── Restaurant summary ── */}
      {preselected ? (
        <View style={styles.restaurantHeader}>
          <Text style={styles.restaurantName}>{resolvedName}</Text>
          {branch && (
            <Text style={styles.restaurantMeta}>
              {branch.name}
              {hours ? ` · ${hours}` : ""}
            </Text>
          )}
          {bookingUnavailable ? (
            <View style={styles.unavailableTag}>
              <Text style={styles.unavailableText}>Booking unavailable for this branch</Text>
            </View>
          ) : null}
        </View>
      ) : (
        /* Restaurant selector (non-preselected path) */
        <View style={styles.section}>
          <Text style={styles.sectionLabel}>Restaurant</Text>
          {restaurants.length === 0 ? (
            <Text style={styles.muted}>No restaurants available.</Text>
          ) : (
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
              {restaurants.map((r) => (
                <Chip
                  key={r.id}
                  label={r.name}
                  selected={restaurant?.id === r.id}
                  onPress={() => setRestaurant(r)}
                />
              ))}
            </ScrollView>
          )}
          {fieldErrors.restaurant_id ? (
            <Text style={styles.fieldError}>{fieldErrors.restaurant_id}</Text>
          ) : null}
        </View>
      )}

      {/* ── Branch selector (only when multiple) ── */}
      {multipleBranches && (
        <View style={styles.section}>
          <Text style={styles.sectionLabel}>Branch</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
            {branches.map((b) => (
              <Chip
                key={b.id}
                label={b.name}
                selected={branch?.id === b.id}
                onPress={() => onSelectBranch(b)}
              />
            ))}
          </ScrollView>
          {fieldErrors.branch_id ? (
            <Text style={styles.fieldError}>{fieldErrors.branch_id}</Text>
          ) : null}
          {branch && hours && (
            <Text style={styles.hoursLine}>
              <Ionicons name="time-outline" size={13} color={colors.textSecondary} />
              {" "}Booking hours: {hours}
            </Text>
          )}
        </View>
      )}

      {/* ── Date & time ── */}
      <DateTimeField
        value={startsAt}
        onChange={setStartsAt}
        error={fieldErrors.starts_at ?? null}
      />

      {/* ── Party size stepper ── */}
      <PartyStepper
        value={partySize}
        onChange={setPartySize}
        error={fieldErrors.party_size ?? null}
      />

      {/* ── Seating area chips ── */}
      {seatingAreas.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionLabel}>Seating area <Text style={styles.optionalTag}>(optional)</Text></Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
            <Chip label="No preference" selected={!seatingArea} onPress={() => onSelectSeatingArea(null)} />
            {seatingAreas.map((sa) => (
              <Chip
                key={sa.id}
                label={sa.name + (sa.type ? ` · ${sa.type}` : "")}
                selected={seatingArea?.id === sa.id}
                onPress={() => onSelectSeatingArea(sa)}
              />
            ))}
          </ScrollView>
        </View>
      )}

      {/* ── Table chips (only if seating area selected and has tables) ── */}
      {seatingArea && tables.length > 0 && (
        <View style={styles.section}>
          <Text style={styles.sectionLabel}>Table <Text style={styles.optionalTag}>(optional)</Text></Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
            <Chip label="No specific table" selected={!table} onPress={() => setTable(null)} small />
            {tables.map((t) => (
              <Chip
                key={t.id}
                label={`${t.label} (${t.capacity})`}
                selected={table?.id === t.id}
                onPress={() => setTable(t)}
                small
              />
            ))}
          </ScrollView>
          {fieldErrors.restaurant_table_id ? (
            <Text style={styles.fieldError}>{fieldErrors.restaurant_table_id}</Text>
          ) : null}
        </View>
      )}

      {/* ── Note ── */}
      <TextField
        label="Note (optional)"
        value={customerNote}
        onChangeText={setCustomerNote}
        placeholder="Any special requests"
        autoCapitalize="sentences"
      />

      {/* ── Submit ── */}
      <Button
        title="Create booking"
        onPress={onSubmit}
        loading={isSubmitting}
        disabled={isSubmitting}
      />
    </ScrollView>
  );
}

// ── Styles ────────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  scroll: { flex: 1, backgroundColor: colors.surface },
  container: {
    padding: spacing.lg,
    gap: spacing.lg,
    paddingBottom: 120,
  },

  // Restaurant header
  restaurantHeader: { gap: 3 },
  restaurantName: { ...typography.xl, fontWeight: "800", color: colors.text },
  restaurantMeta: { ...typography.sm, color: colors.textSecondary },
  unavailableTag: {
    marginTop: spacing.xs,
    alignSelf: "flex-start",
    paddingHorizontal: spacing.sm,
    paddingVertical: 3,
    backgroundColor: colors.warningBg,
    borderRadius: radii.xs,
  },
  unavailableText: { ...typography.xs, color: colors.warning, fontWeight: "600" },

  // Sections
  section: { gap: spacing.xs },
  sectionLabel: { ...typography.base, fontWeight: "600", color: colors.text },
  optionalTag: { fontWeight: "400", color: colors.textMuted },
  hoursLine: { ...typography.xs, color: colors.textSecondary, marginTop: 2 },

  // Chips
  chipScroll: { marginTop: 2 },
  chip: {
    paddingHorizontal: spacing.md,
    paddingVertical: spacing.sm,
    borderRadius: radii.full,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.background,
    marginRight: spacing.xs,
  },
  chipSelected: {
    borderColor: colors.primary,
    backgroundColor: colors.primary,
  },
  chipSmall: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 5,
  },
  chipText: { ...typography.sm, color: colors.text, fontWeight: "500" },
  chipTextSelected: { color: colors.onPrimary, fontWeight: "600" },
  chipTextSmall: { ...typography.xs },

  // Party stepper
  stepperWrapper: { gap: 6 },
  stepperLabel: { ...typography.base, fontWeight: "600", color: colors.text },
  stepperRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.md,
  },
  stepBtn: {
    width: 40,
    height: 40,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.background,
    alignItems: "center",
    justifyContent: "center",
  },
  stepBtnDisabled: {
    borderColor: colors.border,
    backgroundColor: colors.surface,
  },
  stepValue: { ...typography.xl, fontWeight: "700", color: colors.text, minWidth: 32, textAlign: "center" },

  // Errors
  fieldError: { ...typography.sm, color: colors.danger },
  muted: { ...typography.sm, color: colors.textSecondary },
});
