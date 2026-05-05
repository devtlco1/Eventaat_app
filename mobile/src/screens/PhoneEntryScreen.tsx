import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { requestOtp } from "../api/endpoints";
import { ApiErrorResponse } from "../api/client";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
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

      <IraqPhoneInput
        value={localNumber}
        onChangeText={setLocalNumber}
      />

      <Button
        title={loading ? "Sending..." : "Continue"}
        onPress={submit}
        loading={loading}
        disabled={localNumber.trim().length < 7}
      />
    </AuthScreenLayout>
  );
}
