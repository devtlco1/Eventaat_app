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

      <IraqPhoneInput
        value={localNumber}
        onChangeText={setLocalNumber}
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
