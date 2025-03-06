<template>
  <Head title="Modifier Réunion" />

  <AppLayout title="Modifier Réunion">
    <div class="space-y-6">
      <form @submit.prevent="updateMeeting">
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div class="sm:col-span-6">
                <label for="title" class="block text-sm font-medium text-gray-700">Titre</label>
                <input type="text" v-model="meeting.title" id="title" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea v-model="meeting.description" id="description" class="mt-1 block w-full" rows="3"></textarea>
              </div>

              <div class="sm:col-span-6">
                <label for="location" class="block text-sm font-medium text-gray-700">Lieu</label>
                <input type="text" v-model="meeting.location" id="location" class="mt-1 block w-full" required />
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
              Modifier
            </button>
          </div>
        </div>
      </form>

      <form @submit.prevent="rescheduleMeeting">
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div class="sm:col-span-6">
                <label for="scheduled_date" class="block text-sm font-medium text-gray-700">Nouvelle Date</label>
                <input type="date" v-model="meeting.scheduled_date" id="scheduled_date" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">Nouvelle Heure</label>
                <input type="time" v-model="meeting.scheduled_time" id="scheduled_time" class="mt-1 block w-full" required />
              </div>

              <div class="sm:col-span-6">
                <label for="reason" class="block text-sm font-medium text-gray-700">Motif du Report</label>
                <textarea v-model="meeting.reason" id="reason" class="mt-1 block w-full" rows="3" required></textarea>
              </div>
            </div>
          </div>
          <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg">
              Reporter
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
  id: number;
  title: string;
  description: string;
  location: string;
  scheduled_date: string;
  scheduled_time: string;
  localCommittee: number | null;
  reason: string;
}

const props = defineProps<{ meeting: Meeting, localCommittees: LocalCommittee[] }>()

const meeting = ref<Meeting>({ ...props.meeting })

const toast = useToast()

const updateMeeting = async () => {
  try {
    await axios.put(route('meetings.update', meeting.value.id), meeting.value)
    toast.success('La réunion a été mise à jour avec succès')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors de la mise à jour de la réunion')
  }
}

const rescheduleMeeting = async () => {
  try {
    await axios.put(route('meetings.reschedule', meeting.value.id), {
      scheduled_date: meeting.value.scheduled_date,
      scheduled_time: meeting.value.scheduled_time,
      reason: meeting.value.reason
    })
    toast.success('La réunion a été reportée avec succès')
    window.location.href = route('meetings.index')
  } catch (error) {
    console.error('Erreur:', error)
    toast.error('Erreur lors du report de la réunion')
  }
}
</script>
