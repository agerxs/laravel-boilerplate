<template>
  <div class="flex flex-wrap gap-2">
    <!-- Bouton pour valider les présences -->
    <button
      v-if="canValidateAttendance && !meeting.attendance_validated_at"
      @click="validateAttendance"
      class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
      :disabled="loading"
    >
      <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <CheckIcon v-else class="h-4 w-4 mr-2" />
      Valider les présences
    </button>

    <!-- Bouton pour rejeter les présences (remet en état rejected) -->
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
              <svg v-if="rejectionForm.loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Rejeter
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { CheckIcon, XMarkIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { hasRole } from '@/Utils/authUtils'
import axios from 'axios'

const props = defineProps<{
  meeting: {
    id: number
    status: string
    attendance_status?: string
    attendance_validated_at?: string
    attendance_validated_by?: number
  }
  user: {
    roles: any[]
  }
}>()

const emit = defineEmits<{
  attendanceValidated: []
  attendanceRejected: []
}>()

const toast = useToast()
const loading = ref(false)
const showRejectionModal = ref(false)
const rejectionForm = ref({
  comments: '',
  loading: false
})

// Vérifier si l'utilisateur peut valider les présences
const canValidateAttendance = computed(() => {
  return hasRole(props.user.roles, 'president') || hasRole(props.user.roles, 'admin')
})

// Vérifier si l'utilisateur peut rejeter les présences
const canRejectAttendance = computed(() => {
  return hasRole(props.user.roles, 'president') || hasRole(props.user.roles, 'admin')
})

// Vérifier si les présences peuvent être validées
const canValidate = computed(() => {
  // Les présences peuvent être validées dès qu'elles sont soumises, indépendamment du statut de la réunion
  return props.meeting.attendance_status === 'submitted'
})

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

// Fonction pour formater la date
const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script> 