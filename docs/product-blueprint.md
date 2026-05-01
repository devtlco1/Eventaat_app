## Product blueprint (Phase 0–15B)

Phase 0–15B builds on earlier phases including **mobile REST endpoints for customer reviews** (Phase 14B) and adds **dashboard-only support tickets / complaints** (Phase 15A) plus **platform-only internal activity + notes** (Phase 15B): operators manage `SupportTicket` records and append-only `SupportTicketActivity` timeline rows in Filament; restaurant staff keep **read-only** scoped ticket visibility — **no** mobile/API submission, threaded messaging, or restaurant-visible ops notes in Phase 15B. This file exists to match the repository structure required by `docs/eventaat_blueprint_v1.md`.

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

### Booking notification foundation (Phase 9A)

- Lifecycle events produce **`booking_notifications`** rows (`pending`, internal channel only): creation plus accepted/rejected/cancelled/arrived/seated/completed/no-show after successful transitions.
- Platform admins (`super_admin`, `operations_admin`) can browse rows read-only in Filament; restaurant panel has no notification UI in this phase.
- No WhatsApp/SMS/push/email, queues, workers, retries, or customer-facing notification surfaces.

### Notification templates + preview (Phase 9B)

- Each booking lifecycle event can have an active template in `notification_templates` to generate `title` + `message` for outbox rows.
- Supported placeholders:
  - `{{customer_name}}`, `{{customer_phone}}`, `{{restaurant_name}}`, `{{branch_name}}`
  - `{{booking_id}}`, `{{booking_status}}`, `{{starts_at}}`, `{{party_size}}`
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
