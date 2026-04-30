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

## Bookings (Phase 5A)

Phase 5A introduces booking operations:

- **Platform panel** (`super_admin`, `operations_admin`): can view and act (accept/reject/cancel) on all bookings.
- **Restaurant panel** (`restaurant_owner`, `branch_manager`, `restaurant_host`): can view and act on bookings in their scoped restaurant/branch only.
- **Customers**: can create/list/view/cancel their own bookings via the mobile API, and can never access Filament panels.

