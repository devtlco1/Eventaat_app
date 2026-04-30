import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { ErrorBanner } from "../components/ErrorBanner";
import { listRestaurants } from "../api/endpoints";
import { getErrorMessage, isAuthError } from "../api/errors";
import type { MobileRestaurantListItem } from "../api/types";

export function RestaurantListScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<RootStackParamList>>();
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
    <TouchableOpacity
      style={styles.card}
      onPress={() => navigation.navigate("RestaurantDetails", { slug: item.slug })}
    >
      <Text style={styles.name}>{item.name}</Text>
      <Text style={styles.meta}>Active branches: {item.active_branches_count}</Text>
    </TouchableOpacity>
  );

  if (isLoading) {
    return (
      <View style={styles.center}>
        <ActivityIndicator />
        <Text style={styles.centerText}>Loading restaurants…</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ErrorBanner message={error} />

      <TextInput
        value={q}
        onChangeText={setQ}
        placeholder="Search restaurants…"
        style={styles.search}
        autoCapitalize="none"
        returnKeyType="search"
        onSubmitEditing={() => load()}
      />

      {items.length === 0 ? (
        <View style={styles.center}>
          <Text style={styles.centerText}>No restaurants found.</Text>
        </View>
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
  container: { flex: 1, padding: 16, gap: 12 },
  search: {
    borderWidth: 1,
    borderColor: "#D1D5DB",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
  },
  list: { paddingBottom: 24, gap: 10 },
  card: {
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    backgroundColor: "white",
  },
  name: { fontSize: 16, fontWeight: "700", color: "#111827" },
  meta: { marginTop: 4, color: "#4B5563" },
  center: { flex: 1, alignItems: "center", justifyContent: "center", gap: 8, padding: 16 },
  centerText: { color: "#4B5563" },
});

