<?php
require_once __DIR__ . '/../models/MenuItem.php';

class MenuController {
    public function getFullMenu(): array {
        $categories = MenuItem::getCategories();
        $items = MenuItem::getAll();

        $groupedItems = [];
        foreach ($categories as $cat) {
            $groupedItems[$cat['name']] = [];
        }
        foreach ($items as $item) {
            $catName = $item['category_name'] ?? 'Uncategorized';
            if (!isset($groupedItems[$catName])) {
                $groupedItems[$catName] = [];
            }
            $groupedItems[$catName][] = $item;
        }

        return [
            'categories' => $categories,
            'groupedItems' => $groupedItems
        ];
    }

    public function getFeatured(int $limit = 6): array {
        return MenuItem::getFeatured($limit);
    }
}
