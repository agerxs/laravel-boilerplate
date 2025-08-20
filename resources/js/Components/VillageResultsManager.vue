<template>
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold">Saisie des résultats des villages</h3>
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600">
          {{ summary.submitted_villages }}/{{ summary.total_villages }} villages saisis
        </span>
        <button
          @click="refreshResults"
          class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          Actualiser
        </button>
      </div>
    </div>

    <!-- Résumé global -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
      <h4 class="text-sm font-semibold text-gray-900 mb-3">Résumé global</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">{{ globalRates.enrollment_rate.toFixed(1) }}%</div>
          <div class="text-xs text-gray-600">Taux d'enrôlement global</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-purple-600">{{ globalRates.cmu_distribution_rate.toFixed(1) }}%</div>
          <div class="text-xs text-gray-600">Taux de distribution CMU</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold text-green-600">{{ globalRates.complaint_processing_rate.toFixed(1) }}%</div>
          <div class="text-xs text-gray-600">Taux de traitement</div>
        </div>
      </div>
    </div>

    <!-- Formulaire de saisie pour tous les villages -->
    <div class="space-y-6">
      <div class="border rounded-lg p-4">
        <h4 class="text-md font-semibold mb-4">Saisie des résultats</h4>
        <p class="text-sm text-gray-600 mb-4">
          Saisissez les résultats de chaque village pendant la réunion. Demandez à chaque membre de village ses chiffres.
        </p>
        
        <!-- Formulaire pour tous les villages -->
        <div class="space-y-4">
          <div
            v-for="village in villages"
            :key="village.id"
            class="border rounded-lg p-4 bg-gray-50"
          >
            <div class="flex items-center justify-between mb-3">
              <h5 class="font-medium text-gray-900">{{ village.name }}</h5>
              <span 
                :class="getVillageStatusClass(village)"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ getVillageStatusLabel(village) }}
              </span>
            </div>

            <!-- Formulaire pour ce village -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Enrôlement -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enrôlement</label>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs text-gray-500">À enrôler</label>
                    <input
                      :value="villageForms[village.id]?.people_to_enroll_count || ''"
                      @input="e => updateVillageForm(village.id, 'people_to_enroll_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500">Enrôlés</label>
                    <input
                      :value="villageForms[village.id]?.people_enrolled_count || ''"
                      @input="e => updateVillageForm(village.id, 'people_enrolled_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                </div>
              </div>

              <!-- Cartes CMU -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cartes CMU</label>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs text-gray-500">Disponibles</label>
                    <input
                      :value="villageForms[village.id]?.cmu_cards_available_count || ''"
                      @input="e => updateVillageForm(village.id, 'cmu_cards_available_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500">Distribuées</label>
                    <input
                      :value="villageForms[village.id]?.cmu_cards_distributed_count || ''"
                      @input="e => updateVillageForm(village.id, 'cmu_cards_distributed_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                </div>
              </div>

              <!-- Réclamations -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Réclamations</label>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs text-gray-500">Reçues</label>
                    <input
                      :value="villageForms[village.id]?.complaints_received_count || ''"
                      @input="e => updateVillageForm(village.id, 'complaints_received_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500">Traitées</label>
                    <input
                      :value="villageForms[village.id]?.complaints_processed_count || ''"
                      @input="e => updateVillageForm(village.id, 'complaints_processed_count', e.target.value)"
                      type="number"
                      min="0"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      placeholder="0"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Commentaires -->
            <div class="mt-3">
              <label class="block text-sm font-medium text-gray-700 mb-1">Commentaires</label>
              <textarea
                :value="villageForms[village.id]?.comments || ''"
                @input="e => updateVillageForm(village.id, 'comments', e.target.value)"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Commentaires spécifiques à ce village..."
              ></textarea>
            </div>

            <!-- Boutons d'action pour ce village -->
            <div class="mt-3 flex justify-end space-x-2">
              <button
                @click="saveVillageResults(village)"
                :disabled="savingVillage === village.id"
                class="inline-flex items-center px-3 py-1 bg-green-600 text-white text-sm rounded-md hover:bg-green-700 disabled:opacity-50"
              >
                <svg v-if="savingVillage === village.id" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ savingVillage === village.id ? 'Sauvegarde...' : 'Sauvegarder' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Bouton pour sauvegarder tous les villages -->
        <div class="mt-6 flex justify-end">
          <button
            @click="saveAllVillageResults"
            :disabled="savingAll"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            <svg v-if="savingAll" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ savingAll ? 'Sauvegarde...' : 'Sauvegarder tous les villages' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Affichage des résultats existants -->
    <div v-if="results.length > 0" class="mt-6">
      <h4 class="text-md font-semibold mb-4">Résultats saisis</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="village in villages"
          :key="village.id"
          v-if="hasVillageResult(village.id)"
          class="border rounded-lg p-4 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center justify-between mb-3">
            <h5 class="font-medium text-gray-900">{{ village.name }}</h5>
            <span 
              :class="getVillageStatusClass(village)"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            >
              {{ getVillageStatusLabel(village) }}
            </span>
          </div>

          <!-- Affichage des résultats -->
          <div class="grid grid-cols-1 gap-3">
            <div class="text-center p-2 bg-blue-50 rounded-lg">
              <div class="text-lg font-bold text-blue-600">
                {{ getVillageResult(village.id).enrollment_rate.toFixed(1) }}%
              </div>
              <div class="text-xs text-blue-600">Enrôlement</div>
              <div class="text-xs text-gray-500">
                {{ getVillageResult(village.id).people_enrolled_count || 0 }}/{{ getVillageResult(village.id).people_to_enroll_count || 0 }}
              </div>
            </div>
            <div class="text-center p-2 bg-purple-50 rounded-lg">
              <div class="text-lg font-bold text-purple-600">
                {{ getVillageResult(village.id).cmu_distribution_rate.toFixed(1) }}%
              </div>
              <div class="text-xs text-purple-600">Distribution CMU</div>
              <div class="text-xs text-gray-500">
                {{ getVillageResult(village.id).cmu_cards_distributed_count || 0 }}/{{ getVillageResult(village.id).cmu_cards_available_count || 0 }}
              </div>
            </div>
            <div class="text-center p-2 bg-green-50 rounded-lg">
              <div class="text-lg font-bold text-green-600">
                {{ getVillageResult(village.id).complaint_processing_rate.toFixed(1) }}%
              </div>
              <div class="text-xs text-green-600">Traitement</div>
              <div class="text-xs text-gray-500">
                {{ getVillageResult(village.id).complaints_processed_count || 0 }}/{{ getVillageResult(village.id).complaints_received_count || 0 }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  meeting: {
    type: Object,
    required: true
  },
  villages: {
    type: Array,
    required: true
  },
  results: {
    type: Array,
    default: () => []
  },
  summary: {
    type: Object,
    default: () => ({
      total_villages: 0,
      submitted_villages: 0,
      validated_villages: 0,
      draft_villages: 0
    })
  },
  globalRates: {
    type: Object,
    default: () => ({
      enrollment_rate: 0,
      cmu_distribution_rate: 0,
      complaint_processing_rate: 0
    })
  },
  canValidate: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['results-updated'])

