<template>
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Résultats des villages</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Section Enrôlement -->
      <div class="space-y-4">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
          <h4 class="text-md font-semibold text-gray-900">Enrôlement</h4>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="people-to-enroll" value="Personnes à enrôler" />
            <TextInput
              id="people-to-enroll"
              v-model="form.people_to_enroll_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
          
          <div>
            <InputLabel for="people-enrolled" value="Personnes enrôlées" />
            <TextInput
              id="people-enrolled"
              v-model="form.people_enrolled_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
        </div>
        
        <!-- Barre de progression pour l'enrôlement -->
        <div v-if="enrollmentRate > 0" class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Taux d'enrôlement</span>
            <span class="font-medium">{{ enrollmentRate.toFixed(1) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-green-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(enrollmentRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Section Cartes CMU -->
      <div class="space-y-4">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
          </svg>
          <h4 class="text-md font-semibold text-gray-900">Cartes CMU</h4>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="cmu-cards-available" value="Cartes disponibles" />
            <TextInput
              id="cmu-cards-available"
              v-model="form.cmu_cards_available_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
          
          <div>
            <InputLabel for="cmu-cards-distributed" value="Cartes distribuées" />
            <TextInput
              id="cmu-cards-distributed"
              v-model="form.cmu_cards_distributed_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
        </div>
        
        <!-- Barre de progression pour les cartes CMU -->
        <div v-if="cmuDistributionRate > 0" class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Taux de distribution</span>
            <span class="font-medium">{{ cmuDistributionRate.toFixed(1) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-purple-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(cmuDistributionRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Section Réclamations -->
      <div class="space-y-4 md:col-span-2">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          <h4 class="text-md font-semibold text-gray-900">Réclamations</h4>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="complaints-received" value="Réclamations reçues" />
            <TextInput
              id="complaints-received"
              v-model="form.complaints_received_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
          
          <div>
            <InputLabel for="complaints-processed" value="Réclamations traitées" />
            <TextInput
              id="complaints-processed"
              v-model="form.complaints_processed_count"
              type="number"
              min="0"
              class="mt-1 block w-full"
              placeholder="0"
            />
          </div>
        </div>
        
        <!-- Barre de progression pour les réclamations -->
        <div v-if="complaintProcessingRate > 0" class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Taux de traitement</span>
            <span class="font-medium">{{ complaintProcessingRate.toFixed(1) }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="bg-green-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(complaintProcessingRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages d'erreur de validation -->
    <div v-if="errors.length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <div class="flex">
        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Erreurs de validation</h3>
          <div class="mt-2 text-sm text-red-700">
            <ul class="list-disc pl-5 space-y-1">
              <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      people_to_enroll_count: null,
      people_enrolled_count: null,
      cmu_cards_available_count: null,
      cmu_cards_distributed_count: null,
      complaints_received_count: null,
      complaints_processed_count: null,
    })
  }
})

const emit = defineEmits(['update:modelValue', 'validation-error'])

const form = ref({
  people_to_enroll_count: props.modelValue.people_to_enroll_count || '',
  people_enrolled_count: props.modelValue.people_enrolled_count || '',
  cmu_cards_available_count: props.modelValue.cmu_cards_available_count || '',
  cmu_cards_distributed_count: props.modelValue.cmu_cards_distributed_count || '',
  complaints_received_count: props.modelValue.complaints_received_count || '',
  complaints_processed_count: props.modelValue.complaints_processed_count || '',
})

const errors = ref([])

// Calculs des taux
const enrollmentRate = computed(() => {
  const toEnroll = parseInt(form.value.people_to_enroll_count) || 0
  const enrolled = parseInt(form.value.people_enrolled_count) || 0
  return toEnroll > 0 ? (enrolled / toEnroll) * 100 : 0
})

const cmuDistributionRate = computed(() => {
  const available = parseInt(form.value.cmu_cards_available_count) || 0
  const distributed = parseInt(form.value.cmu_cards_distributed_count) || 0
  return available > 0 ? (distributed / available) * 100 : 0
})

const complaintProcessingRate = computed(() => {
  const received = parseInt(form.value.complaints_received_count) || 0
  const processed = parseInt(form.value.complaints_processed_count) || 0
  return received > 0 ? (processed / received) * 100 : 0
})

// Validation
const validate = () => {
  errors.value = []
  
  const toEnroll = parseInt(form.value.people_to_enroll_count) || 0
  const enrolled = parseInt(form.value.people_enrolled_count) || 0
  const available = parseInt(form.value.cmu_cards_available_count) || 0
  const distributed = parseInt(form.value.cmu_cards_distributed_count) || 0
  const received = parseInt(form.value.complaints_received_count) || 0
  const processed = parseInt(form.value.complaints_processed_count) || 0

  if (enrolled > toEnroll && toEnroll > 0) {
    errors.value.push('Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler')
  }

  if (distributed > available && available > 0) {
    errors.value.push('Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles')
  }

  if (processed > received && received > 0) {
    errors.value.push('Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues')
  }

  if (errors.value.length > 0) {
    emit('validation-error', errors.value)
  }

  return errors.value.length === 0
}

// Émettre les changements
watch(form, (newValue) => {
  const data = {
    people_to_enroll_count: newValue.people_to_enroll_count ? parseInt(newValue.people_to_enroll_count) : null,
    people_enrolled_count: newValue.people_enrolled_count ? parseInt(newValue.people_enrolled_count) : null,
    cmu_cards_available_count: newValue.cmu_cards_available_count ? parseInt(newValue.cmu_cards_available_count) : null,
    cmu_cards_distributed_count: newValue.cmu_cards_distributed_count ? parseInt(newValue.cmu_cards_distributed_count) : null,
    complaints_received_count: newValue.complaints_received_count ? parseInt(newValue.complaints_received_count) : null,
    complaints_processed_count: newValue.complaints_processed_count ? parseInt(newValue.complaints_processed_count) : null,
  }
  
  emit('update:modelValue', data)
}, { deep: true })

// Exposer la méthode de validation
defineExpose({
  validate
})
</script> 