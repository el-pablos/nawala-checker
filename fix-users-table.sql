-- ============================================================================
-- ADD is_admin COLUMN TO users TABLE
-- ============================================================================
-- This script adds the missing is_admin column to the users table
-- ============================================================================

BEGIN;

-- Add is_admin column to users table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'is_admin'
    ) THEN
        ALTER TABLE users ADD COLUMN is_admin BOOLEAN NOT NULL DEFAULT FALSE;
        RAISE NOTICE 'Column is_admin added to users table';
    ELSE
        RAISE NOTICE 'Column is_admin already exists in users table';
    END IF;
END $$;

-- Create index on is_admin for faster queries
CREATE INDEX IF NOT EXISTS users_is_admin_index ON users(is_admin);

COMMIT;

-- Verify the column exists
SELECT 'is_admin column added successfully!' AS status,
       column_name,
       data_type,
       column_default,
       is_nullable
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name = 'users'
  AND column_name = 'is_admin';

