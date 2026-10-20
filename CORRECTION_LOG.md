# Human Correction Log — AI Requirements Draft

**Project:** Sports Field & Equipment Booking System (SWE 485, M1)
**AI source:** DEVAI — [agent/model name] — draft generated 2026-10-20
**Reviewed and corrected by:** Abdullah Alhirbish
**Raw AI commit:** `[paste commit hash of raw(ai) commit]`
**Human fix commit:** `[paste commit hash of fix(human) commit]`

Each row records what the AI draft said, what was changed, and why.
Categories: **Untestable** (vague or unmeasurable), **Missing** (requirement absent), **Inconsistent** (contradicts another artifact), **Logic** (wrong reasoning), **Scope** (outside agreed scope), **Leak** (design decision inside a requirement), **Format** (corrupted text).

## REQUIREMENTS.md

| # | Location | AI draft said | Correction | Category | Reason |
|---|---|---|---|---|---|
| R-01 | UC-02 ↔ FR list | UC-02 mentions "end ≤ start" and "outside operating hours" but no FR covers them | Added **FR-13** (end after start; operating hours 06:00–23:00 with boundary values) | Missing | A rule in a use case with no FR cannot be traced to a test |
| R-02 | FR list | No rule for bookings on deactivated items | Added **FR-14** (reject creating or approving a booking for a deactivated item) | Missing | US-07/UC-05 imply it but no FR stated it |
| R-03 | FR list | Registration does not say how Supervisor accounts are created | Added **FR-15** (public registration creates Member only; Supervisors via seeding) | Missing | Otherwise anyone could self-register as Supervisor — security gap |
| R-04 | UC-04 | "booking in the past → allowed with a warning [ASSUMPTION]" | Cancelling a booking whose start time has passed → denied | Logic | Cancelling a past booking is meaningless and corrupts the booking record |
| R-05 | FR-08 | Cancel allowed for any Pending/Approved booking | Added "and whose start time has not passed" | Inconsistent | Must match corrected UC-04 |
| R-06 | US-04 | Cancelling another member's booking labelled **Boundary** | Re-labelled as **Failure (authorization)**; added a real time boundary (cancel at 18:59 allowed, at 19:00 denied) | Logic | Authorization failure is not a boundary value; the true boundary is the start time |
| R-07 | US-04, UC-04 | Only "already Cancelled" handled | Added "or Rejected" | Missing | Cancelling a rejected request is also invalid |
| R-08 | US-07 | "blocks the action **or** requires deactivation" | No hard-delete offered; item can only be deactivated; existing bookings remain | Untestable | "or" allows two behaviours — a test cannot verify either |
| R-09 | UC-03 | "item deactivated → system warns/prevents approval" | Item deactivated *after the booking was requested* → Approve denied with message, booking stays Pending; Reject still allowed (FR-14) | Untestable | "warns/prevents" is ambiguous; also clarified how a Pending booking on a deactivated item can exist (timing) |
| R-10 | SC-02 | Khalid is blocked, then cancels *another of his own* bookings and "can now request that time" | Rewritten: Saad (another member) holds the slot; Khalid books the touching slot 21:00–23:00; Saad cancels; Khalid's retry succeeds | Logic | Cancelling an unrelated booking does not free the conflicting slot — plausible-but-wrong narrative |
| R-11 | SC-03 | "rejects another that would exceed **capacity**" | Removed "capacity" | Scope | Capacity is not defined in any requirement — orphan concept |
| R-12 | NFR-03 | "≥ 90% task success over 5 participants" | "at least 4 of 5 participants succeed" | Untestable | With 5 participants only 80% or 100% are possible; 90% silently means 5/5 |
| R-13 | NFR-04 | "concurrent load, **MySQL with transaction isolation**" | Removed technology | Leak | Database choice is an architecture decision (ADR), not a requirement |
| R-14 | NFR-04 | "100 simultaneous conflicting requests → exactly 1 committed **per conflicting pair**" | "exactly 1 committed and 99 receive a conflict error" | Logic | If all 100 conflict on one slot, only 1 can succeed in total — "per pair" contradicts this |
| R-15 | NFR-05 | "100% coverage of the 17:00 boundary and the failure path" | Suite must contain tests for touching intervals (17:00), a 1-minute overlap, and the conflict failure path | Untestable | "Coverage of a boundary" is not a coverage metric |
| R-16 | SC-02 | `My Bookings)Skip and cancels` | Removed corrupted text | Format | Garbled AI output |
| R-17 | FR-09 | `edit“,” and` | `edit, and` | Format | Garbled AI output |
| R-18 | NFR-02 | Covered only dashboard and approve/reject | Extended to managing items and cancelling another member's booking; removed "within 1 s" | Missing | Access-control scenario left two protected actions (FR-08 ownership, item management) unmeasured |
| R-19 | NFR-03 / FR list | NFR-03 measured "find availability", but no FR let a Member see availability (FR-11 is Supervisor only) | Added **FR-16**: Request Booking shows booked ranges for the selected item and date, without revealing who booked; reflected in US-02, UC-02, SC-01 | Missing | NFR measured a behaviour no FR defined; the persona's "can't see what times are free" had no solution |
| R-20 | NFR-04 | Stimulus "Two Members" vs measure "100 requests"; artifact and response named a transaction and serialization | Stimulus aligned to "up to 100 Members"; mechanism removed | Inconsistent | Scenario parts contradicted each other; the concurrency mechanism is an ADR decision |
| R-21 | FR-08, UC-04 | "whose start time has not passed" | "strictly before its start time" with the 18:59 / 19:00 example | Untestable | At exactly the start time the wording allowed cancelling, contradicting US-04's boundary |
| R-22 | UC-01 | Actor: Member only | Added Supervisor (log in only; accounts seeded) | Missing | Supervisors log in in SC-01 and SC-03 but no use case covered it |

