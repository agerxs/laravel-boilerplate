<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import {
    CurrencyDollarIcon,
    ClockIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    UserGroupIcon,
    ChartBarIcon,
    UsersIcon,
    UserIcon
} from '@heroicons/vue/24/outline';

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
        draft: 'bg-slate-100 text-slate-700 border border-slate-200',
        submitted: 'bg-amber-100 text-amber-700 border border-amber-200',
        validated: 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        rejected: 'bg-red-100 text-red-700 border border-red-200'
    };
    return classes[status] || 'bg-slate-100 text-slate-700 border border-slate-200';
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
            <div class="mx-auto max-w-10xl sm:px-6 lg:px-8">
                <!-- En-tête du dashboard -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                        Tableau de bord
                    </h1>
                    <p class="mt-2 text-slate-600 text-lg">
                        Vue d'ensemble de vos activités et statistiques
                    </p>
                </div>

                <!-- Statistiques principales -->
                <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Paiements totaux -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CurrencyDollarIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-blue-100">Paiements totaux</p>
                                    <p class="text-3xl font-bold text-white">{{ formatAmount(stats.total_payments) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paiements en attente -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 p-6 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ClockIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-amber-100">En attente</p>
                                    <p class="text-3xl font-bold text-white">{{ stats.pending_payments }}</p>
                                    <p class="text-sm text-amber-100">Total : {{ formatAmount(stats.pending_payments_amount) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brouillons -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-500 to-slate-600 p-6 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <DocumentTextIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-slate-100">Brouillons</p>
                                    <p class="text-3xl font-bold text-white">{{ stats.draft_payments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paiements validés -->
                    <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 p-6 text-white shadow-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CheckCircleIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-emerald-100">Validés</p>
                                    <p class="text-3xl font-bold text-white">{{ stats.validated_payments }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques détaillées -->
                <div class="grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2">
                    <!-- Sous-préfets -->
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg border border-slate-200/50 transition-all duration-300 hover:shadow-xl hover:border-blue-200">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-blue-50"></div>
                        <div class="relative">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <UserGroupIcon class="h-6 w-6 text-blue-600" />
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-slate-800">Paiements des présidents</h3>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <span class="text-sm font-medium text-emerald-700">Total payé</span>
                                    <span class="text-xl font-bold text-emerald-600">{{ formatAmount(stats.sub_prefet_payments) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-xl border border-amber-200">
                                    <span class="text-sm font-medium text-amber-700">En attente</span>
                                    <span class="text-xl font-bold text-amber-600">{{ stats.sub_prefet_pending }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Secrétaires -->
                    <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg border border-slate-200/50 transition-all duration-300 hover:shadow-xl hover:border-blue-200">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-indigo-50"></div>
                        <div class="relative">
                            <div class="flex items-center mb-4">
                                <div class="flex-shrink-0">
                                    <UserGroupIcon class="h-6 w-6 text-indigo-600" />
                                </div>
                                <h3 class="ml-3 text-lg font-semibold text-slate-800">Paiements des secrétaires</h3>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <span class="text-sm font-medium text-emerald-700">Total payé</span>
                                    <span class="text-xl font-bold text-emerald-600">{{ formatAmount(stats.secretary_payments) }}</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-amber-50 rounded-xl border border-amber-200">
                                    <span class="text-sm font-medium text-amber-700">En attente</span>
                                    <span class="text-xl font-bold text-amber-600">{{ stats.secretary_pending }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique de progression -->
                <div class="mb-8">
                    <div class="bg-white rounded-2xl shadow-lg border border-slate-200/50 p-6">
                        <div class="flex items-center mb-6">
                            <ChartBarIcon class="h-6 w-6 text-blue-600 mr-3" />
                            <h3 class="text-lg font-semibold text-slate-800">Progression des paiements</h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-slate-800">{{ stats.total_payments > 0 ? Math.round((stats.validated_payments / stats.total_payments) * 100) : 0 }}%</div>
                                <div class="text-sm text-slate-600">Validés</div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full" :style="`width: ${stats.total_payments > 0 ? (stats.validated_payments / stats.total_payments) * 100 : 0}%`"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-slate-800">{{ stats.total_payments > 0 ? Math.round((stats.pending_payments / stats.total_payments) * 100) : 0 }}%</div>
                                <div class="text-sm text-slate-600">En attente</div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-2">
                                    <div class="bg-amber-500 h-2 rounded-full" :style="`width: ${stats.total_payments > 0 ? (stats.pending_payments / stats.total_payments) * 100 : 0}%`"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-slate-800">{{ stats.total_payments > 0 ? Math.round((stats.draft_payments / stats.total_payments) * 100) : 0 }}%</div>
                                <div class="text-sm text-slate-600">Brouillons</div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-2">
                                    <div class="bg-slate-500 h-2 rounded-full" :style="`width: ${stats.total_payments > 0 ? (stats.draft_payments / stats.total_payments) * 100 : 0}%`"></div>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-slate-800">{{ stats.total_payments > 0 ? Math.round(((stats.total_payments - stats.validated_payments - stats.pending_payments - stats.draft_payments) / stats.total_payments) * 100) : 0 }}%</div>
                                <div class="text-sm text-slate-600">Autres</div>
                                <div class="mt-2 w-full bg-slate-200 rounded-full h-2">
                                    <div class="bg-slate-400 h-2 rounded-full" :style="`width: ${stats.total_payments > 0 ? ((stats.total_payments - stats.validated_payments - stats.pending_payments - stats.draft_payments) / stats.total_payments) * 100 : 0}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link
                        :href="route('meetings.create')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105"
                    >
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UsersIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold">Nouvelle réunion</h3>
                                    <p class="text-blue-100 text-sm">Créer une nouvelle réunion</p>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link
                        :href="route('local-committees.create')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-green-600 p-6 text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105"
                    >
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UserGroupIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold">Nouveau comité</h3>
                                    <p class="text-emerald-100 text-sm">Créer un nouveau comité</p>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <Link
                        :href="route('representatives.create')"
                        class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 p-6 text-white shadow-lg transition-all duration-300 hover:shadow-xl hover:scale-105"
                    >
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 h-24 w-24 rounded-full bg-white/10"></div>
                        <div class="relative">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UserIcon class="h-8 w-8 text-white/90" />
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold">Nouveau représentant</h3>
                                    <p class="text-purple-100 text-sm">Ajouter un représentant</p>
                                </div>
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animations personnalisées */
.group:hover .absolute {
    transform: scale(1.1);
    transition: transform 0.3s ease-in-out;
}

/* Effet de brillance sur les cartes */
.group::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.group:hover::before {
    left: 100%;
}
</style>
