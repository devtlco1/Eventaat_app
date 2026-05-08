import React, { useState } from "react";
import {
  ActivityIndicator,
  Image,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { StatusBar } from "expo-status-bar";
import { useAuth } from "../auth/AuthContext";
import { artboard } from "../utils/artboard";

/**
 * Complete Your Profile screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from complete-profile.png (375×812 @3x):
 *
 *   Age field      x=24  y=452  w=326  h=48   — display-only, TODO: backend schema
 *   Gender field   x=24  y=545  w=326  h=48   — display-only, TODO: backend schema
 *   Complete btn   x=24  y=643  w=326  h=52
 *
 * Neither Age nor Gender are submitted. The button calls refreshMe() to re-evaluate
 * auth state. TODO: submit age and gender when backend profile schema is extended.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

const GENDER_OPTIONS = ["Male", "Female", "Prefer not to say"] as const;

export function CompleteProfileScreen() {
  const { refreshMe } = useAuth();
  const ab = artboard();

  // TODO: submit age and gender to backend when profile schema supports them.
  const [age, setAge] = useState("");
  const [genderIndex, setGenderIndex] = useState(-1);
  const [loading, setLoading] = useState(false);

  const selectedGender = genderIndex >= 0 ? GENDER_OPTIONS[genderIndex] : null;

  const cycleGender = () => {
    setGenderIndex((prev) => (prev + 1) % GENDER_OPTIONS.length);
  };

  const submit = async () => {
    setLoading(true);
    try {
      await refreshMe();
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/complete-profile.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* ── Age field — display only, not submitted ── */}
      <TextInput
        style={[ab.rect(24, 452, 326, 48), styles.input, DEBUG_TOUCH_AREAS && styles.debug]}
        value={age}
        onChangeText={setAge}
        placeholder="Ex.28"
        placeholderTextColor={PLACEHOLDER}
        keyboardType="number-pad"
        returnKeyType="done"
      />

      {/* ── Gender selector — display only, not submitted ── */}
      <Pressable
        style={[
          ab.rect(24, 545, 326, 48),
          styles.genderRow,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={cycleGender}
        accessibilityRole="button"
        accessibilityLabel={selectedGender ?? "Select gender"}
        hitSlop={4}
      >
        <Text style={[styles.genderText, !selectedGender && styles.genderPlaceholder]}>
          {selectedGender ?? "Select"}
        </Text>
        <Text style={styles.genderChevron}>▼</Text>
      </Pressable>

      {/* ── Complete Profile button ── */}
      <Pressable
        style={[
          ab.rect(24, 643, 326, 52),
          styles.button,
          loading && styles.buttonDisabled,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={submit}
        disabled={loading}
        accessibilityRole="button"
        accessibilityLabel="Complete Profile"
      >
        {loading && (
          <ActivityIndicator size="small" color="#FFFFFF" style={{ marginRight: 8 }} />
        )}
        <Text style={styles.buttonText}>
          {loading ? "Saving…" : "Complete Profile"}
        </Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },

  input: {
    backgroundColor: INPUT_BG,
    borderRadius: 12,
    paddingHorizontal: 16,
    fontSize: 15,
    color: TEXT,
  },

  genderRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    backgroundColor: INPUT_BG,
    borderRadius: 12,
    paddingHorizontal: 16,
  },
  genderText: { fontSize: 15, color: TEXT },
  genderPlaceholder: { color: PLACEHOLDER },
  genderChevron: { fontSize: 12, color: "#6B7280" },

  button: {
    backgroundColor: PURPLE,
    borderRadius: 999,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
  },
  buttonDisabled: { backgroundColor: "#9D94D8" },
  buttonText: { color: "#FFFFFF", fontSize: 16, fontWeight: "700" },

  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
