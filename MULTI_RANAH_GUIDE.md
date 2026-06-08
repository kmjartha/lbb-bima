# Multi-Ranah Implementation Guide

## Overview
This implementation allows **a single topic to be graded in multiple ranah** (sikap, pengetahuan, keterampilan) simultaneously. Previously, each topic could only have one ranah.

## What Changed

### Database Schema
- New `ranah_list` JSON column in `subject_topics` table
- Backward compatible: old `ranah` column retained
- Migration script: [sql/migration_multi_ranah.sql](../sql/migration_multi_ranah.sql)

### Code Changes
#### [includes/grading_helpers.php](../includes/grading_helpers.php)
- **New function:** `extract_ranah_from_topic($topic)` - Extract ranah array from topic
- **New function:** `ranah_label($ranah)` - Get display label
- **New function:** `ranah_column($ranah)` - Get database column name
- **Updated:** `topics_for()` - Now selects `ranah_list` column
- **Updated:** `recap_topics()` - Returns nested data: `[student][topic][ranah] => value`
- **Updated:** `weighted_average_ranah()` - Distributes weight across multi-ranah topics

#### [public/grades_daily.php](../public/grades_daily.php)
- **Form Input:** Changed from `nilai[student_id]` to `nilai[student_id][ranah]`
- **UI:** Now shows separate input field for each ranah in a topic
- **Badges:** Display all ranah badges in topic header
- **POST Handler:** Dynamically builds INSERT statements for all ranah columns

#### [public/grades_topic_recap.php](../public/grades_topic_recap.php)
- **Table Headers:** Show one column per ranah per topic
- **CSV Export:** One export column per ranah per topic
- **Data Display:** Shows values for all ranah of multi-ranah topics

## Implementation Steps

### Step 1: Apply Database Migration
Run the migration script in your database:
```sql
-- In PHPMyAdmin or mysql CLI:
SOURCE /path/to/sql/migration_multi_ranah.sql;
```

Or manually:
```sql
ALTER TABLE subject_topics ADD COLUMN ranah_list JSON NULL DEFAULT NULL;

UPDATE subject_topics 
SET ranah_list = JSON_ARRAY(ranah) 
WHERE ranah IS NOT NULL AND ranah_list IS NULL;
```

### Step 2: Update Topics to Multiple Ranah
You'll need to update the `subject_topics` table to set `ranah_list` for topics that should have multiple ranah.

**Option A: Via Database** (Direct SQL)
```sql
-- Example: Update topic ID 5 to include all three ranah
UPDATE subject_topics 
SET ranah_list = JSON_ARRAY('sikap', 'pengetahuan', 'keterampilan')
WHERE id = 5;
```

**Option B: Create Admin UI** (Recommended)
You should update [public/admin/subject_topics.php](../public/admin/subject_topics.php) to:
- Add checkboxes for each ranah instead of radio buttons
- Allow multiple selection
- Update the form to save `ranah_list` as JSON

### Step 3: Verify Changes
After applying the migration:
1. Go to [Penilaian Harian](grades_daily.php) page
2. Select a rombel, subject, and topic
3. If the topic has `ranah_list` set, you should see multiple "Nilai" columns
4. Each student row should have input fields for each ranah
5. Go to [Rekap Topik](grades_topic_recap.php) to see multi-ranah columns

## UI Behavior

### Input Page (grades_daily.php)
- **Single Ranah Topic:** Shows 1 input field per student
- **Multi-Ranah Topic:** Shows N input fields per student (one per ranah)
- **Badges:** Display all ranah labels in the card header
- **Override:** Single checkbox applies to all ranah in that row

### Recap Page (grades_topic_recap.php)
- **Single Ranah Topic:** 1 column per topic
- **Multi-Ranah Topic:** N columns per topic (one per ranah)
- **CSV Export:** Automatically expands to multiple columns

## Backward Compatibility
The system is fully backward compatible:
- Old `ranah` column remains in the database
- `extract_ranah_from_topic()` function checks `ranah_list` first, then falls back to `ranah`
- Topics without `ranah_list` set use their original `ranah` value
- Existing grades are preserved in separate columns (nilai_sikap, nilai_pengetahuan, nilai_keterampilan)

## Data Migration (If Needed)
If you have existing grades and need to migrate to `ranah_list`:
```sql
-- Gradual: Update only topics that should have multiple ranah
UPDATE subject_topics 
SET ranah_list = JSON_ARRAY('sikap', 'pengetahuan', 'keterampilan')
WHERE id IN (SELECT topic_id FROM grades_daily WHERE ...);
```

## Example Scenarios

### Scenario 1: Math Topic (All Three Ranah)
```php
// Database
$topic = [
    'id' => 123,
    'judul' => 'Perkalian & Pembagian',
    'ranah' => 'pengetahuan', // kept for BC
    'ranah_list' => '["sikap", "pengetahuan", "keterampilan"]'
];

// Result in grades_daily.php:
// Shows 3 input fields per student:
// - Nilai Sikap (0–100)
// - Nilai Pengetahuan (0–100)
// - Nilai Keterampilan (0–100)
```

### Scenario 2: Reading Comprehension (Single Ranah - Backward Compat)
```php
// Database (old format, no migration needed)
$topic = [
    'id' => 124,
    'judul' => 'Membaca Teks Panjang',
    'ranah' => 'pengetahuan',
    'ranah_list' => NULL
];

// Result in grades_daily.php:
// Shows 1 input field per student:
// - Nilai Pengetahuan (0–100)
```

## Weighted Calculation
For multi-ranah topics, the topic's weight is distributed equally across all ranah:

```
Topic Weight = 2.0
Number of Ranah = 3

Weight per Ranah = 2.0 / 3 = 0.667

Final Weighted Average:
Sikap = (value1 * 0.667 + value2 * 0.667 + ...) / (0.667 + 0.667 + ...)
```

## Important Files
- [sql/migration_multi_ranah.sql](../sql/migration_multi_ranah.sql) - Database migration
- [includes/grading_helpers.php](../includes/grading_helpers.php) - Core helper functions
- [public/grades_daily.php](../public/grades_daily.php) - Grade input form
- [public/grades_topic_recap.php](../public/grades_topic_recap.php) - Grade recap/recap
- [public/admin/subject_topics.php](../public/admin/subject_topics.php) - **TODO: Update to support multi-ranah UI**

## Next Steps (Recommended)
1. ✅ Apply database migration
2. ✅ Update existing topics to use `ranah_list` 
3. ⚠️ **Update admin/subject_topics.php** to allow selecting multiple ranah per topic
4. Test the grading workflow end-to-end
5. Verify weighted calculations are correct
