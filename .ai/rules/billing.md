---
paths:
  - 'app/Actions/Fortify/**,app/Actions/Subscriptions/**,app/Http/Controllers/PlatformBillingController.php,resources/js/pages/{auth/register.tsx,subscription/**,platform/billing/**}'
---

# Billing

## Free onboarding and database-backed manual billing
Self-service registration always starts on the active `free` plan and never accepts a package choice from the browser. Paid upgrades are selected later by the subscriber; server-side plan price/capacity is authoritative, transfer proof stays pending until Platform Admin approval, and the destination bank account is configured in `platform_billing_settings` from the Platform Billing workspace rather than environment variables.
