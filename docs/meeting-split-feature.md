# Fonctionnalité d'éclatement des réunions

## Vue d'ensemble

La fonctionnalité d'éclatement des réunions permet de diviser une réunion principale en plusieurs sous-réunions par sous-régions géographiques. Cette fonctionnalité est particulièrement utile pour les grosses sous-préfectures où il n'est pas possible de réunir tous les participants en une seule fois.

## Fonctionnalités principales

### 1. Éclatement intelligent
- Division automatique par sous-régions géographiques
- Assignation automatique des participants selon leurs villages
- Calcul automatique des participants attendus par sous-réunion

### 2. Gestion des relations parent-enfant
- Réunion parent → Sous-réunions enfants
- Consolidation des résultats vers la réunion parent
- Suivi de l'état de toutes les sous-réunions

### 3. Interface utilisateur intuitive
- Sélection visuelle des sous-régions
- Prévisualisation des villages inclus
- Personnalisation du lieu par sous-réunion

## Utilisation côté web

### Accès à la fonctionnalité

1. **Accéder à une réunion** : Naviguez vers la page de détail d'une réunion
2. **Vérifier l'éligibilité** : La réunion doit être en statut "Planifiée" ou "Programmée"
3. **Bouton d'éclatement** : Cliquez sur le bouton "Éclater la réunion" dans la section des actions

### Processus d'éclatement

1. **Sélection des sous-régions** :
   - Consultez la liste des sous-régions disponibles
   - Sélectionnez les sous-régions que vous souhaitez inclure
   - Chaque sous-région affiche ses villages inclus

2. **Personnalisation** :
   - Spécifiez un lieu spécifique pour chaque sous-réunion (optionnel)
   - Vérifiez le nombre de participants par sous-réunion

3. **Confirmation** :
   - Cliquez sur "Éclater la réunion"
   - Le système crée automatiquement les sous-réunions
   - Redirection vers la page de la réunion parent

### Visualisation des résultats

Après l'éclatement, la page de la réunion parent affiche :

- **Section des sous-réunions** : Liste de toutes les sous-réunions créées
- **Statistiques consolidées** : Total des participants attendus et présents
- **Liens directs** : Accès rapide aux détails de chaque sous-réunion

## Utilisation côté mobile

### Accès à la fonctionnalité

1. **Navigation** : Accédez à la liste des réunions
2. **Sélection** : Choisissez une réunion éligible à l'éclatement
3. **Menu d'actions** : Utilisez l'option "Éclater la réunion"

### Interface mobile

- **Sélection tactile** : Cases à cocher pour les sous-régions
- **Expansion des détails** : Tap pour voir les villages de chaque sous-région
- **Feedback visuel** : Indicateurs de chargement et de succès

## Conditions d'éligibilité

Une réunion peut être éclatée si :

- ✅ Elle est en statut "Planifiée" ou "Programmée"
- ✅ Elle n'a pas encore de sous-réunions
- ✅ L'utilisateur a les droits de secrétaire
- ✅ La réunion a des participants assignés

## Gestion des données

### Structure des données

```php
// Réunion parent
{
  "id": 1,
  "title": "Réunion principale",
  "parent_meeting_id": null,
  "sub_meetings": [...]
}

// Sous-réunion
{
  "id": 2,
  "title": "Sous-réunion - Région A",
  "parent_meeting_id": 1,
  "location": "Mairie de la région A"
}
```

### Relations

- **Parent → Enfants** : `hasMany` (subMeetings)
- **Enfant → Parent** : `belongsTo` (parentMeeting)
- **Participants** : Assignés automatiquement selon les villages

## API Endpoints

### Récupération des sous-régions
```
GET /api/meetings/{meeting}/split/sub-regions
```

### Éclatement d'une réunion
```
POST /api/meetings/{meeting}/split
```

### Consolidation des résultats
```
POST /api/meetings/{meeting}/split/consolidate
```

### Détails avec sous-réunions
```
GET /api/meetings/{meeting}/split/details
```

## Cas d'usage typiques

### Exemple 1 : Grande sous-préfecture
- **Contexte** : 50 villages, 200 participants attendus
- **Solution** : 4 sous-réunions de 12-13 villages chacune
- **Avantage** : Gestion locale, meilleure participation

### Exemple 2 : Zones géographiques distinctes
- **Contexte** : Villages dispersés sur de grandes distances
- **Solution** : Sous-réunions par zones géographiques
- **Avantage** : Réduction des temps de déplacement

### Exemple 3 : Capacité d'accueil limitée
- **Contexte** : Salle de réunion avec capacité limitée
- **Solution** : Division selon la capacité d'accueil
- **Avantage** : Respect des contraintes logistiques

## Bonnes pratiques

### Avant l'éclatement
1. **Vérifiez les participants** : Assurez-vous que tous les participants sont assignés
2. **Planifiez les lieux** : Préparez les lieux pour chaque sous-réunion
3. **Communiquez** : Informez les participants des changements

### Après l'éclatement
1. **Vérifiez les données** : Contrôlez que les participants sont bien répartis
2. **Gérez les sous-réunions** : Suivez chaque sous-réunion individuellement
3. **Consolidez** : Utilisez la fonction de consolidation pour les résultats finaux

## Dépannage

### Problèmes courants

**Erreur : "Cette réunion ne peut pas être éclatée"**
- Vérifiez le statut de la réunion
- Assurez-vous qu'elle n'a pas déjà des sous-réunions

**Erreur : "Aucune sous-région disponible"**
- Vérifiez que la réunion a des participants assignés
- Contrôlez la configuration géographique

**Sous-réunions vides**
- Vérifiez l'assignation des participants aux villages
- Contrôlez la configuration des sous-régions

### Support technique

Pour toute question technique :
- Consultez les logs d'application
- Vérifiez la configuration de la base de données
- Contactez l'équipe de développement

## Évolutions futures

### Fonctionnalités prévues
- **Éclatement automatique** : Suggestion automatique de division
- **Templates de sous-réunions** : Modèles prédéfinis
- **Notifications** : Alertes automatiques aux participants
- **Rapports consolidés** : Génération automatique de rapports

### Améliorations techniques
- **Performance** : Optimisation pour les grosses réunions
- **Interface** : Amélioration de l'expérience utilisateur
- **Mobile** : Fonctionnalités avancées sur mobile 