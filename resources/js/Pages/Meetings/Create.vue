<template>
  <Head title="Planifier Réunion" />

  <AppLayout title="Planifier Réunion">
    <div class="space-y-6">
      <form @submit.prevent="createMeeting">
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div class="sm:col-span-6">
                <label for="title" class="block text-sm font-medium text-gray-700">Titre</label>
                <input type="text" v-model="meeting.title" id="title" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="scheduled_date" class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" v-model="meeting.scheduled_date" id="scheduled_date" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">Heure</label>
                <input type="time" v-model="meeting.scheduled_time" id="scheduled_time" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="localCommittee" class="block text-sm font-medium text-gray-700">Comité Local</label>
                <select v-model="meeting.localCommittee" id="localCommittee" class="mt-1 block w-full" required>
                  <option v-for="committee in localCommittees" :key="committee.id" :value="committee.id">
                    {{ committee.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
          <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg">
              Planifier
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { useToast } from '@/Composables/useToast'
import { defineProps } from 'vue'
import axios from 'axios'

interface LocalCommittee {
  id: number;
  name: string;
}

interface Meeting {
  title: string;
  scheduled_date: string;
  scheduled_time: string;
  localCommittee: number | null;
}

const props = defineProps<{ localCommittees: LocalCommittee[] }>()

const meeting = ref<Meeting>({
  title: '',
  scheduled_date: '',
  scheduled_time: '',
  localCommittee: null
})

const toast = useToast()

const createMeeting = async () => {
  try {
    await axios.post(route('meetings.store'), meeting.value)
    toast.success('La réunion a été planifiée avec succès')
    window.location.href = route('meetings.index')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors de la planification de la réunion')
  }
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
