import React, { useRef, useState } from "react";
import {
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
} from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { ApiErrorResponse } from "../api/client";
import { requestOtp } from "../api/endpoints";
import { AuthScreenLayout } from "../components/auth/AuthScreenLayout";
import { ErrorBanner } from "../components/ErrorBanner";
import { Button } from "../components/Button";
import type { AuthStackParamList } from "../navigation/AppNavigator";
import { useAuth } from "../auth/AuthContext";
import { colors } from "../theme/tokens";

type Props = NativeStackScreenProps<AuthStackParamList, "OtpVerify">;

const OTP_LENGTH = 6;
const PURPLE = "#5B4CBD";

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

  const handleOtpChange = (text: string) => {
    // Allow only digits, cap at OTP_LENGTH
    const digits = text.replace(/\D/g, "").slice(0, OTP_LENGTH);
    setOtp(digits);
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
    setResendLoading(true);
    setError(null);
    try {
      await requestOtp(phone);
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Could not resend code.";
      setError(msg);
    } finally {
      setResendLoading(false);
    }
  };

  // ── OTP box display ─────────────────────────────────────────────────────────

  const focusHidden = () => hiddenRef.current?.focus();

  return (
    <AuthScreenLayout
      title="Verify Code"
      subtitle={
        <Text style={styles.subtitleText}>
          {"Please enter the code we just sent to\n"}
          <Text style={styles.phoneHighlight}>{phone}</Text>
        </Text>
      }
      onBack={() => navigation.goBack()}
      footer={
        <View style={styles.resendContainer}>
          <Text style={styles.resendMuted}>Didn't receive OTP?</Text>
          <Pressable onPress={resend} disabled={resendLoading} hitSlop={8}>
            <Text style={styles.resendLink}>
              {resendLoading ? "Sending…" : "Resend code"}
            </Text>
          </Pressable>
        </View>
      }
    >
      <ErrorBanner message={error} />

      {/* Hidden TextInput captures actual keyboard input */}
      <TextInput
        ref={hiddenRef}
        style={styles.hiddenInput}
        value={otp}
        onChangeText={handleOtpChange}
        keyboardType="number-pad"
        maxLength={OTP_LENGTH}
        caretHidden
      />

      {/* Visible OTP boxes */}
      <Pressable style={styles.boxRow} onPress={focusHidden}>
        {Array.from({ length: OTP_LENGTH }, (_, i) => {
          const char = otp[i];
          const isCurrent = i === otp.length;
          return (
            <View
              key={i}
              style={[
                styles.box,
                char ? styles.boxFilled : null,
                isCurrent && styles.boxActive,
              ]}
            >
              <Text style={styles.boxText}>{char ?? "–"}</Text>
            </View>
          );
        })}
      </Pressable>

      <Button
        title={loading ? "Verifying…" : "Verify"}
        onPress={submit}
        loading={loading}
        disabled={otp.length < OTP_LENGTH || loading}
      />
    </AuthScreenLayout>
  );
}

const styles = StyleSheet.create({
  subtitleText: {
    fontSize: 14,
    lineHeight: 22,
    color: colors.textSecondary,
    textAlign: "left",
  },
  phoneHighlight: {
    color: PURPLE,
    fontWeight: "600",
  },

  // Hidden input
  hiddenInput: {
    position: "absolute",
    opacity: 0,
    width: 1,
    height: 1,
  },

  // OTP boxes
  boxRow: {
    flexDirection: "row",
    gap: 10,
    justifyContent: "center",
    marginVertical: 8,
  },
  box: {
    flex: 1,
    maxWidth: 52,
    height: 60,
    borderRadius: 12,
    backgroundColor: colors.inputBg,
    alignItems: "center",
    justifyContent: "center",
  },
  boxFilled: {
    backgroundColor: "#EDE9FA",
  },
  boxActive: {
    borderWidth: 1.5,
    borderColor: PURPLE,
  },
  boxText: {
    fontSize: 22,
    fontWeight: "700",
    color: colors.text,
  },

  // Resend
  resendContainer: {
    alignItems: "center",
    gap: 4,
  },
  resendMuted: {
    fontSize: 14,
    color: colors.textSecondary,
  },
  resendLink: {
    fontSize: 14,
    fontWeight: "700",
    color: colors.text,
    textDecorationLine: "underline",
  },
});
