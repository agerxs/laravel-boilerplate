<template>
  <div class="flex flex-wrap gap-2">
    <!-- Bouton pour soumettre les présences -->
    <button
      v-if="canSubmitAttendance && meeting.attendance_status === 'draft'"
      @click="submitAttendance"
      class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      :disabled="loading"
    >
      <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <PaperAirplaneIcon v-else class="h-4 w-4 mr-2" />
      Soumettre les présences
    </button>

    <!-- Bouton pour annuler la soumission -->
    <button
      v-if="canCancelSubmission && meeting.attendance_status === 'submitted'"
      @click="cancelSubmission"
      class="inline-flex items-center px-4 py-2 bg-orange-600 text-white rounded-md text-sm font-medium hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
      :disabled="loading"
    >
      <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <ArrowUturnLeftIcon v-else class="h-4 w-4 mr-2" />
      Annuler la soumission
    </button>

    <!-- Indicateur de soumission -->
    <div v-if="meeting.attendance_status === 'submitted'" class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-800 rounded-md text-sm font-medium">
      <PaperAirplaneIcon class="h-4 w-4 mr-2" />
      Présences soumises
      <span class="ml-2 text-xs text-blue-600">
        le {{ formatDate(meeting.attendance_submitted_at) }}
      </span>

    <!-- Indicateur de brouillon 
    <div v-if="meeting.attendance_status === 'draft'" class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-800 rounded-md text-sm font-medium">
      <PencilIcon class="h-4 w-4 mr-2" />
      Brouillon
    </div>-->
  </div>
</div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { PaperAirplaneIcon, ArrowUturnLeftIcon, PencilIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import { hasRole } from '@/Utils/authUtils'
import axios from 'axios'
import { router } from '@inertiajs/vue3'

const props = defineProps<{
  meeting: {
    id: number
    status: string
    attendance_status?: string
    attendance_submitted_at?: string
    attendance_submitted_by?: number
  }
  user: {
    roles: any[]
  }
}>()

const emit = defineEmits<{
  attendanceSubmitted: []
  attendanceSubmissionCancelled: []
}>()

const toast = useToast()
const loading = ref(false)

// Vérifier si l'utilisateur peut soumettre les présences
const canSubmitAttendance = computed(() => {
  return hasRole(props.user.roles, 'secretaire') || hasRole(props.user.roles, 'admin')
})

// Vérifier si l'utilisateur peut annuler la soumission
const canCancelSubmission = computed(() => {
  return hasRole(props.user.roles, 'secretaire') || hasRole(props.user.roles, 'admin')
})

// Fonction pour soumettre les présences
const submitAttendance = async () => {
  if (!confirm('Êtes-vous sûr de vouloir soumettre la liste de présence ? Cette action ne finalise pas la réunion.')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.submit-attendance', props.meeting.id))

    toast.success(response.data.message || 'Liste de présence soumise avec succès')
    // Rediriger vers la liste des réunions pour voir la mise à jour
    router.visit(route('meetings.index'))
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la soumission des présences')
  } finally {
    loading.value = false
  }
}

// Fonction pour annuler la soumission
const cancelSubmission = async () => {
  if (!confirm('Êtes-vous sûr de vouloir annuler la soumission de la liste de présence ?')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.cancel-attendance-submission', props.meeting.id))
    
    toast.success(response.data.message || 'Soumission annulée avec succès')
    emit('attendanceSubmissionCancelled')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'annulation de la soumission')
  } finally {
    loading.value = false
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