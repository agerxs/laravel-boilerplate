import fs from 'fs';

// Charger les données
const sousPref = JSON.parse(fs.readFileSync('./resources/data/sous-pref.json', 'utf8'));
const localite_rsu = JSON.parse(fs.readFileSync('./resources/data/localite_rsu.json', 'utf8'));

// Fonction pour normaliser les noms de localités
function normalizeName(name) {
  if (!name) return '';
  return name
    .replace(/\s+/g, '')  // Supprimer tous les espaces
    .replace(/-/g, '')    // Supprimer les tirets
    .replace(/'/g, '')    // Supprimer les apostrophes
    .replace(/Ô/g, 'O')   // Remplacer les caractères accentués
    .replace(/ô/g, 'o')
    .replace(/é/g, 'e')
    .replace(/è/g, 'e')
    .replace(/ê/g, 'e')
    .replace(/à/g, 'a')
    .replace(/â/g, 'a')
    .replace(/ï/g, 'i')
    .replace(/î/g, 'i')
    .replace(/ç/g, 'c')
    .replace(/ù/g, 'u')
    .replace(/û/g, 'u')
    .toUpperCase();       // Mettre en majuscules
}

// Extraire les localités du fichier localite_rsu.json
const rsuRegions = localite_rsu[2].data
  .filter(item => item.locality_type_id === '3')
  .map(item => ({ id: item.id, name: item.name.trim() }));

const rsuDepartements = localite_rsu[2].data
  .filter(item => item.locality_type_id === '4')
  .map(item => ({ id: item.id, name: item.name.trim(), parent_id: item.parent_id }));

const rsuSousPrefectures = localite_rsu[2].data
  .filter(item => item.locality_type_id === '5')
  .map(item => ({ id: item.id, name: item.name.trim(), parent_id: item.parent_id }));

// Extraire les localités du fichier sous-pref.json
const sousPrefRegions = [...new Set(sousPref.map(s => s.Région))].filter(Boolean);
const sousPrefDepartements = [...new Set(sousPref.map(s => s.Département))].filter(Boolean);
const sousPrefSousPrefectures = [...new Set(sousPref.map(s => s["Sous-Préfecture"]))].filter(Boolean);

// Créer des mappings de normalisation aux noms originaux
const rsuRegionsMap = new Map();
rsuRegions.forEach(region => {
  rsuRegionsMap.set(normalizeName(region.name), region.name);
});

const rsuDepartementsMap = new Map();
rsuDepartements.forEach(dept => {
  rsuDepartementsMap.set(normalizeName(dept.name), dept.name);
});

const rsuSousPrefecturesMap = new Map();
rsuSousPrefectures.forEach(sp => {
  rsuSousPrefecturesMap.set(normalizeName(sp.name), sp.name);
});

// Créer des mappings pour stocker les correspondances
const regionsMapping = {};
const departementsMapping = {};
const sousPrefecturesMapping = {};

// Trouver les correspondances pour les régions
sousPrefRegions.forEach(region => {
  const normalized = normalizeName(region);
  if (rsuRegionsMap.has(normalized)) {
    regionsMapping[region] = rsuRegionsMap.get(normalized);
  } else {
    // Chercher la meilleure correspondance
    let bestMatch = null;
    let bestScore = 0;
    
    for (const [normRsuRegion, originalRsuRegion] of rsuRegionsMap.entries()) {
      // Vérifier si l'une est incluse dans l'autre
      if (normalized.includes(normRsuRegion) || normRsuRegion.includes(normalized)) {
        const score = Math.min(normalized.length, normRsuRegion.length) / 
                     Math.max(normalized.length, normRsuRegion.length);
        if (score > bestScore) {
          bestScore = score;
          bestMatch = originalRsuRegion;
        }
      }
    }
    
    regionsMapping[region] = bestScore > 0.6 ? bestMatch : region;
  }
});

// Cas particuliers pour les régions
const casParticuliersRegions = {
  "DISTRICT AUTONOME D'ABIDJAN": "ABIDJAN",
  "DISTRICT AUTONOME DE YAMOUSSOUKRO": "YAMOUSSOUKRO",
  "SAN-PEDRO": "SAN PEDRO",
  "LOH-DJIBOUA": "LÔH - DJIBOUA",
  "GOH": "GÔH",
  "GBOKLE": "GBÔKLE"
};

// Appliquer les cas particuliers aux régions
Object.keys(casParticuliersRegions).forEach(key => {
  if (sousPrefRegions.includes(key)) {
    regionsMapping[key] = casParticuliersRegions[key];
  }
});

// Trouver les correspondances pour les départements
sousPrefDepartements.forEach(dept => {
  const normalized = normalizeName(dept);
  if (rsuDepartementsMap.has(normalized)) {
    departementsMapping[dept] = rsuDepartementsMap.get(normalized);
  } else {
    // Chercher la meilleure correspondance
    let bestMatch = null;
    let bestScore = 0;
    
    for (const [normRsuDept, originalRsuDept] of rsuDepartementsMap.entries()) {
      // Vérifier si l'une est incluse dans l'autre
      if (normalized.includes(normRsuDept) || normRsuDept.includes(normalized)) {
        const score = Math.min(normalized.length, normRsuDept.length) / 
                     Math.max(normalized.length, normRsuDept.length);
        if (score > bestScore) {
          bestScore = score;
          bestMatch = originalRsuDept;
        }
      }
    }
    
    departementsMapping[dept] = bestScore > 0.6 ? bestMatch : dept;
  }
});

// Trouver les correspondances pour les sous-préfectures
sousPrefSousPrefectures.forEach(sp => {
  const normalized = normalizeName(sp);
  if (rsuSousPrefecturesMap.has(normalized)) {
    sousPrefecturesMapping[sp] = rsuSousPrefecturesMap.get(normalized);
  } else {
    // Chercher la meilleure correspondance
    let bestMatch = null;
    let bestScore = 0;
    
    for (const [normRsuSp, originalRsuSp] of rsuSousPrefecturesMap.entries()) {
      // Vérifier si l'une est incluse dans l'autre
      if (normalized.includes(normRsuSp) || normRsuSp.includes(normalized)) {
        const score = Math.min(normalized.length, normRsuSp.length) / 
                     Math.max(normalized.length, normRsuSp.length);
        if (score > bestScore) {
          bestScore = score;
          bestMatch = originalRsuSp;
        }
      }
    }
    
    sousPrefecturesMapping[sp] = bestScore > 0.6 ? bestMatch : sp;
  }
});

// Créer un objet de mapping global
const mappingComplet = {
  regions: regionsMapping,
  departements: departementsMapping,
  sousPrefectures: sousPrefecturesMapping
};

// Sauvegarder le mapping dans un fichier
fs.writeFileSync('./scripts/sousprefs_mapping.json', JSON.stringify(mappingComplet, null, 2));
console.log('Le fichier de correspondance a été sauvegardé dans ./scripts/sousprefs_mapping.json');

// Afficher quelques statistiques
console.log(`\nStatistiques des correspondances:`);
console.log(`Régions: ${Object.keys(regionsMapping).length} correspondances trouvées`);
console.log(`Départements: ${Object.keys(departementsMapping).length} correspondances trouvées`);
console.log(`Sous-préfectures: ${Object.keys(sousPrefecturesMapping).length} correspondances trouvées`);

// Afficher quelques exemples de modifications
console.log(`\nExemples de modifications pour les régions:`);
const regionsModifiees = Object.entries(regionsMapping)
  .filter(([key, value]) => key !== value)
  .slice(0, 5);
console.log(regionsModifiees);

console.log(`\nExemples de modifications pour les départements:`);
const departementsModifies = Object.entries(departementsMapping)
  .filter(([key, value]) => key !== value)
  .slice(0, 5);
console.log(departementsModifies);

console.log(`\nExemples de modifications pour les sous-préfectures:`);
const sousPrefsModifiees = Object.entries(sousPrefecturesMapping)
  .filter(([key, value]) => key !== value)
  .slice(0, 5);
console.log(sousPrefsModifiees); 