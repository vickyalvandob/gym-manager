---
paths:
  - 'app/{Actions,Http,Models,Support}/**, resources/js/{components,pages}/**'
---

# Componentspages

## Approve manual billing at subscriber level
SaaS billing belongs to subscriptions.subscriber_id, never to an individual gym. Owners upload one private manual payment proof and remain pending until Platform Admin review. Only an approved review may atomically activate or extend the subscription; expired access disables operational navigation while keeping subscriber billing reachable.
