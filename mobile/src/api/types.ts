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

export type MobileRestaurantListItem = {
  id: number;
  name: string;
  slug: string;
  active_branches_count: number;
};

export type MobileRestaurantTable = {
  id: number;
  label: string;
  capacity: number;
};

export type MobileSeatingArea = {
  id: number;
  name: string;
  type: string | null;
  tables: MobileRestaurantTable[];
};

export type MobileBranch = {
  id: number;
  name: string;
  code: string;
  seating_areas: MobileSeatingArea[];
};

export type MobileRestaurantDetails = {
  id: number;
  name: string;
  slug: string;
  branches: MobileBranch[];
};

export type MobileBooking = {
  id: number;
  status: string | null;
  starts_at: string | null;
  party_size: number;
  customer_note: string | null;
  restaurant_note: string | null;
  accepted_at: string | null;
  rejected_at: string | null;
  cancelled_at: string | null;
  arrived_at: string | null;
  seated_at: string | null;
  completed_at: string | null;
  no_show_at: string | null;
  restaurant?: { id: number; name: string; slug: string };
  branch?: { id: number; name: string; code: string };
  seating_area?: { id: number; name: string; code: string; type: string | null } | null;
  table?: { id: number; label: string; capacity: number } | null;
  created_at: string | null;
  updated_at: string | null;
};

export type LaravelValidationError = {
  message?: string;
  errors?: Record<string, string[]>;
};

