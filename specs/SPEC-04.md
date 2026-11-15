# SPEC-04 — Booking request (conflict rule + lock)

**Status:** Draft · **Requirements:** FR-03, FR-04, FR-13, FR-14, FR-16, NFR-04 (design only) · **Architecture:** §2.1 flow, §2.4 (A-BK), §2.6 routes 6–7, §5.2, ADR-003

## Goal
A Member sees which time ranges are already booked for an item and date, then requests a booking. Overlapping requests are rejected; simultaneous requests for the same item and day are serialised by a lock row.

## Scope
1. **Routes** — add exactly these two, middleware `auth` + `role:member`:
   | # | Method | Path | Action |
   |---|---|---|---|
   | 6 | GET | `/bookings/create?item={id}&date={Y-m-d}` | request screen; shows booked ranges when item and date are given |
   | 7 | POST | `/bookings` | create the request |
2. **BookingService** (`app/Modules/Booking/BookingService.php`), constructor-injected `ItemCatalogService`:
   - `bookedRangesFor(int $itemId, string $date): array` — list of `['start' => 'HH:MM', 'end' => 'HH:MM']` for `pending` and `approved` bookings, ordered by start. **Must not load or return any user data** (FR-16).
   - `createRequest(int $userId, int $itemId, string $date, string $start, string $end): Booking` — in this exact order:
     1. `if (! $catalog->isActive($itemId)) throw new ItemInactiveException;` (FR-14)
     2. `DB::table('booking_locks')->insertOrIgnore(['item_id' => $itemId, 'date' => $date]);` — **before** the transaction (idempotent).
     3. `DB::transaction(...)`:
        a. `BookingLock::where('item_id', $itemId)->where('date', $date)->lockForUpdate()->first();`
        b. overlap query on `bookings`: same item, same date, status in (`pending`, `approved`), `start_time < :end AND end_time > :start` → if any exists, `throw new BookingConflictException;` (FR-04)
        c. insert the booking with status `pending`.
   - Times are stored as `H:i:s`.
   - Booking code must **not** query or lock `items` or `users`; it reaches Catalog only through `ItemCatalogService`.
3. **Exceptions** `app/Modules/Booking/Exceptions/BookingConflictException.php`, `ItemInactiveException.php`.
4. **Validation** `app/Http/Requests/BookingRequest.php`: `item_id` required integer; `date` required `date_format:Y-m-d`; `start_time`, `end_time` required `date_format:H:i`; `end_time` after `start_time`; `start_time` ≥ `06:00`; `end_time` ≤ `23:00` (FR-13).
5. **Controller** `app/Http/Controllers/BookingController.php`:
   - `create`: active items from `ItemCatalogService::listActive()` for the dropdown; if `item` and `date` are present, ranges from `bookedRangesFor()`.
   - `store`: call `createRequest()` with the logged-in user's id. On `BookingConflictException` → back with error "This time overlaps an existing booking." and old input. On `ItemInactiveException` → back with error "This item is deactivated and cannot be booked." On success → redirect `/bookings` with flash "Booking requested (Pending)." (`/bookings` is built in SPEC-05.)
6. **View** `resources/views/bookings/create.blade.php`: GET form (item select + date) → "Show availability"; table of booked ranges (start–end only, no names); POST form (hidden item and date, start and end time inputs). Show errors.
7. **Layout**: for Members add links "Request booking" (`/bookings/create`) and "My bookings" (`/bookings`).

## Acceptance criteria
- Existing 16:00–17:00, new 17:00–18:00 on same item/date → accepted (touching, FR-04).
- Existing 17:00–18:00, new 16:00–17:00 → accepted.
- Existing 16:00–17:00, new 16:59–18:00 → rejected (1-minute overlap); no booking created.
- Identical, containing and contained intervals → rejected.
- Same time on a different item, or a different date → accepted.
- Existing `rejected` or `cancelled` bookings do not block.
- Deactivated item → rejected with the FR-14 message; no booking created.
- 06:00–07:00 and 22:00–23:00 accepted; start 05:30, end 23:30, and end ≤ start rejected (FR-13).
- Successful request → one `pending` booking; exactly one `booking_locks` row for (item, date) even after two bookings that day.
- Request screen with item and date shows the booked ranges and does **not** contain other members' names or emails (FR-16); deactivated items are not in the dropdown.
- Guest → `/login`; Supervisor on routes 6–7 → 403.
- `php artisan route:list` shows routes 1–7 and 12–14 only.

## Tests
- `tests/Feature/Booking/BookingServiceTest.php` — service-level, one test per conflict/lock criterion above.
- `tests/Feature/Booking/RequestBookingTest.php` — HTTP: validation, messages, FR-16 screen, access control.
- The 100-simultaneous-request test for NFR-04 is **not** in this SPEC (SPEC-07).

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-04.md  (the task)
- REQUIREMENTS.md (FR-03, FR-04, FR-13, FR-14, FR-16) and M1-architecture-draft.md (§2.1, ADR-003) for detail

Implement SPEC-04 exactly as written. Create or edit only the files listed in its Scope and Tests sections.
Follow the createRequest order in the SPEC exactly: isActive check, insertOrIgnore lock row, then a transaction that locks the booking_locks row with lockForUpdate BEFORE running the overlap query.
The overlap condition is start_time < new_end AND end_time > new_start (strict), on pending and approved bookings only.
BookingService must not query or lock the items or users tables. Do not add routes beyond 6–7. Do not add tables or migrations.
Do not run migrations against the main database. Do not modify any other files.
When finished, run `php artisan test` and `php artisan route:list`, and list every file you created, edited or deleted.
```

## Result
- Raw commit:
- Fix commit:
- Human review / fixes:
