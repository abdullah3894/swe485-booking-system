# Requirements — Sports Field & Equipment Booking System

**Status:** DRAFT v1 — for team review
Every requirement is testable. Assumptions are marked [ASSUMPTION].
Conflict rule (FR-04): [start, end) half-open intervals; end of one booking may equal the start of another without conflict.

---

## a) Simulated personas

### Persona 1 — Member: "Khalid Al-Otaibi" (fictional)
- **Role:** Member; regular 5-a-side football player.
- **Goals:** quickly book a field for his weekly match; see whether his slot is confirmed; avoid clashes.
- **Frustrations:** WhatsApp double-bookings; no confirmation record; can't see what times are free; slow manual follow-up.
- **Typical week:** works 08:00–17:00; books Monday and Thursday 19:00–21:00 evening slots; checks availability midweek.

### Persona 2 — Supervisor: "Faisal Al-Dosari" (fictional)
- **Role:** Supervisor at the community sports center.
- **Goals:** approve/reject requests fast; see the schedule at a glance; keep the item catalog accurate.
- **Frustrations:** flooded by WhatsApp requests; no single availability view; mistakes cause double-bookings; manual follow-up.
- **Typical week:** reviews incoming requests each morning and afternoon; manages peak evening bookings; updates equipment availability.

---

## b) User stories (each with Given–When–Then acceptance criteria)

### US-01 — As a Member, I want to register and log in, so that I can create and track my bookings.
- **Given** I am a new user, **When** I register with a valid email and a password of ≥8 chars (one letter, one number), **Then** an account is created and I am logged in.
- **Failure:** **Given** the email is already in use, **When** I submit, **Then** the system shows a validation error and creates no account.
- **Boundary:** **Given** I enter a 7-char password, **When** I submit, **Then** registration is rejected; an 8-char password succeeds.

### US-02 — As a Member, I want to request a booking for a field/item on a date/time, so that I can secure a slot.
- **Given** I am logged in and have selected an item, date, start time and end time, **When** I submit, **Then** a Pending booking is created and shown on My Bookings.
- **Failure:** **Given** the slot overlaps an existing Pending/Approved booking for the same item and date, **When** I submit, **Then** the system rejects it with a conflict message and creates no booking.
- **Boundary:** **Given** an existing booking ends at 17:00, **When** I request 17:00–18:00 on the same item/date, **Then** the request is accepted (no conflict).

### US-03 — As a Member, I want to view my bookings and their status, so that I know if my request is confirmed.
- **Given** I have bookings, **When** I open My Bookings, **Then** I see each with item, date, times and status.
- **Failure:** **Given** I have no bookings, **When** I open My Bookings, **Then** I see an empty state and a prompt to make a booking (not an error).

### US-04 — As a Member, I want to cancel a booking I made, so that I free the slot for others.
- **Given** I own a Pending or Approved booking, **When** I cancel it, **Then** its status becomes Cancelled and it no longer blocks the slot.
- **Failure:** **Given** the booking is already Cancelled, **When** I attempt to cancel, **Then** the system shows a message and makes no change.
- **Boundary:** **Given** I attempt to cancel another member's booking, **When** I submit, **Then** the system denies it (HTTP 403 / no state change).

### US-05 — As a Supervisor, I want to approve or reject a pending booking, so that I control the schedule.
- **Given** there is a Pending booking, **When** I approve it, **Then** its status becomes Approved; **When** I reject it, **Then** its status becomes Rejected and the slot is freed.
- **Failure:** **Given** the booking was already decided, **When** I decide again, **Then** the system shows it was already handled and makes no change.

### US-06 — As a Supervisor, I want to see all bookings and availability at a glance, so that I can manage them efficiently.
- **Given** there are bookings, **When** I open the dashboard, **Then** I see all bookings with status and an availability view (booked vs free) for the selected day.
- **Failure:** **Given** there are no bookings, **When** I open the dashboard, **Then** I see an empty state.

### US-07 — As a Supervisor, I want to add, edit and deactivate fields/equipment, so that the catalog stays current.
- **Given** I am on the manage-items screen, **When** I add a new item, **Then** it appears in the booking catalog; **When** I deactivate an item, **Then** it can no longer be booked but its past bookings remain.
- **Failure:** **Given** an item has upcoming Pending/Approved bookings, **When** I try to hard-delete it, **Then** the system blocks the action or requires deactivation (soft-delete).

---

## c) Use cases

### UC-01 — Register and log in
- **Actors:** Member
- **Preconditions:** none.
- **Main flow:** user opens Register, enters name/email/password, submits; system validates, creates the account, and logs the user in.
- **Alternates/exception:** email already exists → error; invalid email/wrong password → validation or login error.
- **Postconditions:** an authenticated session exists.

### UC-02 — Request booking
- **Actors:** Member
- **Preconditions:** authenticated; at least one active field/item exists.
- **Main flow:** member selects item, date, start and end time; system validates; checks the conflict rule (FR-04); creates a Pending booking; shows confirmation.
- **Alternates/exception:** conflict → reject with message; end ≤ start, or outside operating hours → validation error.
- **Postconditions:** a Pending booking is persisted and visible on My Bookings.

