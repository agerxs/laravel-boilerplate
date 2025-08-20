# Test des emails avec Mailtrap

## 🎯 Objectif

Tester la configuration des emails de l'application avec Mailtrap pour vérifier que l'envoi d'emails fonctionne correctement.

## 🚀 Méthodes de test

### **1. Commande Artisan personnalisée** (Recommandée)

Nous avons créé une commande Artisan spécialement pour tester les emails : `test:email-sending`

#### **Utilisation de base**
```bash
# Test avec email par défaut (test@example.com)
php artisan test:email-sending

# Test avec un email spécifique
php artisan test:email-sending --email=votre-email@example.com

# Test d'un type spécifique
php artisan test:email-sending --type=simple
php artisan test:email-sending --type=minutes
php artisan test:email-sending --type=notification

# Test avec une réunion spécifique
php artisan test:email-sending --meeting_id=123 --type=minutes
```

#### **Types de tests disponibles**

##### **📋 `--type=minutes` (Par défaut)**
- Teste l'envoi d'un email de compte rendu de réunion
- Utilise la classe `MeetingMinutesSent`
- Inclut un PDF en pièce jointe
- Crée automatiquement un compte rendu de test si nécessaire

##### **📧 `--type=simple`**
- Teste l'envoi d'un email simple
- Utilise `Mail::raw()` pour un test basique
- Idéal pour vérifier la configuration SMTP

##### **🔔 `--type=notification`**
- Teste l'envoi d'une notification par email
- Crée un utilisateur de test si nécessaire
- Simule une notification système

#### **Exemples d'utilisation**

```bash
# Test complet avec email personnalisé
php artisan test:email-sending --email=test@mailtrap.io --type=minutes

# Test simple pour vérifier la configuration
php artisan test:email-sending --type=simple --email=admin@example.com

# Test avec une réunion existante
php artisan test:email-sending --meeting_id=5 --type=minutes --email=secretaire@test.com
```

### **2. Test avec Tinker**

#### **Test simple d'envoi d'email**
```bash
php artisan tinker
```

```php
// Test d'envoi d'email simple
Mail::raw('Test email avec Mailtrap', function ($message) {
    $message->to('test@mailtrap.io')
            ->subject('Test Tinker - Mailtrap');
});

// Test d'envoi d'email de compte rendu
$meeting = App\Models\Meeting::first();
Mail::to('test@mailtrap.io')->send(new App\Mail\MeetingMinutesSent($meeting));
```

#### **Test de notification**
```php
// Créer un utilisateur de test
$user = App\Models\User::firstOrCreate(
    ['email' => 'test@mailtrap.io'],
    [
        'name' => 'Test User',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]
);

// Envoyer une notification
$user->notify(new App\Notifications\TestNotification());
```

### **3. Test via l'interface web**

#### **Envoi de compte rendu par email**
1. Aller sur une page de réunion avec compte rendu
2. Cliquer sur "Envoyer le compte rendu"
3. Saisir l'email de test dans Mailtrap
4. Vérifier la réception dans Mailtrap

## 📧 Configuration Mailtrap

### **1. Variables d'environnement (.env)**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votreapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### **2. Vérification de la configuration**
La commande `test:email-sending` affiche automatiquement la configuration actuelle :
```
📧 Configuration actuelle des emails :
   Mailer: smtp
   Host: smtp.mailtrap.io
   Port: 2525
   Username: votre_username
   Encryption: tls
```

## 🔍 Vérification des résultats

### **1. Dans Mailtrap**
- Aller sur [mailtrap.io](https://mailtrap.io)
- Ouvrir votre boîte de réception
- Vérifier que l'email de test est bien reçu
- Examiner le contenu, les pièces jointes, etc.

### **2. Dans les logs Laravel**
```bash
# Voir les logs d'email
tail -f storage/logs/laravel.log | grep -i mail

# Voir les logs d'erreur
tail -f storage/logs/laravel.log | grep -i error
```

### **3. Vérification de la base de données**
```bash
php artisan tinker
```

```php
// Vérifier les emails envoyés (si logging activé)
\Illuminate\Support\Facades\Log::get('mail');

// Vérifier les réunions avec comptes rendus
App\Models\Meeting::with('minutes')->get();
```

## 🧪 Tests recommandés

### **1. Test de configuration SMTP**
```bash
php artisan test:email-sending --type=simple --email=test@mailtrap.io
```
**Vérifier** : Email reçu dans Mailtrap avec le bon sujet

### **2. Test d'email avec pièce jointe**
```bash
php artisan test:email-sending --type=minutes --email=test@mailtrap.io
```
**Vérifier** : Email reçu avec PDF en pièce jointe

### **3. Test avec réunion existante**
```bash
# Lister les réunions disponibles
php artisan tinker
App\Models\Meeting::pluck('id', 'title')->toArray();

# Tester avec une réunion spécifique
php artisan test:email-sending --meeting_id=1 --type=minutes
```

### **4. Test de gestion d'erreur**
```bash
# Tester avec une configuration SMTP incorrecte
# Modifier temporairement .env avec des mauvaises valeurs
MAIL_PASSWORD=wrong_password

# Lancer le test
php artisan test:email-sending --type=simple

# Vérifier que l'erreur est bien gérée et loggée
```

## 🚨 Dépannage

### **Erreur "Connection refused"**
- Vérifier que le port 2525 est ouvert
- Vérifier les credentials Mailtrap
- Vérifier que l'encryption est correcte (tls)

### **Erreur "Authentication failed"**
- Vérifier username/password Mailtrap
- Vérifier que le compte Mailtrap est actif
- Vérifier les permissions du compte

### **Email non reçu dans Mailtrap**
- Vérifier la boîte de réception correcte
- Vérifier les filtres Mailtrap
- Vérifier que l'email n'est pas dans les spams

### **Erreur "Class not found"**
- Vérifier que la commande est bien enregistrée
- Vérifier les namespaces et imports
- Vérifier que les modèles existent

## 📋 Checklist de test

### **✅ Configuration**
- [ ] Variables d'environnement configurées
- [ ] Configuration mail.php correcte
- [ ] Compte Mailtrap actif

### **✅ Tests de base**
- [ ] Test simple d'email
- [ ] Test d'email avec pièce jointe
- [ ] Test de notification

### **✅ Vérifications**
- [ ] Emails reçus dans Mailtrap
- [ ] Pièces jointes correctes
- [ ] Logs d'erreur propres
- [ ] Gestion d'erreur appropriée

### **✅ Tests avancés**
- [ ] Test avec réunion existante
- [ ] Test de gestion d'erreur
- [ ] Test de performance

## 🎉 Conclusion

Avec la commande `test:email-sending`, vous pouvez facilement tester tous les aspects de l'envoi d'emails :

### **🚀 Avantages de cette approche**
- **Test automatisé** : Plus besoin de tester manuellement
- **Tests multiples** : Différents types d'emails testés
- **Configuration visible** : Affichage de la config actuelle
- **Gestion d'erreur** : Logs et messages d'erreur clairs
- **Flexibilité** : Options pour différents scénarios de test

### **🔧 Prêt à l'utilisation**
- La commande est prête et fonctionnelle
- Configuration Mailtrap facile à vérifier
- Tests complets de tous les types d'emails
- Documentation détaillée pour le dépannage

### **📧 Testez maintenant !**
```bash
# Test rapide
php artisan test:email-sending

# Test complet
php artisan test:email-sending --email=votre-email@mailtrap.io --type=minutes
```

Vérifiez votre boîte Mailtrap et confirmez que la configuration des emails fonctionne parfaitement ! 🎯✨


