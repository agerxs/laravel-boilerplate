<template>
  <AppLayout :title="`Liste de paiement - ${paymentList.meeting?.title || 'Réunion'}`">
    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- En-tête -->
        <div class="bg-white shadow sm:rounded-lg mb-6">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex justify-between items-start">
              <div>
                <h2 class="text-lg font-medium text-gray-900">
                  Liste de paiement
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                  Réunion : {{ paymentList.meeting?.title || 'Réunion non définie' }}
                </p>
                <p class="text-sm text-gray-600">
                  Comité : {{ paymentList.meeting?.local_committee?.name || 'Comité non défini' }}
                </p>
              </div>
              <div class="flex items-center space-x-3">
                <span
                  :class="[getStatusClass(paymentList.status), 'px-3 py-1 rounded-full text-sm font-medium']"
                >
                  {{ getStatusText(paymentList.status) }}
                </span>
              </div>
            </div>
            
            <!-- Informations de la liste -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <p class="text-sm font-medium text-gray-500">Montant total</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(paymentList.total_amount) }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-500">Soumis par</p>
                <p class="text-sm text-gray-900">{{ paymentList.submitter?.name || 'Utilisateur non défini' }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(paymentList.submitted_at) }}</p>
              </div>
              <div v-if="paymentList.validator">
                <p class="text-sm font-medium text-gray-500">Validé par</p>
                <p class="text-sm text-gray-900">{{ paymentList.validator.name }}</p>
                <p class="text-xs text-gray-500">{{ formatDate(paymentList.validated_at) }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center space-x-3">
              <template v-if="paymentList.status === 'draft'">
                <button
                  @click="submitList"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                  :disabled="processing"
                >
                  Soumettre
                </button>
              </template>
              <template v-if="paymentList.status === 'submitted' && userHasRole('gestionnaire')">
                <button
                  @click="validateList"
                  class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                  :disabled="processing"
                >
                  Valider
                </button>
                <button
                  @click="showRejectModal = true"
                  class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                  :disabled="processing"
                >
                  Rejeter
                </button>
              </template>
              <button
                v-if="paymentList.status === 'validated' && userHasRole('gestionnaire')"
                @click="validateAll"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
              >
                Valider tous les paiements
              </button>
            </div>
          </div>
        </div>

        <!-- Liste des paiements -->
        <div class="bg-white shadow sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Détail des paiements</h3>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Participant
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="item in paymentList.payment_items" :key="item.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ item.attendee?.name || 'Participant non défini' }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ item.attendee?.phone || 'Pas de téléphone' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ translateRole(item.role) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ formatCurrency(item.amount) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="[getPaymentStatusClass(item.payment_status), 'px-2 py-1 text-xs rounded-full']"
                      >
                        {{ getPaymentStatusText(item.payment_status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2">
                        <button
                          v-if="item.payment_status === 'pending' && userHasRole('gestionnaire')"
                          @click="validateItem(item)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Valider
                        </button>
                        <button
                          v-if="item.payment_status === 'validated' && userHasRole('gestionnaire')"
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
          <TextArea
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
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
  paymentList: Object,
  canValidate: Boolean,
  user: Object,
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
    'sous_prefet': 'Sous-préfet',
    'secretaire': 'Secrétaire',
    'representant': 'Représentant'
  };
  return translations[role] || role;
}

function getStatusClass(status) {
  const classes = {
    draft: 'bg-slate-100 text-slate-700 border border-slate-200',
    submitted: 'bg-amber-100 text-amber-700 border border-amber-200',
    validated: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    rejected: 'bg-red-100 text-red-700 border border-red-200'
  };
  return classes[status] || 'bg-slate-100 text-slate-700 border border-slate-200';
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
    pending: 'bg-amber-100 text-amber-700 border border-amber-200',
    validated: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
    rejected: 'bg-red-100 text-red-700 border border-red-200'
  };
  return classes[status] || 'bg-slate-100 text-slate-700 border border-slate-200';
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

function invalidateItem(item) {
  if (confirm('Êtes-vous sûr de vouloir invalider ce paiement ?')) {
    router.post(route('meeting-payments.lists.invalidate-item', item.id));
  }
}

function validateAll() {
  if (confirm('Êtes-vous sûr de vouloir valider tous les paiements ?')) {
    router.post(route('meeting-payments.lists.validate-all'), {
      meeting_id: props.paymentList.meeting_id
    });
  }
}

const userHasRole = (role) => {
  if (!props.user || !props.user.roles) return false;
  return props.user.roles.some(r => r.toLowerCase() === role.toLowerCase());
};
</script> 