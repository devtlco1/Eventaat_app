import type { MobileBranchBookingAvailability } from "../api/types";

/** Carbon ISO weekday: Monday = 1 … Sunday = 7 (matches backend). */
export function dayOfWeekIso(d: Date): number {
  const day = d.getDay();
  return day === 0 ? 7 : day;
}

function isWeekdayAllowed(rule: MobileBranchBookingAvailability, iso: number): boolean {
  switch (iso) {
    case 1:
      return rule.mon;
    case 2:
      return rule.tue;
    case 3:
      return rule.wed;
    case 4:
      return rule.thu;
    case 5:
      return rule.fri;
    case 6:
      return rule.sat;
    case 7:
      return rule.sun;
    default:
      return false;
  }
}

/** Normalize to HH:mm:ss for lexicographic comparison (aligned with backend string compare). */
export function normalizeTimeToHms(value: string | null | undefined): string | null {
  if (value == null || value === "") return null;
  const parts = String(value).trim().split(":");
  const h = (parts[0] ?? "0").padStart(2, "0");
  const m = (parts[1] ?? "0").padStart(2, "0");
  const s = (parts[2] ?? "0").padStart(2, "0");
  return `${h}:${m}:${s}`;
}

function bookingClockHms(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

/**
 * Best-effort client checks mirroring BookingCreationValidator::validateBranchAvailability.
 * Server validation remains authoritative.
 */
export function validateClientBranchAvailability(
  rule: MobileBranchBookingAvailability,
  startsAt: Date,
  now: Date = new Date(),
): { ok: true } | { ok: false; message: string } {
  if (!rule.is_booking_enabled) {
    return { ok: false, message: "Booking is currently disabled for this branch." };
  }

  const minAllowedMs = now.getTime() + rule.min_advance_minutes * 60_000;
  if (startsAt.getTime() < minAllowedMs) {
    return {
      ok: false,
      message: `Choose a time at least ${rule.min_advance_minutes} minutes from now.`,
    };
  }

  const latestAllowed = new Date(now);
  latestAllowed.setDate(latestAllowed.getDate() + rule.max_advance_days);
  latestAllowed.setHours(23, 59, 59, 999);
  if (startsAt.getTime() > latestAllowed.getTime()) {
    return {
      ok: false,
      message: `Bookings can only be made up to ${rule.max_advance_days} days in advance.`,
    };
  }

  const iso = dayOfWeekIso(startsAt);
  if (!isWeekdayAllowed(rule, iso)) {
    return { ok: false, message: "Bookings are not available on this weekday." };
  }

  const bookingTime = bookingClockHms(startsAt);
  const open = normalizeTimeToHms(rule.open_time);
  const close = normalizeTimeToHms(rule.close_time);

  if (open !== null && bookingTime < open) {
    return { ok: false, message: "Booking start time is before this branch opens." };
  }

  if (close !== null && bookingTime > close) {
    return { ok: false, message: "Booking start time is after this branch closes." };
  }

  return { ok: true };
}

/** Human-readable lines for branch cards (details + create booking). */
export function formatBookingAvailabilitySummary(
  rule: MobileBranchBookingAvailability | null,
): string[] {
  if (rule === null) {
    return [
      "No branch-specific availability rule on file; the server still validates your booking time.",
    ];
  }
  if (!rule.is_booking_enabled) {
    return ["Booking is currently disabled for this branch."];
  }
  const lines: string[] = [];
  const open = rule.open_time;
  const close = rule.close_time;
  if (open && close) {
    const o = open.length >= 5 ? open.slice(0, 5) : open;
    const c = close.length >= 5 ? close.slice(0, 5) : close;
    lines.push(`Booking window: ${o} – ${c}`);
  } else if (open || close) {
    lines.push(`Hours: ${open ?? "—"} – ${close ?? "—"}`);
  }
  lines.push(`Min advance: ${rule.min_advance_minutes} minutes`);
  lines.push(`Max advance: ${rule.max_advance_days} days`);
  lines.push(`Duration (label): ${rule.booking_duration_minutes} min`);
  const labels = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
  const keys = ["mon", "tue", "wed", "thu", "fri", "sat", "sun"] as const;
  const active = keys
    .map((k, i) => (rule[k] ? labels[i] : null))
    .filter((x): x is string => x !== null);
  lines.push(`Open weekdays: ${active.length ? active.join(", ") : "none"}`);
  return lines;
}
