# Risk Register — Sports Field & Equipment Booking System

**Date:** 2026-11-08 · **Owner of all risks:** Abdullah Alhirbish (solo project)

| ID | Risk | Likelihood / Impact | Threshold (trigger) | Response | Owner |
|---|---|---|---|---|---|
| R-01 | NFR-04 test runs on SQLite, where `lockForUpdate()` does nothing, so it passes falsely | H / H | The 100-request test is configured on any driver other than MySQL | Separate `phpunit` config for the concurrency test on MySQL with parallel processes | Abdullah |
| R-02 | Conflict check runs before the `booking_locks` lock → double bookings | M / H | The 100-request test on MySQL commits > 1 booking | Fix order in `createRequest` (lock → check → insert); keep the test in CI | Abdullah |
| R-03 | PHP 8.2.12 on XAMPP is vulnerable (CVE-2026-109618/109619); PHP 8.2 security support ends 31 Dec 2026 | H / M | Installed PHP < 8.2.35 (triggered 2026-10-21) | Upgrade to ≥ 8.2.35; bind Apache to 127.0.0.1; plan PHP 8.3+ before 31 Dec 2026 | Abdullah |
| R-04 | Scope grows past the agreed ceiling | M / M | Routes > 14 or tables > 8 | No new routes without instructor approval | Abdullah |
