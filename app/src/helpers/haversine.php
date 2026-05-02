<?php
function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float {
  $R = 6371; // Earth radius in km
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat/2) ** 2
     + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
  return $R * 2 * asin(sqrt($a));
}
