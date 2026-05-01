## Implementation plan

Source of truth: `docs/eventaat_blueprint_v1.md`.

### Phase 0 goal

Create a clean Laravel + Filament foundation using PostgreSQL, with two Filament panels only:

- `/platform`
- `/restaurant`

### Deliverables (Phase 0)

- Laravel app initialized in `backend/`
- PostgreSQL configured in `backend/.env` and `backend/.env.example`
- Filament installed and configured
- Two panels created:
  - Platform: `/platform`
  - Restaurant: `/restaurant`
- Local Filament user creation workflow (`php artisan make:filament-user`)
- Documentation updated

### Explicit non-goals (Phase 0)

- No restaurant resources/models
- No bookings
- No mobile app
- No custom dashboard UI (cards, stats, widgets, mock pages)
- No links to unimplemented modules

### Phase 1 goal

Implement real authentication and **role-based panel access** (no restaurant resources yet).

### Deliverables (Phase 1)

- Spatie roles as the **single source of truth**
- Roles created:
  - Platform: `super_admin`, `operations_admin`
  - Restaurant: `restaurant_owner`, `branch_manager`, `restaurant_host`
  - Customer: `customer`
- Filament access enforced via `User::canAccessPanel(Panel $panel)`
- Idempotent local test data seeder: `RolesAndTestUsersSeeder`
- Automated tests for panel access matrix

### Explicit non-goals (Phase 1)

- No restaurant/branch/table/booking models or resources
- No mobile app work
- No API endpoints
- No custom dashboard UI/widgets/cards/stats

### Phase 2 goal

Add the core restaurant operational foundation (models + Filament resources), with strict scoping between Platform and Restaurant panels.

### Deliverables (Phase 2)

- Models: Restaurant, Branch, SeatingArea, RestaurantTable, RestaurantStaffAssignment
- String-backed enums for statuses/types/roles
- Filament resources for **both** panels (native only)
- Restaurant panel scoping backed by `RestaurantStaffAssignment`
- Idempotent demo data seeder
- Focused tests for scoping (index + direct record URL)

### Explicit non-goals (Phase 2)

- No bookings
- No mobile app work
- No mobile/customer API endpoints
- No custom dashboard UI/widgets/cards/stats

### Phase 3 goal

Create a customer mobile authentication REST API foundation (OTP + tokens), without adding bookings or restaurant discovery.

### Deliverables (Phase 3)

- Mobile OTP request + verify endpoints
- Customer-only API tokens using Laravel Sanctum
- `GET /api/mobile/me` and `PATCH /api/mobile/me` profile foundation (name only)
- Ensure mobile-created users are always `customer`
- Tests for OTP flow, token auth, profile completion behavior, and panel lockout

### Explicit non-goals (Phase 3)

- No mobile UI
- No WhatsApp/SMS provider integration (local/dev OTP only)
- No bookings
- No restaurant discovery endpoints

### Phase 4A goal

Add authenticated mobile customer **restaurant discovery API** (list + detail) using Phase 2 restaurant foundation data.

### Deliverables (Phase 4A)

- `GET /api/mobile/restaurants` (active only, search + pagination)
- `GET /api/mobile/restaurants/{restaurant:slug}` (active only, nested branches/seating/tables)
- Tests for auth + active-only visibility + nesting + no bookings endpoint
- API docs updated with request/response examples

### Explicit non-goals (Phase 4A)

- No bookings endpoints or booking creation
- No mobile UI
- No Filament dashboard/UI changes

### Phase 4B goal

Create an Expo React Native mobile app foundation in `mobile/` and implement **customer auth UI only** (no restaurant discovery UI yet).

### Deliverables (Phase 4B)

