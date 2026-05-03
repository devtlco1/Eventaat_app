## Product blueprint (Phase 0–16A)

Phase 0–16A builds on earlier phases including **mobile REST endpoints for customer reviews** (Phase 14B), **dashboard-only support tickets / complaints** (Phase 15A), **platform-only ticket activity + notes** (Phase 15B), and **dashboard-only restaurant menus** (Phase 16A): menus support structured categories/items (optional per-item images on **`public`** under **`menus/items/`**; structured content is authored from **Edit menu** via native Filament **Menu content** tables), uploaded PDFs (**`public`** disk, **`menus/`** directory), or external URLs; Filament resources exist in Platform and Restaurant panels with scoping aligned to offers/stories — **no** mobile menu APIs, guest-facing pages, carts, OCR, or analytics in Phase 16A. This file exists to match the repository structure required by `docs/eventaat_blueprint_v1.md`.

Current source of truth: `docs/eventaat_blueprint_v1.md`.

### Mobile customer UI (current)

- Auth + profile completion
- Restaurant discovery UI (list + details)
- Bookings UI (create + list + details + cancel)

### Booking availability (Phase 8A)

- Each branch may have **one** availability rule record (`BranchAvailabilityRule`): booking on/off, min/max advance window, weekday toggles, optional daily open/close times (single window).
- Enforcement is **shared** between mobile booking creation and Filament manual booking creation via `BookingCreationValidator`.

### Mobile availability guidance (Phase 8B)

- Restaurant detail API exposes the same rule fields per branch as **`booking_availability`** (or **`null`**).
- The mobile app shows summaries on restaurant details and create booking, and runs **best-effort** checks before submit; the API still returns **422** when the server rejects a time.

### Restaurant subscriptions — platform foundation (Phase 8C)

- **`subscription_plans`** defines catalog tiers (**Basic**, **Pro**, **Enterprise** seeded idempotently as placeholders); **`restaurant_subscriptions`** links restaurants to optional plans with statuses **`trial|active|past_due|cancelled|expired`** and optional schedule/note fields.
- **Platform Filament only** (`super_admin`, `operations_admin`): manage plans and subscriptions — **no** payment processor, entitlement middleware, or restaurant-panel subscription screens in this phase.
- Restaurants may keep **multiple historical** subscriptions; only **one** `trial` **or** `active` row per restaurant is allowed at save time.

### Restaurant invoices — internal ledger (Phase 8D)

- **`restaurant_invoices`** captures operator-managed billing rows (**draft|issued|paid|void|overdue**) with optional link to **`restaurant_subscriptions`**, IQD-ready decimal amounts, and **`metadata`** JSON for future extensions.
- Amounts reconcile on save (**total = subtotal − discount + tax**, discount capped at subtotal); **`RestaurantInvoiceService`** allocates **`INV-{YYYY}-{000001}`** numbers and powers Filament row actions (**Mark issued**, **Mark paid**, **Void**) — **no** PSP, PDF/email delivery, automated collections, customer-facing invoices, or blocking restaurants based on invoice status yet.

### Call center foundation — internal logs (Phase 8E)

- **`call_center_calls`** stores operator-entered **call logs** (**inbound / outbound**, categorised reasons/outcomes, optional links to restaurant / booking / support ticket / customer user / handled-by platform staff); **`metadata`** JSON for unstructured operator tags only.
- **`CallCenterCallService`** backs Filament row shortcuts (**Mark resolved**, **Mark escalated**, **Mark no answer**). **`completed_at`** with **`pending`** outcome normalizes to **`resolved`** on save — **no** live calling stack, WhatsApp/SMS bridges, recordings, CSAT polling, restaurant-panel screens, or public/mobile APIs for this module yet.

### Platform operations polish (Phase 8F)

- **Filament `/platform` Operations** sidebar ordering is deterministic (no overlapping sort integers); **Bookings** and **Event nights** resources enforce the same **`super_admin` / `operations_admin`** gates as invoices/subscriptions/tickets/call logs.
- Operations **table badges** use consistent semantic Filament colors for statuses/priorities/modes where rows were previously monochrome — presentation-only tightening (**no** API or schema churn).

### Backend production readiness (Phase 8H)

