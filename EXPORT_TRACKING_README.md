# Système de Tracking des Exports de Paiements

## Vue d'ensemble

Ce système permet de tracker les exports des listes de paiement pour éviter les doublons et permettre aux trésorières de marquer les paiements comme effectués.

## Nouvelles fonctionnalités

### 1. Cases à cocher pour sélection multiple
- Chaque ligne de la liste de paiement a une case à cocher
- Case à cocher "Sélectionner tout" dans l'en-tête du tableau
- Mise en évidence visuelle des lignes sélectionnées (fond bleu)

### 2. Bouton d'export individuel
- Bouton d'export pour chaque liste de paiement non exportée
- Génération automatique d'une référence unique d'export
- Tracking de la date et de l'utilisateur qui a effectué l'export

### 3. Export en lot
- Sélection de plusieurs listes de paiement
- Export groupé avec référence unique
- Bouton "Exporter Sélection (X)" affiché quand des éléments sont sélectionnés

### 4. Système de tracking des exports
- **Statuts d'export** :
  - `not_exported` : Non exporté (par défaut)
  - `exported` : Exporté mais pas encore payé
  - `paid` : Exporté et payé

- **Champs de tracking** :
  - `exported_at` : Date d'export
  - `exported_by` : Utilisateur qui a exporté
  - `export_reference` : Référence unique de l'export
  - `paid_at` : Date de paiement
  - `paid_by` : Utilisateur qui a marqué comme payé

### 5. Mise à jour du statut "payé"
- Bouton "Marquer comme Payé" pour les listes exportées
- Mise à jour en lot pour plusieurs listes sélectionnées
- Changement automatique du statut d'export vers "paid"

## Structure de la base de données

### Table `meeting_payment_lists`
```sql
ALTER TABLE meeting_payment_lists ADD COLUMN exported_at TIMESTAMP NULL;
ALTER TABLE meeting_payment_lists ADD COLUMN exported_by BIGINT UNSIGNED NULL;
ALTER TABLE meeting_payment_lists ADD COLUMN export_status VARCHAR(255) DEFAULT 'not_exported';
ALTER TABLE meeting_payment_lists ADD COLUMN export_reference VARCHAR(255) NULL;
ALTER TABLE meeting_payment_lists ADD COLUMN paid_at TIMESTAMP NULL;
ALTER TABLE meeting_payment_lists ADD COLUMN paid_by BIGINT UNSIGNED NULL;

ALTER TABLE meeting_payment_lists ADD FOREIGN KEY (exported_by) REFERENCES users(id);
ALTER TABLE meeting_payment_lists ADD FOREIGN KEY (paid_by) REFERENCES users(id);
```

### Table `meeting_payment_items`
```sql
ALTER TABLE meeting_payment_items ADD COLUMN exported_at TIMESTAMP NULL;
ALTER TABLE meeting_payment_items ADD COLUMN export_reference VARCHAR(255) NULL;
ALTER TABLE meeting_payment_items ADD COLUMN paid_at TIMESTAMP NULL;
ALTER TABLE meeting_payment_items ADD COLUMN payment_reference VARCHAR(255) NULL;
```

## Workflow utilisateur

### 1. Export d'une liste
1. La trésorière sélectionne une liste de paiement
2. Clique sur le bouton d'export (icône téléchargement)
3. Le système génère une référence unique (ex: EXP_ABC12345_20250127_143022)
4. La liste est marquée comme "exported"
5. Tous les éléments de la liste sont marqués comme exportés

### 2. Export en lot
1. La trésorière coche plusieurs listes de paiement
2. Clique sur "Exporter Sélection (X)"
3. Le système exporte toutes les listes sélectionnées
4. Chaque liste reçoit sa propre référence d'export

### 3. Marquage comme payé
1. Après avoir effectué les virements, la trésorière revient sur l'interface
2. Sélectionne les listes exportées qu'elle a payées
3. Clique sur "Marquer comme Payé (X)"
4. Le système met à jour le statut vers "paid"
5. Tous les éléments de paiement sont marqués comme payés

## Prévention des doublons

- **Vérification avant export** : Le système vérifie si une liste a déjà été exportée
- **Références uniques** : Chaque export génère une référence unique avec timestamp
- **Statuts de progression** : Impossible de marquer comme payé sans avoir exporté
- **Audit trail** : Traçabilité complète des actions (qui, quand, quoi)

## Interface utilisateur

### Filtres ajoutés
- **Statut Export** : Filtre par statut d'export (Non exporté, Exporté, Payé)

### Colonnes ajoutées
- **Sélection** : Cases à cocher pour sélection multiple
- **Export** : Statut d'export avec référence et date

### Boutons ajoutés
- **Exporter Sélection** : Export des éléments sélectionnés
- **Marquer comme Payé** : Mise à jour du statut de paiement
- **Export individuel** : Export d'une liste spécifique

## Sécurité

- **Vérification des rôles** : Seuls les trésoriers et admins peuvent exporter/marquer comme payé
- **Validation des données** : Vérification de l'existence des listes avant modification
- **Logs d'audit** : Enregistrement de toutes les actions d'export et de paiement

## Utilisation

### Pour les trésorières
1. **Exporter** : Sélectionner et exporter les listes de paiement
2. **Effectuer les virements** : Utiliser les fichiers Excel générés
3. **Marquer comme payé** : Retourner sur l'interface pour confirmer les paiements

### Pour les administrateurs
1. **Suivi** : Visualiser l'état des exports et paiements
2. **Audit** : Consulter l'historique des actions
3. **Gestion** : Gérer les cas particuliers et erreurs

## Maintenance

### Nettoyage des références
- Les références d'export sont conservées pour l'audit
- Possibilité d'archivage après un certain délai

### Monitoring
- Logs des exports et paiements
- Alertes en cas d'erreur
- Statistiques d'utilisation

## Dépannage

### Liste déjà exportée
- Vérifier le statut dans la colonne "Export"
- Consulter la référence d'export
- Utiliser la fonction "Marquer comme payé" si nécessaire

### Erreur d'export
- Vérifier les permissions utilisateur
- Consulter les logs d'erreur
- Contacter l'administrateur système

### Statut incorrect
- Vérifier la progression logique des statuts
- Utiliser les boutons appropriés dans l'ordre
- Consulter l'historique des modifications
