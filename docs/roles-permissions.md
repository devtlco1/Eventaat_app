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

