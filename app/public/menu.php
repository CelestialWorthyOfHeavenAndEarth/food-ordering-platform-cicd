<?php
session_start([
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);

require_once __DIR__ . '/../src/controllers/MenuController.php';

$menuController = new MenuController();
$menuData = $menuController->getFullMenu();
$categories = $menuData['categories'];
$groupedItems = $menuData['groupedItems'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <div class="container text-center">
      <h1 class="animate-fade-up">Explore Our Menu</h1>
      <p class="animate-fade-up" style="animation-delay: 0.1s">Freshly prepared, carefully crafted.</p>
    </div>
  </section>

  <!-- MENU SECTION -->
  <section class="section-gap">
    <div class="container">
      
      <!-- Category Nav -->
      <div class="category-nav reveal">
        <?php foreach ($categories as $index => $cat): ?>
          <a href="#cat-<?= $cat['id'] ?>" class="btn <?= $index === 0 ? 'btn-primary' : 'btn-secondary' ?>">
            <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Menu Grids -->
      <?php foreach ($groupedItems as $catName => $items): ?>
        <?php if (empty($items)) continue; ?>
        
        <?php 
          // Find cat id for anchor
          $catId = '';
          foreach($categories as $c) { if($c['name'] === $catName) $catId = $c['id']; }
        ?>
        
        <div class="menu-category" id="cat-<?= $catId ?>">
          <h2 class="menu-category__title reveal">
            <?= htmlspecialchars($catName) ?>
            <div class="divider--amber"></div>
          </h2>
          <div class="menu-grid stagger">
            <?php foreach ($items as $item): ?>
              <?php 
                // Ensure placeholder image
                if (empty($item['image_url'])) {
                    $item['image_url'] = '/assets/images/placeholder-dish.jpg';
                }
                // Add default rating for UI
                if (empty($item['rating'])) $item['rating'] = 4.5;
                $item['category'] = $catName;
                include __DIR__ . '/../templates/partials/menu-card.php'; 
              ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      
    </div>
  </section>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>

  <!-- TOAST CONTAINER -->
  <div class="toast-container" id="toastContainer"></div>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
</body>
</html>
