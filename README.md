## Eventaat (Phase 16A restaurant menu foundation)

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
  - **`booking_audit_logs`**: immutable lifecycle audit from **`BookingTransitionService`** (distinct from **`booking_notifications`**); optional **`actor_id`** when **`web`** or **`sanctum`** user is authenticated
  - Filament BookingResource in `/platform` and `/restaurant`
    - Lifecycle actions: Accept / Reject / Cancel / Mark arrived / Mark seated / Mark completed / Mark no-show
    - **Audit trail** relation manager on Edit booking (read-only; restaurant scope unchanged)
    - Manual booking creation (phone-first) in both panels
  - Customer bookings API (create/list/detail/cancel)
- **Branch booking availability (Phase 8A)**:
  - One `BranchAvailabilityRule` per branch (optional row): enabled flag, advance limits, weekday flags, optional daily open/close times
  - Enforced via shared `BookingCreationValidator` for mobile API + Filament manual booking creation
  - Managed per branch under **Restaurant Setup → Branches → Booking availability** (Platform + Restaurant panels)
- **Mobile availability UX (Phase 8B)**:
  - `GET /api/mobile/restaurants/{slug}` includes per-branch **`booking_availability`** (customer-safe fields) or **`null`** when no rule exists
  - Restaurant details and create-booking screens show summaries and best-effort pre-submit checks; **server validation unchanged**
- **Restaurant subscriptions — platform foundation (Phase 8C)** *(distinct phase id from booking Phase 8A–8B)*:
  - **`subscription_plans`** catalog + **`restaurant_subscriptions`** assignments (`trial|active|past_due|cancelled|expired`); **no** payments/restaurant-panel subscription UI yet
  - Filament **`/platform`** resources **Subscription plans** + **Restaurant subscriptions** for `super_admin` / `operations_admin` only
  - Idempotent **`SubscriptionPlansSeeder`** (Basic / Pro / Enterprise placeholders)
- **Restaurant invoices — internal ledger (Phase 8D)**:
  - **`restaurant_invoices`** linked optionally to **`restaurant_subscriptions`**; statuses **`draft|issued|paid|void|overdue`**; **`RestaurantInvoiceService`** generates **`INV-{year}-{seq}`** numbers and drives simple status actions
  - Filament **`/platform`** **Restaurant invoices** only — **no** PSP, PDF/email invoices, collections automation, or blocking restaurants by invoice status
- **Call center foundation — internal logs (Phase 8E)**:
  - **`call_center_calls`** operational log rows (**inbound / outbound**, reason/outcome enums, optional links to restaurant, booking, support ticket, customer user, handled-by platform user); **`metadata`** JSON only for internal notes — **no** VoIP, WhatsApp, SMS, or restaurant-panel UI in this phase
  - Filament **`/platform`** **Call logs** (`super_admin`, `operations_admin`) with filters and native actions (**Mark resolved**, **Mark escalated**, **Mark no answer**) backed by **`CallCenterCallService`** — platform-only internal tooling
- **Platform operations polish (Phase 8F)**:
  - **Operations** sidebar order deduped (unique `navigationSort`), **Bookings** + **Event nights** gated explicitly like other ops modules (`super_admin` / `operations_admin`), semantic **badge colors** on core Operations tables (tickets, events, menus, offers, stories, reviews), **no** bulk-delete tooling added — stabilization only (**no** new features / APIs / migrations)
- **Authorization alignment (Phase 8G)**:
  - **`User`** helpers (**`isPlatformOperator()`**, **`isRestaurantStaff()`**, **`canManageRestaurantStructure()`**, **`isRestaurantOwner()`**) mirror **`User::canAccessPanel`** rules; **`App\Filament\Concerns\AuthorizesPlatformOperations`** + **`GrantsPlatformOperationsCrud`** dedupe **`/platform`** Filament **`can*`** gates (Operations + Restaurant Setup); **`RestaurantPanelScope::restaurantEvents()`** shares branch-aware event-night scope with **`RestaurantEventResource`** (**no** Laravel Policies introduced; restaurant query/scoping unchanged)
