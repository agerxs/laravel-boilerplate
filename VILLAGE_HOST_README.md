# Gestion du Village Hôte lors du Split des Réunions

## Vue d'ensemble

Cette fonctionnalité permet de spécifier précisément quel village parmi les participants accueillera chaque sous-réunion lors de l'éclatement d'une réunion principale. Cela améliore l'organisation et la planification des réunions par sous-régions.

## Fonctionnalités

### 1. Sélection du village hôte
- **Choix obligatoire** : Chaque sous-réunion doit avoir un village hôte défini
- **Validation automatique** : Le village hôte doit faire partie des villages participants
- **Interface intuitive** : Sélection via un menu déroulant des villages participants

### 2. Intégration avec le lieu
- **Combinaison automatique** : Le lieu saisi est automatiquement combiné avec le nom du village hôte
- **Format standardisé** : "Lieu - Nom du village hôte"
- **Traçabilité claire** : Identification immédiate du village d'accueil

### 3. Validation intelligente
- **Vérification des participants** : Le village hôte doit être dans la liste des villages participants
- **Réinitialisation automatique** : Si un village hôte est retiré des participants, il est automatiquement réinitialisé
- **Messages d'erreur clairs** : Indication des champs manquants ou invalides

## Utilisation

### 1. Création d'une sous-réunion avec village hôte

#### Étape 1 : Définir le lieu
- Saisir le lieu de la réunion (ex: "Salle polyvalente", "Mairie", "Centre communautaire")
- Le lieu sera automatiquement enrichi avec le nom du village hôte

#### Étape 2 : Sélectionner le village hôte
- Choisir le village hôte dans le menu déroulant
- Seuls les villages participants sont disponibles
- Le village hôte est obligatoire

#### Étape 3 : Résultat automatique
- Le lieu final sera : "Lieu - Nom du village hôte"
- Exemple : "Salle polyvalente - Village de Bouaké"

### 2. Exemples de configuration

#### Exemple 1 : Sous-réunion simple
```
Lieu : Salle polyvalente
Village hôte : Village A
Résultat : "Salle polyvalente - Village A"
```

#### Exemple 2 : Sous-réunion avec lieu détaillé
```
Lieu : Mairie annexe, 2ème étage
Village hôte : Village B
Résultat : "Mairie annexe, 2ème étage - Village B"
```

#### Exemple 3 : Sous-réunion avec lieu spécifique
```
Lieu : Centre de formation agricole
Village hôte : Village C
Résultat : "Centre de formation agricole - Village C"
```

## Structure technique

### 1. Backend (Laravel)

#### Validation des données
```php
$validated = $request->validate([
    'sub_meetings' => 'required|array|min:1',
    'sub_meetings.*.location' => 'required|string',
    'sub_meetings.*.villages' => 'required|array|min:1',
    'sub_meetings.*.villages.*.id' => 'required|exists:localite,id',
    'sub_meetings.*.villages.*.name' => 'required|string',
    'sub_meetings.*.host_village_id' => 'required|exists:localite,id',
    'sub_meetings.*.scheduled_date' => 'nullable|date',
    'sub_meetings.*.scheduled_time' => 'nullable|date_format:H:i',
    'sub_meetings.*.title' => 'nullable|string|max:255',
]);
```

#### Vérification du village hôte
```php
// Vérifier que le village hôte fait partie des villages participants
$hostVillageId = $subMeetingData['host_village_id'];
$participatingVillageIds = array_column($subMeetingData['villages'], 'id');

if (!in_array($hostVillageId, $participatingVillageIds)) {
    throw new \Exception("Le village hôte doit faire partie des villages participants");
}

// Obtenir le nom du village hôte pour le lieu
$hostVillage = Locality::find($hostVillageId);
$locationWithHost = $subMeetingData['location'] . ' - ' . $hostVillage->name;
```

### 2. Frontend (Vue.js)

#### Structure des données d'une sous-réunion
```javascript
const subMeeting = {
  location: '',           // Lieu (obligatoire)
  villages: [],           // Villages sélectionnés (obligatoire)
  host_village_id: '',    // Village hôte (obligatoire)
  scheduled_date: '',     // Date personnalisée (optionnel)
  scheduled_time: '',     // Heure personnalisée (optionnel)
  title: ''               // Titre personnalisé (optionnel)
}
```

#### Validation côté client
```javascript
const canSplitMeeting = computed(() => {
  // Vérifier qu'il y a au moins une sous-réunion avec des villages, un lieu et un village hôte
  const validSubMeetings = subMeetings.value.filter(sm => 
    sm.villages.length > 0 && sm.location && sm.host_village_id
  )
  
  if (validSubMeetings.length === 0) return false
  
  // Vérifier qu'aucun village n'est dupliqué
  const allVillageIds = validSubMeetings.flatMap(sm => sm.villages.map(v => v.id))
  const uniqueVillageIds = [...new Set(allVillageIds)]
  
  return allVillageIds.length === uniqueVillageIds.length
})
```

#### Gestion automatique du village hôte
```javascript
// Ajouter un village à une sous-réunion
const addVillageToSubMeeting = (subMeetingIndex, village) => {
  subMeetings.value[subMeetingIndex].villages.push(village)
  // Réinitialiser le village hôte si le village actuel n'est plus dans la liste
  const subMeeting = subMeetings.value[subMeetingIndex]
  if (subMeeting.host_village_id && !subMeeting.villages.find(v => v.id === subMeeting.host_village_id)) {
    subMeeting.host_village_id = ''
  }
}
```

### 3. Application mobile (Flutter)

