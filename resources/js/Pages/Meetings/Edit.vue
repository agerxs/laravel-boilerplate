<template>
  <Head title="Modifier la réunion" />

  <AppLayout :title="`Modifier : ${meeting.title}`">
    <div class="max-w-3xl mx-auto py-6">
      <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6">
        <div class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700">Titre</label>
            <input
              type="text"
              v-model="form.title"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Comité local</label>
            <select
              v-model="form.local_committee_id"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              required
            >
              <option value="">Sélectionnez un comité</option>
              <option v-for="committee in committees" :key="committee.id" :value="committee.id">
                {{ committee.locality?.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Date prévue</label>
            <input
              type="date"
              v-model="form.scheduled_date"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              required
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Statut</label>
            <select
              v-model="form.status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
              required
            >
              <option value="scheduled">Planifiée</option>
              <option value="completed">Terminée</option>
              <option value="cancelled">Annulée</option>
            </select>
          </div>

          <div class="flex justify-end space-x-3">
            <Link
              :href="route('meetings.index')"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Annuler
            </Link>
            <button
              type="submit"
              class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm font-medium hover:bg-primary-700"
              :disabled="form.processing"
            >
              Mettre à jour
            </button>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

interface Committee {
  id: number;
  name: string;
  locality_name: string;
}

interface Meeting {
  id: number;
  title: string;
  scheduled_date: string;
  status: string;
  local_committee_id: number;
  locality_name: string;
}

interface Props {
  meeting: Meeting;
  committees: Committee[];
}

const props = defineProps<Props>()

const form = useForm({
  title: props.meeting.title,
  local_committee_id: props.meeting.local_committee_id,
  scheduled_date: props.meeting.scheduled_date,
  status: props.meeting.status
})

const submit = () => {
  form.put(route('meetings.update', props.meeting.id))
}
</script> 