- **Database & seeds**: Migration order validated via fresh install + **`DatabaseSeeder`** (roles/users, permission catalog defaults, subscription plan placeholders, notification templates, demo restaurant/event/booking content where enabled). Re-run seeders remain idempotent at the row level they control.
- **Runtime config**: **`OTP_DRIVER=log`**, **`NOTIFICATION_DRIVER=dry_run`**, and hourly **`eventaat:booking-reminders`** (internal notification rows only) remain the safe defaults. Optional **`OTP_DRIVER=twilio_sms`** or **`twilio_whatsapp`** + Twilio env enables mobile OTP on those channels (Phase **7D** / **7F**); booking notifications are still not sent via Twilio by default.
- **Operations**: Document **`php artisan storage:link`** for **`public`** disk URLs and **cron + `schedule:run`** for reminders; **`docs/api-reference.md`** ties mobile docs to **`route:list --path=api/mobile`**.
- **Scope**: Documentation and verification only unless a regression is found — **no** new customer API routes or Filament modules in Phase 8H.

### Filament layout consistency (Phase 8I)

- **Panels**: Platform and Restaurant Filament panels default to **full content width** for resource pages.
- **Section stacking**: Root schemas force **one column** at all breakpoints (**`FilamentSchemaLayout::stackSections`**), so **main sections/cards never sit beside each other**; fields stay **horizontal inside** each section via **`Grid`** (typically **3–4** columns for short fields).
- **Coverage**: Aligns layouts across operators’ modules (access management, restaurant setup, bookings, menus, stories/offers/events, reviews, tickets, subscriptions/invoices/call logs, notification templates, booking notification detail, relation-manager modals, structured menu Livewire editors) while preserving existing scopes and **`can*`** rules.

### Restaurant panel readiness (Phase 8J)

- **`/restaurant`** audited for staff roles with **`RestaurantPanelScope`** + **`User::scopedRestaurantIds()` / `scopedBranchIds()`** unchanged.
- **Navigation polish**: unique sidebar icons per resource group and deterministic **`navigationSort`** within Operations so bookings/menus/events/offers/stories/reviews/tickets do not collide.
- **Regression guard**: **`RestaurantPanelReadinessTest`** smoke-loads assigned-restaurant index URLs; deeper scoping stays in **`RestaurantPanelScopingTest`** and domain feature tests.

### Authorization alignment (Phase 8G)

- **`User`** exposes small role helpers (**platform operator**, **restaurant staff**, **structure managers**, **restaurant owners**) so Filament **`can*`** methods stop repeating literal **`hasAnyRole`** arrays; **`/platform`** resources share **`AuthorizesPlatformOperations`** / **`GrantsPlatformOperationsCrud`** traits instead of copying private **`isPlatformUser`** closures.
- **`RestaurantPanelScope::restaurantEvents()`** mirrors restaurant-panel branch-scoping rules without changing outcomes — **no** new Laravel Policies / Gates for now.
- **Filament Access Management**: **`Access Management → Users`** surfaces mobile **`customer`** accounts alongside staff; obvious **`Add user`** / **`Add role`** header actions are **`super_admin`**-only; **`super_admin`** controls Spatie role assignment (the **`roles`** field is hidden/dehydrated for everyone else so **`operations_admin`** edits cannot rewrite pivots); **`super_admin`**-only **`Roles`** + read-only **`Permissions`** inventory ship with **`PermissionsCatalogSeeder`** + **`RolePermissionDefaultsSeeder`** so the Permissions table is populated idempotently while Filament **`can*`** rules stay role-based unless a module later checks **`hasPermissionTo`** — **no** API surface changes.

### Booking audit trail (Phase 5C)

- **`booking_audit_logs`** stores append-only lifecycle rows when **`BookingTransitionService`** successfully changes status (**`accepted`**, **`rejected`**, **`cancelled`**, **`arrived`**, **`seated`**, **`completed`**, **`no_show`**) with **`from_status`** / **`to_status`** and optional **`actor_id`** (dashboard **`web`** or mobile **`sanctum`** user).
- This is **not** the notification outbox (`booking_notifications` remains separate).
- Dashboard operators view a compact **Audit trail** table on **Edit booking** (Filament native relation manager); restaurant panel stays scoped as elsewhere.

