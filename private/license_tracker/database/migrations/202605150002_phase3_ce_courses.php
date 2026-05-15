<?php

declare(strict_types=1);

return [
"ALTER TABLE ce_courses ADD COLUMN renewal_cycle_id INTEGER",
"ALTER TABLE ce_courses ADD COLUMN date_completed TEXT",
"ALTER TABLE ce_courses ADD COLUMN category TEXT",
"ALTER TABLE ce_courses ADD COLUMN format TEXT",
"ALTER TABLE ce_courses ADD COLUMN delivery_mode TEXT",
"ALTER TABLE ce_courses ADD COLUMN approval_source TEXT",
"ALTER TABLE ce_courses ADD COLUMN counts_toward_cycle INTEGER NOT NULL DEFAULT 1",
"ALTER TABLE ce_courses ADD COLUMN is_professional_conference INTEGER NOT NULL DEFAULT 0",
"ALTER TABLE ce_courses ADD COLUMN notes TEXT"
];
