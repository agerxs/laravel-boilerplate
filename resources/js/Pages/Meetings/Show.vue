<template>
  <AppLayout :title="meeting.title">
    <!-- Début de la page principale -->
    <div class="py-6">
      <!-- En-tête personnalisé pour cette page -->
      <div class="mb-6 bg-white shadow rounded-lg p-4">
        <div class="flex justify-between items-center">
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ meeting.title }}
            <span :class="[getStatusClass(meeting.status), 'px-3 py-1 rounded-full text-sm font-medium']">
              {{ getStatusText(meeting.status, 'meeting') }}
            </span>
          </h2>
          
        </div>
      </div>

      <!-- Contenu principal existant -->
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Informations sur la validation -->
        <div v-if="meeting.status === 'validated'" class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6"> 
            <h3 class="text-lg font-medium text-gray-900">Informations de validation</h3>
            <div class="mt-4 space-y-2">
              <p class="text-sm text-gray-600">
                <span class="font-medium">Validée le :</span>
                {{ formatDateTime(meeting.validated_at) }}
                <span v-if="meeting.validator"> par {{ meeting.validator.name }}</span>
              </p>
              <p v-if="meeting.validation_comments" class="text-sm text-gray-600">
                <span class="font-medium">Commentaires :</span>
                {{ meeting.validation_comments }}
              </p>
            </div>
          </div>
        </div>
       
        <!-- Informations de la réunion -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Détails</h3>
                <div class="mt-4 space-y-2">
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Date :</span>
                    {{ formatDateTime(meeting.scheduled_date) }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Lieu :</span>
                    <div v-if="meeting.local_committee && meeting.local_committee.locality">
                      {{ meeting.local_committee.locality.name }}
                    </div>
                    <div v-else>
                      Localité non définie
                    </div>
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Organisateur :</span>
                    {{ meeting.organizer?.name }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Nombre de personnes à enrôler :</span>
                    {{ meeting.target_enrollments || 0 }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Nombre de personnes enrôlées :</span>
                    {{ meeting.actual_enrollments || 0 }}
                    <span v-if="meeting.target_enrollments" class="ml-2 text-xs inline-block bg-emerald-100 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded-full">
                      {{ meeting.actual_enrollments }}/{{ meeting.target_enrollments }}
                    </span>
                  </p>
                  
                  <!-- Formulaire pour mettre à jour les enrôlements -->
                  <div v-if="isSecretary && ['scheduled', 'planned', 'prevalidated', 'validated'].includes(meeting.status)" class="mt-4 border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Mettre à jour les enrôlements</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <InputLabel for="target_enrollments" value="Nombre de personnes à enrôler" />
                        <TextInput
                          id="target_enrollments"
                          v-model="enrollmentsForm.target_enrollments"
                          type="number"
                          min="0"
                          class="mt-1 block w-full text-sm"
                          @input="validateEnrollments"
                        />
                      </div>
                      <div>
                        <InputLabel for="actual_enrollments" value="Nombre de personnes enrôlées" />
                        <TextInput
                          id="actual_enrollments"
                          v-model="enrollmentsForm.actual_enrollments"
                          type="number"
                          min="0"
                          :max="enrollmentsForm.target_enrollments"
                          class="mt-1 block w-full text-sm"
                          @input="validateEnrollments"
                        />
                      </div>
                    </div>
                    <div class="flex justify-end mt-3">
                      <button
                        type="button"
                        @click="updateEnrollments"
                        class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded text-sm font-medium hover:bg-indigo-200"
                        :disabled="enrollmentsLoading"
                      >
                        <span v-if="enrollmentsLoading">Mise à jour...</span>
                        <span v-else>Mettre à jour</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <h3 class="text-lg font-medium text-gray-900">Description</h3>
                <p class="mt-4 text-sm text-gray-600">
                  {{ meeting.description || 'Aucune description' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Liste de Présence -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Liste de Présence</h3>
           
              <!-- Bouton pour gérer la liste de présence (secrétaires et admins) -->
              <div v-if="['scheduled', 'prevalidated', 'validated', 'planned'].includes(meeting.status) && isSecretary" class="mt-2">
               
                <a 
                  href="#"
                  @click.prevent="$inertia.visit(route('meetings.attendance', meeting.id))"
                  class="inline-flex items-center px-4 py-2 bg-white border border-indigo-300 text-indigo-700 rounded-md text-sm font-medium hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <UsersIcon class="h-4 w-4 mr-1" />
                  Gérer la liste de présence
                </a>
              </div>
              
              <!-- Bouton pour consulter la liste de présence (sous-préfets et réunions terminées) -->
              <div v-if="(isSubPrefect && ['scheduled', 'prevalidated', 'validated', 'planned'].includes(meeting.status)) || meeting.status === 'completed'" class="mt-2">
                <a 
                  href="#"
                  @click.prevent="$inertia.visit(route('meetings.attendance', meeting.id))"
                  class="inline-flex items-center px-4 py-2 bg-white border border-green-300 text-green-700 rounded-md text-sm font-medium hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <EyeIcon class="h-4 w-4 mr-1" />
                  Consulter la liste de présence
                </a>
              </div>
            </div>
            
            <!-- Analyse géographique si des participants ont des positions GPS -->
            <div v-if="meeting.attendees && meeting.attendees.some(a => a.presence_location)" class="mt-4">
              <GeographicAnalysis 
                :attendees="meeting.attendees" 
                :max-distance="100"
              />
            </div>
            
            <div v-if="meeting.attendees && meeting.attendees.length > 0" class="mt-4">
              <!-- Barre de recherche et filtres -->
              <div class="mb-4 space-y-3">
                <!-- Recherche -->
                <div class="flex items-center space-x-4">
                  <div class="flex-1">
                    <input
                      v-model="searchQuery"
                      type="text"
                      placeholder="Rechercher un participant..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                  <div class="flex items-center space-x-2">
                    <label class="text-sm font-medium text-gray-700">Statut :</label>
                    <select
                      v-model="statusFilter"
                      class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                      <option value="">Tous</option>
                      <option value="present">Présents</option>
                      <option value="absent">Absents</option>
                      <option value="replaced">Remplacés</option>
                      <option value="expected">En attente</option>
                    </select>
                  </div>
                </div>
                
                <!-- Informations sur les résultats -->
                <div class="flex justify-between items-center text-sm text-gray-600">
                  <span>
                    {{ filteredAttendees.length }} participant(s) sur {{ meeting.attendees.length }} total
                  </span>
                  <span v-if="searchQuery || statusFilter">
                    Filtres actifs
                  </span>
                </div>
              </div>

              <!-- Liste des participants avec pagination -->
              <div v-if="paginatedAttendees.length > 0">
                <ul class="divide-y divide-gray-200">
                  <li v-for="attendee in paginatedAttendees" :key="attendee.id" class="py-4">
                    <div class="flex items-center space-x-4">
                      <!-- Photo de présence si disponible -->
                      <div v-if="attendee.presence_photo" class="flex-shrink-0">
                        <img 
                          :src="`/storage/${attendee.presence_photo}`" 
                          class="h-12 w-12 rounded-full object-cover border-2 border-primary-500 cursor-pointer hover:opacity-80 transition-opacity"
                          @click="showPhotoModal(attendee)"
                          :title="'Photo prise le ' + formatDate(attendee.presence_timestamp)"
                        />
                      </div>
                      <div v-else class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
                          <UserIcon class="h-6 w-6 text-gray-400" />
                        </div>
                      </div>
                      
                      <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">
                          {{ attendee.name }}
                          <span v-if="attendee.replacement_name" class="text-xs text-yellow-600 ml-2">
                            (Remplacé par {{ attendee.replacement_name }})
                          </span>
                        </p>
                        <p class="text-sm text-gray-500">
                          {{ attendee.role || 'Pas de rôle défini' }}
                          <span v-if="attendee.village?.name" class="ml-2">• {{ attendee.village.name }}</span>
                        </p>
                        <p v-if="attendee.phone" class="text-sm text-gray-500">
                          📞 {{ attendee.phone }}
                        </p>
                        <!-- Informations de présence -->
                        <div v-if="attendee.attendance_status === 'present' || attendee.attendance_status === 'replaced'" class="mt-1">
                          <p v-if="attendee.arrival_time" class="text-xs text-green-600">
                            🕐 Arrivé à {{ formatTime(attendee.arrival_time) }}
                          </p>
                          <p v-if="attendee.presence_location" class="text-xs text-blue-600">
                            📍 {{ formatLocation(attendee.presence_location) }}
                          </p>
                        </div>
                        <!-- Commentaires -->
                        <p v-if="attendee.comments" class="text-xs text-gray-600 mt-1">
                          💬 {{ attendee.comments }}
                        </p>
                      </div>
                      <div>
                        <span
                          :class="[
                            'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                            getStatusClass(attendee.attendance_status || 'expected')
                          ]"
                        >
                          {{ getStatusText(attendee.attendance_status || 'expected', 'attendance') }}
                        </span>
                      </div>
                    </div>
                  </li>
                </ul>
                
                <!-- Pagination -->
                <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between">
                  <div class="flex items-center space-x-2 text-sm text-gray-700">
                    <span>
                      Page {{ currentPage }} sur {{ totalPages }}
                    </span>
                    <span>
                      ({{ startIndex + 1 }}-{{ endIndex }} sur {{ filteredAttendees.length }})
                    </span>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <button
                      @click="previousPage"
                      :disabled="currentPage === 1"
                      class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Précédent
                    </button>
                    
                    <div class="flex items-center space-x-1">
                      <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="goToPage(page)"
                        :class="[
                          'px-3 py-1 text-sm rounded-md',
                          page === currentPage
                            ? 'bg-indigo-600 text-white'
                            : 'border border-gray-300 hover:bg-gray-50'
                        ]"
                      >
                        {{ page }}
                      </button>
                    </div>
                    
                    <button
                      @click="nextPage"
                      :disabled="currentPage === totalPages"
                      class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Suivant
                    </button>
                  </div>
                </div>
              </div>
              
              <!-- Message si aucun résultat -->
              <div v-else class="text-center py-8 text-gray-500">
                <p>Aucun participant trouvé avec les critères actuels.</p>
                <button
                  v-if="searchQuery || statusFilter"
                  @click="clearFilters"
                  class="mt-2 text-indigo-600 hover:text-indigo-800 text-sm"
                >
                  Effacer les filtres
                </button>
              </div>
              
              <!-- Statistiques de présence -->
              <div class="mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Statistiques de présence</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ getAttendanceCount('present') }}</div>
                    <div class="text-xs text-gray-500">Présents</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ getAttendanceCount('replaced') }}</div>
                    <div class="text-xs text-gray-500">Remplacés</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ getAttendanceCount('absent') }}</div>
                    <div class="text-xs text-gray-500">Absents</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ getAttendanceCount('expected') }}</div>
                    <div class="text-xs text-gray-500">En attente</div>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="mt-4 text-sm text-gray-600">
              <p>Aucun participant n'a encore été enregistré pour cette réunion.</p>
            </div>
          </div>
        </div>

        <!-- Pièces jointes -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Pièces jointes</h3>
              <div class="flex items-center space-x-2">
                <!-- Supprimer cette partie
                <label class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 cursor-pointer">
                    Ajouter un fichier
                    <input
                        type="file"
                        class="hidden"
                        @change="handleFileChange"
                        ref="fileInput"
                    >
                </label>
                -->
              </div>
            </div>

            <div class="mt-4">
              <form v-if="isSecretary && meeting.status !== 'completed'" @submit.prevent="uploadFile" class="flex items-end space-x-4" enctype="multipart/form-data">
                <div class="flex-1">
                  <InputLabel for="title" value="Titre du document" />
                  <TextInput
                    id="title"
                    v-model="attachmentForm.title"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                </div>

                <div class="flex-1">
                  <InputLabel for="nature" value="Nature du document" />
                  <select
                    id="nature"
                    v-model="attachmentForm.nature"
                    class="mt-1 block w-full rounded-md border-gray-300"
                    required
                  >
                    <option value="">Sélectionner la nature</option>
                    <option value="photo">Photo</option>
                    <option value="document_justificatif">Document justificatif</option>
                    <option value="compte_rendu">Compte rendu</option>
                  </select>
                </div>

                <div class="flex-1">
                  <InputLabel for="file" value="Fichier" />
                  <input
                    type="file"
                    id="file"
                    @change="handleFileChange"
                    class="mt-1 block w-full"
                    required
                  />
                </div>

                <div class="flex-none">
                  <PrimaryButton type="submit" :disabled="uploading">
                    <span v-if="uploading">Envoi en cours...</span>
                    <span v-else>Ajouter</span>
                  </PrimaryButton>
                </div>
              </form>
              
              <!-- Aperçu du fichier sélectionné -->
              <div v-if="filePreview" class="mt-4 p-4 border rounded-md bg-gray-50">
                <div class="flex items-center justify-between">
                  <div>
                    <h4 class="font-medium">Aperçu du fichier à télécharger :</h4>
                    <p class="text-sm text-gray-600">{{ filePreview.name }} ({{ filePreview.size }})</p>
                    <p class="text-xs text-gray-500">Type : {{ filePreview.type }}</p>
                  </div>
                  <div v-if="filePreview.url" class="ml-4">
                    <img :src="filePreview.url" class="max-h-24 rounded border" alt="Aperçu" />
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <h4 class="font-medium text-gray-800 mb-2" v-if="meeting.attachments && meeting.attachments.length > 0">Liste des pièces jointes ({{ meeting.attachments.length }})</h4>
              
              <div
                v-for="attachment in meeting.attachments"
                :key="attachment.id"
                class="flex items-center justify-between p-3 border rounded-md bg-white hover:bg-gray-50"
              >
                <div class="flex items-center">
                  <div class="mr-3">
                    <div v-if="attachment.file_type && attachment.file_type.startsWith('image/')" class="h-12 w-12 bg-gray-100 rounded flex items-center justify-center overflow-hidden">
                      <img :src="route('attachments.download', attachment.id)" class="h-full w-full object-cover" alt="" />
                    </div>
                    <div v-else class="h-12 w-12 bg-gray-100 rounded flex items-center justify-center">
                      <span class="text-2xl text-gray-500">📄</span>
                    </div>
                  </div>
                  <div>
                    <div class="font-medium">{{ attachment.title }}</div>
                    <div class="text-sm text-gray-500">
                      {{ attachment.nature_label }} - {{ formatFileSize(attachment.size) }}
                    </div>
                  </div>
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="downloadFile(attachment)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Télécharger
                  </button>
                  <button
                    v-if="isSecretary && meeting.status !== 'completed'"
                    @click="deleteFile(attachment)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Supprimer
                  </button>
                </div>
              </div>
              
              <p v-if="!meeting.attachments || meeting.attachments.length === 0" class="text-sm text-gray-500 italic">
                Aucune pièce jointe pour cette réunion
              </p>
            </div>
          </div>
        </div>

        <!-- Compte rendu -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Compte rendu</h3>
            
          </div>
          <div>
            <div v-if="editingMinutes">
              <RichTextEditor
                v-model="form.minutes.content"
                placeholder="Rédigez le compte rendu ici..."
              />

              
              <div class="mt-4 flex justify-end space-x-3">
                
                <button
                  v-if="meeting.status !== 'completed'"
                  @click="cancelEditMinutes"
                  class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                >
                  Annuler
                </button>
                <button v-if="isSecretary && meeting.status !== 'completed'"
        @click="saveAll"
        class="flex items-center px-4 py-2 bg-green-600  text-white rounded-lg shadow-lg hover:bg-green-700 transition-colors"
        :disabled="form.processing"
      >
        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Enregistrer</span>
      </button>
                <div class="flex flex-wrap gap-2">
            <MeetingValidationButtons 
              :meeting="meeting"
              @meeting-updated="handleMeetingUpdated"
            />
             <!-- Reschedule button -->
             <button
               v-if="(meeting.status === 'scheduled' || meeting.status === 'planned') && isSecretary && !isSubPrefect"
               @click="showRescheduleModal = true"
               class="inline-flex items-center px-4 py-2 bg-white border border-blue-300 text-blue-700 rounded-md text-sm font-medium hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
             >
               Replanifier
             </button>
            <!-- Button to manage attendance list -->
           
            
            <!-- Autres boutons d'action -->
            <!-- Bouton pour valider -->
            <button
              v-if="(isSubPrefect || isAdmin) && meeting.status === 'completed'"
              @click="showValidationModal = true"
              class="inline-flex items-center px-4 py-2 bg-white border border-violet-300 text-violet-700 rounded-md text-sm font-medium hover:bg-violet-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500"
            >
              Valider
            </button>
            <!-- Bouton pour invalider -->
            <button
              v-if="(isSubPrefect || isAdmin) && meeting.status === 'validated' && meeting.status !== 'cancelled'"
              @click="showInvalidationModal = true"
              class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-700 rounded-md text-sm font-medium hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              Invalider
            </button>
            
           
            
            <!-- Complete button -->
            <button
              v-if="(meeting.status === 'scheduled' || meeting.status === 'planned') && isSecretary"
              @click="completeConfirm"
              class="inline-flex items-center px-4 py-2 bg-white border border-green-300 text-green-700 rounded-md text-sm font-medium hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              Publier
            </button>
            
            <!-- Unpublish button -->
            <button
              v-if="meeting.status === 'completed' && isSecretary && !meeting.validated_at && !meeting.invalidated_at"
              @click="unpublishConfirm"
              class="inline-flex items-center px-4 py-2 bg-white border border-orange-300 text-orange-700 rounded-md text-sm font-medium hover:bg-orange-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
              Dépublier
            </button>
         
          </div>
              </div>
            </div>
            <div v-else>
              <!-- Afficher le statut du compte-rendu -->
              <div v-if="meeting.minutes" class="mb-4">
                <div class="flex items-center space-x-2 mb-3">
                  <span class="font-semibold">Statut :</span>
                  <span 
                    :class="{
                      'bg-gray-100 text-gray-700': meeting.minutes.status === 'draft',
                      'bg-green-100 text-green-700': meeting.minutes.status === 'published',
                      'bg-yellow-100 text-yellow-700': meeting.minutes.status === 'pending_validation',
                      'bg-blue-100 text-blue-700': meeting.minutes.status === 'validated'
                    }"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getStatusLabel(meeting.minutes.status) }}
                  </span>
                </div>
                
                <!-- Si validé, afficher qui a validé et quand -->
                <div v-if="meeting.minutes.status === 'validated'" class="text-sm text-gray-600 mb-3">
                  Validé par {{ meeting.minutes.validator?.name || 'Un sous-préfet' }} 
                  le {{ formatDate(meeting.minutes.validated_at) }}
                </div>
                
                <!-- Commentaires de validation s'il y en a -->
                <div v-if="meeting.minutes.validation_comments" class="mb-3 p-3 bg-gray-50 rounded-lg">
                  <div class="font-medium text-sm">Commentaires de validation :</div>
                  <div class="text-sm text-gray-700">{{ meeting.minutes.validation_comments }}</div>
                </div>
              </div>
              
              <div class="prose max-w-none" v-html="form.minutes.content || 'Aucun compte rendu'" />

              <!-- Boutons d'action selon le rôle et le statut -->
              <div class="flex justify-end space-x-3 mt-4">
                <!-- Bouton de demande de validation (pour les secrétaires) - visible quand le compte rendu est publié -->
                <button
                  v-if="isSecretary && meeting.minutes && meeting.minutes.status === 'published' && isSecretary"
                  @click="requestValidation"
                  class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700"
                >
                  <ClipboardCheckIcon class="h-5 w-5 mr-2" />
                  Demander la validation
                </button>
                
                <!-- Boutons de validation (pour les sous-préfets) - visibles quand le compte rendu est en attente de validation -->
                <div v-if="isSubPrefect && meeting.minutes && meeting.minutes.status === 'pending_validation'" class="flex space-x-3">
                  <button
                    @click="showValidationModal = true; validationDecision = 'validate'"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                  >
                    <CheckCircleIcon class="h-5 w-5 mr-2" />
                    Valider
                  </button>
                  <button
                    @click="showValidationModal = true; validationDecision = 'reject'"
                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                  >
                    <XCircleIcon class="h-5 w-5 mr-2" />
                    Rejeter
                  </button>
                </div>
                
                <!-- Bouton d'envoi par email - visible quand le compte rendu est validé ou publié -->
                <button
                  v-if="meeting.minutes && ['published', 'validated'].includes(meeting.minutes.status) && isSecretary"
                  @click="showSendEmailModal = true"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                >
                  <EnvelopeIcon class="h-5 w-5 mr-2" />
                  Envoyer le compte rendu
                </button>
              </div>
            </div>
          </div>
        </div>

    
      </div>
    </div>

    <!-- Modal pour ajouter/éditer un point d'ordre du jour -->
    <Modal :show="showNewAgendaItemModal" @close="closeAgendaItemModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ editingAgendaItem ? 'Modifier le point' : 'Nouveau point' }}
        </h3>
        <form @submit.prevent="submitAgendaItem" class="space-y-4">
          <div>
            <InputLabel for="agenda-title" value="Titre" />
            <TextInput
              id="agenda-title"
              v-model="agendaForm.title"
              type="text"
              class="mt-1 block w-full"
              required
            />
          </div>

          <div>
            <InputLabel for="agenda-description" value="Description" />
            <TextArea
              id="agenda-description"
              v-model="agendaForm.description"
              class="mt-1 block w-full"
              rows="3"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <InputLabel for="agenda-duration" value="Durée (minutes)" />
              <TextInput
                id="agenda-duration"
                v-model="agendaForm.duration_minutes"
                type="number"
                class="mt-1 block w-full"
                min="1"
              />
            </div>
            <div>
              <InputLabel for="agenda-presenter" value="Présentateur" />
              <select
                id="agenda-presenter"
                v-model="agendaForm.presenter_id"
                class="mt-1 block w-full rounded-md border-gray-300"
              >
                <option value="">Sélectionner un présentateur</option>
                <option
                  v-for="participant in meeting.participants"
                  :key="participant.id"
                  :value="participant.id"
                >
                  {{ participant.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <SecondaryButton @click="closeAgendaItemModal">
              Annuler
            </SecondaryButton>
            <PrimaryButton :disabled="agendaForm.processing">
              {{ editingAgendaItem ? 'Mettre à jour' : 'Ajouter' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Modal d'ajout/modification -->
    <Modal :show="showNewEnrollmentModal" @close="closeEnrollmentModal">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ editingEnrollment ? 'Modifier' : 'Ajouter' }} une personne à enrôler
            </h3>

            <form @submit.prevent="submitEnrollment" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="first_name" value="Prénom" />
                        <TextInput
                            id="first_name"
                            v-model="enrollmentForm.first_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>

                    <div>
                        <InputLabel for="last_name" value="Nom" />
                        <TextInput
                            id="last_name"
                            v-model="enrollmentForm.last_name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="phone" value="Téléphone" />
                        <TextInput
                            id="phone"
                            v-model="enrollmentForm.phone"
                            type="tel"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="enrollmentForm.email"
                            type="email"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel for="address" value="Adresse" />
                    <TextInput
                        id="address"
                        v-model="enrollmentForm.address"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                </div>

                <div>
                    <InputLabel for="notes" value="Notes" />
                    <TextArea
                        id="notes"
                        v-model="enrollmentForm.notes"
                        class="mt-1 block w-full"
                        rows="3"
                    />
                </div>

                <div class="flex justify-end mt-6">
                    <SecondaryButton @click="closeEnrollmentModal" class="mr-3">
                        Annuler
                    </SecondaryButton>
                    <PrimaryButton type="submit">
                        {{ editingEnrollment ? 'Mettre à jour' : 'Ajouter' }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Modal pour gérer les représentants -->
    <Modal :show="showManageRepresentativesModal" @close="closeManageRepresentativesModal" max-width="4xl">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Gérer les participants à la réunion</h3>
        
        <div v-if="meeting.local_committee?.locality?.children && meeting.local_committee.locality.children.length > 0" 
             class="space-y-6">
          <div v-for="village in meeting.local_committee.locality.children" 
               :key="village.id" 
               class="bg-gray-50 p-4 rounded-lg border">
            <div class="flex justify-between items-center mb-4">
              <h4 class="font-medium text-gray-900">{{ village.name }}</h4>
              <button 
                type="button" 
                @click="toggleVillageRepresentatives(village.id)"
                class="text-sm text-blue-600 hover:text-blue-800"
              >
                {{ expandedVillages.includes(village.id) ? 'Masquer' : 'Modifier les représentants' }}
              </button>
            </div>
            
            <div v-if="expandedVillages.includes(village.id)" class="space-y-4">
              <div v-for="(rep, index) in meetingRepresentatives[village.id] || []" 
                   :key="index" 
                   class="bg-white p-3 rounded border">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <InputLabel :for="`rep-${village.id}-${index}-name`" value="Nom complet" />
                    <TextInput
                      :id="`rep-${village.id}-${index}-name`"
                      v-model="rep.name"
                      type="text"
                      class="mt-1 block w-full"
                      required
                    />
                  </div>
                  <div>
                    <InputLabel :for="`rep-${village.id}-${index}-phone`" value="Téléphone" />
                    <TextInput
                      :id="`rep-${village.id}-${index}-phone`"
                      v-model="rep.phone"
                      type="text"
                      class="mt-1 block w-full"
                    />
                  </div>
                  <div>
                    <InputLabel :for="`rep-${village.id}-${index}-role`" value="Rôle" />
                    <select
                      :id="`rep-${village.id}-${index}-role`"
                      v-model="rep.role"
                      class="mt-1 block w-full rounded-md border-gray-300"
                      required
                    >
                      <option value="">Sélectionner un rôle</option>
                      <option value="Chef de village">Chef de village</option>
                      <option value="Représentant des femmes">Représentant des femmes</option>
                      <option value="Représentant des jeunes">Représentant des jeunes</option>
                      <option value="Autre">Autre</option>
                    </select>
                  </div>
                </div>
                
                <div class="flex justify-between items-center mt-3">
                  <div class="flex items-center">
                    <input 
                      :id="`rep-${village.id}-${index}-attending`" 
                      v-model="rep.is_expected" 
                      type="checkbox" 
                      class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    />
                    <label :for="`rep-${village.id}-${index}-attending`" class="ml-2 text-sm text-gray-700">
                      Participera à la réunion
                    </label>
                  </div>
                  
                  <div class="flex items-center space-x-2">
                    <div v-if="meeting.status === 'completed'" class="flex items-center mr-4">
                      <input 
                        :id="`rep-${village.id}-${index}-present`" 
                        v-model="rep.is_present" 
                        type="checkbox" 
                        class="rounded border-gray-300 text-green-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                      />
                      <label :for="`rep-${village.id}-${index}-present`" class="ml-2 text-sm text-gray-700">
                        Était présent
                      </label>
                    </div>
                    
                    <button 
                      type="button" 
                      @click="removeRepresentative(village.id, index)"
                      class="text-sm text-red-600 hover:text-red-800"
                    >
                      Supprimer
                    </button>
                  </div>
                </div>
              </div>
              
              <div class="flex justify-center">
                <button
                  type="button"
                  @click="addRepresentative(village.id)"
                  class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium flex items-center"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  Ajouter un représentant
                </button>
              </div>
            </div>
            
            <div v-else class="text-sm text-gray-600">
              {{ getParticipantsCount(village.id) }} / {{ meetingRepresentatives[village.id]?.length || 0 }} représentants participeront
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          
          <SecondaryButton @click="closeManageRepresentativesModal">
            Annuler
          </SecondaryButton>
          <PrimaryButton @click="saveRepresentatives">
            Enregistrer les participants
          </PrimaryButton>
        </div>
      </div>
    </Modal>

  

    <!-- Modal de validation -->
    <Modal :show="showValidationModal" @close="closeValidationModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Valider la réunion</h2>
        <p class="mt-1 text-sm text-gray-600">
          Souhaitez-vous valider définitivement cette réunion ?
        </p>
        <div class="mt-4">
          <label for="validation-comments" class="block text-sm font-medium text-gray-700">Commentaires (optionnel)</label>
          <textarea
            id="validation-comments"
            v-model="validationComments"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            rows="3"
          ></textarea>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            type="button"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            @click="closeValidationModal"
          >
            Annuler
          </button>
          <button 
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            @click="validateMeeting"
          >
            Valider
          </button>
        </div>
      </div>
    </Modal>
    
    <!-- Modal d'envoi par email -->
    <Modal :show="showSendEmailModal" @close="showSendEmailModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Envoyer le compte rendu par email
        </h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Destinataires</label>
          <div v-for="(recipient, index) in emailRecipients" :key="index" class="flex mb-2">
            <input 
              v-model="emailRecipients[index]"
              type="email"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
              placeholder="Adresse email"
            />
            <button 
              @click="removeRecipient(index)"
              class="ml-2 text-red-500 hover:text-red-700"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
          <button 
            @click="addRecipient"
            class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
          >
            + Ajouter un destinataire
          </button>
        </div>
        
        <div class="flex justify-end">
          <button 
            @click="showSendEmailModal = false"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 mr-3"
          >
            Annuler
          </button>
          <button 
            @click="sendMinutesByEmail"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
          >
            Envoyer
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal pour l'invalidation -->
    <Modal :show="showInvalidationModal" @close="closeInvalidationModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Invalider la réunion</h2>
        <p class="mt-1 text-sm text-gray-600">
          Souhaitez-vous invalider cette réunion ? Celle-ci retournera à l'état de planification.
        </p>
        <div class="mt-4">
          <label for="invalidation-comments" class="block text-sm font-medium text-gray-700">Motif de l'invalidation (obligatoire)</label>
          <textarea
            id="invalidation-comments"
            v-model="invalidationComments"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
            rows="3"
            required
          ></textarea>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
          <button 
            type="button"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            @click="closeInvalidationModal"
          >
            Annuler
          </button>
          <button 
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            @click="invalidateMeeting"
          >
            Invalider
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal de replanification -->
    <Modal :show="showRescheduleModal" @close="closeRescheduleModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Replanifier la réunion
        </h3>
        <form @submit.prevent="submitReschedule">
          <div class="space-y-4">
            <!-- Nouvelle date et heure -->
            <div>
              <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                Nouvelle date et heure
              </label>
              <input
                id="date"
                v-model="rescheduleForm.date"
                type="datetime-local"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>

            <!-- Motif du report -->
            <div>
              <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                Motif du report
              </label>
              <textarea
                id="reason"
                v-model="rescheduleForm.reason"
                rows="4"
                required
                placeholder="Expliquez pourquoi cette réunion doit être reportée..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              ></textarea>
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <button
              type="button"
              @click="closeRescheduleModal"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="rescheduleLoading"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              <span v-if="rescheduleLoading">Enregistrement...</span>
              <span v-else>Replanifier</span>
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Modal pour afficher les photos de présence -->
    <Modal :show="photoModalOpen" @close="closePhotoModal">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Photo de présence - {{ selectedAttendee?.name }}
        </h3>
        
        <div v-if="selectedAttendee?.presence_photo" class="mb-4">
          <img 
            :src="`/storage/${selectedAttendee.presence_photo}`" 
            alt="Photo de présence" 
            class="w-full rounded-lg shadow-lg"
          />
          <div class="mt-2 text-sm text-gray-600">
            <p>Photo prise le {{ formatDate(selectedAttendee.presence_timestamp) }}</p>
            <p>Localisation : {{ formatLocation(selectedAttendee.presence_location) }}</p>
            <p v-if="selectedAttendee.arrival_time">Arrivé à : {{ formatTime(selectedAttendee.arrival_time) }}</p>
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

<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue'
import Modal from '@/Components/Modal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextArea from '@/Components/TextArea.vue'
import TextInput from '@/Components/TextInput.vue'
import { useToast } from '@/Composables/useToast'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    ChevronDownIcon,
    ChevronUpIcon,
    EnvelopeIcon,
    PencilIcon,
    ClipboardDocumentIcon as ClipboardCheckIcon,
    CheckCircleIcon,
    XCircleIcon,
    XMarkIcon,
    UsersIcon,
    EyeIcon,
    UserIcon
} from '@heroicons/vue/24/outline'
import { router, useForm, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, onMounted, ref, watch } from 'vue'
import draggable from 'vuedraggable/src/vuedraggable'
import MeetingValidationButtons from '@/Components/MeetingValidationButtons.vue'
import { getStatusText, getStatusClass, translateRole } from '@/Utils/translations'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'
import GeographicAnalysis from '@/Components/GeographicAnalysis.vue'

interface AgendaItem {
    id: number;
    title: string;
    description: string;
    duration_minutes: number;
    presenter_id: number | null;
    presenter?: {
        name: string;
    };
    order?: number;
}

interface Comment {
    id: number;
    content: string;
    user: {
        name: string;
    };
    created_at: string;
}

interface MinutesVersion {
    id: number;
    version_number: string;
    content: string;
    change_summary: string;
    created_at: string;
    created_by: {
        name: string;
    };
}

interface User {
  id: number
  name: string
  email: string
}

interface Participant {
  id: number
  user_id: number | null
  guest_name: string | null
  guest_email: string | null
  status: string
  user?: User
}

interface Minutes {
  id: number
  content: string
  status: 'draft' | 'published' | 'pending_validation' | 'validated'
  validator?: {
    name: string
  }
  validated_at?: string
  validation_comments?: string
}

interface Attachment {
  id: number
  title: string
  size: number
  nature_label: string
}

interface Meeting {
  id: number
  title: string
  description: string
  start_datetime: string
  end_datetime: string
  location: string
  status: 'planned' | 'completed' | 'cancelled'
  local_committees?: Array<{
    id: number
    name: string
    city: string
    address: string
    members: Array<{
      id: number
      role: string
      user: {
        name: string
        email: string
      }
    }>
  }>
  participants: Participant[]
  minutes?: Minutes
  agenda?: AgendaItem[]
  attachments?: Array<Attachment>
  enrollment_requests?: Array<EnrollmentRequest>
  organizer?: {
    name: string
  }
}

interface Props {
  meeting: Meeting
  user: User
}

const getInitials = (person): string => {
  // Pour les membres du comité qui ont un user_id
  if (person.user_id && person.user) {
    return person.user.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }
  
  // Pour les représentants qui ont first_name et last_name
  if (person.first_name && person.last_name) {
    return (person.first_name[0] + person.last_name[0]).toUpperCase();
  }
  
  // Pour les personnes qui ont juste un name
  if (person.name) {
    return person.name
      .split(' ')
      .map(n => n[0])
      .join('')
      .toUpperCase()
      .substring(0, 2);
  }
  
  // Valeur par défaut
  return 'XX';
}

const props = defineProps<Props>()

const showNewAgendaItemModal = ref(false)
const editingAgendaItem = ref(null)
const showManageRepresentativesModal = ref(false)
const expandedVillages = ref([])
const meetingRepresentatives = ref({})
const savingRepresentatives = ref(false)
const form = useForm({
    agenda: props.meeting.agenda?.map((item, index) => ({
        ...item,
        order: index
    })) || [],
    minutes: {
        content: props.meeting.minutes?.content || '',
        status: props.meeting.minutes?.status || 'draft'
    },
    attachments: props.meeting.attachments || []
});

const agendaForm = useForm({
  title: '',
  description: '',
  duration_minutes: '',
  presenter_id: ''
})

const toast = useToast()

const formatDateTime = (datetime) => {
  if (!datetime) return 'Non défini';
  
  try {
    const date = new Date(datetime);
    
    // Vérifier si la date est valide
    if (isNaN(date.getTime())) {
      return 'Date invalide';
    }
    
    // Formatter la date au format français
    return date.toLocaleString('fr-FR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return 'Date invalide';
  }
}

const closeAgendaItemModal = () => {
  showNewAgendaItemModal.value = false
  editingAgendaItem.value = null
  agendaForm.reset()
}

const editAgendaItem = (item) => {
  editingAgendaItem.value = item
  agendaForm.title = item.title
  agendaForm.description = item.description
  agendaForm.duration_minutes = item.duration_minutes
  agendaForm.presenter_id = item.presenter_id
  showNewAgendaItemModal.value = true
}

const submitAgendaItem = () => {
  if (editingAgendaItem.value) {
    const index = form.agenda.findIndex(i => i.id === editingAgendaItem.value.id);
    if (index !== -1) {
      form.agenda[index] = {
        ...form.agenda[index],
        title: agendaForm.title,
        description: agendaForm.description,
        duration_minutes: agendaForm.duration_minutes,
        presenter_id: agendaForm.presenter_id
      };
    }
  } else {
    form.agenda.push({
      id: Date.now(), // ID temporaire
      title: agendaForm.title,
      description: agendaForm.description,
      duration_minutes: agendaForm.duration_minutes,
      presenter_id: agendaForm.presenter_id
    });
  }
  closeAgendaItemModal();
}

const dragOptions = {
    animation: 200,
    group: "agenda",
    disabled: false,
    ghostClass: "ghost"
};

const updateAgendaOrder = () => {
    // Mettre à jour l'ordre après le drag & drop
    form.agenda = form.agenda.map((item, index) => ({
        ...item,
        order: index
    }));
};

// Pour les pièces jointes
const attachments = ref(props.meeting.attachments || [])

const attachmentForm = useForm({
    title: '',
    nature: '',
    file: null
});

const uploading = ref(false);
const filePreview = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        attachmentForm.file = file;
        
        // Création d'un aperçu du fichier
        filePreview.value = {
            name: file.name,
            size: formatFileSize(file.size),
            type: file.type
        };
        
        // Si c'est une image, créer une URL pour l'aperçu
        if (file.type.startsWith('image/')) {
            filePreview.value.url = URL.createObjectURL(file);
        }
    } else {
        filePreview.value = null;
    }
};

const uploadFile = () => {
    if (!attachmentForm.file) {
        toast.error('Veuillez sélectionner un fichier');
        return;
    }

    const formData = new FormData();
    formData.append('file', attachmentForm.file);
    formData.append('title', attachmentForm.title);
    formData.append('nature', attachmentForm.nature);

    uploading.value = true;

    axios.post(route('attachments.store', props.meeting.id), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Mise à jour des pièces jointes avec les données rafraîchies
        if (response.data && response.data.meeting && response.data.meeting.attachments) {
            attachments.value = response.data.meeting.attachments;
            // Mettre à jour les pièces jointes dans le meeting principal
            props.meeting.attachments = response.data.meeting.attachments;
        }
        
        // Réinitialiser le formulaire
        attachmentForm.reset();
        filePreview.value = null;
        uploading.value = false;
        
        // Réinitialiser le champ de fichier
        const fileInput = document.getElementById('file');
        if (fileInput) fileInput.value = '';
        
        toast.success('Fichier ajouté avec succès');
    })
    .catch(error => {
        uploading.value = false;
        toast.error(error.response?.data?.message || 'Erreur lors de l\'upload du fichier');
    });
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const deleteFile = async (attachment) => {
  if (!confirm('Voulez-vous vraiment supprimer ce fichier ?')) return

  try {
    await axios.delete(route('attachments.destroy', attachment.id))

    const index = attachments.value.findIndex(a => a.id === attachment.id)
    attachments.value.splice(index, 1)

    form.attachments = attachments.value

    toast.success('Fichier supprimé avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    toast.error('Erreur lors de la suppression du fichier')
  }
}

// Pour les comptes rendus
const editingMinutes = ref(true)

const updateMinutes = (content) => {
  form.minutes.content = content;
};

const saveMinutes = async () => {
  try {
    if (!props.meeting.minutes) {
      // Création d'un nouveau compte rendu
      await axios.post(route('minutes.store', props.meeting.id), {
        content: form.minutes.content
      })
    } else {
      // Mise à jour d'un compte rendu existant
      await axios.put(route('minutes.update', props.meeting.minutes.id), {
        content: form.minutes.content,
        status: form.minutes.status
      })
    }

    editingMinutes.value = false
    toast.success('Compte rendu enregistré avec succès')

    // Ne pas changer le statut de la réunion - juste sauvegarder les données
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors de l\'enregistrement du compte rendu')
  }
}

const publishMinutes = async () => {
  try {
    form.minutes.status = 'published'
    await axios.put(route('minutes.update', props.meeting.minutes.id), {
      content: form.minutes.content,
      status: 'published'
    })

    editingMinutes.value = false
    toast.success('Compte rendu publié avec succès')

    // Ne pas changer le statut de la réunion - juste publier le compte rendu
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors de la publication du compte rendu')
  }
}

const cancelEditMinutes = () => {
  editingMinutes.value = false
  form.reset()
  form.minutes.content = props.meeting.minutes.content
}

// Sauvegarde globale
const saveAll = async () => {
  try {
    // Sauvegarder d'abord le compte rendu s'il y en a un
    if (form.minutes.content) {
      if (!props.meeting.minutes) {
        // Création d'un nouveau compte rendu
        await axios.post(route('minutes.store', props.meeting.id), {
          content: form.minutes.content
        })
      } else {
        // Mise à jour d'un compte rendu existant
        await axios.put(route('minutes.update', props.meeting.minutes.id), {
          content: form.minutes.content,
          status: form.minutes.status
        })
      }
    }
    
    // Ensuite sauvegarder les autres modifications de la réunion
    await form.put(route('meetings.update', props.meeting.id))
    
    toast.success('Toutes les modifications ont été enregistrées avec succès');
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    toast.error('Une erreur est survenue lors de la sauvegarde');
  }
};

const showVersionHistory = ref(false)
const newComment = ref('')
const comments = ref<Comment[]>([])
const minutesVersions = ref<MinutesVersion[]>([])

const addComment = () => {
    if (!newComment.value.trim()) return

    axios.post(route('meeting.comments.store', props.meeting.id), {
        content: newComment.value
    }).then(response => {
        comments.value.unshift(response.data.comment)
        newComment.value = ''
        toast.success('Commentaire ajouté')
    }).catch(() => {
        toast.error('Erreur lors de l\'ajout du commentaire')
    })
}

const restoreVersion = (version: MinutesVersion) => {
    if (!confirm('Voulez-vous restaurer cette version ?')) return

    form.minutes.content = version.content
    showVersionHistory.value = false
    toast.success('Version restaurée')
}

const handleFileImport = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0]
    if (!file) return

    const formData = new FormData()
    formData.append('file', file)

    try {
        const response = await axios.post(
            route('minutes.import', props.meeting.id),
            formData
        )
        form.minutes.content = response.data.content
        toast.success('Document importé avec succès')
    } catch (error) {
        toast.error('Erreur lors de l\'import du document')
    }

    // Réinitialiser l'input file
    ;(event.target as HTMLInputElement).value = ''
}

