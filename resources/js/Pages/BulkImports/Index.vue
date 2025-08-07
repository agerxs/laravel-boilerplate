<template>
  <Head title="Historique des imports" />

  <AppLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Historique des imports
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Filtres -->
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <InputLabel for="import_type" value="Type d'import" />
                <select
                  id="import_type"
                  v-model="filters.import_type"
                  @change="applyFilters"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">Tous les types</option>
                  <option value="meetings">Réunions</option>
                </select>
              </div>

              <div>
                <InputLabel for="date_from" value="Date de début" />
                <TextInput
                  id="date_from"
                  type="date"
                  v-model="filters.date_from"
                  @change="applyFilters"
                  class="mt-1 block w-full"
                />
              </div>

              <div>
                <InputLabel for="date_to" value="Date de fin" />
                <TextInput
                  id="date_to"
                  type="date"
                  v-model="filters.date_to"
                  @change="applyFilters"
                  class="mt-1 block w-full"
                />
              </div>
            </div>

            <div class="mt-4 flex justify-between items-center">
              <button
                @click="clearFilters"
                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
              >
                Effacer les filtres
              </button>
            </div>
          </div>

          <!-- Liste des imports -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fichier
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Comité local
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Utilisateur
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Réunions créées
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="importItem in imports.data" :key="importItem.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                          <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                          </svg>
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">
                          {{ importItem.original_filename }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ importItem.formatted_file_size }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ importItem.local_committee && importItem.local_committee.name }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ importItem.user && importItem.user.name }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ importItem.meetings_created }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(importItem.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-3">
                      <Link
                        :href="route('bulk-imports.show', importItem.id)"
                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                        title="Voir les détails"
                      >
                        <EyeIcon class="h-5 w-5" />
                      </Link>
                      <Link
                        v-if="importItem.file_path"
                        :href="route('bulk-imports.download', importItem.id)"
                        class="text-green-600 hover:text-green-900 p-1 rounded-md hover:bg-green-50"
                        title="Télécharger le fichier"
                      >
                        <ArrowDownTrayIcon class="h-5 w-5" />
                      </Link>
                      <button
                        @click="deleteImport(importItem.id)"
                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                        title="Supprimer l'import"
                      >
                        <TrashIcon class="h-5 w-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="px-6 py-4 border-t border-gray-200">
            <Pagination :links="imports.links" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import Pagination from '@/Components/Pagination.vue'
import { EyeIcon, ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline'

interface BulkImport {
  id: number
  original_filename: string
  file_size: number
  formatted_file_size: string
  status: string
  status_label: string
  meetings_created: number
  attachments_count: number
  created_at: string
  local_committee?: {
    id: number
    name: string
  }
  user?: {
    id: number
    name: string
  }
}

interface PaginatedData {
  data: BulkImport[]
  links: any[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface Filters {
  import_type: string
  date_from: string
  date_to: string
}

const props = defineProps<{
  imports: PaginatedData
  filters: Filters
}>()

const filters = ref({ ...props.filters })

const applyFilters = () => {
  router.get(route('bulk-imports.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  filters.value = {
    import_type: '',
    date_from: '',
    date_to: ''
  }
  applyFilters()
}

const deleteImport = (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet import ?')) {
    router.delete(route('bulk-imports.destroy', id))
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script> 