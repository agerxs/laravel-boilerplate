<template>
    <AppLayout title="Paiements des réunions">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Paiements des réunions
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Filtres -->
                        <div class="mb-6">
                            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                                <div class="flex-1">
                                    <InputLabel for="search" value="Rechercher" />
                                    <TextInput
                                        id="search"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="filters.search"
                                        @change="search"
                                        placeholder="Titre de la réunion"
                                    />
                                </div>
                                <div class="flex-1">
                                    <InputLabel for="date_from" value="Date de début" />
                                    <TextInput
                                        id="date_from"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="filters.date_from"
                                        @change="search"
                                    />
                                </div>
                                <div class="flex-1">
                                    <InputLabel for="date_to" value="Date de fin" />
                                    <TextInput
                                        id="date_to"
                                        type="date"
                                        class="mt-1 block w-full"
                                        v-model="filters.date_to"
                                        @change="search"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Tableau des réunions -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Titre
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Comité local
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Participants
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Statut des paiements
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="meeting in meetings.data" :key="meeting.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ meeting.title }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ formatDate(meeting.date) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ meeting.local_committee?.name || 'Non défini' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ meeting.attendees?.length || 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="meeting.payments && meeting.payments.length > 0" 
                                                  :class="getPaymentStatusClass(meeting.payments)">
                                                {{ getPaymentStatusText(meeting.payments) }}
                                            </span>
                                            <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                                                Non traité
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('meeting-payments.show', meeting.id)" class="text-indigo-600 hover:text-indigo-900">
                                                Gérer les paiements
                                            </Link>
                                        </td>
                                    </tr>
                                    <tr v-if="meetings.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Aucune réunion trouvée
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            <Pagination :links="meetings.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

const props = defineProps({
    meetings: Object,
    filters: Object,
});

const filters = ref({
    search: props.filters.search || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function search() {
    router.get(route('meeting-payments.index'), {
        search: filters.value.search,
        date_from: filters.value.date_from,
        date_to: filters.value.date_to,
    }, {
        preserveState: true,
        replace: true,
    });
}

function formatDate(dateString) {
    if (!dateString) return 'Non définie';
    return format(new Date(dateString), 'dd MMMM yyyy', { locale: fr });
}

const getPaymentStatusClass = (payments) => {
  if (!payments || payments.length === 0) {
    return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-700 border border-slate-200';
  }
  
  const allPaid = payments.every(payment => payment.status === 'paid');
  const allPending = payments.every(payment => payment.status === 'pending');
  
  if (allPaid) {
    return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200';
  } else if (allPending) {
    return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-700 border border-amber-200';
  } else {
    return 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-700 border border-red-200';
  }
}

function getPaymentStatusText(payments) {
    const allPaid = payments.every(payment => payment.is_paid);
    const somePaid = payments.some(payment => payment.is_paid);
    
    if (allPaid) {
        return 'Tous payés';
    } else if (somePaid) {
        return 'Partiellement payé';
    } else {
        return 'Non payé';
    }
}
</script> 