### Booking notification foundation (Phase 9A)

- Lifecycle events produce **`booking_notifications`** rows (`pending`, internal channel only): creation plus accepted/rejected/cancelled/arrived/seated/completed/no-show after successful transitions.
- Platform admins (`super_admin`, `operations_admin`) can browse rows read-only in Filament; restaurant panel has no notification UI in this phase.
- No WhatsApp/SMS/push/email, queues, workers, retries, or customer-facing notification surfaces.

### Notification templates + preview (Phase 9B)

- Each booking lifecycle event can have an active template in `notification_templates` to generate `title` + `message` for outbox rows.
- Canonical lifecycle keys include **`booking_requested`** (new pending booking), **`booking_accepted`**, **`booking_rejected`**, **`booking_cancelled`**, plus operational events (`arrived`, `seated`, `completed`, `no_show`). **`booking_arrival_reminder`** has a seeded template; Phase **7C** adds **`php artisan eventaat:booking-reminders`** (default window **`BOOKING_REMINDER_HOURS=2`**) plus an optional Laravel **hourly** schedule entry — still **internal rows only**, no SMS/WhatsApp send until a future integration dispatches them.
- Supported placeholders:
  - `{{customer_name}}`, `{{customer_phone}}`, `{{restaurant_name}}`, `{{branch_name}}`
  - `{{booking_id}}`, `{{booking_status}}`, `{{starts_at}}`, `{{party_size}}`
  - `{{booking_date}}`, `{{booking_time}}` — derived from `starts_at` in the app timezone (for Arabic-friendly copy, keep templates neutral; deep i18n is out of scope)
- Rendering is intentionally simple:
  - Unknown placeholders remain unchanged.
  - Failures fall back to the Phase 9A hardcoded copy and never break booking flows.
- Platform panel provides CRUD + preview modal for templates; restaurant panel does not expose templates in this phase.

### Notification dispatch foundation (Phase 9C)

- The outbox remains internal-only; no external provider integrations.
- Platform admins can mark `booking_notifications`:
  - `pending -> sent` (sets `sent_at`)
  - `pending -> skipped`
  - `pending -> failed` (sets `failed_at` + `failure_reason`)
- `sent`, `skipped`, and `failed` are final for this phase (no retries).

### Notification provider foundation (Phase 10A)

- Introduces provider abstraction for future WhatsApp/SMS providers, without external API calls:
  - `NotificationProvider` interface
  - `NotificationProviderResult` value object
  - `InternalDryRunNotificationProvider` implementation
- Adds dispatch attempt tracking (`notification_dispatch_attempts`) for auditing provider interactions (dry-run payloads/results for now).
- Platform admins can run **Dry-run dispatch** on pending/internal outbox rows:
  - Creates an attempt row
  - Marks notification `sent` on success or `failed` on failure

### Notification provider configuration readiness (Phase 7A)

- `config/eventaat-notifications.php` plus env **`OTP_DRIVER`** (default **`log`**) and **`NOTIFICATION_DRIVER`** (default **`dry_run`**) for production-safe defaults.
- Reserved OTP driver values **`sms`** / **`whatsapp`** still fail fast; production SMS uses **`OTP_DRIVER=twilio_sms`** (Phase **7D**); production WhatsApp OTP uses **`OTP_DRIVER=twilio_whatsapp`** with an approved Twilio Authentication Content Template (Phase **7F**).

### Twilio SMS OTP (Phase 7D)

- **Mobile auth only**: when **`OTP_DRIVER=twilio_sms`**, OTP codes for **`/api/mobile/auth/request-otp`** are sent via Twilio using **`TWILIO_MESSAGING_SERVICE_SID`** (plus account SID and auth token). Default remains **`log`** (OTP in application logs).

### Twilio WhatsApp OTP (Phase 7F)

- **Mobile auth only**, separate driver from SMS: **`OTP_DRIVER=twilio_whatsapp`** uses **`TWILIO_WHATSAPP_FROM`** and **`TWILIO_WHATSAPP_OTP_CONTENT_SID`** with Twilio Content API variables (**slot `1`** = OTP code). Requires Meta/Twilio-approved template (e.g. **`eventaat_otp`**).
- **Not in this phase**: booking notifications via WhatsApp, mobile API contract changes.

