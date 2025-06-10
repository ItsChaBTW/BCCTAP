<?php
/**
 * GeofenceHelper - Utility class for geofencing operations
 */

class GeofenceHelper 
{
    /**
     * Calculate distance between two coordinates using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) 
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Check if a point is within a geofence
     * @param float $userLat User's latitude
     * @param float $userLon User's longitude
     * @param float $eventLat Event location latitude
     * @param float $eventLon Event location longitude
     * @param int $radius Geofence radius in meters
     * @return array Result with status and distance
     */
    public static function isWithinGeofence($userLat, $userLon, $eventLat, $eventLon, $radius) 
    {
        if (empty($eventLat) || empty($eventLon)) {
            // If event has no coordinates, allow attendance (backward compatibility)
            return [
                'within_fence' => true,
                'distance' => 0,
                'message' => 'No geofence set for this event'
            ];
        }
        
        $distance = self::calculateDistance($userLat, $userLon, $eventLat, $eventLon);
        $withinFence = $distance <= $radius;
        
        return [
            'within_fence' => $withinFence,
            'distance' => round($distance, 2),
            'allowed_radius' => $radius,
            'message' => $withinFence ? 
                'You are within the event location' : 
                'You are ' . round($distance - $radius, 2) . 'm outside the allowed area'
        ];
    }
    
    /**
     * Validate coordinates
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return bool True if coordinates are valid
     */
    public static function validateCoordinates($lat, $lon) 
    {
        return ($lat >= -90 && $lat <= 90) && ($lon >= -180 && $lon <= 180);
    }
    
    /**
     * Get address from coordinates using reverse geocoding (placeholder)
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return string Address or coordinate string
     */
    public static function getAddressFromCoordinates($lat, $lon) 
    {
        // This is a placeholder - you can implement actual reverse geocoding API
        // For now, return formatted coordinates
        return "Lat: " . number_format($lat, 6) . ", Lon: " . number_format($lon, 6);
    }
    
    /**
     * Format distance for display
     * @param float $distance Distance in meters
     * @return string Formatted distance string
     */
    public static function formatDistance($distance) 
    {
        if ($distance < 1000) {
            return round($distance, 1) . 'm';
        } else {
            return round($distance / 1000, 2) . 'km';
        }
    }
    
    /**
     * Get coordinates from address using geocoding (placeholder)
     * @param string $address Address to geocode
     * @return array|false Coordinates array or false on failure
     */
    public static function getCoordinatesFromAddress($address) 
    {
        // This is a placeholder for geocoding API integration
        // You can implement Google Maps API, OpenStreetMap Nominatim, etc.
        return false;
    }
}
?> 