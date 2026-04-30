import { apiRequest } from "./client";
import type { MobileMe } from "./types";

export async function requestOtp(phone: string): Promise<{
  success: boolean;
  expires_at?: string;
}> {
  return apiRequest("/api/mobile/auth/request-otp", {
    method: "POST",
    body: JSON.stringify({ phone }),
  });
}

export async function verifyOtp(params: {
  phone: string;
  otp: string;
  name?: string;
}): Promise<{ token: string; me: MobileMe }> {
  return apiRequest("/api/mobile/auth/verify-otp", {
    method: "POST",
    body: JSON.stringify(params),
  });
}

export async function getMe(token: string): Promise<MobileMe> {
  return apiRequest("/api/mobile/me", { method: "GET" }, { token });
}

export async function updateMe(
  token: string,
  params: { name: string }
): Promise<{ me: MobileMe }> {
  return apiRequest(
    "/api/mobile/me",
    { method: "PATCH", body: JSON.stringify(params) },
    { token }
  );
}

export async function logout(token: string): Promise<{ success: boolean }> {
  return apiRequest(
    "/api/mobile/auth/logout",
    { method: "POST", body: JSON.stringify({}) },
    { token }
  );
}

