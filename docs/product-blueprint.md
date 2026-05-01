## Product blueprint (Phase 0–9A)

Phase 0–9A focuses on foundation + role-based access + restaurant operational foundation + mobile customer API + mobile app auth UI foundation + core booking flow foundation + day-of booking operations + **branch-level booking availability rules** + **internal booking notification rows** (no outbound delivery). This file exists to match the repository structure required by `docs/eventaat_blueprint_v1.md`.

Current source of truth: `docs/eventaat_blueprint_v1.md`.

### Mobile customer UI (current)

- Auth + profile completion
- Restaurant discovery UI (list + details)
- Bookings UI (create + list + details + cancel)

### Booking availability (Phase 8A)

- Each branch may have **one** availability rule record (`BranchAvailabilityRule`): booking on/off, min/max advance window, weekday toggles, optional daily open/close times (single window).
- Enforcement is **shared** between mobile booking creation and Filament manual booking creation via `BookingCreationValidator`.
- Mobile UI does **not** implement an availability picker; the API returns normal **422** validation errors when rules reject a time.

### Booking notification foundation (Phase 9A)

- Lifecycle events produce **`booking_notifications`** rows (`pending`, internal channel only): creation plus accepted/rejected/cancelled/arrived/seated/completed/no-show after successful transitions.
- Platform admins (`super_admin`, `operations_admin`) can browse rows read-only in Filament; restaurant panel has no notification UI in this phase.
- No WhatsApp/SMS/push/email, queues, workers, retries, or customer-facing notification surfaces.

