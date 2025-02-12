<template>
  <AppLayout :title="meeting.title">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ meeting.title }}
        </h2>
        <div class="flex space-x-4">
          <span
            :class="{
              'bg-yellow-100 text-yellow-800': meeting.status === 'planned',
              'bg-green-100 text-green-800': meeting.status === 'completed',
              'bg-blue-100 text-blue-800': meeting.status === 'ongoing',
              'bg-red-100 text-red-800': meeting.status === 'cancelled'
            }"
            class="px-3 py-1 rounded-full text-sm font-medium"
          >
            {{ meeting.status }}
          </span>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Informations de la réunion -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Détails</h3>
                <div class="mt-4 space-y-2">
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Date :</span>
                    {{ formatDateTime(meeting.start_datetime) }} - {{ formatDateTime(meeting.end_datetime) }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Lieu :</span>
                    {{ meeting.location || 'Non spécifié' }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Organisateur :</span>
                    {{ meeting.organizer?.name }}
                  </p>
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

        <!-- Ordre du jour -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Ordre du jour</h3>
              <button
                @click="showNewAgendaItemModal = true"
                class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
              >
                Ajouter un point
              </button>
            </div>

            <draggable
              v-model="form.agenda"
              class="space-y-4 mt-4"
              handle=".handle"
              v-bind="dragOptions"
              @end="updateAgendaOrder"
              item-key="id"
            >
              <template #item="{ element: item }">
                <div class="flex items-start space-x-4 p-4 border rounded-lg bg-white">
                  <div class="handle cursor-move text-gray-400 flex flex-col justify-center">
                    <ChevronUpIcon class="h-5 w-5" />
                    <ChevronDownIcon class="h-5 w-5" />
                  </div>
                  <div class="flex-grow">
                    <div class="flex justify-between">
                      <h4 class="font-medium">{{ item.title }}</h4>
                      <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">{{ item.duration_minutes }} min</span>
                        <button
                          @click="editAgendaItem(item)"
                          class="text-gray-400 hover:text-gray-600"
                        >
                          <PencilIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ item.description }}</p>
                    <div class="mt-2 text-sm text-gray-500">
                      Présentateur : {{ item.presenter?.name || 'Non assigné' }}
                    </div>
                  </div>
                </div>
              </template>
            </draggable>
          </div>
        </div>

        <!-- Participants -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900">Participants</h3>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div
                v-for="participant in meeting.participants"
                :key="participant.id"
                class="flex items-center space-x-3"
              >
                <div class="flex-shrink-0">
                  <img
                    :src="participant.profile_photo_url"
                    :alt="participant.name"
                    class="h-8 w-8 rounded-full"
                  >
                </div>
                <div class="flex-grow">
                  <p class="text-sm font-medium text-gray-900">{{ participant.name }}</p>
                  <p class="text-sm text-gray-500">{{ participant.email }}</p>
                </div>
                <div>
                  <span
                    :class="{
                      'bg-yellow-100 text-yellow-800': participant.pivot.status === 'pending',
                      'bg-green-100 text-green-800': participant.pivot.status === 'accepted',
                      'bg-red-100 text-red-800': participant.pivot.status === 'declined'
                    }"
                    class="px-2 py-1 text-xs rounded-full"
                  >
                    {{ participant.pivot.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pièces jointes -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Pièces jointes</h3>
              <div class="flex items-center space-x-2">
                <label
                  class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 cursor-pointer"
                >
                  Ajouter un fichier
                  <input
                    type="file"
                    class="hidden"
                    @change="uploadFile"
                    ref="fileInput"
                  >
                </label>
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <PaperClipIcon class="h-5 w-5 text-gray-400" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">
                      {{ attachment.name }}
                    </p>
                    <p class="text-xs text-gray-500">
                      {{ formatFileSize(attachment.size) }} - Ajouté par {{ attachment.uploader?.name || 'Utilisateur inconnu' }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <a
                    :href="route('attachments.download', attachment.id)"
                    class="text-indigo-600 hover:text-indigo-800"
                  >
                    <DocumentIcon class="h-5 w-5" />
                  </a>
                  <button
                    @click="deleteAttachment(attachment)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Compte rendu -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Compte rendu</h3>
              <div class="flex space-x-3">
                <!-- Masquer temporairement le bouton d'historique -->
                <!-- <button
                  @click="showVersionHistory = true"
                  class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-200"
                >
                  <ClockIcon class="h-5 w-5 mr-2" />
                  Historique
                </button> -->

                <!-- Import Word -->
                <label class="cursor-pointer inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                  <DocumentArrowUpIcon class="w-5 h-5 mr-2" />
                  Importer un fichier Word
                  <input
                    type="file"
                    class="hidden"
                    accept=".doc,.docx"
                    @change="handleFileImport"
                  >
                </label>

                <!-- Bouton d'édition -->
                <button
                  v-if="!editingMinutes"
                  @click="editingMinutes = true"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                >
                  <PencilIcon class="h-5 w-5 mr-2" />
                  Éditer
                </button>
              </div>
            </div>

            <div v-if="editingMinutes">
              <RichTextEditor
                v-model="form.minutes.content"
                placeholder="Rédigez le compte rendu ici..."
              />
              
              <div class="mt-4 flex justify-end space-x-3">
                <button
                  @click="cancelEditMinutes"
                  class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50"
                >
                  Annuler
                </button>
                <button
                  @click="saveMinutes"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700"
                  :disabled="form.processing"
                >
                  Enregistrer
                </button>
                <button
                  v-if="!props.meeting.minutes?.status || props.meeting.minutes?.status === 'draft'"
                  @click="publishMinutes"
                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                  :disabled="form.processing"
                >
                  Publier
                </button>
              </div>
            </div>
            <div v-else class="prose max-w-none" v-html="form.minutes.content || 'Aucun compte rendu'" />
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

    <!-- Boutons d'action globaux -->
    <div class="fixed bottom-4 right-4 flex space-x-3">
      <!-- Bouton d'export -->
      <a
        :href="route('meetings.export', meeting.id)"
        class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg shadow-lg hover:bg-blue-700 transition-colors"
        target="_blank"
      >
        <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
        Exporter en PDF
      </a>

      <!-- Bouton de sauvegarde -->
      <button
        @click="saveAll"
        class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg shadow-lg hover:bg-green-700 transition-colors"
        :disabled="form.processing"
      >
        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Enregistrer les modifications</span>
      </button>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import TextArea from '@/Components/TextArea.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import draggable from 'vuedraggable/src/vuedraggable'
import axios from 'axios'
import {
    PencilIcon,
    TrashIcon,
    PlusIcon,
    DocumentIcon,
    PaperClipIcon,
    ChevronUpIcon,
    ChevronDownIcon,
    DocumentArrowUpIcon,
    ClockIcon,
    DocumentArrowDownIcon
} from '@heroicons/vue/24/outline'
import RichTextEditor from '@/Components/RichTextEditor.vue'
import { useToast } from '@/Composables/useToast'

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

const props = defineProps<{
    meeting: {
        id: number;
        title: string;
        description: string;
        start_datetime: string;
        end_datetime: string;
        location: string;
        status: string;
        organizer: {
            name: string;
        };
        participants: any[];
        agenda: AgendaItem[];
        attachments: any[];
    };
}>();

const showNewAgendaItemModal = ref(false)
const editingAgendaItem = ref(null)
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
  return new Date(datetime).toLocaleString()
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
const fileInput = ref(null)

const attachments = ref(props.meeting.attachments || [])

const uploadFile = async () => {
  const file = fileInput.value.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await axios.post(
      route('attachments.store', props.meeting.id),
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )
    
    attachments.value.push(response.data.attachment)
    
    form.attachments = attachments.value

    toast.success('Fichier ajouté avec succès')
  } catch (error) {
    console.error('Erreur lors de l\'upload:', error)
    toast.error(error.response?.data?.message || 'Erreur lors de l\'upload du fichier')
  }

  fileInput.value.value = ''
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const deleteAttachment = async (attachment) => {
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

const saveMinutes = () => {
  if (props.meeting.minutes) {
    form.put(route('minutes.update', props.meeting.minutes.id), {
      onSuccess: () => {
        editingMinutes.value = false
      }
    })
  } else {
    form.post(route('minutes.store', props.meeting.id), {
      onSuccess: () => {
        editingMinutes.value = false
      }
    })
  }
}

const publishMinutes = () => {
  form.minutes.status = 'published'
  saveMinutes()
}

const cancelEditMinutes = () => {
  editingMinutes.value = false
  form.reset()
  form.minutes.content = props.meeting.minutes.content
}

// Sauvegarde globale
const saveAll = () => {
  form.put(route('meetings.update', props.meeting.id), {
    onSuccess: () => {
      toast.success('Réunion mise à jour avec succès');
    },
    onError: () => {
      toast.error('Une erreur est survenue');
    }
  });
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
    if (props.meeting.id) {
        axios.get(route('meeting.comments.index', props.meeting.id))
            .then(response => {
                comments.value = response.data.comments
            })
            .catch(() => {
                toast.error('Erreur lors du chargement des commentaires')
            })
    }

    if (props.meeting.minutes?.id) {
        axios.get(route('meeting.minutes.versions', props.meeting.minutes.id))
            .then(response => {
                minutesVersions.value = response.data.versions
            })
            .catch(() => {
                toast.error('Erreur lors du chargement de l\'historique')
            })
    }
})

// Récupérer l'utilisateur depuis Inertia
const user = computed(() => usePage().props.auth.user)
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