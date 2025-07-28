/**
 * Utilitaires pour les calculs géographiques
 */

/**
 * Calcule la distance entre deux points GPS (formule de Haversine)
 * @param {number} lat1 - Latitude du premier point
 * @param {number} lon1 - Longitude du premier point
 * @param {number} lat2 - Latitude du deuxième point
 * @param {number} lon2 - Longitude du deuxième point
 * @returns {number} Distance en mètres
 */
export function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371e3; // Rayon de la Terre en mètres
  const φ1 = lat1 * Math.PI / 180;
  const φ2 = lat2 * Math.PI / 180;
  const Δφ = (lat2 - lat1) * Math.PI / 180;
  const Δλ = (lon2 - lon1) * Math.PI / 180;

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
    Math.cos(φ1) * Math.cos(φ2) *
    Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
}

/**
 * Calcule le centre géographique (centroïde) d'un ensemble de points
 * @param {Array} positions - Array d'objets avec lat et lng
 * @returns {Object} {lat, lng} du centre
 */
export function calculateGeographicCenter(positions) {
  if (!positions || positions.length === 0) {
    return null;
  }

  const validPositions = positions.filter(pos => 
    pos.latitude && pos.longitude && 
    !isNaN(pos.latitude) && !isNaN(pos.longitude)
  );

  if (validPositions.length === 0) {
    return null;
  }

  const sumLat = validPositions.reduce((sum, pos) => sum + parseFloat(pos.latitude), 0);
  const sumLng = validPositions.reduce((sum, pos) => sum + parseFloat(pos.longitude), 0);

  return {
    lat: sumLat / validPositions.length,
    lng: sumLng / validPositions.length
  };
}

/**
 * Vérifie la cohérence géographique d'un ensemble de positions
 * @param {Array} positions - Array d'objets avec lat, lng et attendee info
 * @param {number} maxDistance - Distance maximale autorisée en mètres (défaut: 100)
 * @returns {Object} Résultats de l'analyse
 */
export function analyzeGeographicConsistency(positions, maxDistance = 100) {
  const validPositions = positions.filter(pos => 
    pos.latitude && pos.longitude && 
    !isNaN(pos.latitude) && !isNaN(pos.longitude)
  );

  if (validPositions.length === 0) {
    return {
      center: null,
      outliers: [],
      consistency: 100,
      totalPositions: 0,
      validPositions: 0
    };
  }

  const center = calculateGeographicCenter(validPositions);
  const outliers = [];
  let totalDistance = 0;

  validPositions.forEach(pos => {
    const distance = calculateDistance(
      center.lat, center.lng,
      parseFloat(pos.latitude), parseFloat(pos.longitude)
    );
    
    totalDistance += distance;
    
    if (distance > maxDistance) {
      outliers.push({
        attendee: pos,
        distance: Math.round(distance),
        centerDistance: distance
      });
    }
  });

  const averageDistance = totalDistance / validPositions.length;
  const consistency = Math.max(0, 100 - (outliers.length / validPositions.length) * 100);

  return {
    center,
    outliers,
    consistency: Math.round(consistency),
    averageDistance: Math.round(averageDistance),
    totalPositions: positions.length,
    validPositions: validPositions.length,
    maxDistance
  };
}

/**
 * Formate une distance pour l'affichage
 * @param {number} distance - Distance en mètres
 * @returns {string} Distance formatée
 */
export function formatDistance(distance) {
  if (distance < 1000) {
    return `${Math.round(distance)}m`;
  } else {
    return `${(distance / 1000).toFixed(1)}km`;
  }
}

/**
 * Détermine le niveau de cohérence géographique
 * @param {number} consistency - Pourcentage de cohérence (0-100)
 * @returns {Object} {level, color, label}
 */
export function getConsistencyLevel(consistency) {
  if (consistency >= 90) {
    return { level: 'excellent', color: 'green', label: 'Excellente' };
  } else if (consistency >= 75) {
    return { level: 'good', color: 'yellow', label: 'Bonne' };
  } else if (consistency >= 50) {
    return { level: 'fair', color: 'orange', label: 'Moyenne' };
  } else {
    return { level: 'poor', color: 'red', label: 'Faible' };
  }
} 