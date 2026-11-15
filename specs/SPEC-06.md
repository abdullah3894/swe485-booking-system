# SPEC-06 — Supervisor dashboard: approve / reject

**Status:** Draft · **Requirements:** FR-05, FR-06, FR-07, FR-11, FR-14, NFR-02 · **Architecture:** §2.4 (`supervisorSchedule`, three queries, no join), §2.6 routes 10–11, §5.3 · **Use case:** UC-03 · **Scenarios:** SC-01, SC-03

## Goal
The Supervisor sees pending requests and the selected day's schedule with booked vs free time per item, and approves or rejects pending bookings.

## Scope
1. **Routes** — add exactly these two, middleware `auth` + `role:supervisor`:
   | # | Method | Path | Action |
   |---|---|---|---|
   | 10 | GET | `/dashboard?date={Y-m-d}` | dashboard; `date` defaults to today |
   | 11 | POST | `/dashboard/bookings/{booking}/decision` | `decision` = `approve` \| `reject` |
2. **BookingService** — add:
   - `supervisorSchedule(string $date): array` returning:
     - `pending` — all `pending` bookings (any date), oldest `created_at` first;
     - `day` — all bookings on `$date` (any status), ordered by item then start;
     - `availability` — for every **active** item: `booked` ranges (pending + approved) and `free` ranges inside 06:00–23:00 on `$date`.
     Each booking row has `id, item_name, member_name, date, start, end, status, decided_at`.
     Built with **separate queries, no joins**: read `bookings`, then `ItemCatalogService::getByIds()` / `listActive()` and `IdentityService::namesFor()`.
   - `approve(int $bookingId): void`
     1. load the booking; if `! $catalog->isActive($item_id)` → throw `ItemInactiveException` (booking stays pending; FR-14, UC-03);
     2. single conditional update: `UPDATE bookings SET status='approved', decided_at=now() WHERE id=? AND status='pending'`; if 0 rows affected → throw `BookingAlreadyDecidedException`.
   - `reject(int $bookingId): void` — same conditional update to `rejected` (allowed even for a deactivated item); 0 rows → `BookingAlreadyDecidedException`.
   - The `WHERE status='pending'` condition is what makes a member's simultaneous cancel and a supervisor decision safe (only one can win).
3. **Exception** `app/Modules/Booking/Exceptions/BookingAlreadyDecidedException.php`.
4. **Controller** `app/Http/Controllers/DashboardController.php` (`index`, `decide`), validation `decision` in `approve,reject`, optional `date` `date_format:Y-m-d`. Messages (flash, back to the dashboard keeping the date):
   - approve → "Booking approved."; reject → "Booking rejected."
   - `BookingAlreadyDecidedException` → "This booking was already handled."
   - `ItemInactiveException` on approve → "This item is deactivated and cannot be booked" (exact UC-03 text).
5. **View** `resources/views/dashboard/index.blade.php`: date picker (GET); **Pending requests** table with Approve and Reject buttons; **Schedule for {date}** table; **Availability** per item listing booked and free ranges. Empty states for each section (US-06).

## Acceptance criteria
- Dashboard shows pending requests with item and member names, the selected day's bookings with status, and availability.
- Item with one approved booking 16:00–17:00 → booked `16:00–17:00`, free `06:00–16:00` and `17:00–23:00`; item with none → free `06:00–23:00`; rejected/cancelled bookings count as free.
- Approve pending → `approved`, `decided_at` set (FR-05, FR-06).
- Reject pending → `rejected`, `decided_at` set; the slot can then be requested again (FR-07).
- Approve/reject an already approved, rejected or cancelled booking → "already handled", no change.
- Approve a pending booking on a deactivated item → exact UC-03 message, stays `pending`; reject on it → `rejected`.
- Member on routes 10–11 → 403, no change; guest → `/login` (NFR-02).
- No bookings → empty states, not errors.
- `php artisan route:list` shows `/`, routes 1–14 and nothing else (plus `storage/{path}`).

## Tests
- `tests/Feature/Dashboard/DashboardTest.php` — HTTP: display, decisions, messages, 403s.
- Service tests for `supervisorSchedule` (including free-range calculation), `approve`, `reject` in `tests/Feature/Booking/BookingServiceTest.php`.

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-06.md  (the task)
- REQUIREMENTS.md (FR-05, FR-06, FR-07, FR-11, FR-14, UC-03) and M1-architecture-draft.md (§2.4, §5.3) for detail

Implement SPEC-06 exactly as written. Create or edit only the files listed in its Scope and Tests sections.
supervisorSchedule must use separate queries and no joins: bookings first, then ItemCatalogService and IdentityService for names.
approve and reject must use a single conditional update with WHERE status = 'pending' and check the affected row count.
Do not add routes beyond 10–11. Do not add tables or migrations.
Do not run migrations against the main database. Do not modify any other files.
When finished, run `php artisan test` and `php artisan route:list`, and list every file you created, edited or deleted.
```

## Result
- Raw commit:f8167d7
reviewd