### UC-03 — Approve or reject booking
- **Actors:** Supervisor
- **Preconditions:** authenticated as Supervisor; at least one Pending booking.
- **Main flow:** supervisor opens the dashboard, selects a Pending booking, chooses Approve or Reject; system updates the status and sets a decided timestamp.
- **Alternates/exception:** already decided → no change; item deactivated → system warns/prevents approval.
- **Postconditions:** booking status updated; availability reflects the decision.

### UC-04 — Cancel booking
- **Actors:** Member (own booking)
- **Preconditions:** authenticated; owns a Pending or Approved booking.
- **Main flow:** member opens My Bookings, selects Cancel on an own booking; system sets status Cancelled.
- **Alternates/exception:** not the owner → denied; already cancelled → no change; booking in the past → allowed with a warning [ASSUMPTION].
- **Postconditions:** status Cancelled; slot freed.

### UC-05 — Manage items
- **Actors:** Supervisor
- **Preconditions:** authenticated as Supervisor.
- **Main flow:** supervisor opens manage-items; adds or edits an item, or deactivates it (soft-delete).
- **Alternates/exception:** duplicate item name → error; deactivating an item with upcoming bookings → allowed as soft-delete (no hard delete).
- **Postconditions:** catalog updated; deactivated items are not bookable.

---

## d) Scenarios (narrative)

### SC-01 — Khalid books an evening slot
Khalid registers, logs in, opens Request Booking, picks "Pitch A" for Thursday 19:00–21:00, and submits. The system finds no conflict, creates a Pending booking, and shows it on My Bookings. Faisal logs in, opens the dashboard, sees the Pending request in the availability view, approves it, and Khalid sees status change to Approved.

### SC-02 — Khalid is blocked by a conflict, then cancels
Khalid tries to book Pitch A on Monday 19:00–21:00, but it is already Pending for that slot. The system rejects the request and shows a conflict message. Khalid then opens My Bookings)Skip and cancels another of his Pending bookings; its slot is freed and he can now request that time.

### SC-03 — Faisal manages a busy evening
Faisal opens the dashboard for a peak evening, sees booked vs free slots, approves one request, rejects another that would exceed capacity, and deactivates a broken item. The catalog updates so the broken item can no longer be booked, while its past bookings remain for audit.

---

## e) Functional requirements (FR)

- **FR-01** The system shall register a new Member with a unique email and a password of at least 8 characters containing at least one letter and one number.
- **FR-02** The system shall require authentication for all booking and dashboard pages, and redirect unauthenticated users to login.
- **FR-03** The system shall allow a Member to submit a booking request with item, date, start time and end time.
- **FR-04** The system shall reject a booking request whose half-open interval [start, end) overlaps the interval of any existing Pending or Approved booking for the same item and date, where overlap means (new.start < existing.end) AND (new.end > existing.start); a request ending at 17:00 and an existing request starting at 17:00 shall not conflict.
- **FR-05** The system shall store each booking with status one of Pending, Approved, Rejected, Cancelled, and record created_at and decided_at timestamps.
- **FR-06** The system shall allow a Supervisor to approve a Pending booking, setting status to Approved.
- **FR-07** The system shall allow a Supervisor to reject a Pending booking, setting status to Rejected.
- **FR-08** The system shall allow a Member to cancel a booking they own that is Pending or Approved, setting status to Cancelled.
- **FR-09** The system shall allow a Supervisor to add, edit“,” and deactivate (soft-delete) an item.
- **FR-10** The system shall display a Member's My Bookings page listing item, date, start, end and status.
- **FR-11** The system shall display a Supervisor dashboard listing all bookings with status and an availability view of booked vs free slots for the selected day.
- **FR-12** The system shall enforce exactly two roles, Member and Supervisor, and restrict supervisor-only actions (approve/reject, manage items) to the Supervisor role.

---

## f) Non-functional requirements (NFR) — quality-attribute scenarios
[ Six parts per scenario: Source | Stimulus | Artifact | Environment | Response | Response Measure ]

- **NFR-01 (Performance):** A Member submits a booking request → booking route + conflict-check → production-like, up to 50 concurrent members, synthetic data → system validates and returns confirmation or a conflict error → **95th-percentile response ≤ 2 s under 50 concurrent booking requests.**
- **NFR-02 (Security / access control):** A Member attempts to open the Supervisor dashboard or call approve/reject endpoints → supervisor routes & controllers → web app, authenticated as Member → system denies access → **100% of such requests return HTTP 403 within 1 s; 0 supervisor state changes occur (verified by automated test).**
- **NFR-03 (Usability):** A first-time Member attempts to find availability and submit a booking unaided → Request Booking screen → desktop browser, synthetic data → system shows available slots and confirms the booking → **≥ 90% task success, median time ≤ 3 minutes, over a 5-participant moderated usability test.**
- **NFR-04 (Reliability / data integrity):** Two Members submit overlapping booking requests at the same instant → bookings table + conflict-check transaction → concurrent load, MySQL with transaction isolation → database serializes checks so only one is committed → **in a test of 100 simultaneous conflicting requests, exactly 1 booking is committed per conflicting pair and 0 double-bookings occur.**
- **NFR-05 (Maintainability / testability):** A developer changes the booking-conflict rule and runs the suite → booking service and its tests → CI (PHPUnit/Pest + mutation tool) → tests catch regressions in the rule → **line coverage of the booking module ≥ 80%, mutation score ≥ 70%, and 100% coverage of the 17:00 boundary and the failure path.**