<template>
  <AppLayout :title="`Résultats - ${village.name}`">
    <div class="py-6">
      <!-- En-tête -->
      <div class="mb-6 bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
              Résultats du village
            </h2>
            <p class="text-gray-600 mt-1">{{ village.name }} - {{ meeting.title }}</p>
          </div>
          <div class="flex items-center space-x-3">
            <Link
              :href="route('village-results.index', meeting.id)"
              class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Retour à la liste
            </Link>
            <Link
              :href="route('meetings.show', meeting.id)"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              Voir la réunion
            </Link>
          </div>
        </div>
      </div>

      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Informations du village -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Informations du village</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nom du village</label>
              <p class="mt-1 text-sm text-gray-900">{{ village.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Type</label>
              <p class="mt-1 text-sm text-gray-900">{{ village.type || 'Village' }}</p>
            </div>
          </div>
        </div>

        <!-- Résultats du village -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Résultats</h3>
            <div class="flex items-center space-x-2">
              <span 
                v-if="result"
                :class="getStatusClass(result.status)"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ getStatusLabel(result.status) }}
              </span>
              <span class="text-sm text-gray-500 mr-2">
                Saisi par le secrétaire
              </span>
              <button
                v-if="!result"
                @click="showForm = true"
                class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Saisir résultats
              </button>
              <button
                v-else
                @click="showForm = true"
                class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Modifier
              </button>
            </div>
          </div>

          <!-- Affichage des résultats -->
          <div v-if="result" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Enrôlement -->
            <div class="bg-blue-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-blue-900">Enrôlement</h4>
                <span class="text-lg font-bold text-blue-600">{{ (result.enrollment_rate || 0).toFixed(1) }}%</span>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-blue-700">À enrôler</span>
                  <span class="font-semibold text-blue-900">{{ result.people_to_enroll_count || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-blue-700">Enrôlés</span>
                  <span class="font-semibold text-blue-900">{{ result.people_enrolled_count || 0 }}</span>
                </div>
              </div>
              <div class="mt-3">
                <div class="w-full bg-blue-200 rounded-full h-2">
                  <div 
                    class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: Math.min(result.enrollment_rate || 0, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Cartes CMU -->
            <div class="bg-purple-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-purple-900">Cartes CMU</h4>
                <span class="text-lg font-bold text-purple-600">{{ (result.cmu_distribution_rate || 0).toFixed(1) }}%</span>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-purple-700">Disponibles</span>
                  <span class="font-semibold text-purple-900">{{ result.cmu_cards_available_count || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-purple-700">Distribuées</span>
                  <span class="font-semibold text-purple-900">{{ result.cmu_cards_distributed_count || 0 }}</span>
                </div>
              </div>
              <div class="mt-3">
                <div class="w-full bg-purple-200 rounded-full h-2">
                  <div 
                    class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: Math.min(result.cmu_distribution_rate || 0, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- Réclamations -->
            <div class="bg-green-50 rounded-lg p-4">
              <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-green-900">Réclamations</h4>
                <span class="text-lg font-bold text-green-600">{{ (result.complaint_processing_rate || 0).toFixed(1) }}%</span>
              </div>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-green-700">Reçues</span>
                  <span class="font-semibold text-green-900">{{ result.complaints_received_count || 0 }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-green-700">Traitées</span>
                  <span class="font-semibold text-green-900">{{ result.complaints_processed_count || 0 }}</span>
                </div>
              </div>
              <div class="mt-3">
                <div class="w-full bg-green-200 rounded-full h-2">
                  <div 
                    class="bg-green-600 h-2 rounded-full transition-all duration-300"
                    :style="{ width: Math.min(result.complaint_processing_rate || 0, 100) + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Message si aucun résultat -->
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun résultat</h3>
            <p class="mt-1 text-sm text-gray-500">Aucun résultat n'a encore été saisi pour ce village.</p>
          </div>
        </div>

        <!-- Informations de soumission/validation -->
        <div v-if="result" class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Informations</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="result.submitter">
              <label class="block text-sm font-medium text-gray-700">Soumis par</label>
              <p class="mt-1 text-sm text-gray-900">{{ result.submitter.name }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(result.submitted_at) }}</p>
            </div>
            <div v-if="result.validator">
              <label class="block text-sm font-medium text-gray-700">Validé par</label>
              <p class="mt-1 text-sm text-gray-900">{{ result.validator.name }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(result.validated_at) }}</p>
            </div>
            <div v-if="result.comments" class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Commentaires</label>
              <p class="mt-1 text-sm text-gray-900">{{ result.comments }}</p>
            </div>
            <div v-if="result.validation_comments" class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700">Commentaires de validation</label>
              <p class="mt-1 text-sm text-gray-900">{{ result.validation_comments }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal pour saisir/modifier les résultats -->
      <Modal :show="showForm" @close="showForm = false">
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ result ? 'Modifier les résultats' : 'Saisir les résultats' }} - {{ village.name }}
          </h3>
          
          <VillageResultsForm
            v-model="formData"
            @validation-error="handleValidationError"
          />
          
          <div class="mt-6 flex justify-end space-x-3">
            <button
              @click="showForm = false"
              class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
            >
              Annuler
            </button>
            <button
              @click="saveResults"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
            >
              <svg v-if="saving" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import VillageResultsForm from '@/Components/VillageResultsForm.vue'
import { Link, router } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  meeting: Object,
  village: Object,
  result: Object,
  canValidate: Boolean,
})

const toast = useToast()

const showForm = ref(false)
const formData = ref({})
const saving = ref(false)

// Initialiser les données du formulaire
onMounted(() => {
  if (props.result) {
    formData.value = {
      people_to_enroll_count: props.result.people_to_enroll_count || null,
      people_enrolled_count: props.result.people_enrolled_count || null,
      cmu_cards_available_count: props.result.cmu_cards_available_count || null,
      cmu_cards_distributed_count: props.result.cmu_cards_distributed_count || null,
      complaints_received_count: props.result.complaints_received_count || null,
      complaints_processed_count: props.result.complaints_processed_count || null,
      comments: props.result.comments || '',
    }
  } else {
    formData.value = {
      people_to_enroll_count: null,
      people_enrolled_count: null,
      cmu_cards_available_count: null,
      cmu_cards_distributed_count: null,
      complaints_received_count: null,
      complaints_processed_count: null,
      comments: '',
    }
  }
})

// Sauvegarder les résultats
const saveResults = async () => {
  saving.value = true
  
  try {
    await router.post(route('village-results.store', [props.meeting.id, props.village.id]), formData.value)
    toast.success('Résultats sauvegardés avec succès')
    showForm.value = false
    router.reload()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    toast.error('Erreur lors de la sauvegarde')
  } finally {
    saving.value = false
  }
}

// Gérer les erreurs de validation
const handleValidationError = (errors) => {
  toast.error('Erreurs de validation détectées')
  console.error('Erreurs de validation:', errors)
}

// Obtenir le statut lisible
const getStatusLabel = (status) => {
  return {
    'draft': 'Brouillon',
    'submitted': 'Soumis',
    'validated': 'Validé'
  }[status] || 'Inconnu'
}

const getStatusClass = (status) => {
  return {
    'draft': 'bg-gray-100 text-gray-700',
    'submitted': 'bg-yellow-100 text-yellow-700',
    'validated': 'bg-green-100 text-green-700'
  }[status] || 'bg-gray-100 text-gray-700'
}

// Formater une date
const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString('fr-FR')
}
</script> 