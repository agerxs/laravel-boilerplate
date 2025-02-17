<template>
  <AppLayout title="Comités Locaux">
    <template #header>
      <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Liste des comités locaux
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Gérez vos comités locaux et leurs membres
        </p>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Barre de recherche et bouton Nouveau -->
        <div class="flex justify-between items-center mb-6">
          <div class="flex-1 max-w-lg">
            <TextInput
              v-model="search"
              type="text"
              placeholder="Rechercher un comité..."
              class="w-full"
              @input="debouncedSearch"
            />
          </div>
          <div class="flex items-center space-x-4">
            <select
              v-model="perPage"
              class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 text-gray-900"
            >
              <option value="10">10 par page</option>
              <option value="25">25 par page</option>
              <option value="50">50 par page</option>
            </select>
            <Link
              :href="route('local-committees.create')"
              class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
              <PlusIcon class="h-5 w-5 mr-2" />
              Nouveau comité
            </Link>
          </div>
        </div>

        <!-- Tableau des comités -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Nom
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ville
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Membres
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Date de création
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="committee in committees.data" :key="committee.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ committee.name }}</div>
                  <div class="text-sm text-gray-500">{{ committee.address }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ committee.city }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex -space-x-2">
                    <div
                      v-for="member in committee.members.slice(0, 3)"
                      :key="member.id"
                      class="relative z-30 inline-block"
                    >
                      <img
                        :src="`https://ui-avatars.com/api/?name=${member.first_name}+${member.last_name}`"
                        :alt="member.first_name"
                        class="h-8 w-8 rounded-full ring-2 ring-white"
                        :title="`${member.first_name} ${member.last_name} (${member.role})`"
                      >
                    </div>
                    <div
                      v-if="committee.members.length > 3"
                      class="relative z-30 inline-flex h-8 w-8 items-center justify-center rounded-full ring-2 ring-white bg-gray-300"
                    >
                      <span class="text-xs font-medium text-gray-600">
                        +{{ committee.members.length - 3 }}
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(committee.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-3">
                    <Link
                      :href="route('local-committees.show', committee.id)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      Voir
                    </Link>
                    <Link
                      :href="route('local-committees.edit', committee.id)"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button
                      @click="deleteCommittee(committee)"
                      class="text-red-600 hover:text-red-900"
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
        <div class="mt-4">
          <Pagination :links="committees.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import debounce from 'lodash/debounce'
import TextInput from '@/Components/TextInput.vue'

const toast = useToast()

interface Member {
  id: number
  first_name: string
  last_name: string
  email: string
  phone: string
  role: string
}

interface Committee {
  id: number
  name: string
  description: string
  city: string
  address: string
  created_at: string
  members: Member[]
}

interface Props {
  committees: {
    data: Committee[]
    links: any[]
  }
  filters: {
    search: string
    per_page: string
  }
}

const props = defineProps<Props>()

// Initialiser avec les valeurs des filtres
const search = ref(props.filters.search)
const perPage = ref(props.filters.per_page.toString())

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const deleteCommittee = (committee: Committee) => {
  if (confirm(`Voulez-vous vraiment supprimer le comité "${committee.name}" ?`)) {
    router.delete(route('local-committees.destroy', committee.id), {
      onSuccess: () => {
        toast.success('Comité supprimé avec succès')
      },
      onError: () => {
        toast.error('Une erreur est survenue lors de la suppression')
      }
    })
  }
}

// Recherche avec debounce
const debouncedSearch = debounce(() => {
  router.get(
    route('local-committees.index'),
    { search: search.value, per_page: perPage.value },
    { preserveState: true, preserveScroll: true }
  )
}, 300)

// Observer les changements de perPage
watch(perPage, (value) => {
  router.get(
    route('local-committees.index'),
    { search: search.value, per_page: value },
    { preserveState: true, preserveScroll: true }
  )
})
</script> 