<template>
  <div class="flex items-center space-x-1">
    <!-- Debug temporaire -->
    <div class="text-xs text-purple-500 mr-1">
      Props: {{ attendance_status }}
    </div>
    
    <!-- Badge pour le statut de soumission -->
    <span 
      :class="getSubmissionStatusClass()"
      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
      :title="getSubmissionStatusTooltip()"
    >
      <component :is="getSubmissionStatusIcon()" class="h-3 w-3 mr-1" />
      {{ getSubmissionStatusText() }}
    </span>

    <!-- Badge pour la validation (si applicable) -->
    <span 
      v-if="attendance_status === 'submitted' || attendance_status === 'validated'"
      :class="getValidationStatusClass()"
      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
      :title="getValidationStatusTooltip()"
    >
      <component :is="getValidationStatusIcon()" class="h-3 w-3 mr-1" />
      {{ getValidationStatusText() }}
    </span>
  </div>
</template>

<script setup lang="ts">
import { 
  PencilIcon, 
  PaperAirplaneIcon, 
  CheckIcon, 
  ClockIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'

interface Props {
  attendance_status: string
  attendance_validated_at?: string
}

const props = defineProps<Props>()

// Debug temporaire
console.log('AttendanceStatusBadge props:', props)
console.log('attendance_status:', props.attendance_status)
console.log('attendance_validated_at:', props.attendance_validated_at)

// Fonctions pour le statut de soumission
const getSubmissionStatusClass = () => {
  switch (props.attendance_status) {
    case 'draft':
      return 'bg-gray-100 text-gray-700 border border-gray-200'
    case 'submitted':
      return 'bg-blue-100 text-blue-700 border border-blue-200'
    case 'validated':
      return 'bg-green-100 text-green-700 border border-green-200'
    default:
      return 'bg-gray-100 text-gray-700 border border-gray-200'
  }
}

const getSubmissionStatusIcon = () => {
  switch (props.attendance_status) {
    case 'draft':
      return PencilIcon
    case 'submitted':
      return PaperAirplaneIcon
    case 'validated':
      return CheckIcon
    default:
      return PencilIcon
  }
}

const getSubmissionStatusText = () => {
  switch (props.attendance_status) {
    case 'draft':
      return 'Brouillon'
    case 'submitted':
      return 'Soumis'
    case 'validated':
      return 'Validé'
    default:
      return 'Brouillon'
  }
}

const getSubmissionStatusTooltip = () => {
  switch (props.attendance_status) {
    case 'draft':
      return 'Liste de présence en cours de préparation'
    case 'submitted':
      return 'Liste de présence soumise, en attente de validation'
    case 'validated':
      return 'Liste de présence validée par le sous-préfet'
    default:
      return 'Liste de présence en cours de préparation'
  }
}

// Fonctions pour le statut de validation
const getValidationStatusClass = () => {
  if (props.attendance_status === 'validated') {
    return 'bg-green-100 text-green-700 border border-green-200'
  } else if (props.attendance_status === 'submitted') {
    return 'bg-yellow-100 text-yellow-700 border border-yellow-200'
  }
  return 'bg-gray-100 text-gray-700 border border-gray-200'
}

const getValidationStatusIcon = () => {
  if (props.attendance_status === 'validated') {
    return CheckIcon
  } else if (props.attendance_status === 'submitted') {
    return ClockIcon
  }
  return ExclamationTriangleIcon
}

const getValidationStatusText = () => {
  if (props.attendance_status === 'validated') {
    return '✓'
  } else if (props.attendance_status === 'submitted') {
    return 'En attente'
  }
  return '?'
}

const getValidationStatusTooltip = () => {
  if (props.attendance_status === 'validated') {
    return `Validé le ${formatDate(props.attendance_validated_at)}`
  } else if (props.attendance_status === 'submitted') {
    return 'En attente de validation par le sous-préfet'
  }
  return 'Statut de validation inconnu'
}

// Fonction pour formater la date
const formatDate = (dateString?: string) => {
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