### OTP phone validation + SMS wording (Phase 7E)

- **`phone`** on **`request-otp`** and **`verify-otp`** must be **E.164-style** international (`+` prefix, valid length and digits only after normalization — no placeholders such as **`X`**).
- Twilio-delivered OTP SMS uses the short wording **“Eventaat code: … Do not share this code.”**

### OTP rate limits (Phase 7G)

- **Request**: per phone, minimum time between **`request-otp`** sends and maximum requests per rolling hour — **429** when exceeded (**`OTP_REQUEST_COOLDOWN_SECONDS`**, **`OTP_REQUEST_MAX_PER_HOUR`**).
- **Verify**: per phone, bounded failed **`verify-otp`** attempts per time window — **429** when exceeded (**`OTP_VERIFY_MAX_ATTEMPTS`**, **`OTP_VERIFY_DECAY_MINUTES`**); success clears the failure counter.
- WhatsApp OTP driver remains optional where provider/Meta approval is pending; SMS and **`log`** drivers unchanged.

### OTP delivery audit (Phase 7H)

- Platform **`/platform`** → **OTP delivery attempts**: operational visibility into **`log`** / SMS / WhatsApp sends (**pending**/**sent**/**failed**) without storing OTP codes or full phone numbers (masked display + SHA-256 hash, Twilio SID when returned).
- **`LocalLogOtpSender`** structured logs omit plaintext OTP (**masked destination only**).

### Booking notification template lifecycle + dry-run polish (Phase 7B)

- Default templates are seeded idempotently for all **`BookingNotification::EVENTS`** keys (nine rows including **`booking_arrival_reminder`**).
- Outbox **`payload`** retains structured fields plus **`resolved_title`** / **`resolved_message`** for auditing rendered copy.
- **Dry-run dispatch** records attempts with provider **`internal_dry_run`**; still no external WhatsApp/SMS integration.

### Booking arrival reminder command (Phase 7C)

- **`eventaat:booking-reminders`** creates at most one **`booking_arrival_reminder`** row per booking when status is **`accepted`** and **`starts_at`** falls within the configured upcoming window (see **`BOOKING_REMINDER_HOURS`** / **`config/eventaat-notifications.php`**).
- Reminder rows respect the same internal notification pipeline as other events; operators may **Dry-run dispatch** from Filament as today. No automatic external delivery.

### Event nights dashboard foundation (Phase 11A)

- Adds `RestaurantEvent` (event nights) as a dashboard-only module (no mobile/event discovery in this phase).
- Events belong to a restaurant; branch is optional but must belong to the selected restaurant.
- Restaurant panel scoping:
  - `restaurant_owner`: restaurant-wide + branch events
  - `branch_manager` / `restaurant_host`: branch-scoped events only

### Event booking link foundation (Phase 11B)

- Manual/dashboard bookings can optionally link to a published event night (`RestaurantEvent`) via `bookings.restaurant_event_id`.
- Event must be published and bookable (`booking_mode` is `normal_booking` or `event_booking`, not `info_only`).
- Capacity is enforced per event using active booking statuses only (`pending, accepted, arrived, seated`).

### Event booking operations polish (Phase 11C)

- Event pages show linked bookings (native Filament relation manager table).
- Event view/edit shows capacity summary:
  - active reserved seats (consuming statuses only)
  - remaining seats (or “Unlimited” if capacity is null)

### Offers dashboard foundation (Phase 12A)

- Adds `RestaurantOffer` (offers) as a dashboard-only module (no mobile/offers API in this phase).
- Offers belong to a restaurant; branch is optional but must belong to the selected restaurant.
- Offer types:
  - `text_only` (no discount value)
  - `percentage` (`discount_value` required, 1–100)
  - `fixed_amount` (`discount_value` required, > 0)
- Restaurant panel scoping:
  - `restaurant_owner`: restaurant-wide + branch offers
  - `branch_manager` / `restaurant_host`: branch-scoped offers only (restaurant-wide offers are not visible/accessible)

### Offers approval workflow + view polish (Phase 12B)

