CREATE TABLE IF NOT EXISTS restaurants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) DEFAULT 1
);

INSERT INTO restaurants (name) VALUES ('Feastly Main Kitchen');

ALTER TABLE menu_items ADD COLUMN restaurant_id INT DEFAULT 1;
ALTER TABLE orders ADD COLUMN restaurant_id INT DEFAULT 1;
