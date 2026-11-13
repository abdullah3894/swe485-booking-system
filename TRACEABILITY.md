# Traceability Matrix — Sports Field & Equipment Booking System

**Status:** v4 — human-reviewed
**Verified by:** Abdullah Alhirbish (every link checked by hand against REQUIREMENTS.md v3 and M1-architecture-draft.md §2.4)

Architecture element IDs are defined in `M1-architecture-draft.md` §2.4 (A-ID Identity, A-CAT Catalog, A-BK Booking, A-WEB Web layer, A-POL Authorization). ADRs are in `M1-architecture-draft.md` §8. Test ID is filled in M2.

| Req ID | Source (US/UC) | Scenario | Architecture element | ADR | Test ID |
| --- | --- | --- | --- | --- | --- |
| FR-01 | US-01 / UC-01 | SC-01 | A-ID, A-WEB | ADR-001 | TBD |
| FR-02 | US-01 / UC-01 | SC-01 | A-ID, A-WEB | ADR-001 | TBD |
| FR-03 | US-02 / UC-02 | SC-01 | A-BK, A-WEB | ADR-001 | TBD |
| FR-04 | US-02 / UC-02 | SC-01, SC-02 | A-BK (`createRequest`, `booking_locks`) | ADR-003 | TBD |
| FR-05 | US-03, US-04, US-05 / UC-02, UC-03, UC-04 | SC-01, SC-02 | A-BK | ADR-001 | TBD |
| FR-06 | US-05 / UC-03 | SC-01, SC-03 | A-BK, A-POL | ADR-001 | TBD |
| FR-07 | US-05 / UC-03 | SC-03 | A-BK, A-POL | ADR-001 | TBD |
| FR-08 | US-04 / UC-04 | SC-02 | A-BK, A-POL (owner policy) | ADR-001 | TBD |
| FR-09 | US-07 / UC-05 | SC-03 | A-CAT | ADR-001 | TBD |
| FR-10 | US-03 / UC-02, UC-04 | SC-01, SC-02 | A-BK, A-WEB | ADR-001 | TBD |
| FR-11 | US-06 / UC-03 | SC-01, SC-03 | A-BK (`supervisorSchedule`), A-POL | ADR-001 | TBD |
| FR-12 | US-05, US-06, US-07 / UC-03, UC-05 | SC-01, SC-03 | A-POL | ADR-001 | TBD |
| FR-13 | US-02 / UC-02 | SC-02 | A-WEB (form request), A-BK | ADR-001 | TBD |
| FR-14 | US-07 / UC-03, UC-05 | SC-03 | A-CAT (`isActive`), A-BK | ADR-001 | TBD |
| FR-15 | US-01 / UC-01 | SC-01 | A-ID | ADR-001 | TBD |
| FR-16 | US-02 / UC-02 | SC-01 | A-BK (`bookedRangesFor`) | ADR-001 | TBD |
| NFR-01 | US-02 / UC-02 | SC-01 | A-BK, MySQL index | ADR-002 | TBD |
| NFR-02 | US-04, US-05, US-06, US-07 / UC-03, UC-04, UC-05 | SC-03 | A-POL | ADR-001 | TBD |
| NFR-03 | US-02 / UC-02 | SC-01 | A-WEB | ADR-001 | TBD |
| NFR-04 | US-02 / UC-02 | SC-02 | A-BK, `booking_locks`, MySQL InnoDB | ADR-002, ADR-003 | TBD |
| NFR-05 | US-02 / UC-02 | SC-02 | A-BK | ADR-001 | TBD |

## Coverage check (human-verified)

**User stories → FR** — every story maps to at least one FR:

| Story | FRs |
| --- | --- |
| US-01 | FR-01, FR-02, FR-15 |
| US-02 | FR-03, FR-04, FR-13, FR-16 |
| US-03 | FR-05, FR-10 |
| US-04 | FR-05, FR-08 |
| US-05 | FR-05, FR-06, FR-07, FR-12 |
| US-06 | FR-11, FR-12 |
| US-07 | FR-09, FR-12, FR-14 |

**Use cases → FR** — every use case maps to at least one FR:

| Use case | FRs |
| --- | --- |
| UC-01 | FR-01, FR-02, FR-15 |
| UC-02 | FR-03, FR-04, FR-05, FR-10, FR-13, FR-16 |
| UC-03 | FR-05, FR-06, FR-07, FR-11, FR-12, FR-14 |
| UC-04 | FR-05, FR-08, FR-10 |
| UC-05 | FR-09, FR-12, FR-14 |

**Scenarios → requirements:**

| Scenario | Exercises |
| --- | --- |
| SC-01 | FR-01, 02, 03, 04, 05, 06, 10, 11, 12, 15, 16 · NFR-01, NFR-03 |
| SC-02 | FR-04, 05, 08, 10, 13 · NFR-04, NFR-05 |
| SC-03 | FR-06, 07, 09, 11, 12, 14 · NFR-02 |

**NFR → FR links** — each quality scenario constrains these FRs:

| NFR | Related FRs |
| --- | --- |
| NFR-01 | FR-03, FR-04 |
| NFR-02 | FR-08, FR-12 |
| NFR-03 | FR-03, FR-10, FR-16 |
| NFR-04 | FR-04 |
| NFR-05 | FR-04, FR-13 |

**Architecture → requirements:** every element serves at least one requirement — A-ID (FR-01, 02, 15), A-CAT (FR-09, 14), A-BK (FR-03–08, 10, 11, 13, 14, 16), A-WEB (FR-01–03, 10, 13; NFR-03), A-POL (FR-06–08, 11, 12; NFR-02).

**Orphan check:** 16 FRs and 5 NFRs, each with a source story, a use case, a scenario, an architecture element and an ADR. No orphan requirements.
