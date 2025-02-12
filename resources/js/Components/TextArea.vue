<script setup lang="ts">
import { onMounted, ref } from 'vue';

const input = ref<HTMLTextAreaElement | null>(null);

const props = defineProps<{
    modelValue: string;
    name?: string;
    id?: string;
    placeholder?: string;
    required?: boolean;
    rows?: number;
}>();

defineEmits(['update:modelValue']);

onMounted(() => {
    if (input.value) {
        input.value.style.height = 'auto';
        input.value.style.height = input.value.scrollHeight + 'px';
    }
});

const resize = (e: Event) => {
    const textarea = e.target as HTMLTextAreaElement;
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
};
</script>

<template>
    <textarea
        ref="input"
        :value="modelValue"
        @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        @keyup="resize"
        class="border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm w-full"
        :name="name"
        :id="id"
        :placeholder="placeholder"
        :required="required"
        :rows="rows || 3"
    ></textarea>
</template>

<style scoped>
textarea {
    min-height: 80px;
    resize: none;
    overflow-y: hidden;
}
</style> 