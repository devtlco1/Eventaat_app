# eventaat — Product & Technical Blueprint v1

## 1. Strategic Reset

This blueprint is the new source of truth for rebuilding **eventaat** from scratch.

The previous custom dashboard direction is abandoned because the project needs a ready, stable, admin-dashboard foundation instead of a custom-designed dashboard.

## 2. Core Decision

### Backend + Dashboards

Use:

- **Laravel**
- **Filament Panels**
- **PostgreSQL**

### Mobile App

Use:

- **Expo React Native**
- Customer app only

### API

Use:

- Laravel REST API for the mobile customer app
- Laravel/Filament internal panels for admin and restaurant operations

## 3. Golden Rule

Do **not** design the dashboards from scratch.

All dashboard UI must use Filament native components:

- Panels
- Resources
- Tables
- Forms
- Filters
- Actions
- Badges
- Relation managers
- Policies
- Built-in layouts

No custom dashboard cards, fake stats, mock widgets, or random UI sections unless explicitly approved.

## 4. Required System Areas

### 4.1 Platform Admin Panel

URL:

```text
/platform
```

Used by:

- `super_admin`
- `operations_admin`

Purpose:

- Manage the full Eventaat platform
- Manage restaurants, branches, tables, users, staff assignments, and future platform operations
- Review platform-wide data

Rules:

- Platform users can access all restaurants and records according to their role
- Customers must never access this panel
- Restaurant staff must not access this panel unless explicitly granted a platform role

### 4.2 Restaurant Operations Panel

URL:

```text
/restaurant
```

Used by:

- `restaurant_owner`
- `branch_manager`
- `restaurant_host`

Purpose:

- Manage restaurant operational data
- Manage branches, seating areas, tables, and later bookings
- Scope all data to the assigned restaurant/branch

Rules:

- `restaurant_owner` sees the assigned restaurant
- `branch_manager` sees the assigned branch scope
- `restaurant_host` sees operational scope and can later handle table/booking operations according to policy
- Customers must never access this panel

### 4.3 Mobile Customer App

Used by:

- `customer`

Purpose:

- Customer OTP login/register
- Restaurant discovery
- Restaurant details
- Create and track bookings
- Later: favorites, notifications, reviews, offers

Rules:

- Any user registering from mobile is `customer` only
- Customer users must not access `/platform` or `/restaurant`
- Customer API must be separated from admin panel behavior

## 5. Roles

### Platform Roles

```text
super_admin
operations_admin
```

### Restaurant Roles

```text
restaurant_owner
branch_manager
restaurant_host
```

### Customer Role

```text
customer
```

## 6. Repository Structure

```text
eventaat/
├── backend/
│   ├── Laravel app
│   ├── Filament panels
│   ├── REST API
│   └── PostgreSQL integration
│
├── mobile/
│   └── Expo React Native customer app
│
└── docs/
    ├── eventaat_blueprint_v1.md
    ├── product-blueprint.md
    ├── roles-permissions.md
    ├── api-reference.md
    └── implementation-plan.md
```

## 7. Development Environment Rules

Recommended path:

```text
/Users/amjadmohammed/Projects/eventaat
```

Do not develop on Desktop or iCloud-synced folders.

Reason:

- Avoid file sync conflicts
- Avoid corrupted `node_modules`
- Avoid duplicate files like `file 2.ts`
- Avoid Git corruption

## 8. Dashboard Design Rules

### Allowed

- Filament Resources
- Filament Tables
- Filament Forms
- Filament Filters
- Filament Actions
- Filament Badges
- Filament Relation Managers
- Filament Panels
- Filament Policies

### Not Allowed

- Custom dashboard design from scratch
- Random Tailwind dashboard layouts
- Fake cards
- Mock stats
- Placeholder pages
- Sidebar links to features not implemented
- Unapproved UI libraries
- Copy/CSV/Excel/PDF/Print toolbar buttons unless explicitly needed
- “Delete selected” actions unless explicitly approved

## 9. Phase Plan

## Phase 0 — Foundation Only

Goal:

Create a clean Laravel + Filament foundation.

Deliver:

- Laravel app inside `backend/`
- PostgreSQL configuration
- Filament installed
- Two Filament panels:
  - `/platform`
  - `/restaurant`
- Basic User model and role direction
- Documentation files
- Local Git initialization

Do not build:

- Restaurant resources
- Booking resources
- Mobile UI
- Offers
- Stories
- Events
- Finance
- Call center
- Complaints
- Fake dashboard pages
- Custom dashboard UI

Acceptance:

- Laravel runs locally
- Filament panels open
- Login works
- No custom dashboard design
- README/docs updated
- Local commit created

Commit:

```text
chore: initialize eventaat laravel filament foundation
```

## Phase 1 — Auth, Roles, and Panel Access

