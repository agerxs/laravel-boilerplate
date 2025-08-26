# Gestion des Dates et Heures lors du Split des Réunions

## Vue d'ensemble

Cette fonctionnalité permet aux utilisateurs de définir des dates et heures spécifiques pour chaque sous-réunion créée lors de l'éclatement d'une réunion principale. Cela offre une flexibilité maximale dans la planification des réunions par sous-régions.

## Fonctionnalités

### 1. Champs de saisie optionnels
- **Date personnalisée** : Possibilité de définir une date différente de la réunion principale
- **Heure personnalisée** : Possibilité de définir une heure différente de la réunion principale
- **Titre personnalisé** : Possibilité de personnaliser le titre de chaque sous-réunion

### 2. Validation intelligente
- **Date minimale** : Les sous-réunions ne peuvent pas être programmées avant la date de la réunion principale
- **Champs optionnels** : Si aucun champ n'est rempli, la date et l'heure de la réunion principale sont utilisées
- **Combinaison flexible** : Possibilité de ne modifier que la date, que l'heure, ou les deux

### 3. Interface utilisateur intuitive
- **Formulaire intégré** : Champs de date et heure directement dans le formulaire de création de sous-réunion
- **Aide contextuelle** : Messages explicatifs pour guider l'utilisateur
- **Validation visuelle** : Indicateurs clairs sur les champs obligatoires et optionnels

## Utilisation

### 1. Création d'une sous-réunion avec date/heure personnalisées

#### Étape 1 : Accéder au formulaire de split
1. Naviguer vers une réunion principale
2. Cliquer sur "Éclater la réunion"
3. Le formulaire de création de sous-réunions s'affiche

#### Étape 2 : Remplir les informations de base
1. **Lieu** : Saisir le lieu de la sous-réunion (obligatoire)
2. **Villages** : Sélectionner les villages à inclure (obligatoire)

#### Étape 3 : Personnaliser la date et l'heure (optionnel)
1. **Date personnalisée** : 
   - Cliquer sur le champ date
   - Sélectionner une date (doit être >= date de la réunion principale)
   - Laisser vide pour utiliser la date de la réunion principale

2. **Heure personnalisée** :
   - Cliquer sur le champ heure
   - Sélectionner l'heure souhaitée
   - Laisser vide pour utiliser l'heure de la réunion principale

3. **Titre personnalisé** :
   - Saisir un titre spécifique
   - Laisser vide pour utiliser le titre automatique (basé sur les villages)

### 2. Exemples de configuration

#### Exemple 1 : Sous-réunion le même jour, heure différente
```
Date : (vide - utilise la date principale)
Heure : 14:00
Résultat : Sous-réunion programmée le même jour à 14h00
```

#### Exemple 2 : Sous-réunion un autre jour, même heure
```
Date : 2025-02-15
Heure : (vide - utilise l'heure principale)
Résultat : Sous-réunion programmée le 15 février à l'heure de la réunion principale
```

#### Exemple 3 : Sous-réunion complètement personnalisée
```
Date : 2025-02-16
Heure : 09:30
Résultat : Sous-réunion programmée le 16 février à 9h30
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
    'sub_meetings.*.scheduled_date' => 'nullable|date',
    'sub_meetings.*.scheduled_time' => 'nullable|date_format:H:i',
    'sub_meetings.*.title' => 'nullable|string|max:255',
]);
```

#### Logique de détermination de la date/heure
```php
private function determineSubMeetingDateTime(Meeting $parentMeeting, array $subMeetingData): string
{
    // Si une date spécifique est fournie, l'utiliser
    if (!empty($subMeetingData['scheduled_date'])) {
        $date = $subMeetingData['scheduled_date'];
        
        // Si une heure spécifique est fournie, la combiner avec la date
        if (!empty($subMeetingData['scheduled_time'])) {
            return $date . ' ' . $subMeetingData['scheduled_time'];
        }
        
        // Sinon, utiliser l'heure de la réunion parent
        return $date . ' ' . $parentMeeting->scheduled_date->format('H:i:s');
    }
    
    // Si aucune date spécifique n'est fournie, utiliser la date de la réunion parent
    return $parentMeeting->scheduled_date->format('Y-m-d H:i:s');
}
```

### 2. Frontend (Vue.js)

#### Structure des données d'une sous-réunion
```javascript
const subMeeting = {
  location: '',           // Lieu (obligatoire)
  villages: [],           // Villages sélectionnés (obligatoire)
  scheduled_date: '',     // Date personnalisée (optionnel)
  scheduled_time: '',     // Heure personnalisée (optionnel)
  title: ''               // Titre personnalisé (optionnel)
}
```

#### Validation côté client
```javascript
const canSplitMeeting = computed(() => {
  // Vérifier qu'il y a au moins une sous-réunion avec des villages et un lieu
  const validSubMeetings = subMeetings.value.filter(sm => 
    sm.villages.length > 0 && sm.location
  )
  
  if (validSubMeetings.length === 0) return false
  
  // Vérifier qu'aucun village n'est dupliqué
  const allVillageIds = validSubMeetings.flatMap(sm => sm.villages.map(v => v.id))
  const uniqueVillageIds = [...new Set(allVillageIds)]
  
  return allVillageIds.length === uniqueVillageIds.length
})
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
          'scheduled_date': sr['scheduled_date'] ?? null,
          'scheduled_time': sr['scheduled_time'] ?? null,
          'title': sr['title'] ?? null,
        })
    .toList();
```

