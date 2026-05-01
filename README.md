## Eventaat (Phase 15A support tickets dashboard foundation)

This repository is a rebuild of Eventaat following the phased plan in `docs/eventaat_blueprint_v1.md`.

Source of truth: `docs/eventaat_blueprint_v1.md`.

### What exists (Phases 0–10A)

- **Backend**: Laravel app in `backend/`
- **Database**: PostgreSQL configuration (see `backend/.env`)
- **Dashboards**: Filament panels only
  - **Unified web sign-in**: `GET /login` and `POST /login` (email + password, default `web` guard) — redirects by role to `/platform` or `/restaurant`; **customer** accounts are signed out with a clear message (use the mobile app); **Filament panel logout** sends users back to **`/login`** so platform and restaurant staff share one exit/sign-in flow
  - **Platform panel**: `/platform` (also `/platform/login`)
  - **Restaurant panel**: `/restaurant` (also `/restaurant/login`)
- **Docs**: implementation plan and role rules in `docs/`
- **Mobile**: Expo React Native app in `mobile/` (Phase 4B auth UI foundation)
- **Mobile UI (customer)**:
  - Restaurant discovery UI (list + details)
  - Booking UI (create + my bookings + booking details + cancel)
  - Date/time picker for booking start time (no manual typing required)
- **Bookings (Phases 5–6)**:
  - Booking model + statuses: `pending|accepted|rejected|cancelled|arrived|seated|completed|no_show`
  - Filament BookingResource in `/platform` and `/restaurant`
    - Lifecycle actions: Accept / Reject / Cancel / Mark arrived / Mark seated / Mark completed / Mark no-show
    - Manual booking creation (phone-first) in both panels
  - Customer bookings API (create/list/detail/cancel)
- **Branch booking availability (Phase 8A)**:
  - One `BranchAvailabilityRule` per branch (optional row): enabled flag, advance limits, weekday flags, optional daily open/close times
  - Enforced via shared `BookingCreationValidator` for mobile API + Filament manual booking creation
  - Managed per branch under **Restaurant Setup → Branches → Booking availability** (Platform + Restaurant panels)
- **Mobile availability UX (Phase 8B)**:
  - `GET /api/mobile/restaurants/{slug}` includes per-branch **`booking_availability`** (customer-safe fields) or **`null`** when no rule exists
  - Restaurant details and create-booking screens show summaries and best-effort pre-submit checks; **server validation unchanged**
- **Booking notification foundation (Phase 9A)**:
  - Internal `booking_notifications` rows (`pending`, no outbound sending): lifecycle events recorded after successful booking creation and valid transitions
  - `BookingNotificationService` builds title/message/payload; insert failures are reported without failing the booking flow
  - Platform Filament **Booking notifications** list (read-only) for `super_admin` and `operations_admin` only
- **Notification templates + preview (Phase 9B)**:
  - Configurable `notification_templates` (internal channel, `en` locale) for the 8 booking lifecycle events
  - `BookingNotificationService` uses an active template when available; otherwise falls back to the Phase 9A hardcoded copy (never breaks booking flow)
  - Platform Filament **Notification templates** resource (CRUD + preview modal) for `super_admin` and `operations_admin` only
- **Notification dispatch foundation (Phase 9C)**:
  - Internal-only dispatch actions for `booking_notifications` (`pending -> sent|skipped|failed`) with safe, final statuses (no external delivery)
  - Platform Filament **Booking notifications** adds native actions: Mark sent / Mark skipped / Mark failed (reason required)
- **Notification provider foundation (Phase 10A)**:
  - Provider abstraction (`NotificationProvider` + `NotificationProviderResult`) plus an `InternalDryRunNotificationProvider` (no external API calls)
  - Dispatch attempt tracking (`notification_dispatch_attempts`) for provider-ready auditing
  - Platform Filament adds **Dry-run dispatch** for pending/internal booking notifications and shows dispatch attempt history on the view page
