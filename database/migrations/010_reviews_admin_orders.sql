-- Restaurant reviews
CREATE TABLE IF NOT EXISTS restaurant_reviews (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT NOT NULL,
  restaurant_id INT NOT NULL,
  rating        TINYINT NOT NULL,
  comment       TEXT,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY one_review_per_user (user_id, restaurant_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Extend order status enum to include cooking
ALTER TABLE orders MODIFY COLUMN status 
  ENUM('pending','confirmed','preparing','cooking','out_for_delivery','delivered','cancelled') DEFAULT 'pending';


