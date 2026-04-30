## Eventaat (Phase 6 complete)

This repository is a rebuild of Eventaat following the phased plan in `docs/eventaat_blueprint_v1.md`.

Source of truth: `docs/eventaat_blueprint_v1.md`.

### What exists (Phases 0–6)

- **Backend**: Laravel app in `backend/`
- **Database**: PostgreSQL configuration (see `backend/.env`)
- **Dashboards**: Filament panels only
  - **Platform panel**: `/platform`
  - **Restaurant panel**: `/restaurant`
- **Docs**: implementation plan and role rules in `docs/`
- **Mobile**: Expo React Native app in `mobile/` (Phase 4B auth UI foundation)
- **Mobile UI (customer)**:
  - Restaurant discovery UI (list + details)
  - Booking UI (create + my bookings + booking details + cancel)
- **Bookings (Phases 5–6)**:
  - Booking model + statuses: `pending|accepted|rejected|cancelled|arrived|seated|completed|no_show`
  - Filament BookingResource in `/platform` and `/restaurant`
    - Lifecycle actions: Accept / Reject / Cancel / Mark arrived / Mark seated / Mark completed / Mark no-show
    - Manual booking creation (phone-first) in both panels
  - Customer bookings API (create/list/detail/cancel)

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

- No booking reschedule/update API
- No payments/deposits
- No advanced mobile UI kits/design system
- No custom dashboard pages/cards/stats/widgets
- No sidebar links to unimplemented features
- No notifications/WhatsApp integration

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

### Phase 5A: core booking backend + dashboards + customer API (dev only)

Phase 5A adds the first booking lifecycle foundation:
- Customer creates booking request via API (status starts `pending`)
- Restaurant staff accepts/rejects/cancels via Filament actions
- Platform can monitor and act on any booking via Filament actions

Key endpoints (see `docs/api-reference.md` for full details):
- `POST /api/mobile/bookings`
- `GET /api/mobile/bookings`
- `GET /api/mobile/bookings/{booking}`
- `POST /api/mobile/bookings/{booking}/cancel`

Phase 5B adds:
- simple conflict prevention when a `restaurant_table_id` is provided (prevents double-booking the same table/time)

### Phase 6: day-of booking operations (dev only)

Phase 6 extends restaurant day-of operations:
- Statuses: `arrived|seated|completed|no_show` (in addition to Phase 5 statuses)
- Timestamps: `arrived_at|seated_at|completed_at|no_show_at`
- Native Filament actions for day-of transitions (scoped in Restaurant panel)

### Filament usability polish

We periodically apply small **Filament-native** usability improvements (tables/filters/forms/relation managers/navigation grouping) to make the operational hierarchy clearer:

Restaurant → Branch → Seating Area → Table → Booking

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

