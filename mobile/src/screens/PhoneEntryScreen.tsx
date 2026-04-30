import React, { useState } from "react";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import { StyleSheet, Text, View } from "react-native";
import { requestOtp } from "../api/endpoints";
import { ApiErrorResponse } from "../api/client";
import { ErrorBanner } from "../components/ErrorBanner";
import { PrimaryButton } from "../components/PrimaryButton";
import { TextField } from "../components/TextField";
import type { RootStackParamList } from "../navigation/AppNavigator";

type Props = NativeStackScreenProps<RootStackParamList, "PhoneEntry">;

export function PhoneEntryScreen({ navigation }: Props) {
  const [phone, setPhone] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const submit = async () => {
    setLoading(true);
    setError(null);
    try {
      await requestOtp(phone.trim());
      navigation.navigate("OtpVerify", { phone: phone.trim() });
    } catch (e) {
      const msg =
        e instanceof ApiErrorResponse ? e.message : "Failed to request OTP.";
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Enter your phone number</Text>
      <Text style={styles.subtitle}>
        We’ll send a one-time password (OTP) for sign in.
      </Text>

      <ErrorBanner message={error} />

      <TextField
        label="Phone"
        value={phone}
        onChangeText={setPhone}
        placeholder="+15550000001"
        keyboardType="phone-pad"
      />

      <PrimaryButton
        title={loading ? "Sending..." : "Send OTP"}
        onPress={submit}
        disabled={loading || phone.trim().length < 6}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    gap: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: "700",
    color: "#111827",
  },
  subtitle: {
    color: "#4B5563",
  },
});

