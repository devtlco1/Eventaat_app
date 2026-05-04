import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { Pressable, StyleSheet, Text } from "react-native";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { colors, typography } from "../theme/tokens";

type Props = NativeStackScreenProps<AuthStackParamList, "OtpVerify">;

export function OtpVerifyScreen({ route }: Props) {
  const { phone, name: nameFromSignup } = route.params;
  const signupName =
    typeof nameFromSignup === "string" && nameFromSignup.trim().length > 0
      ? nameFromSignup.trim()
      : undefined;

  const { verifyOtp } = useAuth();

  const [otp, setOtp] = useState("");
  const [optionalName, setOptionalName] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [resendLoading, setResendLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      await verifyOtp({
        phone,
        otp: otp.trim(),
        name: signupName ?? (optionalName.trim() ? optionalName.trim() : undefined),
      });
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Verify failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const resend = async () => {
    setResendLoading(true);
    setError(null);
    try {
      await requestOtp(phone.trim());
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Could not resend code.";
      setError(msg);
    } finally {
      setResendLoading(false);
    }
  };

  return (
    <AuthScreenLayout
      title="Verify code"
      subtitle={`Enter the code we sent to ${phone}`}
      footer={
        <Pressable onPress={resend} disabled={resendLoading} hitSlop={8}>
          <Text style={styles.resend}>
            {resendLoading ? "Sending..." : "Resend code"}
          </Text>
        </Pressable>
      }
    >
      <ErrorBanner message={error} />

      <TextField
        label="One-time code"
        value={otp}
        onChangeText={setOtp}
        placeholder="123456"
        keyboardType="number-pad"
      />

      {!signupName ? (
        <TextField
          label="Name (optional)"
          value={optionalName}
          onChangeText={setOptionalName}
          placeholder="Your name"
          autoCapitalize="words"
        />
      ) : null}

      <Button
        title={loading ? "Verifying..." : "Verify and continue"}
        onPress={submit}
        loading={loading}
        disabled={otp.trim().length < 4}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  resend: {
    ...typography.base,
    fontWeight: "600",
    color: colors.accent,
  },
});
