<template>
  <Head title="Listes de Paiement" />

  <AppLayout title="Suivi des Listes de Paiement">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Messages flash -->
        <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ page.props.flash.error }}
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Suivi des Listes de Paiement</h2>
            
            <div class="flex space-x-2">
              <button
                @click="exportLists"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2 inline-block" />
                Exporter Tout
              </button>
              <button
                v-if="canValidateAll"
                @click="validateAll"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Valider Tous les Paiements
              </button>
            </div>
          </div>

          <!-- Filtres -->
          <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comité Local</label>
                <select v-model="filters.local_committee_id" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous les comités</option>
                  <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">
                    {{ committee.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Réunion</label>
                <select v-model="filters.meeting_id" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Toutes les réunions</option>
                  <option v-for="meeting in meetings" :key="meeting.id" :value="meeting.id">
                    {{ meeting.title }} - {{ formatDate(meeting.scheduled_date) }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select v-model="filters.status" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous les statuts</option>
                  <option value="draft">Brouillon</option>
                  <option value="submitted">Soumis</option>
                  <option value="validated">Validé</option>
                  <option value="rejected">Rejeté</option>
                </select>
              </div>
            </div>
            <div class="mt-4 flex space-x-2 justify-end">
              <button
                @click="applyFilters"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Appliquer les filtres
              </button>
              <button
                @click="clearFilters"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm font-medium"
              >
                Réinitialiser
              </button>
            </div>
          </div>

          <!-- Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soumis par</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="paymentLists.data.length === 0">
                  <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucune liste de paiement trouvée.</td>
                </tr>
                <tr v-for="list in paymentLists.data" :key="list.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ list.meeting.title }}</div>
                    <div class="text-sm text-gray-500">{{ formatDate(list.meeting.scheduled_date) }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ list.meeting.local_committee.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(list.total_amount) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(list.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getStatusText(list.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ list.submitter.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(list.submitted_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                     <div class="flex items-center justify-end space-x-2">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          v-if="item.payment_status === 'pending' && canValidateItems"
                          @click="validateItem(item)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Valider
                        </button>
                        <button
                          v-if="item.payment_status === 'validated' && canValidateItems"
                          @click="invalidateItem(item)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Invalider
                        </button>
                      </div>
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
            <template v-if="selectedList.status === 'submitted' && canValidateItems">
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
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import { ref, reactive, computed, watch } from 'vue'
import { 
  EyeIcon, 
  DocumentTextIcon, 
  CheckIcon, 
  XMarkIcon,
  ArrowDownTrayIcon,
  UserIcon
} from '@heroicons/vue/24/outline'
import { getStatusText, getStatusClass, translateRole } from '@/utils/translations'
import * as XLSX from 'xlsx'

const props = defineProps({
  paymentLists: Object,
  localCommittees: Array,
  meetings: Array,
  filters: Object,
  canValidateAll: Boolean,
})

const page = usePage()

const filters = reactive({
  local_committee_id: props.filters?.local_committee_id || '',
  meeting_id: props.filters?.meeting_id || '',
  status: props.filters?.status || '',
})

const selectedList = ref(null)
const showDetailsModal = ref(false)
const showConfirmModal = ref(false)
const selectedMeeting = ref(null)

const canValidate = computed(() => {
  console.log('Debug - canValidateAll:', props.canValidateAll)
  console.log('Debug - user roles:', page.props.auth.user?.roles)
  return props.canValidateAll
})

// Fonction pour vérifier les rôles de l'utilisateur
const userHasRole = (role) => {
  if (!page.props.auth.user || !page.props.auth.user.roles) return false
  return page.props.auth.user.roles.some(r => r.name.toLowerCase() === role.toLowerCase())
}

// Computed pour vérifier si l'utilisateur peut valider
const canValidateItems = computed(() => {
  const hasRole = userHasRole('gestionnaire') || userHasRole('admin')
  console.log('Debug - canValidateItems:', hasRole)
  return hasRole
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
  if (!filters.meeting_id) {
    alert('Veuillez sélectionner une réunion')
    return
  }
  
  selectedMeeting.value = props.meetings.find(m => m.id === parseInt(filters.meeting_id))
  showConfirmModal.value = true
}

const confirmValidateAll = () => {
  router.post(route('meeting-payments.lists.validate-all'), {
    meeting_id: filters.meeting_id
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
    const url = new URL(route('meeting-payments.lists.export'));
    url.search = new URLSearchParams(filters).toString();

    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de l\'export');
    }

    const result = await response.json();
    const lists = result.data;
    const grandTotal = result.total_amount;

    if (!lists || lists.length === 0) {
      alert('Aucune donnée à exporter pour les filtres sélectionnés.');
      return;
    }

    const finalSheetData = [];

    lists.forEach(list => {
      // En-tête pour cette liste
      finalSheetData.push(['Réunion', list['Réunion']]);
      finalSheetData.push(['Date', list['Date']]);
      finalSheetData.push(['Comité Local', list['Comité Local']]);
      finalSheetData.push(['Montant Total Liste', list['Montant Total']]);
      finalSheetData.push(['Statut Liste', list['Statut Liste']]);
      finalSheetData.push([]); // Ligne vide
      finalSheetData.push(['Nom', 'Rôle', 'Montant', 'Statut']); // En-têtes des participants

      // Lignes des participants
      if (list.Participants && Array.isArray(list.Participants)) {
        list.Participants.forEach(p => {
          finalSheetData.push([p.Nom, p.Rôle, p.Montant, p.Statut]);
        });
      }

      // Séparateur avant la prochaine liste
      finalSheetData.push([]);
      finalSheetData.push([]);
    });

    // Ajouter le total général à la fin
    finalSheetData.push(['', '', 'Total Général des listes filtrées:', grandTotal]);

    const worksheet = XLSX.utils.aoa_to_sheet(finalSheetData);

    worksheet['!cols'] = [
      { wch: 30 }, { wch: 25 }, { wch: 15 }, { wch: 15 }
    ];

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Listes de paiement');

    const fileName = `Export_Listes_Paiement_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(workbook, fileName);

    alert('Export des listes réussi !');

  } catch (error) {
    console.error("Erreur lors de l'export des listes:", error);
    alert("Une erreur est survenue : " + error.message);
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

const validateItem = (item) => {
  if (confirm('Êtes-vous sûr de vouloir valider ce paiement ?')) {
    router.post(route('meeting-payments.lists.validate-item', item.id))
  }
}

const invalidateItem = (item) => {
  if (confirm('Êtes-vous sûr de vouloir invalider ce paiement ?')) {
    router.post(route('meeting-payments.lists.invalidate-item', item.id))
  }
}

const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  return `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}`
}

const exportSingleList = async (meetingId) => {
  try {
    const response = await fetch(route('meeting-payments.lists.export-single', meetingId), {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de l\'export');
    }

    const result = await response.json();
    const data = result.data;
    
    // 1. Préparer les données de l'en-tête
    const headerData = [
      ['Réunion', data.Réunion],
      ['Date', data.Date],
      ['Comité Local', data['Comité Local']],
      ['Montant Total', data['Montant Total']],
      ['Statut Liste', data['Statut Liste']],
      [''], // Ligne vide
      ['Nom', 'Rôle', 'Montant', 'Statut']
    ];

    // 2. Créer la feuille de calcul à partir de l'en-tête
    const worksheet = XLSX.utils.aoa_to_sheet(headerData);

    // 3. Ajouter les données des participants à la suite de l'en-tête
    XLSX.utils.sheet_add_json(worksheet, data.Participants, {
      origin: 'A8', // Commencer après l'en-tête (A1 à A7)
      skipHeader: true // Ne pas ré-écrire les en-têtes (Nom, Rôle, etc.)
    });
    
    // 4. Ajuster la largeur des colonnes
    const columnWidths = [
      { wch: 30 }, // Nom
      { wch: 15 }, // Rôle
      { wch: 12 }, // Montant
      { wch: 12 }  // Statut
    ];
    worksheet['!cols'] = columnWidths;
    
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Liste de paiement');
    
    // Générer le nom du fichier
    const fileName = `liste_paiement_${data.Réunion.replace(/[^a-zA-Z0-9]/g, '_')}_${data.Date.replace(/\//g, '-')}.xlsx`;
    
    // Télécharger le fichier
    XLSX.writeFile(workbook, fileName);
    
    // Afficher un message de succès
    alert('Export réussi !');
  } catch (error) {
    alert('Erreur lors de l\'export : ' + error.message);
  }
}
</script> 