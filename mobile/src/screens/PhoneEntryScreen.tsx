import React, { useState } from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";
import { StatusBar } from "expo-status-bar";
import { Ionicons } from "@expo/vector-icons";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { normalizeIraqPhone, isValidIraqPhone } from "../utils/phone";
import { IraqPhoneInput } from "../components/IraqPhoneInput";
import { AuthFooterLink } from "../components/auth/AuthFooterLink";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { Button } from "../components/Button";
import type { AuthStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<AuthStackParamList, "PhoneEntry">;

export function PhoneEntryScreen({ navigation }: Props) {
  const [localNumber, setLocalNumber] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const canSubmit = localNumber.trim().length >= 7 && !loading;

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
      subtitle="Enter your phone number to continue"
      footer={
        <AuthFooterLink
          label="Don't have an account?"
          linkLabel="Sign Up"
          onPress={() => navigation.navigate("SignUp")}
        />
      }
    >
      <StatusBar style="dark" />

      {error ? (
        <View style={styles.errorBanner}>
          <Text style={styles.errorText} numberOfLines={2}>{error}</Text>
        </View>
      ) : null}

      <IraqPhoneInput
        label="Phone Number"
        variant="filled"
        value={localNumber}
        onChangeText={(t) => { setLocalNumber(t); setError(null); }}
        returnKeyType="done"
        onSubmitEditing={submit}
      />

      <Button
        title={loading ? "Sending code…" : "Sign In"}
        onPress={submit}
        disabled={!canSubmit}
        loading={loading}
        style={styles.btn}
      />

      <SocialDivider label="Or sign in with" />
      <SocialRow />
    </AuthScreenLayout>
  );
}

// ── Shared social sub-components ─────────────────────────────────────────────

function SocialDivider({ label }: { label: string }) {
  return (
    <View style={ss.dividerRow}>
      <View style={ss.dividerLine} />
      <Text style={ss.dividerText}>{label}</Text>
      <View style={ss.dividerLine} />
    </View>
  );
}

function SocialRow() {
  return (
    <View style={ss.socialRow}>
      <SocialBtn icon="logo-apple" />
      <SocialBtn icon="logo-google" />
      <SocialBtn icon="logo-facebook" />
    </View>
  );
}

function SocialBtn({ icon }: { icon: React.ComponentProps<typeof Ionicons>["name"] }) {
  return (
    <Pressable
      style={ss.socialBtn}
      disabled
      accessibilityRole="button"
      accessibilityLabel={String(icon)}
    >
      <Ionicons name={icon} size={22} color="#374151" />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  btn: { marginTop: 4 },
  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 8,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  errorText: { color: "#991B1B", fontSize: 13, lineHeight: 18 },
});

const ss = StyleSheet.create({
  dividerRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
    marginVertical: 4,
  },
  dividerLine: { flex: 1, height: 1, backgroundColor: "#E5E7EB" },
  dividerText: { fontSize: 13, color: "#6B7280" },

  socialRow: {
    flexDirection: "row",
    justifyContent: "center",
    gap: 20,
  },
  socialBtn: {
    width: 52,
    height: 52,
    borderRadius: 26,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
  },
});
