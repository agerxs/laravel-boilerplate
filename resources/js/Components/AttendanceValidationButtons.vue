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

    <!-- Bouton pour invalider les présences -->
    <button
      v-if="canInvalidateAttendance && meeting.attendance_validated_at"
      @click="invalidateAttendance"
      class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
      :disabled="loading"
    >
      <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <XMarkIcon v-else class="h-4 w-4 mr-2" />
      Invalider les présences
    </button>

    <!-- Indicateur de validation des présences -->
    <div v-if="meeting.attendance_validated_at" class="inline-flex items-center px-3 py-2 bg-green-100 text-green-800 rounded-md text-sm font-medium">
      <CheckIcon class="h-4 w-4 mr-2" />
      Présences validées
      <span class="ml-2 text-xs text-green-600">
        le {{ formatDate(meeting.attendance_validated_at) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline'
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
  attendanceInvalidated: []
}>()

const toast = useToast()
const loading = ref(false)

// Vérifier si l'utilisateur peut valider les présences
const canValidateAttendance = computed(() => {
  return hasRole(props.user.roles, 'sous-prefet') || hasRole(props.user.roles, 'admin')
})

// Vérifier si l'utilisateur peut invalider les présences
const canInvalidateAttendance = computed(() => {
  return hasRole(props.user.roles, 'sous-prefet') || hasRole(props.user.roles, 'admin')
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

// Fonction pour invalider les présences
const invalidateAttendance = async () => {
  if (!confirm('Êtes-vous sûr de vouloir invalider les présences de cette réunion ?')) {
    return
  }

  loading.value = true

  try {
    const response = await axios.post(route('meetings.invalidate-attendance', props.meeting.id))
    
    toast.success(response.data.message || 'Présences invalidées avec succès')
    emit('attendanceInvalidated')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'invalidation des présences')
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