# Système de Paiements Automatiques des Cadres

## Vue d'ensemble

Le système génère automatiquement des paiements pour les cadres (secrétaires et sous-préfets) lors de la validation des réunions. Les cadres sont payés pour l'organisation et la validation des réunions, pas pour leur présence physique.

## Logique de génération

- **Condition** : Un paiement est généré pour chaque groupe de 2 réunions validées
- **Bénéficiaires** : Tous les secrétaires et sous-préfets de la localité du comité local
- **Montants** :
  - Secrétaire : 10 000 FCFA par paiement
  - Sous-préfet : 15 000 FCFA par paiement

## Nouvelles fonctionnalités (v2.0)

### 1. Affichage des réunions déclencheuses
- Chaque paiement affiche maintenant les **2 réunions** qui ont généré le paiement
- Stockage dans le champ `triggering_meetings` (JSON) avec les IDs des réunions
- Affichage formaté : "Titre Réunion 1 (date) et Titre Réunion 2 (date)"

### 2. Gestion des statuts de paiement
- **Statuts disponibles** : `pending`, `validated`, `paid`, `cancelled`
- **Modification via interface** : Boutons d'action pour changer le statut
- **Historique** : Traçabilité des dates et utilisateurs qui ont validé/payé
- **Route** : `PATCH /executive-payments/{id}/status`

### 3. Export des données
- **Export tous les paiements** : `GET /executive-payments/export/all`
- **Export paiements non effectués** : `GET /executive-payments/export/pending`
- **Formats** : JSON (pour génération Excel/CSV côté frontend)
- **Filtres** : Par rôle, statut, date de création
- **Données exportées** :
  - ID du paiement
  - Nom du cadre
  - Rôle (Secrétaire/Sous-préfet)
  - Montant
  - Statut
  - Réunions déclencheuses
  - Comité local
  - Dates (création, validation, paiement)

## Taux de paiement

Les taux sont configurés dans la table `payment_rates` :

```sql
INSERT INTO payment_rates (role, meeting_rate, is_active) VALUES
('secretaire', 10000, true),
('sous-prefet', 15000, true);
```

## Génération automatique

### Via l'interface web

Les paiements sont automatiquement générés lors de la validation d'une réunion par un sous-préfet ou un administrateur.

### Via la ligne de commande

#### Générer pour toutes les réunions validées

```bash
php artisan payments:generate-executive
```

#### Générer pour une réunion spécifique

```bash
php artisan payments:generate-executive --meeting-id=123
```

#### Mode simulation (dry-run)

```bash
php artisan payments:generate-executive --dry-run
```

#### Combiner les options

```bash
php artisan payments:generate-executive --meeting-id=123 --dry-run
```

## Gestion des statuts

### Changer le statut d'un paiement

```javascript
// Exemple d'appel AJAX
fetch(`/executive-payments/${paymentId}/status`, {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        payment_status: 'validated' // ou 'paid', 'cancelled'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Statut mis à jour:', data.message);
});
```

### Statuts disponibles

- **pending** : En attente (statut initial)
- **validated** : Validé par un gestionnaire
- **paid** : Payé
- **cancelled** : Annulé

## Export des données

### Export tous les paiements

```javascript
fetch('/executive-payments/export/all?role=secretaire&payment_status=pending')
.then(response => response.json())
.then(data => {
    console.log('Données exportées:', data.data);
    console.log('Total:', data.total_amount);
});
```

### Export paiements non effectués

```javascript
fetch('/executive-payments/export/pending?role=sous-prefet')
.then(response => response.json())
.then(data => {
    console.log('Paiements en attente:', data.data);
});
```

## Prévention des doublons

Le système vérifie automatiquement si des paiements existent déjà pour éviter les doublons. Un cadre ne peut pas recevoir plusieurs paiements pour le même groupe de réunions.

## Tables concernées

- `meetings` : Réunions validées
- `users` : Cadres (secrétaires et sous-préfets)
- `payment_rates` : Taux de paiement par rôle
- `meeting_payments` : Paiements générés (avec nouveaux champs)

## Nouveaux champs dans meeting_payments

- `payment_status` : Statut du paiement (pending, validated, paid, cancelled)
- `triggering_meetings` : JSON des IDs des 2 réunions déclencheuses
- `validated_at` : Date de validation
- `validated_by` : ID de l'utilisateur qui a validé
- `paid_at` : Date de paiement
- `paid_by` : ID de l'utilisateur qui a payé

## Exemple de calcul

Pour un comité local avec :
- 1 secrétaire
- 1 sous-préfet
- 4 réunions validées

Résultat :
- 2 paiements pour le secrétaire (4 réunions ÷ 2 = 2 groupes)
- 2 paiements pour le sous-préfet
- Total : 4 paiements
- Montant total : (2 × 10 000) + (2 × 15 000) = 50 000 FCFA

## Dépannage

### Aucun paiement généré

1. Vérifier que les taux de paiement existent :
   ```bash
   php artisan tinker --execute="App\Models\PaymentRate::all()"
   ```

2. Vérifier que les cadres ont les bons rôles :
   ```bash
   php artisan tinker --execute="App\Models\User::role('secretaire')->count()"
   php artisan tinker --execute="App\Models\User::role('sous-prefet')->count()"
   ```

3. Vérifier qu'il y a des réunions validées :
   ```bash
   php artisan tinker --execute="App\Models\Meeting::where('status', 'validated')->count()"
   ```

### Paiements en double

Le système empêche automatiquement les doublons. Si des doublons existent, ils peuvent être supprimés manuellement :

```bash
php artisan tinker --execute="App\Models\MeetingPayment::where('role', 'secretaire')->delete()"
```

### Vérifier les réunions déclencheuses

```bash
php artisan tinker --execute="App\Models\MeetingPayment::with('meeting')->get()->each(function(\$p) { echo 'Paiement ' . \$p->id . ': ' . json_encode(\$p->triggering_meetings) . PHP_EOL; });"
```

## Logs

Les erreurs de génération sont loggées dans les logs Laravel avec le contexte de la réunion concernée. 