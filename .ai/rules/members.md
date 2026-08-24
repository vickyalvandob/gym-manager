---
paths:
  - 'app/Actions/Members/**'
---

# Members

## Allocate member numbers with the locked gym sequence
Generate member_number only on the backend inside a transaction. Lock the current Gym row with lockForUpdate, consume next_member_sequence, then increment it. Never derive the next number from the latest member row or accept gym_id/member_number from frontend input.