- Polishes offer view pages in both panels to match the Filament-native Sections/Grids style used across Restaurant/Event views.
- Adds approval workflow statuses: `draft|pending_review|published|rejected|expired|cancelled`
- Platform roles can approve/reject pending offers and cancel offers.
- Restaurant roles can submit draft offers for review but cannot publish/approve directly.
- Expiry foundation: `offers:expire` marks ended published offers as expired (manual command; no scheduler wiring in this phase).
- Analytics note: view/click analytics will be implemented alongside the future mobile offers API/UI.

### Stories dashboard foundation (Phase 13A)

- Adds `RestaurantStory` (stories) as a dashboard-only module (no mobile/story API/UI in this phase).
- Legacy container fields (`story_type`, `media_url`, `body`) remain for backward compatibility with older seeded/demo rows (dashboard-only).
- Status workflow: `draft|pending_review|published|rejected|expired|cancelled`
- Platform panel can manage all stories and can approve/reject pending review and cancel stories.
- Restaurant panel scoping:
  - `restaurant_owner`: restaurant-wide + branch stories
  - `branch_manager` / `restaurant_host`: branch-scoped stories only (restaurant-wide stories are not visible/accessible)
- Expiry foundation: `stories:expire` marks ended published stories as expired (manual command; no scheduler wiring in this phase).

### Stories uploads + multi-item architecture (Phase 13B)

- Primary authoring moves to **`RestaurantStoryItem`** slides (`image|video|text`) managed via Filament relation managers on each story record (scoped exactly like Phase 13A parent stories).
- Uploads use Filament `FileUpload` on the `public` disk (`storage/app/public/stories/{story_id}`); local dev requires `php artisan storage:link`.
- Adds lifetime presets on the story container (`12h|24h|48h|manual`) plus optional manual shortcut hours when `ends_at` isn’t explicitly set.
- Approval/publishing derives `starts_at`/`ends_at` predictably for non-manual modes when `ends_at` is empty (manual keeps end-time user-controlled).
- Customer/mobile story viewing + analytics remain explicitly **out of scope** until later phases introduce mobile APIs/UI.

### Customer reviews dashboard foundation (Phase 14A)

- Adds `RestaurantReview` (`restaurant_reviews`) as the shared persistence model for reviews (operators capture/edit/moderate in Filament on `/platform`; restaurant panel stays **read-only** scoped).
- Reviews belong to a restaurant; optional branch and optional booking link must align with restaurant (and booking branch must match when both branch and booking are set).
- Rating is required (1–5). Status workflow for moderation: `pending_review|published|rejected|hidden`. Source tracks provenance (`dashboard|mobile|import`).
- Platform roles (`super_admin`, `operations_admin`) manage reviews fully (CRUD) with native row actions: approve / reject / hide.
- Restaurant panel is **read-only**:
  - `restaurant_owner`: sees assigned restaurant-wide + branch reviews
  - `branch_manager` / `restaurant_host`: sees branch-scoped reviews only (restaurant-wide reviews are not visible/accessible)

### Mobile customer reviews API (Phase 14B)

- **Backend/API only** (no new Expo screens required for this phase label): authenticated mobile customers use Sanctum tokens (`mobile.customer`) to submit reviews for **their own completed bookings** and to list/show **their own** reviews (including `pending_review` rows).
- **Published discovery**: `GET /api/mobile/restaurants/{slug}/reviews` lists **published** reviews for an **active** restaurant using a minimal customer-safe payload (display name + rating + comment + timestamp — no phone numbers or internal notes).

### Support tickets dashboard foundation (Phase 15A)

- Adds `SupportTicket` for internal complaint tracking: may be **platform-level** (`restaurant_id` null) or tied to a restaurant (and optionally branch/booking/customer).
- **Platform** operators have full CRUD and status workflow actions; **Restaurant** staff have read-only access to tickets in their scope (owners see restaurant-wide + branch; branch roles see branch rows only).
- No customer-facing submission, mobile APIs, notifications, or conversation threads in this phase.

### Support ticket activity timeline (Phase 15B)

- Adds append-only `SupportTicketActivity` rows (`note`, `status_change`) scoped per ticket; platform Filament shows timeline + **Add internal note**; status transitions also persist automatically via observer after tickets exist.
- Restaurant dashboard continues to hide internal ops commentary — tickets remain view-only without activity tabs.
