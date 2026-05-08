import React, { useRef, useState } from "react";
import {
  ActivityIndicator,
  Image,
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
import { artboard } from "../utils/artboard";
import type { AuthStackParamList } from "../navigation/AppNavigator";

/**
 * Verify Code screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from verify-code.png (375×812 @3x):
 *
 *   Back button   x=16  y=44   w=44   h=44
 *   OTP boxes     x=24  y=220  w=326  h=52   (6 × ~46pt boxes, 8pt gaps)
 *   Resend link   x=60  y=345  w=255  h=36
 *   Verify btn    x=24  y=415  w=326  h=50
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const OTP_LENGTH = 6;

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const INPUT_FILLED_BG = "#EDE9FA";
const TEXT = "#111827";

type Props = NativeStackScreenProps<AuthStackParamList, "OtpVerify">;

export function OtpVerifyScreen({ route, navigation }: Props) {
  const { phone, name: nameParam, mode } = route.params;
  const signupName =
    typeof nameParam === "string" && nameParam.trim().length > 0
      ? nameParam.trim()
      : undefined;

  const { verifyOtp } = useAuth();
  const ab = artboard();
  const hiddenRef = useRef<TextInput>(null);

  const [otp, setOtp] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [resendLoading, setResendLoading] = useState(false);
  const [resendMsg, setResendMsg] = useState<string | null>(null);

  const handleOtpChange = (text: string) => {
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
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/verify-code.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Error / resend feedback */}
      {(error || resendMsg) ? (
        <View
          style={[
            ab.rect(24, 185, 326, 28),
            error ? styles.errorBanner : styles.successBanner,
          ]}
        >
          <Text
            style={error ? styles.errorText : styles.successText}
            numberOfLines={1}
          >
            {error ?? resendMsg}
          </Text>
        </View>
      ) : null}

      {/* ── Back button (top-left circle) ── */}
      <Pressable
        style={[ab.rect(16, 44, 44, 44), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={() => navigation.goBack()}
        hitSlop={8}
        accessibilityRole="button"
        accessibilityLabel="Back"
      />

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

      {/* ── OTP boxes (6 × ~46pt, 8pt gaps, within x=24–350 y=220–272) ── */}
      <Pressable
        style={[
          ab.rect(24, 220, 326, 52),
          styles.otpRow,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={() => hiddenRef.current?.focus()}
      >
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

      {/* ── Resend code link ── */}
      <Pressable
        style={[ab.rect(60, 345, 255, 36), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={resend}
        disabled={resendLoading}
        hitSlop={8}
        accessibilityRole="button"
        accessibilityLabel="Resend code"
      >
        {resendLoading ? (
          <ActivityIndicator size="small" color={PURPLE} />
        ) : null}
      </Pressable>

      {/* ── Verify button ── */}
      <Pressable
        style={[
          ab.rect(24, 415, 326, 50),
          styles.button,
          !canSubmit && styles.buttonDisabled,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={submit}
        disabled={!canSubmit}
        accessibilityRole="button"
        accessibilityLabel="Verify"
      >
        {loading && (
          <ActivityIndicator size="small" color="#FFFFFF" style={{ marginRight: 8 }} />
        )}
        <Text style={styles.buttonText}>{loading ? "Verifying…" : "Verify"}</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },

  hiddenInput: { position: "absolute", opacity: 0, width: 1, height: 1 },

  otpRow: {
    flexDirection: "row",
    gap: 8,
  },
  otpBox: {
    flex: 1,
    height: 42,
    borderRadius: 12,
    backgroundColor: INPUT_BG,
    alignItems: "center",
    justifyContent: "center",
  },
  otpBoxFilled: { backgroundColor: INPUT_FILLED_BG },
  otpBoxActive: { borderWidth: 1.5, borderColor: PURPLE },
  otpChar: { fontSize: 20, fontWeight: "700", color: TEXT },

  button: {
    backgroundColor: PURPLE,
    borderRadius: 999,
    alignItems: "center",
    justifyContent: "center",
    flexDirection: "row",
  },
  buttonDisabled: { backgroundColor: "#9D94D8" },
  buttonText: { color: "#FFFFFF", fontSize: 16, fontWeight: "700" },

  errorBanner: {
    backgroundColor: "#FEE2E2",
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 12,
  },
  errorText: { color: "#991B1B", fontSize: 12 },
  successBanner: {
    backgroundColor: "#D1FAE5",
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 12,
  },
  successText: { color: "#065F46", fontSize: 12 },

  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
