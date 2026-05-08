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
 * Sign In screen — hybrid image + native controls.
 *
 * PNG provides all visual chrome. Native controls overlay interactive areas
 * measured from sign-in.png (375×812 @3x):
 *
 *   Email field  x=24  y=254  w=327  h=42   — visual-only; email auth not yet implemented
 *   Phone field  x=24  y=334  w=326  h=42   — functional; sends OTP via existing flow
 *   Sign In btn  x=24  y=438  w=326  h=47
 *   Footer link  x=40  y=677  w=295  h=36
 *
 * Social login buttons in the PNG are decorative.
 * TODO: Wire email auth and social login when backend supports them.
 */

// Set true to show coloured borders on every overlay for QA alignment.
const DEBUG_TOUCH_AREAS = false;

const PURPLE = "#5B4CBD";
const INPUT_BG = "#F5F5F5";
const PLACEHOLDER = "#9CA3AF";
const TEXT = "#111827";

type Props = NativeStackScreenProps<AuthStackParamList, "PhoneEntry">;

export function PhoneEntryScreen({ navigation }: Props) {
  const ab = artboard();
  const phoneRef = useRef<TextInput>(null);

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
    <View style={styles.root}>
      <StatusBar style="dark" />

      <Image
        source={require("../../assets/auth-final/sign-in.png")}
        style={ab.imageStyle}
        resizeMode="stretch"
      />

      {/* Error banner */}
      {error ? (
        <View style={[ab.rect(24, 222, 326, 28), styles.errorBanner]}>
          <Text style={styles.errorText} numberOfLines={1}>{error}</Text>
        </View>
      ) : null}

      {/*
        Email field (y=254–296) is left as PNG-only visual.
        TODO: overlay a native TextInput and wire to email-based auth when backend supports it.
      */}

      {/* ── Phone field row (+964 prefix) ── */}
      <View
        style={[
          ab.rect(24, 334, 326, 42),
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
          autoFocus={false}
        />
      </View>

      {/* ── Sign In button ── */}
      <Pressable
        style={[
          ab.rect(24, 438, 326, 47),
          styles.button,
          !canSubmit && styles.buttonDisabled,
          DEBUG_TOUCH_AREAS && styles.debug,
        ]}
        onPress={submit}
        disabled={!canSubmit}
        accessibilityRole="button"
        accessibilityLabel="Sign In"
      >
        {loading && <ActivityIndicator size="small" color="#FFFFFF" style={{ marginRight: 8 }} />}
        <Text style={styles.buttonText}>{loading ? "Sending code…" : "Sign In"}</Text>
      </Pressable>

      {/* ── Social login — TODO: no social auth in current backend ── */}

      {/* ── "Don't have an account? Sign Up" footer link ── */}
      <Pressable
        style={[ab.rect(40, 677, 295, 36), DEBUG_TOUCH_AREAS && styles.debug]}
        onPress={() => navigation.navigate("SignUp")}
        hitSlop={8}
        accessibilityRole="link"
        accessibilityLabel="Sign Up"
      />
    </View>
  );
}

const styles = StyleSheet.create({
  root: { flex: 1, backgroundColor: "#FFFFFF" },

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
