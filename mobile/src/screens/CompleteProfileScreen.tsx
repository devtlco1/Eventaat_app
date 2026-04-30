import React, { useState } from "react";
import { StyleSheet, Text, View } from "react-native";
import { ApiErrorResponse } from "../api/client";
import { updateMe } from "../api/endpoints";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { TextField } from "../components/TextField";
import { useAuth } from "../auth/AuthContext";

export function CompleteProfileScreen() {
  const { token, refreshMe } = useAuth();
  const [name, setName] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    if (!token) return;
    setLoading(true);
    setError(null);
    try {
      await updateMe(token, { name: name.trim() });
      await refreshMe();
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Update failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Complete your profile</Text>
      <Text style={styles.subtitle}>Please add your name to continue.</Text>

      <ErrorBanner message={error} />

      <TextField
        label="Name"
        value={name}
        onChangeText={setName}
        placeholder="Your name"
        autoCapitalize="words"
      />

      <PrimaryButton
        title={loading ? "Saving..." : "Save"}
        onPress={submit}
        disabled={loading || name.trim().length < 2}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    gap: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: "700",
    color: "#111827",
  },
  subtitle: {
    color: "#4B5563",
  },
});

