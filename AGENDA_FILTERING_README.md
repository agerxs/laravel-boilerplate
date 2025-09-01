# Filtrage de l'Agenda - Masquage des Réunions Principales

## Objectif
Masquer les réunions principales (parent) dans l'agenda et n'afficher que les sous-réunions et les réunions normales sans sous-réunions.

## Modifications Apportées

### 1. Contrôleur CalendarController

**Fichier** : `app/Http/Controllers/CalendarController.php`

#### Avant
```php
$meetings = Meeting::with('localCommittee')
    ->orderBy('scheduled_date', 'asc')
    ->get()
    ->map(function ($meeting) {
        // Mapping simple des réunions
    });
```

#### Après
```php
// Récupérer toutes les réunions avec leurs relations
$allMeetings = Meeting::with(['localCommittee', 'subMeetings'])
    ->orderBy('scheduled_date', 'asc')
    ->get();

// Filtrer pour exclure les réunions parent et inclure seulement les sous-réunions
$meetings = $allMeetings->flatMap(function ($meeting) {
    // Si c'est une réunion parent avec des sous-réunions, on ne l'affiche pas
    if ($meeting->isParentMeeting() && $meeting->subMeetings()->count() > 0) {
        return collect(); // Retourner une collection vide
    }
    
    // Si c'est une sous-réunion, on l'affiche
    if ($meeting->isSubMeeting()) {
        return collect([$meeting]);
    }
    
    // Si c'est une réunion normale sans sous-réunions, on l'affiche
    if ($meeting->isParentMeeting() && $meeting->subMeetings()->count() === 0) {
        return collect([$meeting]);
    }
    
    return collect(); // Par défaut, ne rien afficher
})->map(function ($meeting) {
    return [
        'id' => $meeting->id,
        'title' => $meeting->title,
        'scheduled_date' => $meeting->scheduled_date->format('Y-m-d H:i:s'),
        'location' => $meeting->localCommittee?->locality?->name ?? 'Non défini',
        'status' => $meeting->status,
        'is_sub_meeting' => $meeting->isSubMeeting(),
        'parent_meeting_title' => $meeting->isSubMeeting() ? $meeting->parentMeeting?->title : null,
        'local_committee' => $meeting->localCommittee ? [
            'id' => $meeting->localCommittee->id,
            'name' => $meeting->localCommittee->name,
        ] : null,
    ];
});
```

### 2. Vue Vue.js

**Fichier** : `resources/js/Pages/Calendar/Index.vue`

#### Interface TypeScript
```typescript
interface Meeting {
  id: number
  title: string
  scheduled_date: string
  end_datetime: string
  location: string
  status: string
  is_sub_meeting: boolean          // Nouveau
  parent_meeting_title?: string    // Nouveau
  local_committee?: {
    id: number
    name: string
  }
}
```

#### Affichage dans le Calendrier
```vue
<div class="flex items-center space-x-1">
  <span v-if="attr.customData.meeting.is_sub_meeting" 
        class="text-xs bg-blue-100 text-blue-700 px-1 rounded-full">
    ⚡
  </span>
  <span class="truncate">
    {{ attr.customData.meeting.title }}
  </span>
</div>
<div class="text-xs opacity-75">
  {{ formatTime(attr.customData.meeting.scheduled_date) }}
</div>
```

#### Modal des Détails
```vue
<h3 class="text-lg font-medium text-gray-900 mb-4">
  {{ selectedMeeting.title }}
  <span v-if="selectedMeeting.is_sub_meeting" 
        class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
    ⚡ Sous-réunion
  </span>
</h3>

<!-- Afficher la réunion parent si c'est une sous-réunion -->
<div v-if="selectedMeeting.is_sub_meeting && selectedMeeting.parent_meeting_title" 
     class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
  <p class="text-sm text-blue-800">
    <strong>Réunion principale :</strong> {{ selectedMeeting.parent_meeting_title }}
  </p>
</div>
```

## Logique de Filtrage

### Règles Appliquées

1. **Réunions parent avec sous-réunions** : ❌ **Masquées**
   - Ces réunions ne sont pas affichées dans l'agenda
   - Seules leurs sous-réunions sont visibles

2. **Sous-réunions** : ✅ **Affichées**
   - Indiquées par une icône ⚡ bleue
   - Affichent le titre de la réunion parent dans le modal
   - Conservent toutes leurs informations (date, lieu, statut)

3. **Réunions normales sans sous-réunions** : ✅ **Affichées**
   - Réunions classiques qui ne sont pas éclatées
   - Affichage normal sans indicateur spécial

### Avantages

- **Clarté** : L'agenda ne montre que les réunions concrètes
- **Évite la confusion** : Plus de doublons entre réunions parent et sous-réunions
- **Contexte préservé** : Les sous-réunions indiquent clairement leur réunion parent
- **UX améliorée** : Interface plus claire et moins encombrée

## Tests

### Scénarios à Vérifier

1. **Réunion parent avec sous-réunions** → Ne doit pas apparaître dans l'agenda
2. **Sous-réunion** → Doit apparaître avec l'icône ⚡ et le lien vers la réunion parent
3. **Réunion normale** → Doit apparaître normalement
4. **Navigation** → Les liens vers les détails doivent fonctionner correctement

### Commandes de Test

```bash
# Compiler l'application
npm run build

# Vérifier que les routes fonctionnent
php artisan route:list --name=admin

# Tester l'accès à l'agenda
# Aller sur /admin/calendar ou la route appropriée
```

## Maintenance

### Ajout de Nouvelles Fonctionnalités

Pour ajouter de nouveaux indicateurs visuels ou informations :

1. **Backend** : Modifier le mapping dans `CalendarController`
2. **Frontend** : Mettre à jour l'interface TypeScript et l'affichage
3. **Tests** : Vérifier les nouveaux comportements

### Dépannage

Si des réunions parent apparaissent encore dans l'agenda :

1. Vérifier que la relation `subMeetings` est bien chargée
2. Vérifier que la méthode `isParentMeeting()` fonctionne correctement
3. Vérifier que le cache est vidé après les modifications

**Responsable** : Assistant IA Claude  
**Date** : Décembre 2024
