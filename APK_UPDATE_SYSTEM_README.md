# 🚀 Système de Gestion des Mises à Jour d'APK

## 📋 Vue d'ensemble

Ce système permet à l'application mobile Flutter de vérifier automatiquement les mises à jour disponibles via une API backend Laravel, et de proposer le téléchargement et l'installation des nouvelles versions directement depuis l'application.

## 🏗️ Architecture

### Backend (Laravel)
- **Modèle** : `AppVersion` - Gestion des versions d'APK
- **Contrôleur API** : `AppVersionController` - Endpoints pour la vérification des mises à jour
- **Migration** : Table `app_versions` pour stocker les informations des versions
- **Interface Admin** : Gestion des versions via l'interface web

### Frontend Mobile (Flutter)
- **Service** : `AppUpdateService` - Communication avec l'API backend
- **Modèle** : `AppUpdateInfo` - Structure des données de mise à jour
- **Widget** : `AppUpdateDialog` - Interface utilisateur pour les mises à jour
- **Gestionnaire** : `UpdateManager` - Orchestration du processus de mise à jour

## 🔧 Installation et Configuration

### 1. Backend Laravel

#### Migration
```bash
php artisan migrate
```

#### Routes API
```php
// Vérification des mises à jour
GET /api/app-versions/check-update/{currentVersion}

// Gestion des versions (admin)
GET /api/app-versions
POST /api/app-versions
GET /api/app-versions/latest
GET /api/app-versions/{id}
```

#### Permissions
- **Lecture** : Tous les utilisateurs authentifiés
- **Écriture** : Administrateurs uniquement

### 2. Frontend Mobile

#### Dépendances
```yaml
dependencies:
  package_info_plus: ^8.0.2
  install_plugin: ^2.1.0
  permission_handler: ^12.0.0+1
  fluttertoast: ^8.2.12
```

#### Installation
```bash
flutter pub get
```

## 📱 Utilisation

### 1. Vérification Automatique

#### Au Démarrage de l'App
```dart
// Dans votre main.dart ou écran principal
final updateManager = UpdateManager();
await updateManager.checkForUpdatesOnStartup(context);
```

#### Vérification Manuelle
```dart
// Depuis un bouton ou menu
await updateManager.checkForUpdatesManually(context);
```

### 2. Gestion des Versions (Admin)

#### Upload d'une Nouvelle Version
1. Accéder à l'interface admin : `/admin/app-versions`
2. Cliquer sur "Nouvelle version"
3. Remplir les champs :
   - **Code version** : Numéro entier unique (ex: 2)
   - **Nom version** : Nom lisible (ex: "2.0.0")
   - **Fichier APK** : Fichier .apk
   - **Notes de version** : Description des changements

#### Suppression d'une Version
- Cliquer sur l'icône de suppression dans la liste
- Confirmer la suppression

## 🔄 Flux de Mise à Jour

### 1. Vérification
```
App Mobile → API Backend → Base de données
     ↓
Comparaison des versions
     ↓
Retour des informations de mise à jour
```

### 2. Téléchargement
```
Utilisateur accepte → Téléchargement APK → Stockage local
     ↓
Vérification des permissions → Installation automatique
```

### 3. Installation
```
APK téléchargé → Demande d'installation → Installation système
     ↓
Redémarrage de l'application
```

## 📊 Structure des Données

### Modèle AppVersion (Backend)
```php
{
  "id": 1,
  "version_code": 2,
  "version_name": "2.0.0",
  "apk_file": "apks/colocs_v2.apk",
  "release_notes": "Nouvelles fonctionnalités...",
  "created_at": "2025-01-27T10:00:00Z",
  "updated_at": "2025-01-27T10:00:00Z"
}
```

### Modèle AppUpdateInfo (Mobile)
```dart
{
  "updateAvailable": true,
  "currentVersion": 1,
  "latestVersion": 2,
  "latestVersionName": "2.0.0",
  "downloadUrl": "https://.../storage/apks/colocs_v2.apk",
  "releaseNotes": "Nouvelles fonctionnalités...",
  "forceUpdate": false,
  "message": "Une nouvelle version est disponible"
}
```

## 🛡️ Sécurité

### Permissions Requises
- **Android** : `WRITE_EXTERNAL_STORAGE`, `REQUEST_INSTALL_PACKAGES`
- **iOS** : Non supporté (App Store uniquement)

### Validation des Fichiers
- **Type** : Uniquement fichiers .apk
- **Taille** : Limite configurable (défaut : 100MB)
- **Authentification** : Token Bearer requis

## ⚙️ Configuration

### Intervalle de Vérification
```dart
// Dans UpdateManager
static const Duration _checkInterval = Duration(hours: 24);
```

### Permissions Android
```xml
<!-- android/app/src/main/AndroidManifest.xml -->
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.REQUEST_INSTALL_PACKAGES" />
```

## 🚨 Gestion des Erreurs

### Erreurs Communes
1. **Pas de connexion internet** : Message informatif
2. **Permission refusée** : Demande de permission
3. **Téléchargement échoué** : Retry automatique
4. **Installation échouée** : Instructions manuelles

### Logs
```dart
print('📱 Mise à jour disponible: ${updateInfo.latestVersionName}');
print('❌ Erreur lors de la vérification: $e');
```

## 🔄 Mise à Jour du Système

### Ajout de Nouvelles Fonctionnalités
1. **Champ force_update** : Mises à jour obligatoires
2. **Notifications push** : Alertes de mises à jour
3. **Mise à jour en arrière-plan** : Téléchargement automatique
4. **Rollback** : Retour à la version précédente

### Optimisations
1. **Cache** : Stockage des informations de version
2. **Différentiel** : Mises à jour partielles
3. **Compression** : Réduction de la taille des APK

## 📝 Notes de Développement

### Dépendances Manquantes
- `package_info_plus` : Pour obtenir la version actuelle de l'app
- `install_plugin` : Pour l'installation automatique des APK

### Limitations Actuelles
- Installation automatique non implémentée (simulation)
- Version actuelle codée en dur (valeur par défaut: 1)
- Pas de gestion des erreurs de téléchargement

### Prochaines Étapes
1. Installer les dépendances manquantes
2. Implémenter l'installation automatique
3. Ajouter la gestion des erreurs de téléchargement
4. Tester le système complet

## 🧪 Tests

### Test de l'API
```bash
# Vérifier la dernière version
curl -X GET "http://localhost/api/app-versions/latest"

# Vérifier les mises à jour
curl -X GET "http://localhost/api/app-versions/check-update/1"
```

### Test Mobile
1. Créer une nouvelle version dans l'admin
2. Lancer l'app mobile
3. Vérifier l'affichage du dialogue de mise à jour
4. Tester le téléchargement

## 📚 Ressources

- [Documentation Flutter](https://flutter.dev/docs)
- [Documentation Laravel](https://laravel.com/docs)
- [Plugin install_plugin](https://pub.dev/packages/install_plugin)
- [Plugin package_info_plus](https://pub.dev/packages/package_info_plus)
