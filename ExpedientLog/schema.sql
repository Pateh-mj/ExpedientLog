-- FILE: schema.sql (Revised for PostgreSQL/Supabase)

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE users (
  id SERIAL PRIMARY KEY, -- Changed from INT(11) NOT NULL AUTO_INCREMENT
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'employee',
  department VARCHAR(100) DEFAULT 'General',
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW() -- Changed from TIMESTAMP...current_timestamp()
);

-- --------------------------------------------------------
-- Table structure for table `tickets`
-- --------------------------------------------------------
CREATE TABLE tickets (
  id SERIAL PRIMARY KEY, -- Changed from INT(11) NOT NULL AUTO_INCREMENT
  user_id INT NOT NULL,
  task TEXT NOT NULL,
  created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(), -- Changed from TIMESTAMP...current_timestamp()
  is_knowledge BOOLEAN DEFAULT FALSE, -- Changed tinyint(4) to BOOLEAN
  category VARCHAR(100) DEFAULT 'General',
  project VARCHAR(100) DEFAULT NULL,
  
  -- Add foreign key constraint
  CONSTRAINT fk_user
    FOREIGN KEY(user_id) 
    REFERENCES users(id)
    ON DELETE CASCADE
);

-- --------------------------------------------------------
-- Indexes
-- --------------------------------------------------------
CREATE INDEX idx_tickets_user_id ON tickets (user_id);
CREATE INDEX idx_tickets_created_at ON tickets (created_at);

-- --------------------------------------------------------
-- Initial Data (Use if you need to quickly set up a test admin)
-- --------------------------------------------------------
-- NOTE: The password hash will be the same as the original MySQL dump.
INSERT INTO users (id, username, password, role, department, created_at) VALUES
(1, 'Administrator', '$2y$10$E9MiXONOCb7tFqyyMBT.buBUdCicSZBnOCyd0BwNPYwhQnRfYHqMe', 'supervisor', 'General', '2025-11-10 14:48:27');
-- IMPORTANT: If you are using Supabase's generated roles, you may need to
-- manually set the `id` sequence after running this INSERT:
-- SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));