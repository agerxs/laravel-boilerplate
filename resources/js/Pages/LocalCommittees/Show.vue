<template>
  <Head :title="committee.name" />

  <AppLayout :title="committee.name">
    <div class="max-w-7xl mx-auto py-6">
      <div class="bg-white shadow rounded-lg">
        <!-- En-tête -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900">
              Détails du comité
            </h2>
            <Link
              :href="route('local-committees.edit', committee.id)"
              class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg text-white text-sm"
            >
              <PencilIcon class="h-5 w-5 mr-2" />
              Modifier
            </Link>
          </div>
        </div>

        <!-- Informations générales -->
        <div class="px-6 py-4">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500">Sous-préfecture</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ committee.locality?.name }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">Statut</dt>
              <dd class="mt-1">
                <StatusBadge :status="committee.status" />
              </dd>
            </div>
          </dl>
        </div>

        <!-- Liste des membres -->
        <div class="px-6 py-4 border-t border-gray-200">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Membres du comité</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nom
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Rôle
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Téléphone
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="member in committee.members" :key="member.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center mr-3">
                        <span class="text-sm font-medium text-gray-600">
                          {{ getInitials(member) }}
                        </span>
                      </div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ getMemberName(member) }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ formatRole(member.role) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ getMemberPhone(member) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <StatusBadge :status="member.status" />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { PencilIcon } from '@heroicons/vue/24/outline'

interface Member {
  id: number;
  user_id?: number;
  first_name?: string;
  last_name?: string;
  phone?: string;
  role: string;
  status: string;
  user?: {
    name: string;
    phone?: string;
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
  committee: Committee;
}

const props = defineProps<Props>();

const getMemberName = (member: Member): string => {
  if (member.user_id && member.user) {
    return member.user.name;
  }
  if (member.first_name && member.last_name) {
    return `${member.first_name} ${member.last_name}`;
  }
  return 'Membre sans nom';
}

const getInitials = (member: Member): string => {
  if (member.user_id && member.user) {
    return member.user.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }
  if (member.first_name && member.last_name) {
    return (member.first_name[0] + member.last_name[0]).toUpperCase();
  }
  return 'XX';
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

const getMemberPhone = (member: Member): string => {
  if (member.user_id && member.user?.phone) {
    return member.user.phone;
  }
  if (member.phone) {
    return member.phone;
  }
  return 'Non renseigné';
}
</script> 