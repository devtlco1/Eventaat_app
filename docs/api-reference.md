## API reference (mobile `/api/mobile`)

Verify current routes with **`php artisan route:list --path=api/mobile`** (registered in **`routes/api.php`**). Historical phase labels below are for traceability only.

Server-to-server webhooks under **`/api/webhooks/*`** are documented separately below; they **do not** use mobile Sanctum auth and **do not** change **`/api/mobile`** success payloads.

### POST `/api/webhooks/twilio/otp-status` (Phase 7I)

Twilio **delivery status callback** endpoint (configure in the Twilio console / Messaging Service **Status callback** URL for OTP traffic). **No** Bearer token; **no** mobile session.

- **Security**
  - **Preferred:** Twilio **`X-Twilio-Signature`** HMAC validated with **`TWILIO_AUTH_TOKEN`** (same secret as the REST API auth token).
  - **Fallback** (e.g. local tunneling with an empty auth token): **`X-Eventaat-Webhook-Secret`** must exactly match **`TWILIO_WEBHOOK_SECRET`**. If **both** token and secret are unset, requests are rejected (**403**). Invalid signature or secret → **403**. Tokens/secrets must never appear in logs.
- **Request:** **`application/x-www-form-urlencoded`** body. Common fields: **`MessageSid`**, **`MessageStatus`** and/or **`SmsStatus`**, optional **`ErrorCode`**, **`ErrorMessage`**, **`AccountSid`**. Other fields (**`To`**, **`From`**, **`Body`**, …) are **not** stored (avoids persisting full numbers or OTP-bearing bodies).
- **Behavior:** Looks up **`otp_delivery_attempts.provider_message_sid` = `MessageSid`**. Updates **`status`** (**`delivered`**, **`undelivered`**, **`failed`**, **`sent`**, intermediate states merge into **`metadata.provider_status`**) and safe **`metadata`** (e.g. **`callback_received_at`**). Unknown **`MessageSid`**: responds **200** with an empty body and does **not** create a row.
- **Response:** **200** empty body on success; **403** when authentication fails.

Mobile **`request-otp`** / **`verify-otp`** contracts are unchanged.

Phase 0–2 did not introduce any mobile/customer API endpoints yet.

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

**Validation (`phone`):** required string in **E.164-style** international form: must start with **`+`**, then a non-zero digit and **7–14** further digits (8–15 digits after `+` in total). Examples: **`+15550000001`**, **`+9647700001781`**. Rejected examples: missing **`+`**, national-only digits, placeholders such as **`X`**, too few digits, or non-digit characters.

Response **unchanged on success**:

```json
{ "success": true, "expires_at": "2026-04-29T12:00:00.000000Z" }
```

Invalid `phone` returns **422** with Laravel validation errors on `phone`. Successful JSON is unchanged regardless of server **`OTP_DRIVER`**. Delivery channel is configured only on the server: **`twilio_sms`** sends SMS copy (**Phase 7D**): **`Eventaat code: {code}. Do not share this code.`**; **`twilio_whatsapp`** sends via an approved Twilio WhatsApp Authentication Content Template (**Phase 7F**, **`contentVariables`** slot **`1`** = code — no plain SMS body). WhatsApp remains optional until Meta/Twilio approve the Authentication template.

**Rate limits (Phase 7G, cache-backed):** Per normalized **`phone`** (after validation): **`request-otp`** enforces a minimum spacing (**`OTP_REQUEST_COOLDOWN_SECONDS`**, default **60**) and a rolling hourly cap (**`OTP_REQUEST_MAX_PER_HOUR`**, default **5**) on successful sends. When exceeded, response **429** with **`message`** and **`retry_after`** (seconds), plus **`Retry-After`** header.

### POST `/api/mobile/auth/verify-otp`

Request:

```json
{ "phone": "+15550000001", "otp": "123456", "name": "Optional Name" }
```

**Validation (`phone`):** same E.164-style rules as **`request-otp`** (must include leading **`+`** and match the normalized format above).

**Rate limits (Phase 7G):** Failed **`verify-otp`** attempts (wrong/expired OTP, **422**) count toward **`OTP_VERIFY_MAX_ATTEMPTS`** (default **5**) per **`phone`** within **`OTP_VERIFY_DECAY_MINUTES`** (default **10**). Over the limit: **429** with **`message`**, **`retry_after`**, **`Retry-After`** header — same generic messaging for wrong code vs no row (no existence leak). Successful verification clears that phone’s failure bucket for the next flow.

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
        "booking_availability": {
          "is_booking_enabled": true,
          "booking_duration_minutes": 90,
          "min_advance_minutes": 60,
          "max_advance_days": 30,
          "open_time": "10:00:00",
          "close_time": "23:00:00",
          "mon": true,
          "tue": true,
          "wed": true,
          "thu": true,
          "fri": true,
          "sat": true,
          "sun": true
        },
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

**`booking_availability` (Phase 8B):** each branch includes this key. If the branch has no `BranchAvailabilityRule` row, the value is **`null`**. When present, the object contains **only** customer-facing fields (no `id`, `branch_id`, `notes`, or timestamps):

- `is_booking_enabled` (boolean)
- `booking_duration_minutes`, `min_advance_minutes`, `max_advance_days` (integers)
- `open_time`, `close_time` (strings such as `HH:mm:ss`, or `null` when unset)
- `mon` … `sun` (booleans) — weekday flags use the same meaning as server validation (ISO weekday Monday = 1 … Sunday = 7 maps to `mon` … `sun`)

