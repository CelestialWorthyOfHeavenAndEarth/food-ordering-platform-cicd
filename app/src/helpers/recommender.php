<?php
class DishRecommender {
  private PDO $db;

  public function __construct(PDO $db) {
    $this->db = $db;
  }

  private function getTimeOfDay(): string {
    $hour = (int)date('H');
    if ($hour >= 6  && $hour < 12) return 'morning';
    if ($hour >= 12 && $hour < 17) return 'afternoon';
    if ($hour >= 17 && $hour < 21) return 'evening';
    return 'night';
  }

  private function getWeatherCondition(): string {
    $apiKey = getenv('WEATHER_API_KEY') ?: ($_ENV['WEATHER_API_KEY'] ?? '');
    $city   = getenv('CITY_NAME') ?: ($_ENV['CITY_NAME'] ?? 'Chennai');
    if (!$apiKey) return 'unknown';

    $url  = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$apiKey}";
    // Add timeout to prevent hanging the request
    $context = stream_context_create(['http' => ['timeout' => 2]]);
    $data = @json_decode(@file_get_contents($url, false, $context), true);
    return $data['weather'][0]['main'] ?? 'unknown'; // Clear, Rain, Clouds, etc.
  }

  public function getRecommendations(int $userId, int $limit = 6): array {
    $timeSlot = $this->getTimeOfDay();
    $weather  = $this->getWeatherCondition();

    // Hot dishes preferred on cold/rainy days
    $weatherTag = in_array($weather, ['Rain', 'Drizzle', 'Snow']) ? 'hot' : 'cold';

    $sql = "
      SELECT
        m.id,
        m.name as dish_name,
        m.price,
        m.image_url,
        -- Signal 1: personal history score (40%)
        COALESCE(hist.personal_score, 0) * 0.40 +
        -- Signal 2: weather tag match (35%)
        (CASE WHEN m.tags LIKE :weatherTag THEN 1 ELSE 0 END) * 0.35 +
        -- Signal 3: platform popularity last 7 days (25%)
        COALESCE(pop.popularity_score, 0) * 0.25
        AS recommendation_score
      FROM menu_items m
      LEFT JOIN (
        SELECT dish_id,
          COUNT(*) / (SELECT MAX(c) FROM (SELECT COUNT(*) c FROM user_order_history
            WHERE user_id = :userId GROUP BY dish_id) t) AS personal_score
        FROM user_order_history
        WHERE user_id = :userId AND time_of_day = :timeSlot
        GROUP BY dish_id
      ) hist ON hist.dish_id = m.id
      LEFT JOIN (
        SELECT dish_id,
          COUNT(*) / (SELECT MAX(c) FROM (SELECT COUNT(*) c FROM user_order_history
            WHERE ordered_at >= NOW() - INTERVAL 7 DAY GROUP BY dish_id) t) AS popularity_score
        FROM user_order_history
        WHERE ordered_at >= NOW() - INTERVAL 7 DAY
        GROUP BY dish_id
      ) pop ON pop.dish_id = m.id
      WHERE m.is_available = 1
      ORDER BY recommendation_score DESC
      LIMIT :limit
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':userId',     $userId,           PDO::PARAM_INT);
    $stmt->bindValue(':timeSlot',   $timeSlot,         PDO::PARAM_STR);
    $stmt->bindValue(':weatherTag', "%{$weatherTag}%", PDO::PARAM_STR);
    $stmt->bindValue(':limit',      $limit,            PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
