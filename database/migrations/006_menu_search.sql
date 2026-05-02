ALTER TABLE menu_items
  ADD COLUMN tags VARCHAR(255) DEFAULT '',
  ADD COLUMN packaging_type ENUM('standard','minimal','plastic-free') DEFAULT 'standard';
ALTER TABLE menu_items ADD FULLTEXT INDEX ft_search (name, description, tags);
