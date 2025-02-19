<template>
  <Head title="Réunions" />

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

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Titre
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Sous-préfecture
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
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
                  {{ meeting.locality_name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ formatDate(meeting.scheduled_date) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <MeetingStatusBadge :status="meeting.status" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <Link
                  :href="route('meetings.show', { meeting: meeting.id })"
                  class="text-primary-600 hover:text-primary-900 mr-4 inline-flex items-center"
                  title="Voir la réunion"
                >
                  <EyeIcon class="h-5 w-5" />
                </Link>
                <button
                  v-if="meeting.status === 'scheduled'"
                  @click="cancelMeeting(meeting)"
                  class="text-red-600 hover:text-red-900 inline-flex items-center"
                  title="Annuler la réunion"
                >
                  <XCircleIcon class="h-5 w-5" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
          <Pagination :links="meetings.links" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { ref } from 'vue'
import {
  PlusIcon,
  EyeIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'
import MeetingStatusBadge from '@/Components/MeetingStatusBadge.vue'

interface Meeting {
  id: number;
  title: string;
  scheduled_date: string;
  status: string;
  locality_name: string;
}

interface Props {
  meetings: {
    data: Meeting[];
    links: any[];
  };
}

const props = defineProps<Props>()
const toast = useToast()

const formatDate = (date: string) => {
  if (!date) return 'Date non définie';
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

const cancelMeeting = async (meeting: Meeting) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réunion ?')) return;

  try {
    await axios.post(route('meetings.cancel', meeting.id));
    meeting.status = 'cancelled';
    toast.success('La réunion a été annulée');
  } catch (error) {
    console.error('Erreur:', error);
    toast.error('Erreur lors de l\'annulation de la réunion');
  }
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
</style> 