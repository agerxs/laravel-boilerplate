<template>
  <AppLayout title="Réunions">
    <div class="space-y-6">
      <!-- Header avec actions -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">
            Liste des réunions
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            Gérez vos réunions et suivez leur statut
          </p>
        </div>
        <Link
          :href="route('meetings.create')"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg text-white font-medium text-sm"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Nouvelle réunion
        </Link>
      </div>

      <!-- Filtres et recherche -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
          <div class="flex items-center space-x-4">
            <div class="flex-1">
              <label class="sr-only">Rechercher</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                  </svg>
                </div>
                <input
                  type="text"
                  v-model="searchQuery"
                  class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="Rechercher une réunion..."
                />
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <select
                v-model="perPage"
                class="block pl-3 pr-10 py-2 text-sm border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="10">10 par page</option>
                <option value="25">25 par page</option>
                <option value="50">50 par page</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Titre
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date & Heure
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Lieu
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Statut
                </th>
                <th scope="col" class="relative px-6 py-3">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="meeting in meetings.data" :key="meeting.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ meeting.title }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">
                    {{ formatDateTime(meeting.start_datetime) }}
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ formatDateTime(meeting.end_datetime) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">
                    {{ meeting.location || 'Non défini' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    statusColors[meeting.status].bg,
                    statusColors[meeting.status].text,
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full'
                  ]">
                    <span class="flex items-center">
                      <span :class="[statusColors[meeting.status].dot, 'flex-shrink-0 h-2 w-2 rounded-full mr-1.5']"></span>
                      {{ getStatusLabel(meeting.status) }}
                    </span>
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <Link
                    :href="route('meetings.show', meeting.id)"
                    class="text-primary-600 hover:text-primary-900 mr-4"
                  >
                    Voir
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <Pagination :links="meetings.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { ref } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

interface Meeting {
    id: number;
    title: string;
    start_datetime: string;
    end_datetime: string;
    location: string;
    status: string;
}

interface Props {
    meetings: {
        data: Meeting[];
        links: any[];
    };
}

const props = defineProps<Props>()
const searchQuery = ref('')
const perPage = ref(10)

const statusColors = {
    'planned': { bg: 'bg-yellow-50', text: 'text-yellow-800', dot: 'bg-yellow-400' },
    'ongoing': { bg: 'bg-blue-50', text: 'text-blue-800', dot: 'bg-blue-400' },
    'completed': { bg: 'bg-green-50', text: 'text-green-800', dot: 'bg-green-400' },
    'cancelled': { bg: 'bg-red-50', text: 'text-red-800', dot: 'bg-red-400' },
}

const formatDateTime = (datetime: string) => {
    return new Date(datetime).toLocaleString('fr-FR', {
        dateStyle: 'long',
        timeStyle: 'short'
    })
}

const getStatusLabel = (status: string) => {
    const labels = {
        'planned': 'Planifiée',
        'ongoing': 'En cours',
        'completed': 'Terminée',
        'cancelled': 'Annulée'
    }
    return labels[status] || status
}
</script>

<style>
.bg-primary-600 {
    background-color: rgb(79, 70, 229);
}
.hover\:bg-primary-700:hover {
    background-color: rgb(67, 56, 202);
}
.text-primary-600 {
    color: rgb(79, 70, 229);
}
.hover\:text-primary-900:hover {
    color: rgb(49, 46, 129);
}
.focus\:ring-primary-500:focus {
    --tw-ring-color: rgb(99, 102, 241);
}
.focus\:border-primary-500:focus {
    border-color: rgb(99, 102, 241);
}
</style> 