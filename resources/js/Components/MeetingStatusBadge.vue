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
import { getStatusText, getStatusClass } from '@/Utils/translations';

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
    return getStatusClass('late');
  }
  return getStatusClass(props.status);
});

// Label calculé en fonction du statut et si la réunion est en retard
const computedLabel = computed(() => {
  if (isLate.value) {
    return getStatusText('late');
  }
  return getStatusText(props.status);
});
</script>
