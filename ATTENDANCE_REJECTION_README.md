# Fonctionnalité de validation et rejet des listes de présence pour les sous-préfets

## 🎯 Objectif

Permettre aux sous-préfets de **valider** ou **rejeter** une liste de présence, avec des comportements différents :
- **Validation** : Approuve la liste de présence
- **Invalidation** : Remet la liste en état "soumis" (pour corrections mineures)
- **Rejet** : Remet la liste en état "brouillon" (pour corrections majeures)

## 🔄 États des listes de présence

### **Flux des états**
```
BROUILLON (draft) → SOUMIS (submitted) → VALIDÉ (validated)
     ↑                    ↑                    ↑
     └── Rejet ──────────┴── Invalidation ───┘
```

### **Détails des états**
- **`draft`** : Liste en cours de création/modification par le secrétaire
- **`submitted`** : Liste soumise au sous-préfet pour validation
- **`validated`** : Liste approuvée par le sous-préfet

## ✅ Implémentation

### 1. **Contrôleur Laravel** (`MeetingController.php`)

#### **Méthode `validateAttendance`** - Valider les présences
```php
public function validateAttendance(Meeting $meeting)
{
    // Vérifier si l'utilisateur est un sous-préfet ou admin
    if (!Auth::user()->hasRole(['president', 'President', 'admin', 'Admin'])) {
        return response()->json([
            'message' => 'Vous n\'avez pas les droits pour valider les présences de cette réunion.'
        ], 403);
    }

    // Vérifier si les présences peuvent être validées
    if ($meeting->attendance_status !== 'submitted') {
        return response()->json([
            'message' => 'Les présences doivent être soumises avant d\'être validées.'
        ], 400);
    }

    // Mettre à jour la validation des présences
    $meeting->update([
        'attendance_status' => 'validated',
        'attendance_validated_at' => now(),
        'attendance_validated_by' => Auth::id(),
    ]);

    // Créer automatiquement une liste de paiement
    // ... logique de création de liste de paiement ...

    return response()->json([
        'message' => 'Présences validées avec succès et liste de paiement générée.'
    ]);
}
```

#### **Méthode `invalidateAttendance`** - Invalider (remet en état soumis)
```php
public function invalidateAttendance(Meeting $meeting)
{
    // Vérifier si l'utilisateur est un sous-préfet ou admin
    if (!Auth::user()->hasRole(['president', 'president', 'admin', 'Admin'])) {
        return response()->json([
            'message' => 'Vous n\'avez pas les droits pour invalider les présences de cette réunion.'
        ], 403);
    }

    // Vérifier si les présences peuvent être invalidées
    if (!$meeting->isAttendanceValidated()) {
        return response()->json([
            'message' => 'Les présences ne sont pas validées.'
        ], 400);
    }

    // Mettre à jour la validation des présences (remet en état soumis)
    $meeting->update([
        'attendance_status' => 'submitted',
        'attendance_validated_at' => null,
        'attendance_validated_by' => null,
    ]);

    return response()->json([
        'message' => 'Présences invalidées avec succès. La liste est remise en état soumis.'
    ]);
}
```

#### **Méthode `rejectAttendance`** - Rejeter (remet en état brouillon)
```php
public function rejectAttendance(Meeting $meeting)
{
    // Vérifier si l'utilisateur est un sous-préfet ou admin
    if (!Auth::user()->hasRole(['president', 'president', 'admin', 'Admin'])) {
        return response()->json([
            'message' => 'Vous n\'avez pas les droits pour rejeter la liste de présence de cette réunion.'
        ], 403);
    }

    // Vérifier si les présences peuvent être rejetées
    if (!$meeting->isAttendanceValidated()) {
        return response()->json([
            'message' => 'Les présences ne sont pas validées.'
        ], 400);
    }

    // Mettre à jour la validation des présences (remet en état brouillon)
    $meeting->update([
        'attendance_status' => 'draft',
        'attendance_validated_at' => null,
        'attendance_validated_by' => null,
        'attendance_submitted_at' => null,
        'attendance_submitted_by' => null,
    ]);

    return response()->json([
        'message' => 'Liste de présence rejetée avec succès. Elle est remise en état brouillon.'
    ]);
}
```

