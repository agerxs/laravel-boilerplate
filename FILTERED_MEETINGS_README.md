# Filtrage des Réunions par Comité Local

## Vue d'ensemble

Cette fonctionnalité améliore l'expérience utilisateur en filtrant automatiquement les réunions disponibles dans le dropdown en fonction du comité local sélectionné. Cela évite de montrer des réunions non pertinentes et simplifie la sélection.

## Fonctionnalités

### 1. Filtrage automatique des réunions
- **Sélection du comité local** : L'utilisateur sélectionne d'abord un comité local
- **Filtrage des réunions** : Le dropdown des réunions se met à jour automatiquement
- **Réunions pertinentes** : Seules les réunions du comité local sélectionné sont affichées

### 2. Interface utilisateur intuitive
- **Dropdown désactivé** : Le dropdown des réunions est désactivé tant qu'aucun comité local n'est sélectionné
- **Message d'aide** : Texte explicatif dans le dropdown des réunions
- **Compteur de réunions** : Affichage du nombre de réunions disponibles
- **Message d'erreur** : Alerte si aucune réunion n'est trouvée

### 3. Réinitialisation automatique
- **Changement de comité** : La sélection de réunion est automatiquement réinitialisée
- **Cohérence des données** : Évite les sélections incohérentes
- **Expérience fluide** : Navigation intuitive entre les filtres

## Implémentation technique

### 1. Computed Property pour le filtrage

#### Vue.js
```javascript
// Computed pour filtrer les réunions en fonction du comité local sélectionné
const filteredMeetings = computed(() => {
  if (!filters.local_committee_id) {
    return props.meetings
  }
  
  return props.meetings.filter(meeting => 
    meeting.local_committee_id === parseInt(filters.local_committee_id)
  )
})
```

#### Logique
- **Sans filtre** : Retourne toutes les réunions si aucun comité local n'est sélectionné
- **Avec filtre** : Filtre les réunions par `local_committee_id`
- **Conversion de type** : Utilise `parseInt()` pour la comparaison

### 2. Surveillance des changements de filtres

#### Watch avec logique de réinitialisation
```javascript
// Surveiller les changements de filtres
watch(filters, (newFilters, oldFilters) => {
  // Si le comité local change, réinitialiser la sélection de réunion
  if (oldFilters && oldFilters.local_committee_id !== newFilters.local_committee_id) {
    filters.meeting_id = ''
  }
  
  router.get(route('meeting-payments.lists.index'), newFilters, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, { deep: true })
```

#### Fonctionnalités
- **Détection de changement** : Compare l'ancien et le nouveau comité local
- **Réinitialisation automatique** : Vide la sélection de réunion
- **Requête serveur** : Met à jour les données avec les nouveaux filtres

### 3. Interface utilisateur améliorée

#### Template avec conditions
```vue
<label class="block text-sm font-medium text-gray-700 mb-1">
  Réunion 
  <span v-if="filters.local_committee_id" class="text-xs text-gray-500 ml-1">
    ({{ filteredMeetings.length }} disponible{{ filteredMeetings.length > 1 ? 's' : '' }})
  </span>
</label>

<select 
  v-model="filters.meeting_id" 
  :disabled="!filters.local_committee_id"
  :class="[
    'w-full border-gray-300 rounded-md shadow-sm',
    !filters.local_committee_id ? 'bg-gray-100 cursor-not-allowed' : ''
  ]"
>
  <option value="">
    {{ filters.local_committee_id ? 'Toutes les réunions' : 'Sélectionnez d\'abord un comité local' }}
  </option>
  <option v-for="meeting in filteredMeetings" :key="meeting.id" :value="meeting.id">
    {{ meeting.title }} - {{ formatDate(meeting.scheduled_date) }}
  </option>
</select>

<div v-if="filters.local_committee_id && filteredMeetings.length === 0" class="text-xs text-red-500 mt-1">
  Aucune réunion trouvée pour ce comité local
</div>
```

#### Caractéristiques
- **Label dynamique** : Affiche le nombre de réunions disponibles
- **Dropdown conditionnel** : Désactivé si aucun comité local n'est sélectionné
- **Styles adaptatifs** : Couleur de fond et curseur adaptés à l'état
- **Messages contextuels** : Texte d'aide et messages d'erreur

## Utilisation

### 1. Sélection du comité local
1. **Ouvrir le dropdown** : Cliquer sur le dropdown "Comité Local"
2. **Choisir un comité** : Sélectionner le comité local souhaité
3. **Validation automatique** : Le filtre est appliqué automatiquement

