CREATE TABLE IF NOT EXISTS user_order_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dish_id INT NOT NULL,
  ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  time_of_day ENUM('morning','afternoon','evening','night') NOT NULL,
  weather_condition VARCHAR(50),
  INDEX idx_user_time (user_id, time_of_day)
);
