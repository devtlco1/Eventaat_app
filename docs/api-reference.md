## API reference (Phase 0–3)

Phase 0–2 does not introduce any mobile/customer API endpoints yet.

Phase 1 adds:
- Role-based Filament panel access (internal dashboards)

Phase 1 does **not** add/change/remove:
- Mobile/customer API endpoints

Phase 2 adds:
- Restaurant operational foundation models/resources (internal dashboards only)

Phase 2 does **not** add/change/remove:
- Mobile/customer API endpoints

Phase 3 adds (mobile/customer API):

### POST `/api/mobile/auth/request-otp`

Request:

```json
{ "phone": "+15550000001" }
```

Response:

```json
{ "success": true, "expires_at": "2026-04-29T12:00:00.000000Z" }
```

### POST `/api/mobile/auth/verify-otp`

Request:

```json
{ "phone": "+15550000001", "otp": "123456", "name": "Optional Name" }
```

Response:

```json
{
  "token": "<sanctum_token>",
  "me": {
    "id": 1,
    "name": "Optional Name",
    "phone": "+15550000001",
    "role": "customer",
    "profile_completed": true,
    "missing_fields": []
  }
}
```

If name is missing for a new customer:

```json
{
  "token": "<sanctum_token>",
  "me": {
    "id": 1,
    "name": null,
    "phone": "+15550000001",
    "role": "customer",
    "profile_completed": false,
    "missing_fields": ["name"]
  }
}
```

### GET `/api/mobile/me`

Auth: `Authorization: Bearer <token>`

Response:

```json
{
  "id": 1,
  "name": "Optional Name",
  "phone": "+15550000001",
  "role": "customer",
  "profile_completed": true,
  "missing_fields": []
}
```

### PATCH `/api/mobile/me`

Auth: `Authorization: Bearer <token>`

Request:

```json
{ "name": "New Name" }
```

Response:

```json
{
  "me": {
    "id": 1,
    "name": "New Name",
    "phone": "+15550000001",
    "role": "customer",
    "profile_completed": true,
    "missing_fields": []
  }
}
```

### POST `/api/mobile/auth/logout`

Auth: `Authorization: Bearer <token>`

Response:

```json
{ "success": true }
```

When API endpoints are introduced in later phases, this file must be updated in the same step, per `docs/eventaat_blueprint_v1.md`.

