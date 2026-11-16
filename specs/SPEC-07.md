# SPEC-07 — UI polish (views and CSS only)

**Status:** Draft · **Requirements:** NFR-03 (usability), FR-10, FR-11, FR-16 (presentation only) · **Architecture:** §2.4 A-WEB

## Goal
Make the six screens clean, consistent and easy to use on desktop and phone, without changing any behaviour.

## Non-goals
No change to controllers, services, models, requests, routes, migrations or tests. No new screens. No JavaScript framework, no Node/npm/Vite, no CDN or web fonts (the app must work offline after `composer install`).

## Scope
1. **One stylesheet** `public/css/app.css`, linked from `resources/views/layouts/app.blade.php` with `<link rel="stylesheet" href="/css/app.css">`. Remove the inline `<style>` block from the layout.
   - CSS variables at `:root` for colours, radius, spacing. Palette: primary green `#1f7a4d`, dark text `#1c1c1a`, muted `#6b6b66`, page background `#f4f6f3`, surface `#ffffff`, border `#dfe3dc`, danger `#b42318`, warning `#b54708`.
   - System font stack; base size 16px; visible `:focus-visible` outline on links, buttons and inputs.
2. **Layout** (`layouts/app.blade.php`):
   - Top bar: app name "Sports Booking" on the left; on the right, role links (Member: Request booking, My bookings · Supervisor: Dashboard, Items), the user's name and the Log out button. Mark the current page's link (`request()->is(...)`).
   - Content area: `max-width` 960px for wide pages, 420px card for login/register; 16px side padding on phones.
   - Shared flash area: `session('status')` as a green success box, `session('error')` and `$errors` as a red box — rendered once in the layout; remove duplicates from the individual views.
3. **Components** (classes in `app.css`, used by the views): `.card`, `.btn`, `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-sm`, form rows with labels above inputs, `.table` (zebra rows, horizontally scrollable wrapper on small screens), `.badge` with `.badge-pending` (amber), `.badge-approved` (green), `.badge-rejected` (red), `.badge-cancelled` (grey), `.badge-active`, `.badge-inactive`, `.empty` (empty-state box).
4. **Views** — restyle only:
   - `auth/login`, `auth/register`: centred card; primary submit button; the link to the other page as a secondary button below.
   - `bookings/create`: two cards — "1. Choose item and date" and "2. Choose a time". Show the booked ranges as a **timeline bar** 06:00–23:00 (booked segments positioned with inline `left`/`width` percentages computed in Blade from HH:MM; hour ticks every 3 h), and keep the existing table of ranges below it.
   - `bookings/index`: status as badges; Cancel as `.btn-danger .btn-sm`.
   - `dashboard/index`: date picker in a card; pending requests table with Approve (`.btn-primary`) and Reject (`.btn-secondary`); day schedule with badges; availability as one timeline bar per item (same component as the request page — put it in `resources/views/partials/timeline.blade.php`).
   - `items/index`: status badges; add-item form in a card; edit fields and Deactivate button aligned in the row.
5. **Text rule:** every existing user-visible text (headings, labels, button texts, messages, empty states) must stay exactly as it is — tests assert them. You may add new text (e.g. section titles, the timeline hour labels).

## Acceptance criteria
- `php artisan test` passes with **no test file changed**, before and after (same count).
- `php artisan route:list` unchanged.
- No inline `<style>` blocks or `style=""` attributes except the timeline segment positions.
- Each of the six screens renders with the top bar, consistent buttons and cards; status values show as coloured badges.
- At 375px width no page scrolls horizontally except inside table wrappers.
- Request page: with a booking 16:00–17:00, the timeline shows one booked segment starting at about 59 % of the bar (10 of 17 hours) and ending at about 65 %.

## Prompt given to DEVAI
```
You are working on a Laravel 12 project in the current workspace.

First read these files from the project:
- specs/CONTEXT.md  (project rules — follow them strictly)
- specs/SPEC-07.md  (the task)
- every Blade view in resources/views

Implement SPEC-07 exactly as written. This is a visual change only.
Create or edit only: public/css/app.css, resources/views/layouts/app.blade.php, resources/views/partials/timeline.blade.php and the existing Blade views.
Do not change any PHP outside resources/views. Do not change any test. Keep every existing user-visible text exactly as it is.
No JavaScript frameworks, no npm/Vite, no CDN or external fonts.
Before you start, run `php artisan test` and record the result. When finished, run `php artisan test` again and `php artisan route:list`, report both test results, and list every file you created, edited or deleted.
```

## Result
- Raw commit:
- Tests before → after:
- Human review / fixes:
