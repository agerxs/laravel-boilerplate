<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
  modelValue: string;
}>();

defineEmits(['update:modelValue']);

const input = ref<HTMLInputElement | null>(null);

defineExpose({ focus: () => input.value?.focus() });

onMounted(() => {
  if (input.value?.hasAttribute('autofocus')) {
    input.value?.focus();
  }
});
</script>

<template>
  <input
    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-black"
    :value="modelValue"
    @input="$emit('update:modelValue', $event.target.value)"
    ref="input"
  >
</template>
