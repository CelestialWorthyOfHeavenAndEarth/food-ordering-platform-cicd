<?php
function parseSearchQuery(string $query): array {
  $params = [
    'keywords'  => '',
    'max_price' => null,
    'tags'      => [],
  ];

  if (preg_match('/(?:under|below|less than)\s*(\d+)/i', $query, $m)) {
    $params['max_price'] = (int)$m[1];
    $query = preg_replace('/(?:under|below|less than)\s*\d+/i', '', $query);
  }

  $knownTags = ['spicy','vegan','vegetarian','non-veg','hot','cold','sweet',
                'gluten-free','jain','healthy','fried','grilled'];
  foreach ($knownTags as $tag) {
    if (stripos($query, $tag) !== false) {
      $params['tags'][] = $tag;
      $query = str_ireplace($tag, '', $query);
    }
  }

  $params['keywords'] = trim(preg_replace('/\s+/', ' ', $query));
  return $params;
}
