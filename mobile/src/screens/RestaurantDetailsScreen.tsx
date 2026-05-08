import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Linking,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { ExploreStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { LoadingState } from "../components/LoadingState";
import {
  getRestaurant,
  listRestaurantOffers,
  listRestaurantEvents,
  listRestaurantMenus,
  listRestaurantReviews,
} from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type {
  MobileBranch,
  MobileEvent,
  MobileMenu,
  MobileOffer,
  MobilePublicReview,
  MobileRestaurantDetails,
} from "../api/types";
import { colors, radii, spacing, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<ExploreStackParamList, "RestaurantDetails">;

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

function Pill({ label, accent }: { label: string; accent?: boolean }) {
  return (
    <View style={[styles.pill, accent && styles.pillAccent]}>
      <Text style={[styles.pillText, accent && styles.pillTextAccent]}>{label}</Text>
    </View>
  );
}

function StarRating({
  avg,
  count,
}: {
  avg: number | null;
  count: number;
}) {
  if (avg === null) {
    return <Text style={styles.ratingMuted}>No reviews yet</Text>;
  }
  const stars = "★".repeat(Math.round(avg)) + "☆".repeat(5 - Math.round(avg));
  return (
    <View style={styles.ratingRow}>
      <Text style={styles.ratingStars}>{stars}</Text>
      <Text style={styles.ratingLabel}>
        {avg.toFixed(1)} · {count} review{count !== 1 ? "s" : ""}
      </Text>
    </View>
  );
}

function BranchesSection({ branches }: { branches: MobileBranch[] }) {
  if (branches.length === 0) return null;
  return (
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
                {hours ? <Pill label={`🕐 ${hours}`} /> : null}
                {tables > 0 ? (
                  <Pill label={`🪑 ${tables} table${tables !== 1 ? "s" : ""}`} />
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
  );
}

function OffersSection({ offers }: { offers: MobileOffer[] }) {
  if (offers.length === 0) return null;
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Offers</Text>
      {offers.map((o) => (
        <Card key={o.id} style={styles.offerCard}>
          <View style={styles.offerHeader}>
            <Text style={styles.cardTitle}>{o.title}</Text>
            {o.discount_value ? (
              <Pill label={o.discount_value} accent />
            ) : null}
          </View>
          {o.description ? (
            <Text style={styles.bodyText}>{o.description}</Text>
          ) : null}
          {(o.starts_at || o.ends_at) && (
            <Text style={styles.mutedText}>
              {o.starts_at ? new Date(o.starts_at).toLocaleDateString() : ""}
              {o.starts_at && o.ends_at ? " – " : ""}
              {o.ends_at ? new Date(o.ends_at).toLocaleDateString() : ""}
            </Text>
          )}
        </Card>
      ))}
    </View>
  );
}

function EventsSection({
  events,
  onBook,
}: {
  events: MobileEvent[];
  onBook: (slug: string) => void;
}) {
  if (events.length === 0) return null;
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Events</Text>
      {events.map((ev) => (
        <Card key={ev.id} style={styles.eventCard}>
          <Text style={styles.cardTitle}>{ev.title}</Text>
          {ev.starts_at ? (
            <Text style={styles.mutedText}>
              {new Date(ev.starts_at).toLocaleString()}
            </Text>
          ) : null}
          <View style={styles.metaRow}>
            {ev.price_label ? <Pill label={ev.price_label} accent /> : null}
            {ev.remaining_seats !== null ? (
              <Pill label={`${ev.remaining_seats} seats left`} />
            ) : null}
          </View>
          {ev.description ? (
            <Text style={styles.bodyText} numberOfLines={2}>
              {ev.description}
            </Text>
          ) : null}
          <Button
            title="View event"
            onPress={() => onBook(ev.slug)}
            variant="outline"
          />
        </Card>
      ))}
    </View>
  );
}

function MenusSection({ menus }: { menus: MobileMenu[] }) {
  if (menus.length === 0) return null;
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Menus</Text>
      {menus.map((menu) => (
        <Card key={menu.id} style={styles.menuCard}>
          <Text style={styles.cardTitle}>{menu.title}</Text>
          <Pill label={menu.mode} />

          {menu.mode === "pdf" && menu.pdf_url ? (
            <TouchableOpacity
              onPress={() => Linking.openURL(menu.pdf_url!)}
              style={styles.linkBtn}
            >
              <Text style={styles.linkText}>Open PDF menu ↗</Text>
            </TouchableOpacity>
          ) : menu.mode === "external" && menu.external_url ? (
            <TouchableOpacity
              onPress={() => Linking.openURL(menu.external_url!)}
              style={styles.linkBtn}
            >
              <Text style={styles.linkText}>View menu online ↗</Text>
            </TouchableOpacity>
          ) : (
            menu.categories.map((cat) => (
              <View key={cat.id} style={styles.menuCategory}>
                <Text style={styles.menuCategoryTitle}>{cat.name}</Text>
                {cat.items.map((item) => (
                  <View key={item.id} style={styles.menuItem}>
                    <View style={styles.menuItemLeft}>
                      <Text style={styles.menuItemName}>
                        {item.name}
                        {item.is_featured ? " ✦" : ""}
                      </Text>
                      {item.description ? (
                        <Text style={styles.menuItemDesc} numberOfLines={2}>
                          {item.description}
                        </Text>
                      ) : null}
                    </View>
                    {item.price ? (
                      <Text style={styles.menuItemPrice}>
                        {item.price} {item.currency}
                      </Text>
                    ) : null}
                  </View>
                ))}
              </View>
            ))
          )}
        </Card>
      ))}
    </View>
  );
}

function ReviewsSection({ reviews }: { reviews: MobilePublicReview[] }) {
  if (reviews.length === 0) {
    return (
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Reviews</Text>
        <Text style={styles.mutedText}>No reviews yet.</Text>
      </View>
    );
  }
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Reviews</Text>
      {reviews.map((r) => (
        <Card key={r.id} style={styles.reviewCard}>
          <View style={styles.reviewHeader}>
            <Text style={styles.reviewerName}>{r.customer_name}</Text>
            <Text style={styles.reviewStars}>
              {"★".repeat(r.rating)}
              {"☆".repeat(5 - r.rating)}
            </Text>
          </View>
          {r.comment ? (
            <Text style={styles.bodyText}>{r.comment}</Text>
          ) : null}
          {r.created_at ? (
            <Text style={styles.mutedText}>
              {new Date(r.created_at).toLocaleDateString()}
            </Text>
          ) : null}
        </Card>
      ))}
    </View>
  );
}