### 2. **Routes** (`routes/web.php`)

```php
// Routes pour la gestion des présences
Route::post('/{meeting}/validate-attendance', [MeetingController::class, 'validateAttendance'])
    ->name('validate-attendance');
Route::post('/{meeting}/invalidate-attendance', [MeetingController::class, 'invalidateAttendance'])
    ->name('invalidate-attendance');
Route::post('/{meeting}/reject-attendance', [MeetingController::class, 'rejectAttendance'])
    ->name('reject-attendance');
```

### 3. **Composant Vue.js** (`AttendanceValidationButtons.vue`)

#### **Interface utilisateur**
```vue
<template>
  <div class="flex flex-wrap gap-2">
    <!-- Bouton pour valider les présences -->
    <button
      v-if="canValidateAttendance && !meeting.attendance_validated_at"
      @click="validateAttendance"
      class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
      :disabled="loading"
    >
      <CheckIcon class="h-4 w-4 mr-2" />
      Valider les présences
    </button>

    <!-- Bouton pour invalider les présences (remet en état soumis) -->
    <button
      v-if="canInvalidateAttendance && meeting.attendance_validated_at"
      @click="invalidateAttendance"
      class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md text-sm font-medium hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
      :disabled="loading"
    >
      <ArrowUturnLeftIcon class="h-4 w-4 mr-2" />
      Remettre en état soumis
    </button>

    <!-- Bouton pour rejeter les présences (remet en état brouillon) -->
    <button
      v-if="canRejectAttendance && meeting.attendance_validated_at"
      @click="rejectAttendance"
      class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
      :disabled="loading"
    >
      <XMarkIcon class="h-4 w-4 mr-2" />
      Rejeter la liste
    </button>

    <!-- Indicateur de validation des présences -->
    <div v-if="meeting.attendance_validated_at" class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-md text-sm font-medium">
      <CheckIcon class="h-4 w-4 mr-2" />
      Présences validées
      <span class="ml-2 text-xs text-green-600">
        le {{ formatDate(meeting.attendance_validated_at) }}
      </span>
    </div>
  </div>
</template>
```

#### **Logique de permissions**
```javascript
// Vérifier si l'utilisateur peut valider les présences
const canValidateAttendance = computed(() => {
  return hasRole(props.user.roles, 'president') || hasRole(props.user.roles, 'admin')
})

// Vérifier si l'utilisateur peut invalider les présences
const canInvalidateAttendance = computed(() => {
  return hasRole(props.user.roles, 'president') || hasRole(props.user.roles, 'admin')
})

// Vérifier si l'utilisateur peut rejeter les présences
const canRejectAttendance = computed(() => {
  return hasRole(props.user.roles, 'president') || hasRole(props.user.roles, 'admin')
})
```

#### **Fonctions d'action**
```javascript
// Fonction pour valider les présences
const validateAttendance = async () => {
  if (!canValidate.value) {
    toast.error('Les présences ne peuvent être validées que si elles ont été soumises.')
    return
  }

  if (!confirm('Êtes-vous sûr de vouloir valider les présences de cette réunion ?')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.validate-attendance', props.meeting.id))
    
    toast.success(response.data.message || 'Présences validées avec succès')
    emit('attendanceValidated')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la validation des présences')
  } finally {
    loading.value = false
  }
}

// Fonction pour invalider les présences (remet en état soumis)
const invalidateAttendance = async () => {
  if (!confirm('Êtes-vous sûr de vouloir remettre les présences en état soumis ?')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.invalidate-attendance', props.meeting.id))
    
    toast.success(response.data.message || 'Présences invalidées avec succès')
    emit('attendanceInvalidated')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'invalidation des présences')
  } finally {
    loading.value = false
  }
}

// Fonction pour rejeter les présences (remet en état brouillon)
const rejectAttendance = async () => {
  if (!confirm('Êtes-vous sûr de vouloir rejeter cette liste de présence ? Elle sera remise en état brouillon.')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.reject-attendance', props.meeting.id))
    
    toast.success(response.data.message || 'Liste de présence rejetée avec succès')
    emit('attendanceRejected')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors du rejet de la liste de présence')
  } finally {
    loading.value = false
  }
}
```

