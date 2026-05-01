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

### Phase 11B: event booking link foundation

Allows dashboard/manual bookings to optionally link to a published `RestaurantEvent`:

- Migration: adds nullable `bookings.restaurant_event_id` FK → `restaurant_events.id` (`nullOnDelete`)
- Relationships:
  - `Booking` belongsTo `RestaurantEvent`
  - `RestaurantEvent` hasMany `Booking`
- Validation when `restaurant_event_id` is provided:
  - event must belong to selected restaurant
  - status must be `published`
  - booking_mode must be `normal_booking` or `event_booking` (reject `info_only`)
  - if event has `branch_id`, booking `branch_id` must match
  - if event is restaurant-wide (`branch_id=null`), allow any branch under same restaurant
  - capacity: sum party_size for linked bookings in statuses `pending, accepted, arrived, seated` must not exceed capacity
- Filament: optional Event select on booking create/edit in both panels + toggleable Event column on booking lists

### Explicit non-goals (Phase 11B)

- No mobile UI
- No mobile API endpoints
- No payments or ticketing
- No forcing booking `starts_at` to the event time

### Phase 11C: event booking operations polish

Make event operations usable from the dashboard:

- Add **Bookings** relation manager under RestaurantEvent in both panels
  - Shows bookings linked to the event (`bookings.restaurant_event_id`)
  - Read-only table (no delete / no bulk delete); optional navigation to existing booking edit page
- Add capacity summary (read-only) on event view/edit:
  - Active reserved seats: sum `party_size` for linked bookings in statuses `pending, accepted, arrived, seated`
  - Remaining seats: `capacity - active_reserved_seats` (or “Unlimited” when capacity is null)

### Explicit non-goals (Phase 11C)

- No mobile UI
- No mobile API endpoints
- No payments or ticketing
- No booking lifecycle rule changes
- No custom dashboards/widgets

### Filament view-page consistency polish

- Standardize View pages to use consistent native Filament infolist layout:
  - Sections + 2-column grids for core details
  - Badges for status-like fields
  - Full-width readable long text fields (description/notes/message bodies)
  - Relation managers remain below the details area
- No functional changes (scoping/permissions/actions unchanged)

### Phase 12A: offers dashboard foundation (dashboard-only)

Adds restaurant offers management in dashboards using native Filament resources only:

- Model/table: `RestaurantOffer` → `restaurant_offers`
- Offer belongs to a restaurant; branch is optional but must belong to the selected restaurant.
- Status: `draft|published|expired|cancelled` (default `draft`)
- Offer type: `text_only|percentage|fixed_amount` (default `text_only`)
- Type rules:
  - `percentage`: `discount_value` required, 1–100
  - `fixed_amount`: `discount_value` required, > 0
  - `text_only`: `discount_value` nullable/ignored
- Date rule: `ends_at` must be after `starts_at` when both provided
- Platform panel:
  - Full CRUD over all offers
  - Table columns: title, restaurant, branch, status, offer type, discount, starts_at, ends_at
  - Filters: status, restaurant, branch, offer type, active now (simple)
- Restaurant panel (scoped):
  - `restaurant_owner`: manage restaurant-wide offers (`branch_id=null`) and branch-scoped offers within assigned restaurant(s)
  - `branch_manager` / `restaurant_host`: manage branch-scoped offers only (restaurant-wide offers are hidden/denied)
  - Out-of-scope direct URLs denied/unavailable

### Explicit non-goals (Phase 12A)

- No mobile UI
- No mobile API endpoints
- No coupon/redemption/claiming logic
- No customer offer claiming
- No cover image upload
- No custom dashboards/widgets/cards/stats

### Phase 12B: offers approval workflow + view polish (dashboard-only)

Builds on Phase 12A offers foundation to add:

- Polished Filament view pages for offers (native Sections/Grids, consistent with Restaurant/Event view style)
- Approval workflow:
  - Statuses: `draft|pending_review|published|rejected|expired|cancelled`
  - Platform roles can approve/reject pending offers and cancel offers
  - Restaurant roles can create/edit in-scope offers but cannot publish directly (submit for review only)
- Expiry foundation:
  - Artisan command `offers:expire` marks `published` offers with `ends_at < now()` as `expired`
  - No queue and no scheduler wiring required in this phase
- Analytics note:
  - Customer view/click analytics will be implemented when mobile offers API/UI is introduced; dashboard-only offers have no real customer view counts yet.

### Explicit non-goals (Phase 12B)

- No mobile UI
- No mobile API endpoints
- No offer view/click tracking yet
- No payments, coupons, redemption, or customer claiming

### Phase 13A: stories dashboard foundation (dashboard-only)

Adds restaurant stories management in dashboards using native Filament resources only:

