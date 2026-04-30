import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { StyleSheet, Text, View } from "react-native";
import { ApiErrorResponse } from "../api/client";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { TextField } from "../components/TextField";
import type { RootStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";

type Props = NativeStackScreenProps<RootStackParamList, "OtpVerify">;

export function OtpVerifyScreen({ route }: Props) {
  const { phone } = route.params;
  const { verifyOtp } = useAuth();

  const [otp, setOtp] = useState("");
  const [name, setName] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      await verifyOtp({
        phone,
        otp: otp.trim(),
        name: name.trim() ? name.trim() : undefined,
      });
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Verify failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Verify OTP</Text>
      <Text style={styles.subtitle}>Sent to: {phone}</Text>

      <ErrorBanner message={error} />

      <TextField
        label="OTP"
        value={otp}
        onChangeText={setOtp}
        placeholder="123456"
        keyboardType="number-pad"
      />

      <TextField
        label="Name (optional)"
        value={name}
        onChangeText={setName}
        placeholder="Your name"
        autoCapitalize="words"
      />

      <PrimaryButton
        title={loading ? "Verifying..." : "Verify"}
        onPress={submit}
        disabled={loading || otp.trim().length < 4}
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

