import React, { useState } from "react";
import { StyleSheet, Text, View } from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import { TextField } from "../components/TextField";
import { normalizeIraqPhone, isValidIraqPhone } from "../utils/phone";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { colors, radii, spacing, typography } from "../theme/tokens";

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

      <View>
        <Text style={styles.label}>Phone</Text>
        <View style={styles.row}>
          <View style={styles.prefix}>
            <Text style={styles.prefixFlag}>🇮🇶</Text>
            <Text style={styles.prefixText}>+964</Text>
          </View>
          <View style={styles.inputWrap}>
            <TextField
              value={localNumber}
              onChangeText={setLocalNumber}
              placeholder="0770 000 1781"
              keyboardType="phone-pad"
            />
          </View>
        </View>
      </View>

      <Button
        title={loading ? "Sending..." : "Continue"}
        onPress={submit}
        loading={loading}
        disabled={!canSubmit}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  label: {
    ...typography.sm,
    fontWeight: "600",
    color: colors.text,
    marginBottom: spacing.xs,
  },
  row: {
    flexDirection: "row",
    gap: spacing.sm,
    alignItems: "flex-start",
  },
  prefix: {
    flexDirection: "row",
    alignItems: "center",
    gap: spacing.xs,
    height: 48,
    paddingHorizontal: spacing.md,
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    backgroundColor: colors.surface,
  },
  prefixFlag: {
    fontSize: 16,
  },
  prefixText: {
    ...typography.base,
    fontWeight: "600",
    color: colors.text,
  },
  inputWrap: {
    flex: 1,
  },
});
