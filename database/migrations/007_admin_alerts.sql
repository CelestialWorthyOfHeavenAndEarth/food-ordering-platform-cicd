CREATE TABLE IF NOT EXISTS admin_alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alert_type ENUM('low_rating','inactivity','anomaly') NOT NULL,
  restaurant_id INT,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
