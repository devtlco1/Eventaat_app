## Implementation plan (Phase 0 only)

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