### 2. Sélection de la réunion
1. **Dropdown activé** : Le dropdown des réunions devient actif
2. **Réunions filtrées** : Seules les réunions du comité local sont affichées
3. **Compteur visible** : Le nombre de réunions disponibles est affiché
4. **Sélection** : Choisir la réunion souhaitée

### 3. Changement de comité local
1. **Nouvelle sélection** : Changer le comité local sélectionné
2. **Réinitialisation automatique** : La réunion sélectionnée est automatiquement désélectionnée
3. **Nouveau filtrage** : Les réunions sont filtrées selon le nouveau comité

## Avantages

### 1. Expérience utilisateur améliorée
- **Navigation intuitive** : Processus de sélection logique et séquentiel
- **Réduction des erreurs** : Impossible de sélectionner une réunion incohérente
- **Feedback visuel** : Indicateurs clairs sur l'état des filtres

### 2. Performance et efficacité
- **Filtrage côté client** : Pas de requêtes serveur supplémentaires
- **Réactivité** : Mise à jour instantanée des options disponibles
- **Optimisation** : Seules les données pertinentes sont affichées

### 3. Maintenance et cohérence
- **Logique centralisée** : Filtrage géré dans un computed property
- **Réinitialisation automatique** : Évite les états incohérents
- **Code maintenable** : Structure claire et modulaire

## Cas d'usage

### 1. Utilisateur standard
**Scénario** : Un utilisateur veut voir les listes de paiement d'une réunion spécifique.

**Processus** :
1. Sélectionner le comité local de son village
2. Voir uniquement les réunions de ce comité local
3. Choisir la réunion souhaitée parmi les options filtrées

### 2. Trésorier
**Scénario** : Un trésorier veut exporter les listes de paiement d'un comité local.

**Processus** :
1. Sélectionner le comité local cible
2. Voir toutes les réunions disponibles pour ce comité
3. Sélectionner la réunion pour l'export

### 3. Administrateur
**Scénario** : Un administrateur veut analyser les paiements par comité local.

**Processus** :
1. Sélectionner le comité local à analyser
2. Voir les réunions de ce comité
3. Analyser les données filtrées

## Gestion des erreurs

### 1. Aucune réunion trouvée
**Symptôme** : Le comité local sélectionné n'a pas de réunions.

**Solution** : Affichage d'un message d'erreur explicite.

**Code** :
```vue
<div v-if="filters.local_committee_id && filteredMeetings.length === 0" class="text-xs text-red-500 mt-1">
  Aucune réunion trouvée pour ce comité local
</div>
```

### 2. Comité local non sélectionné
**Symptôme** : L'utilisateur essaie de sélectionner une réunion sans comité local.

**Solution** : Désactivation du dropdown avec message d'aide.

**Code** :
```vue
<select 
  :disabled="!filters.local_committee_id"
  :class="[
    'w-full border-gray-300 rounded-md shadow-sm',
    !filters.local_committee_id ? 'bg-gray-100 cursor-not-allowed' : ''
  ]"
>
  <option value="">
    {{ filters.local_committee_id ? 'Toutes les réunions' : 'Sélectionnez d\'abord un comité local' }}
  </option>
</select>
```

## Tests

### 1. Test de base
- [ ] Sélection d'un comité local active le dropdown des réunions
- [ ] Seules les réunions du comité local sont affichées
- [ ] Le compteur de réunions est correct

### 2. Test de changement
- [ ] Changement de comité local réinitialise la sélection de réunion
- [ ] Les réunions sont correctement filtrées après le changement
- [ ] L'interface se met à jour correctement

### 3. Test des cas limites
- [ ] Comité local sans réunions affiche le message d'erreur
- [ ] Désélection du comité local désactive le dropdown des réunions
- [ ] Réinitialisation des filtres fonctionne correctement

## Évolutions futures

### 1. Améliorations possibles
- **Filtrage multi-comités** : Permettre la sélection de plusieurs comités locaux
- **Recherche dans les réunions** : Ajouter un champ de recherche textuelle
- **Tri des réunions** : Permettre le tri par date, titre, etc.

### 2. Optimisations techniques
- **Mise en cache** : Mettre en cache les réunions filtrées
- **Pagination** : Gérer les grandes listes de réunions
- **Synchronisation** : Mise à jour en temps réel des données

## Conclusion

Le filtrage automatique des réunions par comité local améliore significativement l'expérience utilisateur en simplifiant la navigation et en évitant les sélections incohérentes. Cette fonctionnalité rend l'interface plus intuitive et efficace pour la gestion des listes de paiement.

En combinant le filtrage côté client avec la réinitialisation automatique, cette implémentation offre une expérience utilisateur fluide et cohérente tout en maintenant les performances de l'application.
