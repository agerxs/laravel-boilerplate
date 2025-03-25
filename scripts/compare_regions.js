import fs from 'fs';

// Charger les données
const secretaires = JSON.parse(fs.readFileSync('./resources/data/secretaires.json', 'utf8'));
const localite_rsu = JSON.parse(fs.readFileSync('./resources/data/localite_rsu.json', 'utf8'));

// Fonction pour normaliser les noms de régions
function normalizeRegionName(name) {
  return name
    .replace(/\s+/g, '') // Supprimer tous les espaces
    .replace(/-/g, '')   // Supprimer les tirets
    .toUpperCase();      // Mettre en majuscules
}

// Extraire les régions du fichier localite_rsu.json
const regionsRSU = localite_rsu[2].data
  .filter(item => item.locality_type_id === '3')
  .map(item => item.name.trim());

// Extraire les régions du fichier secretaires.json
const regionsSecretaires = [...new Set(secretaires.map(s => s.REGIONS))];

// Comparer les deux listes
console.log('Régions dans RSU mais pas dans secretaires.json:');
console.log(regionsRSU.filter(r => !regionsSecretaires.some(s => 
  normalizeRegionName(r) === normalizeRegionName(s)
)));

console.log('\nRégions dans secretaires.json mais pas dans RSU:');
console.log(regionsSecretaires.filter(r => !regionsRSU.some(s => 
  normalizeRegionName(r) === normalizeRegionName(s)
)));

// Afficher un tableau de correspondance proposé
console.log('\nTableau de correspondance proposé:');
const correspondances = {};

// Traiter d'abord les cas particuliers
const casParticuliers = {
  "DISTRICT AUTONOME D'ABIDJAN": "ABIDJAN",
  "DISTRICT AUTONOME DE YAMOUSSOUKRO": "YAMOUSSOUKRO",
  "SAN-PEDRO": "SAN PEDRO",
  "LOH-DJIBOUA": "LÔH - DJIBOUA",
  "GOH": "GÔH",
  "GBOKLE": "GBÔKLE"
};

regionsSecretaires.forEach(region => {
  // Vérifier les cas particuliers d'abord
  if (casParticuliers[region]) {
    correspondances[region] = casParticuliers[region];
    return;
  }

  // Trouver la meilleure correspondance dans regionsRSU
  let bestMatch = null;
  let bestScore = 0;
  
  for (const rsuRegion of regionsRSU) {
    // Normaliser les noms
    const normalizedSecretaire = normalizeRegionName(region);
    const normalizedRSU = normalizeRegionName(rsuRegion);
    
    // Vérifier si elles sont similaires
    if (normalizedSecretaire === normalizedRSU) {
      bestMatch = rsuRegion;
      bestScore = 1;
      break;
    }
    
    // Vérifier si l'une est incluse dans l'autre
    if (normalizedSecretaire.includes(normalizedRSU) || normalizedRSU.includes(normalizedSecretaire)) {
      const score = Math.min(normalizedSecretaire.length, normalizedRSU.length) / 
                   Math.max(normalizedSecretaire.length, normalizedRSU.length);
      if (score > bestScore) {
        bestScore = score;
        bestMatch = rsuRegion;
      }
    }
  }
  
  correspondances[region] = bestScore > 0.5 ? bestMatch : null;
});

// Afficher le résultat
console.log(JSON.stringify(correspondances, null, 2));

// Créer un fichier de correspondance pour utilisation future
const mapping = {};
regionsSecretaires.forEach(region => {
  mapping[region] = correspondances[region] || region;
});

// Sauvegarder le mapping dans un fichier
fs.writeFileSync('./scripts/regions_mapping.json', JSON.stringify(mapping, null, 2));
console.log('\nLe fichier de correspondance a été sauvegardé dans ./scripts/regions_mapping.json'); 