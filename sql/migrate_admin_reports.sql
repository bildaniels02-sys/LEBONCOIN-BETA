-- Migration: add deleted_at to ads - add reports table for signalements

ALTER TABLE ads
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at;

CREATE TABLE IF NOT EXISTS reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad_id INT NOT NULL,
  reporter_id INT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  comment TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('open','dismissed','resolved') DEFAULT 'open',
  FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
  FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Note: If foreign key creation fails due to existing constraints, run the ALTER/CREATE statements individually.
