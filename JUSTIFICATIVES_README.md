# Gestion des Pièces Justificatives des Paiements

## Vue d'ensemble

Ce système permet aux trésorières de télécharger des pièces justificatives (reçus, quittances, preuves de virement, etc.) lors du marquage des listes de paiement comme "payées". Cela garantit la traçabilité complète des paiements et facilite l'audit.

## Fonctionnalités

### 1. Upload de pièces justificatives
- **Types supportés** : PDF, JPG, JPEG, PNG, GIF, WEBP
- **Taille maximale** : 10 MB par fichier
- **Types de justificatifs** :
  - Reçu
  - Quittance
  - Preuve de virement
  - Relevé bancaire
  - Preuve Mobile Money
  - Autre

### 2. Métadonnées des justificatifs
- **Type de justificatif** : Catégorisation obligatoire
- **Description** : Explication optionnelle
- **Référence du paiement** : Numéro de référence optionnel
- **Montant justifié** : Montant optionnel en FCFA
- **Date du paiement** : Date optionnelle du paiement

### 3. Gestion des fichiers
- **Stockage sécurisé** : Fichiers stockés dans le dossier `storage/app/public/payment_justifications`
- **Noms uniques** : Génération automatique de noms de fichiers uniques
- **Téléchargement** : Possibilité de télécharger les justificatifs
- **Suppression** : Suppression des fichiers et métadonnées

### 4. Intégration avec le workflow de paiement
- **Obligation** : Au moins une pièce justificative requise pour marquer comme "payé"
- **Validation** : Vérification automatique avant changement de statut
- **Traçabilité** : Lien direct entre justificatifs et listes de paiement

## Structure de la base de données

### Table `payment_justifications`
```sql
CREATE TABLE payment_justifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    meeting_payment_list_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size BIGINT NOT NULL,
    justification_type VARCHAR(100) NOT NULL,
    description TEXT NULL,
    reference_number VARCHAR(100) NULL,
    amount DECIMAL(10,2) NULL,
    payment_date DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (meeting_payment_list_id) REFERENCES meeting_payment_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);
```

## Workflow utilisateur

### 1. Ajout d'un justificatif
1. **Ouvrir le modal** : Cliquer sur l'icône de gestion des justificatifs
2. **Sélectionner le type** : Choisir le type de justificatif approprié
3. **Uploader le fichier** : Sélectionner le fichier (PDF, image, etc.)
4. **Remplir les métadonnées** : Ajouter les informations complémentaires
5. **Valider** : Cliquer sur "Ajouter le justificatif"

### 2. Marquage comme payé
1. **Vérification** : Le système vérifie qu'il y a au moins un justificatif
2. **Validation** : Si la condition est remplie, possibilité de marquer comme payé
3. **Confirmation** : Le statut passe de "exported" à "paid"

### 3. Gestion des justificatifs
- **Visualisation** : Liste de tous les justificatifs avec métadonnées
- **Téléchargement** : Accès aux fichiers originaux
- **Suppression** : Suppression des justificatifs (avec confirmation)

## Interface utilisateur

### Modal de gestion des justificatifs
- **Formulaire d'ajout** : Interface complète pour l'upload
- **Liste des justificatifs** : Affichage des fichiers existants
- **Actions** : Téléchargement et suppression
- **Bouton de paiement** : Marquage comme payé (si conditions remplies)

### Indicateurs visuels
- **Icône de gestion** : Bouton violet pour accéder aux justificatifs
- **Statut des fichiers** : Affichage du type et de la taille
- **Prévisualisation** : Miniatures pour les images

## Sécurité et permissions

### Contrôles d'accès
- **Rôle requis** : Trésorier ou administrateur uniquement
- **Propriété** : Les utilisateurs ne peuvent supprimer que leurs propres justificatifs
- **Validation** : Vérification des types de fichiers et tailles