// Charger les commentaires et l'historique au montage
onMounted(() => {
  console.log(props.meeting);
    if (props.meeting.id) {
      
        axios.get(route('meeting.comments.index', props.meeting.id))
            .then(response => {
                comments.value = response.data.comments
            })
            .catch(() => {
                toast.error('Erreur lors du chargement des commentaires')
            })
    }

   /* if (props.meeting.minutes?.id) {
        axios.get(route('meeting.minutes.versions', props.meeting.minutes.id))
            .then(response => {
                minutesVersions.value = response.data.versions
            })
            .catch(() => {
                toast.error('Erreur lors du chargement de l\'historique')
            })
    } */
})

// Récupérer l'utilisateur depuis Inertia
const user = computed(() => usePage().props.auth.user)

const formatStatus = (status) => {
  return getStatusText(status, 'meeting')
}

const formatRole = (role) => {
  return translateRole(role)
}

const cancelMeeting = async (id: number) => {
  if (!confirm('Êtes-vous sûr de vouloir annuler cette réunion ?')) return

  try {
    await axios.post(route('meetings.cancel', id))
    props.meeting.status = 'cancelled'
    toast.success('La réunion a été annulée')
  } catch (error) {
    toast.error('Erreur lors de l\'annulation de la réunion')
  }
}

