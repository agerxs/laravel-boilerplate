<template>
  <div :class="[
    'px-2 py-1 text-xs font-medium rounded-full',
    computedClass
  ]">
    {{ computedLabel }}
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  scheduledDate: {
    type: [String, Date],
    default: null
  }
});

const statusClasses = {
  scheduled: 'bg-yellow-100 text-yellow-800',
  planned: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  prevalidated: 'bg-blue-100 text-blue-800',
  validated: 'bg-violet-100 text-violet-800',
  late: 'bg-orange-100 text-orange-800',
  default: 'bg-gray-100 text-gray-800'
};

const statusLabels = {
  scheduled: 'Planifiée',
  planned: 'Planifiée',
  completed: 'Terminée',
  cancelled: 'Annulée',
  prevalidated: 'Prévalidée',
  validated: 'Validée',
  late: 'En retard'
};

// Vérifie si la réunion est en retard (planifiée et date dépassée)
const isLate = computed(() => {
  if (props.status !== 'planned' && props.status !== 'scheduled') return false;
  if (!props.scheduledDate) return false;
  
  const meetingDate = new Date(props.scheduledDate);
  const now = new Date();
  
  return meetingDate < now;
});

// Classe CSS calculée en fonction du statut et si la réunion est en retard
const computedClass = computed(() => {
  if (isLate.value) {
    return statusClasses.late;
  }
  return statusClasses[props.status] || statusClasses.default;
});

// Label calculé en fonction du statut et si la réunion est en retard
const computedLabel = computed(() => {
  if (isLate.value) {
    return statusLabels.late;
  }
  return statusLabels[props.status] || props.status;
});
</script>