- Model/table: `RestaurantStory` → `restaurant_stories`
- Story belongs to a restaurant; branch is optional but must belong to the selected restaurant.
- Legacy container fields (`story_type`, `media_url`, `body`) exist for backward compatibility and older demos/tests (no requirement that these remain the primary authoring path long-term).
- Status workflow: `draft|pending_review|published|rejected|expired|cancelled`
- Date rule: `ends_at` must be after `starts_at` when both provided
- Platform panel:
  - Full CRUD over all stories
  - Workflow actions: approve/reject pending review; cancel
  - Table columns: title, restaurant, branch, story type, status, starts_at, ends_at, display_order
  - Filters: status, restaurant, branch (simple), story type, active now (simple)
- Restaurant panel (scoped):
  - `restaurant_owner`: manage restaurant-wide (`branch_id=null`) and branch-scoped stories within assigned restaurant(s)
  - `branch_manager` / `restaurant_host`: manage branch-scoped stories only (restaurant-wide stories are hidden/denied)
  - Actions: submit for review; cancel
- Expiry foundation:
  - Artisan command `stories:expire` marks `published` stories with `ends_at < now()` as `expired`
  - No queue and no scheduler wiring required in this phase

### Explicit non-goals (Phase 13A)

- No mobile UI
- No mobile API endpoints
- No story view analytics or customer-facing story viewing
- No custom dashboards/widgets/cards/stats

### Phase 13B: stories uploads + multi-item architecture (dashboard-only)

Builds on Phase 13A story scoping/workflow without changing restaurant/staff visibility rules:

- Schema:
  - Add `lifetime_mode` + optional `lifetime_hours` fields on `restaurant_stories`
  - Add `restaurant_story_items` table + `RestaurantStoryItem` model (`image|video|text`, uploads + optional text slides)
- Filament:
  - Relation managers (`StoryItemsRelationManager`) on story edit/view pages in **both** panels
  - Story view infolist previews slides (images + HTML `<video>` preview for uploads)
  - Publishing guardrails: cannot approve/publish/submit-for-review without renderable content (items **or** legacy container fields)
- Storage:
  - `public` disk uploads under `stories/{story_id}`
  - Local dev docs emphasize `php artisan storage:link`

### Explicit non-goals (Phase 13B)

- No mobile UI / no mobile story APIs
- No customer-facing story viewer
- No story analytics/impressions
- No transcoding/thumbnails/CDN pipeline

### Phase 14A: customer reviews dashboard foundation (dashboard-only)

Adds customer reviews management in Platform and Restaurant Filament panels using native Filament resources only:

- Model/table: `RestaurantReview` → `restaurant_reviews`
- Fields: `restaurant_id`, `branch_id?`, `booking_id?`, `user_id?`, `customer_name?`, `customer_phone?`, `rating`, `comment?`, `status`, `source`, `admin_notes?`, timestamps
- Validation (model-layer):
  - `rating` is required and must be an integer 1–5
  - `status` must be one of `pending_review|published|rejected|hidden` (default `pending_review`)
  - `source` must be one of `dashboard|mobile|import` (default `dashboard`)
  - Optional `branch_id` must belong to the selected restaurant
  - Optional `booking_id` must belong to the selected restaurant; if a branch is selected, the booking branch must match
  - When a booking is linked and review `user_id` is blank, infer `user_id` from `bookings.customer_id`
- Platform panel:
  - Full CRUD over all reviews
  - Native moderation actions: **Approve**, **Reject**, **Hide** (per-row, no bulk moderation)
  - Filters: status, source, rating, restaurant, branch
- Restaurant panel (scoped, read-only):
  - `restaurant_owner`: assigned restaurant reviews including restaurant-wide and branch-scoped
  - `branch_manager` / `restaurant_host`: branch-scoped reviews only (restaurant-wide reviews are hidden/denied)
  - No create/edit/delete and no moderation actions (View action only)
  - Out-of-scope direct URLs return 403/404
- Scoping helper: `RestaurantPanelScope::reviews(User $user)`
- Demo data: two small idempotent demo reviews seeded (one published restaurant-wide for Restaurant A, one pending branch-scoped for Restaurant B)

### Explicit non-goals (Phase 14A)

- No mobile UI (Filament/dashboard-only capture and moderation in Phase 14A)
- Phase 14A did **not** include mobile REST endpoints (those ship in Phase 14B)
- No analytics/stats/widgets
- No AI moderation
- No review notifications
- No bulk moderation
- No CSV import

### Phase 14B: mobile customer reviews API (backend-only)

Adds authenticated mobile REST endpoints for customers to submit and read reviews (same middleware stack as existing `/api/mobile/*` routes):

