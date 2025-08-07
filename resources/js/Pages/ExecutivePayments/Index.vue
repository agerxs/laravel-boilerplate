<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, reactive } from 'vue';

const props = defineProps({
  payments: Object,
  filters: Object,
});

const page = usePage();

const filters = reactive({
  role: props.filters?.role || '',
  payment_status: props.filters?.payment_status || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
});

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('fr-FR');
};

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
};

const getStatusClass = (status) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'validated': 'bg-blue-100 text-blue-800',
    'paid': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
  const texts = {
    'pending': 'En attente',
    'validated': 'Validé',
    'paid': 'Payé',
    'cancelled': 'Annulé'
  };
  return texts[status] || status;
};

const getRoleText = (role) => {
  const texts = {
    'secretaire': 'Secrétaire',
    'sous-prefet': 'Président'
  };
  return texts[role] || role;
};

const updateStatus = (paymentId, newStatus) => {
  router.post(`/executive-payments/${paymentId}/update-status`, {
    payment_status: newStatus
  }, {
    onSuccess: () => {
      // Le message de succès sera affiché via les messages flash d'Inertia
      // La page sera automatiquement rechargée avec les nouvelles données
    },
    onError: (errors) => {
      console.error('Erreur lors de la mise à jour:', errors);
    }
  });
};

const exportPayments = async (type) => {
  try {
    const params = new URLSearchParams();
    if (filters.role) params.append('role', filters.role);
    if (filters.payment_status) params.append('payment_status', filters.payment_status);
    if (filters.date_from) params.append('date_from', filters.date_from);
    if (filters.date_to) params.append('date_to', filters.date_to);

    const url = type === 'all' 
      ? `/executive-payments/export/all?${params.toString()}`
      : `/executive-payments/export/pending?${params.toString()}`;

    const response = await fetch(url);
    const data = await response.json();

    if (data.data && data.data.length > 0) {
      // Convertir en CSV
      const headers = Object.keys(data.data[0]);
      const csvContent = [
        headers.join(','),
        ...data.data.map(row => headers.map(header => `"${row[header]}"`).join(','))
      ].join('\n');

      // Télécharger le fichier
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url2 = URL.createObjectURL(blob);
      link.setAttribute('href', url2);
      link.setAttribute('download', `${data.filename}.csv`);
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      alert('Aucune donnée à exporter');
    }
  } catch (error) {
    console.error('Erreur lors de l\'export:', error);
    alert('Erreur lors de l\'export');
  }
};

const getTriggeringMeetingsText = (payment) => {
  if (!payment.triggering_meetings_data || !Array.isArray(payment.triggering_meetings_data)) {
    return payment.meeting?.title || 'N/A';
  }

  return payment.triggering_meetings_data
    .map(meeting => `${meeting.title} (${meeting.date})`)
    .join(' et ');
};

const applyFilters = () => {
  router.get('/executive-payments', filters, {
    preserveState: true,
    preserveScroll: true,
  });
};

const clearFilters = () => {
  Object.keys(filters).forEach(key => filters[key] = '');
  applyFilters();
};
</script>

<template>
  <Head title="Paiements des Comités" />

  <AppLayout title="Suivi des Paiements des Comités">
    <div class="py-12">
      <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
        <!-- Messages flash -->
        <div v-if="page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ page.props.flash.error }}
        </div>
        
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Suivi des Paiements des Comités</h2>
            
            <!-- Boutons d'export -->
            <div class="flex space-x-2">
              <button
                @click="exportPayments('all')"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                📊 Export Tous
              </button>
              <button
                @click="exportPayments('pending')"
                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                ⏳ Export Non Effectués
              </button>
            </div>
          </div>

          <!-- Filtres -->
          <div class="mb-6 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium mb-4">Filtres</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                <select v-model="filters.role" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous les rôles</option>
                  <option value="secretaire">Secrétaire</option>
                  <option value="sous-prefet">Président</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select v-model="filters.payment_status" class="w-full border-gray-300 rounded-md shadow-sm">
                  <option value="">Tous les statuts</option>
                  <option value="pending">En attente</option>
                  <option value="validated">Validé</option>
                  <option value="paid">Payé</option>
                  <option value="cancelled">Annulé</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input v-model="filters.date_from" type="date" class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input v-model="filters.date_to" type="date" class="w-full border-gray-300 rounded-md shadow-sm">
              </div>
            </div>
            
            <div class="mt-4 flex space-x-2">
              <button
                @click="applyFilters"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Appliquer les filtres
              </button>
              <button
                @click="clearFilters"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Effacer
              </button>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bénéficiaire</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunions Déclencheuses</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de création</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                  <th scope="col" class="relative px-6 py-3">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="payments.data.length === 0">
                  <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucun paiement trouvé.</td>
                </tr>
                <tr v-for="payment in payments.data" :key="payment.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ payment.user?.name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                      {{ getRoleText(payment.role) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(payment.amount) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ getTriggeringMeetingsText(payment) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ payment.meeting?.local_committee?.name || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(payment.created_at) }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(payment.payment_status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ getStatusText(payment.payment_status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <!-- Menu d'actions pour changer le statut -->
                    <div class="relative inline-block text-left">
                      <select
                        @change="updateStatus(payment.id, $event.target.value)"
                        :value="payment.payment_status"
                        class="text-sm border-gray-300 rounded-md shadow-sm"
                      >
                        <option value="pending" :disabled="payment.payment_status === 'pending'">En attente</option>
                        <option value="validated" :disabled="payment.payment_status === 'validated'">Valider</option>
                        <option value="paid" :disabled="payment.payment_status === 'paid'">Marquer comme payé</option>
                        <option value="cancelled" :disabled="payment.payment_status === 'cancelled'">Annuler</option>
                      </select>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-4">
            <Pagination :links="payments.links" />
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template> 