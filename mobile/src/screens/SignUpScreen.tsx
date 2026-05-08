import React, { useRef, useState } from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  TextInput,
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
import { colors, radii } from "../theme/tokens";
import type { AuthStackParamList } from "../navigation/AppNavigator";

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

type Props = NativeStackScreenProps<AuthStackParamList, "SignUp">;

export function SignUpScreen({ navigation }: Props) {
  const phoneRef = useRef<TextInput>(null);

  const [name, setName] = useState("");
  const [localNumber, setLocalNumber] = useState("");
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const canSubmit =
    name.trim().length >= 2 &&
    localNumber.trim().length >= 7 &&
    termsAccepted &&
    !loading;

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

  return (
    <AuthScreenLayout
      title="Create Account"
      subtitle="Fill in your details to get started"
      footer={
        <AuthFooterLink
          label="Already have an account?"
          linkLabel="Sign In"
          onPress={() => navigation.navigate("PhoneEntry")}
        />
      }
    >
      <StatusBar style="dark" />

      {error ? (
        <View style={styles.errorBanner}>
          <Text style={styles.errorText} numberOfLines={2}>{error}</Text>
        </View>
      ) : null}

      {/* Name field */}
      <View style={styles.fieldGroup}>
        <Text style={styles.label}>Full Name</Text>
        <TextInput
          style={styles.input}
          value={name}
          onChangeText={(t) => { setName(t); setError(null); }}
          placeholder="John Doe"
          placeholderTextColor={PLACEHOLDER}
          autoCapitalize="words"
          returnKeyType="next"
          onSubmitEditing={() => phoneRef.current?.focus()}
          blurOnSubmit={false}
        />
      </View>

      {/* Phone field */}
      <IraqPhoneInput
        ref={phoneRef}
        label="Phone Number"
        variant="filled"
        value={localNumber}
        onChangeText={(t) => { setLocalNumber(t); setError(null); }}
        returnKeyType="done"
        onSubmitEditing={submit}
      />

      {/* Terms checkbox */}
      <Pressable
        style={styles.termsRow}
        onPress={() => setTermsAccepted(!termsAccepted)}
        hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
        accessibilityRole="checkbox"
        accessibilityState={{ checked: termsAccepted }}
      >
        <View style={[styles.checkbox, termsAccepted && styles.checkboxChecked]}>
          {termsAccepted && <Text style={styles.checkmark}>✓</Text>}
        </View>
        <Text style={styles.termsText}>
          I agree with{" "}
          <Text style={styles.termsLink}>Terms & Conditions</Text>
        </Text>
      </Pressable>

      <Button
        title={loading ? "Sending code…" : "Sign Up"}
        onPress={submit}
        disabled={!canSubmit}
        loading={loading}
        style={styles.btn}
      />

      <SocialDivider label="Or sign up with" />
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
  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 8,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  errorText: { color: "#991B1B", fontSize: 13, lineHeight: 18 },

  fieldGroup: { gap: 6 },
  label: { fontSize: 15, fontWeight: "600", color: TEXT },
  input: {
    height: 52,
    backgroundColor: INPUT_BG,
    borderRadius: radii.input,
    paddingHorizontal: 16,
    fontSize: 16,
    color: TEXT,
  },

  termsRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    marginTop: 4,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 5,
    borderWidth: 2,
    borderColor: "#D1D5DB",
    backgroundColor: "transparent",
    alignItems: "center",
    justifyContent: "center",
  },
  checkboxChecked: { borderColor: PURPLE, backgroundColor: PURPLE },
  checkmark: { color: "#FFFFFF", fontSize: 13, fontWeight: "700", lineHeight: 15 },
  termsText: { fontSize: 14, color: "#6B7280", flex: 1 },
  termsLink: { color: PURPLE, fontWeight: "600" },

  btn: { marginTop: 4 },
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
