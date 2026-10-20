# Charter — Sports Field & Equipment Booking System

**Course:** SWE 485 — Advanced Software Engineering with AI
**Milestone:** M1 Requirements and Architecture
**Status:** DRAFT v1 — for team review
**Date:** 2026-10-20

## Team members
- Abdullah Alharbish — solo project.
  (If paired, add: `- <Partner Name> — <role>` and update contribution statements.)

## Stakeholder problem statement
The community sports center manages field and equipment bookings through
informal WhatsApp messages. There is no central record of who booked what for
when, and the supervisor has no single view of availability.

Consequences:
- **Double bookings:** two members request the same field/item for overlapping
  times; this happens weekly during peak evening hours.
- **No durable record:** there is no authoritative log of who was responsible
  for a field or piece of equipment at a given time.
- **No availability at a glance:** the supervisor cannot quickly see which
  slots are free or booked.
- **Disputes:** arguments about who booked a slot have no source of truth.

The system replaces informal WhatsApp coordination with a central, role-based
booking record and a live availability view.

## Product scope (in-scope features)
- Member registration and login (Laravel Breeze).
- Member creates a booking request for an available field/item on a date/time slot.
- Conflict detection so overlapping requests are rejected.
- Member "My Bookings" page showing status (Pending / Approved / Rejected / Cancelled).
- Member cancels a booking they own.
- Supervisor dashboard: view all bookings and availability, approve/reject pending requests.
- Supervisor manages items (add, edit, deactivate fields and equipment).
- Exactly two user roles: Member and Supervisor.
- Synthetic data only — no real names, phone numbers, or personal data.

## Explicit non-goals
| Non-goal | Reason |
| --- | --- |
| Online payment | Bookings are free; no payment is required. [ASSUMPTION] |
| Mobile app | Web app only; responsive but not native. |
| SMS / WhatsApp notifications | No external messaging; status is shown in-app on My Bookings. |
| Multi-branch support | One community sports center, one deployment target. |
| AI features | Not required for this app project type. |
| Public API for third parties | Internal web app only; no public API. |

## Stakeholders
- **Members** — book fields/equipment; want confirmation and no clashes.
- **Supervisor** — approves/rejects requests and manages the item catalog.
- **Sports center owner/management** — want reliable records and fewer double-bookings.
- **SWE 485 instructor/assessor** — evaluates the project against the rubric.

## Assumptions
- [ASSUMPTION] The center has operating hours (e.g., 06:00–23:00) enforced on booking times; exact hours to be confirmed.
- [ASSUMPTION] A booking is a single date and time slot — no recurring bookings.
- [ASSUMPTION] The catalog starts with a small synthetic set of fields/items.
- [ASSUMPTION] Status is communicated in-app only (no email). [ASSUMPTION]
