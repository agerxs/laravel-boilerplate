<script setup lang="ts">
defineProps({
    value: {
        type: String,
        default: ''
    },
    isRequired: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <label :class="{ required: isRequired }" class="block text-sm font-medium ">
        <span v-if="value" class="block font-medium text-sm text-gray-700">
            {{ value }}
        </span>
        <span v-else><slot /></span>
    </label>
</template>

<style scoped>
.required::after {
    content: " *";
    color: #dc2626;
}
</style>
