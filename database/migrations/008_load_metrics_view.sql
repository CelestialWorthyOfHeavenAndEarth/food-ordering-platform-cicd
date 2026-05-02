CREATE OR REPLACE VIEW restaurant_load_metrics AS
SELECT
  r.id AS restaurant_id,
  r.name,
  r.avg_delivery_time,
  COUNT(o.id) AS orders_last_hour,
  AVG(COUNT(o.id)) OVER (PARTITION BY r.id) AS avg_hourly_orders
FROM restaurants r
LEFT JOIN orders o ON o.restaurant_id = r.id
  AND o.created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY r.id, r.name, r.avg_delivery_time;
