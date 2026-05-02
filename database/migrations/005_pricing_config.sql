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
