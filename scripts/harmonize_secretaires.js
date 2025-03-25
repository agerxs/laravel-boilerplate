import fs from 'fs';

// Charger les données
const secretaires = JSON.parse(fs.readFileSync('./resources/data/secretaires.json', 'utf8'));
const mapping = JSON.parse(fs.readFileSync('./scripts/regions_mapping.json', 'utf8'));

// Statistiques avant harmonisation
const regionsAvant = [...new Set(secretaires.map(s => s.REGIONS))];
console.log(`Nombre de régions avant harmonisation: ${regionsAvant.length}`);

// Harmoniser les régions dans le fichier secretaires.json
const secretairesHarmonises = secretaires.map(secretaire => {
  // Créer une copie pour ne pas modifier l'original
  const nouveauSecretaire = { ...secretaire };
  
  // Remplacer la région par celle du mapping
  if (nouveauSecretaire.REGIONS && mapping[nouveauSecretaire.REGIONS]) {
    nouveauSecretaire.REGIONS = mapping[nouveauSecretaire.REGIONS];
  }
  
  return nouveauSecretaire;
});

// Statistiques après harmonisation
const regionsApres = [...new Set(secretairesHarmonises.map(s => s.REGIONS))];
console.log(`Nombre de régions après harmonisation: ${regionsApres.length}`);

// Sauvegarder le fichier harmonisé
fs.writeFileSync('./resources/data/secretaires_harmonises.json', JSON.stringify(secretairesHarmonises, null, 2));
console.log('\nLe fichier harmonisé a été sauvegardé dans ./resources/data/secretaires_harmonises.json');

// Afficher les modifications effectuées
console.log('\nModifications effectuées:');
const modifications = {};
regionsAvant.forEach(region => {
  if (mapping[region] !== region) {
    modifications[region] = mapping[region];
  }
});

console.log(JSON.stringify(modifications, null, 2)); 