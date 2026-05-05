import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import { IraqPhoneInput } from "../components/IraqPhoneInput";
import { normalizeIraqPhone, isValidIraqPhone } from "../utils/phone";
import type { AuthStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<AuthStackParamList, "SignUp">;

export function SignUpScreen({ navigation }: Props) {
  const [name, setName] = useState("");
  const [localNumber, setLocalNumber] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    const normalized = normalizeIraqPhone(localNumber.trim());
    if (!isValidIraqPhone(normalized)) {
      setError("Enter a valid Iraqi mobile number (e.g. 0770 000 1781).");
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await requestOtp(normalized);
      navigation.navigate("OtpVerify", {
        phone: normalized,
        name: name.trim(),
        mode: "signup",
      });
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Failed to request OTP.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const canSubmit = name.trim().length >= 2 && localNumber.trim().length >= 7 && !loading;

  return (
    <AuthScreenLayout
      title="Create Account"
      subtitle="Fill your information below to get started."
      footer={
        <AuthFooterLink
          label="Already have an account?"
          linkLabel="Sign In"
          onPress={() => navigation.navigate("PhoneEntry")}
        />
      }
    >
      <ErrorBanner message={error} />

      <TextField
        label="Name"
        value={name}
        onChangeText={setName}
        placeholder="John Doe"
        autoCapitalize="words"
        variant="filled"
      />

      <IraqPhoneInput
        label="Phone Number"
        value={localNumber}
        onChangeText={setLocalNumber}
        variant="filled"
      />

      <Button
        title={loading ? "Sending code…" : "Sign Up"}
        onPress={submit}
        loading={loading}
        disabled={!canSubmit}
      />
    </AuthScreenLayout>
  );
}