- Expo app initialized in `mobile/` (TypeScript)
- Clean structure: `src/api`, `src/auth`, `src/screens`, `src/components`, `src/navigation`, `src/config`
- API base URL via `EXPO_PUBLIC_API_BASE_URL` (example in `mobile/.env.example`)
- Secure token storage using `expo-secure-store`
- Auth flow screens:
  - Phone entry
  - OTP verification
  - Complete profile (name only)
  - Authenticated home placeholder + logout

### Explicit non-goals (Phase 4B)

- No mobile restaurant discovery UI (home remains placeholder)
- No bookings
- No backend API changes
- No UI kits / Redux / Zustand / design system

### Mobile UI foundation (post Phase 4B) — implemented

Mobile customer UI now includes:

- Restaurant discovery UI:
  - list: `GET /api/mobile/restaurants`
  - details: `GET /api/mobile/restaurants/{restaurant:slug}`
- Booking UI:
  - create: `POST /api/mobile/bookings` (supports with and without table)
  - list: `GET /api/mobile/bookings`
  - details: `GET /api/mobile/bookings/{booking}`
  - cancel: `POST /api/mobile/bookings/{booking}/cancel`

Notes:
- UI uses React Native core components only (no UI kits).
- If API returns 401/403, the app clears token and returns to the auth flow.
- Phase 7A polish:
  - Date/time picker used for `starts_at` (format sent as `YYYY-MM-DD HH:mm`)
  - Better status badges + loading/empty states across booking screens

### Phase 5A goal

Add the first booking lifecycle foundation:
- Customer creates booking requests via API
- Restaurant staff accepts/rejects/cancels via Filament (native actions)
- Platform monitors all bookings via Filament

### Deliverables (Phase 5A)

- Booking model + `BookingStatus` enum (`pending|accepted|rejected|cancelled`)
- Bookings migration (FKs + indexes)
- Shared transition logic (accept/reject/cancel) used by Filament + API
- Platform `/platform` BookingResource (native tables/forms/actions/badges only)
- Restaurant `/restaurant` BookingResource with strict scoping via `RestaurantStaffAssignment`
- Mobile customer bookings API:
  - `POST /api/mobile/bookings`
  - `GET /api/mobile/bookings`
  - `GET /api/mobile/bookings/{booking}`
  - `POST /api/mobile/bookings/{booking}/cancel`
- Tests for API + ownership + scoping + transitions
- API docs updated

### Explicit non-goals (Phase 5A)

- No day-of statuses (`arrived`, `seated`, `completed`, `no_show`)
- No reschedule/update booking endpoint
- No availability engine
- No payments/deposits
- No notifications / WhatsApp integration
- No mobile UI work
- No custom dashboards/widgets/cards/stats

### Phase 5B (polish): Filament resource hierarchy usability

Non-feature usability step to make the hierarchy clearer in Filament using native components only:
- add hierarchy columns (Restaurant/Branch/Seating Area/Table) where relevant
- add native filters for Restaurant/Branch/Status and a simple date filter for bookings
- improve forms with dependent selects (Restaurant → Branch → Seating Area)
- add relation managers on the Platform panel for Restaurant → Branches → Seating Areas → Tables

### Phase 6: day-of booking operations

Adds visit-day operational statuses and actions for bookings:
- Statuses: `arrived`, `seated`, `completed`, `no_show`
- Nullable timestamps: `arrived_at`, `seated_at`, `completed_at`, `no_show_at`
- Native Filament actions in both panels for day-of transitions (Restaurant panel remains strictly scoped)

### Operational improvement: manual booking creation in Filament

Adds manual booking creation in Filament for operational/testing use:
- Phone-first customer input (`customer_phone` required, `customer_name` optional)
- Creates/reuses customer users by phone (customer role only)
- Dependent selects: Restaurant → Branch → Seating Area → Table
- Reuses the same validation and conflict-prevention rules as the mobile booking API

### Phase 8A: branch booking availability rules

Adds practical branch-level booking constraints shared across mobile booking creation and Filament manual booking creation:

