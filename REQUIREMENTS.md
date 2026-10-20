# Requirements — Sports Field & Equipment Booking System

**Status:** DRAFT v2 human reviewed
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
- **Given** I own a Pending or Approved booking whose start time has not passed, **When** I cancel it, **Then** its status becomes Cancelled and it no longer blocks the slot.
- **Failure (state):** **Given** my booking is already Cancelled or Rejected, **When** I attempt to cancel, **Then** the system shows a message and makes no change.
- **Failure (authorization):** **Given** the booking belongs to another member, **When** I attempt to cancel it, **Then** the system returns HTTP 403 and makes no change.
- **Boundary:** **Given** my booking starts at 19:00, **When** I cancel at 18:59, **Then** it is cancelled; **When** I cancel at 19:00, **Then** it is denied.

### US-05 — As a Supervisor, I want to approve or reject a pending booking, so that I control the schedule.
- **Given** there is a Pending booking, **When** I approve it, **Then** its status becomes Approved; **When** I reject it, **Then** its status becomes Rejected and the slot is freed.
- **Failure:** **Given** the booking was already decided, **When** I decide again, **Then** the system shows it was already handled and makes no change.

### US-06 — As a Supervisor, I want to see all bookings and availability at a glance, so that I can manage them efficiently.
- **Given** there are bookings, **When** I open the dashboard, **Then** I see all bookings with status and an availability view (booked vs free) for the selected day.
- **Failure:** **Given** there are no bookings, **When** I open the dashboard, **Then** I see an empty state.

### US-07 — As a Supervisor, I want to add, edit and deactivate fields/equipment, so that the catalog stays current.
- **Given** I am on the manage-items screen, **When** I add a new item, **Then** it appears in the booking catalog; **When** I deactivate an item, **Then** it can no longer be booked but its past bookings remain.
- **Failure:** **Given** an item has upcoming Pending/Approved bookings, **When** I try to hard-delete it, **Then** the system does not offer hard-delete; the item can only be deactivated (soft-delete), and its existing bookings remain.

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
- **Alternates/exception:** already decided → no change; item deactivated after the booking was requested → Approve is denied with the message "This item is deactivated and cannot be booked" and the booking stays Pending; Reject is still allowed (FR-14).
- **Postconditions:** booking status updated; availability reflects the decision.

### UC-04 — Cancel booking
- **Actors:** Member (own booking)
- **Preconditions:** authenticated; owns a Pending or Approved booking.
- **Main flow:** member opens My Bookings, selects Cancel on an own booking; system sets status Cancelled.
- **Alternates/exception:** not the owner → denied; cancelled or rejected → no change; booking whose start time has passed → denied.
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

### SC-02 — Khalid is blocked by a conflict until the slot is cancelled
Khalid tries to book Pitch A on Monday 19:00–21:00, but Saad (another member) already has a Pending booking for that slot. The system rejects Khalid's request and shows a conflict message. Khalid instead requests 21:00–23:00, which touches but does not overlap Saad's booking, and it is accepted. Later, Saad cancels his booking from My Bookings; its status becomes Cancelled and it no longer blocks the slot. Khalid requests 19:00–21:00 again and this time it is accepted as Pending.

### SC-03 — Faisal manages a busy evening
Faisal opens the dashboard for a peak evening, sees booked vs free slots, approves one request, rejects another, and deactivates a broken item. The catalog updates so the broken item can no longer be booked, while its past bookings remain for audit.

---

## e) Functional requirements (FR)

- **FR-01** The system shall register a new Member with a unique email and a password of at least 8 characters containing at least one letter and one number.
- **FR-02** The system shall require authentication for all booking and dashboard pages, and redirect unauthenticated users to login.
- **FR-03** The system shall allow a Member to submit a booking request with item, date, start time and end time.
- **FR-04** The system shall reject a booking request whose half-open interval [start, end) overlaps the interval of any existing Pending or Approved booking for the same item and date, where overlap means (new.start < existing.end) AND (new.end > existing.start); a request ending at 17:00 and an existing request starting at 17:00 shall not conflict.
- **FR-05** The system shall store each booking with status one of Pending, Approved, Rejected, Cancelled, and record created_at and decided_at timestamps.
- **FR-06** The system shall allow a Supervisor to approve a Pending booking, setting status to Approved.
- **FR-07** The system shall allow a Supervisor to reject a Pending booking, setting status to Rejected.
- **FR-08** The system shall allow a Member to cancel a booking they own that is Pending or Approved and whose start time has not passed, setting status to Cancelled.
- **FR-09** The system shall allow a Supervisor to add, edit, and deactivate (soft-delete) an item.
- **FR-10** The system shall display a Member's My Bookings page listing item, date, start, end and status.
- **FR-11** The system shall display a Supervisor dashboard listing all bookings with status and an availability view of booked vs free slots for the selected day.
- **FR-12** The system shall enforce exactly two roles, Member and Supervisor, and restrict supervisor-only actions (approve/reject, manage items) to the Supervisor role.
- **FR-13** The system shall reject a booking whose end time is not after its start time, or whose start or end falls outside operating hours 06:00–23:00 (06:00–07:00 and 22:00–23:00 are accepted; 05:30 is rejected).
- **FR-14** The system shall reject creating or approving a booking for a deactivated item.
- **FR-15** Public registration shall always create a Member account; Supervisor accounts shall be created only through database seeding.

---

## f) Non-functional requirements (NFR) — quality-attribute scenarios
[ Six parts per scenario: Source | Stimulus | Artifact | Environment | Response | Response Measure ]

- **NFR-01 (Performance):** A Member submits a booking request → booking route + conflict-check → production-like, up to 50 concurrent members, synthetic data → system validates and returns confirmation or a conflict error → **95th-percentile response ≤ 2 s under 50 concurrent booking requests.**
- **NFR-02 (Security / access control):** A Member attempts to open the Supervisor dashboard or call approve/reject endpoints → supervisor routes & controllers → web app, authenticated as Member → system denies access → **100% of such requests return HTTP 403 within 1 s; 0 supervisor state changes occur (verified by automated test).**
- **NFR-03 (Usability):** A first-time Member attempts to find availability and submit a booking unaided → Request Booking screen → desktop browser, synthetic data → system shows available slots and confirms the booking → **at least 4 of 5 participants succeed, median time ≤ 3 minutes, over a 5-participant moderated usability test.**
- **NFR-04 (Reliability / data integrity):** Two Members submit overlapping booking requests at the same instant → bookings table + conflict-check transaction → concurrent load → database serializes checks so only one is committed → **in a test of 100 simultaneous conflicting requests, exactly 1 booking is committed and 99 receive a conflict error; 0 double-bookings occur.**
- **NFR-05 (Maintainability / testability):** A developer changes the booking-conflict rule and runs the suite → booking service and its tests → CI (PHPUnit/Pest + mutation tool) → tests catch regressions in the rule → **line coverage of the booking module ≥ 80%, mutation score ≥ 70%, and the suite contains tests for touching intervals (17:00), a 1-minute overlap, and the conflict failure path**