- **Event nights dashboard foundation (Phase 11A)**:
  - Model `RestaurantEvent` (`restaurant_events`) to represent restaurant-hosted event nights (dashboard-only in this phase)
  - Platform panel can manage all event nights
  - Restaurant panel can manage scoped event nights:
    - `restaurant_owner`: restaurant-wide + branch events
    - `branch_manager` / `restaurant_host`: branch-scoped events only (restaurant-wide events are owner-only)
- **Event booking link foundation (Phase 11B)**:
  - Manual/dashboard bookings can optionally link to a published `RestaurantEvent` via `bookings.restaurant_event_id`
  - Event eligibility + capacity rules are validated in the shared booking creation validator (no mobile work in this phase)
- **Event booking operations polish (Phase 11C)**:
  - Event pages show linked bookings via a native Filament relation manager table
  - Event view/edit show capacity summary: active reserved seats + remaining seats (or Unlimited)
- **Offers dashboard foundation (Phase 12A)**:
  - Model `RestaurantOffer` (`restaurant_offers`) to manage restaurant offers in dashboards (no mobile/offers APIs in this phase)
  - Platform panel can manage all offers
  - Restaurant panel can manage scoped offers:
    - `restaurant_owner`: restaurant-wide + branch offers
    - `branch_manager` / `restaurant_host`: branch-scoped offers only (restaurant-wide offers are not visible/accessible)
- **Offers approval workflow + view polish (Phase 12B)**:
  - Offer view pages use the same polished Filament-native Sections/Grids layout style as Restaurant/Event views
  - Approval workflow for restaurant-submitted offers (submit for review, platform approve/reject/cancel)
  - Expiry foundation via `php artisan offers:expire` (no scheduler wiring in this phase)
  - Note: view/click analytics will be implemented alongside the future mobile offers API/UI
- **Stories dashboard foundation (Phase 13A)**:
  - Model `RestaurantStory` (`restaurant_stories`) to manage restaurant stories in dashboards (no mobile/story APIs in this phase)
  - Legacy single-slide fields remain on the container (`story_type`, `media_url`, `body`) for backward compatibility
  - Platform panel can manage all stories and can approve/reject/cancel
  - Restaurant panel can manage scoped stories and can submit for review/cancel (no direct publishing)
- **Stories uploads + multi-item architecture (Phase 13B)**:
  - Adds `RestaurantStoryItem` (`restaurant_story_items`) plus Filament relation managers for multi-slide stories (image/video uploads on `public` disk under `storage/app/public/stories/{story_id}`)
  - Adds lifetime presets (`12h|24h|48h|manual`) with predictable `starts_at`/`ends_at` derivation when approving/publishing (manual stays user-controlled)
  - Requires `php artisan storage:link` locally so uploaded previews resolve via `/storage/...`
  - Analytics note: story impressions/views remain **out of scope** until a mobile/customer story viewer exists
- **Customer reviews dashboard foundation (Phase 14A)**:
  - Model `RestaurantReview` (`restaurant_reviews`) for collecting customer reviews from inside the dashboards (Filament-only management; restaurant staff still have no moderation actions in `/restaurant`)
  - Validation rules at the model layer: `rating` is required and must be 1–5, `status` is one of `pending_review|published|rejected|hidden`, `source` is one of `dashboard|mobile|import`, optional branch/booking must belong to the selected restaurant, and `user_id` is inferred from a linked booking when blank
  - Platform panel can fully manage reviews (CRUD) and run native moderation actions: **Approve**, **Reject**, **Hide**
  - Restaurant panel exposes a **read-only**, scoped Reviews resource:
    - `restaurant_owner`: assigned restaurant-wide and branch-scoped reviews
    - `branch_manager` / `restaurant_host`: branch-scoped reviews only (restaurant-wide reviews are not visible/accessible)
    - No create/edit/delete and no moderation actions for any restaurant role
