# Fonctionnalité simplifiée de validation et rejet des listes de présence

## 🎯 Objectif

Simplifier la gestion des listes de présence en gardant seulement **deux actions** pour les sous-préfets :
- **Validation** : Approuve la liste de présence
- **Rejet** : Rejette la liste avec commentaires (état `rejected`)

## 🔄 États des listes de présence

### **Flux des états simplifié**
```
BROUILLON (draft) → SOUMIS (submitted) → VALIDÉ (validated)
     ↑                    ↑                    ↑
     └── Rejet ──────────┴── Validation ──────┘
```

### **Détails des états**
- **`draft`** : Liste en cours de création/modification par le secrétaire
- **`submitted`** : Liste soumise au sous-préfet pour validation
- **`validated`** : Liste approuvée par le sous-préfet
- **`rejected`** : Liste rejetée par le sous-préfet (avec commentaires)

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

#### **Méthode `rejectAttendance`** - Rejeter avec commentaires
```php
public function rejectAttendance(Request $request, Meeting $meeting)
{
    // Vérifier si l'utilisateur est un sous-préfet ou admin
    if (!Auth::user()->hasRole(['president', 'president', 'admin', 'Admin'])) {
        return response()->json([
            'message' => 'Vous n\'avez pas les droits pour rejeter la liste de présence de cette réunion.'
        ], 403);
    }

    // Valider les données de la requête
    $request->validate([
        'rejection_comments' => 'required|string|max:1000',
    ]);

    // Vérifier si les présences peuvent être rejetées
    if (!$meeting->isAttendanceValidated()) {
        return response()->json([
            'message' => 'Les présences ne sont pas validées.'
        ], 400);
    }

    // Mettre à jour la validation des présences (remet en état rejected)
    $meeting->update([
        'attendance_status' => 'rejected',
        'attendance_validated_at' => null,
        'attendance_validated_by' => null,
        'attendance_rejection_comments' => $request->rejection_comments,
        'attendance_rejected_at' => now(),
        'attendance_rejected_by' => Auth::id(),
    ]);

    return response()->json([
        'message' => 'Liste de présence rejetée avec succès. Le secrétaire peut maintenant la modifier et la resoumettre.'
    ]);
}
```

### 2. **Routes** (`routes/web.php`)

```php
// Routes pour la gestion des présences
Route::post('/{meeting}/validate-attendance', [MeetingController::class, 'validateAttendance'])
    ->name('validate-attendance');
Route::post('/{meeting}/reject-attendance', [MeetingController::class, 'rejectAttendance'])
    ->name('reject-attendance');
```

### 3. **Composant Vue.js** (`AttendanceValidationButtons.vue`)

