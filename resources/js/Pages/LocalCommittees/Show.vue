<template>
  <Head :title="committee.name" />

  <AppLayout :title="committee.name">
    <div class="max-w-10xl mx-auto py-6">
      <h1 class="text-3xl font-extrabold mb-6 text-gray-900">
        {{ committee.name }} <span class="text-lg text-gray-500">({{ committee.locality?.children.length || 0 }} villages)</span>
      </h1>

      <div class="mb-8">
        <h2 class="text-2xl font-semibold text-indigo-600">Villages et membres</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="village in committee.locality?.children" :key="village.id" class="bg-white shadow-md rounded-lg p-4">
            <h3 class="text-xl font-medium text-gray-800">
              {{ village.name }} <span class="text-sm text-gray-500">({{ village.representatives.length }} membres)</span>
            </h3>
            <ul class="list-none mt-2">
              <li v-for="rep in village.representatives" :key="rep.id" class="flex items-center space-x-4 py-2">
                <div class="flex-shrink-0">
                  <div class="h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center text-white">
                    {{ getInitials(rep) }}
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ rep.first_name }} {{ rep.last_name }}</p>
                  <p class="text-sm text-gray-500">{{ rep.phone }} - {{ formatRole(rep.role) }}</p>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <Link
        :href="route('local-committees.index')"
        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
      >
        Retour à la liste
      </Link>
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
    children:[]
  };
  members: Member[];
  villages: {
    id: number;
    name: string;
    representatives: {
      id: number;
      first_name: string;
      last_name: string;
      phone: string;
      role: string;
    }[];
  }[];
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
    'president': 'Président',
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