const sendMinutes = async () => {
  try {
    await axios.post(route('minutes.send', props.meeting.id))
    toast.success('Compte rendu envoyé avec succès')
  } catch (error) {
    toast.error('Erreur lors de l\'envoi du compte rendu')
  }
}

const showNewEnrollmentModal = ref(false)
const editingEnrollment = ref(null)
const enrollmentForm = useForm({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  address: '',
  notes: ''
})

const closeEnrollmentModal = () => {
  showNewEnrollmentModal.value = false
  editingEnrollment.value = null
  enrollmentForm.reset()
}

const submitEnrollment = () => {
  if (editingEnrollment.value) {
    enrollmentForm.put(route('enrollment-requests.update', editingEnrollment.value.id), {
      onSuccess: () => {
        closeEnrollmentModal();
      }
    });
  } else {
    enrollmentForm.post(route('enrollment-requests.store', props.meeting.id), {
      onSuccess: () => {
        closeEnrollmentModal();
      }
    });
  }
}

const enrollmentRequests = computed(() => props.meeting.enrollment_requests || []);

const editEnrollment = (request) => {
  editingEnrollment.value = request
  enrollmentForm.first_name = request.first_name
  enrollmentForm.last_name = request.last_name
  enrollmentForm.phone = request.phone
  enrollmentForm.email = request.email
  enrollmentForm.address = request.address
  enrollmentForm.notes = request.notes
  showNewEnrollmentModal.value = true
}

