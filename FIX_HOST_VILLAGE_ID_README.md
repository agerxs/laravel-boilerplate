# Correction de l'erreur "Undefined array key host_village_id"

## Problème identifié

L'erreur `Undefined array key "host_village_id"` se produisait lors de l'éclatement de réunions car :

1. **Incohérence de validation** : Le contrôleur `MeetingController::splitMeetingApi` ne validait pas le champ `host_village_id` requis
2. **Service dépendant** : Le `MeetingSplitService` utilisait ce champ sans vérification préalable
3. **Format de données différent** : Les méthodes web et API utilisaient des structures de données différentes

## Solution appliquée

### 1. Correction de la validation dans MeetingController::splitMeetingApi

```php
// Avant (incomplet)
$validated = $request->validate([
    'sub_meetings' => 'required|array|min:1',
    'sub_meetings.*.location' => 'required|string',
    'sub_meetings.*.villages' => 'required|array|min:1',
    'sub_meetings.*.villages.*.id' => 'required|exists:localite,id',
    'sub_meetings.*.villages.*.name' => 'required|string',
]);

// Après (complet)
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

### 2. Correction de la validation dans MeetingController::splitMeeting

```php
// Avant (incomplet)
$validated = $request->validate([
    'sub_regions' => 'required|array|min:1',
    'sub_regions.*.id' => 'required|exists:localite,id',
    'sub_regions.*.name' => 'required|string',
    'sub_regions.*.villages' => 'required|array|min:1',
    'sub_regions.*.villages.*.id' => 'required|exists:localite,id',
    'sub_regions.*.location' => 'nullable|string',
]);

// Après (complet)
$validated = $request->validate([
    'sub_regions' => 'required|array|min:1',
    'sub_regions.*.id' => 'required|exists:localite,id',
    'sub_regions.*.name' => 'required|string',
    'sub_regions.*.villages' => 'required|array|min:1',
    'sub_regions.*.villages.*.id' => 'required|exists:localite,id',
    'sub_regions.*.location' => 'nullable|string',
    'sub_regions.*.host_village_id' => 'required|exists:localite,id',
    'sub_regions.*.scheduled_date' => 'nullable|date',
    'sub_regions.*.scheduled_time' => 'nullable|date_format:H:i',
    'sub_regions.*.title' => 'nullable|string|max:255',
]);
```

## Tests ajoutés

### 1. Test de validation du champ requis

```php
public function test_split_meeting_requires_host_village_id()
{
    // Test que l'API rejette les requêtes sans host_village_id
    $response = $this->postJson(route('api.meetings.split', $meeting->id), [
        'sub_meetings' => [
            [
                'location' => 'Salle A',
                'villages' => [/* villages */],
                // host_village_id manquant intentionnellement
            ]
        ]
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['sub_meetings.0.host_village_id']);
}
```

### 2. Test de fonctionnement avec le champ

```php
public function test_split_meeting_works_with_host_village_id()
{
    // Test que l'API accepte les requêtes avec host_village_id
    $response = $this->postJson(route('api.meetings.split', $meeting->id), [
        'sub_meetings' => [
            [
                'location' => 'Salle A',
                'villages' => [/* villages */],
                'host_village_id' => $village->id,
                'title' => 'Sous-réunion Test'
            ]
        ]
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
}
```

## Structure des données requises

### Format API (sub_meetings)

```json
{
  "sub_meetings": [
    {
      "location": "Salle de réunion",
      "villages": [
        {"id": 1, "name": "Village A"},
        {"id": 2, "name": "Village B"}
      ],
      "host_village_id": 1,
      "scheduled_date": "2025-02-15",
      "scheduled_time": "14:00",
      "title": "Sous-réunion personnalisée"
    }
  ]
}
```

### Format Web (sub_regions)

```json
{
  "sub_regions": [
    {
      "id": 1,
      "name": "Sous-région A",
      "villages": [
        {"id": 1, "name": "Village A"},
        {"id": 2, "name": "Village B"}
      ],
      "location": "Salle de réunion",
      "host_village_id": 1,
      "scheduled_date": "2025-02-15",
      "scheduled_time": "14:00",
      "title": "Sous-réunion personnalisée"
    }
  ]
}
```

## Vérification de la correction

1. **Validation côté serveur** : Les contrôleurs valident maintenant tous les champs requis
2. **Cohérence des formats** : Les deux méthodes d'éclatement utilisent la même structure de validation
3. **Tests automatisés** : Les tests vérifient le bon fonctionnement et la validation
4. **Application mobile** : L'app Flutter envoie déjà le champ `host_village_id` avec une valeur par défaut

## Impact

- ✅ **Erreur résolue** : Plus d'erreur "Undefined array key host_village_id"
- ✅ **Validation robuste** : Tous les champs requis sont validés
- ✅ **Cohérence** : Format uniforme entre API et web
- ✅ **Tests** : Couverture de test pour éviter la régression
- ✅ **Rétrocompatibilité** : L'application mobile continue de fonctionner

## Prévention des régressions

1. **Tests automatisés** : Exécuter `php artisan test --filter=MeetingSplitTest`
2. **Validation des données** : Vérifier que tous les champs requis sont validés
3. **Cohérence des formats** : Maintenir la même structure entre API et web
4. **Documentation** : Mettre à jour la documentation des API
