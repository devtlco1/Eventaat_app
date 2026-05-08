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
  avg_rating: number | null;
  review_count: number;
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

/** Mirrors GET /api/mobile/restaurants/{slug} branch.booking_availability (customer-safe fields only). */
export type MobileBranchBookingAvailability = {
  is_booking_enabled: boolean;
  booking_duration_minutes: number;
  min_advance_minutes: number;
  max_advance_days: number;
  open_time: string | null;
  close_time: string | null;
  mon: boolean;
  tue: boolean;
  wed: boolean;
  thu: boolean;
  fri: boolean;
  sat: boolean;
  sun: boolean;
};

export type MobileBranch = {
  id: number;
  name: string;
  code: string;
  booking_availability: MobileBranchBookingAvailability | null;
  seating_areas: MobileSeatingArea[];
};

export type MobileRestaurantDetails = {
  id: number;
  name: string;
  slug: string;
  avg_rating: number | null;
  review_count: number;
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

export type MobilePublicReview = {
  id: number;
  customer_name: string;
  rating: number;
  comment: string | null;
  created_at: string | null;
};

export type MobileOffer = {
  id: number;
  title: string;
  description: string | null;
  offer_type: string;
  discount_value: string | null;
  status: string;
  starts_at: string | null;
  ends_at: string | null;
  image_url: string | null;
  terms: string | null;
};

export type MobileStory = {
  id: number;
  title: string;
  story_type: string;
  status: string;
  media_url: string | null;
  thumbnail_url: string | null;
  display_order: number;
};

export type MobileEvent = {
  id: number;
  title: string;
  slug: string;
  restaurant?: { id: number; name: string } | null;
  branch?: { id: number; name: string } | null;
  status: string;
  booking_mode: string;
  starts_at: string | null;
  ends_at: string | null;
  price_label: string | null;
  capacity: number | null;
  active_reserved_seats: number;
  remaining_seats: number | null;
  description: string | null;
};

export type MobileMenuItem = {
  id: number;
  name: string;
  description: string | null;
  price: string | null;
  currency: string;
  image_url: string | null;
  is_available: boolean;
  is_featured: boolean;
  display_order: number;
};

export type MobileMenuCategory = {
  id: number;
  name: string;
  display_order: number;
  items: MobileMenuItem[];
};

export type MobileMenu = {
  id: number;
  title: string;
  slug: string;
  mode: string;
  status: string;
  display_order: number;
  pdf_url: string | null;
  external_url: string | null;
  categories: MobileMenuCategory[];
};

export type MobileMyReview = {
  id: number;
  restaurant?: { id: number; name: string; slug: string } | null;
  branch?: { id: number; name: string; code: string } | null;
  booking_id: number | null;
  rating: number;
  comment: string | null;
  status: string;
  source: string;
  created_at: string | null;
  updated_at: string | null;
};

