## Eventaat (Phase 0)

This repository is a clean **Phase 0 foundation** rebuild of Eventaat.

Source of truth: `docs/eventaat_blueprint_v1.md`.

### What exists in Phase 0

- **Backend**: Laravel app in `backend/`
- **Database**: PostgreSQL configuration (see `backend/.env`)
- **Dashboards**: Filament panels only
  - **Platform panel**: `/platform`
  - **Restaurant panel**: `/restaurant`
- **Docs**: Phase 0-only docs in `docs/`

### What does NOT exist in Phase 0

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

