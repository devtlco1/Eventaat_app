import React, { createContext, useCallback, useEffect, useMemo, useState } from "react";
import type { MobileMe } from "../api/types";
import { getMe, logout as apiLogout, verifyOtp as apiVerifyOtp } from "../api/endpoints";
import { clearToken, getToken, setToken } from "./tokenStorage";

type AuthState = {
  isBootstrapping: boolean;
  token: string | null;
  me: MobileMe | null;
};

type AuthContextValue = AuthState & {
  bootstrap: () => Promise<void>;
  verifyOtp: (params: { phone: string; otp: string; name?: string }) => Promise<void>;
  refreshMe: () => Promise<void>;
  logout: () => Promise<void>;
};

export const AuthContext = createContext<AuthContextValue | null>(null);

async function safeClearToken(): Promise<void> {
  try {
    await clearToken();
  } catch {
    // SecureStore errors during cleanup are non-fatal
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [isBootstrapping, setIsBootstrapping] = useState(true);
  const [token, setTokenState] = useState<string | null>(null);
  const [me, setMeState] = useState<MobileMe | null>(null);

  const bootstrap = useCallback(async () => {
    setIsBootstrapping(true);
    try {
      let existing: string | null = null;
      try {
        existing = await getToken();
      } catch {
        // SecureStore unavailable — treat as no token
      }

      if (!existing) {
        setTokenState(null);
        setMeState(null);
        return;
      }

      try {
        const profile = await getMe(existing);
        setTokenState(existing);
        setMeState(profile);
      } catch {
        // Token invalid, expired, or network error (including timeout from apiRequest)
        await safeClearToken();
        setTokenState(null);
        setMeState(null);
      }
    } finally {
      // Always unblock the navigator — even if every await above hangs or throws
      setIsBootstrapping(false);
    }
  }, []);

  useEffect(() => {
    void bootstrap();
  }, [bootstrap]);

  const refreshMe = useCallback(async () => {
    if (!token) return;
    const profile = await getMe(token);
    setMeState(profile);
  }, [token]);

  const verifyOtp = useCallback(
    async (params: { phone: string; otp: string; name?: string }) => {
      const result = await apiVerifyOtp(params);
      await setToken(result.token);
      setTokenState(result.token);
      setMeState(result.me);
    },
    []
  );

  const logout = useCallback(async () => {
    if (token) {
      try {
        await apiLogout(token);
      } catch {
        // ignore
      }
    }
    await safeClearToken();
    setTokenState(null);
    setMeState(null);
  }, [token]);

  const value = useMemo<AuthContextValue>(
    () => ({
      isBootstrapping,
      token,
      me,
      bootstrap,
      verifyOtp,
      refreshMe,
      logout,
    }),
    [bootstrap, isBootstrapping, logout, me, refreshMe, token, verifyOtp]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = React.useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
