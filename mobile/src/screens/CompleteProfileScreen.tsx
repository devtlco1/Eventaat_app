import React, { useState } from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { StatusBar } from "expo-status-bar";
import { ApiErrorResponse } from "../api/client";
import { updateMe } from "../api/endpoints";
import { useAuth } from "../auth/AuthContext";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { Button } from "../components/Button";
import { radii } from "../theme/tokens";

/**
 * Complete Your Profile screen — native implementation.
 *
 * Name is submitted to the backend via updateMe (required to clear the
 * profile_completed gate). Age and Gender are collected locally only;
 * TODO: submit when backend profile schema is extended.
 */

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

const GENDER_OPTIONS = ["Male", "Female", "Prefer not to say"] as const;

export function CompleteProfileScreen() {
  const { token, refreshMe } = useAuth();

  const [name, setName] = useState("");
  // TODO: submit age and gender when backend profile schema supports them.
  const [age, setAge] = useState("");
  const [genderIndex, setGenderIndex] = useState(-1);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const canSubmit = name.trim().length >= 2 && !loading;
  const selectedGender = genderIndex >= 0 ? GENDER_OPTIONS[genderIndex] : null;

  const cycleGender = () =>
    setGenderIndex((prev) => (prev + 1) % GENDER_OPTIONS.length);

  const submit = async () => {
    if (!token) return;
    setLoading(true);
    setError(null);
    try {
      await updateMe(token, { name: name.trim() });
      // TODO: also persist age and gender when backend supports them.
      await refreshMe();
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Update failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreenLayout
      title="Complete Your Profile"
      subtitle="Don't worry, only you can see your personal data."
    >
      <StatusBar style="dark" />

      {error ? (
        <View style={styles.errorBanner}>
          <Text style={styles.errorText} numberOfLines={2}>{error}</Text>
        </View>
      ) : null}

      {/* Name field — functional, submitted to backend */}
      <View style={styles.fieldGroup}>
        <Text style={styles.label}>Full Name</Text>
        <TextInput
          style={styles.input}
          value={name}
          onChangeText={(t) => { setName(t); setError(null); }}
          placeholder="Ex. John Doe"
          placeholderTextColor={PLACEHOLDER}
          autoCapitalize="words"
          returnKeyType="done"
          onSubmitEditing={submit}
        />
      </View>

      {/* Age field — display only */}
      <View style={styles.fieldGroup}>
        <Text style={styles.label}>Age</Text>
        <TextInput
          style={styles.input}
          value={age}
          onChangeText={setAge}
          placeholder="Ex. 28"
          placeholderTextColor={PLACEHOLDER}
          keyboardType="number-pad"
          returnKeyType="done"
        />
      </View>

      {/* Gender selector — display only */}
      <View style={styles.fieldGroup}>
        <Text style={styles.label}>Gender</Text>
        <Pressable
          style={styles.genderRow}
          onPress={cycleGender}
          accessibilityRole="button"
          accessibilityLabel={selectedGender ?? "Select gender"}
        >
          <Text style={[styles.genderText, !selectedGender && styles.genderPlaceholder]}>
            {selectedGender ?? "Select"}
          </Text>
          <Text style={styles.genderChevron}>▼</Text>
        </Pressable>
      </View>

      <Button
        title={loading ? "Saving…" : "Complete Profile"}
        onPress={submit}
        disabled={!canSubmit}
        loading={loading}
        style={styles.btn}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 8,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  errorText: { color: "#991B1B", fontSize: 13, lineHeight: 18 },

  fieldGroup: { gap: 6 },
  label: { fontSize: 15, fontWeight: "600", color: TEXT },
  input: {
    height: 52,
    backgroundColor: INPUT_BG,
    borderRadius: radii.input,
    paddingHorizontal: 16,
    fontSize: 16,
    color: TEXT,
  },

  genderRow: {
    height: 52,
    backgroundColor: INPUT_BG,
    borderRadius: radii.input,
    paddingHorizontal: 16,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  genderText: { fontSize: 16, color: TEXT },
  genderPlaceholder: { color: PLACEHOLDER },
  genderChevron: { fontSize: 12, color: "#6B7280" },

  btn: { marginTop: 4 },
});
