## Eventaat (Phase 4B complete)

This repository is a rebuild of Eventaat following the phased plan in `docs/eventaat_blueprint_v1.md`.

Source of truth: `docs/eventaat_blueprint_v1.md`.

### What exists (Phases 0–4A)

- **Backend**: Laravel app in `backend/`
- **Database**: PostgreSQL configuration (see `backend/.env`)
- **Dashboards**: Filament panels only
  - **Platform panel**: `/platform`
  - **Restaurant panel**: `/restaurant`
- **Docs**: implementation plan and role rules in `docs/`
- **Mobile**: Expo React Native app in `mobile/` (Phase 4B auth UI foundation)

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
- branches, seating areas, and tables
- staff assignments for:
  - `restaurant_owner@eventaat.test` (restaurant-level for Demo Restaurant A)
  - `branch_manager@eventaat.test` (branch-scoped for Demo Restaurant A)
  - `restaurant_host@eventaat.test` (branch-scoped for Demo Restaurant A)

### What does NOT exist yet

- No bookings
- No mobile restaurant discovery UI (home is a placeholder only)
- No custom dashboard pages/cards/stats/widgets
- No sidebar links to unimplemented features
- No new backend API changes beyond Phases 3–4A

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

