import fs from 'fs';

// Charger les données
const sousPref = JSON.parse(fs.readFileSync('./resources/data/sous-pref.json', 'utf8'));
const mappingComplet = JSON.parse(fs.readFileSync('./scripts/sousprefs_mapping.json', 'utf8'));

// Extraire les mappings spécifiques
const regionsMapping = mappingComplet.regions;
const departementsMapping = mappingComplet.departements;
const sousPrefecturesMapping = mappingComplet.sousPrefectures;

// Statistiques avant harmonisation
console.log('Statistiques avant harmonisation:');
console.log(`Nombre de régions uniques: ${new Set(sousPref.map(s => s.Région)).size}`);
console.log(`Nombre de départements uniques: ${new Set(sousPref.map(s => s.Département)).size}`);
console.log(`Nombre de sous-préfectures uniques: ${new Set(sousPref.map(s => s["Sous-Préfecture"])).size}`);

// Harmoniser les localités dans le fichier sous-pref.json
const sousPrefHarmonises = sousPref.map(item => {
  // Créer une copie pour ne pas modifier l'original
  const nouveauSousPref = { ...item };
  
  // Remplacer la région si elle existe dans le mapping
  if (nouveauSousPref.Région && regionsMapping[nouveauSousPref.Région]) {
    nouveauSousPref.Région = regionsMapping[nouveauSousPref.Région];
  }
  
  // Remplacer le département s'il existe dans le mapping
  if (nouveauSousPref.Département && departementsMapping[nouveauSousPref.Département]) {
    nouveauSousPref.Département = departementsMapping[nouveauSousPref.Département];
  }
  
  // Remplacer la sous-préfecture si elle existe dans le mapping
  if (nouveauSousPref["Sous-Préfecture"] && sousPrefecturesMapping[nouveauSousPref["Sous-Préfecture"]]) {
    nouveauSousPref["Sous-Préfecture"] = sousPrefecturesMapping[nouveauSousPref["Sous-Préfecture"]];
  }
  
  return nouveauSousPref;
});

// Statistiques après harmonisation
console.log('\nStatistiques après harmonisation:');
console.log(`Nombre de régions uniques: ${new Set(sousPrefHarmonises.map(s => s.Région)).size}`);
console.log(`Nombre de départements uniques: ${new Set(sousPrefHarmonises.map(s => s.Département)).size}`);
console.log(`Nombre de sous-préfectures uniques: ${new Set(sousPrefHarmonises.map(s => s["Sous-Préfecture"])).size}`);

// Sauvegarder le fichier harmonisé
fs.writeFileSync('./resources/data/sous-pref_harmonises.json', JSON.stringify(sousPrefHarmonises, null, 2));
console.log('\nLe fichier harmonisé a été sauvegardé dans ./resources/data/sous-pref_harmonises.json');

// Calculer les modifications effectuées
console.log('\nModifications effectuées:');

const regionsModifiees = Object.entries(regionsMapping)
  .filter(([key, value]) => key !== value && sousPref.some(s => s.Région === key));
console.log(`Régions modifiées: ${regionsModifiees.length}/${new Set(sousPref.map(s => s.Région)).size}`);

const departementsModifies = Object.entries(departementsMapping)
  .filter(([key, value]) => key !== value && sousPref.some(s => s.Département === key));
console.log(`Départements modifiés: ${departementsModifies.length}/${new Set(sousPref.map(s => s.Département)).size}`);

const sousPrefecturesModifiees = Object.entries(sousPrefecturesMapping)
  .filter(([key, value]) => key !== value && sousPref.some(s => s["Sous-Préfecture"] === key));
console.log(`Sous-préfectures modifiées: ${sousPrefecturesModifiees.length}/${new Set(sousPref.map(s => s["Sous-Préfecture"])).size}`);

// Compter le nombre d'entrées modifiées par catégorie
let regionsChangees = 0;
let departementsChanges = 0;
let sousPrefecturesChangees = 0;

sousPref.forEach((original, index) => {
  const harmonise = sousPrefHarmonises[index];
  
  if (original.Région !== harmonise.Région) {
    regionsChangees++;
  }
  
  if (original.Département !== harmonise.Département) {
    departementsChanges++;
  }
  
  if (original["Sous-Préfecture"] !== harmonise["Sous-Préfecture"]) {
    sousPrefecturesChangees++;
  }
});

console.log(`\nNombre d'entrées modifiées:`);
console.log(`Régions: ${regionsChangees}/${sousPref.length} entrées (${Math.round(regionsChangees/sousPref.length*100)}%)`);
console.log(`Départements: ${departementsChanges}/${sousPref.length} entrées (${Math.round(departementsChanges/sousPref.length*100)}%)`);
console.log(`Sous-préfectures: ${sousPrefecturesChangees}/${sousPref.length} entrées (${Math.round(sousPrefecturesChangees/sousPref.length*100)}%)`);
console.log(`Total: ${regionsChangees + departementsChanges + sousPrefecturesChangees} modifications sur ${sousPref.length * 3} champs possibles`); 