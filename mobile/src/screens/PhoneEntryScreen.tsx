import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { IraqPhoneInput } from "../components/IraqPhoneInput";
import { normalizeIraqPhone, isValidIraqPhone } from "../utils/phone";
import type { AuthStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<AuthStackParamList, "PhoneEntry">;

export function PhoneEntryScreen({ navigation }: Props) {
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
      navigation.navigate("OtpVerify", { phone: normalized, mode: "login" });
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
      title="Sign In"
      subtitle="Hi! Welcome back, you've been missed"
      footer={
        <AuthFooterLink
          label="Don't have an account?"
          linkLabel="Sign Up"
          onPress={() => navigation.navigate("SignUp")}
        />
      }
    >
      <ErrorBanner message={error} />

      <IraqPhoneInput
        label="Phone Number"
        value={localNumber}
        onChangeText={setLocalNumber}
        variant="filled"
      />

      <Button
        title={loading ? "Sending code…" : "Sign In"}
        onPress={submit}
        loading={loading}
        disabled={localNumber.trim().length < 7 || loading}
      />
    </AuthScreenLayout>
  );
}
