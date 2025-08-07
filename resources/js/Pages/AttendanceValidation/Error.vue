<template>
  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-gray-900">
          Erreur de validation
        </h2>
      </div>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div class="text-center">
          <!-- Icône d'erreur -->
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </div>

          <h3 class="mt-4 text-lg font-medium text-gray-900">
            Impossible de valider les présences
          </h3>

          <p class="mt-2 text-sm text-gray-600">
            {{ message }}
          </p>

          <div class="mt-6">
            <p class="text-xs text-gray-500">
              Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur du système.
            </p>
          </div>

          <div class="mt-6">
            <a href="/" class="text-sm text-blue-600 hover:text-blue-500">
              Retour à l'accueil
            </a>
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
  token: string
}

const props = defineProps<Props>()
</script> 