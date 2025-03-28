<template>
  <Head title="Créer une liste de paiement" />

  <AppLayout title="Créer une liste de paiement">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <!-- En-tête de la réunion -->
            <div class="mb-6">
              <h2 class="text-xl font-semibold text-gray-900">{{ meeting.title }}</h2>
              <p class="mt-1 text-sm text-gray-600">
                {{ formatDate(meeting.scheduled_date) }} - {{ meeting.localCommittee?.name }}
              </p>
            </div>

            <form @submit.prevent="submit">
              <!-- Liste des participants -->
              <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Participants à payer</h3>
                
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Nom
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Rôle
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Montant
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          À payer
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="attendee in meeting.attendees" :key="attendee.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm font-medium text-gray-900">
                            {{ attendee.name }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900">
                            {{ attendee.role }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <input
                            type="number"
                            v-model="form.attendees[attendee.id].amount"
                            class="mt-1 block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            :disabled="!form.attendees[attendee.id].selected"
                          >
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <input
                            type="checkbox"
                            v-model="form.attendees[attendee.id].selected"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                          >
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Total et actions -->
              <div class="mt-6 flex justify-between items-center">
                <div class="text-lg font-medium">
                  Total: {{ totalAmount }} FCFA
                </div>
                <div class="flex space-x-3">
                  <Link
                    :href="route('meetings.show', meeting.id)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                  >
                    Annuler
                  </Link>
                  <button
                    type="submit"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    :disabled="processing"
                  >
                    Créer la liste
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  meeting: Object,
})

const form = useForm({
  attendees: {}
})

const processing = ref(false)

// Initialiser le formulaire avec les participants
onMounted(() => {
  props.meeting.attendees.forEach(attendee => {
    form.attendees[attendee.id] = {
      id: attendee.id,
      selected: false,
      amount: 0,
      role: attendee.role
    }
  })
})

// Calculer le montant total
const totalAmount = computed(() => {
  return Object.values(form.attendees)
    .filter(a => a.selected)
    .reduce((sum, attendee) => sum + (parseFloat(attendee.amount) || 0), 0)
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const submit = () => {
  processing.value = true
  
  // Filtrer les participants sélectionnés
  const selectedAttendees = Object.values(form.attendees)
    .filter(a => a.selected)
    .map(({ id, amount, role }) => ({ id, amount, role }))

  form.post(route('meeting-payments.lists.store', props.meeting.id), {
    data: {
      attendees: selectedAttendees
    },
    onSuccess: () => {
      processing.value = false
    },
    onError: () => {
      processing.value = false
    }
  })
}
</script> 