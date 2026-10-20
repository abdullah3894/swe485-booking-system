# Traceability Matrix — Sports Field & Equipment Booking System

**Status:** DRAFT v1
Architecture element, ADR and Test ID are **TBD** — they will be filled in after the
architecture (M1) and tests (M2). Every FR and NFR is present; no orphan requirements.

| Req ID | Source (US/UC) | Scenario | Architecture element | ADR | Test ID |
| --- | --- | --- | --- | --- | --- |
| FR-01 | US-01 / UC-01 | SC-01 | TBD | TBD | TBD |
| FR-02 | US-01 / UC-01 | SC-01 | TBD | TBD | TBD |
| FR-03 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| FR-04 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |
| FR-05 | US-03 / UC-02 | SC-01, SC-02 | TBD | TBD | TBD |
| FR-06 | US-05 / UC-03 | SC-01, SC-03 | TBD | TBD | TBD |
| FR-07 | US-05 / UC-03 | SC-03 | TBD | TBD | TBD |
| FR-08 | US-04 / UC-04 | SC-02 | TBD | TBD | TBD |
| FR-09 | US-07 / UC-05 | SC-03 | TBD | TBD | TBD |
| FR-10 | US-03 | SC-01 | TBD | TBD | TBD |
| FR-11 | US-06 / UC-03 | SC-03 | TBD | TBD | TBD |
| FR-12 | US-01, US-05, US-06, US-07 | SC-01, SC-03 | TBD | TBD | TBD |
| NFR-01 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| NFR-02 | US-05, US-07 | SC-03 | TBD | TBD | TBD |
| NFR-03 | US-02 / UC-02 | SC-01 | TBD | TBD | TBD |
| NFR-04 | US-02 / UC-02 | SC-02 | TBD | TBD | TBD |
| NFR-05 | US-02, US-07 / UC-05 | SC-01 | TBD | TBD | TBD |

## Coverage check
- **User stories → FR:** US-01→FR-01/02; US-02→FR-03/04; US-03→FR-10; US-04→FR-08; US-05→FR-06/07; US-06→FR-11; US-07→FR-09. Every story maps to ≥1 FR. ✔
- **Use cases → FR:** UC-01→FR-01/02; UC-02→FR-03/04/05; UC-03→FR-06/07/11; UC-04→FR-08; UC-05→FR-09. ✔
- **No orphans:** all 12 FRs and 5 NFRs appear in the matrix. ✔
- **Scenario coverage:** SC-01 (happy path + approval), SC-02 (conflict + cancel), SC-03 (dashboard + manage items). ✔
