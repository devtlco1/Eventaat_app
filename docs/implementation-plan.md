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

