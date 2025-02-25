<template>
  <Head title="Nouvelle réunion" />

  <AppLayout title="Nouvelle réunion">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Nouvelle réunion
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <form @submit.prevent="submit" class="p-6 space-y-6">
            <div>
              <InputLabel for="title" value="Titre" />
              <TextInput
                id="title"
                v-model="form.title"
                type="text"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.title" class="mt-2" />
            </div>

            <div>
              <InputLabel for="description" value="Description" />
              <TextArea
                id="description"
                v-model="form.description"
                class="mt-1 block w-full"
                rows="4"
              />
              <InputError :message="form.errors.description" class="mt-2" />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <InputLabel for="start_datetime" value="Date et heure de début" />
                <TextInput
                  id="start_datetime"
                  v-model="form.start_datetime"
                  type="datetime-local"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.start_datetime" class="mt-2" />
              </div>

              <div>
                <InputLabel for="end_datetime" value="Date et heure de fin" />
                <TextInput
                  id="end_datetime"
                  v-model="form.end_datetime"
                  type="datetime-local"
                  class="mt-1 block w-full"
                  required
                />
                <InputError :message="form.errors.end_datetime" class="mt-2" />
              </div>
            </div>

            <div>
              <InputLabel for="location" value="Lieu" />
              <TextInput
                id="location"
                v-model="form.location"
                type="text"
                class="mt-1 block w-full"
              />
              <InputError :message="form.errors.location" class="mt-2" />
            </div>

            <div>
              <InputLabel for="people_to_enroll_count" value="Nombre de Personnes non enrôlées" />
              <TextInput
                id="people_to_enroll_count"
                v-model="form.people_to_enroll_count"
                type="number"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.people_to_enroll_count" class="mt-2" />
            </div>

            <div>
              <InputLabel for="people_enrolled_count" value="Nombre de personnes enrôlées" />
              <TextInput
                id="people_enrolled_count"
                v-model="form.people_enrolled_count"
                type="number"
                class="mt-1 block w-full"
                required
              />
              <InputError :message="form.errors.people_enrolled_count" class="mt-2" />
            </div>

            <div class="mt-6">
              <h3 class="text-lg font-medium text-gray-900">Participants</h3>

              <div class="mt-4">
                <h4 class="font-medium text-gray-700">Comité local</h4>
                <div class="mt-2">
                  <select
                    v-model="form.local_committee_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    required
                  >
                    <option value="">Sélectionnez un comité</option>
                    <option v-for="committee in committees" :key="committee.id" :value="committee.id">
                      {{ committee.name }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="mt-6">
                <h4 class="font-medium text-gray-700">Invités externes</h4>
                <div class="mt-2 space-y-4">
                  <div v-for="(guest, index) in form.guests" :key="index" class="flex items-start space-x-4">
                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Nom</label>
                      <TextInput
                        v-model="guest.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Nom de l'invité"
                        required
                      />
                    </div>

                    <div class="flex-1">
                      <label class="block text-sm font-medium text-gray-700">Email</label>
                      <TextInput
                        v-model="guest.email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="email@example.com"
                        required
                      />
                    </div>

                    <button
                      type="button"
                      @click="removeGuest(index)"
                      class="mt-6 p-2 text-red-600 hover:text-red-800"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>

                  <button
                    type="button"
                    @click="addGuest"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                  >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Ajouter un invité
                  </button>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <h3 class="text-lg font-medium text-gray-900">Ordre du jour</h3>
              <div class="mt-4 space-y-4">
                <div v-for="(item, index) in form.agenda" :key="index" class="flex items-start space-x-4 p-4 border rounded-lg">
                  <div class="flex-1">
                    <h4 class="font-medium">{{ item.title }}</h4>
                    <p class="text-sm text-gray-600">{{ item.description }}</p>
                    <div class="mt-1 text-sm text-gray-500">
                      Durée : {{ item.duration_minutes }} minutes
                    </div>
                  </div>
                  <button
                    type="button"
                    @click="removeAgendaItem(index)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>

                <button
                  type="button"
                  @click="showNewAgendaItemModal = true"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <PlusIcon class="h-5 w-5 mr-2" />
                  Ajouter un point
                </button>
              </div>
            </div>

            <div class="flex items-center justify-end mt-4">
              <PrimaryButton class="ml-4" :disabled="form.processing">
                Créer la réunion
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal pour ajouter un point à l'ordre du jour -->
    <Modal :show="showNewAgendaItemModal" @close="showNewAgendaItemModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          Nouveau point à l'ordre du jour
        </h3>
        <form @submit.prevent="addAgendaItem" class="space-y-4">
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

          <div>
            <InputLabel for="agenda-duration" value="Durée (minutes)" />
            <TextInput
              id="agenda-duration"
              v-model="agendaForm.duration_minutes"
              type="number"
              class="mt-1 block w-full"
              min="1"
              required
            />
          </div>

          <div class="flex justify-end space-x-3">
            <SecondaryButton @click="showNewAgendaItemModal = false">
              Annuler
            </SecondaryButton>
            <PrimaryButton type="submit">
              Ajouter
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Modal from '@/Components/Modal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextArea from '@/Components/TextArea.vue'
import TextInput from '@/Components/TextInput.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface User {
  id: number
  name: string
  email: string
}

interface Member {
  id: number
  user_id: number
  role: string
  user: User
}

interface Committee {
  id: number
  name: string
  locality_name: string
}

interface Props {
  committees: Committee[]
}

const props = defineProps<Props>()

const form = useForm({
  title: '',
  description: '',
  start_datetime: '',
  end_datetime: '',
  location: '',
  local_committee_id: '',
  people_to_enroll_count: 0,
  people_enrolled_count: 0,
  guests: [{
    name: '',
    email: ''
  }],
  agenda: []
})

const showNewAgendaItemModal = ref(false)
const editingAgendaItem = ref(null)

const agendaForm = useForm({
  title: '',
  description: '',
  duration_minutes: '',
  presenter_id: null
})

function addGuest() {
  form.guests.push({
    name: '',
    email: ''
  })
}

function removeGuest(index: number) {
  form.guests.splice(index, 1)
}

function addAgendaItem() {
  form.agenda.push({
    title: agendaForm.title,
    description: agendaForm.description,
    duration_minutes: agendaForm.duration_minutes,
    presenter_id: agendaForm.presenter_id
  })
  agendaForm.reset()
  showNewAgendaItemModal.value = false
}

function removeAgendaItem(index: number) {
  form.agenda.splice(index, 1)
}

function submit() {
  form.post(route('meetings.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
    }
  })
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

select[multiple] {
  min-height: 200px;
}

select[multiple] option {
  padding: 0.5rem;
  margin: 0.25rem 0;
}

select[multiple] optgroup {
  font-weight: 600;
  color: #4B5563;
  padding: 0.5rem 0;
}

/* Style pour les options sélectionnées */
select[multiple] option:checked {
  background: linear-gradient(0deg, #4F46E5 0%, #4F46E5 100%);
  color: white;
}

/* Style pour le hover des options */
select[multiple] option:hover {
  background-color: #F3F4F6;
}
</style>
