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
