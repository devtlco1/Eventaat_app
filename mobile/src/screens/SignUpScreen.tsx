import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import type { AuthStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<AuthStackParamList, "SignUp">;

export function SignUpScreen({ navigation }: Props) {
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      await requestOtp(phone.trim());
      navigation.navigate("OtpVerify", {
        phone: phone.trim(),
        name: name.trim(),
      });
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Failed to request OTP.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const canSubmit = name.trim().length >= 2 && phone.trim().length >= 6 && !loading;

  return (
    <AuthScreenLayout
      title="Create account"
      subtitle="Add your name and phone number. We'll send a code to verify it's you."
      footer={
        <AuthFooterLink
          label="Already have an account?"
          linkLabel="Log in"
          onPress={() => navigation.navigate("PhoneEntry")}
        />
      }
    >
      <ErrorBanner message={error} />

      <TextField
        label="Name"
        value={name}
        onChangeText={setName}
        placeholder="Your full name"
        autoCapitalize="words"
      />

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
        disabled={!canSubmit}
      />
    </AuthScreenLayout>
  );
}
