<template>
  <AppLayout :title="`Liste de présence - ${meeting.title}`">
    <!-- Debug info -->
    <div class="hidden">
      {{ debugButtonConditions }}
    </div>

    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Liste de présence - {{ meeting.title }}
        </h2>
        <div class="flex space-x-4">
          <button
            @click="handleReturnToMeeting"
            class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-800 rounded-md text-sm font-medium hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à la réunion
          </button>
          <a
            :href="route('meetings.attendance.export', meeting.id)"
            target="_blank"
            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium hover:bg-green-200"
          >
            Exporter PDF
          </a>
          <button
            v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
            @click="confirmFinalize"
            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium hover:bg-blue-200"
          >
            Finaliser et terminer la réunion
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Afficher un message si la réunion est déjà terminée -->
        <div v-if="meeting.is_completed" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <InformationCircleIcon class="h-5 w-5 text-yellow-400" aria-hidden="true" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-yellow-700">
                Cette réunion est déjà marquée comme terminée. Vous pouvez consulter la liste de présence mais vous ne pouvez plus la modifier.
              </p>
            </div>
          </div>
        </div>

        <!-- Message informatif pour les sous-préfets -->
        <div v-if="isSubPrefect && !meeting.is_completed" class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <InformationCircleIcon class="h-5 w-5 text-blue-400" aria-hidden="true" />
            </div>
            <div class="ml-3">
              <p class="text-sm text-blue-700">
                Vous êtes en mode consultation. Seuls les secrétaires peuvent modifier la liste de présence, et uniquement pour les réunions en statut "planifié".
              </p>
            </div>
          </div>
        </div>

        <!-- Informations de la réunion -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900">Informations de la réunion</h3>
            <div class="mt-4 grid grid-cols-2 gap-4">
              <div>
                <p class="text-sm font-medium text-gray-500">Titre</p>
                <p class="mt-1 text-sm text-gray-900">{{ meeting.title }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Date</p>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(meeting.scheduled_date) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Lieu</p>
                <p class="mt-1 text-sm text-gray-900">{{ meeting.location }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Comité local</p>
                <p class="mt-1 text-sm text-gray-900">{{ meeting.local_committee.name }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Analyse géographique -->
        <GeographicAnalysis 
          :attendees="attendees" 
          :max-distance="100"
        />

        <!-- Barre de recherche -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-4 sm:p-6">
            <div class="flex justify-between items-center">
              <div class="relative flex-grow max-w-sm">
                <input
                  v-model="search"
                  type="text"
                  placeholder="Rechercher un participant..."
                  class="w-full pl-10 pr-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <div class="absolute left-3 top-2.5 text-gray-400">
                  <MagnifyingGlassIcon class="h-5 w-5" />
                </div>
              </div>
              <div class="ml-4">
                <select
                  v-model="selectedVillage"
                  class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                >
                  <option value="">Tous les villages</option>
                  <option v-for="village in villages" :key="village" :value="village">
                    {{ village }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des participants -->
        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nom
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Village
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Rôle
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Remplaçant
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="attendee in filteredAttendees" :key="attendee.id" :class="{
                  'bg-green-50': attendee.attendance_status === 'present',
                  'bg-red-50': attendee.attendance_status === 'absent',
                  'bg-yellow-50': attendee.attendance_status === 'replaced'
                }">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div v-if="attendee.presence_photo" class="mr-3">
                        <img 
                          :src="`/storage/${attendee.presence_photo}`" 
                          class="h-10 w-10 rounded-full object-cover border-2 border-primary-500 cursor-pointer hover:opacity-80 transition-opacity"
                          @click="showPhotoModal(attendee)"
                        />
                      </div>
                      <div>
                        <!-- Nom du représentant -->
                        <div class="text-base font-semibold text-gray-900">{{ attendee.name }}</div>
                        <!-- Village du représentant -->
                        <div v-if="attendee.village?.name" class="text-sm font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded-full inline-block mt-1">
                          🏘️ {{ attendee.village.name }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500">-</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-700">{{ attendee.role || '-' }}</div>
                    <div v-if="attendee.phone" class="text-xs text-gray-500 mt-1">📞 {{ attendee.phone }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <StatusBadge :status="attendee.attendance_status" />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div v-if="attendee.replacement_name" class="text-sm text-gray-900">
                      {{ attendee.replacement_name }}
                      <div class="text-xs text-gray-500">{{ attendee.replacement_role || 'Pas de rôle' }}</div>
                    </div>
                    <div v-else class="text-sm text-gray-500">-</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
                        @click="markPresent(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.attendance_status === 'present' 
                            ? 'bg-green-100 text-green-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-600'
                        ]"
                        :title="attendee.attendance_status === 'present' ? 'Déjà marqué comme présent' : 'Marquer comme présent'"
                      >
                        <CheckCircleIcon class="h-5 w-5" />
                      </button>
                      <button
                        v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
                        @click="markAbsent(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.attendance_status === 'absent' 
                            ? 'bg-red-100 text-red-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-600'
                        ]"
                        :title="attendee.attendance_status === 'absent' ? 'Déjà marqué comme absent' : 'Marquer comme absent'"
                      >
                        <XCircleIcon class="h-5 w-5" />
                      </button>
                      <button
                        v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
                        @click="showReplacementModal(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.attendance_status === 'replaced' 
                            ? 'bg-yellow-100 text-yellow-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-yellow-100 hover:text-yellow-600'
                        ]"
                        :title="attendee.attendance_status === 'replaced' ? 'Modifier le remplaçant' : 'Ajouter un remplaçant'"
                      >
                        <ArrowPathIcon class="h-5 w-5" />
                      </button>
                      <button
                        v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
                        @click="showCommentModal(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.comments 
                            ? 'bg-blue-100 text-blue-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-600'
                        ]"
                        :title="attendee.comments ? 'Modifier le commentaire' : 'Ajouter un commentaire'"
                      >
                        <ChatBubbleLeftIcon class="h-5 w-5" />
                      </button>
                      <button
                        v-if="isSecretary && (meeting.status === 'planned' || meeting.status === 'scheduled') && !meeting.is_completed"
                        @click="showPhotoModal(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.presence_photo 
                            ? 'bg-green-100 text-green-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-600'
                        ]"
                        :title="attendee.presence_photo ? 'Voir la photo' : 'Prendre une photo'"
                      >
                        <CameraIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Statistiques de présence -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm font-medium text-gray-500">Total</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ attendanceStats.total }}</div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm font-medium text-gray-500">Présents</div>
            <div class="mt-1 text-2xl font-semibold text-green-600">{{ attendanceStats.present }}</div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm font-medium text-gray-500">Remplacés</div>
            <div class="mt-1 text-2xl font-semibold text-yellow-600">{{ attendanceStats.replaced }}</div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm font-medium text-gray-500">Absents</div>
            <div class="mt-1 text-2xl font-semibold text-red-600">{{ attendanceStats.absent }}</div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow">
            <div class="text-sm font-medium text-gray-500">Taux de présence</div>
            <div class="mt-1 text-2xl font-semibold" :class="{
              'text-green-600': attendanceStats.presentPercentage >= 80,
              'text-yellow-600': attendanceStats.presentPercentage >= 50 && attendanceStats.presentPercentage < 80,
              'text-red-600': attendanceStats.presentPercentage < 50
            }">
              {{ attendanceStats.presentPercentage }}%
            </div>
          </div>
        </div>

        <!-- Indicateur des participants incomplets -->
        <div v-if="getIncompleteParticipants().length > 0" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <div class="flex items-start">
            <svg class="w-5 h-5 text-yellow-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <div>
              <h4 class="text-sm font-medium text-yellow-800">
                {{ getIncompleteParticipants().length }} participant(s) avec des informations incomplètes
              </h4>
              <p class="text-sm text-yellow-700 mt-1">
                Certains participants n'ont pas encore de statut défini ou de photo de présence. 
                Ces informations sont recommandées pour une gestion complète de la présence.
              </p>
              <div class="mt-2 text-xs text-yellow-600">
                <p class="font-medium">Participants concernés :</p>
                <ul class="mt-1 space-y-1">
                  <li v-for="participant in getIncompleteParticipants()" :key="participant.id" class="flex items-center space-x-2">
                    <span>• {{ participant.name }}</span>
                    <span v-if="!participant.attendance_status || participant.attendance_status === 'expected'" class="px-2 py-0.5 bg-yellow-200 text-yellow-800 rounded text-xs">
                      Statut manquant
                    </span>
                    <span v-if="(participant.attendance_status === 'present' || participant.attendance_status === 'replaced') && !participant.presence_photo" class="px-2 py-0.5 bg-yellow-200 text-yellow-800 rounded text-xs">
                      Photo manquante
                    </span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Bouton de retour en bas de page -->
        <div class="flex justify-center mt-8 mb-4">
          <button
            @click="handleReturnToMeeting"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retourner à la page de la réunion
          </button>
        </div>
      </div>
    </div>

    <!-- Modal pour les remplaçants -->
    <Modal :show="replacementModalOpen" @close="closeReplacementModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Définir un remplaçant pour {{ selectedAttendee?.name }}
        </h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Nom du remplaçant
          </label>
          <input
            v-model="replacementData.name"
            type="text"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            placeholder="Nom complet du remplaçant..."
            required
          />
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Téléphone du remplaçant
          </label>
          <input
            v-model="replacementData.phone"
            type="text"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            placeholder="Numéro de téléphone..."
          />
        </div>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Rôle du remplaçant
          </label>
          <input
            v-model="replacementData.role"
            type="text"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            placeholder="Rôle du remplaçant..."
          />
        </div>
        
        <div class="flex justify-end">
          <button 
            @click="closeReplacementModal"
            class="bg-white px-4 py-2 border border-gray-300 rounded-md mr-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Annuler
          </button>
          <button 
            @click="saveReplacement"
            class="bg-yellow-600 px-4 py-2 rounded-md text-sm font-medium text-white hover:bg-yellow-700"
            :disabled="!replacementData.name"
          >
            Enregistrer le remplaçant
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal pour les commentaires -->
    <Modal :show="commentModalOpen" @close="closeCommentModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Ajouter un commentaire pour {{ selectedAttendee?.name }}
        </h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Commentaire
          </label>
          <textarea
            v-model="commentData.text"
            rows="3"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
            placeholder="Saisir un commentaire..."
            required
          ></textarea>
        </div>
        
        <div class="flex justify-end">
          <button 
            @click="closeCommentModal"
            class="bg-white px-4 py-2 border border-gray-300 rounded-md mr-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Annuler
          </button>
          <button 
            @click="saveComment"
            class="bg-indigo-600 px-4 py-2 rounded-md text-sm font-medium text-white hover:bg-indigo-700"
            :disabled="!commentData.text"
          >
            Enregistrer le commentaire
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal de confirmation de finalisation -->
    <Modal :show="finalizeModalOpen" @close="closeFinalizeModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Finaliser la liste de présence
        </h3>
        
        <p class="mb-4 text-sm text-gray-600">
          Êtes-vous sûr de vouloir finaliser la liste de présence ? Cette action marquera la réunion comme terminée et tous les participants sans statut explicite seront marqués comme absents.
        </p>
        
        <div class="flex justify-end">
          <button 
            @click="closeFinalizeModal"
            class="bg-white px-4 py-2 border border-gray-300 rounded-md mr-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Annuler
          </button>
          <button 
            @click="finalizeAttendance"
            class="bg-blue-600 px-4 py-2 rounded-md text-sm font-medium text-white hover:bg-blue-700"
          >
            Finaliser et terminer
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal pour la prise de photo -->
    <Modal :show="photoModalOpen" @close="closePhotoModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ selectedAttendee?.presence_photo ? 'Photo de présence' : 'Prendre une photo' }}
        </h3>
        
        <div v-if="selectedAttendee?.presence_photo" class="mb-4">
          <img 
            :src="`/storage/${selectedAttendee.presence_photo}`" 
            alt="Photo de présence" 
            class="w-full rounded-lg shadow-lg"
          />
          <div class="mt-2 text-sm text-gray-600">
            <p>Photo prise le {{ formatDate(selectedAttendee.presence_timestamp) }}</p>
            <p>Localisation : {{ formatLocation(selectedAttendee.presence_location) }}</p>
          </div>
          
          <!-- Bouton pour supprimer la photo existante -->
          <div class="mt-4 flex justify-center">
            <button
              @click="deleteExistingPhoto"
              class="px-4 py-2 bg-red-100 text-red-700 rounded-md hover:bg-red-200 flex items-center space-x-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
              <span>Supprimer cette photo</span>
            </button>
          </div>
        </div>
        <div v-else>
          <PhotoCapture @photo-captured="handlePhotoCaptured" @photo-cancelled="closePhotoModal" />
        </div>
        
        <div class="mt-6 flex justify-end">
          <button
            type="button"
            @click="closePhotoModal"
            class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
          >
            Fermer
          </button>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import PhotoCapture from '@/Components/PhotoCapture.vue'
