-- Migration: add conversations, add columns to messages and users

ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS conversations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  user_one INT NOT NULL,
  user_two INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv (ad_id, user_one, user_two),
  FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
  FOREIGN KEY (user_one) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (user_two) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE messages
  ADD COLUMN conversation_id INT NULL AFTER ad_id,
  ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER content,
  ADD COLUMN deleted_at DATETIME NULL AFTER is_read,
  ADD FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE;

-- Note: run this migration after creating the initial schema. If foreign key creation fails due to existing data, run without the FK and add later.
