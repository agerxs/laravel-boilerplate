import fs from 'fs';

// Charger les données
const secretaires = JSON.parse(fs.readFileSync('./resources/data/secretaires.json', 'utf8'));
const mappingComplet = JSON.parse(fs.readFileSync('./scripts/localites_mapping.json', 'utf8'));

// Extraire les mappings spécifiques
const regionsMapping = mappingComplet.regions;
const departementsMapping = mappingComplet.departements;
const sousPrefecturesMapping = mappingComplet.sousPrefectures;

// Statistiques avant harmonisation
console.log('Statistiques avant harmonisation:');
console.log(`Nombre de régions uniques: ${new Set(secretaires.map(s => s.REGIONS)).size}`);
console.log(`Nombre de départements uniques: ${new Set(secretaires.map(s => s.DEPARTEMENTS)).size}`);
console.log(`Nombre de sous-préfectures uniques: ${new Set(secretaires.map(s => s['SOUS-PREFECTURES'])).size}`);

// Harmoniser les localités dans le fichier secretaires.json
const secretairesHarmonises = secretaires.map(secretaire => {
  // Créer une copie pour ne pas modifier l'original
  const nouveauSecretaire = { ...secretaire };
  
  // Remplacer la région si elle existe dans le mapping
  if (nouveauSecretaire.REGIONS && regionsMapping[nouveauSecretaire.REGIONS]) {
    nouveauSecretaire.REGIONS = regionsMapping[nouveauSecretaire.REGIONS];
  }
  
  // Remplacer le département s'il existe dans le mapping
  if (nouveauSecretaire.DEPARTEMENTS && departementsMapping[nouveauSecretaire.DEPARTEMENTS]) {
    nouveauSecretaire.DEPARTEMENTS = departementsMapping[nouveauSecretaire.DEPARTEMENTS];
  }
  
  // Remplacer la sous-préfecture si elle existe dans le mapping
  if (nouveauSecretaire['SOUS-PREFECTURES'] && sousPrefecturesMapping[nouveauSecretaire['SOUS-PREFECTURES']]) {
    nouveauSecretaire['SOUS-PREFECTURES'] = sousPrefecturesMapping[nouveauSecretaire['SOUS-PREFECTURES']];
  }
  
  return nouveauSecretaire;
});

// Statistiques après harmonisation
console.log('\nStatistiques après harmonisation:');
console.log(`Nombre de régions uniques: ${new Set(secretairesHarmonises.map(s => s.REGIONS)).size}`);
console.log(`Nombre de départements uniques: ${new Set(secretairesHarmonises.map(s => s.DEPARTEMENTS)).size}`);
console.log(`Nombre de sous-préfectures uniques: ${new Set(secretairesHarmonises.map(s => s['SOUS-PREFECTURES'])).size}`);

// Sauvegarder le fichier harmonisé
fs.writeFileSync('./resources/data/secretaires_harmonises_complet.json', JSON.stringify(secretairesHarmonises, null, 2));
console.log('\nLe fichier harmonisé a été sauvegardé dans ./resources/data/secretaires_harmonises_complet.json');

// Calculer les modifications effectuées
console.log('\nModifications effectuées:');

const regionsModifiees = Object.entries(regionsMapping)
  .filter(([key, value]) => key !== value);
console.log(`Régions modifiées: ${regionsModifiees.length}/${Object.keys(regionsMapping).length}`);

const departementsModifies = Object.entries(departementsMapping)
  .filter(([key, value]) => key !== value);
console.log(`Départements modifiés: ${departementsModifies.length}/${Object.keys(departementsMapping).length}`);

const sousPrefecturesModifiees = Object.entries(sousPrefecturesMapping)
  .filter(([key, value]) => key !== value);
console.log(`Sous-préfectures modifiées: ${sousPrefecturesModifiees.length}/${Object.keys(sousPrefecturesMapping).length}`);

// Compter le nombre d'entrées modifiées par catégorie
let regionsChangees = 0;
let departementsChanges = 0;
let sousPrefecturesChangees = 0;

secretaires.forEach((original, index) => {
  const harmonise = secretairesHarmonises[index];
  
  if (original.REGIONS !== harmonise.REGIONS) {
    regionsChangees++;
  }
  
  if (original.DEPARTEMENTS !== harmonise.DEPARTEMENTS) {
    departementsChanges++;
  }
  
  if (original['SOUS-PREFECTURES'] !== harmonise['SOUS-PREFECTURES']) {
    sousPrefecturesChangees++;
  }
});

console.log(`\nNombre d'entrées modifiées:`);
console.log(`Régions: ${regionsChangees}/${secretaires.length} entrées (${Math.round(regionsChangees/secretaires.length*100)}%)`);
console.log(`Départements: ${departementsChanges}/${secretaires.length} entrées (${Math.round(departementsChanges/secretaires.length*100)}%)`);
console.log(`Sous-préfectures: ${sousPrefecturesChangees}/${secretaires.length} entrées (${Math.round(sousPrefecturesChangees/secretaires.length*100)}%)`);
console.log(`Total: ${regionsChangees + departementsChanges + sousPrefecturesChangees} modifications sur ${secretaires.length * 3} champs possibles`); 