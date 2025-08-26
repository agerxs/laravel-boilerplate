<template>
  <Head title="Listes de Paiement" />

  <AppLayout title="Suivi des Listes de Paiement">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        
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
              <!-- Bouton pour exporter les éléments sélectionnés -->
              <button
                v-if="selectedLists.length > 0"
                @click="exportSelectedLists"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2 inline-block" />
                Exporter Sélection ({{ selectedLists.length }})
              </button>
              
              <!-- Bouton pour marquer comme payé -->
              <button
                v-if="selectedLists.length > 0"
                @click="markSelectedAsPaid"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                <CheckIcon class="h-4 w-4 mr-2 inline-block" />
                Marquer comme Payé ({{ selectedLists.length }})
              </button>
              
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
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
              <div>
                <Select2Input
                  v-model="filters.local_committee_id"
                  :options="committeeOptions"
                  label="Comité Local"
                  placeholder="Rechercher un comité local..."
                  help-text="Sélectionnez un comité local pour filtrer les réunions"
                  :allowCustom="false"
                  :showCounts="false"
                />
              </div>
              <div>
                <Select2Input
                  v-model="filters.meeting_id"
                  :options="meetingOptions"
                  label="Réunion"
                  :placeholder="filters.local_committee_id ? 'Rechercher une réunion...' : 'Sélectionnez d\'abord un comité local'"
                  :help-text="filters.local_committee_id ? `${filteredMeetings.length} réunion(s) disponible(s)` : 'Sélectionnez d\'abord un comité local'"
                  :disabled="!filters.local_committee_id"
                  :allowCustom="false"
                  :showCounts="false"
                />
                <div v-if="filters.local_committee_id && filteredMeetings.length === 0" class="text-xs text-red-500 mt-1">
                  Aucune réunion trouvée pour ce comité local
                </div>
              </div>
              <div>
                <Select2Input
                  v-model="filters.status"
                  :options="statusOptions"
                  label="Statut"
                  placeholder="Sélectionner un statut..."
                  help-text="Filtrer par statut de la liste de paiement"
                  :allowCustom="false"
                  :showCounts="false"
                />
              </div>
              <div>
                <Select2Input
                  v-model="filters.export_status"
                  :options="exportStatusOptions"
                  label="Statut Export"
                  placeholder="Sélectionner un statut d'export..."
                  help-text="Filtrer par statut d'export de la liste"
                  :allowCustom="false"
                  :showCounts="false"
                />
              </div>
              <div>
                <div class="mb-1">
                  <label class="block text-sm font-medium text-gray-700">Recherche</label>
                </div>
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Rechercher par titre, comité, soumis par..."
                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
                <div class="text-xs text-gray-500 mt-1">
                  Recherche dans les titres, comités et noms
                </div>
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
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <input 
                      type="checkbox" 
                      :checked="isAllSelected" 
                      @change="toggleSelectAll"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Export</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Soumis par</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="paymentLists.data.length === 0">
                  <td colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucune liste de paiement trouvée.</td>
                </tr>
                <tr v-for="list in paymentLists.data" :key="list.id" :class="{'bg-blue-50': selectedLists.includes(list.id)}">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <input 
                      type="checkbox" 
                      :value="list.id"
                      v-model="selectedLists"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  </td>
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
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex flex-col space-y-1">
                      <span :class="getExportStatusClass(list.export_status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ getExportStatusText(list.export_status) }}
                      </span>
                      <div v-if="list.export_reference" class="text-xs text-gray-500">
                        Ref: {{ list.export_reference }}
                      </div>
                      <div v-if="list.exported_at" class="text-xs text-gray-500">
                        {{ formatDate(list.exported_at) }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ list.submitter.name }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(list.submitted_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                     <div class="flex items-center justify-end space-x-2">
                        <!-- Bouton d'export individuel -->
                        <button
                          v-if="list.export_status === 'not_exported'"
                          @click="exportSingleList(list.id)"
                          class="p-1 text-blue-600 hover:text-blue-900 transition-colors duration-200 rounded-full hover:bg-blue-100"
                          title="Exporter cette liste"
                        >
                          <ArrowDownTrayIcon class="h-5 w-5" />
                        </button>
                        
                        <!-- Bouton pour marquer comme payé -->
                        <button
                          v-if="list.export_status === 'exported' && canValidateItems"
                          @click="openJustificationModal(list)"
                          class="p-1 text-green-600 hover:text-green-900 transition-colors duration-200 rounded-full hover:bg-green-100"
                          title="Marquer comme payé avec justificatif"
                        >
                          <CheckIcon class="h-5 w-5" />
                        </button>
                        
                        <!-- Bouton pour gérer les justificatifs -->
                        <button
                          v-if="list.export_status === 'exported' || list.export_status === 'paid'"
                          @click="openJustificationModal(list)"
                          class="p-1 text-purple-600 hover:text-purple-900 transition-colors duration-200 rounded-full hover:bg-purple-100"
                          title="Gérer les justificatifs"
                        >
                          <DocumentIcon class="h-5 w-5" />
                        </button>
                        
                        <!-- Bouton pour consulter la liste des personnes à payer -->
                        <button
                          @click="openPaymentDetailsModal(list)"
                          class="p-1 text-blue-600 hover:text-blue-900 transition-colors duration-200 rounded-full hover:bg-blue-100"
                          title="Consulter la liste des personnes à payer"
                        >
                          <UserIcon class="h-5 w-5" />
                        </button>
                        
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
                        
                        <!-- Bouton pour consulter la liste des participants -->
                        <button
                          @click="showParticipantsList(list)"
                          class="p-1 text-orange-600 hover:text-orange-900 transition-colors duration-200 rounded-full hover:bg-orange-100"
                          title="Consulter la liste des participants"
                        >
                          <UsersIcon class="h-5 w-5" />
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

          <!-- Informations d'export -->
          <div v-if="selectedList.export_status !== 'not_exported'">
            <h3 class="font-medium mb-2">Informations d'export</h3>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <p><span class="font-medium">Statut export :</span> {{ getExportStatusText(selectedList.export_status) }}</p>
                <p><span class="font-medium">Référence :</span> {{ selectedList.export_reference || 'N/A' }}</p>
                <p><span class="font-medium">Exporté le :</span> {{ selectedList.exported_at ? formatDate(selectedList.exported_at) : 'N/A' }}</p>
              </div>
              <div>
                <p><span class="font-medium">Exporté par :</span> {{ selectedList.exporter?.name || 'N/A' }}</p>
                <p v-if="selectedList.paid_at"><span class="font-medium">Payé le :</span> {{ formatDate(selectedList.paid_at) }}</p>
                <p v-if="selectedList.paid_by"><span class="font-medium">Payé par :</span> {{ selectedList.payer?.name || 'N/A' }}</p>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Export</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in selectedList.payment_items" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div v-if="item.attendee && item.attendee.presence_photo" class="relative">
                        <img 
                          :src="`/storage/${item.attendee.presence_photo}`" 
                          class="h-12 w-12 rounded-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                          @click="item.attendee ? showPhotoDetails(item.attendee) : null"
                          :alt="`Photo de ${item.attendee.representative.name}`"
                        />
                      </div>
                      <div v-else class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                        <UserIcon class="h-6 w-6 text-gray-400" />
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ item.attendee ? item.attendee.representative.name : item.name || 'Nom non défini' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ translateRole(item.role) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(item.amount) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getPaymentStatusClass(item.payment_status)">
                        {{ getPaymentStatusText(item.payment_status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex flex-col space-y-1">
                        <span :class="getExportStatusClass(item.export_status || 'not_exported')" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                          {{ getExportStatusText(item.export_status || 'not_exported') }}
                        </span>
                        <div v-if="item.export_reference" class="text-xs text-gray-500">
                          Ref: {{ item.export_reference }}
                        </div>
                      </div>
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
            <!-- Bouton pour marquer comme payé -->
            <template v-if="selectedList.export_status === 'exported' && canValidateItems">
              <button @click="markListAsPaid(selectedList.id)" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                Marquer comme Payé
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

    <!-- Modal pour la gestion des pièces justificatives -->
    <Modal :show="showJustificationModal" @close="closeJustificationModal">
      <div class="p-6 max-w-4xl">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Gestion des Pièces Justificatives - {{ selectedListForJustification?.meeting?.title }}
        </h3>
        
        <!-- Formulaire d'ajout de justificatif -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <h4 class="font-medium mb-3">Ajouter une pièce justificative</h4>
          <form @submit.prevent="uploadJustification" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type de justificatif</label>
                <select v-model="newJustification.justification_type" class="w-full border-gray-300 rounded-md shadow-sm" required>
                  <option value="">Sélectionner un type</option>
                  <option value="receipt">Reçu</option>
                  <option value="quittance">Quittance</option>
                  <option value="transfer_proof">Preuve de virement</option>
                  <option value="bank_statement">Relevé bancaire</option>
                  <option value="mobile_money_proof">Preuve Mobile Money</option>
                  <option value="other">Autre</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fichier</label>
                <input 
                  type="file" 
                  @change="handleFileSelect" 
                  accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                  class="w-full border-gray-300 rounded-md shadow-sm"
                  required
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Référence du paiement</label>
                <input 
                  v-model="newJustification.reference_number" 
                  type="text" 
                  class="w-full border-gray-300 rounded-md shadow-sm"
                  placeholder="N° de référence"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Montant justifié</label>
                <input 
                  v-model="newJustification.amount" 
                  type="number" 
                  step="0.01"
                  class="w-full border-gray-300 rounded-md shadow-sm"
                  placeholder="Montant en FCFA"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date du paiement</label>
                <input 
                  v-model="newJustification.payment_date" 
                  type="date" 
                  class="w-full border-gray-300 rounded-md shadow-sm"
                />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea 
                v-model="newJustification.description" 
                rows="3"
                class="w-full border-gray-300 rounded-md shadow-sm"
                placeholder="Description du justificatif..."
              ></textarea>
            </div>
            
            <div class="flex justify-end">
              <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                :disabled="uploading"
              >
                <span v-if="uploading">Upload en cours...</span>
                <span v-else>Ajouter le justificatif</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Liste des justificatifs existants -->
        <div class="mb-6">
          <h4 class="font-medium mb-3">Justificatifs existants</h4>
          <div v-if="justifications.length === 0" class="text-gray-500 text-center py-4">
            Aucun justificatif ajouté pour le moment.
          </div>
          <div v-else class="space-y-3">
            <div 
              v-for="justification in justifications" 
              :key="justification.id"
              class="flex items-center justify-between p-3 bg-white border rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <DocumentIcon v-if="justification.file_type === 'pdf'" class="h-8 w-8 text-red-500" />
                  <img v-else-if="justification.is_image" :src="justification.public_url" class="h-8 w-8 object-cover rounded" />
                  <DocumentIcon v-else class="h-8 w-8 text-gray-400" />
                </div>
                <div>
                  <p class="font-medium">{{ justification.file_name }}</p>
                  <p class="text-sm text-gray-600">
                    {{ justification.justification_type_text }} - 
                    {{ justification.formatted_file_size }} - 
                    Ajouté le {{ formatDate(justification.created_at) }}
                  </p>
                  <p v-if="justification.description" class="text-sm text-gray-500">
                    {{ justification.description }}
                  </p>
                </div>
              </div>
              <div class="flex items-center space-x-2">
                <button
                  @click="downloadJustification(justification)"
                  class="p-1 text-blue-600 hover:text-blue-900"
                  title="Télécharger"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                </button>
                <button
                  @click="deleteJustification(justification)"
                  class="p-1 text-red-600 hover:text-red-900"
                  title="Supprimer"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-between">
          <button @click="closeJustificationModal" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
            Fermer
          </button>
          
          <div class="flex space-x-3">
            <button 
              v-if="selectedListForJustification?.export_status === 'exported' && canValidateItems && justifications.length > 0"
              @click="markListAsPaid(selectedListForJustification.id)"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            >
              Marquer comme Payé
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Modal pour la liste des participants -->
    <Modal :show="showParticipantsModal" @close="closeParticipantsModal">
      <div class="p-6 max-w-6xl">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Liste des Participants - {{ selectedListForParticipants?.meeting?.title }}
        </h3>
        
        <!-- Informations de la réunion -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p><span class="font-medium">Date :</span> {{ formatDate(selectedListForParticipants?.meeting?.scheduled_date) }}</p>
              <p><span class="font-medium">Lieu :</span> {{ selectedListForParticipants?.meeting?.location || 'Non spécifié' }}</p>
            </div>
            <div>
              <p><span class="font-medium">Comité local :</span> {{ selectedListForParticipants?.meeting?.local_committee?.name }}</p>
              <p><span class="font-medium">Statut :</span> {{ getStatusText(selectedListForParticipants?.status) }}</p>
            </div>
            <div>
              <p><span class="font-medium">Montant total :</span> {{ formatCurrency(selectedListForParticipants?.total_amount || 0) }}</p>
              <p><span class="font-medium">Participants :</span> {{ participantsList.length }}</p>
            </div>
          </div>
        </div>

        <!-- Filtres -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <h4 class="font-medium mb-3">Filtres</h4>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut de présence</label>
              <select v-model="participantFilters.attendance_status" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Tous les statuts</option>
                <option value="present">Présent</option>
                <option value="absent">Absent</option>
                <option value="replaced">Remplacé</option>
                <option value="excused">Excusé</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
              <select v-model="participantFilters.role" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Tous les rôles</option>
                <option value="secretaire">Secrétaire</option>
                <option value="president">Président</option>
                <option value="participant">Participant</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Statut de paiement</label>
              <select v-model="participantFilters.payment_status" class="w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Tous les statuts</option>
                <option value="pending">En attente</option>
                <option value="validated">Validé</option>
                <option value="paid">Payé</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
              <input 
                v-model="participantFilters.search" 
                type="text" 
                class="w-full border-gray-300 rounded-md shadow-sm"
                placeholder="Nom, téléphone..."
              />
            </div>
          </div>
        </div>

        <!-- Table des participants -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Présence</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut Paiement</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="filteredParticipants.length === 0">
                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                  Aucun participant trouvé avec les filtres sélectionnés.
                </td>
              </tr>
              <tr v-for="participant in filteredParticipants" :key="participant.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="participant.presence_photo" class="relative">
                    <img 
                      :src="`/storage/${participant.presence_photo}`" 
                      class="h-12 w-12 rounded-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                      @click="showPhotoDetails(participant)"
                      :alt="`Photo de ${participant.representative?.name || 'Participant'}`"
                    />
                  </div>
                  <div v-else class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                    <UserIcon class="h-6 w-6 text-gray-400" />
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ participant.representative?.name || 'Nom non défini' }}
                  </div>
                  <div v-if="participant.representative?.email" class="text-sm text-gray-500">
                    {{ participant.representative.email }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ participant.representative?.phone || participant.phone || 'Non renseigné' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ translateRole(participant.role) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getAttendanceStatusClass(participant.attendance_status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ getAttendanceStatusText(participant.attendance_status) }}
                  </span>
                  <div v-if="participant.attendance_time" class="text-xs text-gray-500 mt-1">
                    {{ formatTime(participant.attendance_time) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ formatCurrency(participant.payment_item?.amount || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getPaymentStatusClass(participant.payment_item?.payment_status || 'pending')" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ getPaymentStatusText(participant.payment_item?.payment_status || 'pending') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <button
                      v-if="participant.presence_photo"
                      @click="showPhotoDetails(participant)"
                      class="text-blue-600 hover:text-blue-900"
                      title="Voir la photo"
                    >
                      <EyeIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-if="canValidateItems && participant.payment_item?.payment_status === 'pending'"
                      @click="validateParticipantPayment(participant)"
                      class="text-green-600 hover:text-green-900"
                      title="Valider le paiement"
                    >
                      <CheckIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Résumé -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
          <h4 class="font-medium mb-3">Résumé</h4>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
            <div>
              <p><span class="font-medium">Total participants :</span> {{ participantsList.length }}</p>
              <p><span class="font-medium">Présents :</span> {{ attendanceSummary.present }}</p>
            </div>
            <div>
              <p><span class="font-medium">Absents :</span> {{ attendanceSummary.absent }}</p>
              <p><span class="font-medium">Remplacés :</span> {{ attendanceSummary.replaced }}</p>
            </div>
            <div>
              <p><span class="font-medium">Montant total :</span> {{ formatCurrency(attendanceSummary.totalAmount) }}</p>
              <p><span class="font-medium">Paiements validés :</span> {{ attendanceSummary.validatedPayments }}</p>
            </div>
            <div>
              <p><span class="font-medium">Paiements payés :</span> {{ attendanceSummary.paidPayments }}</p>
              <p><span class="font-medium">En attente :</span> {{ attendanceSummary.pendingPayments }}</p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end">
          <button @click="closeParticipantsModal" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
            Fermer
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal pour afficher les détails de la liste de paiement -->
    <Modal :show="showPaymentDetailsModal" @close="closePaymentDetailsModal">
      <div class="p-6 max-w-6xl">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Liste des Personnes à Payer - {{ selectedPaymentList?.meeting?.title }}
        </h3>
        
        <!-- Informations générales de la liste -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <span class="text-sm font-medium text-gray-700">Statut d'export :</span>
              <span :class="getExportStatusClass(selectedPaymentList?.export_status)" class="ml-2 px-2 py-1 text-xs rounded-full">
                {{ getExportStatusText(selectedPaymentList?.export_status) }}
              </span>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-700">Nombre de participants :</span>
              <span class="ml-2 text-gray-900">{{ paymentDetails?.length || 0 }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-700">Montant total :</span>
              <span class="ml-2 text-gray-900 font-semibold">{{ formatCurrency(calculateTotalAmount()) }} FCFA</span>
            </div>
          </div>
        </div>

        <!-- Filtres -->
        <div class="mb-4 flex flex-wrap gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut de présence</label>
            <select v-model="presenceFilter" class="border-gray-300 rounded-md shadow-sm">
              <option value="">Tous</option>
              <option value="present">Présent</option>
              <option value="absent">Absent</option>
              <option value="replaced">Remplacé</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Statut de paiement</label>
            <select v-model="paymentFilter" class="border-gray-300 rounded-md shadow-sm">
              <option value="">Tous</option>
              <option value="pending">En attente</option>
              <option value="processing">En cours</option>
              <option value="paid">Payé</option>
              <option value="cancelled">Annulé</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
            <input 
              v-model="searchTerm" 
              type="text" 
              placeholder="Nom, village, rôle..."
              class="border-gray-300 rounded-md shadow-sm"
            />
          </div>
        </div>

        <!-- Tableau des participants -->
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Participant
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Village
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rôle
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Statut de présence
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Montant
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Statut de paiement
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="participant in filteredPaymentDetails" :key="participant.id" class="hover:bg-gray-50">
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10">
                      <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <UserIcon class="h-5 w-5 text-blue-600" />
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        {{ participant.representative?.name || participant.name || 'N/A' }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ participant.phone || participant.representative?.phone || 'N/A' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ participant.locality?.name || 'N/A' }}
                </td>
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ participant.representative?.role || participant.replacement_role || 'N/A' }}
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <span :class="getPresenceStatusClass(participant.attendance_status)" class="px-2 py-1 text-xs rounded-full">
                    {{ getPresenceStatusText(participant.attendance_status) }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                  {{ formatCurrency(participant.payment_item?.amount || 0) }} FCFA
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <span :class="getPaymentStatusClass(participant.payment_item?.payment_status)" class="px-2 py-1 text-xs rounded-full">
                    {{ getPaymentStatusText(participant.payment_item?.payment_status) }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="flex space-x-2">
                    <button
                      v-if="participant.presence_photo"
                      @click="showParticipantPhoto(participant)"
                      class="text-blue-600 hover:text-blue-900"
                      title="Voir la photo de présence"
                    >
                      <EyeIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-if="participant.comments"
                      @click="showParticipantComments(participant)"
                      class="text-green-600 hover:text-green-900"
                      title="Voir les commentaires"
                    >
                      <DocumentTextIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Résumé des statistiques -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg">
          <h4 class="text-sm font-medium text-blue-900 mb-3">Résumé des statistiques</h4>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
              <span class="text-blue-700">Présents :</span>
              <span class="ml-2 font-medium text-blue-900">{{ getPresenceCount('present') }}</span>
            </div>
            <div>
              <span class="text-blue-700">Absents :</span>
              <span class="ml-2 font-medium text-blue-900">{{ getPresenceCount('absent') }}</span>
            </div>
            <div>
              <span class="text-sm font-medium text-blue-900">{{ getPresenceCount('replaced') }}</span>
            </div>
            <div>
              <span class="text-blue-700">Total :</span>
              <span class="ml-2 font-medium text-blue-900">{{ paymentDetails?.length || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-6 flex justify-end">
          <button @click="closePaymentDetailsModal" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">
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
  UserIcon,
  DocumentIcon,
  UsersIcon
} from '@heroicons/vue/24/outline'
import Select2Input from '@/Components/Select2Input.vue'
import { getStatusText, getStatusClass, translateRole } from '@/Utils/translations'
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
  export_status: props.filters?.export_status || '',
  search: props.filters?.search || '',
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
  const hasRole = userHasRole('tresorier') || userHasRole('admin')
  console.log('Debug - canValidateItems:', hasRole)
  return hasRole
})

// Computed pour filtrer les réunions en fonction du comité local sélectionné
const filteredMeetings = computed(() => {
  if (!filters.local_committee_id) {
    return props.meetings
  }
  
  return props.meetings.filter(meeting => 
    meeting.local_committee_id === parseInt(filters.local_committee_id)
  )
})

// Options pour les filtres Select2Input
const committeeOptions = computed(() => {
  return [
    { value: '', label: 'Tous les comités' },
    ...props.localCommittees.map(committee => ({
      value: committee.id.toString(),
      label: committee.name
    }))
  ]
})

const meetingOptions = computed(() => {
  if (!filters.local_committee_id) {
    return [{ value: '', label: 'Sélectionnez d\'abord un comité local' }]
  }
  
  return [
    { value: '', label: 'Toutes les réunions' },
    ...filteredMeetings.value.map(meeting => ({
      value: meeting.id.toString(),
      label: `${meeting.title}`,
      subtitle: formatDate(meeting.scheduled_date),
      location: meeting.location || 'Lieu non défini'
    }))
  ]
})

const statusOptions = [
  { value: '', label: 'Tous les statuts' },
  { value: 'draft', label: 'Brouillon' },
  { value: 'submitted', label: 'Soumis' },
  { value: 'validated', label: 'Validé' },
  { value: 'rejected', label: 'Rejeté' }
]

const exportStatusOptions = [
  { value: '', label: 'Tous les exports' },
  { value: 'not_exported', label: 'Non exporté' },
  { value: 'exported', label: 'Exporté' },
  { value: 'paid', label: 'Payé' }
]

// Computed pour filtrer les détails de paiement
const filteredPaymentDetails = computed(() => {
  let filtered = paymentDetails.value

  // Filtre par statut de présence
  if (presenceFilter.value) {
    filtered = filtered.filter(p => p.attendance_status === presenceFilter.value)
  }

  // Filtre par statut de paiement
  if (paymentFilter.value) {
    filtered = filtered.filter(p => p.payment_item?.payment_status === paymentFilter.value)
  }

  // Filtre par recherche
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(p => {
      const name = (p.representative?.name || p.name || '').toLowerCase()
      const village = (p.locality?.name || '').toLowerCase()
      const role = (p.role || '').toLowerCase()
      return name.includes(term) || village.includes(term) || role.includes(term)
    })
  }

  return filtered
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
watch(filters, (newFilters, oldFilters) => {
  // Si le comité local change, réinitialiser la sélection de réunion
  if (oldFilters && oldFilters.local_committee_id !== newFilters.local_committee_id) {
    filters.meeting_id = ''
  }
  
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

// Fonction pour réinitialiser les filtres
const clearFilters = () => {
  filters.local_committee_id = ''
  filters.meeting_id = ''
  filters.status = ''
  filters.export_status = ''
  filters.search = ''
}

// Fonction pour appliquer les filtres manuellement
const applyFilters = () => {
  // Les filtres sont appliqués automatiquement par le watch
  // Cette fonction peut être utilisée pour des actions supplémentaires si nécessaire
  console.log('Filtres appliqués:', filters)
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
    const mobileMoneyData = result.data;
    const grandTotal = result.total_amount;

    if (!mobileMoneyData || mobileMoneyData.length === 0) {
      alert('Aucune donnée à exporter pour les filtres sélectionnés.');
      return;
    }

    // Préparer les données pour l'export mobile money
    const finalSheetData = [
      ['Référence', 'Montant (FCFA)', 'Nom du Destinataire', 'Commentaire', 'Type d\'opération']
    ];

    // Ajouter les données des transferts
    mobileMoneyData.forEach(item => {
      finalSheetData.push([
        item['Référence'],
        item['Montant'],
        item['Nom du Destinataire'],
        item['Commentaire'],
        item['Type d\'opération']
      ]);
    });



    const worksheet = XLSX.utils.aoa_to_sheet(finalSheetData);

    // Ajuster la largeur des colonnes
    worksheet['!cols'] = [
      { wch: 20 }, // Référence
      { wch: 15 }, // Montant
      { wch: 30 }, // Nom du Destinataire
      { wch: 40 }, // Commentaire
      { wch: 20 }  // Type d'opération
    ];

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Transferts Mobile Money');

    const fileName = `Transferts_Mobile_Money_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(workbook, fileName);

    alert(`Export réussi ! ${mobileMoneyData.length} transferts générés pour un total de ${grandTotal} FCFA`);

  } catch (error) {
    console.error("Erreur lors de l'export des listes:", error);
    alert("Une erreur est survenue : " + error.message);
  }
}

// Ajout des refs pour le modal photo
const showPhotoModal = ref(false)
const selectedAttendee = ref(null)

// Variables pour la gestion des justificatifs
const showJustificationModal = ref(false)
const selectedListForJustification = ref(null)
const justifications = ref([])
const uploading = ref(false)
const newJustification = reactive({
  justification_type: '',
  description: '',
  reference_number: '',
  amount: '',
  payment_date: ''
})
const selectedFile = ref(null)

// Variables pour la gestion des détails de paiement
const showPaymentDetailsModal = ref(false)
const selectedPaymentList = ref(null)
const paymentDetails = ref([])
const presenceFilter = ref('')
const paymentFilter = ref('')
const searchTerm = ref('')

// Variables pour la gestion des participants
const showParticipantsModal = ref(false)
const selectedListForParticipants = ref(null)
const participantsList = ref([])
const participantFilters = reactive({
  attendance_status: '',
  role: '',
  payment_status: '',
  search: ''
})

// Fonctions pour gérer le modal photo
const showPhotoDetails = (attendee) => {
  if (attendee) {
    selectedAttendee.value = attendee
    showPhotoModal.value = true
  }
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

// Fonctions pour gérer les justificatifs
const openJustificationModal = (list) => {
  selectedListForJustification.value = list
  showJustificationModal.value = true
  loadJustifications(list.id)
}

const closeJustificationModal = () => {
  showJustificationModal.value = false
  selectedListForJustification.value = null
  justifications.value = []
  resetJustificationForm()
}

const resetJustificationForm = () => {
  newJustification.justification_type = ''
  newJustification.description = ''
  newJustification.reference_number = ''
  newJustification.amount = ''
  newJustification.payment_date = ''
  selectedFile.value = null
}

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0]
}

const loadJustifications = async (listId) => {
  try {
    const response = await fetch(route('meeting-payments.justifications.index', listId))
    if (response.ok) {
      const data = await response.json()
      justifications.value = data.justifications
    }
  } catch (error) {
    console.error('Erreur lors du chargement des justificatifs:', error)
  }
}

const uploadJustification = async () => {
  if (!selectedFile.value) {
    alert('Veuillez sélectionner un fichier')
    return
  }

  uploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', selectedFile.value)
    formData.append('justification_type', newJustification.justification_type)
    formData.append('description', newJustification.description)
    formData.append('reference_number', newJustification.reference_number)
    formData.append('amount', newJustification.amount)
    formData.append('payment_date', newJustification.payment_date)

    const response = await fetch(route('meeting-payments.justifications.store', selectedListForJustification.value.id), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      const data = await response.json()
      alert('Justificatif ajouté avec succès')
      resetJustificationForm()
      loadJustifications(selectedListForJustification.value.id)
    } else {
      const errorData = await response.json()
      alert('Erreur lors de l\'ajout: ' + errorData.message)
    }
  } catch (error) {
    console.error('Erreur lors de l\'upload:', error)
    alert('Erreur lors de l\'upload: ' + error.message)
  } finally {
    uploading.value = false
  }
}

const downloadJustification = async (justification) => {
  try {
    const response = await fetch(route('meeting-payments.justifications.download', [selectedListForJustification.value.id, justification.id]))
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = justification.file_name
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    }
  } catch (error) {
    console.error('Erreur lors du téléchargement:', error)
    alert('Erreur lors du téléchargement')
  }
}

const deleteJustification = async (justification) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce justificatif ?')) {
    return
  }

  try {
    const response = await fetch(route('meeting-payments.justifications.destroy', [selectedListForJustification.value.id, justification.id]), {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      alert('Justificatif supprimé avec succès')
      loadJustifications(selectedListForJustification.value.id)
    } else {
      const errorData = await response.json()
      alert('Erreur lors de la suppression: ' + errorData.message)
    }
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression')
  }
}

// Fonctions pour gérer les détails de paiement
const openPaymentDetailsModal = (list) => {
  selectedPaymentList.value = list
  showPaymentDetailsModal.value = true
  loadPaymentDetails(list.id)
}

const closePaymentDetailsModal = () => {
  showPaymentDetailsModal.value = false
  selectedPaymentList.value = null
  paymentDetails.value = []
  presenceFilter.value = ''
  paymentFilter.value = ''
  searchTerm.value = ''
}

const loadPaymentDetails = async (listId) => {
  try {
    const response = await fetch(route('meeting-payments.lists.participants', listId))
    if (response.ok) {
      const data = await response.json()
      paymentDetails.value = data.participants || []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des détails:', error)
  }
}

const calculateTotalAmount = () => {
  return paymentDetails.value.reduce((total, participant) => total + (participant.payment_item?.amount || 0), 0)
}

const getPresenceCount = (status) => {
  return paymentDetails.value.filter(p => p.attendance_status === status).length
}

const getPresenceStatusClass = (status) => {
  switch (status) {
    case 'present': return 'bg-green-100 text-green-800'
    case 'absent': return 'bg-red-100 text-red-800'
    case 'replaced': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const getPresenceStatusText = (status) => {
  switch (status) {
    case 'present': return 'Présent'
    case 'absent': return 'Absent'
    case 'replaced': return 'Remplacé'
    default: return 'Inconnu'
  }
}

const showParticipantPhoto = (participant) => {
  selectedAttendee.value = participant
  showPhotoModal.value = true
}

const showParticipantComments = (participant) => {
  alert(`Commentaires pour ${participant.representative?.name || participant.name}:\n\n${participant.comments}`)
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
    const mobileMoneyData = result.data;
    
    if (!mobileMoneyData || mobileMoneyData.length === 0) {
      alert('Aucune donnée à exporter pour cette réunion.');
      return;
    }

    // Préparer les données pour l'export mobile money
    const finalSheetData = [
      ['Référence', 'Montant (FCFA)', 'Nom du Destinataire', 'Commentaire', 'Type d\'opération']
    ];

    // Ajouter les données des transferts
    mobileMoneyData.forEach(item => {
      finalSheetData.push([
        item['Référence'],
        item['Montant'],
        item['Nom du Destinataire'],
        item['Commentaire'],
        item['Type d\'opération']
      ]);
    });


    
    const worksheet = XLSX.utils.aoa_to_sheet(finalSheetData);

    // Ajuster la largeur des colonnes
    worksheet['!cols'] = [
      { wch: 20 }, // Référence
      { wch: 15 }, // Montant
      { wch: 30 }, // Nom du Destinataire
      { wch: 40 }, // Commentaire
      { wch: 20 }  // Type d'opération
    ];
    
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Transferts Mobile Money');
    
    // Générer le nom du fichier
    const fileName = `Transferts_Mobile_Money_${result.meeting_title.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().split('T')[0]}.xlsx`;
    
    // Télécharger le fichier
    XLSX.writeFile(workbook, fileName);
    
    // Afficher un message de succès
    alert(`Export réussi ! ${mobileMoneyData.length} transferts générés pour un total de ${result.total_amount} FCFA`);
  } catch (error) {
    alert('Erreur lors de l\'export : ' + error.message);
  }
}

// Fonctions pour gérer la liste des participants
const showParticipantsList = async (list) => {
  selectedListForParticipants.value = list
  showParticipantsModal.value = true
  await loadParticipantsList(list.id)
}

const closeParticipantsModal = () => {
  showParticipantsModal.value = false
  selectedListForParticipants.value = null
  participantsList.value = []
  resetParticipantFilters()
}

const resetParticipantFilters = () => {
  participantFilters.attendance_status = ''
  participantFilters.role = ''
  participantFilters.payment_status = ''
  participantFilters.search = ''
}

const loadParticipantsList = async (listId) => {
  try {
    const response = await fetch(route('meeting-payments.lists.participants', listId))
    if (response.ok) {
      const data = await response.json()
      participantsList.value = data.participants || []
    } else {
      console.error('Erreur lors du chargement des participants')
      participantsList.value = []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des participants:', error)
    participantsList.value = []
  }
}

const validateParticipantPayment = async (participant) => {
  if (!confirm(`Êtes-vous sûr de vouloir valider le paiement de ${participant.representative?.name || 'ce participant'} ?`)) {
    return
  }

  try {
    const response = await fetch(route('meeting-payments.lists.validate-item', [selectedListForParticipants.value.id, participant.payment_item.id]), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })

    if (response.ok) {
      alert('Paiement validé avec succès')
      await loadParticipantsList(selectedListForParticipants.value.id)
    } else {
      const errorData = await response.json()
      alert('Erreur lors de la validation: ' + errorData.message)
    }
  } catch (error) {
    console.error('Erreur lors de la validation:', error)
    alert('Erreur lors de la validation')
  }
}

// Computed properties pour les participants
const filteredParticipants = computed(() => {
  let filtered = participantsList.value

  if (participantFilters.attendance_status) {
    filtered = filtered.filter(p => p.attendance_status === participantFilters.attendance_status)
  }

  if (participantFilters.role) {
    filtered = filtered.filter(p => p.role === participantFilters.role)
  }

  if (participantFilters.payment_status) {
    filtered = filtered.filter(p => p.payment_item?.payment_status === participantFilters.payment_status)
  }

  if (participantFilters.search) {
    const search = participantFilters.search.toLowerCase()
    filtered = filtered.filter(p => 
      (p.representative?.name && p.representative.name.toLowerCase().includes(search)) ||
      (p.representative?.phone && p.representative.phone.includes(search)) ||
      (p.phone && p.phone.includes(search))
    )
  }

  return filtered
})

const attendanceSummary = computed(() => {
  const participants = participantsList.value
  
  return {
    present: participants.filter(p => p.attendance_status === 'present').length,
    absent: participants.filter(p => p.attendance_status === 'absent').length,
    replaced: participants.filter(p => p.attendance_status === 'replaced').length,
    excused: participants.filter(p => p.attendance_status === 'excused').length,
    totalAmount: participants.reduce((sum, p) => sum + (p.payment_item?.amount || 0), 0),
    validatedPayments: participants.filter(p => p.payment_item?.payment_status === 'validated').length,
    paidPayments: participants.filter(p => p.payment_item?.payment_status === 'paid').length,
    pendingPayments: participants.filter(p => p.payment_item?.payment_status === 'pending').length
  }
})

// Fonctions pour gérer la sélection multiple
const selectedLists = ref([])

const isAllSelected = computed(() => {
  return selectedLists.value.length === props.paymentLists.data.length && props.paymentLists.data.length > 0
})

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedLists.value = []
  } else {
    selectedLists.value = props.paymentLists.data.map(list => list.id)
  }
}

const exportSelectedLists = async () => {
  if (selectedLists.value.length === 0) {
    alert('Veuillez sélectionner au moins une liste pour l\'export.');
    return;
  }

  try {
    const url = new URL(route('meeting-payments.lists.export-multiple'));
    url.search = new URLSearchParams({ ids: selectedLists.value }).toString();

    const response = await fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de l\'export des sélections.');
    }

    const result = await response.json();
    const mobileMoneyData = result.data;
    const grandTotal = result.total_amount;

    if (!mobileMoneyData || mobileMoneyData.length === 0) {
      alert('Aucune donnée à exporter pour les listes sélectionnées.');
      return;
    }

    // Préparer les données pour l'export mobile money
    const finalSheetData = [
      ['Référence', 'Montant (FCFA)', 'Nom du Destinataire', 'Commentaire', 'Type d\'opération']
    ];

    // Ajouter les données des transferts
    mobileMoneyData.forEach(item => {
      finalSheetData.push([
        item['Référence'],
        item['Montant'],
        item['Nom du Destinataire'],
        item['Commentaire'],
        item['Type d\'opération']
      ]);
    });


    const worksheet = XLSX.utils.aoa_to_sheet(finalSheetData);

    // Ajuster la largeur des colonnes
    worksheet['!cols'] = [
      { wch: 20 }, // Référence
      { wch: 15 }, // Montant
      { wch: 30 }, // Nom du Destinataire
      { wch: 40 }, // Commentaire
      { wch: 20 }  // Type d'opération
    ];

    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, 'Transferts Mobile Money');

    const fileName = `Transferts_Mobile_Money_${new Date().toISOString().split('T')[0]}.xlsx`;
    XLSX.writeFile(workbook, fileName);

    alert(`Export réussi ! ${mobileMoneyData.length} transferts générés pour un total de ${grandTotal} FCFA`);

  } catch (error) {
    console.error("Erreur lors de l'export des sélections:", error);
    alert("Une erreur est survenue : " + error.message);
  }
}

const markSelectedAsPaid = async () => {
  if (selectedLists.value.length === 0) {
    alert('Veuillez sélectionner au moins une liste pour marquer comme payé.');
    return;
  }

  if (!confirm('Êtes-vous sûr de vouloir marquer comme payé les listes sélectionnées ? Cela marquera tous les paiements de ces listes comme payés.')) {
    return;
  }

  try {
    const response = await router.post(route('meeting-payments.lists.mark-paid-multiple'), {
      ids: selectedLists.value
    });

    if (response.ok) {
      alert('Les listes sélectionnées ont été marquées comme payées.');
      router.reload(); // Recharger la page pour mettre à jour les statuts
    } else {
      const errorData = await response.json();
      alert('Erreur lors de la mise à jour des statuts : ' + errorData.message);
    }
  } catch (error) {
    console.error("Erreur lors de la mise à jour des statuts des sélections:", error);
    alert("Une erreur est survenue : " + error.message);
  }
}

const markListAsPaid = async (listId) => {
  if (!confirm('Êtes-vous sûr de vouloir marquer cette liste comme payée ? Cela marquera tous les paiements de cette liste comme payés.')) {
    return;
  }

  try {
    const response = await router.post(route('meeting-payments.lists.mark-paid', listId));

    if (response.ok) {
      alert('La liste a été marquée comme payée.');
      router.reload(); // Recharger la page pour mettre à jour le statut
    } else {
      const errorData = await response.json();
      alert('Erreur lors de la mise à jour du statut : ' + errorData.message);
    }
  } catch (error) {
    console.error("Erreur lors de la mise à jour du statut de la liste:", error);
    alert("Une erreur est survenue : " + error.message);
  }
}

const getExportStatusClass = (status) => {
  switch (status) {
    case 'not_exported':
      return 'bg-yellow-100 text-yellow-800'
    case 'exported':
      return 'bg-blue-100 text-blue-800'
    case 'paid':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getExportStatusText = (status) => {
  switch (status) {
    case 'not_exported':
      return 'Non exporté'
    case 'exported':
      return 'Exporté'
    case 'paid':
      return 'Payé'
    default:
      return 'Inconnu'
  }
}

// Méthodes utilitaires pour les statuts
const getAttendanceStatusClass = (status) => {
  switch (status) {
    case 'present':
      return 'bg-green-100 text-green-800'
    case 'absent':
      return 'bg-red-100 text-red-800'
    case 'replaced':
      return 'bg-blue-100 text-blue-800'
    case 'excused':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getAttendanceStatusText = (status) => {
  switch (status) {
    case 'present':
      return 'Présent'
    case 'absent':
      return 'Absent'
    case 'replaced':
      return 'Remplacé'
    case 'excused':
      return 'Excusé'
    default:
      return 'Inconnu'
  }
}

const formatTime = (time) => {
  if (!time) return ''
  try {
    return format(new Date(time), 'HH:mm', { locale: fr })
  } catch {
    return time
  }
}
</script> 