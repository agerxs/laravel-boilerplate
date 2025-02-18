<template>
  <AppLayout :title="committee.name">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ committee.name }}
        </h2>
        <div class="flex space-x-3">
          <Link
            :href="route('local-committees.edit', committee.id)"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
          >
            <PencilIcon class="h-5 w-5 mr-2" />
            Modifier
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Informations du comité -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Détails</h3>
                <div class="mt-4 space-y-2">
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Ville :</span>
                    {{ committee.city }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Adresse :</span>
                    {{ committee.address }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Créé le :</span>
                    {{ formatDate(committee.created_at) }}
                  </p>
                </div>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Description</h3>
                <p class="mt-4 text-sm text-gray-600">
                  {{ committee.description || 'Aucune description' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste des membres -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Membres</h3>
              <Link
                :href="route('local-committees.edit', committee.id)"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm"
              >
                <UserPlusIcon class="h-5 w-5 mr-2" />
                Gérer les membres
              </Link>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="member in committee.members"
                :key="member.id"
                class="bg-gray-50 p-4 rounded-lg"
              >
                <div class="flex items-start space-x-4">
                  <img
                    :src="`https://ui-avatars.com/api/?name=${member.first_name}+${member.last_name}`"
                    :alt="member.first_name"
                    class="h-12 w-12 rounded-full"
                  >
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                      {{ member.first_name }} {{ member.last_name }}
                    </p>
                    <p class="text-sm text-gray-500">{{ member.email }}</p>
                    <p class="text-sm text-gray-500">{{ member.phone }}</p>
                    <span 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-2"
                      :class="{
                        'bg-blue-100 text-blue-800': member.role === 'president',
                        'bg-green-100 text-green-800': member.role === 'secretary',
                        'bg-gray-100 text-gray-800': member.role === 'member'
                      }"
                    >
                      {{ member.role }}
                    </span>
                  </div>
                  <a
                    :href="`mailto:${member.email}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    <EnvelopeIcon class="h-5 w-5" />
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Réunions associées -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Réunions associées</h3>
            <div v-if="committee.meetings?.length" class="space-y-4">
              <div
                v-for="meeting in committee.meetings"
                :key="meeting.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
              >
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ meeting.title }}</h4>
                  <p class="text-sm text-gray-500">
                    {{ formatDateTime(meeting.start_datetime) }}
                  </p>
                </div>
                <Link
                  :href="route('meetings.show', meeting.id)"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  Voir
                </Link>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500">
              Aucune réunion associée
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  PencilIcon,
  UserPlusIcon,
  EnvelopeIcon
} from '@heroicons/vue/24/outline'

interface Meeting {
  id: number
  title: string
  start_datetime: string
}

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
  meetings?: Meeting[]
}

const props = defineProps<{
  committee: Committee
}>()

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (datetime: string) => {
  return new Date(datetime).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script> 