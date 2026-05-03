# Messaging Operations Runbook

> **Phase 7K** — safe toggle reference for OTP and booking notification drivers.
> All toggle decisions are made via environment variables only. No code changes are required to switch modes.

## Overview

Eventaat messaging is controlled by two env-driven driver toggles:

| Toggle | Config key | Default | Controls |
|---|---|---|---|
| `OTP_DRIVER` | `eventaat-notifications.otp.driver` | `log` | Mobile login/register OTP delivery |
| `NOTIFICATION_DRIVER` | `eventaat-notifications.booking_notifications.driver` | `dry_run` | Booking lifecycle SMS dispatch |

Both defaults are intentionally safe: no real SMS or WhatsApp is sent unless you explicitly opt in.

---

## Mode Reference

### Mode 1 — Local safe mode (default)

No external calls. OTP is logged to the application log. Booking notifications are dry-run only (recorded in `notification_dispatch_attempts` with provider `internal_dry_run`).

```env
OTP_DRIVER=log
NOTIFICATION_DRIVER=dry_run
```

- OTP codes appear in `storage/logs/laravel.log` (grep for `OTP`).
- `notification_dispatch_attempts` rows are created but no SMS is sent.
- No Twilio credentials required.
- **Use for**: local development, CI, staging without real numbers.

---

### Mode 2 — SMS OTP test mode

Real OTP SMS sent via Twilio. Booking notifications remain dry-run.

```env
OTP_DRIVER=twilio_sms
NOTIFICATION_DRIVER=dry_run

TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=<your_auth_token>
TWILIO_MESSAGING_SERVICE_SID=MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_OTP_VALIDITY_PERIOD=300
```

- OTP SMS delivered to real phone numbers via Twilio Programmable Messaging (Messaging Service SID).
- Booking notification dispatch still records dry-run attempt rows — no outbound SMS.
- **Use for**: testing OTP delivery end-to-end without activating booking SMS.
- Verify delivery in the Twilio console (Message Logs) and in `otp_delivery_attempts` (Platform panel, **OTP delivery attempts** resource).

---

### Mode 3 — Booking SMS test mode

Both OTP and booking notification SMS are live.

```env
OTP_DRIVER=twilio_sms
NOTIFICATION_DRIVER=twilio_sms

TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=<your_auth_token>
TWILIO_MESSAGING_SERVICE_SID=MGxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_OTP_VALIDITY_PERIOD=300
TWILIO_NOTIFICATION_VALIDITY_PERIOD=36000
```

- OTP SMS delivered as in Mode 2.
- Booking lifecycle notifications (requested / accepted / rejected / cancelled / arrived / seated / completed / no-show) dispatched as real SMS when an operator triggers dispatch in the Platform panel.
- Booking SMS uses the same `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, and `TWILIO_MESSAGING_SERVICE_SID` as OTP.
- `TWILIO_NOTIFICATION_VALIDITY_PERIOD` controls booking SMS queue TTL only (1–36000 s; default 36000). Independent of `TWILIO_OTP_VALIDITY_PERIOD`.
- Monitor dispatch attempts in Platform → **Booking notifications** list.
- **Use for**: full production messaging or pre-production sign-off with real test numbers.

> **Recommended practice**: enable `NOTIFICATION_DRIVER=twilio_sms` only after confirming OTP delivery in Mode 2. Always run `php artisan optimize:clear` after changing driver env vars on a cached deployment.

---

### Mode 4 — WhatsApp OTP mode ⚠️ BLOCKED

> **Status: blocked — do not activate until Meta/Twilio approve the Authentication template.**

```env
OTP_DRIVER=twilio_whatsapp
# NOTIFICATION_DRIVER stays dry_run (WhatsApp booking notifications not implemented)

TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=<your_auth_token>
TWILIO_WHATSAPP_FROM=whatsapp:+<twilio_whatsapp_number>
TWILIO_WHATSAPP_OTP_CONTENT_SID=HXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

- OTP sent via Twilio Content API using an approved WhatsApp Authentication Content Template.
- OTP code is passed as `contentVariables` slot `1` — no plain SMS body.
- `TWILIO_OTP_VALIDITY_PERIOD` is configured but **not** applied to WhatsApp template sends (Twilio Content-template flow omits `validityPeriod`).
- `MissingTwilioWhatsAppOtpConfigurationException` is thrown at send time if `TWILIO_WHATSAPP_FROM` or `TWILIO_WHATSAPP_OTP_CONTENT_SID` is empty.
- WhatsApp booking notifications are **not** implemented in any current phase.
- **Use for**: after template approval only. Coordinate with Twilio support and confirm `TWILIO_WHATSAPP_OTP_CONTENT_SID` from the Twilio console Content Template list.