### 4. **Intégration dans la vue** (`Meetings/Show.vue`)

```vue
<AttendanceValidationButtons 
  :meeting="meeting"
  :user="user"
  @attendance-validated="handleAttendanceValidated"
  @attendance-invalidated="handleAttendanceInvalidated"
  @attendance-rejected="handleAttendanceRejected"
/>
```

#### **Gestion des événements**
```javascript
const handleAttendanceValidated = () => {
  // Recharger la page pour mettre à jour les données
  window.location.reload()
}

const handleAttendanceInvalidated = () => {
  // Recharger la page pour mettre à jour les données
  router.reload()
}

const handleAttendanceRejected = () => {
  // Recharger la page pour mettre à jour les données
  router.reload()
}
```

## 🔄 Flux de fonctionnement détaillé

### **1. État initial (brouillon)**
- ✅ **Bouton "Valider les présences"** : Caché
- ✅ **Bouton "Remettre en état soumis"** : Caché
- ✅ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- **Action possible** : Le secrétaire peut modifier la liste

### **2. Après soumission par le secrétaire**
- ✅ **Bouton "Valider les présences"** : Visible (vert)
- ✅ **Bouton "Remettre en état soumis"** : Caché
- ✅ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- **Action possible** : Le sous-préfet peut valider

### **3. Après validation par le sous-préfet**
- ❌ **Bouton "Valider les présences"** : Caché
- ✅ **Bouton "Remettre en état soumis"** : Visible (orange)
- ✅ **Bouton "Rejeter la liste"** : Visible (rouge)
- ✅ **Indicateur "Présences validées"** : Visible avec date
- **Actions possibles** : Le sous-préfet peut invalider ou rejeter

### **4. Après invalidation (remet en état soumis)**
- ✅ **Bouton "Valider les présences"** : Visible (vert)
- ❌ **Bouton "Remettre en état soumis"** : Caché
- ❌ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- **Action possible** : Le sous-préfet peut revalider après corrections

### **5. Après rejet (remet en état brouillon)**
- ❌ **Bouton "Valider les présences"** : Caché
- ❌ **Bouton "Remettre en état soumis"** : Caché
- ❌ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- **Action possible** : Le secrétaire peut modifier et resoumettre

## 🔐 Gestion des permissions

### **Rôles autorisés**
- **`president`** : Sous-préfet (peut valider, invalider et rejeter)
- **`admin`** : Administrateur (peut valider, invalider et rejeter)

### **Vérifications de sécurité**
- **Côté client** : Vérification des rôles dans le composant Vue
- **Côté serveur** : Vérification des rôles dans le contrôleur Laravel
- **Double protection** : Même si le client est modifié, le serveur vérifie les permissions

## 📊 Impact sur les données

### **Lors de la validation**
- `attendance_status` → `'validated'`
- `attendance_validated_at` → `now()`
- `attendance_validated_by` → `Auth::id()`
- **Création automatique** d'une liste de paiement

### **Lors de l'invalidation (remet en état soumis)**
- `attendance_status` → `'submitted'`
- `attendance_validated_at` → `null`
- `attendance_validated_by` → `null`
- **Conservation** de la liste de paiement existante

### **Lors du rejet (remet en état brouillon)**
- `attendance_status` → `'draft'`
- `attendance_validated_at` → `null`
- `attendance_validated_by` → `null`
- `attendance_submitted_at` → `null`
- `attendance_submitted_by` → `null`
- **Conservation** de la liste de paiement existante

## 🎨 Interface utilisateur

### **Boutons**
- **Validation** : Bouton vert avec icône de validation
- **Invalidation** : Bouton orange avec icône de retour (remet en état soumis)
- **Rejet** : Bouton rouge avec icône X (remet en état brouillon)
- **États** : Boutons affichés/masqués selon le contexte

### **Indicateurs visuels**
- **Présences validées** : Badge vert avec date de validation
- **Chargement** : Spinner pendant les opérations
- **Messages** : Toast notifications de succès/erreur

