<template>
  <Head title="Réunions" />

  <AppLayout title="Gestion des Réunions">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        
        <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ page.props.flash.error }}
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Liste des Réunions</h2>
            <div class="flex space-x-3">
              <Link
                :href="route('meetings.create-multiple')"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
              >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Créer plusieurs réunions
              </Link>
              <Link
                :href="route('meetings.create')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-flex items-center"
              >
                <PlusIcon class="h-4 w-4 mr-2" />
                Nouvelle Réunion
              </Link>
            </div>
          </div>

          <!-- Filtres -->
          <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div class="lg:col-span-2">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input id="search" type="text" v-model="filters.search" @keyup.enter="applyFilters" placeholder="Par titre..." class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
              <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select id="status" v-model="filters.status" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous</option>
                  <option v-for="status in meetingStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
              </div>
              <div>
                <label for="hierarchy" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select id="hierarchy" v-model="filters.hierarchy" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Toutes</option>
                  <option value="parent">Réunions parent uniquement</option>
                  <option value="sub">Sous-réunions uniquement</option>
                  <option value="normal">Réunions normales uniquement</option>
                </select>
              </div>
              <div class="lg:col-span-2">
                <label for="committee" class="block text-sm font-medium text-gray-700 mb-1">Comité Local</label>
                <select id="committee" v-model="filters.local_committee_id" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous</option>
                  <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">{{ committee.name }}</option>
                </select>
              </div>
              <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input id="date_from" type="date" v-model="filters.date_from" class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
              <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input id="date_to" type="date" v-model="filters.date_to" class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
            </div>
            <div class="mt-4 flex space-x-2 justify-end">
               <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">Appliquer</button>
               <button @click="clearFilters" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">Réinitialiser</button>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                    <th @click="sortBy('title')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Titre</th>
                    <th @click="sortBy('scheduled_date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Date</th>
                    <th @click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Présences</th>
                    <th @click="sortBy('local_committee')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Comité Local</th>
                    <th @click="sortBy('updated_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer">Date de modification</th>
                    <th class="relative px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                 <tr v-if="meetings.data.length === 0">
                  <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucune réunion trouvée.</td>
                </tr>
                <template v-for="meeting in meetings.data" :key="meeting.id">
                  <!-- Réunion principale (ne montrer que les réunions parent ou normales) -->
                  <tr v-if="!meeting.parent_meeting_id" :class="getMeetingRowClass(meeting)">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 max-w-xs">
                      <div class="flex items-center">
                        <!-- Bouton expand/collapse pour les réunions parent -->
                        <button 
                          v-if="meeting.sub_meetings_count && meeting.sub_meetings_count > 0"
                          @click="toggleSubMeetings(meeting.id)"
                          class="mr-2 text-blue-500 hover:text-blue-700 transition-colors"
                          :title="expandedMeetings.includes(meeting.id) ? 'Réduire' : 'Développer'"
                        >
                          <svg 
                            class="h-4 w-4 transition-transform" 
                            :class="{ 'rotate-90': expandedMeetings.includes(meeting.id) }"
                            fill="none" 
                            stroke="currentColor" 
                            viewBox="0 0 24 24"
                          >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                          </svg>
                        </button>
                        <div v-else class="mr-2 w-4"></div>
                        
                        <Link 
                          :href="route('meetings.show', { meeting: meeting.id })" 
                          class="text-blue-700 hover:underline"
                          :title="meeting.title"
                        >
                          {{ meeting.title }}
                        </Link>
                        
                        <!-- Badge pour indiquer le type -->
                        <span v-if="meeting.parent_meeting_id" class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">
                          Sous-réunion
                        </span>
                        <span v-else-if="meeting.sub_meetings_count && meeting.sub_meetings_count > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded">
                          Sous-réunions ({{ meeting.sub_meetings_count }})
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(meeting.scheduled_date) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <MeetingStatusBadge :status="meeting.status" :scheduled-date="meeting.scheduled_date" />
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <!-- Affichage direct du statut -->
                        <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border"
                             :class="{
                               'bg-gray-100 text-gray-700 border-gray-200': meeting.attendance_status === 'draft' || !meeting.attendance_status,
                               'bg-blue-100 text-blue-700 border-blue-200': meeting.attendance_status === 'submitted',
                               'bg-green-100 text-green-700 border-green-200': meeting.attendance_status === 'validated'
                             }">
                          {{ meeting.attendance_status === 'submitted' ? 'Soumis' : meeting.attendance_status === 'validated' ? 'Validé' : 'Brouillon' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ meeting.locality_name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(meeting.updated_at) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                             <Link :href="route('meetings.show', { meeting: meeting.id })" class="flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 rounded transition-colors hover:bg-blue-50" title="Voir"><EyeIcon class="h-5 w-5" /></Link>
                             <button v-if="isSecretary && !isSubPrefect && (meeting.status === 'scheduled' || meeting.status === 'planned')" @click="cancelMeeting(meeting)" class="flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 rounded transition-colors hover:bg-red-50" title="Annuler"><XCircleIcon class="h-5 w-5" /></button>
                             <Link v-if="isSecretary && !isSubPrefect && (meeting.status === 'scheduled' || meeting.status === 'planned')" :href="route('meetings.reschedule', meeting.id)" class="flex items-center justify-center w-8 h-8 text-yellow-600 hover:text-yellow-900 rounded transition-colors hover:bg-yellow-50" title="Reporter"><ClockIcon class="h-5 w-5" /></Link>
                             <button v-if="canSplitMeeting(meeting)" @click="splitMeeting(meeting)" class="flex items-center justify-center w-8 h-8 text-purple-600 hover:text-purple-900 rounded transition-colors hover:bg-purple-50" title="Éclater la réunion"><Squares2X2Icon class="h-5 w-5" /></button>
                             <MeetingValidationButtons :meeting="meeting" />
                        </div>
                    </td>
                  </tr>
                  
                  <!-- Sous-réunions (si développées) -->
                  <template v-if="expandedMeetings.includes(meeting.id) && meeting.sub_meetings">
                    <tr v-for="subMeeting in meeting.sub_meetings" :key="subMeeting.id" class="bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 max-w-xs">
                        <div class="flex items-center min-w-0">
                          <div class="mr-6 w-4 flex-shrink-0"></div> <!-- Espace pour l'alignement -->
                          <div class="mr-2 w-4 flex-shrink-0"></div> <!-- Espace vide pour l'alignement -->
                          <Link 
                            :href="route('meetings.show', { meeting: subMeeting.id })" 
                            class="text-blue-700 hover:underline truncate flex-1 min-w-0"
                            :title="subMeeting.title"
                          >
                            {{ subMeeting.title }}
                          </Link>
                          <span class="ml-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded flex-shrink-0">
                            Sous-réunion
                          </span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(subMeeting.scheduled_date) }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">
                          <MeetingStatusBadge :status="subMeeting.status" :scheduled-date="subMeeting.scheduled_date" />
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                          <!-- Affichage direct du statut -->
                          <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border"
                               :class="{
                                 'bg-gray-100 text-gray-700 border-gray-200': subMeeting.attendance_status === 'draft' || !subMeeting.attendance_status,
                                 'bg-blue-100 text-blue-700 border-blue-200': subMeeting.attendance_status === 'submitted',
                                 'bg-green-100 text-green-700 border-green-200': subMeeting.attendance_status === 'validated'
                               }">
                            {{ subMeeting.attendance_status === 'submitted' ? 'Soumis' : subMeeting.attendance_status === 'validated' ? 'Validé' : 'Brouillon' }}
                          </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ subMeeting.locality_name }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDateTime(subMeeting.updated_at) }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <div class="flex items-center justify-end space-x-2">
                               <Link :href="route('meetings.show', { meeting: subMeeting.id })" class="flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 rounded transition-colors hover:bg-blue-50" title="Voir"><EyeIcon class="h-5 w-5" /></Link>
                               <button v-if="isSecretary && !isSubPrefect && (subMeeting.status === 'scheduled' || subMeeting.status === 'planned')" @click="cancelMeeting(subMeeting)" class="flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-900 rounded transition-colors hover:bg-red-50" title="Annuler"><XCircleIcon class="h-5 w-5" /></button>
                               <Link v-if="isSecretary && !isSubPrefect && (subMeeting.status === 'scheduled' || subMeeting.status === 'planned')" :href="route('meetings.reschedule', subMeeting.id)" class="flex items-center justify-center w-8 h-8 text-yellow-600 hover:text-yellow-900 rounded transition-colors hover:bg-yellow-50" title="Reporter"><ClockIcon class="h-5 w-5" /></Link>
                               <MeetingValidationButtons :meeting="subMeeting" />
                          </div>
                      </td>
                    </tr>
                  </template>
                </template>
              </tbody>
            </table>
          </div>
          
          <div class="mt-4">
            <Pagination :links="meetings.links" />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import MeetingStatusBadge from '@/Components/MeetingStatusBadge.vue';
