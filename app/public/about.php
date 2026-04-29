<?php
session_start([
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
</head>
<body>
  <?php include __DIR__ . '/../templates/layout/nav.php'; ?>

  <!-- PAGE HEADER -->
  <section class="page-header" style="background: linear-gradient(135deg, rgba(20,20,20,0.8), rgba(10,10,10,0.9)), url('/assets/images/placeholder-dish.jpg') center/cover;">
    <div class="container text-center">
      <span class="badge badge-amber animate-fade-up">Our Story</span>
      <h1 class="animate-fade-up" style="animation-delay: 0.1s">Culinary Excellence<br>Since 2010</h1>
    </div>
  </section>

  <!-- CONTENT -->
  <section class="section-gap">
    <div class="container" style="max-width: 800px; margin: 0 auto; line-height: 1.8;">
      <h2 class="reveal">A Passion for Flavor</h2>
      <p class="reveal">
        Feastly began with a simple idea: that exceptional food shouldn't be confined to fine-dining restaurants. 
        We wanted to bring gourmet experiences directly to your dining table at home. What started as a small cloud 
        kitchen has now blossomed into the city's premier food delivery platform.
      </p>
      
      <div class="divider--amber reveal" style="margin: var(--space-xl) 0;"></div>
      
      <h3 class="reveal">Our Ingredients</h3>
      <p class="reveal">
        We believe that the best dishes start with the best ingredients. That's why we partner exclusively with 
        local farmers and trusted suppliers to source fresh, organic, and sustainable produce daily. Our meats are 
        ethically raised, and our spices are hand-ground in-house to ensure maximum flavor and aroma.
      </p>

      <div class="divider--amber reveal" style="margin: var(--space-xl) 0;"></div>

      <h3 class="reveal">The Team</h3>
      <p class="reveal">
        Behind every Feastly meal is a team of passionate chefs, culinary experts, and delivery partners who work 
        tirelessly to ensure your food is prepared perfectly and delivered hot. Our head chef brings over two 
        decades of international experience, blending traditional recipes with modern cooking techniques.
      </p>
    </div>
  </section>

  <!-- CART DRAWER -->
  <?php include __DIR__ . '/../templates/partials/cart-drawer.php'; ?>
  <div class="toast-container" id="toastContainer"></div>

  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/cart.js"></script>
</body>
</html>
