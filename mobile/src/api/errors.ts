import { ApiErrorResponse } from "./client";
import type { LaravelValidationError } from "./types";

export function isAuthError(err: unknown): boolean {
  return err instanceof ApiErrorResponse && (err.status === 401 || err.status === 403);
}

export function getValidationErrors(
  err: unknown
): Record<string, string[]> | null {
  if (!(err instanceof ApiErrorResponse)) return null;
  const details = err.details as LaravelValidationError | null;
  if (!details || typeof details !== "object") return null;
  if (!("errors" in details)) return null;
  const errors = (details as LaravelValidationError).errors;
  if (!errors || typeof errors !== "object") return null;
  return errors;
}

export function getErrorMessage(err: unknown): string {
  if (err instanceof ApiErrorResponse) return err.message;
  if (err instanceof Error) return err.message;
  return "Something went wrong.";
}

/**
 * Converts a requestOtp / signUp API error into a user-facing message.
 *
 * status 0   → network/timeout (server unreachable)
 * status 422 → validation error; backend already provides a clear message
 * status 429 → rate-limited; shows retry_after seconds when available
 * other      → backend message or generic fallback
 */
export function getOtpRequestError(e: unknown): string {
  if (!(e instanceof ApiErrorResponse)) return "Failed to request OTP.";

  if (e.status === 0) {
    return "Could not reach the server. Check that the backend is running and the API URL is correct.";
  }

  if (e.status === 429) {
    const ra = (e.details as Record<string, unknown> | null)?.retry_after;
    return typeof ra === "number"
      ? `Too many requests. Try again in ${ra} seconds.`
      : "Too many requests. Please wait before trying again.";
  }

  // 422 and other: Laravel already returns a clear message field.
  return e.message || "Failed to request OTP.";
}