The mobile app may use these values for UX and best-effort client checks; **`POST /api/mobile/bookings`** validation remains authoritative.

When API endpoints are introduced in later phases, this file must be updated in the same step, per `docs/eventaat_blueprint_v1.md`.

## Phase 5A: mobile customer bookings API

Auth for all endpoints below:
`Authorization: Bearer <token>` (customer token only)

Booking statuses (Phase 5A–6):
```text
pending
accepted
rejected
cancelled
arrived
seated
completed
no_show
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

Branch availability rules (**Phase 8A**) apply when the branch has a configured `BranchAvailabilityRule` row:

- If booking is disabled for the branch → validation error on **`branch_id`**: “Booking is disabled for this branch.”
- **`starts_at`** must be at least **`min_advance_minutes`** after “now”.
- **`starts_at`** must not be later than **end of day** at **`now + max_advance_days`** (calendar-day bound).
- **`starts_at`** weekday must be enabled (`mon` … `sun` flags).
- When **`open_time`** / **`close_time`** are set, the booking **start clock time** must fall within those bounds (inclusive).

Branches without a rule keep Phase 5A behavior only (no availability gate).

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
    "arrived_at": null,
    "seated_at": null,
    "completed_at": null,
    "no_show_at": null,
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

Branch availability examples (422):

```json
{
  "message": "Booking is disabled for this branch.",
  "errors": { "branch_id": ["Booking is disabled for this branch."] }
}
```

```json
{
  "message": "Bookings must be made at least 60 minutes in advance.",
  "errors": { "starts_at": ["Bookings must be made at least 60 minutes in advance."] }
}
```

### GET `/api/mobile/bookings`

List the authenticated customer's bookings only.

Query params:
- `status` (optional): one of `pending|accepted|rejected|cancelled|arrived|seated|completed|no_show`
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
  "cancelled_at": null,
  "arrived_at": null,
  "seated_at": null,
  "completed_at": null,
  "no_show_at": null
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

## Phase 14B: mobile customer reviews API

Auth for all endpoints below matches existing mobile routes:

`Authorization: Bearer <token>` — Sanctum token issued to a **`customer`** user only.

Middleware stack on each route: **`auth:sanctum`**, **`mobile.token`**, **`mobile.customer`**.

### POST `/api/mobile/bookings/{booking}/review`

Creates a review for the authenticated customer’s booking.

Rules:

- Booking **`customer_id`** must match the authenticated user (**otherwise `404`**, same style as booking detail endpoints).
- Booking **`status`** must be **`completed`** (**otherwise `422`** with `errors.booking`).
- **One review per booking**: duplicate submissions return **`422`** with a clear message (also enforced by a DB unique index on `booking_id`).
- **`rating`** required: integer **1–5**.
- **`comment`** optional: string, **max 2000** characters.

Server-derived fields (ignored if sent by client):

- `restaurant_id`, `branch_id`, `booking_id`, `user_id` from the booking / auth user
- `status` → **`pending_review`**
- `source` → **`mobile`**
- `customer_name` → snapshot from user **`name`**, or **`Customer`** when name is blank
- `customer_phone` → optional snapshot from user **`phone`** (stored only; **never** returned from the public restaurant reviews endpoint)

Response (**201**):

```json
{
  "review": {
    "id": 10,
    "restaurant": { "id": 1, "name": "Review Cafe", "slug": "review-cafe" },
    "branch": { "id": 2, "name": "Main", "code": "main" },
    "booking_id": 55,
    "rating": 5,
    "comment": "Great experience",
    "status": "pending_review",
    "source": "mobile",
    "created_at": "2026-05-01T12:00:00.000000Z",
    "updated_at": "2026-05-01T12:00:00.000000Z"
  }
}
```

Duplicate example (**422**):

```json
{
  "message": "A review already exists for this booking.",
  "errors": {
    "booking": ["A review already exists for this booking."]
  }
}
```

Non-completed booking example (**422**):

```json
{
  "message": "Cannot submit review for this booking.",
  "errors": {
    "booking": ["The booking must be completed before submitting a review."]
  }
}
```

### GET `/api/mobile/me/reviews`

Lists reviews belonging to the authenticated customer only.

Query params:

- `per_page` (optional): pagination size (**max 50**)

Response: paginated collection of review objects with the **same shape** as `review` in the POST response (`restaurant` and `branch` nested objects included).

### GET `/api/mobile/me/reviews/{review}`

Returns one review if it belongs to the authenticated customer.

If the review belongs to another user → **`404`**.

Response body: single review object (same keys as `review` above).

### GET `/api/mobile/restaurants/{restaurant:slug}/reviews`

Lists **published** reviews for an **active** restaurant.

Rules:

- If the restaurant slug does not exist or the restaurant is **not active** → **`404`**.
- Only reviews with **`status=published`** are returned.

Query params:

- `per_page` (optional): pagination size (**max 50**)

Each item in `data` contains **only**:

- `id`
- `customer_name` (display string)
- `rating`
- `comment`
- `created_at`

Does **not** include: `customer_phone`, `phone`, `admin_notes`, `status`, `source`, `booking_id`, or nested restaurant/branch objects.

