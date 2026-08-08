Unclad Collection — Advertising Fair Rotation Patch

Purpose
-------
Replaces pure weighted-random ad delivery with fair, stateful rotation while preserving the existing Rotation Weight values.

Behavior
--------
1. Campaigns are selected first, so a campaign with several creatives does not get extra traffic merely because it has more creative records.
2. Smooth weighted round-robin keeps long-term delivery influenced by Rotation Weight.
3. Consecutive-display guard:
   - 1 eligible campaign: unlimited (nothing else can display)
   - 2-3 eligible campaigns: max 2 consecutive selections when alternatives exist
   - 4+ eligible campaigns: max 1 consecutive selection when alternatives exist
4. Starvation guard: every continuously eligible campaign is forced back into rotation if it has gone too long without a turn. The threshold is 2 × the number of eligible campaigns.
5. Creatives inside the selected campaign rotate round-robin, so multiple creatives within one campaign all receive exposure.
6. Rotation state is stored in Laravel Cache for 7 days and protected with a cache lock when supported. No migration is required.

Files
-----
app/Services/PublicAdDeliveryService.php
tests/Feature/Advertising/PublicAdDeliveryTest.php

Install
-------
Copy the files over the matching paths in the application, then run:

php artisan optimize:clear
php artisan test --filter=PublicAdDeliveryTest
npm run build

Notes
-----
- Existing Rotation Weight values continue to work.
- Weight is now a preference within a fairness envelope. Very extreme weights may not achieve an exact percentage because the anti-streak and starvation safeguards intentionally guarantee exposure to the other eligible campaigns.
- Clearing Laravel cache resets the rotation history; the algorithm immediately begins building a new fair sequence.