- **Platform access management (Filament; complements Phase 8G)**:
  - **`Access Management`** group (**Users**, **Roles**, **Permissions**) with native **`CreateAction`** buttons (**Add user**, **Add role**) visible only to **`super_admin`**, plus **`DeleteAction`** visibility tied to **`canDelete`** so forbidden deletes stay hidden (including core **`Roles`** rows).
  - **Users**: every account including mobile **`customer`** users; **`super_admin`** assigns Spatie **`roles`** and optional **`password`** (create form explains “leave blank → secure random password”); **`operations_admin`** edits profile fields only (roles/password fields never dehydrate).
  - **Roles** (**`super_admin`** only): manage **`web`** roles + attach permissions; core seeded roles cannot be deleted (**`name`/`guard_name`** locked on edit).
  - **Permissions** (**`super_admin`** inspect-only): **`PermissionsCatalogSeeder`** + **`RolePermissionDefaultsSeeder`** run from **`DatabaseSeeder`** — idempotent **`web`** capability catalog aligned with dashboard modules; **`super_admin`** receives every catalog permission on the role pivot, **`operations_admin`** receives the operational subset (**excludes** **`roles.view`**, **`roles.manage`**, **`permissions.view`**). Panel **`can*`** rules remain role-driven unless future code opts into **`hasPermissionTo`** checks.
  - **No** mobile/public API changes or migrations for this UI/catalog layer.
- **Backend production readiness audit (Phase 8H)**:
  - **`migrate:fresh --seed`** verified on an empty Postgres schema (local dev): all migrations apply in timestamp order; **`DatabaseSeeder`** chain is idempotent-friendly (`RolesAndTestUsersSeeder`, **`RolePermissionDefaultsSeeder`** → catalog, subscription plans, notification templates, demo restaurants/events/bookings).
  - **Environment**: **`backend/.env.example`** documents DB, **`APP_URL`**, **`FILESYSTEM_PUBLIC_URL`** (optional; default **`/storage`** root-relative), **`OTP_DRIVER=log`**, **`NOTIFICATION_DRIVER=dry_run`**, **`BOOKING_REMINDER_HOURS`**, mail/session/cache/queue defaults — **no** real SMS/WhatsApp/payment providers.
  - **Storage**: `public` disk root is **`storage/app/public`** with default URL prefix **`/storage`**; run **`php artisan storage:link`** once per deploy for web-served uploads (menus/PDFs/images).
  - **Scheduler**: **`eventaat:booking-reminders`** is registered **hourly** in **`bootstrap/app.php`**; production needs system cron **`php artisan schedule:run`** every minute (see **`php artisan schedule:list`**).
  - **API docs**: **`docs/api-reference.md`** intro points at **`route:list --path=api/mobile`**; mobile API behavior unchanged in this audit.
  - **Filament**: platform/restaurant indexes covered by existing feature tests where applicable; Access Management / Operations resources use empty or non-destructive **`bulkActions`** (no bulk delete introduced for users/roles).
- **Filament UI layout standardization (Phase 8I)**:
  - **`PlatformPanelProvider`** + **`RestaurantPanelProvider`** set **`->maxContentWidth(Width::Full)`** so Filament pages use full content width by default (aligned with **`/platform/restaurant-menus/create`** style).
  - **Forms / infolists**: **`Section::compact()`**, multi-column **`Grid`** layouts (typically three columns on desktop), **`columnSpanFull()`** for long text/PDF/uploads — applied across Operations + Restaurant Setup resources (users/roles/permissions, restaurants/branches/seating/tables/staff, bookings/manual booking create, menus/categories/items helpers, events/offers/stories, reviews, support tickets, notification templates, booking notifications view, subscriptions/invoices/call logs) plus shared **`BranchAvailabilityRuleFormComponents`**, **`RestaurantMenuCategoryFormSchema`**, **`RestaurantMenuItemFormSchema`**, story **`RelationManagers`**, **`RestaurantStoryContentRepeater`** section.
  - **Scope**: presentation/layout only — **no** API/mobile changes, **no** migrations, **no** new bulk deletes, permissions/scopes/validation unchanged.
