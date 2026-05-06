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
import { ApiErrorResponse } from "../api/client";
import { updateMe } from "../api/endpoints";
import { useAuth } from "../auth/AuthContext";
import { artboard } from "../utils/artboard";

/**
 * Complete Your Profile screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from complete-profile.png (375×812 @3x):
 *
 *   Name field     x=24  y=399  w=326  h=42   — functional (updateMe API)
 *   Phone field    x=24  y=481  w=326  h=42   — display-only, TODO: backend doesn't support
 *   Gender field   x=24  y=562  w=326  h=42   — display-only, TODO: backend doesn't support
 *   Complete btn   x=24  y=637  w=326  h=47
 *
 * Only the Name field is submitted. Phone and Gender are collected locally
 * and marked TODO for when the backend profile schema is extended.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

// TODO: Add Male/Female/Other to backend profile schema when supported.
const GENDER_OPTIONS = ["Male", "Female", "Prefer not to say"] as const;

export function CompleteProfileScreen() {
  const { token, refreshMe } = useAuth();
  const ab = artboard();

  const [name, setName] = useState("");
  // TODO: phone and gender are collected but not yet submitted to the backend.
  const [phone, setPhone] = useState("");
  const [genderIndex, setGenderIndex] = useState(-1);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const canSubmit = name.trim().length >= 2 && !loading;

  const submit = async () => {
    if (!token) return;
    setLoading(true);
    setError(null);
    try {
      await updateMe(token, { name: name.trim() });
      // TODO: also persist phone and gender when backend supports them.
      await refreshMe();
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Update failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const cycleGender = () => {
    setGenderIndex((prev) => (prev + 1) % GENDER_OPTIONS.length);
  };

  const selectedGender = genderIndex >= 0 ? GENDER_OPTIONS[genderIndex] : null;

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth/complete-profile.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Error banner */}
      {error ? (
        <View style={[ab.rect(24, 365, 326, 28), styles.errorBanner]}>
          <Text style={styles.errorText} numberOfLines={1}>{error}</Text>
        </View>
      ) : null}

      {/* ── Name field ── */}
      <TextInput
        style={[ab.rect(24, 399, 326, 42), styles.input, DEBUG_TOUCH_AREAS && styles.debug]}
        value={name}
        onChangeText={setName}
        placeholder="Ex. John Doe"
        placeholderTextColor={PLACEHOLDER}
        autoCapitalize="words"
        returnKeyType="done"
        onSubmitEditing={submit}
      />

      {/* ── Phone field — display only, not submitted ── */}
      {/* TODO: wire to backend when profile phone update is supported */}
      <TextInput
        style={[ab.rect(24, 481, 326, 42), styles.input, DEBUG_TOUCH_AREAS && styles.debug]}
        value={phone}
        onChangeText={setPhone}
        placeholder="+964 770 000 0000"
        placeholderTextColor={PLACEHOLDER}
        keyboardType="phone-pad"
        returnKeyType="done"
      />

      {/* ── Gender selector — display only, not submitted ── */}
      {/* TODO: wire to backend when profile gender update is supported */}
      <Pressable
        style={[
          ab.rect(24, 562, 326, 42),
          styles.genderRow,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={cycleGender}
        accessibilityRole="button"
        accessibilityLabel={selectedGender ?? "Select gender"}
        hitSlop={4}
      >
        <Text
          style={[styles.genderText, !selectedGender && styles.genderPlaceholder]}
        >
          {selectedGender ?? "Select gender"}
        </Text>
        <Text style={styles.genderChevron}>▼</Text>
      </Pressable>

      {/* ── Complete Profile button ── */}
      <Pressable
        style={[
          ab.rect(24, 637, 326, 47),
          styles.button,
          !canSubmit && styles.buttonDisabled,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={submit}
        disabled={!canSubmit}
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

  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 12,
  },
  errorText: { color: "#991B1B", fontSize: 12 },

  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
