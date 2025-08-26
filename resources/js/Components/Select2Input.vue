<template>
  <div class="relative">
    <!-- Label -->
    <InputLabel v-if="label" :value="label" :isRequired="isRequired" class="mb-1" />
    
    <!-- Input principal -->
    <div class="relative">
      <input
        ref="inputRef"
        v-model="searchTerm"
        type="text"
        :placeholder="placeholder"
        :class="[
          'w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500',
          'pr-10', // Espace pour l'icône
          isOpen ? 'rounded-b-none border-b-0' : '',
          error ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''
        ]"
        @focus="openDropdown"
        @blur="handleBlur"
        @input="handleInput"
        @keydown="handleKeydown"
        :disabled="disabled"
      />
      
      <!-- Icône de dropdown -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg 
          class="h-5 w-5 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180': isOpen }"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </div>

    <!-- Dropdown des suggestions -->
    <div 
      v-if="isOpen && (filteredOptions.length > 0 || showCustomOption)"
      class="absolute z-50 w-full bg-white border border-gray-300 rounded-b-md shadow-lg max-h-60 overflow-y-auto"
    >
      <!-- Options filtrées -->
      <div 
        v-for="(option, index) in filteredOptions" 
        :key="option.value || index"
        :class="[
          'px-3 py-2 cursor-pointer hover:bg-indigo-50 transition-colors duration-150',
          selectedIndex === index ? 'bg-indigo-100 text-indigo-900' : 'text-gray-900'
        ]"
        @click="selectOption(option)"
        @mouseenter="selectedIndex = index"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1">
            <div class="font-medium text-gray-900">{{ option.label }}</div>
            <div v-if="option.subtitle" class="text-sm text-gray-500">{{ option.subtitle }}</div>
            <div v-if="option.location" class="text-xs text-gray-400">{{ option.location }}</div>
          </div>
          <span v-if="option.count" class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded ml-2">
            {{ option.count }}
          </span>
        </div>
      </div>
      
      <!-- Option personnalisée -->
      <div 
        v-if="showCustomOption && searchTerm.trim()"
        class="px-3 py-2 cursor-pointer hover:bg-green-50 transition-colors duration-150 border-t border-gray-200"
        @click="selectCustomOption"
        @mouseenter="selectedIndex = filteredOptions.length"
      >
        <div class="flex items-center text-green-700">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
          <span class="font-medium">Ajouter "{{ searchTerm }}"</span>
        </div>
      </div>
      
      <!-- Message si aucune option -->
      <div v-if="filteredOptions.length === 0 && !showCustomOption && searchTerm.trim()" class="px-3 py-2 text-gray-500 text-sm">
        Aucune suggestion trouvée
      </div>
    </div>

    <!-- Valeur sélectionnée affichée -->
    <div v-if="modelValue && !isOpen" class="mt-1">
      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
        {{ getDisplayValue(modelValue) }}
        <button 
          v-if="!disabled"
          @click="clearValue" 
          class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 transition-colors duration-150"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </span>
    </div>

    <!-- Message d'erreur -->
    <div v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </div>

    <!-- Aide contextuelle -->
    <div v-if="helpText" class="mt-1 text-sm text-gray-500">
      {{ helpText }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'

interface SelectOption {
  value: string
  label: string
  subtitle?: string
  location?: string
  count?: number
}

interface Props {
  modelValue?: string
  options?: SelectOption[]
  label?: string
  placeholder?: string
  helpText?: string
  error?: string
  disabled?: boolean
  isRequired?: boolean
  allowCustom?: boolean
  showCounts?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [],
  placeholder: 'Tapez pour rechercher...',
  allowCustom: true,
  showCounts: true,
  disabled: false,
  isRequired: false
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'custom-value', value: string): void
}>()

// Références
const inputRef = ref<HTMLInputElement>()
const isOpen = ref(false)
const searchTerm = ref('')
const selectedIndex = ref(-1)

// Computed properties
const filteredOptions = computed(() => {
  if (!searchTerm.value.trim()) {
    return props.options
  }
  
  const term = searchTerm.value.toLowerCase()
  return props.options.filter(option => 
    option.label.toLowerCase().includes(term) ||
    option.value.toLowerCase().includes(term) ||
    (option.subtitle && option.subtitle.toLowerCase().includes(term)) ||
    (option.location && option.location.toLowerCase().includes(term))
  )
})

const showCustomOption = computed(() => {
  if (!props.allowCustom) return false
  
  const term = searchTerm.value.trim()
  if (!term) return false
  
  // Ne pas afficher si l'option existe déjà
  const exists = props.options.some(option => 
    option.value.toLowerCase() === term.toLowerCase() ||
    option.label.toLowerCase() === term.toLowerCase()
  )
  
  return !exists
})

// Méthodes
const openDropdown = () => {
  if (props.disabled) return
  
  isOpen.value = true
  selectedIndex.value = -1
  
  // Si une valeur est sélectionnée, l'afficher dans la recherche
  if (props.modelValue) {
    const option = props.options.find(opt => opt.value === props.modelValue)
    if (option) {
      searchTerm.value = option.label
    }
  }
}

const closeDropdown = () => {
  isOpen.value = false
  selectedIndex.value = -1
}

const handleBlur = () => {
  // Délai pour permettre le clic sur les options
  setTimeout(() => {
    closeDropdown()
    
    // Si une valeur personnalisée a été saisie, l'émettre
    if (searchTerm.value.trim() && !props.modelValue) {
      emit('custom-value', searchTerm.value.trim())
    }
  }, 150)
}

const handleInput = () => {
  if (!isOpen.value) {
    openDropdown()
  }
  
  // Réinitialiser la sélection
  selectedIndex.value = -1
  
  // Émettre la valeur de recherche pour les composants parents
  if (searchTerm.value.trim()) {
    emit('update:modelValue', searchTerm.value.trim())
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (!isOpen.value) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, filteredOptions.value.length - 1)
      break
      
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
      break
      
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0 && selectedIndex.value < filteredOptions.value.length) {
        selectOption(filteredOptions.value[selectedIndex.value])
      } else if (showCustomOption.value) {
        selectCustomOption()
      }
      break
      
    case 'Escape':
      event.preventDefault()
      closeDropdown()
      inputRef.value?.blur()
      break
  }
}

const selectOption = (option: SelectOption) => {
  emit('update:modelValue', option.value)
  searchTerm.value = option.label
  closeDropdown()
}

const selectCustomOption = () => {
  const customValue = searchTerm.value.trim()
  emit('update:modelValue', customValue)
  emit('custom-value', customValue)
  closeDropdown()
}

const clearValue = () => {
  emit('update:modelValue', '')
  searchTerm.value = ''
}

const getDisplayValue = (value: string) => {
  const option = props.options.find(opt => opt.value === value)
  return option ? option.label : value
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    const option = props.options.find(opt => opt.value === newValue)
    if (option) {
      searchTerm.value = option.label
    } else {
      searchTerm.value = newValue
    }
  } else {
    searchTerm.value = ''
  }
}, { immediate: true })

// Gestion des clics extérieurs
const handleClickOutside = (event: Event) => {
  if (inputRef.value && !inputRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Styles pour la scrollbar du dropdown */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
