import React, { useState } from "react";
import {
  Modal,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  View,
} from "react-native";
import DateTimePicker, {
  type DateTimePickerEvent,
} from "@react-native-community/datetimepicker";
import { Ionicons } from "@expo/vector-icons";
import { colors, radii, spacing, typography } from "../theme/tokens";

/** Formats a Date to "YYYY-MM-DD HH:mm" for backend submission. */
export function formatStartsAt(date: Date): string {
  const pad = (n: number) => String(n).padStart(2, "0");
  return (
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
    ` ${pad(date.getHours())}:${pad(date.getMinutes())}`
  );
}

const DAY_NAMES = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
const MONTH_NAMES = [
  "Jan", "Feb", "Mar", "Apr", "May", "Jun",
  "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
];

function fmtDate(d: Date): string {
  return `${DAY_NAMES[d.getDay()]}, ${MONTH_NAMES[d.getMonth()]} ${d.getDate()}`;
}

function fmtTime(d: Date): string {
  let h = d.getHours();
  const m = String(d.getMinutes()).padStart(2, "0");
  const ampm = h >= 12 ? "PM" : "AM";
  h = h % 12 || 12;
  return `${h}:${m} ${ampm}`;
}

function mergeDate(base: Date, src: Date): Date {
  const out = new Date(base);
  out.setFullYear(src.getFullYear(), src.getMonth(), src.getDate());
  return out;
}

function mergeTime(base: Date, src: Date): Date {
  const out = new Date(base);
  out.setHours(src.getHours(), src.getMinutes(), 0, 0);
  return out;
}

type Mode = "date" | "time";

type Props = {
  value: Date | null;
  onChange: (date: Date) => void;
  error?: string | null;
};

/**
 * Clean date/time field with two pressable rows (Date · Time).
 * iOS: bottom-sheet modal with Done button.
 * Android: native dialog; auto-opens time after date.
 */
export function DateTimeField({ value, onChange, error }: Props) {
  const [pickerMode, setPickerMode] = useState<Mode | null>(null);
  // iOS accumulates spinner ticks until the user taps Done
  const [iosPending, setIosPending] = useState<Date>(new Date());

  const openPicker = (mode: Mode) => {
    setIosPending(value ?? new Date());
    setPickerMode(mode);
  };

  // ── iOS modal ─────────────────────────────────────────────────────────────
  const commitIos = () => {
    const base = value ?? new Date();
    onChange(
      pickerMode === "date"
        ? mergeDate(base, iosPending)
        : mergeTime(base, iosPending),
    );
    setPickerMode(null);
  };

  // ── Android dialog ────────────────────────────────────────────────────────
  const onAndroidChange = (ev: DateTimePickerEvent, selected?: Date) => {
    const mode = pickerMode; // capture before React re-render
    setPickerMode(null);
    if (ev.type === "dismissed" || !selected) return;
    const base = value ?? new Date();
    if (mode === "date") {
      onChange(mergeDate(base, selected));
      setTimeout(() => setPickerMode("time"), 30); // auto-open time
    } else {
      onChange(mergeTime(base, selected));
    }
  };

  return (
    <View style={styles.wrapper}>
      <Text style={styles.label}>Date & time</Text>

      <View style={[styles.card, !!error && styles.cardError]}>
        {/* Date row */}
        <Pressable
          style={styles.row}
          onPress={() => openPicker("date")}
          android_ripple={{ color: colors.surface }}
        >
          <Ionicons
            name="calendar-outline"
            size={18}
            color={value ? colors.text : colors.textMuted}
            style={styles.icon}
          />
          <Text style={[styles.rowText, !value && styles.placeholder]}>
            {value ? fmtDate(value) : "Select date"}
          </Text>
          <Ionicons name="chevron-forward" size={15} color={colors.textMuted} />
        </Pressable>

        <View style={styles.separator} />

        {/* Time row */}
        <Pressable
          style={styles.row}
          onPress={() => openPicker("time")}
          android_ripple={{ color: colors.surface }}
        >
          <Ionicons
            name="time-outline"
            size={18}
            color={value ? colors.text : colors.textMuted}
            style={styles.icon}
          />
          <Text style={[styles.rowText, !value && styles.placeholder]}>
            {value ? fmtTime(value) : "Select time"}
          </Text>
          <Ionicons name="chevron-forward" size={15} color={colors.textMuted} />
        </Pressable>
      </View>

      {error ? <Text style={styles.error}>{error}</Text> : null}

      {/* Android: native dialog (auto-dismisses via onChange) */}
      {Platform.OS !== "ios" && pickerMode ? (
        <DateTimePicker
          value={value ?? new Date()}
          mode={pickerMode}
          display="default"
          onChange={onAndroidChange}
        />
      ) : null}

      {/* iOS: bottom-sheet modal with Done */}
      {Platform.OS === "ios" && pickerMode ? (
        <Modal transparent animationType="slide" visible>
          <View style={styles.overlay}>
            <Pressable style={styles.overlayBg} onPress={commitIos} />
            <View style={styles.sheet}>
              <View style={styles.sheetHeader}>
                <Text style={styles.sheetTitle}>
                  {pickerMode === "date" ? "Select date" : "Select time"}
                </Text>
                <Pressable onPress={commitIos} hitSlop={12}>
                  <Text style={styles.doneText}>Done</Text>
                </Pressable>
              </View>
              <DateTimePicker
                value={iosPending}
                mode={pickerMode}
                display="spinner"
                onChange={(_, sel) => {
                  if (sel) setIosPending(sel);
                }}
              />
            </View>
          </View>
        </Modal>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: { gap: 6 },
  label: { ...typography.base, fontWeight: "600", color: colors.text },
  card: {
    borderWidth: 1,
    borderColor: colors.borderInput,
    borderRadius: radii.input,
    backgroundColor: colors.background,
    overflow: "hidden",
  },
  cardError: { borderColor: colors.dangerBorder, borderWidth: 1.5 },
  row: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 14,
    paddingVertical: 13,
    gap: spacing.sm,
  },
  icon: { width: 20 },
  rowText: { flex: 1, ...typography.md, color: colors.text },
  placeholder: { color: colors.textMuted },
  separator: { height: 1, backgroundColor: colors.border },
  error: { ...typography.sm, color: colors.danger },
  // iOS modal
  overlay: { flex: 1, justifyContent: "flex-end" },
  overlayBg: { flex: 1, backgroundColor: "rgba(0,0,0,0.35)" },
  sheet: {
    backgroundColor: colors.background,
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
    paddingBottom: 32,
  },
  sheetHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderBottomWidth: 1,
    borderBottomColor: colors.border,
  },
  sheetTitle: { ...typography.base, fontWeight: "700", color: colors.text },
  doneText: { ...typography.base, fontWeight: "700", color: colors.accent },
});
