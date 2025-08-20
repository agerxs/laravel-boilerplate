<template>
  <Head title="Créer une liste de paiement" />

  <AppLayout title="Créer une liste de paiement">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Créer une liste de paiement
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-900">
                Réunion : {{ meeting.title }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Date : {{ formatDate(meeting.scheduled_date) }}
              </p>
            </div>

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
                      Montant
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="(attendee, index) in meeting.attendees" :key="attendee.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ attendee.name }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        {{ translateRole(attendee.role) }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="max-w-xs">
                        <TextInput
                          :value="15000"
                          disabled
                          type="number"
                          class="mt-1 block w-full bg-gray-50"
                        />
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
              <Link
                :href="route('meeting-payments.lists.index')"
                class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
              >
                Annuler
              </Link>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                :disabled="processing"
              >
                Créer la liste
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { ref, computed, onMounted } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

const props = defineProps({
  meeting: Object,
})

const processing = ref(false)

const form = useForm({
  attendees: {}
})

const attendeeAmounts = computed(() => {
  const amounts = {}
  props.meeting.attendees.forEach(attendee => {
    amounts[attendee.id] = form.attendees[attendee.id]?.amount || 0
  })
  return amounts
})

onMounted(() => {
  // Initialiser les montants pour chaque participant
  props.meeting.attendees.forEach(attendee => {
    form.attendees[attendee.id] = {
      id: attendee.id,
      amount: 15000,
      role: attendee.role,
    }
  })
})

function formatDate(date) {
  if (!date) return ''
  return format(new Date(date), 'dd MMMM yyyy', { locale: fr })
}

function translateRole(role) {
  const translations = {
    'prefet': 'Préfet',
    'sous_prefet': 'Président',
    'secretaire': 'Secrétaire',
            'membre': 'Membre'
  }
  return translations[role] || role
}

const submit = () => {
  processing.value = true
  
  form.post(route('meeting-payments.lists.store', props.meeting.id), {
    onSuccess: () => {
      processing.value = false
    },
    onError: () => {
      processing.value = false
    }
  })
}
</script> 