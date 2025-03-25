import fs from 'fs';

// Charger les données
const sousPref = JSON.parse(fs.readFileSync('./resources/data/sous-pref_harmonises.json', 'utf8'));
const secretaires = JSON.parse(fs.readFileSync('./resources/data/secretaires_harmonises_complet.json', 'utf8'));

// Fonction pour normaliser une date au format DD/MM/YYYY
function normalizeDate(dateStr) {
  if (!dateStr || dateStr.trim() === '') return '';
  
  // Supprimer les espaces
  dateStr = dateStr.trim();
  
  // Si la date est "NEANT" ou "-", retourner une chaîne vide
  if (dateStr === 'NEANT' || dateStr === '-') {
    return '';
  }
  
  // Différents formats possibles
  const patterns = [
    // Format avec double slash: 21//11/2024 -> 21/11/2024
    {
      regex: /^(\d{1,2})\/\/(\d{1,2})\/(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}`
    },
    // Format avec slash au milieu: 15/10//2024 -> 15/10/2024
    {
      regex: /^(\d{1,2})\/(\d{1,2})\/\/(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}`
    },
    // Format avec slash au début: 20/12//2024 -> 20/12/2024
    {
      regex: /^(\d{1,2})\/(\d{1,2})\/\/(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}`
    },
    // Format sans slash: 24/102024 -> 24/10/2024
    {
      regex: /^(\d{1,2})\/(\d{2})(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2]}/${m[3]}`
    },
    // Format sans slash au milieu: 07/032025 -> 07/03/2025
    {
      regex: /^(\d{1,2})\/(\d{2})(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2]}/${m[3]}`
    },
    // Format avec mois en chiffres incorrects: 16/012/2024 -> 16/12/2024
    {
      regex: /^(\d{1,2})\/0?(\d{1,2})\/(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}`
    },
    // Format dd-mmm[-yy]: 13-Nov-24 -> 13/11/2024
    {
      regex: /^(\d{1,2})-([A-Za-z]{3,9})(?:-(\d{2}))?$/,
      format: (m) => {
        const moisMap = {
          'jan': '01', 'feb': '02', 'mar': '03', 'apr': '04', 'may': '05', 'jun': '06',
          'jul': '07', 'aug': '08', 'sep': '09', 'oct': '10', 'nov': '11', 'dec': '12',
          'janvier': '01', 'fevrier': '02', 'février': '02', 'mars': '03', 'avril': '04',
          'mai': '05', 'juin': '06', 'juillet': '07', 'aout': '08', 'août': '08',
          'septembre': '09', 'octobre': '10', 'novembre': '11', 'decembre': '12', 'décembre': '12'
        };
        
        const moisKey = m[2].toLowerCase().substring(0, 3);
        const mois = moisMap[moisKey] || '??';
        const annee = m[3] ? `20${m[3]}` : '2024'; // Par défaut 2024 si l'année n'est pas spécifiée
        
        return `${m[1].padStart(2, '0')}/${mois}/${annee}`;
      }
    },
    // Format sans slash entre jour et mois: 2712/2024 -> 27/12/2024
    {
      regex: /^(\d{2})(\d{2})\/(\d{4})$/,
      format: (m) => `${m[1]}/${m[2]}/${m[3]}`
    },
    // Format m/d/yy
    {
      regex: /^(\d{1,2})\/(\d{1,2})\/(\d{2})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/20${m[3]}`
    },
    // Format d/m/yyyy
    {
      regex: /^(\d{1,2})\/(\d{1,2})\/(\d{4})$/,
      format: (m) => `${m[1].padStart(2, '0')}/${m[2].padStart(2, '0')}/${m[3]}`
    },
    // Format dd/mm/yyyy
    {
      regex: /^(\d{2})\/(\d{2})\/(\d{4})$/,
      format: (m) => `${m[1]}/${m[2]}/${m[3]}`
    },
    // Format dd/mm/yyyy avec espaces
    {
      regex: /^(\d{2})\/(\d{2})\/(\d{4})\s*$/,
      format: (m) => `${m[1]}/${m[2]}/${m[3]}`
    },
    // Format dd-mm-yyyy
    {
      regex: /^(\d{2})-(\d{2})-(\d{4})$/,
      format: (m) => `${m[1]}/${m[2]}/${m[3]}`
    },
    // Format yyyy-mm-dd
    {
      regex: /^(\d{4})-(\d{2})-(\d{2})$/,
      format: (m) => `${m[3]}/${m[2]}/${m[1]}`
    },
    // Format français avec texte: 23 octobre 2024
    {
      regex: /^(\d{1,2})\s*([a-zéûôêâîçà]+)\s*(\d{4})$/i,
      format: (m) => {
        const mois = {
          'janvier': '01', 'fevrier': '02', 'février': '02', 'mars': '03', 'avril': '04',
          'mai': '05', 'juin': '06', 'juillet': '07', 'aout': '08', 'août': '08',
          'septembre': '09', 'octobre': '10', 'novembre': '11', 'decembre': '12', 'décembre': '12'
        };
        const moisNormalise = m[2].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        return `${m[1].padStart(2, '0')}/${mois[moisNormalise] || '??'}/${m[3]}`;
      }
    }
  ];
  
  // Cas spéciaux détectés lors du premier passage
  const casSpeciaux = {
    '13-Feb': '13/02/2024',
    '20/12//2024': '20/12/2024',
    '21//11/2024': '21/11/2024',
    '15/10//2024': '15/10/2024',
    '24/102024': '24/10/2024',
    '22-Nov': '22/11/2024',
    '21-Feb': '21/02/2024',
    '17//12/2024': '17/12/2024',
    '28/11//2024': '28/11/2024',
    '21/102024': '21/10/2024',
    '16/012/2024': '16/12/2024',
    '13/112024': '13/11/2024',
    '20603/2025': '06/03/2025', // Corrigé manuellement
    '31/01/20258': '31/01/2025', // Corrigé manuellement
    '28/112024': '28/11/2024',
    '13/01/20250': '13/01/2025', // Corrigé manuellement
    '11/12/20024': '11/12/2024', // Corrigé manuellement
    '07/032025': '07/03/2025',
    '06//12/2024': '06/12/2024',
    '18/111/2024': '18/11/2024', // Corrigé manuellement
    '13-Nov-24': '13/11/2024',
    '21-Nov-24': '21/11/2024',
    '13-Dec-24': '13/12/2024',
    '20-Dec-24': '20/12/2024',
    '3-Jan-25': '03/01/2025',
    '10-Jan-25': '10/01/2025',
    '19-Dec': '19/12/2024',
    '12-Feb': '12/02/2024',
    '26-Feb': '26/02/2024',
    '27-Dec-24': '27/12/2024',
    '9-Jan-25': '09/01/2025',
    '14-Jan-25': '14/01/2025',
    '29-Jan-25': '29/01/2025',
    '13-Jan-25': '13/01/2025',
    '30-Jan-25': '30/01/2025',
    '13-Dec-25': '13/12/2025',
    '23-Dec-24': '23/12/2024',
    '12-Jan-25': '12/01/2025',
    '17-Jan-25': '17/01/2025',
    '2712/2024': '27/12/2024',
    '7-Jan-25': '07/01/2025',
    '22-Jan-25': '22/01/2025',
    '6-Jan-25': '06/01/2025',
    '28-Jan-25': '28/01/2025',
    '18-Jan-25': '18/01/2025',
    '2812/2024': '28/12/2024'
  };
  
  // Vérifier d'abord les cas spéciaux
  if (casSpeciaux[dateStr]) {
    return casSpeciaux[dateStr];
  }
  
  // Essayer chaque format
  for (const pattern of patterns) {
    const match = dateStr.match(pattern.regex);
    if (match) {
      return pattern.format(match);
    }
  }
  
  // Si on ne reconnaît pas le format, on retourne la chaîne originale
  console.log(`Format de date non reconnu: "${dateStr}"`);
  return dateStr;
}

// Liste spécifique des champs de dates dans le fichier sous-pref_harmonises.json
// En excluant "Population à enrôlées" qui n'est pas une date
const champsDatesSousPref = [
  "Date de prise de l'arrêté/décision",
  "Date de réception de l'arrêté /Décision",
  "Date de planification de la tenue de la reunion_d'installation du COLOC",
  "Date de validation de l'ANO",
  "Date de transmission des fonds au Sous-Préfet",
  "reunion_1",
  "reunion_2",
  "reunion_3",
  "reunion_4",
  "reunion_5",
  "reunion_6"
];

let modificationsSousPref = 0;
const sousPrefHarmonises = sousPref.map(item => {
  const nouveauItem = { ...item };
  
  // Harmoniser uniquement les champs de dates identifiés
  champsDatesSousPref.forEach(champ => {
    if (nouveauItem[champ]) {
      const dateOriginale = nouveauItem[champ];
      const dateNormalisee = normalizeDate(dateOriginale);
      
      if (dateOriginale !== dateNormalisee) {
        modificationsSousPref++;
        nouveauItem[champ] = dateNormalisee;
      }
    }
  });
  
  return nouveauItem;
});

// Harmoniser les dates dans le fichier secretaires_harmonises_complet.json
// Actuellement, ce fichier ne semble pas contenir de champs de dates à harmoniser
let modificationsSecretaires = 0;

// Sauvegarder les fichiers harmonisés
fs.writeFileSync('./resources/data/sous-pref_dates_harmonisees_v2.json', JSON.stringify(sousPrefHarmonises, null, 2));
fs.writeFileSync('./resources/data/secretaires_dates_harmonisees_v2.json', JSON.stringify(secretaires, null, 2));

// Afficher les statistiques
console.log(`Harmonisation des dates terminée.`);
console.log(`Modifications dans sous-pref_harmonises.json: ${modificationsSousPref}`);
console.log(`Modifications dans secretaires_harmonises_complet.json: ${modificationsSecretaires}`);
console.log(`\nLes fichiers ont été sauvegardés:`);
console.log(`- ./resources/data/sous-pref_dates_harmonisees_v2.json`);
console.log(`- ./resources/data/secretaires_dates_harmonisees_v2.json`); 