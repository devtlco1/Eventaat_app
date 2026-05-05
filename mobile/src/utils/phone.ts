const ARABIC_DIGITS = "٠١٢٣٤٥٦٧٨٩";
const PERSIAN_DIGITS = "۰۱۲۳۴۵۶۷۸۹";

function normalizeDigits(s: string): string {
  return s
    .split("")
    .map((c) => {
      const ai = ARABIC_DIGITS.indexOf(c);
      if (ai !== -1) return String(ai);
      const pi = PERSIAN_DIGITS.indexOf(c);
      if (pi !== -1) return String(pi);
      return c;
    })
    .join("");
}

/**
 * Normalizes an Iraqi phone number to E.164 (+9647xxxxxxxxx).
 * Accepts: 07xxxxxxxxx, 7xxxxxxxxx, +9647xxxxxxxxx, 9647xxxxxxxxx,
 *          as well as Arabic/Persian digit variants.
 * Returns the normalized form, or the stripped input if it doesn't match.
 */
export function normalizeIraqPhone(raw: string): string {
  const stripped = normalizeDigits(raw).replace(/[\s\-().]/g, "");

  if (stripped.startsWith("+9647")) return stripped;
  if (stripped.startsWith("9647")) return `+${stripped}`;
  if (stripped.startsWith("07")) return `+964${stripped.slice(1)}`;
  if (stripped.startsWith("7") && stripped.length === 10) return `+964${stripped}`;

  return stripped;
}

/** Returns true if normalized value is a valid Iraqi mobile number. */
export function isValidIraqPhone(normalized: string): boolean {
  return /^\+9647\d{9}$/.test(normalized);
}
