import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { requestOtp } from "../api/endpoints";
import { ApiErrorResponse } from "../api/client";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import type { AuthStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<AuthStackParamList, "PhoneEntry">;

export function PhoneEntryScreen({ navigation }: Props) {
  const [phone, setPhone] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      await requestOtp(phone.trim());
      navigation.navigate("OtpVerify", { phone: phone.trim() });
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Failed to request OTP.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <AuthScreenLayout
      title="Log in"
      subtitle="Enter your phone number and we'll send you a one-time code."
      footer={
        <AuthFooterLink
          label="New to Eventaat?"
          linkLabel="Sign up"
          onPress={() => navigation.navigate("SignUp")}
        />
      }
    >
      <ErrorBanner message={error} />

      <TextField
        label="Phone"
        value={phone}
        onChangeText={setPhone}
        placeholder="+15550000001"
        keyboardType="phone-pad"
      />

      <Button
        title={loading ? "Sending..." : "Continue"}
        onPress={submit}
        loading={loading}
        disabled={phone.trim().length < 6}
      />
    </AuthScreenLayout>
  );
}
