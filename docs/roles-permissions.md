## Roles & permissions (Phase 1)

Phase 1 implements **role-based panel access** using **Spatie roles** as the source of truth.

Role direction from `docs/eventaat_blueprint_v1.md`:

### Platform roles

- `super_admin`
- `operations_admin`

### Restaurant roles

- `restaurant_owner`
- `branch_manager`
- `restaurant_host`

### Customer role

- `customer`

## Mobile customer rules (Phase 3)

- Phone number is the mobile login identity.
- Any user created via mobile OTP verification is **always** assigned the `customer` role.
- Customers must not access Filament panels (`/platform`, `/restaurant`).

## Unified dashboard login (`/login`)

- **`GET /login` / `POST /login`**: shared staff/customer credential form using the **web** session guard (not Sanctum). Intended for dashboard operators only.
- After sign-in (or when an already authenticated user opens `/login`), redirects follow role precedence:
  1. **`super_admin` / `operations_admin`** → `/platform`
  2. **`restaurant_owner` / `branch_manager` / `restaurant_host`** → `/restaurant`
  3. Otherwise (typically **`customer`** only or no dashboard role): session cleared and redirect back to `/login` with an explanatory message — customers must use the **mobile app**.
- **`/platform/login`** and **`/restaurant/login`** remain valid Filament entry points with the same panel access rules as today.
- Signing out from either Filament panel redirects to **`/login`** (not `/platform/login` or `/restaurant/login`), so switching accounts never strands restaurant staff on the wrong panel login screen.

## Panel access rules (Phase 1)

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

Denied:
- `restaurant_owner`
- `branch_manager`
- `restaurant_host`
- `customer`

### `/restaurant`

Allowed:
- `restaurant_owner`
- `branch_manager`
- `restaurant_host`

Denied:
- `super_admin`
- `operations_admin`
- `customer`

## Restaurant panel scoping (Phase 2)

Phase 2 introduces restaurant operational data and scoping via `RestaurantStaffAssignment`:

- `restaurant_owner`: restaurant-level access (all branches under assigned restaurant)
- `branch_manager`: branch-scoped access (assigned branch)
- `restaurant_host`: branch-scoped operational access (assigned branch)

### Branch booking availability rules (Phase 8A)

Availability rules are edited from each Branch record (**Booking availability** relation manager):

- `restaurant_owner` / `branch_manager`: can create/update/delete rules only on branches they are allowed to edit (same branch edit permissions as the Branch resource).
- `restaurant_host`: branch view access only — booking availability actions follow Branch edit rules (typically **no** create/edit/delete).

## Bookings (Phase 5A)

Phase 5A introduces booking operations:

- **Platform panel** (`super_admin`, `operations_admin`): can view and act (accept/reject/cancel) on all bookings.
- **Restaurant panel** (`restaurant_owner`, `branch_manager`, `restaurant_host`): can view and act on bookings in their scoped restaurant/branch only.
- **Customers**: can create/list/view/cancel their own bookings via the mobile API, and can never access Filament panels.

Manual booking creation (Filament):
- Platform and Restaurant panel users can create bookings manually using customer phone (phone-first input), within their allowed scope.
- Manual creation can create/reuse customer users by phone and assigns only the `customer` role.

## Platform booking notifications (Phase 9A)

Phase 9A adds a **read-only** Filament resource **Booking notifications** under `/platform`:

- Allowed: `super_admin`, `operations_admin`
- Denied for all other roles (including restaurant staff)

Restaurant panel users do **not** get a booking notification resource in Phase 9A.

## Platform notification templates (Phase 9B)

Phase 9B adds a Filament resource **Notification templates** under `/platform`:

- Allowed: `super_admin`, `operations_admin`
- Denied for all other roles (including restaurant staff and customers)

This resource is preview-only and does not send notifications (no WhatsApp/SMS/push/email, queues, workers, or retries).

## Platform booking notification dispatch actions (Phase 9C)

Phase 9C extends the Platform **Booking notifications** resource:

- Allowed: `super_admin`, `operations_admin`
- Denied for all other roles (including restaurant staff and customers)

Actions are internal-only and do not send messages externally:

- Mark sent
- Mark skipped
- Mark failed (reason required)

## Platform provider dry-run dispatch (Phase 10A)

