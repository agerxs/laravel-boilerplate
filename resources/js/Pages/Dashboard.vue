<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    stats: Object,
    pending_payment_lists: Array,
    draft_payment_lists: Array,
});

const formatDate = (date) => {
    return format(new Date(date), 'dd MMMM yyyy', { locale: fr });
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        submitted: 'bg-yellow-100 text-yellow-800',
        validated: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };
    return `px-2 py-1 rounded-full text-xs ${classes[status]}`;
};

const getStatusText = (status) => {
    const texts = {
        draft: 'Brouillon',
        submitted: 'Soumis',
        validated: 'Validé',
        rejected: 'Rejeté',
    };
    return texts[status] || status;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Tableau de bord
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Statistiques -->
                <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Paiements totaux</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ formatAmount(stats.total_payments) }}</p>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Paiements en attente</h3>
                        <p class="mt-2 text-3xl font-bold text-yellow-600">{{ stats.pending_payments }}</p>
                        <p class="mt-2 text-sm text-gray-500">Montant total : {{ formatAmount(stats.pending_payments_amount) }}</p>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Brouillons</h3>
                        <p class="mt-2 text-3xl font-bold text-gray-600">{{ stats.draft_payments }}</p>
                    </div>
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Paiements validés</h3>
                        <p class="mt-2 text-3xl font-bold text-green-600">{{ stats.validated_payments }}</p>
                    </div>
                </div>

                <!-- Statistiques des sous-préfets et secrétaires -->
                <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2">
                    <!-- Sous-préfets -->
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Paiements des sous-préfets</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Total payé</p>
                                <p class="mt-1 text-2xl font-bold text-green-600">{{ formatAmount(stats.sub_prefet_payments) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">En attente</p>
                                <p class="mt-1 text-2xl font-bold text-yellow-600">{{ stats.sub_prefet_pending }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Secrétaires -->
                    <div class="p-6 bg-white rounded-lg shadow">
                        <h3 class="text-lg font-medium text-gray-900">Paiements des secrétaires</h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-sm text-gray-500">Total payé</p>
                                <p class="mt-1 text-2xl font-bold text-green-600">{{ formatAmount(stats.secretary_payments) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">En attente</p>
                                <p class="mt-1 text-2xl font-bold text-yellow-600">{{ stats.secretary_pending }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Listes de paiement en attente -->
                <div class="mb-6 bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Listes de paiement en attente</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="list in pending_payment_lists" :key="list.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ list.meeting.title }}</div>
                                            <div class="text-sm text-gray-500">{{ formatDate(list.meeting.scheduled_date) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ list.meeting.local_committee.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ formatAmount(list.total_amount) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(list.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getStatusText(list.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link :href="route('meeting-payments.lists.show', list.id)" class="text-indigo-600 hover:text-indigo-900">
                                                Voir détails
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Listes de paiement en brouillon -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Listes de paiement en brouillon</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Réunion</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comité Local</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="list in draft_payment_lists" :key="list.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ list.meeting.title }}</div>
                                            <div class="text-sm text-gray-500">{{ formatDate(list.meeting.scheduled_date) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ list.meeting.local_committee.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ formatAmount(list.total_amount) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getStatusClass(list.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                                {{ getStatusText(list.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link :href="route('meeting-payments.lists.show', list.id)" class="text-indigo-600 hover:text-indigo-900">
                                                Voir détails
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
