<template>
  <Head title="Créer plusieurs réunions" />

  <AppLayout title="Créer plusieurs réunions">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Planification de réunions multiples</h2>
            <p class="text-sm text-gray-600">
              Créez plusieurs réunions pour un comité local. Chaque comité doit faire une réunion par mois pendant 6 mois.
            </p>
          </div>

          <form @submit.prevent="submit" enctype="multipart/form-data">
            <!-- Informations communes -->
            <div class="mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Comité local</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="local_committee_id" value="Comité local" />
                  <div v-if="userCommittee" class="mt-1">
                    <!-- Comité local fixé pour le secrétaire connecté -->
                    <div class="flex items-center p-3 bg-blue-50 border border-blue-200 rounded-md">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-blue-800">{{ userCommittee.name }}</p>
                        <p class="text-xs text-blue-600">Comité local de votre localité</p>
                      </div>
                    </div>
                    <input type="hidden" v-model="form.local_committee_id" />
                  </div>
                  <select
                    v-else
                    id="local_committee_id"
                    v-model="form.local_committee_id"
                    class="mt-1 block w-full rounded-md border-gray-300"
                    required
                  >
                    <option value="">Sélectionner un comité local</option>
                    <option
                      v-for="committee in localCommittees"
                      :key="committee.id"
                      :value="committee.id"
                    >
                      {{ committee.name }}
                    </option>
                  </select>
                  <InputError :message="form.errors.local_committee_id" class="mt-2" />
                </div>
              </div>
            </div>

            <!-- Import Excel/CSV -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Import Excel/CSV</h3>
              <div class="bg-gray-50 p-4 rounded-lg border">
                <div class="mb-4">
                  <InputLabel for="import_file" value="Fichier Excel/CSV" />
                  <input
                    id="import_file"
                    type="file"
                    @change="handleFileImport"
                    accept=".xlsx,.xls,.csv"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  />
                  <p class="text-sm text-gray-600 mt-2">
                    Format attendu: colonnes "titre", "date", "heure", "lieu"
                  </p>
                </div>
                <div class="flex space-x-2">
                  <button
                    type="button"
                    @click="importFile"
                    :disabled="!selectedFile || form.processing"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium disabled:opacity-50"
                  >
                    Importer
                  </button>
                  <button
                    type="button"
                    @click="downloadTemplate"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md text-sm font-medium"
                  >
                    Télécharger le modèle
                  </button>
                </div>
              </div>
            </div>

            <!-- Pièces jointes communes -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Pièces jointes communes</h3>
              <div class="bg-gray-50 p-4 rounded-lg border">
                <div class="mb-4">
                  <InputLabel for="common_attachments" value="Fichiers à joindre à toutes les réunions" />
                  <input
                    id="common_attachments"
                    type="file"
                    @change="handleCommonAttachments"
                    multiple
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                  />
                  <p class="text-sm text-gray-600 mt-2">
                    Ces fichiers seront joints à toutes les réunions créées
                  </p>
                </div>
                <div v-if="commonAttachments.length > 0" class="mt-4">
                  <h4 class="text-sm font-medium text-gray-700 mb-2">Fichiers sélectionnés:</h4>
                  <div class="space-y-2">
                    <div v-for="(file, index) in commonAttachments" :key="index" 
                         class="flex items-center justify-between bg-white p-2 rounded border">
                      <span class="text-sm text-gray-600">{{ file.name }}</span>
                      <button
                        type="button"
                        @click="removeCommonAttachment(index)"
                        class="text-red-600 hover:text-red-800"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Liste des réunions -->
            <div class="mb-8">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Réunions à créer</h3>
                <button
                  type="button"
                  @click="addMeeting"
                  class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md text-sm font-medium inline-flex items-center"
                >
                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  Ajouter une réunion
                </button>
              </div>

              <!-- En-tête du tableau -->
              <div class="bg-gray-50 px-4 py-3 rounded-t-lg border-b">
                <div class="grid grid-cols-12 gap-4 text-sm font-medium text-gray-700">
                  <div class="col-span-4">Titre de la réunion</div>
                  <div class="col-span-3">Date</div>
                  <div class="col-span-3">Heure</div>
                  <div class="col-span-2">Lieu</div>
                  <div class="col-span-1">Actions</div>
                </div>
              </div>

              <!-- Lignes des réunions -->
              <div class="space-y-2">
                <div v-for="(meeting, index) in form.meetings" :key="index" 
                     class="bg-white p-4 rounded-lg border">
                  <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-4">
                      <TextInput
                        :id="`meeting-${index}-title`"
                        v-model="meeting.title"
                        type="text"
                        class="block w-full"
                        placeholder="Titre de la réunion"
                        required
                      />
                    </div>
                    <div class="col-span-3">
                      <TextInput
                        :id="`meeting-${index}-date`"
                        v-model="meeting.scheduled_date"
                        type="date"
                        class="block w-full"
                        required
                      />
                    </div>
                    <div class="col-span-3">
                      <TextInput
                        :id="`meeting-${index}-time`"
                        v-model="meeting.scheduled_time"
                        type="time"
                        class="block w-full"
                        required
                      />
                    </div>
                    <div class="col-span-2">
                      <TextInput
                        :id="`meeting-${index}-location`"
                        v-model="meeting.location"
                        type="text"
                        class="block w-full"
                        placeholder="Lieu"
                        required
                      />
                    </div>
                    <div class="col-span-1">
                      <button
                        type="button"
                        @click="removeMeeting(index)"
                        class="text-red-600 hover:text-red-800 p-1"
                        :disabled="form.meetings.length <= 1"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="form.meetings.length === 0" class="text-center py-8 text-gray-500">
                <p>Aucune réunion ajoutée. Cliquez sur "Ajouter une réunion" pour commencer.</p>
              </div>
            </div>

            <div class="flex justify-end mt-6">
              <button
                type="button"
                @click="cancel"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-md text-sm font-medium mr-2"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium"
                :disabled="form.processing || form.meetings.length === 0"
              >
                Créer {{ form.meetings.length }} réunion(s)
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed, onMounted } from 'vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