Phase 10A adds a **Dry-run dispatch** action and a read-only dispatch attempt history on the Platform Booking notifications resource:

- Allowed: `super_admin`, `operations_admin`
- Denied for all other roles (including restaurant staff and customers)

This is provider-ready foundation only (no real WhatsApp/SMS/push/email sending, no external API calls, no retries).

## Event nights (Phase 11A)

Phase 11A adds **Event nights** (`RestaurantEvent`) resources to both panels:

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

### `/restaurant`

- `restaurant_owner`: can create/edit/view/delete restaurant-wide (`branch_id=null`) and branch-scoped events within their assigned restaurant(s)
- `branch_manager` / `restaurant_host`: can create/edit/view branch-scoped events only (branch is required and must be within scope)

## Offers (Phase 12A)

Phase 12A adds **Offers** (`RestaurantOffer`) resources to both panels (dashboard-only; no mobile/offers APIs in this phase):

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

### `/restaurant`

- `restaurant_owner`: can create/edit/view/delete restaurant-wide offers (`branch_id=null`) and branch-scoped offers within their assigned restaurant(s)
- `branch_manager` / `restaurant_host`: can create/edit/view branch-scoped offers only (branch is required and must be within scope)
- `branch_manager` / `restaurant_host` must not see or access restaurant-wide offers (`branch_id=null`) in the restaurant panel

## Offers approval workflow (Phase 12B)

Phase 12B adds an approval workflow and expiry foundation for dashboard-only offers:

- Statuses: `draft|pending_review|published|rejected|expired|cancelled`
- Platform roles (`super_admin`, `operations_admin`):
  - can create/edit offers
  - can publish directly
  - can approve/reject `pending_review`
  - can cancel offers
- Restaurant roles:
  - can create/edit in-scope offers
  - cannot publish/approve directly
  - can keep offers as `draft` or submit `draft -> pending_review`
- Expiry: ended published offers can be marked `expired` via the `offers:expire` artisan command (no scheduler wiring in this phase).

## Stories (Phase 13A + Phase 13B)

Phase 13A adds **Stories** (`RestaurantStory`) resources to both panels (dashboard-only; no mobile/story APIs in this phase).
Phase 13B adds **`RestaurantStoryItem` slides** managed via Filament relation managers on each story record (same parent-story scoping rules apply).

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

### `/restaurant`

- `restaurant_owner`: can create/edit/view/delete restaurant-wide stories (`branch_id=null`) and branch-scoped stories within their assigned restaurant(s)
- `branch_manager` / `restaurant_host`: can create/edit/view branch-scoped stories only (branch is required and must be within scope)
- `branch_manager` / `restaurant_host` must not see or access restaurant-wide stories (`branch_id=null`) in the restaurant panel

Workflow:
- Restaurant roles can keep stories as `draft` or submit `draft -> pending_review`
- Platform roles can approve/reject `pending_review` and cancel stories

Expiry:
- Ended published stories can be marked `expired` via the `stories:expire` artisan command (no scheduler wiring in this phase).

Story items:
- Items are created/edited/deleted **only through an in-scope parent story** (Filament relation managers inherit the parent resource authorization/scoping).
- Uploads require `APP_URL` + `php artisan storage:link` locally so `/storage/...` previews resolve.

Analytics note:
- Story impressions/views/analytics remain **out of scope** until mobile/customer story viewing exists.

## Customer reviews (Phase 14A)

Phase 14A adds **Reviews** (`RestaurantReview`) resources to both panels (dashboard-only; no mobile review APIs or customer submission UI in this phase).

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

Capabilities:
- Full CRUD over all reviews
- Native moderation actions (per row): approve / reject / hide

### `/restaurant`

- `restaurant_owner`: **read-only** access to assigned restaurant-wide (`branch_id=null`) and branch-scoped reviews within their assigned restaurant(s)
- `branch_manager` / `restaurant_host`: **read-only** access to branch-scoped reviews only (`branch_id` required and within assigned branch scope); restaurant-wide reviews must not be visible or accessible (direct URLs denied as 403/404)
- Restaurant roles must **not** create, edit, delete, or moderate reviews (no approve/reject/hide actions)