## Cas d'usage

### 1. Planification échelonnée
**Scénario** : Une réunion principale est programmée pour le 20 février à 10h00, mais les participants de différentes sous-régions ne peuvent pas tous se réunir le même jour.

**Solution** :
- Sous-réunion A : 20 février à 10h00 (même jour, même heure)
- Sous-réunion B : 21 février à 14h00 (jour suivant, heure différente)
- Sous-réunion C : 22 février à 09h00 (jour suivant, heure différente)

### 2. Adaptation aux contraintes locales
**Scénario** : Certains villages ont des contraintes de transport ou d'organisation qui nécessitent des horaires différents.

**Solution** :
- Sous-réunion urbaine : 09h00 (accès facile, participants disponibles tôt)
- Sous-réunion rurale : 14h00 (temps de transport, participants disponibles plus tard)

### 3. Optimisation des ressources
**Scénario** : Les salles de réunion disponibles ont des créneaux différents.

**Solution** :
- Sous-réunion 1 : 09h00-11h00 (salle A)
- Sous-réunion 2 : 14h00-16h00 (salle B)
- Sous-réunion 3 : 16h00-18h00 (salle A)

## Avantages

### 1. Flexibilité maximale
- Adaptation aux contraintes locales
- Respect des disponibilités des participants
- Optimisation des ressources disponibles

### 2. Planification efficace
- Réduction des conflits d'horaires
- Meilleure gestion des espaces de réunion
- Coordination optimisée entre sous-réunions

### 3. Traçabilité complète
- Chaque sous-réunion a sa propre date/heure
- Historique clair des modifications
- Audit trail pour la planification

## Bonnes pratiques

### 1. Pour les organisateurs
- **Planifier à l'avance** : Définir les dates/heures dès la création des sous-réunions
- **Vérifier les disponibilités** : S'assurer que les créneaux choisis sont appropriés
- **Communiquer clairement** : Informer les participants des horaires spécifiques

### 2. Pour les participants
- **Vérifier les horaires** : Consulter la date/heure spécifique de sa sous-réunion
- **Respecter les créneaux** : Arriver à l'heure pour ne pas retarder le groupe
- **Prévoir le transport** : Tenir compte du temps de déplacement

### 3. Pour les administrateurs
- **Monitorer l'utilisation** : Suivre l'utilisation de cette fonctionnalité
- **Former les utilisateurs** : Expliquer les avantages et le fonctionnement
- **Optimiser les processus** : Ajuster les workflows selon les retours

## Dépannage

### 1. Problèmes courants

#### Erreur de validation de date
**Symptôme** : Impossible de sélectionner une date
**Cause** : La date sélectionnée est antérieure à la date de la réunion principale
**Solution** : Choisir une date égale ou postérieure à la réunion principale

#### Heure non prise en compte
**Symptôme** : L'heure personnalisée n'est pas appliquée
**Cause** : Le champ heure est vide ou mal formaté
**Solution** : Vérifier le format de l'heure (HH:MM) et s'assurer qu'elle est saisie

#### Titre non personnalisé
**Symptôme** : Le titre automatique est utilisé au lieu du titre personnalisé
**Cause** : Le champ titre est vide
**Solution** : Saisir le titre personnalisé souhaité

### 2. Solutions

#### Vérification des données
- Contrôler que tous les champs obligatoires sont remplis
- Vérifier le format des dates et heures
- S'assurer que les villages sont correctement sélectionnés

#### Logs et débogage
- Consulter les logs du serveur pour les erreurs backend
- Vérifier la console du navigateur pour les erreurs frontend
- Contrôler les données envoyées dans les requêtes API

## Évolutions futures

### 1. Fonctionnalités envisagées
- **Gestion des fuseaux horaires** : Support des différents fuseaux pour les réunions internationales
- **Synchronisation calendrier** : Intégration avec les calendriers externes (Google Calendar, Outlook)
- **Notifications automatiques** : Envoi de rappels aux participants selon les horaires personnalisés

### 2. Améliorations techniques
- **Validation en temps réel** : Vérification instantanée des conflits d'horaires
- **Suggestion automatique** : Propositions d'horaires optimaux basées sur les disponibilités
- **Gestion des récurrences** : Planification de séries de sous-réunions

## Conclusion

La gestion des dates et heures lors du split des réunions offre une flexibilité maximale pour la planification des réunions par sous-régions. Cette fonctionnalité permet d'adapter les horaires aux contraintes locales tout en maintenant la cohérence globale du processus d'éclatement.

En combinant la simplicité d'utilisation avec la puissance de personnalisation, cette fonctionnalité améliore significativement l'efficacité de la planification des réunions et la satisfaction des participants.
