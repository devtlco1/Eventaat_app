import { API_BASE_URL } from "../config/env";
import type { ApiError } from "./types";

export class ApiErrorResponse extends Error {
  status: number;
  details?: unknown;

  constructor(err: ApiError) {
    super(err.message);
    this.name = "ApiErrorResponse";
    this.status = err.status;
    this.details = err.details;
  }
}

const DEFAULT_TIMEOUT_MS = 10_000;

type ClientOptions = {
  token?: string | null;
  timeoutMs?: number;
};

export async function apiRequest<T>(
  path: string,
  init: RequestInit,
  options: ClientOptions = {}
): Promise<T> {
  const url = `${API_BASE_URL}${path.startsWith("/") ? "" : "/"}${path}`;

  const headers: Record<string, string> = {
    Accept: "application/json",
    ...(init.headers as Record<string, string> | undefined),
  };

  if (init.body && !headers["Content-Type"]) {
    headers["Content-Type"] = "application/json";
  }

  const token = options.token;
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const controller = new AbortController();
  const timeoutMs = options.timeoutMs ?? DEFAULT_TIMEOUT_MS;
  const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

  let res: Response;
  try {
    res = await fetch(url, { ...init, headers, signal: controller.signal });
  } catch (e) {
    if (controller.signal.aborted) {
      throw new ApiErrorResponse({
        status: 0,
        message: "Request timed out. Check your network connection.",
      });
    }
    // Wrap raw network errors (e.g. wrong host, no connection) so callers
    // always receive ApiErrorResponse and get the helpful status-0 message.
    throw new ApiErrorResponse({
      status: 0,
      message:
        e instanceof Error ? e.message : "Network request failed.",
    });
  } finally {
    clearTimeout(timeoutId);
  }

  const text = await res.text();
  const json = text ? safeJsonParse(text) : null;

  if (!res.ok) {
    const message =
      (json && typeof json === "object" && "message" in json
        ? // eslint-disable-next-line @typescript-eslint/no-explicit-any
          (json as any).message
        : null) ?? `Request failed (${res.status}).`;

    throw new ApiErrorResponse({
      status: res.status,
      message,
      details: json ?? text,
    });
  }

  return (json as T) ?? ({} as T);
}

function safeJsonParse(input: string): unknown {
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}
