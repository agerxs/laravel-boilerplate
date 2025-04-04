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
                <label class="block text-sm font-medium text-gray-700">Comité Local</label>
                <select v-model="filters.local_committee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                  <option value="">Tous les comités</option>
                  <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">
                    {{ committee.name }}
                  </option>
                </select>
              </div>
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
                @click="exportLists"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                Exporter les listes
              </button>
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soumis par</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center w-40">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="list in paymentLists.data" :key="list.id">
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
                    <span :class="getStatusClass(list.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getStatusText(list.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ list.submitter.name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500">{{ formatDate(list.submitted_at) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex items-center space-x-2 justify-end">
                      <div class="flex items-center space-x-1">
                        <button
                          v-if="canValidateAll && list.status === 'submitted'"
                          @click="validateList(list.id)"
                          class="p-1 text-green-600 hover:text-green-900 transition-colors duration-200 rounded-full hover:bg-green-100"
                          title="Valider"
                        >
                          <CheckIcon class="h-5 w-5" />
                        </button>
                        <button
                          v-if="canValidateAll && list.status === 'submitted'"
                          @click="rejectList(list.id)"
                          class="p-1 text-red-600 hover:text-red-900 transition-colors duration-200 rounded-full hover:bg-red-100"
                          title="Rejeter"
                        >
                          <XMarkIcon class="h-5 w-5" />
                        </button>
                      </div>
                      <div class="flex items-center space-x-1">
                        <Link
                          :href="route('meetings.show', list.meeting.id)"
                          class="p-1 text-blue-600 hover:text-blue-900 transition-colors duration-200 rounded-full hover:bg-blue-100"
                          title="Voir réunion"
                        >
                          <EyeIcon class="h-5 w-5" />
                        </Link>
                        <button
                          @click="showDetails(list)"
                          class="p-1 text-indigo-600 hover:text-indigo-900 transition-colors duration-200 rounded-full hover:bg-indigo-100"
                          title="Voir détails"
                        >
                          <DocumentTextIcon class="h-5 w-5" />
                        </button>
                      </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in selectedList.payment_items" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div v-if="item.attendee.presence_photo" class="relative">
                        <img 
                          :src="`/storage/${item.attendee.presence_photo}`" 
                          class="h-12 w-12 rounded-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                          @click="showPhotoDetails(item.attendee)"
                          :alt="`Photo de ${item.attendee.name}`"
                        />
                      </div>
                      <div v-else class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                        <UserIcon class="h-6 w-6 text-gray-400" />
                      </div>
                    </td>
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

    <!-- Modal pour afficher la photo en grand -->
    <Modal :show="showPhotoModal" @close="closePhotoModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Photo de présence</h3>
        <div v-if="selectedAttendee?.presence_photo" class="mb-4">
          <img 
            :src="`/storage/${selectedAttendee.presence_photo}`" 
            :alt="`Photo de ${selectedAttendee.name}`"
            class="w-full rounded-lg shadow-lg"
          />
          <div class="mt-2 text-sm text-gray-600">
            <p>Photo prise le {{ formatDate(selectedAttendee.presence_timestamp) }}</p>
            <p v-if="selectedAttendee.presence_location">
              Localisation : {{ formatLocation(selectedAttendee.presence_location) }}
            </p>
          </div>
        </div>
        <div class="mt-6 flex justify-end">
          <button
            type="button"
            @click="closePhotoModal"
            class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
          >
            Fermer
          </button>
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
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import ExcelJS from 'exceljs'
import axios from 'axios'
import { 
  CheckIcon, 
  XMarkIcon, 
  EyeIcon, 
  DocumentTextIcon,
  ArrowDownTrayIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import { getStatusText, getStatusClass, translateRole } from '@/Utils/translations'

const props = defineProps({
  paymentLists: {
    type: Object,
    required: true
  },
  meetings: {
    type: Array,
    required: true
  },
  localCommittees: {
    type: Array,
    required: true
  },
  canValidate: {
    type: Boolean,
    required: true
  }
})

const filters = ref({
  local_committee_id: '',
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

function getPaymentStatusClass(status) {
  return getStatusClass(status)
}

function getPaymentStatusText(status) {
  return getStatusText(status, 'payment')
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

// Surveiller les changements de filtres
watch(filters, (newFilters) => {
  router.get(route('meeting-payments.lists.index'), newFilters, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, { deep: true })

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

const exportLists = async () => {
  try {
    const response = await axios.get(route('meeting-payments.lists.export'), {
      params: filters.value
    })
    
    // Créer un fichier Excel avec les données
    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Listes de paiement')
    
    // Définir les colonnes
    worksheet.columns = [
      { header: 'Réunion', key: 'reunion', width: 30 },
      { header: 'Date', key: 'date', width: 15 },
      { header: 'Comité Local', key: 'comite', width: 25 },
      { header: 'Nom', key: 'nom', width: 25 },
      { header: 'Rôle', key: 'role', width: 15 },
      { header: 'Montant', key: 'montant', width: 15 },
      { header: 'Statut', key: 'statut', width: 15 }
    ]

    // Style des en-têtes
    worksheet.getRow(1).font = { bold: true }
    worksheet.getRow(1).fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FFE0E0E0' }
    }

    // Ajouter les données
    let rowCount = 1
    response.data.data.forEach(list => {
      if (list.Participants && Array.isArray(list.Participants)) {
        list.Participants.forEach(participant => {
          rowCount++
          worksheet.addRow({
            reunion: list.Réunion,
            date: list.Date,
            comite: list['Comité Local'],
            nom: participant.Nom,
            role: participant.Rôle,
            montant: participant.Montant,
            statut: participant.Statut
          })
        })
      }
    })

    // Formater la colonne des montants
    worksheet.getColumn('montant').numFmt = '#,##0 "FCFA"'

    // Ajouter une ligne pour le montant total
    rowCount += 2
    worksheet.addRow([])
    const totalRow = worksheet.addRow({
      reunion: 'TOTAL',
      montant: response.data.total_amount
    })
    totalRow.font = { bold: true }
    worksheet.getCell(`F${rowCount}`).numFmt = '#,##0 "FCFA"'

    // Ajouter des bordures
    for (let i = 1; i <= rowCount; i++) {
      worksheet.getRow(i).eachCell({ includeEmpty: true }, cell => {
        cell.border = {
          top: { style: 'thin' },
          left: { style: 'thin' },
          bottom: { style: 'thin' },
          right: { style: 'thin' }
        }
      })
    }
    
    // Générer le fichier
    const buffer = await workbook.xlsx.writeBuffer()
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `listes_paiement_${new Date().toISOString().split('T')[0]}.xlsx`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Erreur lors de l\'export:', error)
    alert('Une erreur est survenue lors de l\'export')
  }
}

// Ajout des refs pour le modal photo
const showPhotoModal = ref(false)
const selectedAttendee = ref(null)

// Fonctions pour gérer le modal photo
const showPhotoDetails = (attendee) => {
  selectedAttendee.value = attendee
  showPhotoModal.value = true
}

const closePhotoModal = () => {
  showPhotoModal.value = false
  selectedAttendee.value = null
}

const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  return `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`
}
</script> 