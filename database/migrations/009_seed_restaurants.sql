INSERT IGNORE INTO restaurants (id, name, latitude, longitude, avg_delivery_time, avg_rating, is_eco_friendly, eco_score, is_active) VALUES
(1,  'Feastly Main Kitchen',    13.0827, 80.2707, 25, 4.5, 1, 85, 1),
(2,  'Biryani Palace',          13.0850, 80.2750, 30, 4.3, 0, 40, 1),
(3,  'Spice Garden',            13.0800, 80.2660, 20, 4.7, 1, 90, 1),
(4,  'Andhra Bhojnam',          13.0900, 80.2800, 35, 4.1, 0, 30, 1),
(5,  'Green Leaf Veg',          13.0780, 80.2690, 22, 4.6, 1, 95, 1),
(6,  'Street Bites Corner',     13.0840, 80.2730, 15, 4.0, 0, 25, 1);

UPDATE menu_items SET restaurant_id = 1 WHERE id IN (1, 2, 5, 9);
UPDATE menu_items SET restaurant_id = 2 WHERE id IN (3, 6, 7);
UPDATE menu_items SET restaurant_id = 3 WHERE id IN (4, 8, 10);
UPDATE menu_items SET restaurant_id = 4 WHERE id IN (11, 12);
UPDATE menu_items SET restaurant_id = 5 WHERE id IN (13, 14);
UPDATE menu_items SET restaurant_id = 6 WHERE id IN (15);

INSERT IGNORE INTO pricing_config (config_key, config_value) VALUES
('gst_rate',           5.00),
('platform_fee',       5.00),
('delivery_fee_base',  30.00),
('delivery_fee_per_km',5.00),
('free_delivery_above',500.00);

INSERT INTO admin_alerts (alert_type, message, is_read) VALUES
('inactivity', 'System initialized. Welcome to Feastly Admin!', 0);
