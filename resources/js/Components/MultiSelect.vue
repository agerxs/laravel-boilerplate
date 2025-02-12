<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue';

interface Option {
    id: number;
    name: string;
    email?: string;
}

const props = defineProps<{
    modelValue: number[];
    options: Option[];
    placeholder?: string;
    label?: string;
}>();

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const selectedOptions = ref<Option[]>([]);
const dropdownRef = ref<HTMLElement | null>(null);

// Initialiser les options sélectionnées
watch(() => props.modelValue, (newValue) => {
    selectedOptions.value = props.options.filter(option => 
        newValue.includes(option.id)
    );
}, { immediate: true });

const filteredOptions = computed(() => {
    return props.options.filter(option => {
        const isSelected = selectedOptions.value.some(selected => selected.id === option.id);
        if (isSelected) return false;

        const searchLower = search.value.toLowerCase();
        return option.name.toLowerCase().includes(searchLower) ||
               (option.email && option.email.toLowerCase().includes(searchLower));
    });
});

const toggleOption = (option: Option) => {
    const index = selectedOptions.value.findIndex(selected => selected.id === option.id);
    if (index === -1) {
        selectedOptions.value.push(option);
    } else {
        selectedOptions.value.splice(index, 1);
    }
    emit('update:modelValue', selectedOptions.value.map(opt => opt.id));
};

const removeOption = (optionToRemove: Option) => {
    selectedOptions.value = selectedOptions.value.filter(option => option.id !== optionToRemove.id);
    emit('update:modelValue', selectedOptions.value.map(opt => opt.id));
};

// Gestion du clic en dehors
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        search.value = '';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="relative" ref="dropdownRef">
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        <div
            @click="isOpen = true"
            class="min-h-[38px] w-full bg-white border border-gray-300 rounded-md shadow-sm px-3 py-1.5 cursor-text focus-within:border-primary-500 focus-within:ring-1 focus-within:ring-primary-500"
        >
            <div class="flex flex-wrap gap-1">
                <div
                    v-for="option in selectedOptions"
                    :key="option.id"
                    class="inline-flex items-center bg-gray-100 rounded px-2 py-0.5 text-sm"
                >
                    <span>{{ option.name }}</span>
                    <button
                        @click.stop="removeOption(option)"
                        class="ml-1 text-gray-500 hover:text-gray-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <input
                    type="text"
                    v-model="search"
                    :placeholder="selectedOptions.length === 0 ? (placeholder || 'Sélectionner...') : ''"
                    class="outline-none border-none p-0 m-0 flex-1 min-w-[80px] bg-transparent text-sm"
                    @focus="isOpen = true"
                />
            </div>
        </div>

        <div
            v-show="isOpen"
            class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base overflow-auto focus:outline-none sm:text-sm"
        >
            <div
                v-for="option in filteredOptions"
                :key="option.id"
                @click="toggleOption(option)"
                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
            >
                <div class="flex items-center">
                    <span class="block truncate">
                        {{ option.name }}
                        <span v-if="option.email" class="text-gray-500 text-sm ml-1">
                            ({{ option.email }})
                        </span>
                    </span>
                </div>
            </div>
            <div
                v-if="filteredOptions.length === 0"
                class="text-gray-500 text-sm py-2 px-3"
            >
                Aucun résultat trouvé
            </div>
        </div>
    </div>
</template>

<style scoped>
.multiselect-enter-active,
.multiselect-leave-active {
    transition: opacity 0.15s ease;
}

.multiselect-enter-from,
.multiselect-leave-to {
    opacity: 0;
}
</style> 