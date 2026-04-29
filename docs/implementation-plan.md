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

