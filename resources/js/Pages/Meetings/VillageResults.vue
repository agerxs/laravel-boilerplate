<template>
  <AppLayout :title="`Résultats des villages - ${meeting.title}`">
    <div class="py-6">
      <!-- En-tête -->
      <div class="mb-6 bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
              Résultats des villages
            </h2>
            <p class="text-gray-600 mt-1">{{ meeting.title }}</p>
            <p class="text-sm text-gray-500 mt-1">Saisie par le secrétaire pendant la réunion</p>
          </div>
          <div class="flex items-center space-x-3">
            <Link
              :href="route('meetings.show', meeting.id)"
              class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Retour à la réunion
            </Link>
          </div>
        </div>
      </div>

      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Résumé global -->
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-lg font-semibold mb-4">Résumé global</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
              <div class="text-2xl font-bold text-blue-600">{{ summary.submitted_villages }}/{{ summary.total_villages }}</div>
              <div class="text-sm text-blue-600">Villages saisis</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
              <div class="text-2xl font-bold text-green-600">{{ globalRates.enrollment_rate.toFixed(1) }}%</div>
              <div class="text-sm text-green-600">Taux d'enrôlement</div>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
              <div class="text-2xl font-bold text-purple-600">{{ globalRates.cmu_distribution_rate.toFixed(1) }}%</div>
              <div class="text-sm text-purple-600">Distribution CMU</div>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-lg">
              <div class="text-2xl font-bold text-yellow-600">{{ globalRates.complaint_processing_rate.toFixed(1) }}%</div>
              <div class="text-sm text-yellow-600">Traitement réclamations</div>
            </div>
          </div>
        </div>

        <!-- Interface de saisie des résultats -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold">Saisie des résultats</h3>
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

          <p class="text-sm text-gray-600 mb-6">
            Saisissez les résultats de chaque village pendant la réunion. Demandez à chaque représentant de village ses chiffres.
          </p>

          <!-- Formulaire pour tous les villages -->
          <div class="space-y-6">
            <!-- Debug: Afficher le nombre de villages -->
            <div class="text-sm text-gray-500 mb-4">
              Nombre de villages: {{ safeVillages.length }}
            </div>
            <div
              v-for="(village, index) in safeVillages"
              :key="village.id || index"
              class="border rounded-lg p-4 bg-gray-50"
              @mounted="console.log('Village rendu:', village.name, 'Index:', index)"
              @vue:mounted="console.log('Village monté:', village.name, 'Index:', index)"
            >
              <div class="flex items-center justify-between mb-3">
                <h5 class="font-medium text-gray-900">
                  {{ village.name || 'Village inconnu' }}
                  <span class="text-xs text-gray-400">(ID: {{ village.id }})</span>
                </h5>
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

        <!-- Affichage des résultats existants -->
        <div v-if="results.length > 0" class="bg-white rounded-lg shadow">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Résultats saisis</h3>
            <p class="text-sm text-gray-600 mt-1">Résultats saisis par le secrétaire pendant la réunion</p>
          </div>
          <div class="divide-y divide-gray-200">
            <div
              v-for="village in safeVillages"
              :key="village.id || 'unknown'"
              class="px-6 py-4 hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                  <div>
                    <h4 class="font-medium text-gray-900">{{ village.name || 'Village inconnu' }}</h4>
                    <p class="text-sm text-gray-500">{{ village.type || 'Village' }}</p>
                  </div>
                  <span 
                    :class="getVillageStatusClass(village)"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getVillageStatusLabel(village) }}
                  </span>
                </div>
                <div class="flex items-center space-x-2">
                  <Link
                    :href="route('village-results.show', [meeting.id, village.id])"
                    class="inline-flex items-center px-3 py-1 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Voir détails
                  </Link>
                </div>
              </div>

              <!-- Résultats du village s'ils existent -->
              <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                  <div class="text-lg font-bold text-blue-600">
                    {{ (getVillageResult(village.id).enrollment_rate || 0).toFixed(1) }}%
                  </div>
                  <div class="text-xs text-blue-600">Enrôlement</div>
                  <div class="text-xs text-gray-500">
                    {{ getVillageResult(village.id).people_enrolled_count || 0 }}/{{ getVillageResult(village.id).people_to_enroll_count || 0 }}
                  </div>
                  <!-- Debug: Afficher les valeurs brutes -->
                  <div class="text-xs text-gray-400">
                    Debug: {{ getVillageResult(village.id).enrollment_rate }}
                  </div>
                </div>
                <div class="text-center p-3 bg-purple-50 rounded-lg">
                  <div class="text-lg font-bold text-purple-600">
                    {{ (getVillageResult(village.id).cmu_distribution_rate || 0).toFixed(1) }}%
                  </div>
                  <div class="text-xs text-purple-600">Distribution CMU</div>
                  <div class="text-xs text-gray-500">
                    {{ getVillageResult(village.id).cmu_cards_distributed_count || 0 }}/{{ getVillageResult(village.id).cmu_cards_available_count || 0 }}
                  </div>
                  <!-- Debug: Afficher les valeurs brutes -->
                  <div class="text-xs text-gray-400">
                    Debug: {{ getVillageResult(village.id).cmu_distribution_rate }}
                  </div>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                  <div class="text-lg font-bold text-green-600">
                    {{ (getVillageResult(village.id).complaint_processing_rate || 0).toFixed(1) }}%
                  </div>
                  <div class="text-xs text-green-600">Traitement</div>
                  <div class="text-xs text-gray-500">
                    {{ getVillageResult(village.id).complaints_processed_count || 0 }}/{{ getVillageResult(village.id).complaints_received_count || 0 }}
                  </div>
                  <!-- Debug: Afficher les valeurs brutes -->
                  <div class="text-xs text-gray-400">
                    Debug: {{ getVillageResult(village.id).complaint_processing_rate }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

const props = defineProps({
  meeting: Object,
  villages: Array,
  results: Array,
  totals: Object,
  globalRates: Object,
  summary: Object,
  canValidate: Boolean,
})

// Debug: Afficher les props reçues
console.log('Props reçues:', {
  meeting: props.meeting,
  villages: props.villages,
  results: props.results
})

// Debug: Afficher les résultats détaillés
console.log('Résultats détaillés:', props.results?.map(result => ({
  id: result.id,
  localite_id: result.localite_id,
  people_to_enroll_count: result.people_to_enroll_count,
  people_enrolled_count: result.people_enrolled_count,
  enrollment_rate: result.enrollment_rate,
  cmu_distribution_rate: result.cmu_distribution_rate,
  complaint_processing_rate: result.complaint_processing_rate
})))

// Debug: Vérifier si les propriétés calculées sont présentes
props.results?.forEach(result => {
  console.log(`Village ${result.localite_id}:`, {
    enrollment_rate: result.enrollment_rate,
    cmu_distribution_rate: result.cmu_distribution_rate,
    complaint_processing_rate: result.complaint_processing_rate,
    has_enrollment_rate: 'enrollment_rate' in result,
    has_cmu_rate: 'cmu_distribution_rate' in result,
    has_complaint_rate: 'complaint_processing_rate' in result
  })
})

const toast = useToast()

// S'assurer que les props sont définies
const safeVillages = computed(() => {
  const villages = props.villages || []
  console.log('safeVillages computed - villages bruts:', villages)
  
  // Vérifier chaque village individuellement et convertir en objets simples
  const validVillages = []
  for (const village of villages) {
    console.log('Vérification village:', village)
    console.log('Village.id:', village?.id)
    console.log('Village.name:', village?.name)
    
    // Critères plus souples
    if (village && (village.id || village.name)) {
      // Convertir le Proxy en objet simple
      const simpleVillage = {
        id: village.id,
        name: village.name,
        type: village.type
      }
      console.log('Village valide (simple):', simpleVillage)
      validVillages.push(simpleVillage)
    } else {
      console.log('Village invalide:', village)
    }
  }
  
  console.log('safeVillages computed - villages valides:', validVillages)
  console.log('Nombre de villages valides:', validVillages.length)
  return validVillages
})
const safeResults = computed(() => props.results || [])

const villageForms = ref({})
const savingVillage = ref(null)
const savingAll = ref(false)

// Initialiser les formulaires pour chaque village
const initializeVillageForms = () => {
  safeVillages.value.forEach(village => {
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
  })
}

// Vérifier si un village a des résultats
const hasVillageResult = (villageId) => {
  if (!villageId) return true // Afficher tous les villages même sans résultats
  return safeResults.value.some(result => result.localite_id === villageId)
}

// Obtenir les résultats d'un village
const getVillageResult = (villageId) => {
  if (!villageId) return {}
  const result = safeResults.value.find(result => result.localite_id === villageId) || {}
  
  // Calculer les pourcentages si ils ne sont pas définis
  if (result && !result.enrollment_rate && result.people_to_enroll_count > 0) {
    result.enrollment_rate = Math.round((result.people_enrolled_count / result.people_to_enroll_count) * 100 * 10) / 10
    console.log('Enrollment rate calculé:', result.enrollment_rate, 'pour village', villageId)
  }
  
  if (result && !result.cmu_distribution_rate && result.cmu_cards_available_count > 0) {
    result.cmu_distribution_rate = Math.round((result.cmu_cards_distributed_count / result.cmu_cards_available_count) * 100 * 10) / 10
    console.log('CMU distribution rate calculé:', result.cmu_distribution_rate, 'pour village', villageId)
  }
  
  if (result && !result.complaint_processing_rate && result.complaints_received_count > 0) {
    result.complaint_processing_rate = Math.round((result.complaints_processed_count / result.complaints_received_count) * 100 * 10) / 10
    console.log('Complaint processing rate calculé:', result.complaint_processing_rate, 'pour village', villageId)
  }
  
  return result
}

// Obtenir le statut d'un village
const getVillageStatusLabel = (village) => {
  if (!village || !village.id) return 'Non saisi'
  const result = getVillageResult(village.id)
  if (!result.status) return 'Non saisi'
  return {
    'draft': 'Brouillon',
    'submitted': 'Soumis',
    'validated': 'Validé'
  }[result.status] || 'Inconnu'
}

const getVillageStatusClass = (village) => {
  if (!village || !village.id) return 'bg-gray-100 text-gray-700'
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
  if (!villageForms.value[village.id]) {
    toast.error('Formulaire non initialisé pour ce village')
    return
  }
  
  savingVillage.value = village.id
  
  try {
    await axios.post(route('village-results.store', [props.meeting.id, village.id]), villageForms.value[village.id])
    
    toast.success(`Résultats de ${village.name} sauvegardés`)
    // Recharger la page pour mettre à jour les données
    window.location.reload()
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
    const promises = safeVillages.value.map(village => {
      if (!villageForms.value[village.id]) {
        console.warn(`Formulaire non initialisé pour le village ${village.name}`)
        return Promise.resolve()
      }
      return axios.post(route('village-results.store', [props.meeting.id, village.id]), villageForms.value[village.id])
    })
    
    await Promise.all(promises)
    toast.success('Tous les résultats ont été sauvegardés')
    // Recharger la page pour mettre à jour les données
    window.location.reload()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    toast.error('Erreur lors de la sauvegarde de certains villages')
  } finally {
    savingAll.value = false
  }
}

// Actualiser les résultats
const refreshResults = () => {
  window.location.reload()
}

// Fonction pour mettre à jour un champ du formulaire d'un village
const updateVillageForm = (villageId, field, value) => {
  if (!villageForms.value[villageId]) {
    villageForms.value[villageId] = {}
  }
  villageForms.value[villageId][field] = value
}

// Initialiser les données au montage
onMounted(() => {
  console.log('onMounted - safeVillages:', safeVillages.value)
  console.log('onMounted - nombre de villages:', safeVillages.value.length)
  initializeVillageForms()
})
</script> 