## Product blueprint (Phase 0–8A)

Phase 0–8A focuses on foundation + role-based access + restaurant operational foundation + mobile customer API + mobile app auth UI foundation + core booking flow foundation + day-of booking operations + **branch-level booking availability rules**. This file exists to match the repository structure required by `docs/eventaat_blueprint_v1.md`.

Current source of truth: `docs/eventaat_blueprint_v1.md`.

### Mobile customer UI (current)

- Auth + profile completion
- Restaurant discovery UI (list + details)
- Bookings UI (create + list + details + cancel)

### Booking availability (Phase 8A)

- Each branch may have **one** availability rule record (`BranchAvailabilityRule`): booking on/off, min/max advance window, weekday toggles, optional daily open/close times (single window).
- Enforcement is **shared** between mobile booking creation and Filament manual booking creation via `BookingCreationValidator`.
- Mobile UI does **not** implement an availability picker; the API returns normal **422** validation errors when rules reject a time.

