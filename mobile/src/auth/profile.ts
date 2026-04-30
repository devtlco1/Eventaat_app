import type { MobileMe } from "../api/types";

export function needsName(me: MobileMe | null): boolean {
  if (!me) return false;
  return !me.profile_completed && me.missing_fields.includes("name");
}

