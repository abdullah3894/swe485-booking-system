# Project Context (attach to every DEVAI prompt)

## Project
Sports Field & Equipment Booking System. Members request bookings for fields/equipment; a Supervisor approves or rejects them; the system prevents double bookings.
Source of truth: `REQUIREMENTS.md` (FR-01–FR-16, NFR-01–NFR-05) and `M1-architecture-draft.md` (Option A).

## Stack (fixed)
- PHP 8.2, Laravel 12, Blade views (no Livewire, no SPA, no Inertia)
- MySQL / MariaDB (InnoDB)
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`
- Tests: PHPUnit (Laravel default)

## Modules and table ownership
| Module | Namespace | Owns tables | Public service |
|---|---|---|---|
| Identity | `App\Models\User` + `App\Modules\Identity` | `users` | `IdentityService` |
| Catalog | `App\Modules\Catalog` | `items` | `ItemCatalogService` |
| Booking | `App\Modules\Booking` | `bookings`, `booking_locks` | `BookingService` |

**Rule:** a module may read, write or lock only its own tables. It reaches another module only through that module's service. No cross-module joins.

## Hard limits
- 6 screens: Login, Register, Request Booking, My Bookings, Supervisor Dashboard, Manage Items
- 14 routes (listed in `M1-architecture-draft.md` §2.6) — do not add routes
- 5 tables: `users`, `items`, `bookings`, `booking_locks`, `migrations` — do not add tables
- 2 roles: `member`, `supervisor`

## Booking rules (never change)
- Intervals are half-open `[start, end)`. Overlap: `new.start < existing.end AND new.end > existing.start`.
- Only `pending` and `approved` bookings block a slot.
- Operating hours 06:00–23:00.
- `createRequest` order: transaction → lock `booking_locks(item_id, date)` with `FOR UPDATE` → overlap query → insert → commit.

## Output rules
- Generate only the files the SPEC asks for.
- Every new behaviour comes with PHPUnit tests.
- Synthetic data only.
