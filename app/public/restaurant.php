<?php
session_start(['cookie_secure'=>false,'cookie_httponly'=>true,'cookie_samesite'=>'Strict','use_strict_mode'=>true]);
if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once __DIR__.'/../src/config/Database.php';
$db = Database::getConnection();

$rid = (int)($_GET['id'] ?? 0);
if (!$rid) { header('Location: /index.php'); exit; }

$restaurant = $db->prepare("SELECT * FROM restaurants WHERE id = ? AND is_active = 1");
$restaurant->execute([$rid]);
$restaurant = $restaurant->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) { header('Location: /index.php'); exit; }

$menu = $db->prepare("
    SELECT m.*, c.name AS category_name FROM menu_items m
    JOIN categories c ON c.id = m.category_id
    WHERE m.restaurant_id = ? AND m.is_available = 1
    ORDER BY c.sort_order, m.price
");
$menu->execute([$rid]);
$dishes = $menu->fetchAll(PDO::FETCH_ASSOC);
$byCategory = [];
foreach ($dishes as $d) $byCategory[$d['category_name']][] = $d;

$user_logged = isset($_SESSION['user_id']);
$is_admin    = ($user_logged && ($_SESSION['role'] ?? '') === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
  <title><?= htmlspecialchars($restaurant['name']) ?> — Feastly</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/components.css">
  <link rel="stylesheet" href="/assets/css/animations.css">
  <style>
    .rest-hero { background: linear-gradient(135deg,#1a1a2e,#16213e); padding: 3rem 0 2rem; border-bottom: 1px solid var(--border); }
    .rest-hero__inner { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1.5rem; }
    .rest-hero__title { font-size:2.2rem; font-weight:800; margin:0 0 .5rem; }
    .rest-hero__meta { display:flex; gap:1rem; flex-wrap:wrap; color:var(--text-muted); font-size:.9rem; align-items:center; margin-top:.5rem; }
    .rest-hero__badge { background:var(--accent-amber); color:#000; padding:3px 12px; border-radius:20px; font-weight:700; font-size:.85rem; }
    .eco-pill { background:linear-gradient(135deg,#22c55e,#16a34a); color:#fff; padding:3px 12px; border-radius:20px; font-size:.8rem; font-weight:600; }

    .menu-section { margin-bottom:2.5rem; }
    .menu-section h3 { font-size:1.2rem; color:var(--accent-amber); border-bottom:1px solid var(--border); padding-bottom:.5rem; margin-bottom:1rem; }
    .dish-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:1rem; }
    .dish-card { background:var(--surface-light); border-radius:12px; padding:1.25rem; border:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; gap:1rem; transition:box-shadow .2s; }
    .dish-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.4); }
    .dish-card__info { flex:1; }
    .dish-card__name { font-weight:600; margin-bottom:.25rem; }
    .dish-card__desc { font-size:.8rem; color:var(--text-muted); margin-bottom:.5rem; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .dish-card__price { color:var(--accent-amber); font-weight:700; font-size:1rem; }
    .dish-card__veg { font-size:.75rem; }
    .add-btn { background:var(--accent-coral); color:#fff; border:none; border-radius:8px; padding:.5rem 1rem; cursor:pointer; font-weight:600; white-space:nowrap; transition:opacity .2s; }
    .add-btn:hover { opacity:.85; }

    /* Reviews */
    .review-section { background:var(--surface-light); border-radius:16px; padding:2rem; border:1px solid var(--border); margin-top:2rem; }
    .review-section h3 { margin-bottom:1.5rem; }
    .star-picker { display:flex; gap:.5rem; font-size:2rem; cursor:pointer; margin-bottom:1rem; }
    .star-picker span { color:#555; transition:color .15s; }
    .star-picker span.active { color:var(--accent-amber); }
    .review-card { background:var(--surface); border-radius:10px; padding:1rem; margin-bottom:.75rem; border:1px solid var(--border); }
    .review-card__header { display:flex; justify-content:space-between; margin-bottom:.4rem; font-size:.85rem; }
    .review-card__name { font-weight:600; color:var(--accent-amber); }
    .review-card__stars { color:var(--accent-amber); }
    .review-card__date { color:var(--text-muted); font-size:.75rem; }
    .review-card__comment { color:var(--text-muted); font-size:.9rem; }
    .no-reviews { color:var(--text-muted); text-align:center; padding:2rem 0; }
  </style>
</head>
<body>
<?php include __DIR__.'/../templates/layout/nav.php'; ?>

<!-- Restaurant Hero -->
<div class="rest-hero">
  <div class="container">
    <div class="rest-hero__inner">
      <div>
        <h1 class="rest-hero__title"><?= htmlspecialchars($restaurant['name']) ?></h1>
        <div class="rest-hero__meta">
          <span class="rest-hero__badge">★ <?= number_format($restaurant['avg_rating'],1) ?></span>
          <span>⏱ <?= $restaurant['avg_delivery_time'] ?> mins delivery</span>
          <span><?= count($dishes) ?> items</span>
          <?php if ($restaurant['is_eco_friendly']): ?>
            <span class="eco-pill">🌿 Eco Score <?= $restaurant['eco_score'] ?>/100</span>
          <?php endif; ?>
        </div>
      </div>
      <a href="/index.php#restaurants" class="btn btn-secondary">← All Restaurants</a>
    </div>
  </div>
</div>

<!-- Menu -->
<section class="section-gap">
  <div class="container">
    <?php if (empty($dishes)): ?>
      <p style="color:var(--text-muted); text-align:center; padding:3rem 0;">No dishes available for this restaurant yet.</p>
    <?php else: ?>
      <?php foreach ($byCategory as $catName => $catDishes): ?>
      <div class="menu-section reveal">
        <h3><?= htmlspecialchars($catName) ?></h3>
        <div class="dish-grid">
          <?php foreach ($catDishes as $d): ?>
          <div class="dish-card">
            <div class="dish-card__info">
              <div class="dish-card__name">
                <span class="dish-card__veg"><?= $d['is_veg'] ? '🟢' : '🔴' ?></span>
                <?= htmlspecialchars($d['name']) ?>
              </div>
              <?php if ($d['description']): ?>
              <p class="dish-card__desc"><?= htmlspecialchars($d['description']) ?></p>
              <?php endif; ?>
              <div class="dish-card__price">₹<?= number_format($d['price'],0) ?></div>
            </div>
            <?php if (!$is_admin): ?>
            <button class="add-btn" onclick="Cart.addItem(<?= $d['id'] ?>, '<?= addslashes($d['name']) ?>', <?= $d['price'] ?>)">+ Add</button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>

    <!-- Reviews Section -->
    <div class="review-section reveal">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <h3>⭐ Customer Reviews <span id="review-count" style="color:var(--text-muted); font-weight:400; font-size:.9rem;"></span></h3>
        <div id="avg-display" style="font-size:1.5rem; font-weight:700; color:var(--accent-amber);"></div>
      </div>

      <?php if ($user_logged && !$is_admin): ?>
      <div id="review-form" style="background:var(--surface); border-radius:12px; padding:1.25rem; margin:1rem 0; border:1px solid var(--border);">
        <p style="margin-bottom:.75rem; font-weight:600;">Leave a Review</p>
        <div class="star-picker" id="star-picker">
          <span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
        </div>
        <textarea id="review-comment" class="form-input" rows="3" placeholder="Share your experience..." style="width:100%; margin-bottom:.75rem;"></textarea>
        <button class="btn btn-primary btn--sm" id="submit-review">Submit Review</button>
        <span id="review-msg" style="margin-left:1rem; font-size:.85rem;"></span>
      </div>
      <?php elseif (!$user_logged): ?>
      <p style="color:var(--text-muted); margin:1rem 0;"><a href="/login.php" style="color:var(--accent-amber);">Sign in</a> to leave a review.</p>
      <?php endif; ?>

      <div id="reviews-list"><p class="no-reviews">Loading reviews…</p></div>
    </div>
  </div>
</section>

<?php if (!$is_admin): ?>
<?php include __DIR__.'/../templates/partials/cart-drawer.php'; ?>
<?php endif; ?>
<div class="toast-container" id="toastContainer"></div>
<script src="/assets/js/main.js"></script>
<?php if (!$is_admin): ?><script src="/assets/js/cart.js"></script><?php endif; ?>

<script>
const RESTAURANT_ID = <?= $rid ?>;
const IS_LOGGED = <?= $user_logged ? 'true' : 'false' ?>;
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let selectedRating = 0;

// Load reviews
async function loadReviews() {
  const res = await fetch(`/api/review.php?restaurant_id=${RESTAURANT_ID}`);
  const data = await res.json();
  const list = document.getElementById('reviews-list');
  document.getElementById('review-count').textContent = data.count ? `(${data.count})` : '';
  document.getElementById('avg-display').textContent = data.avg_rating ? `★ ${data.avg_rating}` : '';

  if (!data.reviews || !data.reviews.length) {
    list.innerHTML = '<p class="no-reviews">No reviews yet. Be the first!</p>';
    return;
  }
  list.innerHTML = data.reviews.map(r => `
    <div class="review-card">
      <div class="review-card__header">
        <span class="review-card__name">${r.user_name}</span>
        <div>
          <span class="review-card__stars">${'★'.repeat(r.rating)}${'☆'.repeat(5-r.rating)}</span>
          <span class="review-card__date"> · ${r.created_at.slice(0,10)}</span>
        </div>
      </div>
      ${r.comment ? `<p class="review-card__comment">${r.comment}</p>` : ''}
    </div>
  `).join('');
}
loadReviews();

// Star picker
document.querySelectorAll('#star-picker span').forEach(star => {
  star.addEventListener('mouseover', () => {
    const v = +star.dataset.v;
    document.querySelectorAll('#star-picker span').forEach(s => s.classList.toggle('active', +s.dataset.v <= v));
  });
  star.addEventListener('click', () => {
    selectedRating = +star.dataset.v;
    document.querySelectorAll('#star-picker span').forEach(s => s.classList.toggle('active', +s.dataset.v <= selectedRating));
  });
});
document.getElementById('star-picker')?.addEventListener('mouseleave', () => {
  document.querySelectorAll('#star-picker span').forEach(s => s.classList.toggle('active', +s.dataset.v <= selectedRating));
});

// Submit review
document.getElementById('submit-review')?.addEventListener('click', async () => {
  if (!selectedRating) { document.getElementById('review-msg').textContent = 'Please select a star rating.'; return; }
  const comment = document.getElementById('review-comment').value;
  const btn = document.getElementById('submit-review');
  btn.disabled = true; btn.textContent = 'Submitting…';
  const res = await fetch('/api/review.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    body: JSON.stringify({ restaurant_id: RESTAURANT_ID, rating: selectedRating, comment })
  });
  const data = await res.json();
  if (data.success) {
    document.getElementById('review-msg').textContent = '✅ Review submitted!';
    document.getElementById('review-comment').value = '';
    selectedRating = 0;
    document.querySelectorAll('#star-picker span').forEach(s => s.classList.remove('active'));
    loadReviews();
  } else {
    document.getElementById('review-msg').textContent = data.error || 'Failed';
  }
  btn.disabled = false; btn.textContent = 'Submit Review';
});
</script>
</body>
</html>
