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

## Phase 4A: mobile restaurant discovery API

Auth for all endpoints below:
`Authorization: Bearer <token>` (customer token only)

### GET `/api/mobile/restaurants`

Query params:
- `q` (optional): search by restaurant name
- `per_page` (optional): pagination size (max 50)

Response (paginated):

```json
{
  "data": [
    {
      "id": 1,
      "name": "Demo Restaurant A",
      "slug": "demo-restaurant-a",
      "active_branches_count": 1
    }
  ],
  "links": { },
  "meta": { }
}
```

### GET `/api/mobile/restaurants/{restaurant:slug}`

Response:

```json
{
  "data": {
    "id": 1,
    "name": "Demo Restaurant A",
    "slug": "demo-restaurant-a",
    "branches": [
      {
        "id": 10,
        "name": "Main Branch",
        "code": "main",
        "seating_areas": [
          {
            "id": 100,
            "name": "Indoor",
            "type": "indoor",
            "tables": [
              { "id": 1000, "label": "T1", "capacity": 2 }
            ]
          }
        ]
      }
    ]
  }
}
```

When API endpoints are introduced in later phases, this file must be updated in the same step, per `docs/eventaat_blueprint_v1.md`.

