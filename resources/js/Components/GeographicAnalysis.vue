<template>
  <div class="geographic-analysis">
    <!-- En-tête avec indicateur de cohérence -->
    <div class="mb-4 p-4 bg-white rounded-lg shadow border">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900">Cohérence géographique</h3>
          </div>
          
          <!-- Indicateur de cohérence -->
          <div class="flex items-center space-x-2">
            <div 
              :class="[
                'w-3 h-3 rounded-full',
                {
                  'bg-green-500': consistencyLevel.level === 'excellent',
                  'bg-yellow-500': consistencyLevel.level === 'good',
                  'bg-orange-500': consistencyLevel.level === 'fair',
                  'bg-red-500': consistencyLevel.level === 'poor'
                }
              ]"
            ></div>
            <span class="text-sm font-medium" :class="{
              'text-green-700': consistencyLevel.level === 'excellent',
              'text-yellow-700': consistencyLevel.level === 'good',
              'text-orange-700': consistencyLevel.level === 'fair',
              'text-red-700': consistencyLevel.level === 'poor'
            }">
              {{ consistencyLevel.label }} ({{ analysis.consistency }}%)
            </span>
          </div>
        </div>
        
        <button
          @click="showDetails = !showDetails"
          class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center space-x-1"
        >
          <span>{{ showDetails ? 'Masquer' : 'Détails' }}</span>
          <svg 
            class="w-4 h-4 transition-transform" 
            :class="{ 'rotate-180': showDetails }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>
      
      <!-- Statistiques rapides -->
      <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
        <div>
          <span class="text-gray-600">Positions valides :</span>
          <span class="font-medium">{{ analysis.validPositions }}/{{ analysis.totalPositions }}</span>
        </div>
        <div>
          <span class="text-gray-600">Distance moyenne :</span>
          <span class="font-medium">{{ formatDistance(analysis.averageDistance) }}</span>
        </div>
        <div>
          <span class="text-gray-600">Anomalies :</span>
          <span class="font-medium" :class="{
            'text-red-600': analysis.outliers.length > 0,
            'text-green-600': analysis.outliers.length === 0
          }">
            {{ analysis.outliers.length }}
          </span>
        </div>
      </div>
    </div>

    <!-- Détails de l'analyse -->
    <div v-if="showDetails" class="mb-4">
      <!-- Alertes pour les anomalies -->
      <div v-if="analysis.outliers.length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
        <div class="flex items-start">
          <svg class="w-5 h-5 text-red-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
          <div>
            <h4 class="text-sm font-medium text-red-800">
              {{ analysis.outliers.length }} participant(s) hors zone détecté(s)
            </h4>
            <p class="text-sm text-red-700 mt-1">
              Ces participants sont à plus de {{ analysis.maxDistance }}m du centre de la réunion.
            </p>
          </div>
        </div>
      </div>

      <!-- Liste des participants avec leurs distances -->
      <div class="bg-white rounded-lg shadow border">
        <div class="px-4 py-3 border-b border-gray-200">
          <h4 class="text-sm font-medium text-gray-900">Détails des positions</h4>
        </div>
        <div class="divide-y divide-gray-200">
          <div 
            v-for="attendee in attendeesWithPositions" 
            :key="attendee.id"
            class="px-4 py-3 flex items-center justify-between"
            :class="{
              'bg-red-50': isOutlier(attendee),
              'bg-green-50': !isOutlier(attendee) && attendee.presence_location
            }"
          >
            <div class="flex items-center space-x-3">
              <!-- Photo ou avatar -->
              <div class="flex-shrink-0">
                <div v-if="attendee.presence_photo" class="h-10 w-10 rounded-full overflow-hidden">
                  <img :src="`/storage/${attendee.presence_photo}`" class="h-full w-full object-cover" />
                </div>
                <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                </div>
              </div>
              
              <!-- Informations du participant -->
              <div>
                <p class="text-sm font-medium text-gray-900">{{ attendee.name }}</p>
                <p class="text-xs text-gray-500">{{ attendee.role }}</p>
                <p v-if="attendee.phone" class="text-xs text-gray-500">📞 {{ attendee.phone }}</p>
              </div>
            </div>
            
            <!-- Distance et statut -->
            <div class="flex items-center space-x-3">
              <div v-if="attendee.presence_location" class="text-right">
                <p class="text-sm font-medium" :class="{
                  'text-red-600': isOutlier(attendee),
                  'text-green-600': !isOutlier(attendee)
                }">
                  {{ formatDistance(getDistanceFromCenter(attendee)) }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ formatLocation(attendee.presence_location) }}
                </p>
              </div>
              <div v-else class="text-sm text-gray-400">
                Pas de position
              </div>
              
              <!-- Indicateur d'anomalie -->
              <div v-if="isOutlier(attendee)" class="flex-shrink-0">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { analyzeGeographicConsistency, getConsistencyLevel, formatDistance, calculateDistance } from '@/utils/geoUtils'

const props = defineProps({
  attendees: {
    type: Array,
    required: true
  },
  maxDistance: {
    type: Number,
    default: 100
  }
})

const showDetails = ref(false)

// Filtrer les participants avec des positions GPS
const attendeesWithPositions = computed(() => {
  return props.attendees.filter(attendee => 
    attendee.presence_location && 
    attendee.presence_location.latitude && 
    attendee.presence_location.longitude
  )
})

// Analyser la cohérence géographique
const analysis = computed(() => {
  const positions = attendeesWithPositions.value.map(attendee => ({
    ...attendee,
    latitude: attendee.presence_location.latitude,
    longitude: attendee.presence_location.longitude
  }))
  
  return analyzeGeographicConsistency(positions, props.maxDistance)
})

// Niveau de cohérence
const consistencyLevel = computed(() => {
  return getConsistencyLevel(analysis.value.consistency)
})

// Vérifier si un participant est une anomalie
const isOutlier = (attendee) => {
  return analysis.value.outliers.some(outlier => outlier.attendee.id === attendee.id)
}

// Obtenir la distance d'un participant au centre
const getDistanceFromCenter = (attendee) => {
  if (!attendee.presence_location || !analysis.value.center) return 0
  
  return calculateDistance(
    analysis.value.center.lat,
    analysis.value.center.lng,
    parseFloat(attendee.presence_location.latitude),
    parseFloat(attendee.presence_location.longitude)
  )
}

// Formater la localisation
const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  
  const lat = parseFloat(location.latitude)
  const lng = parseFloat(location.longitude)
  
  if (isNaN(lat) || isNaN(lng)) {
    return 'Coordonnées invalides'
  }
  
  return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
}
</script> 