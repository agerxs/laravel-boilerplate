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
                <Link
                  :href="route('local-committees.show', committee.id)"
                  class="text-primary-600 hover:text-primary-900 mr-4"
                  title="Voir le comité"
                >
                  <EyeIcon class="h-5 w-5" />
                </Link>
                <Link
                  :href="route('local-committees.edit', committee.id)"
                  class="text-primary-600 hover:text-primary-900"
                  title="Modifier le comité"
                >
                  <PencilIcon class="h-5 w-5" />
                </Link>
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
} from '@heroicons/vue/24/outline'

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