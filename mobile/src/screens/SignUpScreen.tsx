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
import { normalizeIraqPhone, isValidIraqPhone } from "../utils/phone";
import { artboard } from "../utils/artboard";
import type { AuthStackParamList } from "../navigation/AppNavigator";

/**
 * Create Account screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome (blobs, typography, field outlines, button art).
 * Native controls overlay the interactive areas using artboard-space coordinates
 * measured from create-account.png (375×812 @3x):
 *
 *   Name field       x=24  y=253  w=326  h=42
 *   Phone field row  x=24  y=337  w=326  h=42
 *   Terms checkbox   x=24  y=425  w=295  h=40
 *   Sign Up button   x=24  y=474  w=326  h=47
 *   Footer link      x=40  y=713  w=295  h=36
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

type Props = NativeStackScreenProps<AuthStackParamList, "SignUp">;

export function SignUpScreen({ navigation }: Props) {
  const ab = artboard();
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

  const d = (base: object) =>
    DEBUG_TOUCH_AREAS
      ? { ...base, borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.15)" }
      : base;

  return (
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/create-account.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Error banner — floats below header area */}
      {error ? (
        <View style={[ab.rect(24, 220, 326, 28), styles.errorBanner]}>
          <Text style={styles.errorText} numberOfLines={1}>{error}</Text>
        </View>
      ) : null}

      {/* ── Name field ── */}
      <TextInput
        style={[ab.rect(24, 253, 326, 42), styles.input, DEBUG_TOUCH_AREAS && styles.debug]}
        value={name}
        onChangeText={setName}
        placeholder="John Doe"
        placeholderTextColor={PLACEHOLDER}
        autoCapitalize="words"
        returnKeyType="next"
        onSubmitEditing={() => phoneRef.current?.focus()}
        blurOnSubmit={false}
      />

      {/* ── Phone field row (+964 prefix) ── */}
      <View
        style={[
          ab.rect(24, 337, 326, 42),
          styles.phoneRow,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
      >
        <View style={styles.prefix}>
          <Text style={styles.prefixFlag}>🇮🇶</Text>
          <Text style={styles.prefixCode}>+964</Text>
        </View>
        <View style={styles.prefixDivider} />
        <TextInput
          ref={phoneRef}
          style={styles.phoneInput}
          value={localNumber}
          onChangeText={setLocalNumber}
          placeholder="770 000 0000"
          placeholderTextColor={PLACEHOLDER}
          keyboardType="phone-pad"
          returnKeyType="done"
          onSubmitEditing={submit}
        />
      </View>

      {/* ── Terms & Conditions checkbox ── */}
      <Pressable
        style={[ab.rect(24, 425, 295, 40), styles.termsRow, DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={() => setTermsAccepted(!termsAccepted)}
        hitSlop={4}
        accessibilityRole="checkbox"
        accessibilityState={{ checked: termsAccepted }}
      >
        <View style={[styles.checkbox, termsAccepted && styles.checkboxChecked]}>
          {termsAccepted && <Text style={styles.checkmark}>✓</Text>}
        </View>
      </Pressable>

      {/* ── Sign Up button ── */}
      <Pressable
        style={[
          ab.rect(24, 474, 326, 47),
          styles.button,
          !canSubmit && styles.buttonDisabled,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={submit}
        disabled={!canSubmit}
        accessibilityRole="button"
        accessibilityLabel="Sign Up"
      >
        {loading && <ActivityIndicator size="small" color="#FFFFFF" style={{ marginRight: 8 }} />}
        <Text style={styles.buttonText}>{loading ? "Sending code…" : "Sign Up"}</Text>
      </Pressable>

      {/* ── Social login area — TODO: no social auth in current backend ── */}
      {/* Social login circles in the PNG are decorative; wire when OAuth is added. */}

      {/* ── "Already have an account? Sign In" footer link ── */}
      <Pressable
        style={[ab.rect(40, 713, 295, 36), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={() => navigation.navigate("PhoneEntry")}
        hitSlop={8}
        accessibilityRole="link"
        accessibilityLabel="Sign In"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },

  input: {
    backgroundColor: INPUT_BG,
    borderRadius: 12,
    paddingHorizontal: 16,
    fontSize: 15,
    color: TEXT,
  },

  phoneRow: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: INPUT_BG,
    borderRadius: 12,
    overflow: "hidden",
  },
  prefix: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 12,
    gap: 6,
  },
  prefixFlag: { fontSize: 18 },
  prefixCode: { fontSize: 14, color: TEXT, fontWeight: "600" },
  prefixDivider: { width: 1, height: 22, backgroundColor: "#D1D5DB" },
  phoneInput: { flex: 1, paddingHorizontal: 12, fontSize: 15, color: TEXT },

  termsRow: { flexDirection: "row", alignItems: "center" },
  checkbox: {
    width: 20,
    height: 20,
    borderRadius: 4,
    borderWidth: 2,
    borderColor: "#D1D5DB",
    backgroundColor: "transparent",
    alignItems: "center",
    justifyContent: "center",
  },
  checkboxChecked: { borderColor: PURPLE, backgroundColor: PURPLE },
  checkmark: { color: "#FFFFFF", fontSize: 12, fontWeight: "700", lineHeight: 14 },

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

  debug: { borderWidth: 2, borderColor: "red", backgroundColor: "rgba(255,0,0,0.12)" },
});