- Model `BranchAvailabilityRule` (**one row per branch**, optional — branches without a rule keep legacy behavior)
- Filament relation manager **Booking availability** on Branch resources (Platform + Restaurant panels), respecting restaurant panel branch scoping
- Validation lives in `BookingCreationValidator`: booking disabled flag, min/max advance window, weekday toggles, optional daily open/close window (single shift)
- Demo seed attaches a **default rule** to demo branches (10:00–23:00, all weekdays, min advance 60 minutes, max advance 30 days, duration 90 minutes stored for future use)

### Explicit non-goals (Phase 8A)

- No holidays/exceptions, multi-shift hours, slot grids, or capacity-based availability engines
- No dedicated mobile availability picker UI (API returns normal `422` validation messages)

### Phase 8B: mobile availability UX + booking form guidance

- Extends **`GET /api/mobile/restaurants/{slug}`** only: each branch includes **`booking_availability`** (nullable object with `is_booking_enabled`, advance limits, `open_time` / `close_time`, weekday booleans) or **`null`** when no rule; excludes internal fields (`notes`, ids, timestamps)
- Mobile: restaurant details + create booking show branch rule summaries; **client-side checks** mirror `BookingCreationValidator` where practical (disabled branch, min/max advance, weekday, open/close); **422 from `POST /api/mobile/bookings`** remains authoritative
- No new booking endpoint; **no change** to `BookingCreationValidator` logic

### Phase 9A: booking notification foundation

Adds internal lifecycle notification rows only (no outbound channels):

- Migration `booking_notifications`: required `booking_id` FK to `bookings.id` with `cascadeOnDelete`; nullable `user_id` with `nullOnDelete`; event string; title/message/payload JSON; channel default `internal`; status default `pending`
- Model `App\Models\BookingNotification` and service `App\Services\Notifications\BookingNotificationService` (centralized copy + payload; `report()` on insert failure without breaking bookings)
- Recording hooks: mobile `POST /api/mobile/bookings`, `ManualBookingCreationService`, and each successful path in `BookingTransitionService` (invalid transitions create no rows)
- Filament Platform read-only **Booking notifications** resource visible to `super_admin` and `operations_admin` only

### Explicit non-goals (Phase 9A)

- No WhatsApp, SMS, push, or email sending
- No queues, workers, retries, send actions, restaurant-panel notification UI, mobile notification UI, notification APIs, or custom dashboards/widgets/stats

### Phase 9B: notification templates + preview

Adds configurable templates (no outbound delivery) so each booking lifecycle event produces predictable internal copy:

- Model/table: `NotificationTemplate` → `notification_templates`
- Templates are keyed by a unique `event` for this phase; `channel=internal`, `locale=en`
- Simple placeholder rendering with `{{placeholders}}`:
  - Supported: `customer_name`, `customer_phone`, `restaurant_name`, `branch_name`, `booking_id`, `booking_status`, `starts_at`, `party_size`
  - Unknown placeholders remain unchanged (never crash)
- `BookingNotificationService` uses an active template when present; otherwise falls back to Phase 9A hardcoded copy
- Platform Filament: **Notification templates** resource (CRUD) with a preview modal showing rendered output from a sample payload

### Explicit non-goals (Phase 9B)

- No WhatsApp/SMS/push/email sending
- No queues/workers/retries
- No restaurant-panel notification UI
- No mobile notification UI
- No API changes or notification endpoints

### Phase 9C: notification dispatch foundation (internal-only)

Adds safe, internal-only dispatch state transitions for the notification outbox (no external delivery):

- New service: `App\Services\Notifications\NotificationDispatchService`
- Channel support: `internal` only
- Supported transitions (final for this phase):
  - `pending -> sent` (sets `sent_at`, clears `failed_at` / `failure_reason`)
  - `pending -> skipped` (clears `sent_at`, clears `failed_at` / `failure_reason`)
  - `pending -> failed` (sets `failed_at` + `failure_reason`, clears `sent_at`)
