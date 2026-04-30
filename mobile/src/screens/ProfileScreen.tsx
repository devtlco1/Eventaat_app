import React, { useState } from "react";
import { Alert, StyleSheet, Text, View } from "react-native";
import { PrimaryButton } from "../components/PrimaryButton";
import { TextField } from "../components/TextField";
import { ErrorBanner } from "../components/ErrorBanner";
import { useAuth } from "../auth/AuthContext";
import { isAuthError, getErrorMessage, getValidationErrors } from "../api/errors";
import { updateMe } from "../api/endpoints";

export function ProfileScreen() {
  const { me, token, refreshMe, logout } = useAuth();
  const [name, setName] = useState(me?.name ?? "");
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldError, setFieldError] = useState<string | null>(null);

  const onSave = async () => {
    if (!token) return;
    setIsSaving(true);
    setError(null);
    setFieldError(null);
    try {
      await updateMe(token, { name });
      await refreshMe();
      Alert.alert("Saved", "Profile updated.");
    } catch (e) {
      if (isAuthError(e)) {
        await logout();
        return;
      }
      const errors = getValidationErrors(e);
      const nameErr = errors?.name?.[0] ?? null;
      if (nameErr) setFieldError(nameErr);
      setError(getErrorMessage(e));
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Your profile</Text>
      <Text style={styles.subtitle}>Phone: {me?.phone ?? "-"}</Text>

      <ErrorBanner message={error} />

      <TextField label="Name" value={name} onChangeText={setName} placeholder="Your name" autoCapitalize="words" />
      {fieldError ? <Text style={styles.fieldError}>{fieldError}</Text> : null}

      <PrimaryButton title={isSaving ? "Saving..." : "Save"} onPress={onSave} disabled={isSaving} />

      <View style={styles.divider} />

      <PrimaryButton title="Logout" onPress={logout} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: 20, gap: 12 },
  title: { fontSize: 20, fontWeight: "700", color: "#111827" },
  subtitle: { color: "#4B5563" },
  fieldError: { color: "#991B1B" },
  divider: { height: 1, backgroundColor: "#E5E7EB", marginVertical: 8 },
});

