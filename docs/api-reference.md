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

## Phase 5A: mobile customer bookings API

Auth for all endpoints below:
`Authorization: Bearer <token>` (customer token only)

Booking statuses in Phase 5A:
```text
pending
accepted
rejected
cancelled
```

### POST `/api/mobile/bookings`

Create a booking request. Status always starts as `pending`.

Request:

```json
{
  "restaurant_id": 1,
  "branch_id": 10,
  "seating_area_id": 100,
  "restaurant_table_id": 1000,
  "starts_at": "2026-05-01T19:00:00.000Z",
  "party_size": 2,
  "customer_note": "Window seat if possible"
}
```

Rules:
- `customer_id` is always taken from the authenticated user (not provided by client)
- restaurant and branch must be active
- branch must belong to restaurant
- `starts_at` must be in the future
- seating area (if provided) must belong to the branch and be active
- table (if provided) must belong to the seating area / branch and be active
- if table is provided, `party_size` must not exceed `table.capacity`
- **Conflict prevention (Phase 5B)**: if a table is provided, a booking is rejected when another `pending` or `accepted` booking exists for the same table within a fixed conflict window (simple block model).

Response (201):

```json
{
  "booking": {
    "id": 1,
    "status": "pending",
    "starts_at": "2026-05-01T19:00:00.000000Z",
    "party_size": 2,
    "customer_note": "Window seat if possible",
    "restaurant_note": null,
    "accepted_at": null,
    "rejected_at": null,
    "cancelled_at": null,
    "restaurant": { "id": 1, "name": "Demo Restaurant A", "slug": "demo-restaurant-a" },
    "branch": { "id": 10, "name": "Main Branch", "code": "main" },
    "seating_area": { "id": 100, "name": "Indoor", "code": "indoor", "type": "indoor" },
    "table": { "id": 1000, "label": "T2", "capacity": 4 },
    "created_at": "2026-05-01T10:00:00.000000Z",
    "updated_at": "2026-05-01T10:00:00.000000Z"
  }
}
```

Validation error example (422):

```json
{
  "message": "The party size field must not exceed table capacity.",
  "errors": { "party_size": ["Party size must not exceed table capacity."] }
}
```

Conflict error example (422):

```json
{
  "message": "The restaurant table id field is invalid.",
  "errors": { "restaurant_table_id": ["Table is not available at the selected time."] }
}
```

### GET `/api/mobile/bookings`

List the authenticated customer's bookings only.

Query params:
- `status` (optional): one of `pending|accepted|rejected|cancelled`
- `per_page` (optional): pagination size (max 50)

Response (paginated):

```json
{
  "data": [
    { "id": 1, "status": "pending", "starts_at": "2026-05-01T19:00:00.000000Z", "party_size": 2 }
  ],
  "links": { },
  "meta": { }
}
```

### GET `/api/mobile/bookings/{booking}`

Get booking details for the authenticated customer.

If the booking belongs to another customer, the API returns `404`.

Response:

```json
{
  "id": 1,
  "status": "pending",
  "starts_at": "2026-05-01T19:00:00.000000Z",
  "party_size": 2,
  "customer_note": "Window seat if possible",
  "restaurant_note": null,
  "accepted_at": null,
  "rejected_at": null,
  "cancelled_at": null
}
```

### POST `/api/mobile/bookings/{booking}/cancel`

Cancel a booking for the authenticated customer.

Rules:
- Customer can cancel only their own booking
- Allowed only when status is `pending` or `accepted`
- Sets `status=cancelled` and `cancelled_at`

Response:

```json
{
  "booking": {
    "id": 1,
    "status": "cancelled",
    "cancelled_at": "2026-05-01T12:00:00.000000Z"
  }
}
```

