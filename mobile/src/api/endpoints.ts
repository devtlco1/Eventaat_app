import { apiRequest } from "./client";
import type {
  MobileBooking,
  MobileMe,
  MobileMenu,
  MobileMyReview,
  MobileEvent,
  MobileOffer,
  MobilePublicReview,
  MobileRestaurantDetails,
  MobileRestaurantListItem,
} from "./types";

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

export async function listRestaurants(
  token: string,
  params: { q?: string } = {}
): Promise<{ data: MobileRestaurantListItem[] }> {
  const qs = new URLSearchParams();
  if (params.q) qs.set("q", params.q);

  const path = `/api/mobile/restaurants${qs.toString() ? `?${qs.toString()}` : ""}`;
  return apiRequest(path, { method: "GET" }, { token });
}

export async function getRestaurant(
  token: string,
  slug: string
): Promise<MobileRestaurantDetails> {
  const res = await apiRequest<{ data: MobileRestaurantDetails }>(
    `/api/mobile/restaurants/${encodeURIComponent(slug)}`,
    { method: "GET" },
    { token }
  );
  return res.data;
}

export async function listMyBookings(
  token: string
): Promise<{ data: MobileBooking[] }> {
  return apiRequest("/api/mobile/bookings", { method: "GET" }, { token });
}

export async function getBooking(
  token: string,
  bookingId: number
): Promise<MobileBooking> {
  return apiRequest(`/api/mobile/bookings/${bookingId}`, { method: "GET" }, { token });
}

export async function createBooking(
  token: string,
  params: {
    restaurant_id: number;
    branch_id: number;
    seating_area_id?: number | null;
    restaurant_table_id?: number | null;
    starts_at: string;
    party_size: number;
    customer_note?: string | null;
  }
): Promise<{ booking: MobileBooking }> {
  return apiRequest(
    "/api/mobile/bookings",
    { method: "POST", body: JSON.stringify(params) },
    { token }
  );
}

export async function cancelBooking(
  token: string,
  bookingId: number
): Promise<{ booking: MobileBooking }> {
  return apiRequest(
    `/api/mobile/bookings/${bookingId}/cancel`,
    { method: "POST", body: JSON.stringify({}) },
    { token }
  );
}

export async function submitBookingReview(
  token: string,
  bookingId: number,
  payload: { rating: number; comment?: string | null }
): Promise<{ review: MobileMyReview }> {
  return apiRequest(
    `/api/mobile/bookings/${bookingId}/review`,
    { method: "POST", body: JSON.stringify(payload) },
    { token }
  );
}

export async function listRestaurantReviews(
  token: string,
  slug: string,
  params: { page?: number } = {}
): Promise<{ data: MobilePublicReview[] }> {
  const qs = new URLSearchParams();
  if (params.page) qs.set("page", String(params.page));
  const path = `/api/mobile/restaurants/${encodeURIComponent(slug)}/reviews${qs.toString() ? `?${qs.toString()}` : ""}`;
  return apiRequest(path, { method: "GET" }, { token });
}

export async function listRestaurantOffers(
  token: string,
  slug: string
): Promise<{ data: MobileOffer[] }> {
  return apiRequest(
    `/api/mobile/restaurants/${encodeURIComponent(slug)}/offers`,
    { method: "GET" },
    { token }
  );
}

export async function listRestaurantEvents(
  token: string,
  slug: string
): Promise<{ data: MobileEvent[] }> {
  return apiRequest(
    `/api/mobile/restaurants/${encodeURIComponent(slug)}/events`,
    { method: "GET" },
    { token }
  );
}

export async function listRestaurantMenus(
  token: string,
  slug: string
): Promise<{ data: MobileMenu[] }> {
  return apiRequest(
    `/api/mobile/restaurants/${encodeURIComponent(slug)}/menus`,
    { method: "GET" },
    { token }
  );
}

export async function listMyReviews(
  token: string,
  params: { page?: number } = {}
): Promise<{ data: MobileMyReview[] }> {
  const qs = new URLSearchParams();
  if (params.page) qs.set("page", String(params.page));
  const path = `/api/mobile/reviews${qs.toString() ? `?${qs.toString()}` : ""}`;
  return apiRequest(path, { method: "GET" }, { token });
}

