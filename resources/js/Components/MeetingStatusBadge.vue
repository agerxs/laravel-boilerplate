<template>
  <div :class="[
    'px-2 py-1 text-xs font-medium rounded-full',
    statusClasses[status] || statusClasses.default
  ]">
    {{ statusLabels[status] || status }}
  </div>
</template>

<script setup>
const props = defineProps({
  status: {
    type: String,
    required: true
  }
});

const statusClasses = {
  scheduled: 'bg-yellow-100 text-yellow-800',
  completed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  default: 'bg-gray-100 text-gray-800'
};

const statusLabels = {
  scheduled: 'Planifiée',
  planned: 'Planifiée',
  completed: 'Terminée',
  cancelled: 'Annulée'
};
</script>
