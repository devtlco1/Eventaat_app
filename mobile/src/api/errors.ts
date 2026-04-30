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

