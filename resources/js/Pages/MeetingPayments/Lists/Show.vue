<template>
  <AuthenticatedLayout :title="'Liste de paiement - ' + paymentList.meeting.title">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Liste de paiement
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <!-- En-tête -->
          <div class="mb-6">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  {{ paymentList.meeting.title }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  {{ paymentList.meeting.local_committee.name }} - {{ formatDate(paymentList.meeting.scheduled_date) }}
                </p>
              </div>
              <div class="text-right">
                <span :class="getStatusClass(paymentList.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                  {{ getStatusText(paymentList.status) }}
                </span>
                <p class="mt-1 text-sm text-gray-500">
                  Total : {{ formatCurrency(paymentList.total_amount) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Liste des participants -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nom
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Rôle
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Montant
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Statut
                  </th>
                  <th v-if="canValidate" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in paymentList.payment_items" :key="item.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ item.attendee.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ item.attendee.phone }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ translateRole(item.role) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ formatCurrency(item.amount) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getPaymentStatusClass(item.payment_status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getPaymentStatusText(item.payment_status) }}
                    </span>
                  </td>
                  <td v-if="canValidate" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      v-if="item.payment_status === 'pending'"
                      @click="validateItem(item)"
                      class="text-green-600 hover:text-green-900"
                    >
                      Valider
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Actions -->
          <div class="mt-6 flex justify-end space-x-4">
            <Link
              :href="route('meeting-payments.lists.index')"
              class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
            >
              Retour
            </Link>

            <!-- Boutons selon le statut -->
            <template v-if="paymentList.status === 'draft'">
              <button
                @click="submitList"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                :disabled="processing"
              >
                Soumettre pour validation
              </button>
            </template>

            <template v-if="paymentList.status === 'submitted' && canValidate">
              <button
                @click="showRejectModal = true"
                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                :disabled="processing"
              >
                Rejeter
              </button>
              <button
                @click="validateList"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                :disabled="processing"
              >
                Valider
              </button>
            </template>

            <button
              v-if="canValidate && paymentList.status === 'submitted'"
              @click="validateAll"
              class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            >
              Valider tous les paiements
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de rejet -->
    <Modal :show="showRejectModal" @close="showRejectModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Rejeter la liste de paiement
        </h2>

        <p class="mt-1 text-sm text-gray-600">
          Veuillez indiquer la raison du rejet.
        </p>

        <div class="mt-6">
          <InputLabel for="rejection_reason" value="Raison du rejet" />
          <Textarea
            id="rejection_reason"
            v-model="rejectionForm.rejection_reason"
            class="mt-1 block w-full"
            rows="4"
            required
          />
          <InputError :message="rejectionForm.errors.rejection_reason" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <button
            @click="showRejectModal = false"
            class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200"
          >
            Annuler
          </button>
          <button
            @click="rejectList"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
            :disabled="processing"
          >
            Rejeter
          </button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Textarea from '@/Components/Textarea.vue';
import InputError from '@/Components/InputError.vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
  paymentList: Object,
  canValidate: Boolean,
});

const processing = ref(false);
const showRejectModal = ref(false);

const rejectionForm = useForm({
  rejection_reason: '',
});

function formatDate(date) {
  if (!date) return '';
  return format(new Date(date), 'dd MMMM yyyy', { locale: fr });
}

function formatCurrency(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
}

function translateRole(role) {
  const translations = {
    'prefet': 'Préfet',
    'sous_prefet': 'Sous-préfet',
    'secretaire': 'Secrétaire',
    'representant': 'Représentant'
  };
  return translations[role] || role;
}

function getStatusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    submitted: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

function getStatusText(status) {
  const texts = {
    draft: 'Brouillon',
    submitted: 'Soumis',
    validated: 'Validé',
    rejected: 'Rejeté',
  };
  return texts[status] || status;
}

function getPaymentStatusClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
}

function getPaymentStatusText(status) {
  const texts = {
    pending: 'En attente',
    validated: 'Validé',
    rejected: 'Rejeté'
  };
  return texts[status] || status;
}

function submitList() {
  processing.value = true;
  
  router.post(route('meeting-payments.lists.submit', props.paymentList.id), {}, {
    onSuccess: () => {
      processing.value = false;
    },
    onError: () => {
      processing.value = false;
    }
  });
}

function validateList() {
  processing.value = true;
  
  router.post(route('meeting-payments.lists.validate', props.paymentList.id), {}, {
    onSuccess: () => {
      processing.value = false;
    },
    onError: () => {
      processing.value = false;
    }
  });
}

function rejectList() {
  processing.value = true;
  
  rejectionForm.post(route('meeting-payments.lists.reject', props.paymentList.id), {
    onSuccess: () => {
      processing.value = false;
      showRejectModal.value = false;
    },
    onError: () => {
      processing.value = false;
    }
  });
}

function validateItem(item) {
  if (confirm('Êtes-vous sûr de vouloir valider ce paiement ?')) {
    router.post(route('meeting-payments.lists.validate-item', item.id));
  }
}

function validateAll() {
  if (confirm('Êtes-vous sûr de vouloir valider tous les paiements ?')) {
    router.post(route('meeting-payments.lists.validate-all'), {
      meeting_id: props.paymentList.meeting_id
    });
  }
}
</script> 