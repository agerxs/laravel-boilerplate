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
    <label :class="{ required: isRequired }" class="block text-sm font-medium text-gray-700">
        <span v-if="value" class="inline-block">{{ value }}</span>
        <span v-else class="inline-block"><slot /></span>
    </label>
</template>

<style scoped>
.required::after {
    content: " *";
    color: #dc2626;
    display: inline;
    vertical-align: middle;
    margin-left: 2px;
}
</style>
