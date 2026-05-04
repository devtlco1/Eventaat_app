import React, { useState } from "react";
import { ApiErrorResponse } from "../api/client";
import { updateMe } from "../api/endpoints";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
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
    <AuthScreenLayout
      title="Complete your profile"
      subtitle="Add your name so restaurants know how to greet you."
    >
      <ErrorBanner message={error} />

      <TextField
        label="Name"
        value={name}
        onChangeText={setName}
        placeholder="Your name"
        autoCapitalize="words"
      />

      <Button
        title={loading ? "Saving..." : "Save and continue"}
        onPress={submit}
        loading={loading}
        disabled={name.trim().length < 2}
      />
    </AuthScreenLayout>
  );
}