- Non-pending rows (`sent`, `skipped`, `failed`) are final: transition methods return `false` and do not modify the row.
- Platform Filament **Booking notifications** adds native row actions:
  - Mark sent
  - Mark skipped
  - Mark failed (reason required)

### Explicit non-goals (Phase 9C)

- No WhatsApp/SMS/push/email integration or sending
- No queues/workers/retries
- No API endpoints
- No mobile notification UI

### Phase 10A: notification provider foundation (WhatsApp-ready, dry-run only)

Prepares the notification system for future providers without integrating any external APIs:

- Provider abstraction:
  - `NotificationProvider` interface
  - `NotificationProviderResult` value object (`success`, `provider_message_id`, `failure_reason`)
  - `InternalDryRunNotificationProvider` (no external calls)
- Attempt tracking:
  - Model/table: `NotificationDispatchAttempt` → `notification_dispatch_attempts`
  - Stores provider/channel/status, request/response payloads, provider message id, failure reason, and `attempted_at`
- Dispatch service:
  - Adds `dispatchInternalDryRun(BookingNotification $notification)`
  - Allowed only for `pending` + `internal`
  - Creates an attempt row
  - Marks notification `sent` on success, or `failed` with reason on failure
  - Non-pending returns `false` and creates no attempt
- Platform Filament:
  - Adds a **Dry-run dispatch** action on Booking notifications (pending/internal only)
  - Booking notification view shows related dispatch attempts (read-only)

### Explicit non-goals (Phase 10A)

- No real WhatsApp/SMS/push/email sending or external API calls
- No queues/workers/retries
- No API endpoints
- No mobile notification UI

### Phase 11A: event nights dashboard foundation

Adds dashboard-only event nights management using native Filament resources:

- Model/table: `RestaurantEvent` → `restaurant_events`
- Required fields: `restaurant_id`, `title`, `slug`, `starts_at`
- Optional: `branch_id` (must belong to restaurant), `ends_at` (must be after `starts_at`), `description`, `capacity`, `price_label`, `notes`
- Status: `draft|published|cancelled|completed` (default `draft`)
- Booking mode: `normal_booking|event_booking|info_only` (default `info_only`)
- Platform panel:
  - Full CRUD over all events
  - Table columns: title, restaurant, branch, starts_at, status, booking_mode
  - Filters: status, restaurant, branch, starts date (simple)
- Restaurant panel (scoped):
  - `restaurant_owner`: manage restaurant-wide events (`branch_id=null`) and branch events
  - `branch_manager` / `restaurant_host`: manage branch-scoped events only (`branch_id` required)
  - Out-of-scope direct URLs denied/unavailable

### Explicit non-goals (Phase 11A)

- No mobile screens or event discovery
- No mobile API endpoints
- No offers, stories, payments, ticketing, or event booking flow (Phase 11B)

### Unified dashboard login entry

- Routes: `GET /login` (Blade sign-in form), `POST /login` (validate + `Auth::attempt` on default **web** guard)
- After successful authentication, redirect by Spatie role precedence: **platform** roles (`super_admin`, `operations_admin`) → `/platform`; else **restaurant** roles (`restaurant_owner`, `branch_manager`, `restaurant_host`) → `/restaurant`; else treat as **no dashboard access** — `Auth::logout()`, invalidate session, redirect to `/login` with flash message (e.g. customer-only accounts)
- Authenticated users hitting `GET /login` are redirected the same way (customers are logged out and sent back with the message)
- Filament panel logins **`/platform/login`** and **`/restaurant/login`** remain unchanged; panel `canAccessPanel` rules unchanged
- Filament **`LogoutResponse`** is bound app-wide so logout from either panel redirects to **`route('dashboard.login')`** (`/login`) instead of the panel-local login URL
- No Sanctum, mobile OTP, or API route changes