---

## Twilio Status Webhook

### Endpoint

```
POST /api/webhooks/twilio/otp-status
```

Named route: `webhooks.twilio.otp-status`

- **No** Sanctum Bearer token required.
- **Not** part of the mobile `/api/mobile` contract — does not change OTP or booking API responses.

### Purpose

Receives Twilio delivery status callbacks (e.g. `delivered`, `undelivered`, `failed`) and updates the corresponding `otp_delivery_attempts` row. Keeps the Platform **OTP delivery attempts** resource accurate for operational monitoring.

### Authentication

| Preferred | Fallback |
|---|---|
| Twilio `X-Twilio-Signature` HMAC validated with `TWILIO_AUTH_TOKEN` | `X-Eventaat-Webhook-Secret` header matched against `TWILIO_WEBHOOK_SECRET` |

Both `TWILIO_AUTH_TOKEN` and `TWILIO_WEBHOOK_SECRET` empty → **403** (all callbacks rejected). Do not leave both unset in production.

### Twilio Console Setup

1. Go to **Messaging → Services → [your Messaging Service]**.
2. Under **Integration**, set **Status callback URL** to:
   ```
   https://yourdomain.com/api/webhooks/twilio/otp-status
   ```
3. Save. Twilio will POST delivery status updates for every outbound OTP message SID.

### Request payload (common fields)

Twilio sends `application/x-www-form-urlencoded`. Stored fields: `MessageSid`, `MessageStatus` / `SmsStatus`, optional `ErrorCode`, `ErrorMessage`, `AccountSid`. Fields containing phone numbers or message bodies are **not** stored.

### Response

- `200` (empty body) — status processed or `MessageSid` not found (no new row created for unknown SIDs).
- `403` — authentication failed.

---

## Safety Checklist

| Rule | Detail |
|---|---|
| **Never commit `.env`** | `.env` is in `.gitignore`. Real credentials belong in your private `.env` only. |
| **Rotate immediately if exposed** | If `TWILIO_AUTH_TOKEN` appears in a commit, PR, or log: rotate it in the Twilio console immediately, then update your server `.env`. |
| **Dry-run before production** | Always confirm `NOTIFICATION_DRIVER=dry_run` creates attempt rows correctly before switching to `twilio_sms`. |
| **Monitor OTP delivery** | Platform panel → **OTP delivery attempts** — check `status` column for `delivered` / `failed`. Cross-check in Twilio console → Message Logs. |
| **Monitor booking dispatch** | Platform panel → **Booking notifications** — check dispatch attempt `status` per notification row. |
| **Rate limits are active** | `OTP_REQUEST_COOLDOWN_SECONDS` (default 60 s), `OTP_REQUEST_MAX_PER_HOUR` (default 5), `OTP_VERIFY_MAX_ATTEMPTS` (default 5 per `OTP_VERIFY_DECAY_MINUTES`=10 min). Adjust in `.env` only — cache-backed, no DB migration needed. |
| **`php artisan optimize:clear` after env changes** | Cached config (`php artisan config:cache`) will not pick up `.env` changes until cache is cleared. |
| **WhatsApp template required** | `OTP_DRIVER=twilio_whatsapp` requires a Meta/Twilio-approved Authentication Content Template. Do not set this value until `TWILIO_WHATSAPP_OTP_CONTENT_SID` is confirmed. |

---

## Quick Reference

```env
# Safe local defaults (no external calls)
OTP_DRIVER=log
NOTIFICATION_DRIVER=dry_run

# OTP SMS only
OTP_DRIVER=twilio_sms
NOTIFICATION_DRIVER=dry_run

# Both OTP and booking SMS live
OTP_DRIVER=twilio_sms
NOTIFICATION_DRIVER=twilio_sms

# WhatsApp OTP — BLOCKED until template approved
OTP_DRIVER=twilio_whatsapp
NOTIFICATION_DRIVER=dry_run
```

---

## Related

- Config: `backend/config/eventaat-notifications.php`
- Env template: `backend/.env.example`
- API reference (OTP + webhook): `docs/api-reference.md`
- Implementation history: `docs/implementation-plan.md` (Phases 7B–7K)
- Product blueprint: `docs/product-blueprint.md`
