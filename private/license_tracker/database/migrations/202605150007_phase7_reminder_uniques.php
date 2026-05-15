<?php

declare(strict_types=1);

return [
"CREATE UNIQUE INDEX IF NOT EXISTS idx_reminder_logs_dedupe ON reminder_logs (reminder_key, license_id, renewal_cycle_id, recipient_email, sent_date)"
];