const toast = useToast()

const results = ref(props.results)
const summary = ref(props.summary)
const globalRates = ref(props.globalRates)

const villageForms = ref({})
const savingVillage = ref(null)
const savingAll = ref(false)

// S'assurer qu'un formulaire village existe
const ensureVillageForm = (villageId) => {
  if (!villageForms.value[villageId]) {
    villageForms.value[villageId] = {
      people_to_enroll_count: null,
      people_enrolled_count: null,
      cmu_cards_available_count: null,
      cmu_cards_distributed_count: null,
      complaints_received_count: null,
      complaints_processed_count: null,
      comments: '',
    }
  }
  return villageForms.value[villageId]
}

// Mettre à jour un champ du formulaire village
const updateVillageForm = (villageId, field, value) => {
  ensureVillageForm(villageId)
  villageForms.value[villageId][field] = value
}

// Initialiser les formulaires pour chaque village
const initializeVillageForms = () => {
  props.villages.forEach(village => {
    if (village && village.id) {
      const existingResult = getVillageResult(village.id)
      villageForms.value[village.id] = {
        people_to_enroll_count: existingResult.people_to_enroll_count || null,
        people_enrolled_count: existingResult.people_enrolled_count || null,
        cmu_cards_available_count: existingResult.cmu_cards_available_count || null,
        cmu_cards_distributed_count: existingResult.cmu_cards_distributed_count || null,
        complaints_received_count: existingResult.complaints_received_count || null,
        complaints_processed_count: existingResult.complaints_processed_count || null,
        comments: existingResult.comments || '',
      }
    }
  })
}

// Initialiser les données
const initializeData = () => {
  results.value = props.results
  summary.value = props.summary
  globalRates.value = props.globalRates
  initializeVillageForms()
}

// Vérifier si un village a des résultats
const hasVillageResult = (villageId) => {
  return results.value.some(result => result.localite_id === villageId)
}

// Obtenir les résultats d'un village
const getVillageResult = (villageId) => {
  return results.value.find(result => result.localite_id === villageId) || {}
}

// Obtenir le statut d'un village
const getVillageStatusLabel = (village) => {
  const result = getVillageResult(village.id)
  if (!result.status) return 'Non saisi'
  return {
    'draft': 'Brouillon',
    'submitted': 'Soumis',
    'validated': 'Validé'
  }[result.status] || 'Inconnu'
}

const getVillageStatusClass = (village) => {
  const result = getVillageResult(village.id)
  if (!result.status) return 'bg-gray-100 text-gray-700'
  return {
    'draft': 'bg-gray-100 text-gray-700',
    'submitted': 'bg-yellow-100 text-yellow-700',
    'validated': 'bg-green-100 text-green-700'
  }[result.status] || 'bg-gray-100 text-gray-700'
}

// Sauvegarder les résultats d'un village
const saveVillageResults = async (village) => {
  savingVillage.value = village.id
  
  try {
    await axios.post(route('api.village-results.store', [props.meeting.id, village.id]), villageForms.value[village.id])
    
    toast.success(`Résultats de ${village.name} sauvegardés`)
    await loadResults()
    emit('results-updated')
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la sauvegarde')
  } finally {
    savingVillage.value = null
  }
}

// Sauvegarder tous les villages
const saveAllVillageResults = async () => {
  savingAll.value = true
  
  try {
    const promises = props.villages.map(village => 
      axios.post(route('api.village-results.store', [props.meeting.id, village.id]), villageForms.value[village.id])
    )
    
    await Promise.all(promises)
    toast.success('Tous les résultats ont été sauvegardés')
    await loadResults()
    emit('results-updated')
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    toast.error('Erreur lors de la sauvegarde de certains villages')
  } finally {
    savingAll.value = false
  }
}

// Actualiser les résultats
const refreshResults = () => {
  initializeData()
}

// Initialiser les données au montage
onMounted(() => {
  initializeData()
})
</script> 