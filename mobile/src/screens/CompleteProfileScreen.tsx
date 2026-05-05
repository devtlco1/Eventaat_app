import React, { useState } from "react";
import { StyleSheet, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { ApiErrorResponse } from "../api/client";
import { updateMe } from "../api/endpoints";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import { useAuth } from "../auth/AuthContext";

const PURPLE = "#5B4CBD";

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
    <AuthScreenLayout
      title="Complete Your Profile"
      subtitle="Don't worry, only you can see your personal data. No one else will be able to see it."
    >
      <ErrorBanner message={error} />

      {/* ── Avatar placeholder ── */}
      <View style={styles.avatarWrapper}>
        <View style={styles.avatarCircle}>
          <Ionicons name="person" size={52} color={PURPLE} />
        </View>
        <View style={styles.editBadge}>
          <Ionicons name="pencil" size={13} color="#FFFFFF" />
        </View>
      </View>

      <TextField
        label="Name"
        value={name}
        onChangeText={setName}
        placeholder="Ex. John Doe"
        autoCapitalize="words"
        variant="filled"
      />

      <Button
        title={loading ? "Saving…" : "Complete Profile"}
        onPress={submit}
        loading={loading}
        disabled={name.trim().length < 2 || loading}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  avatarWrapper: {
    alignSelf: "center",
    marginBottom: 8,
  },
  avatarCircle: {
    width: 108,
    height: 108,
    borderRadius: 54,
    backgroundColor: "#EDEBF8",
    alignItems: "center",
    justifyContent: "center",
  },
  editBadge: {
    position: "absolute",
    bottom: 4,
    right: 4,
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 2,
    borderColor: "#FFFFFF",
  },
});
