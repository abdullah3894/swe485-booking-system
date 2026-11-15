# SPEC-01 — Database schema and seed data

**Status:** Done · **Requirements:** FR-05, FR-09, FR-14, FR-15 · **Architecture:** §2.4, §2.6

## Goal
Create the five tables of the architecture and seed synthetic data, so the app runs with `php artisan migrate:fresh --seed`.

## Scope
1. Delete `database/migrations/0001_01_01_000001_create_cache_table.php` and `0001_01_01_000002_create_jobs_table.php`.
2. Edit `0001_01_01_000000_create_users_table.php`: remove `password_reset_tokens` and `sessions`; add `role` enum(`member`, `supervisor`) default `member`.
3. New migrations:
   - `items`: id, `name` string unique, `type` enum(`field`, `equipment`), `is_active` boolean default true, timestamps.
   - `bookings`: id, `user_id` FK → users, `item_id` FK → items (restrict on delete), `date` date, `start_time` time, `end_time` time, `status` enum(`pending`, `approved`, `rejected`, `cancelled`) default `pending`, `decided_at` timestamp nullable, timestamps. Index on (`item_id`, `date`, `status`).
   - `booking_locks`: id, `item_id`, `date`; unique (`item_id`, `date`).
4. Models: `App\Models\User` (add `role` to fillable, `isSupervisor()`), `App\Modules\Catalog\Models\Item`, `App\Modules\Booking\Models\Booking`, `App\Modules\Booking\Models\BookingLock`. No `SoftDeletes`.
5. Factories: `UserFactory` (role `member`), `ItemFactory`.
6. Seeders: one Supervisor (`supervisor@example.com` / `Password123`), 3 Members (`member1..3@example.com` / `Password123`), items: Pitch A, Pitch B (field), Football Set (equipment), Old Goal (equipment, `is_active=false`).

## Acceptance criteria
- `php artisan migrate:fresh --seed` succeeds on MySQL.
- The database contains exactly: `users`, `items`, `bookings`, `booking_locks`, `migrations`.
- Inserting two `booking_locks` rows with the same (`item_id`, `date`) fails.
- Exactly one user has role `supervisor`; new users default to `member`.
- Deleting an item that has bookings fails (FK restrict).

## Tests (PHPUnit, `tests/Feature/SchemaTest.php`)
- seeded supervisor exists with role `supervisor`
- user created without role gets `member`
- duplicate `booking_locks` (item, date) throws
- booking defaults to status `pending`

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-01.md  (the task)
- REQUIREMENTS.md and M1-architecture-draft.md only if you need more detail

Implement SPEC-01 exactly as written. Create or edit only:
- the migration changes and new migrations in database/migrations
- the models, factories and seeders listed in SPEC-01
- tests/Feature/SchemaTest.php

Do not add tables, routes, controllers or views. Do not use SoftDeletes.
Do not run migrations. Do not modify any other files.
When finished, list every file you created, edited or deleted.
```

## Result
- Raw commit: <hash of the raw(ai) commit>
- Human fixes:
  1. Removed the `date` casts on `Booking` and `BookingLock` — they stored `Y-m-d 00:00:00`, which breaks date-equality queries under the SQLite test database (MySQL truncates, so tests and production would disagree).
  2. Added `test_deleting_item_with_bookings_fails` — the FK-restrict acceptance criterion had no test.
- Note: DEVAI added an FK on `booking_locks.item_id` (not in the SPEC); kept — it is DB-level integrity, not cross-module code access.
- Verified: `php artisan migrate:fresh --seed` on MySQL and `php artisan test` pass.