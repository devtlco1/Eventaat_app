import React from "react";
import { StyleSheet, Text, View } from "react-native";

function statusColors(status: string | null): { bg: string; border: string; text: string; label: string } {
  const s = status ?? "unknown";
  const map: Record<string, { bg: string; border: string; text: string; label: string }> = {
    pending: { bg: "#FEF3C7", border: "#FDE68A", text: "#92400E", label: "Pending" },
    accepted: { bg: "#D1FAE5", border: "#A7F3D0", text: "#065F46", label: "Accepted" },
    rejected: { bg: "#FEE2E2", border: "#FCA5A5", text: "#991B1B", label: "Rejected" },
    cancelled: { bg: "#E5E7EB", border: "#D1D5DB", text: "#374151", label: "Cancelled" },
    arrived: { bg: "#DBEAFE", border: "#BFDBFE", text: "#1E40AF", label: "Arrived" },
    seated: { bg: "#E0E7FF", border: "#C7D2FE", text: "#3730A3", label: "Seated" },
    completed: { bg: "#D1FAE5", border: "#A7F3D0", text: "#065F46", label: "Completed" },
    no_show: { bg: "#FEE2E2", border: "#FCA5A5", text: "#991B1B", label: "No-show" },
    unknown: { bg: "#F3F4F6", border: "#E5E7EB", text: "#374151", label: "Unknown" },
  };

  return map[s] ?? map.unknown;
}

export function StatusBadge({ status }: { status: string | null }) {
  const c = statusColors(status);
  return (
    <View style={[styles.badge, { backgroundColor: c.bg, borderColor: c.border }]}>
      <Text style={[styles.text, { color: c.text }]}>{c.label}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    borderWidth: 1,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
    alignSelf: "flex-start",
  },
  text: {
    fontSize: 12,
    fontWeight: "700",
  },
});