- **Booking notification foundation (Phase 9A)**:
  - Internal `booking_notifications` rows (`pending`, no outbound sending): lifecycle events recorded after successful booking creation and valid transitions
  - `BookingNotificationService` builds title/message/payload; insert failures are reported without failing the booking flow
  - Platform Filament **Booking notifications** list (read-only) for `super_admin` and `operations_admin` only
- **Notification templates + preview (Phase 9B)**:
  - Configurable `notification_templates` (internal channel, `en` locale) for nine booking lifecycle/reminder keys (`booking_requested`, accepted/rejected/cancelled, arrived/seated/completed/no-show, **`booking_arrival_reminder`**)
  - Placeholders include `{{booking_date}}` / `{{booking_time}}` (from `starts_at`, app timezone) alongside customer/restaurant/branch/booking fields
  - `BookingNotificationService` uses an active template when available; otherwise falls back to the Phase 9A hardcoded copy (never breaks booking flow)
  - Platform Filament **Notification templates** resource (CRUD + preview modal) for `super_admin` and `operations_admin` only
- **Notification dispatch foundation (Phase 9C)**:
  - Internal-only dispatch actions for `booking_notifications` (`pending -> sent|skipped|failed`) with safe, final statuses (no external delivery)
  - Platform Filament **Booking notifications** adds native actions: Mark sent / Mark skipped / Mark failed (reason required)
- **Notification provider foundation (Phase 10A)**:
  - Provider abstraction (`NotificationProvider` + `NotificationProviderResult`) plus an `InternalDryRunNotificationProvider` (no external API calls)
  - Dispatch attempt tracking (`notification_dispatch_attempts`) for provider-ready auditing
  - Platform Filament adds **Dry-run dispatch** for pending/internal booking notifications and shows dispatch attempt history on the view page
- **Notification provider configuration readiness (Phase 7A)**:
  - `config/eventaat-notifications.php` with **`OTP_DRIVER`** (default **`log`**) and **`NOTIFICATION_DRIVER`** (default **`dry_run`**); see `backend/.env.example`
  - Central factories resolve senders/providers; **`sms`** / **`whatsapp`** are reserved and throw clear exceptions until a real integration exists (no credentials, no outbound messages in this phase)
- **Booking notification templates + dry-run lifecycle (Phase 7B)**:
  - Idempotent **`NotificationTemplatesSeeder`** for default English templates; dry-run **`dispatchInternalDryRun`** stores provider **`internal_dry_run`** on **`notification_dispatch_attempts`**
  - Still **no** live SMS/WhatsApp — outbound integrations remain future work
- **Booking arrival reminder command (Phase 7C)**:
  - **`php artisan eventaat:booking-reminders`** — records internal **`booking_arrival_reminder`** notifications for **`accepted`** bookings whose **`starts_at`** is within the next **`BOOKING_REMINDER_HOURS`** (default **2**, app timezone); skips bookings that already have that reminder row
  - **`bootstrap/app.php`** registers an **hourly** Laravel scheduler entry; run **`php artisan schedule:run`** from cron (or invoke the command manually) — command does **not** send SMS/WhatsApp by itself
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
- **Support ticket activity timeline + internal notes (Phase 15B)** (dashboard-only):
  - Model `SupportTicketActivity` (`support_ticket_activities`): append-only rows (`note`, `status_change`; `system` reserved); optional `user_id`; `message`; `old_status` / `new_status` for transitions; internal-only flag defaults true
  - `SupportTicketActivityService` (`recordNote`, `recordStatusChange`) plus `SupportTicketObserver` logs **every post-create status change** (table actions + edit form); initial ticket creation does not emit a status-change row
  - Platform ticket **view/edit**: relation manager **Internal activity** (read-only rows) + **Add internal note** (modal, message required); status workflow unchanged and writes `status_change` activities
  - Restaurant panel: **no** activity timeline and **no** add-note action (still read-only ticket list/view only)
  - No mobile/API customer replies, notifications, or automation in this phase
