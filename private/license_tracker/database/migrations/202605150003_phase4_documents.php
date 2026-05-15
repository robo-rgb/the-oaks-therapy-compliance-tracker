<?php

declare(strict_types=1);

return [
"ALTER TABLE documents ADD COLUMN renewal_cycle_id INTEGER",
"ALTER TABLE documents ADD COLUMN ce_course_id INTEGER",
"ALTER TABLE documents ADD COLUMN title TEXT",
"ALTER TABLE documents ADD COLUMN stored_filename TEXT",
"ALTER TABLE documents ADD COLUMN file_path TEXT",
"ALTER TABLE documents ADD COLUMN mime_type TEXT",
"ALTER TABLE documents ADD COLUMN file_size INTEGER",
"ALTER TABLE documents ADD COLUMN notes TEXT"
];