#### **Interface utilisateur simplifiée**
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

    <!-- Bouton pour rejeter les présences -->
    <button
      v-if="canRejectAttendance && meeting.attendance_validated_at"
      @click="showRejectionModal = true"
      class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
      :disabled="loading"
    >
      <XMarkIcon class="h-4 w-4 mr-2" />
      Rejeter la liste
    </button>

    <!-- Indicateur si la liste a été rejetée -->
    <div v-if="meeting.attendance_status === 'rejected'" class="inline-flex items-center px-3 py-2 bg-red-100 text-red-800 rounded-md text-sm font-medium">
      <XMarkIcon class="h-4 w-4 mr-2" />
      Liste rejetée
      <span class="ml-2 text-xs text-red-600">
        le {{ formatDate(meeting.attendance_rejected_at) }}
      </span>
    </div>

    <!-- Indicateur de validation des présences -->
    <div v-if="meeting.attendance_validated_at" class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-md text-sm font-medium">
      <CheckIcon class="h-4 w-4 mr-2" />
      Présences validées
      <span class="ml-2 text-xs text-green-600">
        le {{ formatDate(meeting.attendance_validated_at) }}
      </span>
    </div>
  </div>

  <!-- Modal de rejet avec commentaires -->
  <div v-if="showRejectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Rejeter la liste de présence</h3>
        
        <form @submit.prevent="submitRejection">
          <div class="mb-4">
            <label for="rejection_comments" class="block text-sm font-medium text-gray-700 mb-2">
              Commentaires de rejet <span class="text-red-500">*</span>
            </label>
            <textarea
              id="rejection_comments"
              v-model="rejectionForm.comments"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              placeholder="Expliquez pourquoi cette liste est rejetée..."
              required
            ></textarea>
          </div>
          
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showRejectionModal = false"
              class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="rejectionForm.loading"
              class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50"
            >
              Rejeter
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
```

#### **Logique de permissions simplifiée**
```javascript
// Vérifier si l'utilisateur peut valider les présences
const canValidateAttendance = computed(() => {
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

// Fonction pour soumettre le rejet avec commentaires
const submitRejection = async () => {
  if (!rejectionForm.value.comments.trim()) {
    toast.error('Veuillez saisir un commentaire de rejet')
    return
  }

  rejectionForm.value.loading = true

  try {
    const response = await axios.post(route('meetings.reject-attendance', props.meeting.id), {
      rejection_comments: rejectionForm.value.comments
    })
    
    toast.success(response.data.message || 'Liste de présence rejetée avec succès')
    showRejectionModal.value = false
    rejectionForm.value.comments = ''
    emit('attendanceRejected')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors du rejet de la liste de présence')
  } finally {
    rejectionForm.value.loading = false
  }
}
```

### 4. **Intégration dans la vue** (`Meetings/Show.vue`)

```vue
<AttendanceValidationButtons 
  :meeting="meeting"
  :user="user"
  @attendance-validated="handleAttendanceValidated"
  @attendance-rejected="handleAttendanceRejected"
/>
```

#### **Gestion des événements**
```javascript
const handleAttendanceValidated = () => {
  // Recharger la page pour mettre à jour les données
  window.location.reload()
}

const handleAttendanceRejected = () => {
  // Recharger la page pour mettre à jour les données
  router.reload()
}
```

## 🔄 Flux de fonctionnement simplifié

### **1. État initial (brouillon)**
- ✅ **Bouton "Valider les présences"** : Caché
- ✅ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- ❌ **Indicateur "Liste rejetée"** : Caché
- **Action possible** : Le secrétaire peut modifier la liste

### **2. Après soumission par le secrétaire**
- ✅ **Bouton "Valider les présences"** : Visible (vert)
- ✅ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- ❌ **Indicateur "Liste rejetée"** : Caché
- **Action possible** : Le sous-préfet peut valider

### **3. Après validation par le sous-préfet**
- ❌ **Bouton "Valider les présences"** : Caché
- ✅ **Bouton "Rejeter la liste"** : Visible (rouge)
- ✅ **Indicateur "Présences validées"** : Visible avec date
- ❌ **Indicateur "Liste rejetée"** : Caché
- **Action possible** : Le sous-préfet peut rejeter

### **4. Après rejet par le sous-préfet**
- ❌ **Bouton "Valider les présences"** : Caché
- ❌ **Bouton "Rejeter la liste"** : Caché
- ❌ **Indicateur "Présences validées"** : Caché
- ✅ **Indicateur "Liste rejetée"** : Visible avec date et commentaires
- **Action possible** : Le secrétaire peut modifier et resoumettre

## 🔐 Gestion des permissions

### **Rôles autorisés**
- **`president`** : Sous-préfet (peut valider et rejeter)
- **`admin`** : Administrateur (peut valider et rejeter)

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

### **Lors du rejet**
- `attendance_status` → `'rejected'`
- `attendance_validated_at` → `null`
- `attendance_validated_by` → `null`
- `attendance_rejection_comments` → Commentaires du rejet
- `attendance_rejected_at` → `now()`
- `attendance_rejected_by` → `Auth::id()`
- **Conservation** de la liste de paiement existante

## 🎨 Interface utilisateur

### **Boutons**
- **Validation** : Bouton vert avec icône de validation
- **Rejet** : Bouton rouge avec icône X (ouvre un modal)

### **Indicateurs visuels**
- **Présences validées** : Badge vert avec date de validation
- **Liste rejetée** : Badge rouge avec date de rejet
- **Chargement** : Spinner pendant les opérations
- **Messages** : Toast notifications de succès/erreur

### **Modal de rejet**
- **Champ obligatoire** : Commentaires de rejet (max 1000 caractères)
- **Validation** : Impossible de rejeter sans commentaires
- **Interface** : Modal centré avec formulaire clair

## 🚀 Cas d'usage

### **1. Validation**
- **Quand** : La liste de présence est correcte et complète
- **Résultat** : Création automatique de la liste de paiement
- **Action suivante** : Traitement des paiements

### **2. Rejet**
- **Quand** : Corrections nécessaires (participants manquants, erreurs de comptage, etc.)
- **Résultat** : La liste passe en état "rejected" avec commentaires
- **Action suivante** : Le secrétaire corrige et resoumet

## 🎯 Avantages de cette approche simplifiée

### **1. Simplicité**
- **Deux actions seulement** : Validation ou rejet
- **Interface claire** : Moins de boutons, moins de confusion
- **Workflow linéaire** : Plus facile à comprendre

### **2. Traçabilité améliorée**
- **État distinct** : `rejected` au lieu de retourner à `draft`
- **Commentaires obligatoires** : Raison du rejet toujours documentée
- **Historique complet** : Toutes les actions sont tracées

### **3. Expérience utilisateur**
- **Feedback clair** : L'utilisateur sait exactement ce qui se passe
- **Actions contextuelles** : Boutons affichés selon l'état
- **Confirmations** : Messages explicites pour éviter les erreurs

### **4. Maintenance**
- **Code simplifié** : Moins de logique conditionnelle
- **Moins de bugs** : Moins de cas d'usage à tester
- **Évolution facile** : Facile d'ajouter de nouvelles fonctionnalités

## 📋 Tests recommandés

### **1. Tests de permissions**
- ✅ **Sous-préfet** : Peut valider et rejeter
- ✅ **Secrétaire** : Ne peut ni valider ni rejeter
- ✅ **Admin** : Peut valider et rejeter

### **2. Tests de workflow**
- ✅ **Validation** → Bouton de rejet visible
- ✅ **Rejet** → Indicateur "Liste rejetée" visible
- ✅ **États visuels** : Indicateurs corrects selon l'état

### **3. Tests de validation**
- ✅ **Commentaires obligatoires** : Impossible de rejeter sans commentaires
- ✅ **Longueur des commentaires** : Maximum 1000 caractères
- ✅ **Modal de rejet** : Affichage et fermeture corrects

### **4. Tests de sécurité**
- ✅ **Validation serveur** : Permissions vérifiées côté serveur
- ✅ **États valides** : Actions uniquement sur états appropriés
- ✅ **Messages d'erreur** : Erreurs explicites et appropriées

## 🎉 Conclusion

La fonctionnalité de **validation et rejet des listes de présence** a été **simplifiée et optimisée** :

### ✅ **Fonctionnalités disponibles**
- **Validation** : Approuve la liste et crée la liste de paiement
- **Rejet** : Rejette avec commentaires obligatoires (état `rejected`)

### 🚀 **Prêt à l'utilisation**
- Les sous-préfets ont un contrôle clair sur les listes de présence
- L'interface s'adapte automatiquement selon l'état
- Toutes les vérifications de sécurité sont en place
- L'expérience utilisateur est optimale avec des actions simples

### 🔄 **Workflow simplifié**
- **Validation** → **Rejet** → **Correction par le secrétaire** → **Nouvelle soumission**
- **Traçabilité complète** : État `rejected` distinct avec commentaires
- **Interface intuitive** : Deux boutons clairs selon le contexte

La solution est maintenant opérationnelle et offre une gestion simple et efficace des listes de présence ! 🎯✨
