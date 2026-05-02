-- Initial Data for Andhra Cuisine
INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Starters', 'starters', '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>', 1),
('Main Course', 'main-course', '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/></svg>', 2),
('Breads', 'breads', '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>', 3),
('Desserts', 'desserts', '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 22 1-1h3l9-9M3 21v-3l9-9M15 6l3.5-3.5a2.12 2.12 0 0 1 3 3L18 9l-3-3"/></svg>', 4);

INSERT INTO menu_items (category_id, name, description, price, is_popular, is_veg, image_url) VALUES
(1, 'Guntur Mirchi Bajji', 'Spicy, batter-fried Guntur chilies stuffed with tangy tamarind filling.', 120.00, TRUE, TRUE, '/assets/images/starter-dish.png'),
(1, 'Kodi Vepudu (Chicken Fry)', 'Traditional Andhra dry chicken fry tossed with aromatic spices and curry leaves.', 280.00, TRUE, FALSE, '/assets/images/kodi_vepudu.png'),
(1, 'Apollo Fish', 'Crispy boneless fish fillets tossed in a fiery, tangy yogurt sauce.', 320.00, FALSE, FALSE, '/assets/images/apollo_fish.png'),
(1, 'Punugulu', 'Deep-fried spiced batter fritters served with peanut and ginger chutney.', 90.00, FALSE, TRUE, '/assets/images/punugulu.png'),
(2, 'Hyderabadi Dum Biryani', 'Fragrant basmati rice layered with marinated tender chicken and slow-cooked over dum.', 350.00, TRUE, FALSE, '/assets/images/main-dish.png'),
(2, 'Gongura Mutton', 'Succulent mutton chunks slow-cooked with tangy Gongura (roselle) leaves.', 450.00, TRUE, FALSE, '/assets/images/gongura_mutton.png'),
(2, 'Natu Kodi Pulusu', 'Spicy country chicken curry simmered in a robust, traditional Andhra gravy.', 400.00, TRUE, FALSE, '/assets/images/natu_kodi.png'),
(2, 'Andhra Bhojanam (Meals)', 'Authentic thali featuring plain rice, pappu, sambar, rasam, poriyal, and pacchadi.', 250.00, FALSE, TRUE, '/assets/images/meals.png'),
(2, 'Gutti Vankaya Kura', 'Stuffed baby eggplants slow-cooked in a rich peanut and sesame seed gravy.', 200.00, FALSE, TRUE, '/assets/images/main-dish.png'),
(3, 'Tandoori Roti', 'Whole wheat flatbread baked in a traditional clay oven.', 30.00, FALSE, TRUE, '/assets/images/bread-dish.png'),
(3, 'Garlic Naan', 'Soft flatbread topped with minced garlic and cilantro, baked fresh.', 60.00, TRUE, TRUE, '/assets/images/naan.png'),
(3, 'Chapati', 'Soft, thin whole wheat flatbread, perfect with curries.', 25.00, FALSE, TRUE, '/assets/images/chapati.png'),
(4, 'Pootharekulu', 'Traditional delicate sweet made of rice paper layers stuffed with jaggery and ghee.', 150.00, TRUE, TRUE, '/assets/images/pootharekulu.png'),
(4, 'Qubani Ka Meetha', 'Classic Hyderabadi dessert made from dried apricots, served with cream.', 180.00, TRUE, TRUE, '/assets/images/qubani.png'),
(4, 'Double Ka Meetha', 'Rich bread pudding dessert fried in ghee and soaked in saffron-infused milk.', 140.00, FALSE, TRUE, '/assets/images/double_meetha.png');

-- Default password is 'password'
INSERT INTO users (name, email, password_hash, role) VALUES
('Admin User', 'admin@feastly.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
CREATE TABLE IF NOT EXISTS restaurants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  is_active TINYINT(1) DEFAULT 1
);

INSERT INTO restaurants (name) VALUES ('Feastly Main Kitchen');

ALTER TABLE menu_items ADD COLUMN restaurant_id INT DEFAULT 1;
ALTER TABLE orders ADD COLUMN restaurant_id INT DEFAULT 1;
CREATE TABLE IF NOT EXISTS user_order_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  dish_id INT NOT NULL,
  ordered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  time_of_day ENUM('morning','afternoon','evening','night') NOT NULL,
  weather_condition VARCHAR(50),
  INDEX idx_user_time (user_id, time_of_day)
);
ALTER TABLE restaurants
  ADD COLUMN latitude DECIMAL(10,8) DEFAULT 0,
  ADD COLUMN longitude DECIMAL(11,8) DEFAULT 0,
  ADD COLUMN avg_delivery_time INT DEFAULT 30,
  ADD COLUMN avg_rating DECIMAL(3,2) DEFAULT 4.0,
  ADD COLUMN is_eco_friendly TINYINT(1) DEFAULT 0,
  ADD COLUMN eco_score INT DEFAULT 0;
CREATE TABLE IF NOT EXISTS order_status (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  status ENUM('placed','confirmed','preparing','cooking','out_for_delivery','delivered') DEFAULT 'placed',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_order (order_id)
);
CREATE TABLE IF NOT EXISTS meal_combos (
  combo_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  items JSON NOT NULL,
  base_price DECIMAL(8,2) NOT NULL,
  discount_percentage DECIMAL(4,2) DEFAULT 0,
  final_price DECIMAL(8,2) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS pricing_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  config_key VARCHAR(50) UNIQUE NOT NULL,
  config_value DECIMAL(8,2) NOT NULL,
  description VARCHAR(255)
);
INSERT INTO pricing_config (config_key, config_value, description) VALUES
  ('gst_percent', 5.00, 'GST on food items'),
  ('platform_fee', 5.00, 'Fixed platform fee per order'),
  ('packing_charge', 10.00, 'Packing charge per restaurant'),
  ('base_delivery_fee', 30.00, 'Base delivery fee'),
  ('per_km_rate', 5.00, 'Additional fee per km beyond 3km');
ALTER TABLE menu_items
  ADD COLUMN tags VARCHAR(255) DEFAULT '',
  ADD COLUMN packaging_type ENUM('standard','minimal','plastic-free') DEFAULT 'standard';
ALTER TABLE menu_items ADD FULLTEXT INDEX ft_search (name, description, tags);
CREATE TABLE IF NOT EXISTS admin_alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alert_type ENUM('low_rating','inactivity','anomaly') NOT NULL,
  restaurant_id INT,
  message TEXT NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE OR REPLACE VIEW restaurant_load_metrics AS
SELECT
  r.id AS restaurant_id,
  r.name,
  r.avg_delivery_time,
  COUNT(o.id) AS orders_last_hour,
  AVG(COUNT(o.id)) OVER (PARTITION BY r.id) AS avg_hourly_orders
FROM restaurants r
LEFT JOIN orders o ON o.restaurant_id = r.id
  AND o.created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY r.id, r.name, r.avg_delivery_time;
