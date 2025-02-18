<template>
  <div class="flex items-end space-x-4">
    <div v-for="(level, index) in levels" :key="index" class="flex-1">
      <InputLabel :value="level.label" />
      <select
        v-model="selectedValues[index]"
        class="mt-1 block w-full rounded-md border-gray-300"
        @change="handleChange(index)"
      >
        <option value="">Sélectionner {{ level.label }}</option>
        <option
          v-for="option in getOptionsForLevel(index)"
          :key="option.name"
          :value="option.name"
        >
          {{ option.name }}
        </option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'

const props = defineProps({
  localities: {
    type: Array,
    required: true
  },
  modelValue: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue'])

const levels = [
  { label: 'Région', level: '0' },
  { label: 'Département', level: '3' },
  { label: 'Sous-préfecture', level: '4' }
]

const selectedValues = ref(Array(levels.length).fill(''))

// Log au début pour voir les données reçues
console.log('Props localities:', JSON.stringify(props.localities.slice(0, 5), null, 2))

const getOptionsForLevel = (levelIndex) => {
  const level = levels[levelIndex]
  
  if (levelIndex === 0) {
    // Pour les régions
    const regions = props.localities.filter(loc => {
      const isMatch = loc.hierarchy_level == level.level
      console.log('Checking region:', {
        name: loc.name,
        level: loc.hierarchy_level,
        isMatch
      })
      return isMatch
    })
    return regions
  } else {
    const parentName = selectedValues.value[levelIndex - 1]
    if (!parentName) return []

    // Pour les autres niveaux
    const children = props.localities.filter(loc => {
      const isMatch = loc.hierarchy_level == level.level && 
                     loc.parent_loc_code == props.localities.find(
                       p => p.name == parentName
                     )?.code
      console.log('Checking child:', {
        name: loc.name,
        level: loc.hierarchy_level,
        parent: loc.parent_loc_code,
        selectedParent: parentName,
        isMatch
      })
      return isMatch
    })
    return children
  }
}

const handleChange = (levelIndex) => {
  // Réinitialiser les niveaux suivants
  for (let i = levelIndex + 1; i < levels.length; i++) {
    selectedValues.value[i] = ''
  }

  // Construire la chaîne de localisation
  const selectedLocations = selectedValues.value
    .slice(0, levelIndex + 1)
    .map((value, idx) => {
      if (!value) return null
      const loc = props.localities.find(l => l.name == value)
      return loc ? loc.name : null
    })
    .filter(Boolean)

  // Émettre la chaîne formatée
  emit('update:modelValue', selectedLocations.join(', '))
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    selectedValues.value = Array(levels.length).fill('')
  }
})
</script> 