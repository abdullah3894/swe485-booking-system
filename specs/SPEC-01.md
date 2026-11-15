# SPEC-01 — Database schema and seed data

**Status:** Draft · **Requirements:** FR-05, FR-09, FR-14, FR-15 · **Architecture:** §2.4, §2.6

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
You are working on a Laravel 12 project. Read the attached CONTEXT.md and follow it strictly.
Implement SPEC-01 exactly as written (attached). Generate only:
- the migration changes and new migrations
- the models, factories and seeders listed
- tests/Feature/SchemaTest.php
Do not add tables, routes, controllers or views. Do not use SoftDeletes.
Output each file with its full path and complete content.
```

## Result
- Raw commit: <hash>
- Human fixes: <what was corrected and why>
