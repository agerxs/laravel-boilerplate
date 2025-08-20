<template>
    <AppLayout :title="`Paiements pour la réunion: ${meeting.title}`">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Paiements pour la réunion: {{ meeting.title }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
                <!-- Informations sur la réunion -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Détails de la réunion</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p><span class="font-semibold">Titre:</span> {{ meeting.title }}</p>
                                <p><span class="font-semibold">Date:</span> {{ formatDate(meeting.scheduled_date) }}</p>
                                <p><span class="font-semibold">Lieu:</span> {{ meeting.location || 'Non défini' }}</p>
                            </div>
                            <div>
                                <p><span class="font-semibold">Comité local:</span> {{ meeting.local_committee?.name || 'Non défini' }}</p>
                                <p><span class="font-semibold">Participants:</span> {{ meeting.attendees?.length || 0 }}</p>
                                <p><span class="font-semibold">Statut:</span> {{ meeting.status || 'Non défini' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de paiements -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Gestion des paiements</h3>
                        
                        <form @submit.prevent="submitPayments">
                            <!-- Tableau des officiels à payer -->
                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nom
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Rôle
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Taux
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Montant
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Payé
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date de paiement
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Méthode
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(payment, index) in form.payments" :key="index">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ payment.user_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ translateRole(payment.role) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ formatCurrency(payment.rate) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <InputLabel for="amount" value="Montant" :isRequired="true" />
                                                    <TextInput
                                                        id="amount"
                                                        v-model="payment.amount"
                                                        type="number"
                                                        step="0.01"
                                                        class="mt-1 block w-full"
                                                        :class="{ 'border-red-500': errors[`payments.${index}.amount`] }"
                                                        required
                                                    />
                                                </div>
                                                <div v-if="errors[`payments.${index}.amount`]" class="text-red-500 text-xs mt-1">
                                                    {{ errors[`payments.${index}.amount`] }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Checkbox
                                                    v-model:checked="payment.is_paid"
                                                    :class="{ 'border-red-500': errors[`payments.${index}.is_paid`] }"
                                                />
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <TextInput
                                                    v-model="payment.payment_date"
                                                    type="date"
                                                    class="mt-1 block w-full"
                                                    :disabled="!payment.is_paid"
                                                    :class="{ 'border-red-500': errors[`payments.${index}.payment_date`] }"
                                                />
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <InputLabel for="payment_method" value="Méthode de paiement *" class="required" />
                                                    <select
                                                        id="payment_method"
                                                        v-model="payment.payment_method"
                                                        class="mt-1 block w-full rounded-md border-gray-300"
                                                        :disabled="!payment.is_paid"
                                                        :class="{ 'border-red-500': errors[`payments.${index}.payment_method`] }"
                                                        required
                                                    >
                                                        <option value="">Sélectionner une méthode</option>
                                                        <option value="especes">Espèces</option>
                                                        <option value="virement">Virement bancaire</option>
                                                        <option value="cheque">Chèque</option>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="form.payments.length === 0">
                                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                                Aucun officiel avec un taux de paiement défini n'a été trouvé.
                                                Veuillez configurer les taux de paiement dans la section administration.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Notes générales -->
                            <div class="mb-6">
                                <InputLabel for="notes" value="Notes générales" />
                                <TextArea
                                    id="notes"
                                    v-model="form.notes"
                                    class="mt-1 block w-full"
                                    rows="3"
                                    placeholder="Notes concernant les paiements"
                                />
                            </div>

                            <!-- Boutons d'action -->
                            <div class="flex items-center justify-end">
                                <Link :href="route('meeting-payments.index')" class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:shadow-outline-gray transition ease-in-out duration-150 mr-3">
                                    Annuler
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': processing }" :disabled="processing">
                                    Enregistrer les paiements
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    meeting: Object,
    paymentData: Array,
    errors: Object,
});

const processing = ref(false);

// Initialiser le formulaire avec les paiements
const form = useForm({
    payments: [],
    notes: '',
});

onMounted(() => {
    // Utiliser les données de paiement fournies par le contrôleur
    form.payments = props.paymentData || [];
    form.notes = props.meeting.payment_notes || '';
});

function formatDate(dateString) {
    if (!dateString) return 'Non définie';
    return format(new Date(dateString), 'dd MMMM yyyy', { locale: fr });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
}

function translateRole(role) {
    const translations = {
        'sous_prefet': 'Président',
        'secretaire': 'Secrétaire',
        'membre': 'Membre'
    };
    
    return translations[role] || role;
}

function submitPayments() {
    processing.value = true;
    
    form.post(route('meeting-payments.process', props.meeting.id), {
        onSuccess: () => {
            processing.value = false;
        },
        onError: () => {
            processing.value = false;
        }
    });
}
</script> 