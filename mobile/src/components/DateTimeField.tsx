import DateTimePicker from "@react-native-community/datetimepicker";
import React, { useMemo, useState } from "react";
import { Platform, StyleSheet, Text, View } from "react-native";
import { PrimaryButton } from "./PrimaryButton";

export function formatStartsAt(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, "0");
  const y = date.getFullYear();
  const m = pad(date.getMonth() + 1);
  const d = pad(date.getDate());
  const hh = pad(date.getHours());
  const mm = pad(date.getMinutes());
  return `${y}-${m}-${d} ${hh}:${mm}`;
}

export function DateTimeField({
  label,
  value,
  onChange,
  error,
}: {
  label: string;
  value: Date | null;
  onChange: (date: Date | null) => void;
  error?: string | null;
}) {
  const [showDate, setShowDate] = useState(false);
  const [showTime, setShowTime] = useState(false);

  const display = useMemo(() => (value ? formatStartsAt(value) : "Not set"), [value]);

  const onPickDate = (_event: unknown, selected?: Date) => {
    setShowDate(false);
    if (!selected) return;
    const base = value ?? new Date();
    const next = new Date(base);
    next.setFullYear(selected.getFullYear(), selected.getMonth(), selected.getDate());
    onChange(next);

    if (Platform.OS !== "ios") {
      setShowTime(true);
    }
  };

  const onPickTime = (_event: unknown, selected?: Date) => {
    setShowTime(false);
    if (!selected) return;
    const base = value ?? new Date();
    const next = new Date(base);
    next.setHours(selected.getHours(), selected.getMinutes(), 0, 0);
    onChange(next);
  };

  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{display}</Text>

      <View style={styles.buttons}>
        <PrimaryButton title="Pick date" onPress={() => setShowDate(true)} />
        <PrimaryButton title="Pick time" onPress={() => setShowTime(true)} />
      </View>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      {showDate ? (
        <DateTimePicker
          value={value ?? new Date()}
          mode="date"
          display={Platform.OS === "ios" ? "spinner" : "default"}
          onChange={onPickDate}
        />
      ) : null}

      {showTime ? (
        <DateTimePicker
          value={value ?? new Date()}
          mode="time"
          is24Hour
          display={Platform.OS === "ios" ? "spinner" : "default"}
          onChange={onPickTime}
        />
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: { gap: 8 },
  label: { color: "#111827", fontWeight: "600" },
  value: {
    borderWidth: 1,
    borderColor: "#D1D5DB",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 16,
    color: "#111827",
    backgroundColor: "white",
  },
  buttons: { flexDirection: "row", gap: 10, flexWrap: "wrap" },
  error: { color: "#991B1B" },
});

