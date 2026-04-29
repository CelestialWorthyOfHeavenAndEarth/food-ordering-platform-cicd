<div class="menu-card card reveal animate-fade-up" data-id="<?= htmlspecialchars($item['id']) ?>">
  <div class="menu-card__img-wrap">
    <img
      src="<?= htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder-dish.jpg') ?>"
      alt="<?= htmlspecialchars($item['name']) ?>"
      class="menu-card__img"
      loading="lazy"
    >
    <div class="menu-card__overlay">
      <button
        class="btn btn-primary menu-card__quick-add"
        onclick="Cart.addItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)"
      >
        + Quick Add
      </button>
    </div>
    <?php if (!empty($item['is_popular'])): ?>
      <span class="menu-card__badge badge badge-coral">🔥 Popular</span>
    <?php elseif (!empty($item['is_new'])): ?>
      <span class="menu-card__badge badge badge-green">✨ New</span>
    <?php endif; ?>
  </div>
  <div class="menu-card__body">
    <div class="menu-card__top">
      <span class="menu-card__category"><?= htmlspecialchars($item['category'] ?? 'Main Course') ?></span>
      <span class="menu-card__rating" style="display:flex;align-items:center;gap:4px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="var(--accent-amber)" stroke="var(--accent-amber)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
        <?= number_format($item['rating'] ?? 4.5, 1) ?>
      </span>
    </div>
    <h3 class="menu-card__name"><?= htmlspecialchars($item['name']) ?></h3>
    <p class="menu-card__desc"><?= htmlspecialchars(substr($item['description'] ?? '', 0, 90)) ?>...</p>
    <div class="menu-card__footer">
      <div class="menu-card__price">
        ₹<?= number_format($item['price'], 2) ?>
        <?php if (!empty($item['original_price']) && $item['original_price'] > $item['price']): ?>
          <span class="menu-card__price-original">₹<?= number_format($item['original_price'], 2) ?></span>
        <?php endif; ?>
      </div>
      <div class="menu-card__qty" data-id="<?= $item['id'] ?>">
        <button class="qty-btn" onclick="Cart.decrease(<?= $item['id'] ?>)">−</button>
        <span class="qty-val" id="qty-<?= $item['id'] ?>">0</span>
        <button class="qty-btn qty-btn--add" onclick="Cart.addItem(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)">+</button>
      </div>
    </div>
  </div>
</div>