- **Mobile customer reviews API (Phase 14B)** (no new mobile app UI in this phase):
  - Authenticated customer (`auth:sanctum` + `mobile.token` + `mobile.customer`): submit a review for **own completed booking** (`POST /api/mobile/bookings/{booking}/review`), list/show **own** reviews (`GET /api/mobile/me/reviews`, `GET /api/mobile/me/reviews/{review}`)
  - Same auth stack as other mobile routes: browse **published** reviews for an **active** restaurant (`GET /api/mobile/restaurants/{slug}/reviews`) with a minimal public payload (no phone, admin notes, booking id, or non-published statuses)
  - One review per booking (DB unique on `booking_id`); submissions default to `pending_review` with `source=mobile`
- **Support tickets / complaints dashboard foundation (Phase 15A)** (dashboard-only):
  - Model `SupportTicket` (`support_tickets`) for platform-level or restaurant-scoped complaints; optional `restaurant_id`, `branch_id`, `booking_id`, `user_id`, customer snapshot fields, subject, category/priority/status/source enums, message, internal notes, `resolved_at` / `closed_at`
  - Platform (`super_admin`, `operations_admin`): full CRUD + native workflow actions (**Mark in progress**, **Resolve**, **Close**, **Reopen**) — reopen keeps `resolved_at` / `closed_at` for audit
  - Restaurant panel: **read-only** scoped tickets via `RestaurantPanelScope::supportTickets()`:
    - `restaurant_owner`: restaurant-wide (`branch_id` null) + branch tickets for assigned restaurants
    - `branch_manager` / `restaurant_host`: branch-scoped tickets only (no restaurant-wide rows; `restaurant_id` null platform tickets never appear)
  - No mobile API/UI, notifications, threading, or analytics in this phase
- **Filament view-page consistency polish**:
  - View pages use consistent native infolist Sections/Grids for clean, readable details with relation managers below

### Phase 1: local test users (dev only)

Phase 1 introduces role-based panel access using Spatie roles.

Seed roles + users:

```bash
cd backend
php artisan db:seed --class=RolesAndTestUsersSeeder
```

Default password for all test users: **`password`**

- Platform:
  - `super_admin@eventaat.test`
  - `operations_admin@eventaat.test`
- Restaurant:
  - `restaurant_owner@eventaat.test`
  - `branch_manager@eventaat.test`
  - `restaurant_host@eventaat.test`
- Customer:
  - `customer@eventaat.test`

Phase 0 user:
- `admin@eventaat.test` is assigned **`super_admin`** by the Phase 1 seeder.

### Phase 2: local demo restaurant data (dev only)

Phase 2 adds restaurant operational foundation models/resources and demo data.

Seed everything (roles/users + demo restaurants):

```bash
cd backend
php artisan db:seed
```

Demo data includes:
- 2 demo restaurants (`demo-restaurant-a`, `demo-restaurant-b`)
- branches, seating areas, tables, and **default branch availability rules** (10:00–23:00, all weekdays, 90 min duration label, 60 min min advance, 30 days max advance)
- staff assignments for:
  - `restaurant_owner@eventaat.test` (restaurant-level for Demo Restaurant A)
  - `branch_manager@eventaat.test` (branch-scoped for Demo Restaurant A)
  - `restaurant_host@eventaat.test` (branch-scoped for Demo Restaurant A)

### What does NOT exist yet

- No booking reschedule/update API
- No payments/deposits
- No advanced mobile UI kits/design system
- No custom dashboard pages/cards/stats/widgets
- No sidebar links to unimplemented features
- No real WhatsApp/SMS/push/email sending, no queues/workers/retries, and no mobile notification UI (Phase 10A is dry-run only)

### Phase 3: mobile customer auth API (dev only)

Phase 3 adds a mobile/customer REST API foundation:
- OTP request/verify (local/dev OTP sender logs OTP to app logs)
- Sanctum token auth for mobile sessions
- `GET /api/mobile/me`, `PATCH /api/mobile/me`, and logout