const deleteEnrollment = (request) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette personne ?')) {
        router.delete(route('enrollment-requests.destroy', request.id), {
            onSuccess: () => {
                toast.success('Personne supprimée avec succès');
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            }
        });
    }
};

const downloadFile = (attachment: Attachment) => {
    window.location.href = route('attachments.download', attachment.id);
};

const toggleVillageRepresentatives = (villageId) => {
  if (expandedVillages.value.includes(villageId)) {
    expandedVillages.value = expandedVillages.value.filter(id => id !== villageId)
  } else {
    expandedVillages.value.push(villageId)
  }
}

const addRepresentative = (villageId) => {
  if (!meetingRepresentatives.value[villageId]) {
    meetingRepresentatives.value[villageId] = []
  }
  
  meetingRepresentatives.value[villageId].push({
    id: null,
    name: '',
    phone: '',
    role: '',
    is_expected: true,
    is_present: false,
    representative_id: null
  })
}

const removeRepresentative = (villageId, index) => {
  if (meetingRepresentatives.value[villageId]) {
    meetingRepresentatives.value[villageId].splice(index, 1)
  }
}

const getParticipantsCount = (villageId) => {
  // Compte les participants dans meetingRepresentatives
  if (meetingRepresentatives.value[villageId] && meetingRepresentatives.value[villageId].length > 0) {
    // Compter les participants enregistrés qui sont attendus
    return meetingRepresentatives.value[villageId].filter(rep => rep.is_expected).length;
  }
  
  // Si pas de participants enregistrés, utiliser les représentants du village
  const village = props.meeting.local_committee?.locality?.children?.find(v => v.id === villageId);
  if (village?.representatives && village.representatives.length) {
    return village.representatives.length;
  }
  
  return 0;
}