- **Restaurant menu foundation (Phase 16A)** (dashboard-only):
  - Models `RestaurantMenu`, `RestaurantMenuCategory`, `RestaurantMenuItem` with modes **`structured`** (categories/items), **`pdf_upload`** (PDF on `public` disk under `menus/`), **`external_link`** (validated URL); **`slug` is globally unique**; structured items may store optional **`image_path`** on the **`public`** disk under **`menus/items/`**
  - Platform + Restaurant Filament resources; restaurant scoping via `RestaurantPanelScope::menus()` (same branch-only vs restaurant-wide pattern as offers/stories); list tables expose an **Add menu** header action
  - Structured mode: create flow shows a short **save-first** helper; **Edit menu** opens **Menu content** (native Filament categories table + one menu-wide items table with category filter; **Add category** / **Add item** header actions). **PDF upload** and **external link** modes show only their own fields on edit (no Menu content block). View menu shows structured preview with categories/items (item image placeholder when missing), or PDF/open link, or external URL only—matching `menu_mode`. Categories relation manager is **disabled by default** and can be re-enabled via `RestaurantMenuStructuredUi::SHOW_CATEGORIES_RELATION_MANAGER_FALLBACK`
  - Public disk URLs default to root-relative **`/storage`** so uploads/previews follow the browser host/port (override with **`FILESYSTEM_PUBLIC_URL`** in `backend/.env`; see `backend/.env.example`)
  - Demo seed adds a structured menu for Demo Restaurant A and an external-link menu for Demo Restaurant B; local previews need **`php artisan storage:link`** when serving uploads (PDFs and menu item images)
  - No mobile menu APIs, guest-facing pages, carts, OCR, or analytics in this phase
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

Phase 5C adds:
- **`booking_audit_logs`** table + **`BookingAuditService`** wired into **`BookingTransitionService`** (`from_status` / `to_status`, **`actor_id`** when authenticated via **`web`** or **`sanctum`**)
- Filament **Audit trail** on Edit booking (Platform + Restaurant; no API exposure)

### Phase 6: day-of booking operations (dev only)

Phase 6 extends restaurant day-of operations:
- Statuses: `arrived|seated|completed|no_show` (in addition to Phase 5 statuses)
- Timestamps: `arrived_at|seated_at|completed_at|no_show_at`
- Native Filament actions for day-of transitions (scoped in Restaurant panel)

### Phase 8C: restaurant subscription foundation — platform only (dev only)

Adds **`subscription_plans`** (priced catalog: IQD placeholders, monthly/yearly intervals) and **`restaurant_subscriptions`** (historical rows per restaurant; at most one **trial** or **active** slot enforced by validation). Filament Platform CRUD under **Operations** — **no** Stripe/payment gateways, **no** automatic blocking of restaurants by subscription status, **no** restaurant-panel screens or mobile/API exposure in this phase.

### Phase 8D: restaurant invoices — internal ledger (platform-only, dev only)

Adds **`restaurant_invoices`** with optional **`restaurant_subscription_id`**, unique **`invoice_number`** (`INV-{year}-{seq}` when blank on create), amount lines (**total** recomputed as **subtotal − discount + tax**), and **`RestaurantInvoiceService`** helpers (**Mark issued / Mark paid / Void** table actions). **No** PSP integration, PDF/email sending, finance exports as product features in this phase, and **no** restaurant-panel access.

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

Run tests from `backend/`:

```bash
php artisan test
```

`backend/phpunit.xml` raises PHPUnit `memory_limit` slightly so Filament + Livewire file-upload feature tests do not intermittently abort the PHP process on typical machines.

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

