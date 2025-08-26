# Commandes de Configuration du Système d'Export

## 1. Exécuter les migrations

```bash
# Exécuter les migrations pour ajouter le tracking des exports et les justificatifs
php artisan migrate

# Si vous voulez annuler les migrations
php artisan migrate:rollback
```

## 2. Vérifier la fonctionnalité de split avec dates/heures et village hôte

```bash
# Tester l'API de split des réunions
curl -X POST "http://localhost:8000/api/meetings/{meeting_id}/split" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "sub_meetings": [
      {
        "location": "Salle A",
        "villages": [{"id": 1, "name": "Village A"}],
        "host_village_id": 1,
        "scheduled_date": "2025-02-15",
        "scheduled_time": "14:00",
        "title": "Sous-réunion Village A"
      }
    ]
  }'
```

## 2. Vérifier que les tables ont été mises à jour

```bash
# Vérifier la structure de la table meeting_payment_lists
php artisan tinker --execute="Schema::getColumnListing('meeting_payment_lists')"

# Vérifier la structure de la table meeting_payment_items
php artisan tinker --execute="Schema::getColumnListing('meeting_payment_items')"
```

## 3. Mettre à jour les routes

Assurez-vous que le fichier `routes/meeting-payments.php` est inclus dans votre fichier `routes/web.php` ou `routes/api.php` :

```php
// Dans routes/web.php ou routes/api.php
require __DIR__.'/meeting-payments.php';
```

## 4. Vérifier les permissions

```bash
# Vérifier que le rôle 'tresorier' existe
php artisan tinker --execute="Spatie\Permission\Models\Role::all()"

# Créer le rôle s'il n'existe pas
php artisan tinker --execute="Spatie\Permission\Models\Role::create(['name' => 'tresorier'])"
```

## 5. Tester le système

```bash
# Lancer les tests (si vous avez créé des tests)
php artisan test

# Ou tester manuellement en accédant à l'interface
```

## 6. Vérifier les logs

```bash
# Vérifier les logs d'export
tail -f storage/logs/laravel.log | grep "Export"
```

## Structure finale des tables

### meeting_payment_lists
- `id` (bigint, auto-increment)
- `meeting_id` (bigint, index)
- `submitted_by` (bigint, nullable, index)
- `validated_by` (bigint, nullable, index)
- `submitted_at` (timestamp, nullable)
- `validated_at` (timestamp, nullable)
- `status` (string)
- `rejection_reason` (text, nullable)
- `total_amount` (decimal)
- **`exported_at` (timestamp, nullable)** ← NOUVEAU
- **`exported_by` (bigint, nullable, foreign key)** ← NOUVEAU
- **`export_status` (string, default: 'not_exported')** ← NOUVEAU
- **`export_reference` (string, nullable)** ← NOUVEAU
- **`paid_at` (timestamp, nullable)** ← NOUVEAU
- **`paid_by` (bigint, nullable, foreign key)** ← NOUVEAU
- `created_at` (timestamp)
- `updated_at` (timestamp)
- `deleted_at` (timestamp, nullable)

### meeting_payment_items
- `id` (bigint, auto-increment)
- `meeting_payment_list_id` (bigint, index)
- `attendee_id` (bigint, nullable, index)
- `amount` (decimal)
- `role` (string)
- `payment_status` (string)
- `payment_method` (string, nullable)
- `payment_date` (timestamp, nullable)
- `comments` (text, nullable)
- **`exported_at` (timestamp, nullable)** ← NOUVEAU
- **`export_reference` (string, nullable)** ← NOUVEAU
- **`paid_at` (timestamp, nullable)** ← NOUVEAU
- **`payment_reference` (string, nullable)** ← NOUVEAU
- `created_at` (timestamp)
- `updated_at` (timestamp)
- `deleted_at` (timestamp, nullable)

## Statuts d'export

- `not_exported` : Liste non exportée (état par défaut)
- `exported` : Liste exportée mais pas encore payée
- `paid` : Liste exportée et payée

## Utilisation

1. **Export individuel** : Cliquer sur l'icône d'export d'une ligne
2. **Export en lot** : Cocher plusieurs lignes et cliquer sur "Exporter Sélection"
3. **Marquer comme payé** : Après export, cocher les lignes et cliquer sur "Marquer comme Payé"

## Dépannage

### Erreur de migration
```bash
# Vérifier l'état des migrations
php artisan migrate:status

# Forcer la migration si nécessaire
php artisan migrate --force
```

### Erreur de routes
```bash
# Lister toutes les routes
php artisan route:list | grep meeting-payments

# Vider le cache des routes
php artisan route:clear
```

### Erreur de permissions
```bash
# Vérifier les rôles de l'utilisateur
php artisan tinker --execute="Auth::user()->roles"
```
