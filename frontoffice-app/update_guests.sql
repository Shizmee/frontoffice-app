-- Add meal plan and nationality columns to guests table
ALTER TABLE guests 
ADD COLUMN IF NOT EXISTS meal_plan ENUM('BB', 'HB', 'FB', 'AI') DEFAULT 'BB' AFTER guest_name,
ADD COLUMN IF NOT EXISTS nationality VARCHAR(2) DEFAULT 'OTH' AFTER meal_plan;

-- Update existing records with default values
UPDATE guests 
SET meal_plan = 'BB' 
WHERE meal_plan IS NULL;

UPDATE guests 
SET nationality = 'OTH' 
WHERE nationality IS NULL;

-- Add indexes for better performance
ALTER TABLE guests
ADD INDEX idx_meal_plan (meal_plan),
ADD INDEX idx_nationality (nationality),
ADD INDEX idx_check_dates (check_in, check_out);