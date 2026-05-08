import React, { useRef, useState } from "react";
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { StatusBar } from "expo-status-bar";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { useAuth } from "../auth/AuthContext";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { Button } from "../components/Button";
import { colors } from "../theme/tokens";
import type { AuthStackParamList } from "../navigation/AppNavigator";

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const INPUT_FILLED_BG = "#EDE9FA";
const TEXT = "#111827";

const OTP_LENGTH = 6;

type Props = NativeStackScreenProps<AuthStackParamList, "OtpVerify">;

function maskPhone(phone: string): string {
  // "+9647700001781" → "+9647*****1781"
  if (phone.length <= 9) return phone;
  return phone.slice(0, 5) + "*".repeat(Math.max(phone.length - 9, 3)) + phone.slice(-4);
}

export function OtpVerifyScreen({ route, navigation }: Props) {
  const { phone, name: nameParam, mode } = route.params;
  const signupName =
    typeof nameParam === "string" && nameParam.trim().length > 0
      ? nameParam.trim()
      : undefined;

  const { verifyOtp } = useAuth();
  const hiddenRef = useRef<TextInput>(null);

  const [otp, setOtp] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [resendLoading, setResendLoading] = useState(false);
  const [resendMsg, setResendMsg] = useState<string | null>(null);

  const handleOtpChange = (text: string) => {
    const digits = text.replace(/\D/g, "").slice(0, OTP_LENGTH);
    setOtp(digits);
    if (error) setError(null);
  };

  const submit = async () => {
    if (otp.length < OTP_LENGTH) return;
    setLoading(true);
    setError(null);
    try {
      await verifyOtp({
        phone,
        otp: otp.trim(),
        name: mode === "signup" ? signupName : undefined,
      });
    } catch (e) {
      const msg = e instanceof ApiErrorResponse ? e.message : "Verify failed.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  const resend = async () => {
    if (resendLoading) return;
    setResendLoading(true);
    setResendMsg(null);
    setError(null);
    try {
      await requestOtp(phone);
      setResendMsg("Code sent!");
      setTimeout(() => setResendMsg(null), 3000);
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Could not resend code.";
      setError(msg);
    } finally {
      setResendLoading(false);
    }
  };

  const canSubmit = otp.length >= OTP_LENGTH && !loading;

  return (
    <AuthScreenLayout
      title="Verify Code"
      subtitle={`A verification code was sent to\n${maskPhone(phone)}`}
      onBack={() => navigation.goBack()}
    >
      <StatusBar style="dark" />

      {/* Error / success banner */}
      {(error || resendMsg) ? (
        <View style={[styles.banner, error ? styles.bannerError : styles.bannerSuccess]}>
          <Text style={[styles.bannerText, error ? styles.bannerTextError : styles.bannerTextSuccess]}>
            {error ?? resendMsg}
          </Text>
        </View>
      ) : null}

      {/* Hidden TextInput — captures keyboard input */}
      <TextInput
        ref={hiddenRef}
        style={styles.hiddenInput}
        value={otp}
        onChangeText={handleOtpChange}
        keyboardType="number-pad"
        maxLength={OTP_LENGTH}
        caretHidden
      />

      {/* OTP boxes */}
      <Pressable style={styles.otpRow} onPress={() => hiddenRef.current?.focus()}>
        {Array.from({ length: OTP_LENGTH }, (_, i) => {
          const char = otp[i];
          const isCurrent = i === otp.length;
          return (
            <View
              key={i}
              style={[
                styles.otpBox,
                char ? styles.otpBoxFilled : null,
                isCurrent ? styles.otpBoxActive : null,
              ]}
            >
              <Text style={styles.otpChar}>{char ?? ""}</Text>
            </View>
          );
        })}
      </Pressable>

      {/* Resend */}
      <Pressable
        style={styles.resendRow}
        onPress={resend}
        disabled={resendLoading}
        hitSlop={{ top: 12, bottom: 12, left: 16, right: 16 }}
        accessibilityRole="button"
        accessibilityLabel="Resend code"
      >
        {resendLoading ? (
          <ActivityIndicator size="small" color={PURPLE} style={{ marginRight: 6 }} />
        ) : null}
        <Text style={styles.resendMuted}>Didn't receive OTP? </Text>
        <Text style={styles.resendLink}>Resend code</Text>
      </Pressable>

      <Button
        title={loading ? "Verifying…" : "Verify"}
        onPress={submit}
        disabled={!canSubmit}
        loading={loading}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  hiddenInput: { position: "absolute", opacity: 0, width: 1, height: 1 },

  banner: {
    borderRadius: 8,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  bannerError: { backgroundColor: "#FEE2E2" },
  bannerSuccess: { backgroundColor: "#D1FAE5" },
  bannerText: { fontSize: 13, lineHeight: 18 },
  bannerTextError: { color: "#991B1B" },
  bannerTextSuccess: { color: "#065F46" },

  otpRow: {
    flexDirection: "row",
    gap: 8,
    justifyContent: "center",
  },
  otpBox: {
    flex: 1,
    height: 52,
    maxWidth: 48,
    borderRadius: 12,
    backgroundColor: INPUT_BG,
    alignItems: "center",
    justifyContent: "center",
  },
  otpBoxFilled: { backgroundColor: INPUT_FILLED_BG },
  otpBoxActive: { borderWidth: 1.5, borderColor: PURPLE },
  otpChar: { fontSize: 20, fontWeight: "700", color: TEXT },

  resendRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 4,
  },
  resendMuted: { fontSize: 14, color: colors.textSecondary },
  resendLink: { fontSize: 14, fontWeight: "600", color: PURPLE },
});
