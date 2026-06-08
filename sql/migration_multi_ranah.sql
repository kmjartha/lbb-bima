-- Migration: Support multiple ranah per topic
-- Change subject_topics.ranah from single ENUM to JSON array
-- This allows a topic to be graded in sikap, pengetahuan, AND keterampilan simultaneously

-- Backup the old column (optional, comment out if not needed)
-- ALTER TABLE subject_topics ADD COLUMN ranah_old VARCHAR(20) NULL;
-- UPDATE subject_topics SET ranah_old = ranah;

-- Add new JSON column for multiple ranah
ALTER TABLE subject_topics ADD COLUMN ranah_list JSON NULL DEFAULT NULL;

-- Migrate existing ENUM values to JSON arrays
-- Each topic gets its single ranah wrapped in an array
UPDATE subject_topics 
SET ranah_list = JSON_ARRAY(ranah) 
WHERE ranah IS NOT NULL AND ranah_list IS NULL;

-- Drop the old ENUM column (uncomment when ready)
-- ALTER TABLE subject_topics DROP COLUMN ranah;

-- Add index for performance (optional)
-- ALTER TABLE subject_topics ADD FULLTEXT INDEX ft_ranah_list (ranah_list);

-- Alternative: If you want to keep ranah column for backward compatibility,
-- you can use a computed column (MySQL 5.7+):
-- ALTER TABLE subject_topics ADD COLUMN ranah_computed VARCHAR(50) 
--     GENERATED ALWAYS AS (JSON_EXTRACT(ranah_list, '$[0]')) STORED;

COMMIT;
