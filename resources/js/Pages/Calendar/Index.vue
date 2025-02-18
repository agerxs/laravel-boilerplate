<template>
  <AppLayout title="Agenda">
    <template #header>
      <div>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Agenda des réunions
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Visualisez et gérez vos réunions
        </p>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- Contrôles du calendrier -->
          <div class="flex justify-between items-center mb-6">
            
            <Link
              :href="route('meetings.create')"
              class="flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
            >
              <PlusIcon class="h-5 w-5 mr-2" />
              Nouvelle réunion
            </Link>
          </div>

          <!-- Calendrier -->
          <Calendar
            v-model="selectedDate"
            :attributes="attributes"
            :rows="1"
            :from-page="{ month: currentMonth, year: currentYear }"
            v-bind="calendarOptions"
            expanded
            :columns="1"
            @update:from-page="handlePageChange"
          >
            <template #day-content="{ day, attributes }">
              <div class="h-full">
                <span class="text-sm">{{ day.day }}</span>
                <div class="mt-1 space-y-1">
                  <div
                    v-for="attr in attributes"
                    :key="attr.key"
                    class="text-xs p-1 rounded-md cursor-pointer"
                    :class="attr.customData.classes"
                    @click="showMeetingDetails(attr.customData.meeting)"
                  >
                    {{ attr.customData.meeting.title }}
                    {{ formatTime(attr.customData.meeting.start_datetime) }}
                  </div>
                </div>
              </div>
            </template>
          </Calendar>
        </div>
      </div>
    </div>

    <!-- Modal des détails de la réunion -->
    <Modal :show="!!selectedMeeting" @close="selectedMeeting = null">
      <div class="p-6" v-if="selectedMeeting">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ selectedMeeting.title }}
        </h3>
        <div class="space-y-3">
          <p class="text-sm text-gray-600">
            <ClockIcon class="h-5 w-5 inline mr-2" />
            {{ formatDateTime(selectedMeeting.start_datetime) }} - 
            {{ formatDateTime(selectedMeeting.end_datetime) }}
          </p>
          <p class="text-sm text-gray-600">
            <MapPinIcon class="h-5 w-5 inline mr-2" />
            {{ selectedMeeting.location || 'Aucun lieu spécifié' }}
          </p>
          <p class="text-sm text-gray-600">
            {{ selectedMeeting.description || 'Aucune description' }}
          </p>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
          <Link
            :href="route('meetings.show', selectedMeeting.id)"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
          >
            Voir les détails
          </Link>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Calendar } from 'v-calendar'
import 'v-calendar/style.css'
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import {
  PlusIcon,
  ClockIcon,
  MapPinIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'

interface Meeting {
  id: number
  title: string
  description: string
  start_datetime: string
  end_datetime: string
  location: string
  status: string
}

interface Props {
  meetings: Meeting[]
}

const props = defineProps<Props>()
const view = ref<'month' | 'week'>('month')
const selectedDate = ref(new Date())
const selectedMeeting = ref<Meeting | null>(null)

const currentMonth = ref(new Date().getMonth() + 1)
const currentYear = ref(new Date().getFullYear())

// Fonction de navigation modifiée
function moveDate(amount: number) {
  const date = new Date(currentYear.value, currentMonth.value - 1)
  date.setMonth(date.getMonth() + amount)
  currentMonth.value = date.getMonth() + 1
  currentYear.value = date.getFullYear()
  selectedDate.value = date
}

// Gérer le changement de page du calendrier
function handlePageChange({ year, month }: { year: number, month: number }) {
  currentMonth.value = month
  currentYear.value = year
  selectedDate.value = new Date(year, month - 1)
}

// Initialiser v-calendar avec les options
const calendarOptions = {
  locale: 'fr-FR',
  firstDayOfWeek: 1,
  masks: {
    weekdays: 'WWW',
    title: 'MMMM YYYY',
  },
  navVisibility: 'hidden',
  transition: 'slide-h'
}

// Préparer les attributs pour le calendrier
const attributes = computed(() => {
  return props.meetings.map(meeting => ({
    dates: new Date(meeting.start_datetime),
    dot: {
      color: getStatusColor(meeting.status),
    },
    customData: {
      meeting,
      classes: `bg-${getStatusColor(meeting.status)}-100 text-${getStatusColor(meeting.status)}-800`
    },
    key: meeting.id
  }))
})

function getStatusColor(status: string) {
  switch (status) {
    case 'planned': return 'yellow'
    case 'ongoing': return 'blue'
    case 'completed': return 'green'
    case 'cancelled': return 'red'
    default: return 'gray'
  }
}

function setView(newView: 'month' | 'week') {
  view.value = newView
  // Réinitialiser la date sélectionnée au début de la semaine si on passe en vue semaine
  if (newView === 'week') {
    selectedDate.value = new Date(weekStart.value)
  }
}

function showMeetingDetails(meeting: Meeting) {
  selectedMeeting.value = meeting
}

function formatTime(datetime: string) {
  return new Date(datetime).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatDateTime(datetime: string) {
  return new Date(datetime).toLocaleString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function onDayClick(day: Date) {
  // Optionnel : rediriger vers la création de réunion avec la date pré-remplie
  // router.visit(route('meetings.create', { date: day.toISOString() }))
}

// Calculer le début et la fin de la semaine
const weekStart = computed(() => {
  const date = new Date(selectedDate.value)
  const day = date.getDay() || 7
  date.setDate(date.getDate() - day + 1)
  return date
})

const weekEnd = computed(() => {
  const date = new Date(weekStart.value)
  date.setDate(date.getDate() + 6)
  return date
})

function formatMonthYear(date: Date) {
  if (view.value === 'week') {
    return `Semaine du ${weekStart.value.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long'
    })} au ${weekEnd.value.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })}`
  }
  return date.toLocaleDateString('fr-FR', {
    month: 'long',
    year: 'numeric'
  })
}
</script>

<style>
/* Styles spécifiques pour v-calendar */
.vc-container {
  --vc-font-family: inherit;
  --vc-rounded-full: 9999px;
  --vc-font-bold: 600;
  --vc-bg-light: #fff;
  --vc-border-radius: 0.375rem;
  --vc-nav-hover-bg: #f3f4f6;
  --vc-nav-hover-text: #4f46e5;
  --vc-accent-600: #4f46e5;
  --vc-accent-500: #6366f1;
  --vc-accent-400: #818cf8;
  --vc-accent-100: #e0e7ff;
}

.vc-header {
  padding: 10px 0;
}

.vc-weeks {
  padding: 0;
}

.vc-day {
  min-height: 150px !important;
}

.vc-day-content {
  font-weight: 600;
  color: #374151;
  position: absolute;
  top: 4px;
  left: 4px;
}

.vc-day > div {
  min-height: 100%;
  padding-top: 24px; /* Espace pour le numéro du jour */
}

.vc-day:hover .vc-day-content {
  background-color: #f3f4f6;
}

.vc-highlight {
  background-color: var(--vc-accent-100);
}

/* Ajustements pour la vue mensuelle */
.vc-container {
  max-width: 100%;
  margin: 0 auto;
}

/* Ajustements pour la vue semaine */
.vc-container[data-view="week"] .vc-day {
  min-height: 150px;
}

.vc-container[data-view="month"] .vc-day {
  min-height: 120px;
}

/* Style pour la transition */
.slide-h-enter-active,
.slide-h-leave-active {
  transition: transform 0.3s ease;
}

.slide-h-enter-from {
  transform: translateX(100%);
}

.slide-h-leave-to {
  transform: translateX(-100%);
}
</style> 