export function RestaurantDetailsScreen({ route, navigation }: Props) {
  const { slug } = route.params;
  const { token, logout } = useAuth();

  const [data, setData] = useState<MobileRestaurantDetails | null>(null);
  const [offers, setOffers] = useState<MobileOffer[]>([]);
  const [events, setEvents] = useState<MobileEvent[]>([]);
  const [menus, setMenus] = useState<MobileMenu[]>([]);
  const [reviews, setReviews] = useState<MobilePublicReview[]>([]);

  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) return;
    setIsLoading(true);
    setError(null);
    try {
      const [restaurant, offersRes, eventsRes, menusRes, reviewsRes] =
        await Promise.all([
          getRestaurant(token, slug),
          listRestaurantOffers(token, slug).catch(() => ({ data: [] as MobileOffer[] })),
          listRestaurantEvents(token, slug).catch(() => ({ data: [] as MobileEvent[] })),
          listRestaurantMenus(token, slug).catch(() => ({ data: [] as MobileMenu[] })),
          listRestaurantReviews(token, slug).catch(() => ({ data: [] as MobilePublicReview[] })),
        ]);

      setData(restaurant);
      setOffers(offersRes.data);
      setEvents(eventsRes.data);
      setMenus(menusRes.data);
      setReviews(reviewsRes.data);
      navigation.setOptions({ title: restaurant.name ?? "Restaurant" });
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
        <StarRating avg={data?.avg_rating ?? null} count={data?.review_count ?? 0} />
      </View>

      <Button
        title="Book a table"
        onPress={() => navigation.navigate("CreateBooking", { restaurantSlug: slug })}
      />

      <BranchesSection branches={branches} />
      <OffersSection offers={offers} />
      <EventsSection
        events={events}
        onBook={(eventSlug) => {
          // Navigate to event detail when that screen exists
          void Linking.openURL(`/events/${eventSlug}`);
        }}
      />
      <MenusSection menus={menus} />
      <ReviewsSection reviews={reviews} />
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
  offerCard: { gap: spacing.sm },
  eventCard: { gap: spacing.sm },
  menuCard: { gap: spacing.sm },
  reviewCard: { gap: spacing.xs },
  cardTitle: { ...typography.base, fontWeight: "700", color: colors.text },
  bodyText: { ...typography.sm, color: colors.textSecondary, lineHeight: 20 },
  mutedText: { ...typography.xs, color: colors.textSecondary },
  metaRow: { flexDirection: "row", flexWrap: "wrap", gap: spacing.xs },
  pill: {
    paddingHorizontal: spacing.sm,
    paddingVertical: 4,
    backgroundColor: colors.surface,
    borderRadius: radii.full,
    borderWidth: 1,
    borderColor: colors.border,
  },
  pillAccent: {
    backgroundColor: colors.accent + "18",
    borderColor: colors.accent + "40",
  },
  pillText: { ...typography.xs, color: colors.textSecondary },
  pillTextAccent: { color: colors.accent, fontWeight: "600" },
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
  ratingRow: { flexDirection: "row", alignItems: "center", gap: spacing.xs },
  ratingStars: { fontSize: 14, color: colors.accent },
  ratingLabel: { ...typography.sm, color: colors.textSecondary },
  ratingMuted: { ...typography.sm, color: colors.textSecondary, fontStyle: "italic" },
  offerHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "flex-start" },
  linkBtn: { paddingVertical: spacing.xs },
  linkText: { ...typography.sm, color: colors.accent, fontWeight: "600" },
  menuCategory: { gap: spacing.xs, paddingTop: spacing.xs },
  menuCategoryTitle: { ...typography.sm, fontWeight: "700", color: colors.text },
  menuItem: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
    gap: spacing.sm,
    paddingVertical: 4,
  },
  menuItemLeft: { flex: 1, gap: 2 },
  menuItemName: { ...typography.sm, color: colors.text },
  menuItemDesc: { ...typography.xs, color: colors.textSecondary },
  menuItemPrice: { ...typography.sm, fontWeight: "600", color: colors.text },
  reviewHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  reviewerName: { ...typography.sm, fontWeight: "700", color: colors.text },
  reviewStars: { fontSize: 12, color: colors.accent },
});