const getAttendingCount = (villageId) => {
  if (!meetingRepresentatives.value[villageId]) return 0;
  return meetingRepresentatives.value[villageId].filter(rep => rep.is_expected).length;
}

const saveRepresentatives = () => {
  // Préparer les données pour l'envoi
  const data = {
    representatives: meetingRepresentatives.value
  }
  
  savingRepresentatives.value = true
  
  // Envoyer les données au serveur
  axios.post(route('meetings.representatives.save', props.meeting.id), data)
    .then(response => {
      toast.success('Représentants enregistrés avec succès')
      showManageRepresentativesModal.value = false
      
      // Forcer le rechargement de la page pour afficher les changements
      window.location.reload()
    })
    .catch(error => {
      console.error('Erreur lors de l\'enregistrement des représentants:', error)
      toast.error('Erreur lors de l\'enregistrement des représentants')
    })
    .finally(() => {
      savingRepresentatives.value = false
    })
}

const closeManageRepresentativesModal = () => {
  showManageRepresentativesModal.value = false
  expandedVillages.value = []
}

const getAttendanceClass = (rep) => {
  // Vérifier si le représentant est dans la liste des participants
  const villageId = rep.locality_id || rep.localite_id
  const repName = rep.name || `${rep.first_name} ${rep.last_name}`
  
  if (!meetingRepresentatives.value[villageId]) return 'bg-slate-100 text-slate-700 border border-slate-200'
  
  const attendee = meetingRepresentatives.value[villageId].find(
    r => r.representative_id === rep.id || r.name === repName
  )
  
  if (!attendee) return 'bg-slate-100 text-slate-700 border border-slate-200'
  
  if (props.meeting.status === 'completed') {
    return attendee.is_present ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-red-100 text-red-700 border border-red-200'
  } else {
    return attendee.is_expected ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200'
  }
}

