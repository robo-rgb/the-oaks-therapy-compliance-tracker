<?php

declare(strict_types=1);

return [
"ALTER TABLE licenses ADD COLUMN licensee_first_name TEXT",
"ALTER TABLE licenses ADD COLUMN licensee_last_name TEXT",
"ALTER TABLE licenses ADD COLUMN state TEXT DEFAULT 'GA'",
"ALTER TABLE licenses ADD COLUMN original_issue_date TEXT",
"ALTER TABLE licenses ADD COLUMN notes TEXT",
"ALTER TABLE renewal_cycles ADD COLUMN renewal_deadline TEXT",
"ALTER TABLE renewal_cycles ADD COLUMN late_renewal_deadline TEXT",
"ALTER TABLE renewal_cycles ADD COLUMN is_active INTEGER NOT NULL DEFAULT 0",
"ALTER TABLE renewal_cycles ADD COLUMN renewal_submitted INTEGER NOT NULL DEFAULT 0",
"ALTER TABLE renewal_cycles ADD COLUMN renewal_submitted_date TEXT",
"ALTER TABLE renewal_cycles ADD COLUMN renewal_fee_paid INTEGER NOT NULL DEFAULT 0",
"ALTER TABLE renewal_cycles ADD COLUMN renewal_fee_paid_date TEXT"
];