#### Structure des données d'éclatement
```dart
final subRegionsData = _subRegions
    .where((sr) => _selectedSubRegions.contains(sr['id']))
    .map((sr) => {
          'id': sr['id'],
          'name': sr['name'],
          'villages': (sr['villages'] as List<dynamic>)
              .map((v) => {'id': v['id']})
              .toList(),
          'location': sr['location'] ?? '',
          'host_village_id': sr['host_village_id'] ?? sr['villages'][0]['id'], // Utiliser le premier village par défaut
        })
    .toList();
```

## Interface utilisateur

### 1. Formulaire de création de sous-réunion

#### Champs obligatoires
- **Lieu** : Saisie libre du lieu de la réunion
- **Village hôte** : Sélection obligatoire parmi les villages participants
- **Villages participants** : Liste des villages inclus dans la sous-réunion

#### Champs optionnels
- **Date personnalisée** : Date spécifique pour la sous-réunion
- **Heure personnalisée** : Heure spécifique pour la sous-réunion
- **Titre personnalisé** : Titre spécifique pour la sous-réunion

### 2. Validation en temps réel

#### Indicateurs visuels
- **Champs obligatoires** : Marqués avec un astérisque (*)
- **Validation** : Vérification instantanée de la complétude
- **Messages d'aide** : Explications contextuelles pour chaque champ

#### Gestion des erreurs
- **Village hôte manquant** : Message d'erreur clair
- **Village hôte invalide** : Réinitialisation automatique
- **Validation globale** : Bouton d'éclatement désactivé si invalide

## Cas d'usage

### 1. Organisation par proximité géographique
**Scénario** : Organiser des sous-réunions dans les villages les plus accessibles pour les participants.

**Solution** :
- Sous-réunion A : Village central avec salle polyvalente
- Sous-réunion B : Village avec mairie accessible
- Sous-réunion C : Village avec centre communautaire

### 2. Optimisation des ressources locales
**Scénario** : Utiliser les infrastructures disponibles dans chaque village.

**Solution** :
- Village A : Salle de réunion municipale
- Village B : Centre de formation existant
- Village C : Espace communautaire rénové

### 3. Coordination logistique
**Scénario** : Faciliter l'organisation et la logistique des réunions.

**Solution** :
- Identification claire du village d'accueil
- Coordination avec les autorités locales
- Préparation des espaces et équipements

## Avantages

### 1. Organisation claire
- **Identification précise** : Chaque sous-réunion a un village hôte défini
- **Responsabilité claire** : Le village hôte est responsable de l'accueil
- **Coordination facilitée** : Communication directe avec le village d'accueil

### 2. Planification efficace
- **Optimisation des ressources** : Utilisation des infrastructures locales
- **Réduction des conflits** : Éviter les doublons de lieux
- **Gestion logistique** : Préparation appropriée des espaces

### 3. Traçabilité complète
- **Historique clair** : Chaque sous-réunion a son village hôte documenté
- **Audit facilité** : Suivi des lieux d'accueil
- **Rapports détaillés** : Informations complètes pour l'analyse

## Bonnes pratiques

### 1. Pour les organisateurs
- **Choisir judicieusement** : Sélectionner le village le plus approprié pour l'accueil
- **Vérifier les capacités** : S'assurer que le village hôte peut accueillir la réunion
- **Communiquer à l'avance** : Informer le village hôte de ses responsabilités

### 2. Pour les villages hôtes
- **Préparer l'accueil** : Aménager l'espace et vérifier les équipements
- **Coordonner localement** : Travailler avec les autorités locales
- **Faciliter la logistique** : Aider à l'organisation pratique

### 3. Pour les participants
- **Vérifier le lieu** : Consulter le lieu complet avec le village hôte
- **Prévoir le transport** : Tenir compte de la localisation du village hôte
- **Respecter les règles** : Adapter aux contraintes locales du village hôte

## Dépannage

### 1. Problèmes courants

#### Village hôte non sélectionné
**Symptôme** : Impossible de créer la sous-réunion
**Cause** : Le champ village hôte est vide
**Solution** : Sélectionner un village hôte parmi les participants

#### Village hôte invalide
**Symptôme** : Erreur de validation
**Cause** : Le village hôte ne fait pas partie des participants
**Solution** : Vérifier la cohérence entre villages participants et village hôte

#### Lieu non mis à jour
**Symptôme** : Le lieu n'inclut pas le nom du village hôte
**Cause** : Problème dans la génération automatique du lieu
**Solution** : Vérifier que le village hôte est correctement sélectionné

### 2. Solutions

#### Vérification des données
- Contrôler que tous les champs obligatoires sont remplis
- Vérifier la cohérence entre villages participants et village hôte
- S'assurer que le lieu est saisi correctement

#### Logs et débogage
- Consulter les logs du serveur pour les erreurs backend
- Vérifier la console du navigateur pour les erreurs frontend
- Contrôler les données envoyées dans les requêtes API

## Évolutions futures

### 1. Fonctionnalités envisagées
- **Gestion des capacités** : Vérification automatique de la capacité d'accueil
- **Suggestion de lieux** : Propositions automatiques basées sur les infrastructures
- **Gestion des équipements** : Inventaire des équipements disponibles par village

### 2. Améliorations techniques
- **Validation avancée** : Vérification des conflits d'horaires par lieu
- **Géolocalisation** : Intégration de cartes pour visualiser les lieux
- **Notifications automatiques** : Informer automatiquement le village hôte

## Conclusion

La gestion du village hôte lors du split des réunions améliore significativement l'organisation et la planification des réunions par sous-régions. Cette fonctionnalité permet d'identifier clairement le village responsable de l'accueil tout en maintenant une traçabilité complète des lieux de réunion.

En combinant la sélection obligatoire du village hôte avec la génération automatique du lieu complet, cette fonctionnalité facilite la coordination logistique et améliore l'efficacité de l'organisation des réunions.
