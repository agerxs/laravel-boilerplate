<template>
  <AppLayout title="Tableau de bord">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Tableau de bord
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Section Gestionnaire -->
        <template v-if="user?.roles?.includes('gestionnaire')">
          <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Gestion des Paiements</h3>
            
            <!-- Statistiques des paiements -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <Link
                :href="route('meeting-payments.lists.index', { status: 'all' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Montant total des paiements</h3>
                <p class="mt-2 text-3xl font-bold text-indigo-600">{{ formatCurrency(stats.total_payments) }}</p>
              </Link>

              <Link
                :href="route('meeting-payments.lists.index', { status: 'submitted' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Paiements en attente</h3>
                <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.pending_payments }}</p>
                <p class="mt-2 text-xs text-gray-500">Montant total : {{ formatCurrency(stats.pending_payments_amount) }}</p>
                <p class="mt-2 text-xs text-gray-500">Cliquez pour voir la liste</p>
              </Link>

              <Link
                :href="route('meeting-payments.lists.index', { status: 'validated' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Paiements validés</h3>
                <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.validated_payments }}</p>
                <p class="mt-2 text-xs text-gray-500">Cliquez pour voir la liste</p>
              </Link>

              <Link
                :href="route('meetings.index', { has_pending_payments: true })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Réunions en attente</h3>
                <p class="mt-2 text-3xl font-bold text-orange-600">{{ stats.meetings_with_pending_payments }}</p>
                <p class="mt-2 text-xs text-gray-500">Cliquez pour voir la liste</p>
              </Link>
            </div>

            <!-- Détails des paiements par rôle -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <Link
                :href="route('meeting-payments.lists.index', { role: 'sous_prefet' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Paiements Sous-préfets</h3>
                <p class="mt-2 text-2xl font-bold text-blue-600">{{ formatCurrency(stats.sub_prefet_payments) }}</p>
                <p class="mt-2 text-xs text-gray-500">50.000 FCFA par 2 réunions</p>
              </Link>

              <Link
                :href="route('meeting-payments.lists.index', { role: 'secretaire' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Paiements Secrétaires</h3>
                <p class="mt-2 text-2xl font-bold text-purple-600">{{ formatCurrency(stats.secretary_payments) }}</p>
                <p class="mt-2 text-xs text-gray-500">25.000 FCFA par 2 réunions</p>
              </Link>

              <Link
                :href="route('meeting-payments.lists.index', { role: 'participant' })"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 hover:bg-gray-50 transition-colors duration-200"
              >
                <h3 class="text-sm font-medium text-gray-500">Paiements Participants</h3>
                <p class="mt-2 text-2xl font-bold text-green-600">{{ formatCurrency(stats.participant_payments) }}</p>
                <p class="mt-2 text-xs text-gray-500">15.000 FCFA par réunion</p>
              </Link>
            </div>

            <!-- Dernières listes de paiement -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Dernières listes de paiement soumises</h3>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soumis par</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="list in stats.recent_payment_lists" :key="list.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ list.meeting.title }}</div>
                        <div class="text-sm text-gray-500">{{ formatDate(list.meeting.scheduled_date) }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ list.meeting.local_committee.name }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ formatCurrency(list.total_amount) }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ list.submitter.name }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ formatDate(list.submitted_at) }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <Link
                          :href="route('meeting-payments.lists.show', list.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Voir détails
                        </Link>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </template>

        <!-- Section Statistiques Générales -->
        <div class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Statistiques Générales</h3>
          
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

          <!-- Graphiques -->
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Réunions par mois</h3>
              <div class="h-80">
                <LineChart :data="chartData.byMonth" />
              </div>
            </div>

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
                          {{ meeting.local_committee?.name }}
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
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

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
    total_payments: number
    pending_payments: number
    validated_payments: number
    meetings_with_pending_payments: number
    sub_prefet_payments: number
    secretary_payments: number
    participant_payments: number
    recent_payment_lists: any[]
    pending_payments_amount: number
  }
  upcomingMeetings: Meeting[]
  meetingsByMonth: Record<string, number>
  meetingsByStatus: Record<string, number>
  user: {
    id: number
    name: string
    email: string
    roles: string[]
    locality_id: number
  }
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
    labels: ['Planifiée', 'Prévalidée', 'Validée', 'Terminée', 'Annulée'],
    datasets: [{
      data: [
        (props.meetingsByStatus['scheduled'] || 0) + (props.meetingsByStatus['planned'] || 0),
        props.meetingsByStatus['prevalidated'] || 0,
        props.meetingsByStatus['validated'] || 0,
        props.meetingsByStatus['completed'] || 0,
        props.meetingsByStatus['cancelled'] || 0
      ],
      backgroundColor: [
        'rgb(234, 179, 8)',    // Jaune pour planifiée
        'rgb(59, 130, 246)',   // Bleu pour prévalidée
        'rgb(124, 58, 237)',   // Violet pour validée
        'rgb(16, 185, 129)',   // Vert pour terminée
        'rgb(239, 68, 68)'     // Rouge pour annulée
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

const formatDate = (date) => {
  if (!date) return ''
  return format(new Date(date), 'dd MMMM yyyy', { locale: fr })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount)
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