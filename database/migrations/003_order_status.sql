CREATE TABLE IF NOT EXISTS order_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  status ENUM('placed','confirmed','preparing','cooking','out_for_delivery','delivered') DEFAULT 'placed',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order (order_id)
);
