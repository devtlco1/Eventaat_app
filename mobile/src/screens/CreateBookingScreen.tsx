import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { TextField } from "../components/TextField";
import { createBooking, getRestaurant, listRestaurants } from "../api/endpoints";
import { getErrorMessage, getValidationErrors, isAuthError } from "../api/errors";
import type {
  MobileBranch,
  MobileRestaurantDetails,
  MobileRestaurantListItem,
  MobileSeatingArea,
  MobileRestaurantTable,
} from "../api/types";

type Props = NativeStackScreenProps<RootStackParamList, "CreateBooking">;

function pickFirst<T>(arr: T[]): T | null {
  return arr.length ? arr[0] : null;
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

  const [startsAt, setStartsAt] = useState("");
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
        navigation.setOptions({ title: `Create booking` });

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
    [logout, navigation, token]
  );

  useEffect(() => {
    void loadRestaurants();
  }, [loadRestaurants]);

  useEffect(() => {
    if (!restaurant?.slug) return;
    void loadRestaurantDetails(restaurant.slug);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [restaurant?.slug]);

  const onSelectBranch = (b: MobileBranch) => {
    setBranch(b);
    setSeatingArea(pickFirst(b.seating_areas ?? []));
    setTable(null);
  };

  const onSelectSeatingArea = (sa: MobileSeatingArea | null) => {
    setSeatingArea(sa);
    setTable(null);
  };

  const onSelectTable = (t: MobileRestaurantTable | null) => {
    setTable(t);
  };

  const validateLocal = (): boolean => {
    const errs: Record<string, string> = {};
    if (!restaurant) errs.restaurant_id = "Restaurant is required.";
    if (!branch) errs.branch_id = "Branch is required.";
    if (!startsAt.trim()) errs.starts_at = "Starts at is required (YYYY-MM-DD HH:mm).";
    if (startsAt.trim() && !/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(startsAt.trim())) {
      errs.starts_at = "Use format YYYY-MM-DD HH:mm.";
    }
    const ps = Number(partySize);
    if (!partySize.trim() || Number.isNaN(ps) || ps < 1) errs.party_size = "Party size must be at least 1.";
    setFieldErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const onSubmit = async () => {
    if (!token) return;
    setError(null);
    setFieldErrors({});
    if (!validateLocal()) return;
    if (!restaurant || !branch) return;

    setIsSubmitting(true);
    try {
      const res = await createBooking(token, {
        restaurant_id: restaurant.id,
        branch_id: branch.id,
        seating_area_id: seatingArea?.id ?? null,
        restaurant_table_id: table?.id ?? null,
        starts_at: startsAt.trim(),
        party_size: Number(partySize),
        customer_note: customerNote.trim() ? customerNote.trim() : null,
      });

      Alert.alert("Created", "Booking created.");
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
    return (
      <View style={styles.center}>
        <ActivityIndicator />
        <Text style={styles.centerText}>Loading…</Text>
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <ErrorBanner message={error} />

      <Text style={styles.sectionTitle}>Restaurant</Text>
      {restaurants.length === 0 ? (
        <Text style={styles.muted}>No restaurants available.</Text>
      ) : (
        restaurants.map((r) => (
          <TouchableOpacity
            key={r.id}
            style={[styles.choice, restaurant?.id === r.id ? styles.choiceSelected : null]}
            onPress={() => setRestaurant(r)}
          >
            <Text style={styles.choiceTitle}>{r.name}</Text>
            <Text style={styles.muted}>Active branches: {r.active_branches_count}</Text>
          </TouchableOpacity>
        ))
      )}
      {fieldErrors.restaurant_id ? <Text style={styles.fieldError}>{fieldErrors.restaurant_id}</Text> : null}

      <Text style={styles.sectionTitle}>Branch</Text>
      {branches.length === 0 ? (
        <Text style={styles.muted}>No active branches.</Text>
      ) : (
        branches.map((b) => (
          <TouchableOpacity
            key={b.id}
            style={[styles.choice, branch?.id === b.id ? styles.choiceSelected : null]}
            onPress={() => onSelectBranch(b)}
          >
            <Text style={styles.choiceTitle}>{b.name}</Text>
            <Text style={styles.muted}>Code: {b.code}</Text>
          </TouchableOpacity>
        ))
      )}
      {fieldErrors.branch_id ? <Text style={styles.fieldError}>{fieldErrors.branch_id}</Text> : null}

      <Text style={styles.sectionTitle}>Seating area (optional)</Text>
      <TouchableOpacity
        style={[styles.choice, !seatingArea ? styles.choiceSelected : null]}
        onPress={() => onSelectSeatingArea(null)}
      >
        <Text style={styles.choiceTitle}>No seating area</Text>
      </TouchableOpacity>
      {seatingAreas.map((sa) => (
        <TouchableOpacity
          key={sa.id}
          style={[styles.choice, seatingArea?.id === sa.id ? styles.choiceSelected : null]}
          onPress={() => onSelectSeatingArea(sa)}
        >
          <Text style={styles.choiceTitle}>
            {sa.name}
            {sa.type ? ` (${sa.type})` : ""}
          </Text>
          <Text style={styles.muted}>Tables: {(sa.tables ?? []).length}</Text>
        </TouchableOpacity>
      ))}

      <Text style={styles.sectionTitle}>Table (optional)</Text>
      <TouchableOpacity
        style={[styles.choice, !table ? styles.choiceSelected : null]}
        onPress={() => onSelectTable(null)}
      >
        <Text style={styles.choiceTitle}>No table</Text>
        <Text style={styles.muted}>Booking without a table is allowed.</Text>
      </TouchableOpacity>
      {tables.map((t) => (
        <TouchableOpacity
          key={t.id}
          style={[styles.choice, table?.id === t.id ? styles.choiceSelected : null]}
          onPress={() => onSelectTable(t)}
        >
          <Text style={styles.choiceTitle}>
            {t.label} (cap {t.capacity})
          </Text>
        </TouchableOpacity>
      ))}
      {fieldErrors.restaurant_table_id ? <Text style={styles.fieldError}>{fieldErrors.restaurant_table_id}</Text> : null}

      <TextField
        label="Starts at (YYYY-MM-DD HH:mm)"
        value={startsAt}
        onChangeText={setStartsAt}
        placeholder="2026-05-01 19:00"
      />
      {fieldErrors.starts_at ? <Text style={styles.fieldError}>{fieldErrors.starts_at}</Text> : null}

      <TextField
        label="Party size"
        value={partySize}
        onChangeText={setPartySize}
        keyboardType="number-pad"
        placeholder="2"
      />
      {fieldErrors.party_size ? <Text style={styles.fieldError}>{fieldErrors.party_size}</Text> : null}

      <TextField
        label="Customer note (optional)"
        value={customerNote}
        onChangeText={setCustomerNote}
        placeholder="Anything the restaurant should know"
        autoCapitalize="sentences"
      />

      <PrimaryButton
        title={isSubmitting ? "Submitting..." : "Create booking"}
        onPress={onSubmit}
        disabled={isSubmitting}
      />
      <Text style={styles.muted}>
        If you select a table, the API may reject the booking for conflicts/capacity.
      </Text>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 16, gap: 12 },
  sectionTitle: { fontSize: 16, fontWeight: "700", color: "#111827" },
  choice: {
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "white",
    gap: 4,
  },
  choiceSelected: {
    borderColor: "#111827",
  },
  choiceTitle: { fontWeight: "700", color: "#111827" },
  muted: { color: "#4B5563" },
  fieldError: { color: "#991B1B" },
  center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8, padding: 16 },
  centerText: { color: "#4B5563" },
});

