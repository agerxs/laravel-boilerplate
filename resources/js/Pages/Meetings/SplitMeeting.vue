<template>
  <AppLayout :title="'Éclater la réunion - ' + meeting.title">
    <!-- Début de la page principale -->
    <div class="py-6">
      <!-- En-tête personnalisé pour cette page -->
      <div class="mb-6 bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center">
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Éclater la réunion
          </h2>
          <Link
            :href="route('meetings.show', meeting.id)"
            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
          >
            <ArrowLeftIcon class="w-4 h-4 mr-2" />
            Retour à la réunion
          </Link>
        </div>
      </div>

      <!-- Contenu principal -->
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Informations de la réunion parent -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Réunion principale
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Titre</label>
                <p class="mt-1 text-sm text-gray-900">{{ meeting.title }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700">Comité local</label>
                <p class="mt-1 text-sm text-gray-900">{{ meeting.local_committee.name }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Villages disponibles -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Créer les sous-réunions
            </h3>
            
            <div v-if="loading" class="flex justify-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <div v-else-if="error" class="text-center py-8">
              <div class="text-red-600 mb-4">
                <ExclamationTriangleIcon class="h-12 w-12 mx-auto" />
              </div>
              <p class="text-gray-600 mb-4">{{ error }}</p>
              <button
                @click="loadVillages"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
              >
                Réessayer
              </button>
            </div>

            <div v-else-if="villages.length === 0" class="text-center py-8">
              <div class="text-gray-400 mb-4">
                <MapIcon class="h-12 w-12 mx-auto" />
              </div>
              <p class="text-gray-500">Aucun village disponible pour cette réunion</p>
            </div>

            <div v-else>
              <!-- Instructions -->
              <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h4 class="text-sm font-medium text-blue-900 mb-2">Instructions :</h4>
                <p class="text-sm text-blue-700">
                  Sélectionnez les villages pour chaque sous-réunion et spécifiez un lieu commun pour chaque groupe.
                  Une sous-réunion peut regrouper plusieurs villages.
                  <strong>Important : Un village ne peut pas être dans plusieurs sous-réunions.</strong>
                </p>
                <div class="mt-2 text-sm text-blue-600">
                  <span class="font-medium">Progression :</span> 
                  {{ totalSelectedVillages }} / {{ villages.length }} villages assignés
                </div>
              </div>

              <!-- Message d'erreur si des villages sont dupliqués -->
              <div v-if="hasDuplicateVillages" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <h4 class="text-sm font-medium text-red-900 mb-2">Erreur :</h4>
                <p class="text-sm text-red-700">
                  Certains villages sont sélectionnés dans plusieurs sous-réunions. Un village ne peut appartenir qu'à une seule sous-réunion.
                </p>
              </div>

              <!-- Liste des sous-réunions -->
              <div class="space-y-6">
                <div
                  v-for="(subMeeting, subMeetingIndex) in subMeetings"
                  :key="subMeetingIndex"
                  class="border border-gray-200 rounded-lg p-4"
                >
                  <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-medium text-gray-900">
                      Sous-réunion {{ subMeetingIndex + 1 }}
                    </h4>
                    <button
                      v-if="subMeetings.length > 1"
                      @click="removeSubMeeting(subMeetingIndex)"
                      class="text-red-600 hover:text-red-800 text-sm"
                    >
                      Supprimer cette sous-réunion
                    </button>
                  </div>

                  <!-- Lieu de la sous-réunion -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Lieu de la sous-réunion *
                    </label>
                    <input
                      type="text"
                      v-model="subMeeting.location"
                      placeholder="Ex: Salle polyvalente de [Nom du village principal]"
                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>

                  <!-- Villages sélectionnés pour cette sous-réunion -->
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      Villages de cette sous-réunion ({{ subMeeting.villages.length }} sélectionnés)
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                      <div
                        v-for="village in subMeeting.villages"
                        :key="village.id"
                        class="flex items-center justify-between p-2 bg-gray-50 rounded border"
                      >
                        <span class="text-sm text-gray-700">{{ village.name }}</span>
                        <button
                          @click="removeVillageFromSubMeeting(subMeetingIndex, village)"
                          class="text-red-500 hover:text-red-700 text-xs"
                        >
                          ×
                        </button>
                      </div>
                    </div>
                  </div>

                                     <!-- Ajouter des villages à cette sous-réunion -->
                   <div>
                     <label class="block text-sm font-medium text-gray-700 mb-2">
                       Ajouter des villages
                     </label>
                     <div v-if="availableVillagesForSubMeeting(subMeetingIndex).length === 0" class="text-sm text-gray-500 italic">
                       Tous les villages ont été assignés à d'autres sous-réunions
                     </div>
                     <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                       <div
                         v-for="village in availableVillagesForSubMeeting(subMeetingIndex)"
                         :key="village.id"
                         class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded hover:bg-gray-50"
                       >
                         <span class="text-sm text-gray-700">{{ village.name }}</span>
                         <button
                           @click="addVillageToSubMeeting(subMeetingIndex, village)"
                           class="text-blue-600 hover:text-blue-800 text-xs"
                         >
                           +
                         </button>
                       </div>
                     </div>
                   </div>
                </div>

                <!-- Bouton pour ajouter une nouvelle sous-réunion -->
                <div class="text-center">
                  <button
                    @click="addSubMeeting"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                  >
                    <PlusIcon class="w-4 h-4 mr-2" />
                    Ajouter une sous-réunion
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4 pt-6 border-t">
            <Link
              :href="route('meetings.show', meeting.id)"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
            >
              Annuler
            </Link>
            <button
              @click="splitMeeting"
              :disabled="!canSplitMeeting || processing"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
            >
              <span v-if="processing">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Éclatement en cours...
              </span>
              <span v-else>
                Éclater la réunion ({{ totalSubMeetings }} sous-réunions)
              </span>
            </button>
          </div>

          <!-- Message de succès après éclatement -->
          <div v-if="splitResult" class="mt-6 rounded-md bg-green-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <CheckCircleIcon class="h-5 w-5 text-green-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800">
                  Réunion éclatée avec succès !
                </h3>
                <p class="mt-2 text-sm text-green-700">
                  {{ splitResult.length }} sous-réunions ont été créées. Vous serez redirigé vers la réunion principale.
                </p>
                <div class="mt-4">
                  <Link
                    :href="route('meetings.show', meeting.id)"
                    class="text-sm font-medium text-green-800 hover:text-green-900 underline"
                  >
                    Voir la réunion principale
                  </Link>
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
import { ref, onMounted, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  MapIcon,
  CheckCircleIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  meeting: {
    type: Object,
    required: true
  }
})

const villages = ref([])
const subMeetings = ref([])
const loading = ref(true)
const processing = ref(false)
const error = ref(null)
const splitResult = ref(null)

// Computed properties
const totalSubMeetings = computed(() => {
  return subMeetings.value.filter(sm => sm.villages.length > 0 && sm.location).length
})

const totalSelectedVillages = computed(() => {
  return subMeetings.value.flatMap(sm => sm.villages).length
})

const hasDuplicateVillages = computed(() => {
  const validSubMeetings = subMeetings.value.filter(sm => sm.villages.length > 0)
  if (validSubMeetings.length === 0) return false
  
  const allVillageIds = validSubMeetings.flatMap(sm => sm.villages.map(v => v.id))
  const uniqueVillageIds = [...new Set(allVillageIds)]
  
  return allVillageIds.length !== uniqueVillageIds.length
})

const canSplitMeeting = computed(() => {
  // Vérifier qu'il y a au moins une sous-réunion avec des villages et un lieu
  const validSubMeetings = subMeetings.value.filter(sm => sm.villages.length > 0 && sm.location)
  
  if (validSubMeetings.length === 0) return false
  
  // Vérifier qu'aucun village n'est dupliqué
  const allVillageIds = validSubMeetings.flatMap(sm => sm.villages.map(v => v.id))
  const uniqueVillageIds = [...new Set(allVillageIds)]
  
  return allVillageIds.length === uniqueVillageIds.length
})

// Charger les villages disponibles
const loadVillages = async () => {
  try {
    loading.value = true
    error.value = null

    const response = await axios.get(route('api.meetings.split.villages', props.meeting.id))

    if (response.data.success) {
      villages.value = response.data.data.villages
      // Initialiser avec une première sous-réunion
      if (subMeetings.value.length === 0) {
        addSubMeeting()
      }
    } else {
      error.value = response.data.message || 'Erreur lors du chargement des villages'
    }
  } catch (e) {
    error.value = 'Erreur réseau lors du chargement des villages'
    console.error('Erreur lors du chargement des villages:', e)
  } finally {
    loading.value = false
  }
}

// Ajouter une nouvelle sous-réunion
const addSubMeeting = () => {
  subMeetings.value.push({
    location: '',
    villages: []
  })
}

// Supprimer une sous-réunion
const removeSubMeeting = (index) => {
  subMeetings.value.splice(index, 1)
}

// Ajouter un village à une sous-réunion
const addVillageToSubMeeting = (subMeetingIndex, village) => {
  subMeetings.value[subMeetingIndex].villages.push(village)
}

// Retirer un village d'une sous-réunion
const removeVillageFromSubMeeting = (subMeetingIndex, village) => {
  const subMeeting = subMeetings.value[subMeetingIndex]
  const index = subMeeting.villages.findIndex(v => v.id === village.id)
  if (index > -1) {
    subMeeting.villages.splice(index, 1)
  }
}

// Obtenir les villages disponibles pour une sous-réunion
const availableVillagesForSubMeeting = (subMeetingIndex) => {
  // Récupérer tous les villages déjà sélectionnés dans toutes les sous-réunions
  const allSelectedVillageIds = subMeetings.value.flatMap(sm => sm.villages.map(v => v.id))
  return villages.value.filter(village => !allSelectedVillageIds.includes(village.id))
}

// Éclater la réunion
const splitMeeting = async () => {
  if (!canSplitMeeting.value) {
    alert('Veuillez créer au moins une sous-réunion avec des villages et un lieu.')
    return
  }

  processing.value = true
  try {
    const subMeetingsData = subMeetings.value
      .filter(sm => sm.villages.length > 0 && sm.location)
      .map(sm => ({
        location: sm.location,
        villages: sm.villages.map(v => ({ id: v.id, name: v.name }))
      }))

    const response = await axios.post(route('api.meetings.split', props.meeting.id), {
      sub_meetings: subMeetingsData
    })

    if (response.data.success) {
      splitResult.value = response.data.data

      // Rediriger vers la page de la réunion parent après un délai
      setTimeout(() => {
        window.location.href = `/meetings/${props.meeting.id}`
      }, 3000)
    }
  } catch (e) {
    console.error('Erreur lors de l\'éclatement:', e)
    alert('Erreur lors de l\'éclatement de la réunion: ' + (e.response?.data?.message || e.message))
  } finally {
    processing.value = false
  }
}

// Formater une date
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

onMounted(() => {
  loadVillages()
})
</script> 