import MeetingValidationButtons from '@/Components/MeetingValidationButtons.vue';
import AttendanceStatusBadge from '@/Components/AttendanceStatusBadge.vue';
import { PlusIcon, EyeIcon, XCircleIcon, ClockIcon, Squares2X2Icon } from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';
import { hasRole } from '@/Utils/authUtils';

interface Meeting {
  id: number;
  title: string;
  scheduled_date: string;
  status: string;
  locality_name: string;
  updated_at: string;
  parent_meeting_id?: number;
  sub_meetings_count?: number;
  sub_meetings?: Meeting[];
  attendance_status?: string;
  attendance_validated_at?: string;
}

interface LocalCommittee {
    id: number;
    name: string;
}

interface MeetingStatus {
  value: string;
  label: string;
}

interface Props {
  meetings: {
    data: Meeting[];
    links: any[];
  };
  filters: {
    search: string;
    status: string;
    local_committee_id: string;
    date_from: string;
    date_to: string;
    hierarchy?: string;
    sort?: string;
    direction?: string;
  };
  localCommittees: LocalCommittee[];
  meetingStatuses: MeetingStatus[];
  auth: {
    user: {
      roles: any[];
    }
  }
}

const props = defineProps<Props>();
const page = usePage();
const toast = useToast();

const filters = reactive({
  search: props.filters.search || '',
  status: props.filters.status || '',
  local_committee_id: props.filters.local_committee_id || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  hierarchy: props.filters.hierarchy || '',
});

