<template>
  <div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-lg font-semibold">Résultats des villages</h3>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Carte Enrôlement -->
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center space-x-2">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h4 class="text-sm font-semibold text-blue-900">Enrôlement</h4>
          </div>
          <span class="text-xs text-blue-600 font-medium">{{ enrollmentRate.toFixed(1) }}%</span>
        </div>
        
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-blue-700">À enrôler</span>
            <span class="font-semibold text-blue-900">{{ minutes.people_to_enroll_count || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-blue-700">Enrôlées</span>
            <span class="font-semibold text-blue-900">{{ minutes.people_enrolled_count || 0 }}</span>
          </div>
        </div>
        
        <div class="mt-3">
          <div class="w-full bg-blue-200 rounded-full h-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(enrollmentRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Carte Cartes CMU -->
      <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center space-x-2">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <h4 class="text-sm font-semibold text-purple-900">Cartes CMU</h4>
          </div>
          <span class="text-xs text-purple-600 font-medium">{{ cmuDistributionRate.toFixed(1) }}%</span>
        </div>
        
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-purple-700">Disponibles</span>
            <span class="font-semibold text-purple-900">{{ minutes.cmu_cards_available_count || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-purple-700">Distribuées</span>
            <span class="font-semibold text-purple-900">{{ minutes.cmu_cards_distributed_count || 0 }}</span>
          </div>
        </div>
        
        <div class="mt-3">
          <div class="w-full bg-purple-200 rounded-full h-2">
            <div 
              class="bg-purple-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(cmuDistributionRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Carte Réclamations -->
      <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center space-x-2">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            <h4 class="text-sm font-semibold text-red-900">Réclamations</h4>
          </div>
          <span class="text-xs text-red-600 font-medium">{{ complaintProcessingRate.toFixed(1) }}%</span>
        </div>
        
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-red-700">Reçues</span>
            <span class="font-semibold text-red-900">{{ minutes.complaints_received_count || 0 }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-red-700">Traitées</span>
            <span class="font-semibold text-red-900">{{ minutes.complaints_processed_count || 0 }}</span>
          </div>
        </div>
        
        <div class="mt-3">
          <div class="w-full bg-red-200 rounded-full h-2">
            <div 
              class="bg-red-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: Math.min(complaintProcessingRate, 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Résumé des performances -->
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
      <h4 class="text-sm font-semibold text-gray-900 mb-3">Résumé des performances</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold" :class="getPerformanceColor(enrollmentRate)">
            {{ enrollmentRate.toFixed(1) }}%
          </div>
          <div class="text-xs text-gray-600">Taux d'enrôlement</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold" :class="getPerformanceColor(cmuDistributionRate)">
            {{ cmuDistributionRate.toFixed(1) }}%
          </div>
          <div class="text-xs text-gray-600">Taux de distribution CMU</div>
        </div>
        <div class="text-center">
          <div class="text-2xl font-bold" :class="getPerformanceColor(complaintProcessingRate)">
            {{ complaintProcessingRate.toFixed(1) }}%
          </div>
          <div class="text-xs text-gray-600">Taux de traitement</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  minutes: {
    type: Object,
    required: true
  }
})

// Calculs des taux
const enrollmentRate = computed(() => {
  const toEnroll = props.minutes.people_to_enroll_count || 0
  const enrolled = props.minutes.people_enrolled_count || 0
  return toEnroll > 0 ? (enrolled / toEnroll) * 100 : 0
})

const cmuDistributionRate = computed(() => {
  const available = props.minutes.cmu_cards_available_count || 0
  const distributed = props.minutes.cmu_cards_distributed_count || 0
  return available > 0 ? (distributed / available) * 100 : 0
})

const complaintProcessingRate = computed(() => {
  const received = props.minutes.complaints_received_count || 0
  const processed = props.minutes.complaints_processed_count || 0
  return received > 0 ? (processed / received) * 100 : 0
})

// Fonction pour déterminer la couleur selon la performance
const getPerformanceColor = (rate) => {
  if (rate >= 80) return 'text-green-600'
  if (rate >= 60) return 'text-yellow-600'
  return 'text-red-600'
}
</script> 