interface LocalCommittee {
  id: number;
  name: string;
}

const props = defineProps<{ 
  localCommittees: LocalCommittee[]
  userCommittee?: LocalCommittee | null
  flash?: {
    imported_meetings?: any[]
    selected_committee?: string
    success?: string
    bulk_import_id?: number
  }
}>()

const form = useForm({
  local_committee_id: '',
  bulk_import_id: null as number | null,
  meetings: [{
    title: '',
    scheduled_date: '',
    scheduled_time: '',
    location: ''
  }]
})

const selectedFile = ref(null)
const commonAttachments = ref([])

// Pré-remplir avec les données importées si disponibles
onMounted(() => {
  // Pré-remplir le comité local de l'utilisateur connecté s'il existe
  if (props.userCommittee) {
    form.local_committee_id = props.userCommittee.id
  }
  
  if (props.flash?.imported_meetings) {
    form.meetings = props.flash.imported_meetings.map((meeting: any) => ({
      ...meeting,
      attachments: []
    }))
  }
  if (props.flash?.selected_committee) {
    form.local_committee_id = props.flash.selected_committee
  }
  if (props.flash?.bulk_import_id) {
    form.bulk_import_id = props.flash.bulk_import_id
  }
})

// Ajouter une réunion
const addMeeting = () => {
  form.meetings.push({
    title: '',
    scheduled_date: '',
    scheduled_time: '',
    location: ''
  })
}

// Supprimer une réunion
const removeMeeting = (index: number) => {
  if (form.meetings.length > 1) {
    form.meetings.splice(index, 1)
  }
}

const submit = () => {
  // Utiliser la route avec pièces jointes si des fichiers communs sont présents
  const hasCommonAttachments = commonAttachments.value.length > 0
  
  if (hasCommonAttachments) {
    // Créer un FormData avec les pièces jointes communes
    const formData = new FormData()
    formData.append('local_committee_id', form.local_committee_id)
    if (form.bulk_import_id) {
      formData.append('bulk_import_id', form.bulk_import_id.toString())
    }
    
    // Ajouter chaque réunion individuellement
    form.meetings.forEach((meeting, index) => {
      formData.append(`meetings[${index}][title]`, meeting.title)
      formData.append(`meetings[${index}][scheduled_date]`, meeting.scheduled_date)
      formData.append(`meetings[${index}][scheduled_time]`, meeting.scheduled_time)
      formData.append(`meetings[${index}][location]`, meeting.location)
    })
    
    // Ajouter les pièces jointes communes
    commonAttachments.value.forEach((file, index) => {
      formData.append(`common_attachments[${index}]`, file)
    })
    
    router.post(route('meetings.store-multiple-with-attachments'), formData)
  } else {
    form.post(route('meetings.store-multiple'))
  }
}

const cancel = () => {
  window.history.back()
}

// Gestion de l'import de fichier
const handleFileImport = (event: Event) => {
  const target = event.target as HTMLInputElement
  selectedFile.value = target.files?.[0] || null
}

const importFile = () => {
  if (!selectedFile.value) {
    alert('Veuillez sélectionner un fichier à importer.')
    return
  }

  // Si l'utilisateur a un comité local fixé, l'utiliser automatiquement
  if (props.userCommittee && !form.local_committee_id) {
    form.local_committee_id = props.userCommittee.id
  }

  if (!form.local_committee_id) {
    alert('Veuillez sélectionner un comité local avant d\'importer.')
    return
  }

  // Créer un nouveau FormData pour l'import
  const importFormData = new FormData()
  importFormData.append('file', selectedFile.value)
  importFormData.append('local_committee_id', form.local_committee_id)

  // Utiliser Inertia pour l'import
  router.post(route('meetings.import'), importFormData, {
    preserveState: false,
    onSuccess: (page) => {
      console.log('Import réussi:', page.props.flash)
      
      // Pré-remplir avec les données importées
      if (page.props.flash?.imported_meetings) {
        form.meetings = page.props.flash.imported_meetings.map((meeting: any) => ({
          ...meeting
        }))
      }
      if (page.props.flash?.selected_committee) {
        form.local_committee_id = page.props.flash.selected_committee
      }
      selectedFile.value = null
      
      // Afficher un message de succès
      if (page.props.flash?.success) {
        alert(page.props.flash.success)
      }
    },
    onError: (errors) => {
      console.error('Erreurs d\'import:', errors)
      if (errors.file) {
        alert('Erreur lors de l\'import: ' + errors.file)
      }
    }
  })
}

const downloadTemplate = () => {
  // Rediriger vers la route de téléchargement du template
  window.location.href = route('templates.meetings')
}

// Gestion des pièces jointes communes
const handleCommonAttachments = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = target.files
  if (files) {
    commonAttachments.value = Array.from(files)
  }
}

const removeCommonAttachment = (index: number) => {
  commonAttachments.value.splice(index, 1)
}
</script>

<style scoped>
.bg-primary-600 {
  background-color: rgb(79, 70, 229)
}
.hover\:bg-primary-700:hover {
  background-color: rgb(67, 56, 202)
}
.focus\:ring-primary-500:focus {
  --tw-ring-color: rgb(99, 102, 241)
}
</style> 