const sortColumn = ref(props.filters.sort || 'scheduled_date');
const sortDirection = ref(props.filters.direction || 'desc');

const queryServer = (newSortColumn?: string) => {
    let newSort = sortColumn.value;
    let newDirection = sortDirection.value;

    if (newSortColumn) {
        if (sortColumn.value === newSortColumn) {
            newDirection = sortDirection.value === 'asc' ? 'desc' : 'asc';
        } else {
            newSort = newSortColumn;
            newDirection = 'asc';
        }
    }
    
    router.get(route('meetings.index'), {
        ...filters,
        sort: newSort,
        direction: newDirection,
    }, {
        preserveState: true,
        replace: true,
        onSuccess: () => {
            sortColumn.value = newSort;
            sortDirection.value = newDirection;
        },
    });
};

const applyFilters = () => queryServer();
const sortBy = (column: string) => queryServer(column);

const clearFilters = () => {
  filters.search = '';
  filters.status = '';
  filters.local_committee_id = '';
  filters.date_from = '';
  filters.date_to = '';
  filters.hierarchy = '';
  applyFilters();
};

const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatDateTime = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const cancelMeeting = (meeting: Meeting) => {
  if (confirm('Êtes-vous sûr de vouloir annuler cette réunion ?')) {
    router.post(route('meetings.cancel', meeting.id), {}, {
      onSuccess: () => toast.success('Réunion annulée avec succès.'),
      onError: () => toast.error('Erreur lors de l\'annulation de la réunion.'),
    });
  }
};

const isSecretary = computed(() => hasRole(props.auth.user.roles, 'secretaire') || hasRole(props.auth.user.roles, 'Secrétaire'));
const isSubPrefect = computed(() => hasRole(props.auth.user.roles, 'president') || hasRole(props.auth.user.roles, 'President'));

const expandedMeetings = ref<number[]>([]);

const getMeetingRowClass = (meeting: Meeting) => {
  if (meeting.parent_meeting_id) {
    return 'bg-gray-50'; // Sous-réunion
  } else if (meeting.sub_meetings_count && meeting.sub_meetings_count > 0) {
    return 'bg-blue-50'; // Réunion parent
  }
  return ''; // Réunion normale
};

const toggleSubMeetings = (meetingId: number) => {
  const index = expandedMeetings.value.indexOf(meetingId);
  if (index > -1) {
    expandedMeetings.value.splice(index, 1);
  } else {
    expandedMeetings.value.push(meetingId);
  }
};

const canSplitMeeting = (meeting: Meeting) => {
  // Peut éclater si c'est une réunion parent (pas une sous-réunion) et si le statut le permet
  return !meeting.parent_meeting_id && 
         (meeting.status === 'planned' || meeting.status === 'scheduled') &&
         isSecretary.value;
};

const splitMeeting = (meeting: Meeting) => {
  // Rediriger vers la page d'éclatement
  window.location.href = route('meetings.split', meeting.id);
};
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