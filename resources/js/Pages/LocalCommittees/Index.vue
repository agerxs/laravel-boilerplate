<template>
  <Head title="Comités locaux" />

  <AppLayout title="Comités locaux">
    <div class="space-y-6">
      <!-- Header avec actions -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-900">
            Liste des comités locaux
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            Gérez les comités locaux et leurs membres
          </p>
        </div>
        <Link
          :href="route('local-committees.create')"
          class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg text-white font-medium text-sm"
        >
          <PlusIcon class="h-5 w-5 mr-2" />
          Nouveau comité
        </Link>
      </div>

      <!-- Filtres -->
      <div class="bg-white rounded-lg shadow p-4 space-y-4">
        <div class="flex gap-4">
          <div class="flex-1">
            <input
              type="text"
              v-model="search"
              placeholder="Rechercher..."
              class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
            />
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nom
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sous-préfecture
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Membres
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
            <tr v-for="committee in committees.data" :key="committee.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ committee.name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ committee.locality?.name }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm text-gray-900">
                  <AvatarGroup :members="committee.members" />
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <StatusBadge :status="committee.status" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-3">
                  <Link
                    :href="route('local-committees.show', { id: committee.id })"
                    class="text-blue-500 hover:text-blue-700 inline-flex items-center"
                    title="Voir le comité"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="route('local-committees.edit', committee.id)"
                    class="text-primary-600 hover:text-primary-900 inline-flex items-center"
                    title="Modifier le comité"
                  >
                    <PencilIcon class="h-5 w-5" />
                  </Link>
                  <button
                    @click="openRepresentativesModal(committee)"
                    class="text-green-600 hover:text-green-900 inline-flex items-center"
                    title="Voir les représentants par village"
                  >
                    <UserGroupIcon class="h-5 w-5" />
                  </button>
                  <button
                    @click="deleteCommittee(committee.id)"
                    class="text-red-500 hover:text-red-700 inline-flex items-center"
                    title="Supprimer le comité"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <Pagination :links="committees.links" />
        </div>
      </div>
    </div>

    <!-- Modal des représentants par village -->
    <div v-if="showRepresentativesModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" @click="showRepresentativesModal = false">
          <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
        
        <div 
          class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
          @click.stop
        >
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="sm:flex sm:items-start">
              <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Représentants par village - {{ selectedCommittee?.name }}
                  </h3>
                  <button
                    @click="showRepresentativesModal = false"
                    class="text-gray-400 hover:text-gray-500"
                  >
                    <span class="sr-only">Fermer</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                
                <div class="mt-4 space-y-6">
                  <div v-for="(representatives, villageName) in representativesByVillage" :key="villageName" class="border-b border-gray-200 pb-4">
                    <h4 class="text-lg font-medium text-gray-900 mb-2">{{ villageName }}</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      <div v-for="rep in representatives" :key="rep.id" class="bg-gray-50 p-3 rounded-lg">
                        <div class="font-medium">{{ rep.first_name }} {{ rep.last_name }}</div>
                        <div class="text-sm text-gray-600">{{ rep.role }}</div>
                        <div v-if="rep.phone" class="text-sm text-gray-500">{{ rep.phone }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { computed } from 'vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import AvatarGroup from '@/Components/AvatarGroup.vue'
import {
  PlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'
import axios from 'axios'

interface Member {
  id: number;
  user_id?: number;
  first_name?: string;
  last_name?: string;
  role: string;
  user?: {
    name: string;
  };
}

interface Committee {
  id: number;
  name: string;
  status: string;
  locality?: {
    name: string;
  };
  members: Member[];
}

interface Props {
  committees: {
    data: Committee[];
    links: any[];
  };
  filters: {
    search: string;
  };
}

const props = defineProps<Props>()

const search = ref(props.filters.search)

watch(search, (value) => {
  router.get(
    route('local-committees.index'),
    { search: value },
    { preserveState: true, preserveScroll: true }
  )
})

const deleteCommittee = (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce comité ?')) {
    router.delete(route('local-committees.destroy', id), {
      onSuccess: () => {
        alert('Comité supprimé avec succès.');
      }
    });
  }
}

const getMemberName = (member: Member): string => {
  if (member.user_id && member.user) {
    return member.user.name;
  }
  if (member.first_name && member.last_name) {
    return `${member.first_name} ${member.last_name}`;
  }
  return 'Membre sans nom';
}

const formatRole = (role: string): string => {
  const roles: { [key: string]: string } = {
    'president': 'Secrétaire',
    'vice_president': 'Vice-président',
    'treasurer': 'Trésorier',
    'secretary': 'Secrétaire',
    'member': 'Membre'
  }
  return roles[role] || role;
}

const showRepresentativesModal = ref(false);
const selectedCommittee = ref<Committee | null>(null);
const representativesByVillage = ref<{[key: string]: any[]}>({});

const openRepresentativesModal = async (committee: Committee) => {
  selectedCommittee.value = committee;
  try {
    const response = await axios.get(route('local-committees.village-representatives', committee.id));
    representativesByVillage.value = response.data;
    showRepresentativesModal.value = true;
  } catch (error) {
    console.error('Erreur lors de la récupération des représentants:', error);
  }
};
</script>

<style scoped>
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
</style> 