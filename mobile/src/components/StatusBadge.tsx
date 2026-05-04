import React from "react";
import { Badge } from "./Badge";

type StatusVariant = "success" | "warning" | "danger" | "info" | "neutral";

const STATUS_MAP: Record<string, { variant: StatusVariant; label: string }> = {
  pending: { variant: "warning", label: "Pending" },
  accepted: { variant: "success", label: "Accepted" },
  rejected: { variant: "danger", label: "Rejected" },
  cancelled: { variant: "neutral", label: "Cancelled" },
  arrived: { variant: "info", label: "Arrived" },
  seated: { variant: "info", label: "Seated" },
  completed: { variant: "success", label: "Completed" },
  no_show: { variant: "danger", label: "No-show" },
};

export function StatusBadge({ status }: { status: string | null }) {
  const entry = STATUS_MAP[status ?? ""] ?? { variant: "neutral" as const, label: "Unknown" };
  return <Badge label={entry.label} variant={entry.variant} />;
}
