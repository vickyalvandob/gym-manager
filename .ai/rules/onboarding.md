---
paths:
  - 'app/Actions/Onboarding/**,app/Http/{Controllers/OnboardingController.php,Requests/CompleteOnboardingRequest.php,Responses/RegisterResponse.php},resources/js/pages/onboarding/**'
---

# Onboarding

## Keep onboarding focused and server authoritative
Registration redirects directly to onboarding. Onboarding is a standalone profile form with optional contact fields and no currency input; CompleteGymOnboarding always writes IDR. Completion is idempotent under the gym row lock: a repeated request must not overwrite the profile, timestamp, or audit. Avoid loading subscription plans on the setup page.
