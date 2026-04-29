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