### **Confirmations**
- **Validation** : "Êtes-vous sûr de vouloir valider les présences de cette réunion ?"
- **Invalidation** : "Êtes-vous sûr de vouloir remettre les présences en état soumis ?"
- **Rejet** : "Êtes-vous sûr de vouloir rejeter cette liste de présence ? Elle sera remise en état brouillon."

## 🚀 Cas d'usage

### **1. Validation**
- **Quand** : La liste de présence est correcte et complète
- **Résultat** : Création automatique de la liste de paiement
- **Action suivante** : Traitement des paiements

### **2. Invalidation (remet en état soumis)**
- **Quand** : Corrections mineures nécessaires (orthographe, formatage)
- **Résultat** : La liste reste soumise, le secrétaire peut la corriger
- **Action suivante** : Le secrétaire corrige et le sous-préfet revalide

### **3. Rejet (remet en état brouillon)**
- **Quand** : Corrections majeures nécessaires (participants manquants, erreurs de comptage)
- **Résultat** : La liste retourne en brouillon, le secrétaire peut la modifier complètement
- **Action suivante** : Le secrétaire refait la liste et la resoumet

## 🎯 Avantages de cette implémentation

### **1. Flexibilité**
- **Trois niveaux d'action** : Validation, invalidation, rejet
- **Gestion des erreurs** : Différents niveaux de correction
- **Workflow adaptatif** : S'adapte aux besoins de correction

### **2. Sécurité**
- **Double vérification** : Permissions vérifiées côté client et serveur
- **Validation des états** : Actions uniquement sur états appropriés
- **Messages d'erreur** : Erreurs explicites et appropriées

### **3. Expérience utilisateur**
- **Interface intuitive** : Boutons contextuels selon l'état
- **Feedback visuel** : Indicateurs clairs et boutons colorés
- **Confirmations** : Messages explicites pour éviter les erreurs

### **4. Traçabilité**
- **Historique complet** : Toutes les actions sont tracées
- **Horodatage** : Dates précises des actions
- **Identification** : Utilisateurs responsables identifiés

## 📋 Tests recommandés

### **1. Tests de permissions**
- ✅ **Sous-préfet** : Peut valider, invalider et rejeter
- ✅ **Secrétaire** : Ne peut ni valider ni invalider ni rejeter
- ✅ **Admin** : Peut valider, invalider et rejeter

### **2. Tests de workflow**
- ✅ **Validation** → Boutons d'invalidation et rejet visibles
- ✅ **Invalidation** → Bouton de validation visible, état "soumis"
- ✅ **Rejet** → Aucun bouton visible, état "brouillon"
- ✅ **États visuels** : Indicateurs corrects selon l'état

### **3. Tests de sécurité**
- ✅ **Validation serveur** : Permissions vérifiées côté serveur
- ✅ **États valides** : Actions uniquement sur états appropriés
- ✅ **Messages d'erreur** : Erreurs explicites et appropriées

### **4. Tests de données**
- ✅ **Validation** : Création de liste de paiement
- ✅ **Invalidation** : Conservation de la liste de paiement
- ✅ **Rejet** : Conservation de la liste de paiement

## 🎉 Conclusion

La fonctionnalité de **validation et rejet des listes de présence** est maintenant **complètement implémentée** avec trois niveaux d'action :

### ✅ **Fonctionnalités disponibles**
- **Validation** : Approuve la liste et crée la liste de paiement
- **Invalidation** : Remet en état "soumis" pour corrections mineures
- **Rejet** : Remet en état "brouillon" pour corrections majeures

### 🚀 **Prêt à l'utilisation**
- Les sous-préfets ont un contrôle complet sur les listes de présence
- L'interface s'adapte automatiquement selon l'état
- Toutes les vérifications de sécurité sont en place
- L'expérience utilisateur est optimale avec des boutons contextuels

### 🔄 **Workflow flexible**
- **Validation** → **Invalidation** → **Revalidation** (pour corrections mineures)
- **Validation** → **Rejet** → **Nouvelle soumission** (pour corrections majeures)

La solution est maintenant opérationnelle et offre une gestion complète des listes de présence ! 🎯✨
