import React, { useState } from "react";
import { Alert, StyleSheet, Text, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";
import { Button } from "../components/Button";
import { Divider } from "../components/Divider";
import { ErrorBanner } from "../components/ErrorBanner";
import { TextField } from "../components/TextField";
import { useAuth } from "../auth/AuthContext";
import { isAuthError, getErrorMessage, getValidationErrors } from "../api/errors";
import { updateMe } from "../api/endpoints";
import { colors, spacing, typography } from "../theme/tokens";

export function ProfileScreen() {
  const { me, token, refreshMe, logout } = useAuth();
  const [name, setName] = useState(me?.name ?? "");
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [nameError, setNameError] = useState<string | null>(null);

  const onSave = async () => {
    if (!token) return;
    setIsSaving(true);
    setError(null);
    setNameError(null);
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
      if (nameErr) setNameError(nameErr);
      setError(getErrorMessage(e));
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe} edges={["bottom"]}>
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.title}>Profile</Text>
          <Text style={styles.phone}>{me?.phone ?? "—"}</Text>
        </View>

        <ErrorBanner message={error} />

        <TextField
          label="Name"
          value={name}
          onChangeText={setName}
          placeholder="Your name"
          autoCapitalize="words"
          error={nameError}
        />

        <Button
          title={isSaving ? "Saving…" : "Save"}
          onPress={onSave}
          loading={isSaving}
        />

        <Divider />

        <Button title="Log out" onPress={logout} variant="ghost" />
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  container: { flex: 1, padding: spacing.xl, gap: spacing.md },
  header: { gap: 4, marginBottom: spacing.sm },
  title: { ...typography.xl, fontWeight: "700", color: colors.text },
  phone: { ...typography.base, color: colors.textSecondary },
});
