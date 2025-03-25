<template>
  <Head title="Reporter la Réunion" />

  <AppLayout title="Reporter la Réunion">
    <div class="max-w-2xl mx-auto py-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Reporter la Réunion</h2>
      
      <!-- Informations sur la réunion -->
      <div class="mb-4 p-4 bg-gray-100 rounded-lg">
        <p><strong>Titre :</strong> {{ meeting.title }}</p>
        <p><strong>Date actuelle :</strong> {{ formatDate(meeting.scheduled_date) }}</p>
        <p><strong>Localité :</strong> {{ meeting.locality_name }}</p>
      </div>

      <form @submit.prevent="submit">
        <div class="mb-4">
          <label for="newDate" class="block text-sm font-medium text-gray-700">Nouvelle Date</label>
          <input
            type="date"
            id="newDate"
            v-model="newDate"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
            required
          />
        </div>
        <div class="mb-4">
          <label for="reason" class="block text-sm font-medium text-gray-700">Motif du report</label>
          <textarea
            id="reason"
            v-model="reason"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm"
            rows="3"
            required
          ></textarea>
        </div>
        <div class="flex justify-end">
          <button
            type="submit"
            class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 rounded-lg text-white font-medium text-sm"
          >
            Reporter
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, onMounted } from 'vue'
import { useToast } from '@/Composables/useToast'
import axios from 'axios'

// Obtenez l'ID de la réunion à partir des props
const props = defineProps<{ meetingId: number }>()

const page = usePage()
const meeting = page.props.meeting

const toast = useToast()
const newDate = ref('')
const reason = ref('')

const formatDate = (date: string) => {
  if (!date) return 'Date non définie';
  return new Date(date).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

const submit = async () => {
  try {
    await axios.post(route('meetings.reschedule.submit', { meeting: props.meetingId }), {
      date: newDate.value,
      reason: reason.value
    });
    toast.success('La réunion a été reportée avec succès');
    router.visit(route('meetings.index'));
  } catch (error) {
    console.error('Erreur:', error);
    toast.error('Erreur lors du report de la réunion');
  }
}

onMounted(() => {
  // Vérifier si la réunion est dans un état qui permet le report
  axios.get(route('meetings.show', props.meetingId))
    .then(response => {
      const meetingStatus = response.data.props.meeting.status;
      if (meetingStatus === 'completed' || meetingStatus === 'cancelled' || 
          meetingStatus === 'prevalidated' || meetingStatus === 'validated') {
        toast.error('Cette réunion ne peut pas être reportée');
        router.visit(route('meetings.show', props.meetingId));
      }
    })
    .catch(error => {
      console.error('Erreur lors de la vérification du statut:', error);
      toast.error('Erreur lors du chargement de la réunion');
    });
});
</script> 