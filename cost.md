# Cost Model — Sports Field & Equipment Booking System

**Date:** 2026-11-08 · **Author:** Abdullah Alhirbish · **Chosen design:** Option A (ADR-001)

## 1. Usage assumptions
| # | Assumption | Value |
|---|---|---|
| U1 | Users | ~50 Members, 1–2 Supervisors (synthetic data) |
| U2 | Peak concurrency | 50 booking requests (NFR-01) |
| U3 | Bookings per month | ~500 |
| U4 | DEVAI prompts in M1 | ~6 (charter + requirements ×3, architecture ×2, review ×1) |
| U5 | Tokens per prompt | ~50,000 input (attached files + agent context), ~8,000 output |
| U6 | Exchange rate | 1 EUR ≈ 1.15 USD |

## 2. Published rates
| Item | Rate | Source (retrieved) |
|---|---|---|
| Hetzner CX23 VPS (2 vCPU, 4 GB, 40 GB) | €5.49/mo + €0.50 IPv4 = **€5.99/mo** | Cloud Pricing Comparison, https://kimmo.suominen.com/stuff/cpc-2026-08.txt (Aug 2026; checked 2026-11-08) |
| DeepSeek-V4.1-Flash (model used in DEVAI) | **$0.30 / 1M input, $1.20 / 1M output** (Together AI list price; DeepSeek official: RMB 1 / RMB 4 off-peak, ×2 at peak) | https://llmgateway.io/models/deepseek-v4.1-flash · https://technode.com/2026/09/10/deepseek-formally-launches-v4-1-flash-routes-v4-pro-requests-to-flash/ (checked 2026-11-08) |
| Firebase (Option B, for comparison) | Free quotas; Blaze plan required for Functions | https://firebase.google.com/pricing |

## 3. Calculation
**Hosting (Option A):** €5.99/mo ≈ **$6.89/mo** → $82.7/year. Development on local XAMPP: **$0**. One VPS covers U1–U3 easily.

**AI tokens (M1):**
- Input: 6 × 50,000 = 300,000 tokens × $0.30/1M = $0.090
- Output: 6 × 8,000 = 48,000 tokens × $1.20/1M = $0.058
- **Total ≈ $0.15** at list price. **Actual cost to me: $0** — DEVAI access is provided free by the instructor.

## 4. Sensitivity (×10)
| Scenario | Hosting | AI tokens |
|---|---|---|
| Baseline | $6.89/mo | $0.15 |
| ×10 users / bookings (500 users, 5,000 bookings/mo) | Same VPS; still well below 2 vCPU / 4 GB → **$6.89/mo** | — |
| ×10 tokens (60 prompts or 10× longer context) | — | **$1.48** |

**Conclusion:** cost is dominated by the fixed VPS (~$7/mo). AI cost stays under $2 even at ×10, and is $0 to me in this course.
