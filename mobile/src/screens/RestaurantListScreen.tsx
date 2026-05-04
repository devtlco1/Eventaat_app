import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { ExploreStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { Card } from "../components/Card";
import { EmptyState } from "../components/EmptyState";
import { ErrorBanner } from "../components/ErrorBanner";
import { LoadingState } from "../components/LoadingState";
import { listRestaurants } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileRestaurantListItem } from "../api/types";
import { colors, radii, spacing, typography } from "../theme/tokens";

export function RestaurantListScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<ExploreStackParamList>>();
  const { token, logout } = useAuth();

  const [q, setQ] = useState("");
  const [items, setItems] = useState<MobileRestaurantListItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const filteredQuery = useMemo(() => q.trim(), [q]);

  const load = useCallback(
    async (opts: { refreshing?: boolean } = {}) => {
      if (!token) return;
      if (opts.refreshing) setIsRefreshing(true);
      else setIsLoading(true);
      setError(null);

      try {
        const res = await listRestaurants(token, filteredQuery ? { q: filteredQuery } : {});
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
    [filteredQuery, logout, token]
  );

  useEffect(() => {
    void load();
  }, [load]);

  const renderItem = ({ item }: { item: MobileRestaurantListItem }) => (
    <Card onPress={() => navigation.navigate("RestaurantDetails", { slug: item.slug })}>
      <Text style={styles.name}>{item.name}</Text>
      <Text style={styles.meta}>{item.active_branches_count} active branch{item.active_branches_count !== 1 ? "es" : ""}</Text>
    </Card>
  );

  if (isLoading) {
    return <LoadingState message="Loading restaurants…" />;
  }

  return (
    <View style={styles.container}>
      <ErrorBanner message={error} />

      <TextInput
        value={q}
        onChangeText={setQ}
        placeholder="Search restaurants…"
        placeholderTextColor={colors.textMuted}
        style={styles.search}
        autoCapitalize="none"
        returnKeyType="search"
        onSubmitEditing={() => load()}
      />

      {items.length === 0 ? (
        <EmptyState
          title="No restaurants found"
          subtitle={filteredQuery ? `No results for "${filteredQuery}"` : "Check back soon."}
        />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(r) => String(r.id)}
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
  container: { flex: 1, padding: spacing.lg, gap: spacing.md, backgroundColor: colors.surface },
  search: {
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    paddingHorizontal: spacing.md,
    paddingVertical: 10,
    ...typography.md,
    color: colors.text,
    backgroundColor: colors.background,
  },
  list: { paddingBottom: spacing.xxl, gap: spacing.sm },
  name: { ...typography.md, fontWeight: "700", color: colors.text },
  meta: { ...typography.sm, color: colors.textSecondary },
});
