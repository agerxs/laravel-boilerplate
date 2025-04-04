<template>
  <AppLayout :title="`Liste de présence - ${meeting.title}`">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Liste de présence - {{ meeting.title }}
        </h2>
        <div class="flex space-x-4">
          <Link
            :href="route('meetings.show', meeting.id)"
            class="inline-flex items-center px-4 py-2 bg-indigo-100 text-indigo-800 rounded-md text-sm font-medium hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retour à la réunion
          </Link>
          <a
            :href="route('meetings.attendance.export', meeting.id)"
            target="_blank"
            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium hover:bg-green-200"
          >
            Exporter PDF
          </a>
          <button
            v-if="!meeting.is_completed"
            @click="confirmFinalize"
            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium hover:bg-blue-200"
          >
            Finaliser et terminer la réunion
          </button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                        <div class="text-sm font-medium text-gray-900">{{ attendee.name }}</div>
                        <div class="text-sm text-gray-500">{{ attendee.phone || 'Pas de téléphone' }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ attendee.village?.name || '-' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ attendee.role || '-' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="{
                      'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                      'bg-green-100 text-green-800': attendee.attendance_status === 'present',
                      'bg-red-100 text-red-800': attendee.attendance_status === 'absent',
                      'bg-yellow-100 text-yellow-800': attendee.attendance_status === 'replaced',
                      'bg-gray-100 text-gray-800': attendee.attendance_status === 'expected'
                    }">
                      {{ formatStatus(attendee.attendance_status) }}
                    </span>
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
                        v-if="!meeting.is_completed"
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
                        v-if="!meeting.is_completed"
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
                        v-if="!meeting.is_completed"
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
                        v-if="!meeting.is_completed"
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
                        v-if="!meeting.is_completed"
                        @click="showPhotoModal(attendee)"
                        :class="[
                          'p-1 rounded-full',
                          attendee.presence_photo 
                            ? 'bg-purple-100 text-purple-600' 
                            : 'bg-gray-100 text-gray-600 hover:bg-purple-100 hover:text-purple-600'
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

        <!-- Bouton de retour en bas de page -->
        <div class="flex justify-center mt-8 mb-4">
          <Link
            :href="route('meetings.show', meeting.id)"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Retourner à la page de la réunion
          </Link>
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
        </div>
        <div v-else>
          <PhotoCapture @photo-captured="handlePhotoCaptured" />
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
import { ref, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
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
  CameraIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

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

// Formatter un statut de présence
const formatStatus = (status) => {
  switch (status) {
    case 'present': return 'Présent'
    case 'absent': return 'Absent'
    case 'replaced': return 'Remplacé'
    case 'expected': return 'Prévu'
    default: return status
  }
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

    // Mettre à jour l'attendee dans la liste
    const index = localAttendees.value.findIndex(a => a.id === selectedAttendee.value.id)
    if (index !== -1) {
      localAttendees.value[index] = response.data.attendee
    }

    closePhotoModal()
    toast.success('Photo de présence enregistrée avec succès')
  } catch (error) {
    console.error('Erreur lors de l\'enregistrement de la photo:', error)
    toast.error('Erreur lors de l\'enregistrement de la photo')
  }
}

const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  return `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`
}
</script> 