Key endpoints (see `docs/api-reference.md` for full details):
- `POST /api/mobile/auth/request-otp`
- `POST /api/mobile/auth/verify-otp`
- `GET /api/mobile/me`
- `PATCH /api/mobile/me`
- `POST /api/mobile/auth/logout`

### Phase 4A: mobile restaurant discovery API (dev only)

Phase 4A adds authenticated customer restaurant discovery:
- `GET /api/mobile/restaurants` (active only, search + pagination)
- `GET /api/mobile/restaurants/{restaurant:slug}` (active only, includes nested branches/seating/tables)

### Phase 4B: mobile app foundation + customer auth UI (dev only)

Phase 4B adds an Expo mobile app in `mobile/` that consumes the existing Phase 3 auth/profile endpoints:
- Phone entry -> request OTP
- OTP verify -> stores token securely
- Complete profile (name only) if `profile_completed=false`
- Authenticated home placeholder + logout

### Phase 5A: core booking backend + dashboards + customer API (dev only)

Phase 5A adds the first booking lifecycle foundation:
- Customer creates booking request via API (status starts `pending`)
- Restaurant staff accepts/rejects/cancels via Filament actions
- Platform can monitor and act on any booking via Filament actions

Key endpoints (see `docs/api-reference.md` for full details):
- `POST /api/mobile/bookings`
- `GET /api/mobile/bookings`
- `GET /api/mobile/bookings/{booking}`
- `POST /api/mobile/bookings/{booking}/cancel`

Phase 5B adds:
- simple conflict prevention when a `restaurant_table_id` is provided (prevents double-booking the same table/time)

### Phase 6: day-of booking operations (dev only)

Phase 6 extends restaurant day-of operations:
- Statuses: `arrived|seated|completed|no_show` (in addition to Phase 5 statuses)
- Timestamps: `arrived_at|seated_at|completed_at|no_show_at`
- Native Filament actions for day-of transitions (scoped in Restaurant panel)

### Phase 9A: booking notification foundation (dev only)

Phase 9A adds an internal notification log (`booking_notifications`) for lifecycle events (`booking_created`, `booking_accepted`, `booking_rejected`, `booking_cancelled`, `booking_arrived`, `booking_seated`, `booking_completed`, `booking_no_show`). Rows are written from mobile booking creation, Filament/manual booking creation, and successful `BookingTransitionService` transitions only. Platform Filament exposes a read-only **Booking notifications** resource for platform admins.

### Filament usability polish

We periodically apply small **Filament-native** usability improvements (tables/filters/forms/relation managers/navigation grouping) to make the operational hierarchy clearer:

Restaurant → Branch → Seating Area → Table → Booking

### Local setup (backend)

Prerequisites:
- PHP (8.2+)
- Composer
- PostgreSQL running locally

From the repo root:

```bash
cd backend
cp .env.example .env
php artisan key:generate
```

Configure PostgreSQL credentials in `backend/.env` if needed, then run:

```bash
php artisan migrate
```

If you’re testing story media uploads/previews locally:

```bash
php artisan storage:link
```

Create a Filament admin user (for local login testing):

```bash
php artisan make:filament-user --panel=platform
```

Run the server:

```bash
php artisan serve
```

Open:
- `http://localhost:8000/login` (unified dashboard entry)
- `http://localhost:8000/platform` or `http://localhost:8000/platform/login`
- `http://localhost:8000/restaurant` or `http://localhost:8000/restaurant/login`

### Local setup (mobile)

Prerequisites:
- Node.js + npm
- Expo Go (for quick device testing) or an iOS/Android simulator

Configure API base URL for your environment (LAN IP or tunnel for physical devices):

```bash
cd mobile
cp .env.example .env
```

Install + run:

```bash
npm install
npm run start
```