Goal:

Create real authentication and role-based panel access.

Deliver:

- Users
- Roles
- Permissions
- Panel access rules
- Customer blocked from panels
- Restaurant staff blocked from platform unless given platform role
- Basic audit structure if needed

Acceptance:

- `super_admin` can access `/platform`
- `operations_admin` can access `/platform`
- `restaurant_owner` can access `/restaurant`
- `branch_manager` can access `/restaurant`
- `restaurant_host` can access `/restaurant`
- `customer` cannot access any panel

## Phase 2 — Restaurant Foundation

Goal:

Add real restaurant operational foundation.

Deliver:

- Restaurant model/resource
- Branch model/resource
- SeatingArea model/resource
- RestaurantTable model/resource
- RestaurantStaffAssignment model/resource
- Filament tables/forms using native Filament only
- Policies/scopes for Platform vs Restaurant panels

Acceptance:

- Platform panel can manage all restaurants
- Restaurant panel sees only assigned restaurant/branch data
- No bookings yet
- No mobile changes yet

## Phase 3 — Mobile API Foundation

Goal:

Create customer API foundation for Expo mobile.

Deliver:

- Customer OTP login/register
- Customer profile endpoint
- API auth strategy
- API documentation
- No booking yet

Acceptance:

- Mobile customer can register/login
- Mobile-created users are `customer`
- Customer cannot access Filament panels

## Phase 4 — Restaurant Discovery

Goal:

Mobile customers can browse restaurants.

Deliver:

- Public/customer API for restaurant list
- Restaurant details
- Branch availability basics
- Mobile screens for list/details

Acceptance:

- Customer can view active restaurants
- No booking creation yet

## Phase 5 — Core Booking Flow

Goal:

Build the first real reservation lifecycle.

Deliver:

- Booking model
- Booking status flow
- Customer creates booking
- Restaurant accepts/rejects/updates booking
- Platform can view bookings
- Audit logs
- Notifications placeholder

Acceptance:

- Customer can request booking
- Restaurant can act on booking
- Platform can monitor bookings

## Phase 6 — Day-of Operations

Goal:

Support visit-day operations.

Possible statuses:

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

Arabic UI label for `no_show`:

```text
لم يحضر
```

## Phase 7 — Notifications / WhatsApp

Goal:

Add real notification workflow.

Deliver:

- OTP provider integration
- Booking confirmation templates
- Reminder templates
- Cancellation templates

## Phase 8 — Platform Operations

Goal:

Add operational tools only when core booking is stable.

Possible modules:

- Complaints
- Call center
- Subscriptions
- Finance
- Offers
- Events
- Stories
- Reviews

These must not be built early.

## 10. API Documentation Rule

Any API endpoint added, changed, or removed must update:

```text
docs/api-reference.md
```

in the same step.

## 11. Documentation Rule

Every implementation step must update:

- README
- relevant docs
- API docs if endpoints changed

## 12. Git Rule

Every approved implementation step must end with:

```bash
git add .
git commit -m "<appropriate topical commit message>"
```

Do not push to GitHub until the repository remote is confirmed.

## 13. Cursor Working Rules

Cursor must always:

- Read this blueprint first
- Work in Plan mode before editing
- Ask for approval before implementation
- Keep scope small
- Avoid broad formatting
- Avoid custom dashboards
- Use Filament native components
- Report files changed
- Report commands run
- Report tests/builds
- Commit locally after success

Cursor must never:

- Invent dashboard design
- Build mock pages
- Add fake modules
- Add unapproved features
- Work on mobile during backend phases unless explicitly requested
- Modify unrelated files
- Push to GitHub without approval

## 14. Phase 0 Cursor Prompt

```text
We are rebuilding Eventaat from scratch.

Read docs/eventaat_blueprint_v1.md first and treat it as the source of truth.

Stack:
- backend: Laravel + Filament
- database: PostgreSQL
- mobile: Expo React Native
- API: Laravel REST API for mobile

Main rule:
Do not design dashboards from scratch.
Use Filament native panels/resources/tables/forms/actions/badges only.

Phase 0 only:
- Initialize Laravel backend inside /backend.
- Install and configure Filament.
- Configure PostgreSQL.
- Create two Filament panels:
  - /platform
  - /restaurant
- Add documentation files.
- Do not add restaurant resources yet.
- Do not add bookings.
- Do not add mobile UI yet.
- Do not add custom dashboard cards/stats/mock pages.

Before implementation:
Show the Phase 0 execution plan:
- files/folders to create
- packages to install
- commands to run
- what will NOT be built

Do not modify code until I approve the plan.

After approval:
- Implement Phase 0 only.
- Run required checks.
- Update README/docs.
- Commit locally:
  chore: initialize eventaat laravel filament foundation

Do not push to GitHub.
```