const getAttendanceStatus = (rep) => {
  const villageId = rep.locality_id || rep.localite_id
  const repName = rep.name || `${rep.first_name} ${rep.last_name}`
  
  if (!meetingRepresentatives.value[villageId]) return getStatusText('expected', 'attendance')
  
  const attendee = meetingRepresentatives.value[villageId].find(
    r => r.representative_id === rep.id || r.name === repName
  )
  
  if (!attendee) return getStatusText('expected', 'attendance')
  
  if (props.meeting.status === 'completed') {
    return attendee.is_present ? getStatusText('present', 'attendance') : getStatusText('absent', 'attendance')
  } else {
    return attendee.is_expected ? getStatusText('expected', 'attendance') : getStatusText('expected', 'attendance')
  }
}

// Charger les représentants au montage du composant
onMounted(() => {
  loadRepresentatives()
})

// Fonctions pour gérer les représentants
const loadRepresentatives = async () => {
  try {
    const response = await axios.get(route('meetings.representatives', props.meeting.id))
    meetingRepresentatives.value = {}
    
    console.log('Représentants reçus du serveur:', response.data.representatives);
    
    // Organiser les représentants par village
    if (response.data.representatives && response.data.representatives.length > 0) {
      response.data.representatives.forEach(rep => {
        if (!meetingRepresentatives.value[rep.localite_id]) {
          meetingRepresentatives.value[rep.localite_id] = []
        }
        
        meetingRepresentatives.value[rep.localite_id].push({
          id: rep.id,
          name: rep.name,
          phone: rep.phone,
          role: rep.role,
          is_expected: rep.is_expected,
          is_present: rep.is_present,
          representative_id: rep.representative_id
        })
      })
      
      console.log('Représentants organisés par village:', meetingRepresentatives.value);
    }
    
    // Pour les villages sans représentants, initialiser avec un tableau vide
    if (props.meeting.local_committee?.locality?.children) {
      props.meeting.local_committee.locality.children.forEach(village => {
        if (!meetingRepresentatives.value[village.id]) {
          meetingRepresentatives.value[village.id] = []
          
          // Ajouter les représentants du village s'ils existent
          if (village.representatives && village.representatives.length > 0) {
            village.representatives.forEach(rep => {
              // Vérifier si ce représentant est déjà ajouté
              const existingRep = meetingRepresentatives.value[village.id].find(
                r => r.representative_id === rep.id
              );
              
              if (!existingRep) {
                meetingRepresentatives.value[village.id].push({
                  id: null,
                  name: `${rep.first_name} ${rep.last_name}`,
                  phone: rep.phone,
                  role: rep.role,
                  is_expected: false,
                  is_present: false,
                  representative_id: rep.id
                })
              }
            })
          }
        }
      })
      
      console.log('Représentants finaux après ajout des représentants potentiels:', meetingRepresentatives.value);
    }
  } catch (error) {
    console.error('Erreur lors du chargement des représentants:', error)
    toast.error('Erreur lors du chargement des représentants')
  }
}

