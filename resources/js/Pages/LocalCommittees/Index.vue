<template>
  <Head title="Comités Locaux" />

  <AppLayout title="Gestion des Comités Locaux">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">

        <!-- Messages flash -->
        <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ page.props.flash.error }}
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Liste des Comités Locaux</h2>
            
            <Link v-if="hasRole(props.auth.user.roles, 'admin')"
              :href="route('local-committees.create')"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
            >
              <PlusIcon class="h-4 w-4 mr-2" />
              Nouveau Comité
            </Link>
          </div>

          <!-- Filtres -->
          <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="md:col-span-3">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input
                  id="search"
                  type="text"
                  v-model="search"
                  placeholder="Rechercher par nom, sous-préfecture..."
                  class="w-full border-gray-300 rounded-md shadow-sm"
                  @keyup.enter="applyFilters"
                />
              </div>
            </div>
            <div class="mt-4 flex space-x-2 justify-end">
              <button
                @click="applyFilters"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Appliquer
              </button>
              <button
                @click="clearFilters"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium"
              >
                Réinitialiser
              </button>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sous-préfecture</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membres</th>
                  <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="committees.data.length === 0">
                  <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucun comité trouvé.</td>
                </tr>
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
                        v-if="hasRole(props.auth.user.roles, 'admin') || hasRole(props.auth.user.roles, 'secretaire')"
                        :href="route('local-committees.edit', committee.id)"
                        class="text-primary-600 hover:text-primary-900 inline-flex items-center"
                        title="Modifier le comité"
                      >
                        <PencilIcon class="h-5 w-5" />
                      </Link>
                     
                      <button v-if="hasRole(props.auth.user.roles, 'admin')"
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
          </div>

          <div class="mt-4">
            <Pagination :links="committees.links" />
          </div>
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
import { Head, Link, router, usePage } from '@inertiajs/vue3'
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
import { hasRole } from '@/utils/authUtils'
import { Role } from '@/types/Role'

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
  auth: {
    user: {
      roles: Role[];
    };
  };
  committees: {
    data: Committee[];
    links: any[];
  };
  filters: {
    search: string;
  };
}

const props = defineProps<Props>()
const page = usePage()

const search = ref(props.filters.search)

const applyFilters = () => {
  router.get(
    route('local-committees.index'),
    { search: search.value },
    { preserveState: true, replace: true }
  )
}

const clearFilters = () => {
  search.value = ''
  applyFilters()
}

const deleteCommittee = (id: number) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce comité ?')) {
    router.delete(route('local-committees.destroy', id))
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

console.log('Rôles initiaux de l\'utilisateur:', props.auth.user.roles);

watch(() => props.auth.user.roles, (newRoles) => {
  console.log('Rôles de l\'utilisateur:', newRoles);
});
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