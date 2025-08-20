<template>
  <select 
    :value="modelValue" 
    @change="$emit('update:modelValue', $event.target.value)"
    class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
  >
    <option value="">Tous les sexes</option>
    <option value="M">Masculin</option>
    <option value="F">Féminin</option>
  </select>
</template>

<script setup lang="ts">
interface Props {
  modelValue: string;
}

defineProps<Props>();
defineEmits<{
  'update:modelValue': [value: string];
}>();
</script>