// Ajouter l'état pour les modals et la validation
const showValidationModal = ref(false)
const showSendEmailModal = ref(false)
const validationDecision = ref('validate')
const validationComments = ref('')
const emailRecipients = ref([''])

// Détecter les rôles de l'utilisateur
const isSubPrefect = computed(() => {
  if (!props.user || !props.user.roles) return false
  // Un admin a aussi les droits d'un sous-préfet
  return props.user.roles.some(role => ['sous-prefet', 'Sous-prefet'].includes(role.name))
})

const isSecretary = computed(() => {
  console.log("props.user");
  console.log(props.user.roles);
  if (!props.user || !props.user.roles) return false
  
  // Un admin a aussi les droits d'un secrétaire
  return props.user.roles.some(role => ['secretaire', 'Secrétaire', 'admin', 'Admin'].includes(role.name))
})

// Vérifier si l'utilisateur est un administrateur
const isAdmin = computed(() => {
  if (!props.user || !props.user.roles) return false
  return props.user.roles.some(role => ['admin', 'Admin'].includes(role.name))
})

// Vérifier si l'utilisateur peut éditer les minutes
const canEditMinutes = computed(() => {
  // Si la réunion est validée, personne ne peut la modifier
  if (props.meeting.status === 'validated') {
    return false;
  }
  
  // Si l'utilisateur est un sous-préfet, il ne peut pas modifier quoi que ce soit
  if (isSubPrefect.value) {
    return false;
  }
  
  // Pour les autres utilisateurs, appliquer les règles normales
  return props.meeting.minutes?.status !== 'pending_validation' && 
         props.meeting.minutes?.status !== 'validated';
})

// Fonction pour démarrer l'édition des minutes
const startEditMinutes = () => {
  editingMinutes.value = true
}

