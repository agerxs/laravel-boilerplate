<template>
  <AppLayout title="Tableau de bord">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Statistiques générales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Total Réunions</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_meetings }}</div>
            <div class="mt-2 flex items-center text-sm text-green-600">
              <ArrowUpIcon class="h-4 w-4 mr-1" />
              <span>12% ce mois</span>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Réunions à venir</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.upcoming_meetings }}</div>
            <div class="mt-2 flex items-center text-sm text-indigo-600">
              <CalendarIcon class="h-4 w-4 mr-1" />
              <span>Cette semaine</span>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Utilisateurs</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_users }}</div>
            <div class="mt-2 flex items-center text-sm text-green-600">
              <UserGroupIcon class="h-4 w-4 mr-1" />
              <span>Actifs</span>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <div class="text-sm font-medium text-gray-500">Comités Locaux</div>
            <div class="mt-2 text-3xl font-semibold text-gray-900">{{ stats.total_committees }}</div>
            <div class="mt-2 flex items-center text-sm text-blue-600">
              <BuildingOfficeIcon class="h-4 w-4 mr-1" />
              <span>En activité</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Graphique des réunions par mois -->
          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Réunions par mois</h3>
            <div class="h-80">
              <LineChart :data="chartData.byMonth" />
            </div>
          </div>

          <!-- Graphique des réunions par statut -->
          <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Répartition par statut</h3>
            <div class="h-80">
              <DoughnutChart :data="chartData.byStatus" />
            </div>
          </div>
        </div>

        <!-- Prochaines réunions -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Prochaines réunions</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="meeting in upcomingMeetings" :key="meeting.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ meeting.title }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500">{{ formatDateTime(meeting.scheduled_date) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500">
                        {{ (meeting.local_committees || []).map((c: { name: string }) => c.name).join(', ') }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Link :href="route('meetings.show', meeting.id)" class="text-indigo-600 hover:text-indigo-900">
                        Voir
                      </Link>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div>
          <label for="subPrefecture">Sous-préfecture :</label>
          <select v-model="selectedSubPrefecture" @change="fetchVillages">
            <option v-for="subPrefecture in subPrefectures" :key="subPrefecture.id" :value="subPrefecture.id">
              {{ subPrefecture.name }}
            </option>
          </select>
        </div>

        <div v-if="villages.length > 0">
          <p>Nombre de villages : {{ villages.length }}</p>
          <ul>
            <li v-for="village in villages" :key="village.id">{{ village.name }}</li>
          </ul>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'
import {
  ArrowUpIcon,
  CalendarIcon,
  UserGroupIcon,
  BuildingOfficeIcon
} from '@heroicons/vue/24/outline'
import LineChart from '@/Components/Charts/LineChart.vue'
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue'

interface LocalCommittee {
  name: string;
}

interface Meeting {
  local_committees: LocalCommittee[];
}

const props = defineProps<{
  stats: {
    total_meetings: number
    upcoming_meetings: number
    total_users: number
    total_committees: number
  }
  upcomingMeetings: Meeting[]
  meetingsByMonth: Record<string, number>
  meetingsByStatus: Record<string, number>
}>()

const chartData = computed(() => ({
  byMonth: {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
    datasets: [{
      label: 'Réunions',
      data: Array.from({ length: 12 }, (_, i) => props.meetingsByMonth[i + 1] || 0),
      borderColor: 'rgb(79, 70, 229)',
      tension: 0.1
    }]
  },
  byStatus: {
    labels: ['Planifiée', 'Terminée', 'Annulée'],
    datasets: [{
      data: [
        props.meetingsByStatus['planned'] || 0,
        props.meetingsByStatus['completed'] || 0,
        props.meetingsByStatus['cancelled'] || 0
      ],
      backgroundColor: [
        'rgb(59, 130, 246)',
        'rgb(16, 185, 129)',
        'rgb(239, 68, 68)'
      ]
    }]
  }
}))

const formatDateTime = (datetime: string) => {
  return new Date(datetime).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const subPrefectures = ref([])
const selectedSubPrefecture = ref(null)
const villages = ref([])

const fetchVillages = () => {
  if (selectedSubPrefecture.value) {
    fetch(`/api/sub-prefectures/${selectedSubPrefecture.value}/villages`)
      .then(response => response.json())
      .then(data => {
        villages.value = data
      })
      .catch(error => {
        console.error('Erreur lors de la récupération des villages:', error)
      })
  }
}

const fetchSubPrefectures = () => {
  fetch('/api/sub-prefectures')
    .then(response => response.json())
    .then(data => {
      subPrefectures.value = data
    })
    .catch(error => {
      console.error('Erreur lors de la récupération des sous-préfectures:', error)
    })
}

fetchSubPrefectures()
</script> 