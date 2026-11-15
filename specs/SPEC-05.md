# SPEC-05 — My Bookings and cancel

**Status:** Draft · **Requirements:** FR-05, FR-08, FR-10, NFR-02 · **Architecture:** §2.4 (A-BK, A-POL, A-WEB), §2.6 routes 8–9 · **Scenario:** SC-02

## Goal
A Member sees their own bookings with status and can cancel a Pending or Approved booking strictly before it starts. A cancelled booking no longer blocks the slot.

## Scope
1. **Timezone:** in `config/app.php` set `'timezone' => 'Asia/Riyadh'` (the cancel deadline is compared with local time).
2. **Routes** — add exactly these two, middleware `auth` + `role:member`:
   | # | Method | Path | Action |
   |---|---|---|---|
   | 8 | GET | `/bookings` | My Bookings |
   | 9 | POST | `/bookings/{booking}/cancel` | cancel (owner only) |
3. **BookingService** — add:
   - `listForMember(int $userId): array` — the member's bookings, newest date first, each with `id, item_name, date, start (HH:MM), end (HH:MM), status, can_cancel`. Item names come from `ItemCatalogService::getByIds()` (works for deactivated items too) — **no join with `items`**.
   - `cancel(int $bookingId): void` — throws `BookingNotCancellableException` when status is not `pending`/`approved`, or when `now() >= date + start_time` (FR-08: 18:59 allowed, 19:00 denied). Otherwise sets status `cancelled`. Does not touch `decided_at`.
4. **Exception** `app/Modules/Booking/Exceptions/BookingNotCancellableException.php`.
5. **Authorization (A-POL):** `app/Policies/BookingPolicy.php` with `cancel(User $user, Booking $booking): bool` → `$user->id === $booking->user_id`. Register it in `AppServiceProvider` with `Gate::policy(Booking::class, BookingPolicy::class)`. Another member's booking → **HTTP 403**, no change (NFR-02).
6. **Controller** `BookingController`: `index` (route 8) and `cancel` (route 9, `$this->authorize('cancel', $booking)` before calling the service). On `BookingNotCancellableException` → back with error "This booking can no longer be cancelled."; on success → back to `/bookings` with flash "Booking cancelled."
7. **View** `resources/views/bookings/index.blade.php`: flash message (also shows "Booking requested (Pending)." from SPEC-04); table item, date, start, end, status; a Cancel button only when `can_cancel`; empty state "You have no bookings yet." with a link to `/bookings/create` (US-03).

## Acceptance criteria
- My Bookings lists only the logged-in member's bookings with item name, date, start, end, status (FR-10); a deactivated item's name still shows.
- No bookings → empty state with a link to request one (not an error).
- Owner cancels a Pending booking → `cancelled`; same for Approved.
- Booking at 19:00: cancel at 18:59 → cancelled; at 19:00 → denied, status unchanged (use `Carbon::setTestNow`).
- Already Cancelled or Rejected → message, no change.
- Another member's booking → 403, no change.
- After a cancel, a new request for the same item/date/time is accepted (SC-02).
- Supervisor on routes 8–9 → 403; guest → `/login`.
- `php artisan route:list` shows only the app's planned routes (1–9, 12–14, and `/` if present).

## Tests
- `tests/Feature/Booking/MyBookingsTest.php` — HTTP: list, empty state, cancel, 403s, messages.
- Add service tests for `listForMember` and `cancel` (including the 18:59 / 19:00 boundary) to `tests/Feature/Booking/BookingServiceTest.php`.

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-05.md  (the task)
- REQUIREMENTS.md (FR-05, FR-08, FR-10, NFR-02, SC-02) for detail

Implement SPEC-05 exactly as written. Create or edit only the files listed in its Scope and Tests sections.
BookingService must not query or join the items or users tables; item names come from ItemCatalogService::getByIds().
The owner check must be a policy and return 403. The cancel deadline is strict: now() must be before date + start_time.
Do not add routes beyond 8–9. Do not add tables or migrations.
Do not run migrations against the main database. Do not modify any other files.
When finished, run `php artisan test` and `php artisan route:list`, and list every file you created, edited or deleted.
```

## Result
- Raw commit:
- Fix commit:
- Human review / fixes:
