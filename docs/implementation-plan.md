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

