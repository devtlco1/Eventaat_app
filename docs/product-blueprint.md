## Product blueprint (Phase 0–10A)

Phase 0–10A focuses on foundation + role-based access + restaurant operational foundation + mobile customer API + mobile app auth UI foundation + core booking flow foundation + day-of booking operations + **branch-level booking availability rules** + **internal booking notification rows** + **configurable notification templates + preview** + **internal dispatch actions** + **provider-ready dry-run dispatch with attempts** (no outbound delivery). This file exists to match the repository structure required by `docs/eventaat_blueprint_v1.md`.

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