// Fonctions pour les statuts
const getStatusLabel = (status) => {
  const labels = {
    'draft': 'Brouillon',
    'published': 'Publié',
    'pending_validation': 'En attente de validation',
    'validated': 'Validé'
  }
  return labels[status] || 'Inconnu'
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonctions pour la demande de validation
const requestValidation = async () => {
  try {
    await axios.post(route('minutes.request-validation', props.meeting.minutes.id))
    toast.success('Demande de validation envoyée avec succès')
    // Actualiser les données de la réunion
    window.location.reload()
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la demande de validation')
  }
}

// Fonction pour soumettre la validation ou le rejet
const submitValidation = async () => {
  try {
    await axios.post(route('minutes.validate', props.meeting.minutes.id), {
      decision: validationDecision.value,
      validation_comments: validationComments.value
    })
    
    toast.success(validationDecision.value === 'validate' 
      ? 'Compte rendu validé avec succès'
      : 'Validation rejetée avec succès')
    
    showValidationModal.value = false
    validationComments.value = ''
    
    // Actualiser les données de la réunion
    window.location.reload()
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de la validation')
  }
}

// Fonctions pour l'envoi par email
const addRecipient = () => {
  emailRecipients.value.push('')
}

const removeRecipient = (index) => {
  emailRecipients.value.splice(index, 1)
  if (emailRecipients.value.length === 0) {
    emailRecipients.value = ['']
  }
}

const sendMinutesByEmail = async () => {
  // Filtrer les emails vides
  const recipients = emailRecipients.value.filter(email => email.trim() !== '')
  
  if (recipients.length === 0) {
    toast.error('Veuillez ajouter au moins un destinataire')
    return
  }
  
  try {
    await axios.post(route('minutes.send', props.meeting.id), {
      recipients: recipients
    })
    
    toast.success('Compte rendu envoyé avec succès')
    showSendEmailModal.value = false
    emailRecipients.value = ['']
  } catch (error) {
    console.error('Erreur:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'envoi du compte rendu')
  }
}



// Valider définitivement la réunion
const validateMeeting = async () => {
  try {
    await axios.post(
      route('meetings.validate', props.meeting.id), 
      { validation_comments: validationComments.value }
    )
    
    toast.success('Réunion validée avec succès')
    closeValidationModal()
    window.location.reload()
  } catch (error) {
    toast.error(
      error.response?.data?.message || 
      'Une erreur est survenue lors de la validation'
    )
  }
}

// Fermer la modal de validation
const closeValidationModal = () => {
  showValidationModal.value = false
  validationComments.value = ''
}

// Ajouter l'état pour l'invalidation
const showInvalidationModal = ref(false)
const invalidationComments = ref('')

// Fonction pour invalider la réunion
const invalidateMeeting = async () => {
  if (!confirm('Êtes-vous sûr de vouloir invalider cette réunion ?')) return
  
  try {
    await axios.post(
      route('meetings.invalidate', props.meeting.id),
      { validation_comments: invalidationComments.value }
    )
    
    toast.success('Réunion invalide avec succès')
    showInvalidationModal.value = false
    window.location.reload()
  } catch (error) {
    toast.error(
      error.response?.data?.message || 
      'Une erreur est survenue lors de l\'invalidation'
    )
  }
}

// Fonction pour fermer la modal d'invalidation
const closeInvalidationModal = () => {
  showInvalidationModal.value = false
  invalidationComments.value = ''
}

// Vérifier si l'utilisateur peut gérer les réunions (secrétaire ou admin)
const canManageMeeting = computed(() => {
  // Utiliser isSecretary qui inclut déjà la vérification pour admin
  return isSecretary.value;
});

const completeConfirm = async () => {
  if (!confirm('Êtes-vous sûr de vouloir marquer cette réunion comme terminée ?')) return
  
  try {
    // D'abord sauvegarder toutes les modifications (réunion + compte rendu)
    if (form.minutes.content) {
      if (!props.meeting.minutes) {
        // Création d'un nouveau compte rendu
        await axios.post(route('minutes.store', props.meeting.id), {
          content: form.minutes.content
        })
      } else {
        // Mise à jour d'un compte rendu existant
        await axios.put(route('minutes.update', props.meeting.minutes.id), {
          content: form.minutes.content,
          status: form.minutes.status
        })
      }
    }
    
    // Sauvegarder les autres modifications de la réunion
    await form.put(route('meetings.update', props.meeting.id))
    
    // Ensuite marquer la réunion comme terminée
    await axios.post(route('meetings.complete', props.meeting.id))
    props.meeting.status = 'completed'
    toast.success('La réunion a été marquée comme terminée et toutes les modifications ont été enregistrées')
    window.location.reload()
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors de la mise à jour de la réunion')
  }
}

const unpublishConfirm = async () => {
  if (!confirm('Êtes-vous sûr de vouloir dépublier cette réunion ? Elle reviendra à l\'état planifiée.')) return
  
  try {
    await axios.post(route('meetings.unpublish', props.meeting.id))
    props.meeting.status = 'scheduled'
    toast.success('La réunion a été dépublicée avec succès')
    window.location.reload()
  } catch (error) {
    console.error('Erreur:', error)
    if (error.response?.data?.message) {
      toast.error(error.response.data.message)
    } else {
      toast.error('Erreur lors de la dépublication de la réunion')
    }
  }
}

// Ajouter la fonction pour rediriger vers la page de gestion des listes de présence
const manageAttendanceList = () => {
  window.location.href = route('meetings.attendance', props.meeting.id);
}

// Formulaire pour les enrôlements
const enrollmentsForm = ref({
  target_enrollments: props.meeting.target_enrollments || 0,
  actual_enrollments: props.meeting.actual_enrollments || 0
})

const enrollmentsLoading = ref(false)

// Fonction pour valider que les enrôlements sont cohérents
const validateEnrollments = () => {
  // S'assurer que les valeurs sont des nombres
  enrollmentsForm.value.target_enrollments = parseInt(enrollmentsForm.value.target_enrollments) || 0
  enrollmentsForm.value.actual_enrollments = parseInt(enrollmentsForm.value.actual_enrollments) || 0
  
  // Vérifier que les enrôlements actuels ne dépassent pas la cible
  if (enrollmentsForm.value.actual_enrollments > enrollmentsForm.value.target_enrollments) {
    enrollmentsForm.value.actual_enrollments = enrollmentsForm.value.target_enrollments
  }
}

// Fonction pour mettre à jour les enrôlements
const updateEnrollments = async () => {
  enrollmentsLoading.value = true
  try {
    const response = await axios.patch(route('meetings.update-enrollments', props.meeting.id), {
      target_enrollments: enrollmentsForm.value.target_enrollments,
      actual_enrollments: enrollmentsForm.value.actual_enrollments
    })
    
    // Mettre à jour les valeurs dans l'objet meeting
    props.meeting.target_enrollments = enrollmentsForm.value.target_enrollments
    props.meeting.actual_enrollments = enrollmentsForm.value.actual_enrollments
    
    toast.success('Enrôlements mis à jour avec succès')
  } catch (error) {
    if (error.response?.status === 403) {
      toast.error('Vous n\'êtes pas autorisé à modifier les enrôlements')
    } else {
      toast.error('Erreur lors de la mise à jour des enrôlements')
    }
    console.error(error)
  } finally {
    enrollmentsLoading.value = false
  }
}

// Formulaire pour l'ajout de participants aux inscriptions

// Variables pour les représentants des villages

// Autres variables pour les modals

const handleMeetingUpdated = (updatedMeeting) => {
  // Émettre un événement pour mettre à jour la réunion
  router.reload()
}

function getAttendanceStatusClass(villageId) {
  if (!meetingRepresentatives.value[villageId]) return 'bg-slate-100 text-slate-700 border border-slate-200'
  
  const attendee = meetingAttendees.value.find(a => a.localite_id === villageId)
  if (!attendee) return 'bg-slate-100 text-slate-700 border border-slate-200'
  
  if (attendee.attendance_status === 'replaced') {
    return attendee.is_present ? 'bg-emerald-100 text-emerald-700 border border-emerald-200' : 'bg-red-100 text-red-700 border border-red-200'
  }
  return attendee.is_expected ? 'bg-blue-100 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200'
}

const showRescheduleModal = ref(false)
const rescheduleForm = useForm({
  date: props.meeting.start_datetime ? new Date(props.meeting.start_datetime).toISOString().slice(0, 16) : '',
  reason: ''
})
const rescheduleLoading = ref(false)

const submitReschedule = () => {
  rescheduleLoading.value = true
  axios.post(route('meetings.reschedule.submit', props.meeting.id), rescheduleForm.data())
    .then(response => {
      toast.success('Réunion replaniée avec succès')
      showRescheduleModal.value = false
      rescheduleForm.reset()
      rescheduleLoading.value = false
      window.location.reload()
    })
    .catch(error => {
      toast.error('Erreur lors de la réplanification de la réunion')
      rescheduleLoading.value = false
    })
}

const closeRescheduleModal = () => {
  showRescheduleModal.value = false
  rescheduleForm.reset()
  rescheduleLoading.value = false
}

// Fonctions pour la liste de présence
const showPhotoModal = (attendee) => {
  selectedAttendee.value = attendee
  photoModalOpen.value = true
}

const closePhotoModal = () => {
  photoModalOpen.value = false
  selectedAttendee.value = null
}

// Fonction pour formater l'heure
const formatTime = (time) => {
  if (!time) return ''
  return new Date(time).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Fonction pour formater la localisation
const formatLocation = (location) => {
  if (!location) return 'Non disponible'
  
  // Vérifier que latitude et longitude sont des nombres valides
  const lat = parseFloat(location.latitude)
  const lng = parseFloat(location.longitude)
  
  if (isNaN(lat) || isNaN(lng)) {
    return 'Coordonnées invalides'
  }
  
  return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
}

// Fonction pour compter les participants par statut
const getAttendanceCount = (status) => {
  if (!props.meeting.attendees) return 0
  return props.meeting.attendees.filter(attendee => attendee.attendance_status === status).length
}

// Variables pour le modal de photo
const photoModalOpen = ref(false)
const selectedAttendee = ref(null)

// Variables pour la pagination et la recherche
const searchQuery = ref('')
const statusFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = 10

// Computed properties pour la pagination
const filteredAttendees = computed(() => {
  if (!props.meeting.attendees) return []
  
  return props.meeting.attendees.filter(attendee => {
    const nameMatch = !searchQuery.value || 
      attendee.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      attendee.replacement_name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      attendee.role?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      attendee.village?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
    
    const statusMatch = !statusFilter.value || attendee.attendance_status === statusFilter.value
    
    return nameMatch && statusMatch
  })
})

const totalPages = computed(() => Math.ceil(filteredAttendees.value.length / itemsPerPage))

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage)

const endIndex = computed(() => Math.min(startIndex.value + itemsPerPage, filteredAttendees.value.length))

const paginatedAttendees = computed(() => {
  return filteredAttendees.value.slice(startIndex.value, endIndex.value)
})

const visiblePages = computed(() => {
  const pages = []
  const maxVisible = 5
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2))
  let end = Math.min(totalPages.value, start + maxVisible - 1)
  
  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

// Fonctions pour la pagination
const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const goToPage = (page) => {
  currentPage.value = page
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  currentPage.value = 1
}

// Watcher pour réinitialiser la page quand les filtres changent
watch([searchQuery, statusFilter], () => {
  currentPage.value = 1
})
</script>

<style scoped>
.ghost {
    opacity: 0.5;
    background: #c8ebfb;
}

.handle {
    cursor: move;
    cursor: -webkit-grabbing;
}
</style>