- **POST `/api/mobile/bookings/{booking}/review`**: customer submits `{ rating, comment? }` for their own booking; booking must be **`completed`**; infers `restaurant_id`, `branch_id`, `booking_id`, `user_id`, snapshots `customer_name` / optional `customer_phone`; **`status=pending_review`**, **`source=mobile`**; duplicate booking rejected with **422**; enforced **unique `booking_id`** at DB level
- **GET `/api/mobile/me/reviews`**: paginated list of authenticated customer’s reviews (full mobile review resource: restaurant, branch, booking_id, rating, comment, status, source, timestamps)
- **GET `/api/mobile/me/reviews/{review}`**: same full shape for **own** review or **404**
- **GET `/api/mobile/restaurants/{restaurant:slug}/reviews`**: **active** restaurant only (else **404**); **published** reviews only; response includes **only** `id`, `customer_name`, `rating`, `comment`, `created_at` (no phone, admin notes, status, source, booking_id)
- Ownership: wrong booking or wrong review returns **404** (match existing bookings mobile API)

### Explicit non-goals (Phase 14B)

- No mobile app UI/screens for reviews yet
- No Filament/dashboard UX changes required for this phase
- No analytics, AI moderation, or notifications on review status changes
- No public HTML pages / guest web listings beyond the JSON API above
- No customer-facing approve/reject/hide endpoints

### Phase 15A: complaints / support tickets dashboard foundation (dashboard-only)

Adds `SupportTicket` → `support_tickets` with native Filament in Platform + Restaurant panels:

- Fields: nullable `restaurant_id` / `branch_id` / `booking_id` / `user_id`; optional `customer_name` / `customer_phone`; required `subject`; `category` (`general|booking|restaurant|payment|app|other`); `priority` (`low|normal|high|urgent`); `status` (`open|in_progress|resolved|closed`); `source` (`dashboard|mobile|phone|whatsapp|import`); `message` / `internal_notes`; `resolved_at` / `closed_at`
- Validation (model-layer): platform-level ticket = `restaurant_id` null ⇒ `branch_id` and `booking_id` must be null; **`booking_id` requires `restaurant_id`** and booking must belong to restaurant; branch must belong to restaurant; if both branch and booking, booking’s branch matches; infer `user_id` from `booking.customer_id`; prefill empty customer fields from linked `User`; auto-set `resolved_at` / `closed_at` when status becomes resolved/closed if still null; **reopen** → `open` without clearing audit timestamps
- Platform: full CRUD; table filters (status, priority, category, restaurant, branch, source); row actions: mark in progress, resolve, close, reopen
- Restaurant: **read-only** list + view; scoped with `RestaurantPanelScope::supportTickets(User $user)` (same branching pattern as reviews/offers)
- Demo seed: one open booking-related ticket for Demo Restaurant A (after `BookingDemoSeeder`), one resolved general ticket for Demo Restaurant B

### Explicit non-goals (Phase 15A)

- No mobile UI or API for tickets
- No customer submission flow
- No notifications or call-center automation
- No analytics/widgets
- No message threads / replies
- No restaurant-side workflow or internal-notes editing

### Phase 15B: support ticket activity timeline + internal notes (dashboard-only)

Adds `SupportTicketActivity` → `support_ticket_activities` with Filament-native timeline on **platform** tickets only:

- Columns: required `support_ticket_id` (FK cascade); nullable `user_id` (nullOnDelete); `type` (`note|status_change`; `system` reserved unused); nullable `old_status` / `new_status`; nullable `message`; `is_internal` default true; timestamps
- `SupportTicketActivityService`: `recordNote(SupportTicket, User, message)`, `recordStatusChange(SupportTicket, ?User, ?old, new)`
- `SupportTicketObserver::updated()` writes **status_change** rows using `getChanges()` / `getPrevious()` (no rows on initial insert); reopen behavior unchanged at ticket level (`resolved_at` / `closed_at` retained)
- Platform `SupportTicketResource`: relation manager **Internal activity** — append-only table + header **Add internal note** (`CreateAction`, required message); `$isLazy = false` on relation manager so actions resolve reliably in Livewire tests
- Restaurant panel: unchanged **read-only** tickets — **no** relation manager / timeline / notes UI

### Explicit non-goals (Phase 15B)

- No mobile UI/API for tickets or notes
- No customer-visible replies or threads
- No notifications or automation
- No analytics/widgets
- No restaurant-visible internal timeline or note entry

### Unified dashboard login entry

- Routes: `GET /login` (Blade sign-in form), `POST /login` (validate + `Auth::attempt` on default **web** guard)
- After successful authentication, redirect by Spatie role precedence: **platform** roles (`super_admin`, `operations_admin`) → `/platform`; else **restaurant** roles (`restaurant_owner`, `branch_manager`, `restaurant_host`) → `/restaurant`; else treat as **no dashboard access** — `Auth::logout()`, invalidate session, redirect to `/login` with flash message (e.g. customer-only accounts)
- Authenticated users hitting `GET /login` are redirected the same way (customers are logged out and sent back with the message)
- Filament panel logins **`/platform/login`** and **`/restaurant/login`** remain unchanged; panel `canAccessPanel` rules unchanged
- Filament **`LogoutResponse`** is bound app-wide so logout from either panel redirects to **`route('dashboard.login')`** (`/login`) instead of the panel-local login URL
- No Sanctum, mobile OTP, or API route changes

