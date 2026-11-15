# SPEC-02 — Identity: register, login, logout, roles

**Status:** Draft · **Requirements:** FR-01, FR-02, FR-12, FR-15, NFR-02 · **Architecture:** §2.4 (A-ID, A-WEB, A-POL), §2.6 routes 1–5

## Goal
Members can register; Members and the Supervisor can log in and out; supervisor-only routes are protected by role.

## Scope
1. **Routes** (`routes/web.php`) — exactly these five, nothing else:
   | # | Method | Path | Middleware |
   |---|---|---|---|
   | 1 | GET | `/login` | guest |
   | 2 | POST | `/login` | guest |
   | 3 | GET | `/register` | guest |
   | 4 | POST | `/register` | guest |
   | 5 | POST | `/logout` | auth |
   Remove the default `GET /` welcome route and `resources/views/welcome.blade.php`. Remove the `health: '/up'` line in `bootstrap/app.php`.
2. **IdentityService** (`app/Modules/Identity/IdentityService.php`):
   - `register(string $name, string $email, string $password): User` — always creates role `member` (FR-15).
   - `namesFor(array $userIds): array` — returns `[id => name]`; used later by the dashboard.
3. **Controllers** (`app/Http/Controllers/Auth/`): `LoginController`, `RegisterController`, `LogoutController`. Controllers call `IdentityService`; they do not query `users` directly except through Laravel's `Auth`.
4. **Validation** (`app/Http/Requests/RegisterRequest.php`): name required; email required, valid, unique; password required, confirmed, `Password::min(8)->letters()->numbers()` (FR-01). Any `role` field in the request is ignored.
5. **Redirects:** after login/register — Supervisor → `/dashboard`, Member → `/bookings`. Guests hitting an `auth` route → `/login` (FR-02). (These pages are built in later SPECs; a 404 there is expected for now.)
6. **Authorization (A-POL):**
   - Middleware `EnsureRole` (`app/Http/Middleware/EnsureRole.php`), alias `role`, used as `role:supervisor`; wrong role → HTTP 403 (NFR-02).
   - Gate `supervisor` in `AppServiceProvider`: `fn (User $u) => $u->isSupervisor()`.
7. **Views** (Blade, no Vite, no CSS framework; small inline CSS is fine): `layouts/app.blade.php`, `auth/login.blade.php`, `auth/register.blade.php`. Show validation errors. Include `@csrf`.

## Acceptance criteria
- `php artisan route:list` shows exactly routes 1–5 (ignore Laravel's internal `storage/{path}` route).
- Register with a valid form → user created with role `member`, logged in, redirected to `/bookings`.
- Register with `role=supervisor` in the request → user is still `member`.
- Password `Abc1234` (7 chars) rejected; `Abc12345` (8 chars) accepted; `abcdefgh` (no number) and `12345678` (no letter) rejected.
- Duplicate email → validation error, no new user.
- Login with correct credentials → Member to `/bookings`, Supervisor to `/dashboard`; wrong password → error, still guest.
- Logout → guest.
- Guest requesting an `auth` route → redirect to `/login`.
- Member requesting a `role:supervisor` route → 403.

## Tests (`tests/Feature/Identity/AuthTest.php`, `tests/Feature/Identity/IdentityServiceTest.php`)
One test per acceptance criterion above (except route:list). For the last two, register a temporary route inside the test, e.g. `Route::middleware(['web','auth','role:supervisor'])->get('/_probe', fn () => 'ok');`. Plus `namesFor` returns names keyed by id.

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-02.md  (the task)
- REQUIREMENTS.md and M1-architecture-draft.md only if you need more detail

Implement SPEC-02 exactly as written. Create or edit only the files listed in its Scope and Tests sections.
Do not add routes beyond routes 1–5. Do not add tables or migrations. Do not install packages (no Breeze, Jetstream or Fortify).
Do not run migrations against the main database. Do not modify any other files.
When finished, run `php artisan test` and `php artisan route:list`, and list every file you created, edited or deleted.
```

## Result
- Raw commit: <7172d39>
- Follow-up: DEVAI asked how to handle tests/Feature/ExampleTest.php (asserted GET / = 200, route removed by SPEC-02). Answer: delete it.
- Human review: code reviewed against all acceptance criteria; no corrections needed. 21 tests pass on booking_test.
- Note: GET / is intentionally not defined (14-route limit); users open /login directly.
