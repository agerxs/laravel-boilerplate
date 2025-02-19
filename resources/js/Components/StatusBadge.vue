<template>
  <span
    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
    :class="colorClass"
  >
    {{ statusText }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  status: string;
}

const props = defineProps<Props>();

const colorClass = computed(() => {
  switch (props.status) {
    case 'active':
      return 'bg-green-100 text-green-800';
    case 'inactive':
      return 'bg-red-100 text-red-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
});

const statusText = computed(() => {
  const statusMap: { [key: string]: string } = {
    'active': 'Actif',
    'inactive': 'Inactif',
    'pending': 'En attente'
  };
  return statusMap[props.status] || props.status;
});
</script> 