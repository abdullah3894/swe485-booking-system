# SPEC-03 — Catalog: manage items

**Status:** Draft · **Requirements:** FR-09, FR-12, FR-14, NFR-02 · **Architecture:** §2.4 (A-CAT, A-WEB, A-POL), §2.6 routes 12–14

## Goal
The Supervisor can add, edit and deactivate fields and equipment. Deactivated items stay in the database (past bookings keep their item) but are not bookable.

## Scope
1. **Routes** (`routes/web.php`) — add exactly these three, all with middleware `auth` + `role:supervisor`:
   | # | Method | Path | Action |
   |---|---|---|---|
   | 12 | GET | `/items` | list all items + add form + edit/deactivate controls |
   | 13 | POST | `/items` | create item |
   | 14 | POST | `/items/{item}` | edit **or** deactivate, chosen by a hidden field `action` = `update` \| `deactivate` |
2. **ItemCatalogService** (`app/Modules/Catalog/ItemCatalogService.php`) — the only code that touches the `items` table:
   - `listAll(): Collection` (for the manage screen, ordered by name)
   - `listActive(): Collection`
   - `getByIds(array $ids): array` — `[id => Item]`
   - `isActive(int $id): bool` — false for deactivated **or** unknown ids
   - `create(string $name, string $type): Item` — always `is_active = true`
   - `update(int $id, string $name, string $type): Item`
   - `deactivate(int $id): void` — sets `is_active = false`; never deletes
3. **Controller** `app/Http/Controllers/ItemController.php` (`index`, `store`, `update`) — calls the service only.
4. **Validation** `app/Http/Requests/ItemRequest.php`: `name` required, max 100, unique in `items` (ignore the current item on update); `type` in `field`, `equipment`. For `action=deactivate` no name/type is required.
5. **View** `resources/views/items/index.blade.php`: table of items (name, type, Active/Deactivated), add form, per-row edit form and Deactivate button (hidden for already deactivated items). No hard-delete control anywhere.
6. **Layout** `resources/views/layouts/app.blade.php`: when logged in, show the user's name and a Log out button (`POST /logout`); for the Supervisor also links to `/dashboard` and `/items`.

## Acceptance criteria
- Supervisor opens `/items` → sees all items, active and deactivated.
- Supervisor adds "Pitch C" (field) → it appears and is active.
- Adding a duplicate name → validation error, no new row.
- Supervisor edits an item's name/type → saved; renaming to another item's name → error.
- Supervisor deactivates an item that has a booking → `is_active = false`, the item row and the booking row still exist.
- `listActive()` excludes deactivated items; `isActive()` is false for deactivated and unknown ids.
- Member on routes 12, 13 and 14 → HTTP 403, no change (NFR-02).
- Guest on `/items` → redirect to `/login`.
- `php artisan route:list` shows routes 1–5 and 12–14 only (ignore `storage/{path}`).

## Tests
`tests/Feature/Catalog/ManageItemsTest.php` (HTTP, one test per acceptance criterion) and `tests/Feature/Catalog/ItemCatalogServiceTest.php` (service methods).

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-03.md  (the task)
- REQUIREMENTS.md and M1-architecture-draft.md only if you need more detail

Implement SPEC-03 exactly as written. Create or edit only the files listed in its Scope and Tests sections.
Only ItemCatalogService may query the items table. Do not add routes beyond routes 12–14. Do not add tables or migrations. Do not add a delete route or hard delete.
Do not run migrations against the main database. Do not modify any other files.
When finished, run `php artisan test` and `php artisan route:list`, and list every file you created, edited or deleted.
```

## Result
- Raw commit: <085a835>
- Human review: code reviewed against all acceptance criteria; no corrections needed. 39 tests pass on booking_test.
- Decision: DEVAI flagged that ItemRequest's Rule::unique('items','name') queries the items table from the web layer. Accepted — framework validation in A-WEB, not another module (Booking) accessing Catalog's table; the ownership rule in §5 is not broken.