import { 
  MagnifyingGlassIcon, 
  CheckCircleIcon, 
  XCircleIcon, 
  ArrowPathIcon,
  ChatBubbleLeftIcon,
  InformationCircleIcon,
  CameraIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'
import StatusBadge from '@/Components/StatusBadge.vue'
import GeographicAnalysis from '@/Components/GeographicAnalysis.vue'
import { analyzeGeographicConsistency, calculateDistance, formatDistance } from '@/utils/geoUtils'

const props = defineProps({
  meeting: Object,
  attendees: Array
})

const toast = useToast()
const search = ref('')
const selectedVillage = ref('')
const replacementModalOpen = ref(false)
const commentModalOpen = ref(false)
const finalizeModalOpen = ref(false)
const photoModalOpen = ref(false)
const selectedAttendee = ref(null)
const localAttendees = ref([...props.attendees])

// Détecter les rôles de l'utilisateur
const isSubPrefect = computed(() => {
  const user = usePage().props.auth?.user
  console.log('Debug - User:', user)
  console.log('Debug - User roles:', user?.roles)
  if (!user || !user.roles) return false
  return user.roles.some(role => role?.name && ['sous-prefet', 'Sous-prefet'].includes(role.name))
})

const isSecretary = computed(() => {
  const user = usePage().props.auth?.user
  console.log('Debug - User for secretary check:', user)
  console.log('Debug - User roles for secretary check:', user?.roles)
  if (!user || !user.roles) {
    console.log('Debug - No user or no roles found')
    return false
  }
  const hasSecretaryRole = user.roles.some(role => role?.name && ['secretaire', 'Secrétaire', 'admin', 'Admin'].includes(role.name))
  console.log('Debug - Has secretary role:', hasSecretaryRole)
  return hasSecretaryRole
})

// Debug pour les conditions d'affichage des boutons
const debugButtonConditions = computed(() => {
  console.log('Debug - Meeting status:', props.meeting.status)
  console.log('Debug - Meeting is_completed:', props.meeting.is_completed)
  console.log('Debug - Is secretary:', isSecretary.value)
  console.log('Debug - Should show buttons:', isSecretary.value && (props.meeting.status === 'planned' || props.meeting.status === 'scheduled') && !props.meeting.is_completed)
  return {
    isSecretary: isSecretary.value,
    meetingStatus: props.meeting.status,
    isCompleted: props.meeting.is_completed,
    shouldShowButtons: isSecretary.value && (props.meeting.status === 'planned' || props.meeting.status === 'scheduled') && !props.meeting.is_completed
  }
})

// Données pour les formulaires
const replacementData = ref({
  name: '',
  phone: '',
  role: ''
})

const commentData = ref({
  text: ''
})

const arrivalTimeData = ref({
  time: new Date().toISOString().slice(0, 16)
})

// Liste des villages uniques
const villages = computed(() => {
  const uniqueVillages = new Set()
  props.attendees.forEach(attendee => {
    if (attendee.village?.name) {
      uniqueVillages.add(attendee.village.name)
    }
  })
  return Array.from(uniqueVillages).sort()
})

// Attendees filtrés par recherche et village
const filteredAttendees = computed(() => {
  let filtered = localAttendees.value
  
  if (search.value) {
    const searchTerm = search.value.toLowerCase()
    filtered = filtered.filter(attendee => {
      return (
        attendee.name.toLowerCase().includes(searchTerm) ||
        (attendee.village?.name && attendee.village.name.toLowerCase().includes(searchTerm)) ||
        (attendee.role && attendee.role.toLowerCase().includes(searchTerm))
      )
    })
  }
  
  if (selectedVillage.value) {
    filtered = filtered.filter(attendee => 
      attendee.village?.name === selectedVillage.value
    )
  }
  
  return filtered
})

// Statistiques de présence
const attendanceStats = computed(() => {
  const total = filteredAttendees.value.length
  const present = filteredAttendees.value.filter(a => a.attendance_status === 'present').length
  const replaced = filteredAttendees.value.filter(a => a.attendance_status === 'replaced').length
  const absent = filteredAttendees.value.filter(a => a.attendance_status === 'absent').length
  const expected = filteredAttendees.value.filter(a => a.attendance_status === 'expected').length
  
  return {
    total,
    present,
    replaced,
    absent,
    expected,
    presentPercentage: total ? Math.round(((present + replaced) / total) * 100) : 0
  }
})

// Formatter une date
const formatDate = (date) => {
  if (!date) return 'Date non définie'
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Marquer un participant comme présent
const markPresent = async (attendee) => {
  try {
    const response = await axios.post(route('attendees.present', attendee.id), {
      arrival_time: arrivalTimeData.value.time
    })
    updateAttendee(attendee.id, response.data.attendee)
    toast.success('Participant marqué comme présent')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors du marquage comme présent')
  }
}

// Marquer un participant comme absent
const markAbsent = async (attendee) => {
  try {
    const response = await axios.post(route('attendees.absent', attendee.id))
    updateAttendee(attendee.id, response.data.attendee)
    toast.success('Participant marqué comme absent')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors du marquage comme absent')
  }
}

// Afficher le modal de remplaçant
const showReplacementModal = (attendee) => {
  selectedAttendee.value = attendee
  replacementData.value = {
    name: attendee.replacement_name || '',
    phone: attendee.replacement_phone || '',
    role: attendee.replacement_role || ''
  }
  replacementModalOpen.value = true
}

// Fermer le modal de remplaçant
const closeReplacementModal = () => {
  replacementModalOpen.value = false
  selectedAttendee.value = null
  replacementData.value = { name: '', phone: '', role: '' }
}

// Enregistrer un remplaçant
const saveReplacement = async () => {
  if (!selectedAttendee.value || !replacementData.value.name) return
  
  try {
    const response = await axios.post(
      route('attendees.replacement', selectedAttendee.value.id),
      {
        replacement_name: replacementData.value.name,
        replacement_phone: replacementData.value.phone,
        replacement_role: replacementData.value.role
      }
    )
    
    updateAttendee(selectedAttendee.value.id, response.data.attendee)
    toast.success('Remplaçant enregistré avec succès')
    closeReplacementModal()
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement du remplaçant')
  }
}

// Afficher le modal de commentaire
const showCommentModal = (attendee) => {
  selectedAttendee.value = attendee
  commentData.value.text = attendee.comments || ''
  commentModalOpen.value = true
}

// Fermer le modal de commentaire
const closeCommentModal = () => {
  commentModalOpen.value = false
  selectedAttendee.value = null
  commentData.value = { text: '' }
}

// Enregistrer un commentaire
const saveComment = async () => {
  if (!selectedAttendee.value || !commentData.value.text) return
  
  try {
    const response = await axios.post(
      route('attendees.comment', selectedAttendee.value.id),
      {
        comments: commentData.value.text
      }
    )
    
    updateAttendee(selectedAttendee.value.id, response.data.attendee)
    toast.success('Commentaire enregistré avec succès')
    closeCommentModal()
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement du commentaire')
  }
}

// Mettre à jour un participant dans la liste locale
const updateAttendee = (id, updatedAttendee) => {
  const index = localAttendees.value.findIndex(a => a.id === id)
  if (index !== -1) {
    localAttendees.value[index] = {
      ...localAttendees.value[index],
      is_present: updatedAttendee.is_present,
      is_expected: updatedAttendee.is_expected,
      attendance_status: updatedAttendee.attendance_status,
      replacement_name: updatedAttendee.replacement_name,
      replacement_phone: updatedAttendee.replacement_phone,
      replacement_role: updatedAttendee.replacement_role,
      arrival_time: updatedAttendee.arrival_time,
      comments: updatedAttendee.comments,
      payment_status: updatedAttendee.payment_status,
      presence_photo: updatedAttendee.presence_photo,
      presence_location: updatedAttendee.presence_location,
      presence_timestamp: updatedAttendee.presence_timestamp
    }
  }
}

// Confirmation de finalisation
const confirmFinalize = () => {
  finalizeModalOpen.value = true
}

// Fermer le modal de finalisation
const closeFinalizeModal = () => {
  finalizeModalOpen.value = false
}

// Finaliser la liste de présence
const finalizeAttendance = async () => {
  try {
    await axios.post(route('meetings.attendance.finalize', props.meeting.id))
    toast.success('Liste de présence finalisée et réunion marquée comme terminée')
    // Rediriger vers la page de détail de la réunion
    router.visit(route('meetings.show', props.meeting.id))
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la finalisation de la liste')
    closeFinalizeModal()
  }
}

const showPhotoModal = (attendee) => {
  selectedAttendee.value = attendee
  photoModalOpen.value = true
}

const closePhotoModal = () => {
  photoModalOpen.value = false
  selectedAttendee.value = null
}

const handlePhotoCaptured = async (photoData) => {
  if (!selectedAttendee.value) return
  
  try {
    const formData = new FormData()
    formData.append('photo', photoData.photo)
    formData.append('latitude', photoData.location.latitude)
    formData.append('longitude', photoData.location.longitude)
    formData.append('timestamp', photoData.timestamp)

    const response = await axios.post(
      route('meetings.attendees.confirm-presence-with-photo', selectedAttendee.value.id),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    // Utiliser updateAttendee pour préserver toutes les propriétés
    updateAttendee(selectedAttendee.value.id, response.data.attendee)

    closePhotoModal()
    toast.success('Photo de présence enregistrée avec succès')
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement de la photo:', error)
    toast.error('Erreur lors de l\'enregistrement de la photo')
  }
}

const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  
  // Vérifier que latitude et longitude sont des nombres valides
  const lat = parseFloat(location.latitude)
  const lng = parseFloat(location.longitude)
  
  if (isNaN(lat) || isNaN(lng)) {
    return 'Coordonnées invalides'
  }
  
  return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
}

const deleteExistingPhoto = async () => {
  if (!selectedAttendee.value) return
  
  try {
    const response = await axios.post(route('meetings.attendees.delete-photo', selectedAttendee.value.id))
    updateAttendee(selectedAttendee.value.id, response.data.attendee)
    toast.success('Photo supprimée avec succès')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la suppression de la photo')
  }
}

const isGeographicOutlier = (attendee) => {
  // Analyser la cohérence géographique de tous les participants
  const positions = localAttendees.value.filter(a => 
    a.presence_location && 
    a.presence_location.latitude && 
    a.presence_location.longitude
  ).map(a => ({
    ...a,
    latitude: a.presence_location.latitude,
    longitude: a.presence_location.longitude
  }))
  
  const analysis = analyzeGeographicConsistency(positions, 100)
  
  // Vérifier si ce participant est dans les anomalies
  return analysis.outliers.some(outlier => outlier.attendee.id === attendee.id)
}

const getDistanceFromCenter = (attendee) => {
  if (!attendee.presence_location) return 0
  
  // Calculer le centre de tous les participants
  const positions = localAttendees.value.filter(a => 
    a.presence_location && 
    a.presence_location.latitude && 
    a.presence_location.longitude
  ).map(a => ({
    latitude: a.presence_location.latitude,
    longitude: a.presence_location.longitude
  }))
  
  if (positions.length === 0) return 0
  
  // Calculer le centroïde
  const sumLat = positions.reduce((sum, pos) => sum + parseFloat(pos.latitude), 0)
  const sumLng = positions.reduce((sum, pos) => sum + parseFloat(pos.longitude), 0)
  const centerLat = sumLat / positions.length
  const centerLng = sumLng / positions.length
  
  // Calculer la distance
  return calculateDistance(
    centerLat,
    centerLng,
    parseFloat(attendee.presence_location.latitude),
    parseFloat(attendee.presence_location.longitude)
  )
}

const getStatusClass = (status) => {
  const classes = {
    present: 'bg-green-100 text-green-800',
    absent: 'bg-red-100 text-red-800',
    replaced: 'bg-yellow-100 text-yellow-800',
    expected: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || classes.expected
}

const getStatusText = (status, type) => {
  if (type === 'attendance') {
    const texts = {
      present: 'Présent',
      absent: 'Absent',
      replaced: 'Remplacé',
      expected: 'En attente'
    }
    return texts[status] || 'En attente'
  }
  return status
}

// Vérification des participants incomplets
const getIncompleteParticipants = () => {
  return localAttendees.value.filter(attendee => {
    // Vérifier si le participant n'a pas de statut de présence
    const noStatus = !attendee.attendance_status || attendee.attendance_status === 'expected'
    
    // Vérifier si le participant n'a pas de photo (seulement pour les présents)
    const noPhoto = (attendee.attendance_status === 'present' || attendee.attendance_status === 'replaced') && !attendee.presence_photo
    
    return noStatus || noPhoto
  })
}

// Fonction pour afficher l'alerte avant de quitter
const showExitWarning = () => {
  const incompleteParticipants = getIncompleteParticipants()
  
  if (incompleteParticipants.length > 0) {
    const message = `Attention : ${incompleteParticipants.length} participant(s) n'ont pas encore toutes les informations renseignées.\n\n` +
      incompleteParticipants.map(p => {
        const issues = []
        if (!p.attendance_status || p.attendance_status === 'expected') {
          issues.push('statut non défini')
        }
        if ((p.attendance_status === 'present' || p.attendance_status === 'replaced') && !p.presence_photo) {
          issues.push('photo manquante')
        }
        return `• ${p.name} : ${issues.join(', ')}`
      }).join('\n')
    
    return message
  }
  
  return null
}

// Événement avant de quitter la page
const handleBeforeUnload = (event) => {
  const warningMessage = showExitWarning()
  if (warningMessage) {
    event.preventDefault()
    event.returnValue = warningMessage
    return warningMessage
  }
}

// Ajouter l'écouteur d'événement au montage du composant
onMounted(() => {
  window.addEventListener('beforeunload', handleBeforeUnload)
})

// Nettoyer l'écouteur d'événement au démontage du composant
onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', handleBeforeUnload)
})

// Fonction pour gérer le retour à la réunion avec vérification
const handleReturnToMeeting = () => {
  const incompleteParticipants = getIncompleteParticipants()
  
  if (incompleteParticipants.length > 0) {
    const message = `Attention : ${incompleteParticipants.length} participant(s) n'ont pas encore toutes les informations renseignées.\n\n` +
      incompleteParticipants.map(p => {
        const issues = []
        if (!p.attendance_status || p.attendance_status === 'expected') {
          issues.push('statut non défini')
        }
        if ((p.attendance_status === 'present' || p.attendance_status === 'replaced') && !p.presence_photo) {
          issues.push('photo manquante')
        }
        return `• ${p.name} : ${issues.join(', ')}`
      }).join('\n') + '\n\nVoulez-vous quand même quitter cette page ?'
    
    if (confirm(message)) {
      router.visit(route('meetings.show', props.meeting.id))
    }
  } else {
    // Aucun participant incomplet, navigation directe
    router.visit(route('meetings.show', props.meeting.id))
  }
}
</script> 