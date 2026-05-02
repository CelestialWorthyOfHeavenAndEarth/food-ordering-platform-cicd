CREATE TABLE IF NOT EXISTS meal_combos (
  combo_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  items JSON NOT NULL,
  base_price DECIMAL(8,2) NOT NULL,
  discount_percentage DECIMAL(4,2) DEFAULT 0,
  final_price DECIMAL(8,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
