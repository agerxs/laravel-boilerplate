<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Liste de paiement - {{ meeting.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Informations de la réunion -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Informations de la réunion</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p><span class="font-medium">Titre :</span> {{ meeting.title }}</p>
                                    <p><span class="font-medium">Date :</span> {{ formatDate(meeting.date) }}</p>
                                    <p><span class="font-medium">Heure :</span> {{ meeting.time }}</p>
                                </div>
                                <div>
                                    <p><span class="font-medium">Lieu :</span> {{ meeting.location }}</p>
                                    <p><span class="font-medium">Statut :</span> {{ meeting.status }}</p>
                                    <p><span class="font-medium">Type :</span> {{ meeting.type }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des participants -->
                        <div class="mb-6">
                            <h3 class="text-lg font-medium mb-2">Participants</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in paymentList.items" :key="item.id">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ item.attendee.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ item.attendee.role }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ formatAmount(item.amount) }} FCFA</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="getStatusClass(item.status)">
                                                    {{ getStatusLabel(item.status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <button @click="showPaymentDetails(item)" class="text-indigo-600 hover:text-indigo-900">
                                                    Voir détails
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4">
                            <button @click="submitList" v-if="canSubmit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Soumettre la liste
                            </button>
                            <button @click="validateList" v-if="canValidate" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                Valider la liste
                            </button>
                            <button @click="rejectList" v-if="canReject" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                                Rejeter la liste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal des détails de paiement -->
        <Modal :show="showDetails" @close="closeDetails">
            <div class="p-6">
                <h2 class="text-lg font-medium mb-4">Détails du paiement</h2>
                <div v-if="selectedItem" class="space-y-4">
                    <div>
                        <h3 class="font-medium">Informations du participant</h3>
                        <p>Nom : {{ selectedItem.attendee.name }}</p>
                        <p>Rôle : {{ selectedItem.attendee.role }}</p>
                    </div>
                    <div>
                        <h3 class="font-medium">Détails du paiement</h3>
                        <p>Montant : {{ formatAmount(selectedItem.amount) }} FCFA</p>
                        <p>Statut : {{ getStatusLabel(selectedItem.status) }}</p>
                        <p>Date de création : {{ formatDate(selectedItem.created_at) }}</p>
                        <p v-if="selectedItem.updated_at">Dernière mise à jour : {{ formatDate(selectedItem.updated_at) }}</p>
                    </div>
                    <div v-if="selectedItem.status === 'pending' && canValidateItem">
                        <button @click="validateItem(selectedItem)" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Valider ce paiement
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    meeting: Object,
    paymentList: Object,
    canSubmit: Boolean,
    canValidate: Boolean,
    canReject: Boolean,
    canValidateItem: Boolean,
});

const showDetails = ref(false);
const selectedItem = ref(null);

const showPaymentDetails = (item) => {
    selectedItem.value = item;
    showDetails.value = true;
};

const closeDetails = () => {
    showDetails.value = false;
    selectedItem.value = null;
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('fr-FR').format(amount);
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-slate-100 text-slate-700 border border-slate-200',
        submitted: 'bg-amber-100 text-amber-700 border border-amber-200',
        validated: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        rejected: 'bg-red-100 text-red-700 border border-red-200',
        paid: 'bg-emerald-100 text-emerald-700 border border-emerald-200'
    };
    return classes[status] || 'bg-slate-100 text-slate-700 border border-slate-200';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        validated: 'Validé',
        rejected: 'Rejeté',
        paid: 'Payé'
    };
    return labels[status] || status;
};

const submitList = () => {
    router.post(route('meeting-payments.lists.submit', props.paymentList.id));
};

const validateList = () => {
    router.post(route('meeting-payments.lists.validate', props.paymentList.id));
};

const rejectList = () => {
    router.post(route('meeting-payments.lists.reject', props.paymentList.id));
};

const validateItem = (item) => {
    router.post(route('meeting-payments.lists.validate-item', item.id));
};
</script> 