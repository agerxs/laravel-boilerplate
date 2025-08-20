<template>
  <span v-if="gender === 'M'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
    <span class="w-2 h-2 bg-blue-400 rounded-full mr-1"></span>
    Masculin
  </span>
  <span v-else-if="gender === 'F'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
    <span class="w-2 h-2 bg-pink-400 rounded-full mr-1"></span>
    Féminin
  </span>
  <span v-else class="text-gray-400 text-xs">Non spécifié</span>
</template>

<script setup lang="ts">
interface Props {
  gender?: string;
}

defineProps<Props>();
</script>
