## Eventaat (Phase 1 in progress)

This repository is a rebuild of Eventaat following the phased plan in `docs/eventaat_blueprint_v1.md`.

Source of truth: `docs/eventaat_blueprint_v1.md`.

### What exists (Phases 0–1)

- **Backend**: Laravel app in `backend/`
- **Database**: PostgreSQL configuration (see `backend/.env`)
- **Dashboards**: Filament panels only
  - **Platform panel**: `/platform`
  - **Restaurant panel**: `/restaurant`
- **Docs**: implementation plan and role rules in `docs/`

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

### What does NOT exist yet

- No restaurant resources/models
- No bookings
- No mobile app (`mobile/` not created)
- No custom dashboard pages/cards/stats/widgets
- No sidebar links to unimplemented features
- No API work beyond the Laravel/Filament foundation

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

Create a Filament admin user (for local login testing):

```bash
php artisan make:filament-user --panel=platform
```

Run the server:

```bash
php artisan serve
```

Open:
- `http://localhost:8000/platform`
- `http://localhost:8000/restaurant`