### Validation des données
- **Types de fichiers** : Seuls les formats autorisés sont acceptés
- **Taille des fichiers** : Limite de 10 MB par fichier
- **Métadonnées** : Validation des champs obligatoires et optionnels

## Types de justificatifs supportés

### 1. Reçu (`receipt`)
- **Usage** : Preuve de paiement en espèces ou par chèque
- **Format recommandé** : Image ou PDF
- **Informations** : Montant, date, signature

### 2. Quittance (`quittance`)
- **Usage** : Document officiel de libération de dette
- **Format recommandé** : PDF
- **Informations** : Référence, montant, date

### 3. Preuve de virement (`transfer_proof`)
- **Usage** : Confirmation de virement bancaire
- **Format recommandé** : Image ou PDF
- **Informations** : Numéro de transaction, montant, date

### 4. Relevé bancaire (`bank_statement`)
- **Usage** : Extrait de compte bancaire
- **Format recommandé** : PDF
- **Informations** : Période, transactions, solde

### 5. Preuve Mobile Money (`mobile_money_proof`)
- **Usage** : Confirmation de transfert Mobile Money
- **Format recommandé** : Image
- **Informations** : Code de transaction, montant, date

### 6. Autre (`other`)
- **Usage** : Justificatifs non standardisés
- **Format recommandé** : Selon le type de document
- **Informations** : Description détaillée requise

## API Endpoints

### 1. Récupération des justificatifs
```
GET /meeting-payments/{paymentList}/justifications
```

### 2. Upload d'un justificatif
```
POST /meeting-payments/{paymentList}/justifications
```

### 3. Mise à jour d'un justificatif
```
PUT /meeting-payments/{paymentList}/justifications/{justification}
```

### 4. Suppression d'un justificatif
```
DELETE /meeting-payments/{paymentList}/justifications/{justification}
```

### 5. Téléchargement d'un justificatif
```
GET /meeting-payments/{paymentList}/justifications/{justification}/download
```

## Gestion des erreurs

### Erreurs courantes
1. **Fichier trop volumineux** : Limite de 10 MB dépassée
2. **Type de fichier non supporté** : Format non autorisé
3. **Permissions insuffisantes** : Rôle trésorier requis
4. **Fichier corrompu** : Erreur lors de l'upload

### Messages d'erreur
- Messages clairs et explicatifs
- Suggestions de résolution
- Logs détaillés pour le débogage

## Maintenance et nettoyage

### Stockage des fichiers
- **Organisation** : Structure de dossiers hiérarchique
- **Nettoyage** : Suppression automatique des fichiers orphelins
- **Sauvegarde** : Intégration avec la stratégie de sauvegarde

### Audit et traçabilité
- **Logs** : Enregistrement de toutes les actions
- **Historique** : Traçabilité des modifications
- **Rapports** : Génération de rapports d'audit

## Bonnes pratiques

### Pour les trésorières
1. **Nommage** : Utiliser des noms de fichiers descriptifs
2. **Types** : Sélectionner le type de justificatif approprié
3. **Métadonnées** : Remplir toutes les informations disponibles
4. **Vérification** : Contrôler la qualité des fichiers uploadés

### Pour les administrateurs
1. **Surveillance** : Monitorer l'utilisation du stockage
2. **Maintenance** : Nettoyer régulièrement les fichiers orphelins
3. **Sauvegarde** : Intégrer les justificatifs dans la stratégie de sauvegarde
4. **Audit** : Vérifier régulièrement la conformité des justificatifs

## Dépannage

### Problèmes courants
1. **Upload échoue** : Vérifier la taille et le type de fichier
2. **Fichier non trouvé** : Vérifier l'existence et les permissions
3. **Erreur de téléchargement** : Vérifier l'intégrité du fichier
4. **Permissions insuffisantes** : Vérifier le rôle de l'utilisateur

### Solutions
- Vérifier les logs d'erreur
- Contrôler les permissions des dossiers
- Valider la configuration du stockage
- Tester avec des fichiers de petite taille
