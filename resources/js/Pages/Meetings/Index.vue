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

      <!-- Champ de recherche -->
      <div class="mb-4">
        <input
          v-model="search"
          type="text"
          placeholder="Rechercher..."
          class="border rounded-md p-2 w-full"
        />
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('title')"
              >
                Titre
                <span v-if="sortColumn === 'title'" class="ml-1">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('scheduled_date')"
              >
                Date
                <span v-if="sortColumn === 'date'" class="ml-1">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('status')"
              >
                Statut
                <span v-if="sortColumn === 'status'" class="ml-1">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th 
                scope="col" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                @click="sortBy('local_committee')"
              >
                Comité Local
                <span v-if="sortColumn === 'local_committee'" class="ml-1">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="meeting in sortedMeetings" :key="meeting.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ meeting.title }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ formatDate(meeting.scheduled_date) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <MeetingStatusBadge :status="meeting.status" :scheduled-date="meeting.scheduled_date" />
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ meeting.locality_name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="action-buttons-container">
                  <Link
                    :href="route('meetings.show', { meeting: meeting.id })"
                    class="text-primary-600 hover:text-primary-900 action-button"
                    title="Voir la réunion"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="route('meeting-payments.lists.create', { meeting: meeting.id })"
                    class="text-green-600 hover:text-green-900 action-button"
                    title="Gérer les paiements"
                    v-if="meeting.status === 'confirmed' && canManagePayments(meeting)"
                  >
                    <CurrencyDollarIcon class="h-5 w-5" />
                  </Link>
                  <button
                    v-if="meeting.status === 'scheduled' && !isSubPrefect"
                    @click="cancelMeeting(meeting)"
                    class="text-red-600 hover:text-red-900 action-button"
                    title="Annuler la réunion"
                  >
                    <XCircleIcon class="h-5 w-5" />
                  </button>
                  <Link
                    v-if="!isSubPrefect && meeting.status !== 'prevalidated' && meeting.status !== 'validated' && meeting.status !== 'cancelled' && meeting.status !== 'completed'"
                    :href="route('meetings.reschedule.form', meeting.id)"
                    class="text-yellow-600 hover:text-yellow-900 action-button"
                    title="Reporter la réunion"
                  >
                    <ClockIcon class="h-5 w-5" />
                  </Link>
                  <MeetingValidationButtons :meeting="meeting" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
        <Pagination :links="meetings.links" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, router, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { ref, computed, watch} from 'vue'
import {
  PlusIcon,
  EyeIcon,
  XCircleIcon,
  ClockIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'
import MeetingStatusBadge from '@/Components/MeetingStatusBadge.vue'
import MeetingValidationButtons from '@/Components/MeetingValidationButtons.vue'
import { usePage } from '@inertiajs/vue3'

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
  filters: {
    search: string;
    sort?: string;
    direction?: string;
  };
}

const props = defineProps<Props>()
const toast = useToast()

const search = ref(props.filters.search)

// État pour le tri
const sortColumn = ref(props.filters.sort || 'scheduled_date')
const sortDirection = ref(props.filters.direction || 'desc')

// Fonction pour changer la colonne de tri
const sortBy = (column) => {
  let direction;
  
  // Si on clique sur la même colonne, on inverse la direction
  if (sortColumn.value === column) {
    direction = sortDirection.value === 'asc' ? 'desc' : 'asc';
    sortDirection.value = direction;
  } else {
    // Pour une nouvelle colonne, on commence par asc
    sortColumn.value = column;
    direction = 'asc';
    sortDirection.value = direction;
  }
  
  // Effectuer une requête au serveur avec les paramètres de tri
  router.get(
    route('meetings.index'),
    { 
      search: search.value,
      sort: column,
      direction: direction
    },
    { 
      preserveState: true, 
      preserveScroll: true 
    }
  );
}

// Computed property pour les réunions triées
const sortedMeetings = computed(() => {
  return [...props.meetings.data].sort((a, b) => {
    let valueA, valueB;
    
    // Déterminer les valeurs à comparer en fonction de la colonne
    switch (sortColumn.value) {
      case 'title':
        valueA = a.title.toLowerCase();
        valueB = b.title.toLowerCase();
        break;
      case 'date':
        valueA = new Date(a.scheduled_date);
        valueB = new Date(b.scheduled_date);
        break;
      case 'status':
        valueA = a.status.toLowerCase();
        valueB = b.status.toLowerCase();
        break;
      case 'local_committee':
        valueA = a.locality_name.toLowerCase();
        valueB = b.locality_name.toLowerCase();
        break;
      default:
        valueA = a[sortColumn.value];
        valueB = b[sortColumn.value];
    }
    
    // Comparer les valeurs
    if (valueA < valueB) {
      return sortDirection.value === 'asc' ? -1 : 1;
    }
    if (valueA > valueB) {
      return sortDirection.value === 'asc' ? 1 : -1;
    }
    return 0;
  });
});

watch(search, (value) => {
  router.get(
    route('meetings.index'),
    { search: value },
    { preserveState: true, preserveScroll: true }
  )
})


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

// Ajouter d'abord la vérification du rôle de sous-préfet dans la partie script
const user = computed(() => usePage().props.auth.user)

// Vérifier si l'utilisateur est un sous-préfet
const isSubPrefect = computed(() => {
  return user.value?.roles?.some(role => 
    ['sous-prefet', 'Sous-prefet'].includes(role.name)
  ) || false
})

const canManagePayments = (meeting) => {
  const meetingDate = new Date(meeting.scheduled_date);
  const twoDaysBefore = new Date(meetingDate);
  twoDaysBefore.setDate(twoDaysBefore.getDate() - 2);
  const now = new Date();
  
  return now <= twoDaysBefore;
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

/* Styles pour les boutons d'action */
.action-buttons-container {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}
</style> 