<template>
  <Head title="Compte Rendu de Réunion" />

  <AppLayout title="Compte Rendu de Réunion">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête avec informations de la réunion -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">{{ meeting.title }}</h2>
              <p class="text-gray-600">{{ formatDate(meeting.scheduled_date) }} à {{ meeting.scheduled_time }}</p>
              <p class="text-gray-600">{{ meeting.location }}</p>
            </div>
            <div class="text-right">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                    :class="{
                      'bg-gray-100 text-gray-800': meeting.status === 'draft',
                      'bg-blue-100 text-blue-800': meeting.status === 'scheduled',
                      'bg-green-100 text-green-800': meeting.status === 'completed',
                      'bg-yellow-100 text-yellow-800': meeting.status === 'cancelled'
                    }">
                {{ getStatusLabel(meeting.status) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Formulaire du compte rendu -->
        <form @submit.prevent="saveMinutes" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="space-y-8">
            <!-- Section 1: Contenu du compte rendu -->
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Contenu du compte rendu</h3>
              <div>
                <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                  Contenu principal
                </label>
                <textarea
                  id="content"
                  v-model="form.content"
                  rows="6"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Résumé détaillé de la réunion, points abordés, décisions prises..."
                ></textarea>
              </div>
            </div>

            <!-- Section 2: Difficultés et recommandations -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Difficultés rencontrées</h3>
                <div>
                  <label for="difficulties" class="block text-sm font-medium text-gray-700 mb-2">
                    Difficultés <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="difficulties"
                    v-model="form.difficulties"
                    rows="4"
                    required
                    minlength="10"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Décrivez les difficultés rencontrées pendant la réunion (minimum 10 caractères)..."
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-500">
                    Ce champ est obligatoire et doit contenir au moins 10 caractères.
                  </p>
                </div>
              </div>

              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recommandations</h3>
                <div>
                  <label for="recommendations" class="block text-sm font-medium text-gray-700 mb-2">
                    Recommandations <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="recommendations"
                    v-model="form.recommendations"
                    rows="4"
                    required
                    minlength="10"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Formulez vos recommandations et suggestions d'amélioration (minimum 10 caractères)..."
                  ></textarea>
                  <p class="mt-1 text-sm text-gray-500">
                    Ce champ est obligatoire et doit contenir au moins 10 caractères.
                  </p>
                </div>
              </div>
            </div>

            <!-- Section 3: Résultats des villages -->
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Résultats des villages</h3>
              <p class="text-sm text-gray-600 mb-6">
                Statistiques et résultats obtenus lors de la réunion
              </p>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Enrôlements -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium text-gray-900 mb-3">Enrôlements</h4>
                  <div class="space-y-3">
                    <div>
                      <label for="people_to_enroll_count" class="block text-sm font-medium text-gray-700">
                        Personnes à enrôler
                      </label>
                      <input
                        type="number"
                        id="people_to_enroll_count"
                        v-model="form.people_to_enroll_count"
                        min="0"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                    </div>
                    <div>
                      <label for="people_enrolled_count" class="block text-sm font-medium text-gray-700">
                        Personnes enrôlées
                      </label>
                      <input
                        type="number"
                        id="people_enrolled_count"
                        v-model="form.people_enrolled_count"
                        min="0"
                        :max="form.people_to_enroll_count || undefined"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                      <div v-if="form.people_to_enroll_count && form.people_enrolled_count" class="mt-1">
                        <div class="flex justify-between text-sm text-gray-600">
                          <span>Taux d'enrôlement</span>
                          <span class="font-medium">{{ calculateEnrollmentRate() }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div class="bg-blue-600 h-2 rounded-full" :style="{ width: calculateEnrollmentRate() + '%' }"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Cartes CMU -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium text-gray-900 mb-3">Cartes CMU</h4>
                  <div class="space-y-3">
                    <div>
                      <label for="cmu_cards_available_count" class="block text-sm font-medium text-gray-700">
                        Cartes disponibles
                      </label>
                      <input
                        type="number"
                        id="cmu_cards_available_count"
                        v-model="form.cmu_cards_available_count"
                        min="0"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                    </div>
                    <div>
                      <label for="cmu_cards_distributed_count" class="block text-sm font-medium text-gray-700">
                        Cartes distribuées
                      </label>
                      <input
                        type="number"
                        id="cmu_cards_distributed_count"
                        v-model="form.cmu_cards_distributed_count"
                        min="0"
                        :max="form.cmu_cards_available_count || undefined"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                      <div v-if="form.cmu_cards_available_count && form.cmu_cards_distributed_count" class="mt-1">
                        <div class="flex justify-between text-sm text-gray-600">
                          <span>Taux de distribution</span>
                          <span class="font-medium">{{ calculateCmuDistributionRate() }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div class="bg-green-600 h-2 rounded-full" :style="{ width: calculateCmuDistributionRate() + '%' }"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Réclamations -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <h4 class="font-medium text-gray-900 mb-3">Réclamations</h4>
                  <div class="space-y-3">
                    <div>
                      <label for="complaints_received_count" class="block text-sm font-medium text-gray-700">
                        Réclamations reçues
                      </label>
                      <input
                        type="number"
                        id="complaints_received_count"
                        v-model="form.complaints_received_count"
                        min="0"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                    </div>
                    <div>
                      <label for="complaints_processed_count" class="block text-sm font-medium text-gray-700">
                        Réclamations traitées
                      </label>
                      <input
                        type="number"
                        id="complaints_processed_count"
                        v-model="form.complaints_processed_count"
                        min="0"
                        :max="form.complaints_received_count || undefined"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                      />
                      <div v-if="form.complaints_received_count && form.complaints_processed_count" class="mt-1">
                        <div class="flex justify-between text-sm text-gray-600">
                          <span>Taux de traitement</span>
                          <span class="font-medium">{{ calculateComplaintsProcessingRate() }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                          <div class="bg-orange-600 h-2 rounded-full" :style="{ width: calculateComplaintsProcessingRate() + '%' }"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 4: Statut et actions -->
            <div class="border-t pt-6">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">Statut du compte rendu</h3>
                  <p class="text-sm text-gray-600">
                    Choisissez le statut approprié pour ce compte rendu
                  </p>
                </div>
                <div class="flex items-center space-x-4">
                  <select
                    v-model="form.status"
                    class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="draft">Brouillon</option>
                    <option value="pending_validation">En attente de validation</option>
                    <option value="published">Publié</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex items-center justify-between pt-6 border-t">
              <button
                type="button"
                @click="goBack"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium py-2 px-4 rounded-lg transition-colors"
              >
                Retour
              </button>
              <div class="flex items-center space-x-3">
                <button
                  type="button"
                  @click="saveAsDraft"
                  class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                >
                  Enregistrer en brouillon
                </button>
                <button
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
                >
                  {{ form.status === 'pending_validation' ? 'Soumettre pour validation' : 'Enregistrer' }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed } from 'vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'

interface Meeting {
  id: number;
  title: string;
  scheduled_date: string;
  scheduled_time: string;
  location: string;
  status: string;
}

interface MeetingMinutes {
  id?: number;
  content: string;
  status: string;
  difficulties: string;
  recommendations: string;
  people_to_enroll_count: number | null;
  people_enrolled_count: number | null;
  cmu_cards_available_count: number | null;
  cmu_cards_distributed_count: number | null;
  complaints_received_count: number | null;
  complaints_processed_count: number | null;
}

interface Props {
  meeting: Meeting;
  minutes?: MeetingMinutes;
}

const props = defineProps<Props>()
const toast = useToast()

// Initialiser le formulaire avec les données existantes ou des valeurs par défaut
const form = ref<MeetingMinutes>({
  content: props.minutes?.content || '',
  status: props.minutes?.status || 'draft',
  difficulties: props.minutes?.difficulties || '',
  recommendations: props.minutes?.recommendations || '',
  people_to_enroll_count: props.minutes?.people_to_enroll_count || null,
  people_enrolled_count: props.minutes?.people_enrolled_count || null,
  cmu_cards_available_count: props.minutes?.cmu_cards_available_count || null,
  cmu_cards_distributed_count: props.minutes?.cmu_cards_distributed_count || null,
  complaints_received_count: props.minutes?.complaints_received_count || null,
  complaints_processed_count: props.minutes?.complaints_processed_count || null,
})

// Validation côté client
const validateForm = (): boolean => {
  if (!form.value.difficulties || form.value.difficulties.trim().length < 10) {
    toast.error('Le champ "Difficultés rencontrées" est obligatoire et doit contenir au moins 10 caractères')
    return false
  }
  
  if (!form.value.recommendations || form.value.recommendations.trim().length < 10) {
    toast.error('Le champ "Recommandations" est obligatoire et doit contenir au moins 10 caractères')
    return false
  }

  return true
}

// Sauvegarder le compte rendu
const saveMinutes = async () => {
  if (!validateForm()) return

  try {
    if (props.minutes?.id) {
      // Mise à jour
      await axios.put(route('minutes.update', props.minutes.id), form.value)
      toast.success('Compte rendu mis à jour avec succès')
    } else {
      // Création
      await axios.post(route('minutes.store', props.meeting.id), form.value)
      toast.success('Compte rendu créé avec succès')
    }
    
    // Rediriger vers la page de la réunion
    window.location.href = route('meetings.show', props.meeting.id)
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      toast.error('Une erreur est survenue lors de la sauvegarde')
    }
  }
}

// Sauvegarder en brouillon
const saveAsDraft = async () => {
  form.value.status = 'draft'
  await saveMinutes()
}

// Calculs des taux
const calculateEnrollmentRate = (): number => {
  if (!form.value.people_to_enroll_count || !form.value.people_enrolled_count) return 0
  return Math.round((form.value.people_enrolled_count / form.value.people_to_enroll_count) * 100)
}

const calculateCmuDistributionRate = (): number => {
  if (!form.value.cmu_cards_available_count || !form.value.cmu_cards_distributed_count) return 0
  return Math.round((form.value.cmu_cards_distributed_count / form.value.cmu_cards_available_count) * 100)
}

const calculateComplaintsProcessingRate = (): number => {
  if (!form.value.complaints_received_count || !form.value.complaints_processed_count) return 0
  return Math.round((form.value.complaints_processed_count / form.value.complaints_received_count) * 100)
}

// Utilitaires
const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getStatusLabel = (status: string): string => {
  const labels = {
    'draft': 'Brouillon',
    'scheduled': 'Programmée',
    'completed': 'Terminée',
    'cancelled': 'Annulée'
  }
  return labels[status] || status
}

const goBack = () => {
  window.history.back()
}
</script>
