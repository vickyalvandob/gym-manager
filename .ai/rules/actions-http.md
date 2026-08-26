---
paths:
  - 'app/{Actions,Http}/**'
---

# Actions Http

## Require explicit trainer login mode
Creating a trainer profile must require an explicit account_mode: none, create, or link. Default the UI to none; never create a login account merely because user_id is empty.

## Create trainer login accounts automatically
This supersedes the older explicit trainer login mode rule. Every newly created trainer must create and link a Trainer-role login account from the required email and password; do not expose none, create, or link account choices in the trainer form.