## CHARTER.md

| # | Location | AI draft said | Correction | Category | Reason |
|---|---|---|---|---|---|
| C-01 | Problem statement | "this happens **weekly** during peak evening hours" | Marked as [ASSUMPTION — frequency not measured] | Untestable | The AI invented a frequency with no evidence |
| C-02 | Product scope | "Member registration and login **(Laravel Breeze)**" | Removed "(Laravel Breeze)" | Leak | Library choice is a design decision for an ADR, not product scope |
| C-03 | Assumptions | Operating hours "06:00–23:00 … exact hours **to be confirmed**" | Hours fixed at 06:00–23:00, referencing FR-13 | Inconsistent | FR-13 tests exact hours; the charter said they were undecided |
| C-04 | Non-goals | AI features: "Not required for this app project type" | "Excluded to keep scope within one-person capacity; the core problem is solved by deterministic rules" | Logic | Original reason did not justify the exclusion |
| C-05 | Non-goals | No mention of the extra pages the auth scaffolding generates | Added non-goal: password reset, email verification, profile pages | Scope | Without it the app exceeds the 6-screen cap |
| C-06 | Product scope | Screens not listed | Listed the 6 screens (at the cap) | Scope | Makes the scope ceiling checkable |
| C-07 | Product scope | No statement on how Supervisors are created | Added: Supervisors by database seeding only | Missing | Charter must match FR-15 |
| C-08 | Stakeholders | Listed "SWE 485 instructor/assessor" | Removed | Logic | The instructor grades the project; they do not use or depend on the product |
| C-09 | Whole charter | No implementation language declared | Added "PHP 8.2 with Laravel 12" | Missing | Required at M1 by the project brief |
| C-10 | Assumptions | `(no email). [ASSUMPTION]` | Removed duplicate tag | Format | Duplicated AI output |

## TRACEABILITY.md

| # | Location | AI draft said | Correction | Category | Reason |
|---|---|---|---|---|---|
| T-01 | Matrix | FR-13, FR-14, FR-15 absent | Added rows with sources and scenarios | Missing | Follows from R-01 to R-03 |
| T-02 | NFR-05 row | Traced to US-07 / UC-05 (manage items) | Traced to US-02 / UC-02 / SC-02, related FR-04, FR-13 | Logic | NFR-05 is about the booking-conflict rule, not item management |
| T-03 | FR-12 row | Traced to US-01 (member registration) | Traced to US-05, US-06, US-07 / UC-03, UC-05 | Logic | FR-12 restricts Supervisor-only actions; US-01 has none |
| T-04 | FR-10 row | No use case | Linked to UC-02 postcondition ("visible on My Bookings") | Missing | Every FR needs a use-case link |
| T-05 | FR-05 row | Only US-03 / UC-02 | Added US-05 / UC-03 | Missing | decided_at is set when the Supervisor approves or rejects |
| T-06 | Matrix | NFRs not linked to the FRs they constrain | Added an "NFR → FR links" section | Missing | Needed to trace quality scenarios to design and tests |
| T-07 | Coverage check | AI marked "No orphans ✔" itself | Replaced with a hand-verified coverage section (stories, use cases, scenarios) | Logic | The AI's self-check missed T-02 to T-05; it could not be trusted |
| T-08 | FR-05, FR-10 rows | Not linked to UC-04 | Linked FR-05 and FR-10 to UC-04, and FR-10 to SC-02 | Missing | UC-04 cancels from My Bookings (FR-10) and sets status Cancelled (FR-05) |
| T-09 | NFR-02 row | Linked to US-04, US-07 / UC-04, UC-05 without support in NFR text; US-06 missing | Links match the extended NFR-02 (R-18) and include US-06 | Inconsistent | Every trace link must be justified by the requirement text |
| T-10 | Matrix | — (FR-16 added during review) | Added FR-16 row; linked FR-04 to SC-01 (touching 17:00–19:00 / 19:00–21:00); NFR-03 linked to FR-16 | Missing | Keep the matrix consistent with REQUIREMENTS v3 |

## Summary
- Total corrections: 42 (22 requirements, 10 charter, 10 traceability)
- Most common AI error type: untestable wording and plausible-but-wrong logic.
- Lesson: the AI's own "✔ no orphans" self-check was not reliable; every claim was re-verified by hand.
