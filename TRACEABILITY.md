# Traceability Matrix — Sports Field & Equipment Booking System

**Status:** DRAFT v2 (human review ) 
Architecture element, ADR and Test ID are **TBD**: Architecture and ADR are filled later in M1 after the architecture decision; Test ID is filled in M2.

| Req ID | Source (US/UC) | Scenario | Architecture element | ADR | Test ID |
| --- | --- | --- | --- | --- | --- |
| FR-01 | US-01 / UC-01 | SC-01 | TBD | TBD | TBD |
| FR-02 | US-01 / UC-01 | SC-01 | TBD | TBD | TBD |
| FR-03 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| FR-04 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |
| FR-05 | US-03, US-04, US-05 / UC-02, UC-03, UC-04 | SC-01, SC-02 | TBD | TBD | TBD |
| FR-06 | US-05 / UC-03 | SC-01, SC-03 | TBD | TBD | TBD |
| FR-07 | US-05 / UC-03 | SC-03 | TBD | TBD | TBD |
| FR-08 | US-04 / UC-04 | SC-02 | TBD | TBD | TBD |
| FR-09 | US-07 / UC-05 | SC-03 | TBD | TBD | TBD |
| FR-10 | US-03 / UC-02, UC-04 | SC-01, SC-02 | TBD | TBD | TBD |
| FR-11 | US-06 / UC-03 | SC-01, SC-03| TBD | TBD | TBD |
| FR-12 | US-05, US-06, US-07 / UC-03, UC-05 | SC-01, SC-03 | TBD | TBD | TBD |
| FR-13 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |
| FR-14 | US-07 / UC-03, UC-05 | SC-03 | TBD | TBD | TBD |
| FR-15 | US-01 / UC-01 | SC-01 | TBD | TBD | TBD |
| NFR-01 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| NFR-02 | US-04, US-05, US-06, US-07 / UC-03, UC-04, UC-05 | SC-03 | TBD | TBD | TBD |
| NFR-03 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| NFR-04 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |
| NFR-05 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |

## Coverage check 

**User stories → FR** — every story maps to at least one FR:

| Story | FRs |
| --- | --- |
| US-01 | FR-01, FR-02, FR-15 |
| US-02 | FR-03, FR-04, FR-13 |
| US-03 | FR-05, FR-10 |
| US-04 | FR-05, FR-08 |
| US-05 | FR-05, FR-06, FR-07, FR-12 |
| US-06 | FR-11, FR-12 |
| US-07 | FR-09, FR-12, FR-14 |

**Use cases → FR** — every use case maps to at least one FR:

| Use case | FRs |
| --- | --- |
| UC-01 | FR-01, FR-02, FR-15 |
| UC-02 | FR-03, FR-04, FR-05, FR-10, FR-13 |
| UC-03 | FR-05, FR-06, FR-07, FR-11, FR-12, FR-14 |
| UC-04 | FR-05, FR-08, FR-10 |
| UC-05 | FR-09, FR-12, FR-14 |

**Scenarios → requirements:**

| Scenario | Exercises |
| --- | --- |
| SC-01 | FR-01, 02, 03, 05, 06, 10, 11, 12, 15 · NFR-01, NFR-03 |
| SC-02 | FR-04, 05, 08, 10, 13 · NFR-04, NFR-05 |
| SC-03 | FR-06, 07, 09, 11, 12, 14 · NFR-02 |

**NFR → FR links** — each quality scenario constrains these FRs:

| NFR | Related FRs |
| --- | --- |
| NFR-01 | FR-03, FR-04 |
| NFR-02 | FR-08, FR-12 |
| NFR-03 | FR-03, FR-10 |
| NFR-04 | FR-04 |
| NFR-05 | FR-04, FR-13 |

**Orphan check:** 15 FRs and 5 NFRs, each with a source story, a use case and a scenario. No orphan requirements. Every NFR links to the FRs it constrains.
