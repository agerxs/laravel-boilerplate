<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">
          Validation réussie
        </h2>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div class="text-center">
          <!-- Icône de succès -->
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>

          <h3 class="mt-4 text-lg font-medium text-gray-900">
            Présences validées avec succès
          </h3>

          <p class="mt-2 text-sm text-gray-600">
            {{ message }}
          </p>

          <!-- Détails de la réunion -->
          <div v-if="meeting" class="mt-6 bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Détails de la réunion</h4>
            <div class="text-sm text-gray-600 space-y-1">
              <p><strong>Titre :</strong> {{ meeting.title }}</p>
              <p><strong>Date :</strong> {{ formatDate(meeting.scheduled_date) }}</p>
              <p><strong>Localité :</strong> {{ meeting.locality_name }}</p>
            </div>
          </div>

          <div class="mt-6">
            <p class="text-xs text-gray-500">
              Cette validation est définitive et ne peut pas être annulée.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

interface Props {
  message: string
  meeting?: {
    id: number
    title: string
    scheduled_date: string
    locality_name: string
  }
}

const props = defineProps<Props>()

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric'
  })
}
</script> 