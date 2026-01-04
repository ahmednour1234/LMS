-- Fix course_trainer table structure
-- Run this SQL directly on your MySQL database if the migration fails

-- Step 1: Drop foreign keys (adjust constraint names if needed)
SET @fk_course = (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'course_trainer' 
                  AND COLUMN_NAME = 'course_id' 
                  AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);
SET @fk_user = (SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'course_trainer' 
                AND COLUMN_NAME = 'user_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1);

SET @sql1 = IF(@fk_course IS NOT NULL, CONCAT('ALTER TABLE course_trainer DROP FOREIGN KEY ', @fk_course), 'SELECT 1');
SET @sql2 = IF(@fk_user IS NOT NULL, CONCAT('ALTER TABLE course_trainer DROP FOREIGN KEY ', @fk_user), 'SELECT 1');

PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Step 2: Drop primary key
ALTER TABLE course_trainer DROP PRIMARY KEY;

-- Step 3: Drop user_id column if it exists
ALTER TABLE course_trainer DROP COLUMN IF EXISTS user_id;

-- Step 4: Add trainer_id column if it doesn't exist
ALTER TABLE course_trainer 
ADD COLUMN IF NOT EXISTS trainer_id BIGINT UNSIGNED NOT NULL AFTER course_id;

-- Step 5: Add foreign keys
ALTER TABLE course_trainer 
ADD CONSTRAINT course_trainer_course_id_foreign 
FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE;

ALTER TABLE course_trainer 
ADD CONSTRAINT course_trainer_trainer_id_foreign 
FOREIGN KEY (trainer_id) REFERENCES users(id) ON DELETE CASCADE;

-- Step 6: Add unique constraint
ALTER TABLE course_trainer 
ADD UNIQUE KEY course_trainer_course_id_trainer_id_unique (course_id, trainer_id);

-- Step 7: Add timestamps if they don't exist
ALTER TABLE course_trainer 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL;