Out-of-scope URLs:
- Restaurant panel direct links to reviews outside `RestaurantPanelScope::reviews()` must fail as **403** or **404** (consistent with other scoped resources).

## Mobile customer reviews API (Phase 14B)

Phase 14B adds **authenticated** mobile endpoints under `/api/mobile` using **`auth:sanctum`** + **`mobile.token`** + **`mobile.customer`** (same stack as bookings/discovery):

- **`customer`** (Sanctum): may submit a review for **own completed** bookings; may list/show **own** reviews (`/api/mobile/me/reviews` …); may call **`GET /api/mobile/restaurants/{slug}/reviews`** for **published** reviews of an **active** restaurant only.
- Customers **cannot** approve/reject/hide reviews via API (moderation remains Platform Filament only).
- Wrong booking ownership / wrong review ownership → **404** (consistent with booking detail endpoints).

Non-customer tokens / missing customer role → **`mobile.customer`** middleware responds **403**.

## Support tickets (Phase 15A–15B)

Phase 15A adds **Support tickets** (`SupportTicket`) resources to both panels (dashboard-only; **no** mobile ticket API). Phase 15B adds **platform-only** `SupportTicketActivity` timeline + internal notes — restaurant roles never see or author activity rows.

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

Capabilities:
- Full CRUD over all tickets (including platform-level tickets with no restaurant)
- Native row actions only: **Mark in progress**, **Resolve**, **Close**, **Reopen** (restaurant panel has **no** workflow actions); each transition writes an append-only **status_change** activity after the ticket exists
- **Phase 15B**: ticket **view/edit** pages include relation manager **Internal activity** (read-only rows) + **Add internal note** (`note` activities); rows are append-only (no edit/delete)

### `/restaurant`

- `restaurant_owner`: **read-only** access to tickets for assigned restaurant(s), including restaurant-wide (`branch_id=null`) and branch-scoped rows
- `branch_manager` / `restaurant_host`: **read-only** access to **branch-scoped** tickets only (`branch_id` must be in scope); restaurant-wide tickets and platform-level (`restaurant_id=null`) tickets must not appear or be reachable (**403/404**)

Restaurant roles must **not** create, edit, delete, change ticket status, edit internal notes, add timeline notes, or view the activity relation manager (Phase 15A–15B).

Out-of-scope URLs:
- Restaurant panel direct links outside `RestaurantPanelScope::supportTickets()` fail as **403** or **404** (consistent with other scoped resources).

## Restaurant menus (Phase 16A)

Phase 16A adds **Menus** (`RestaurantMenu` + nested categories/items) to both panels (dashboard-only; **no** mobile menu APIs or guest-facing pages in this phase).

### `/platform`

Allowed:
- `super_admin`
- `operations_admin`

Capabilities:
- Full CRUD over all menus
- Structured menus: manage categories from the menu record; manage items from each category’s nested edit page
- PDF uploads stored on the **`public`** disk under **`menus/`** (local browsing requires `php artisan storage:link`)

### `/restaurant`

Scoping uses `RestaurantPanelScope::menus()` (same split as offers/stories):

- `restaurant_owner`: restaurant-wide menus (`branch_id=null`) plus branch menus for assigned restaurant(s); **delete** menus owner-only (matches offers pattern)
- `branch_manager` / `restaurant_host`: branch-scoped menus only (`branch_id` must be in scope); restaurant-wide menus must not appear or be reachable (**403/404**)

Structured categories/items inherit parent-menu authorization (relation managers + nested category edit).

Out-of-scope URLs:
- Restaurant panel direct links outside `RestaurantPanelScope::menus()` fail as **403** or **404**.

## Event bookings link (Phase 11B)

Phase 11B allows linking bookings to events from dashboards:

- Platform panel bookings: platform roles can optionally select a published, bookable event when creating/editing a booking.
- Restaurant panel bookings:
  - `restaurant_owner`: can link to compatible events in assigned restaurant(s)
  - `branch_manager` / `restaurant_host`: can link only to compatible branch-scoped events in their assigned branch scope

## Event bookings operations view (Phase 11C)

Phase 11C adds event-side operational visibility in dashboards:

- Platform panel: platform roles can view linked bookings on an event record (relation manager table).
- Restaurant panel: restaurant staff can view linked bookings only for in-scope events (resource scoping applies).
