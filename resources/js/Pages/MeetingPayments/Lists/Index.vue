<template>
  <Head title="Listes de paiement" />

  <AppLayout title="Listes de paiement">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Listes de paiement
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="mb-6 flex justify-between items-center">
            <div class="flex space-x-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Réunion</label>
                <select v-model="filters.meeting_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                  <option value="">Toutes les réunions</option>
                  <option v-for="meeting in meetings" :key="meeting.id" :value="meeting.id">
                    {{ meeting.title }} - {{ formatDate(meeting.scheduled_date) }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Statut</label>
                <select v-model="filters.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                  <option value="">Tous les statuts</option>
                  <option value="draft">Brouillon</option>
                  <option value="submitted">Soumis</option>
                  <option value="validated">Validé</option>
                  <option value="rejected">Rejeté</option>
                </select>
              </div>
            </div>
            <div class="flex space-x-4">
              <button
                v-if="canValidateAll"
                @click="validateAll"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
              >
                Valider tous les paiements
              </button>
            </div>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Réunion
                    </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Comité local
                    </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Montant total
                    </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Statut
                    </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Soumis par
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="list in filteredLists" :key="list.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ list.meeting.title }}
                      </div>
                      <div class="text-sm text-gray-500">
                      {{ formatDate(list.meeting.scheduled_date) }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                      {{ list.meeting.local_committee.name }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                      {{ formatCurrency(list.total_amount) }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(list.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getStatusText(list.status) }}
                      </span>
                    </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ list.submitter.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ formatDate(list.submitted_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        v-if="canValidateAll && list.status === 'submitted'"
                        @click="validateList(list.id)"
                        class="text-green-600 hover:text-green-900"
                      >
                        Valider
                      </button>
                      <button
                        v-if="canValidateAll && list.status === 'submitted'"
                        @click="rejectList(list.id)"
                        class="text-red-600 hover:text-red-900"
                      >
                        Rejeter
                      </button>
                      <button
                        @click="showDetails(list)"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        Voir détails
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            <Pagination :links="paymentLists.links" />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de confirmation -->
    <Modal :show="showConfirmModal" @close="showConfirmModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Confirmer la validation
        </h2>

        <p class="mt-1 text-sm text-gray-600">
          Êtes-vous sûr de vouloir valider tous les paiements de la réunion "{{ selectedMeeting?.title }}" ?
        </p>

        <div class="mt-6 flex justify-end space-x-4">
          <button
            @click="showConfirmModal = false"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
          >
            Annuler
          </button>
          <button
            @click="confirmValidateAll"
            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
          >
            Confirmer
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal des détails -->
    <Modal :show="showDetailsModal" @close="closeDetails">
      <div class="p-6">
        <h2 class="text-lg font-medium mb-4">Détails de la liste de paiement</h2>
        <div v-if="selectedList" class="space-y-6">
          <!-- Informations de la réunion -->
          <div>
            <h3 class="font-medium mb-2">Informations de la réunion</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p><span class="font-medium">Titre :</span> {{ selectedList.meeting.title }}</p>
                <p><span class="font-medium">Date :</span> {{ formatDate(selectedList.meeting.scheduled_date) }}</p>
                <p><span class="font-medium">Lieu :</span> {{ selectedList.meeting.location }}</p>
              </div>
              <div>
                <p><span class="font-medium">Comité local :</span> {{ selectedList.meeting.local_committee.name }}</p>
                <p><span class="font-medium">Statut :</span> {{ getStatusText(selectedList.status) }}</p>
                <p><span class="font-medium">Montant total :</span> {{ formatCurrency(selectedList.total_amount) }}</p>
              </div>
            </div>
          </div>

          <!-- Liste des participants -->
          <div>
            <h3 class="font-medium mb-2">Participants</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in selectedList.payment_items" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap">{{ item.attendee.name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ translateRole(item.role) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(item.amount) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getPaymentStatusClass(item.payment_status)">
                        {{ getPaymentStatusText(item.payment_status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            </div>

          <!-- Actions -->
          <div class="flex justify-end space-x-4">
            <button @click="closeDetails" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
              Fermer
            </button>
            <template v-if="selectedList.status === 'draft'">
              <button @click="submitList" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Soumettre pour validation
              </button>
            </template>
            <template v-if="selectedList.status === 'submitted' && canValidate">
              <button @click="validateList" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Valider
              </button>
              <button @click="rejectList" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                Rejeter
              </button>
            </template>
          </div>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  paymentLists: {
    type: Object,
    required: true
  },
  meetings: {
    type: Array,
    required: true
  },
  canValidate: {
    type: Boolean,
    required: true
  }
})

const filters = ref({
  meeting_id: '',
  status: ''
})

const selectedList = ref(null)
const showDetailsModal = ref(false)
const showConfirmModal = ref(false)
const selectedMeeting = ref(null)

const canValidateAll = computed(() => {
  return props.canValidate
})

function formatDate(date) {
  if (!date) return ''
  return format(new Date(date), 'dd MMMM yyyy', { locale: fr })
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount)
}

function getStatusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    submitted: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getStatusText(status) {
  const texts = {
    draft: 'Brouillon',
    submitted: 'Soumis',
    validated: 'Validé',
    rejected: 'Rejeté',
  }
  return texts[status] || status
}

function getPaymentStatusClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function getPaymentStatusText(status) {
  const texts = {
    pending: 'En attente',
    validated: 'Validé',
    rejected: 'Rejeté'
  }
  return texts[status] || status
}

function translateRole(role) {
  const translations = {
    'prefet': 'Préfet',
    'sous_prefet': 'Sous-préfet',
    'secretaire': 'Secrétaire',
    'representant': 'Représentant'
  }
  return translations[role] || role
}

const validateList = (listId) => {
  router.post(route('meeting-payments.lists.validate', listId))
}

const rejectList = (listId) => {
  router.post(route('meeting-payments.lists.reject', listId))
}

const validateAll = () => {
  if (!filters.value.meeting_id) {
    alert('Veuillez sélectionner une réunion')
    return
  }
  
  selectedMeeting.value = props.meetings.find(m => m.id === parseInt(filters.value.meeting_id))
  showConfirmModal.value = true
}

const confirmValidateAll = () => {
  router.post(route('meeting-payments.lists.validate-all'), {
    meeting_id: filters.value.meeting_id
  })
  showConfirmModal.value = false
}

const filteredLists = computed(() => {
  return props.paymentLists.data.filter(list => {
    if (filters.value.meeting_id && list.meeting_id !== parseInt(filters.value.meeting_id)) {
      return false
    }
    if (filters.value.status && list.status !== filters.value.status) {
      return false
    }
    return true
  })
})

const showDetails = (list) => {
  selectedList.value = list
  showDetailsModal.value = true
}

const closeDetails = () => {
  showDetailsModal.value = false
  selectedList.value = null
}

const submitList = () => {
  if (selectedList.value) {
    router.post(route('meeting-payments.lists.submit', selectedList.value.id))
  }
}
</script> 