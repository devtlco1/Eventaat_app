export type ApiError = {
  status: number;
  message: string;
  details?: unknown;
};

export type MobileMe = {
  id: number;
  name: string | null;
  phone: string;
  role: "customer";
  profile_completed: boolean;
  missing_fields: string[];
};

