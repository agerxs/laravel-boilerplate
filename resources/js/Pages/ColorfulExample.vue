<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ColorfulSidebar from '@/Components/ColorfulSidebar.vue';
import ColorfulNavItem from '@/Components/ColorfulNavItem.vue';
import ModernCard from '@/Components/ModernCard.vue';
import ModernButton from '@/Components/ModernButton.vue';
import {
    HomeIcon,
    CalendarIcon,
    UserGroupIcon,
    UsersIcon,
    DocumentIcon,
    Cog6ToothIcon,
    StarIcon,
    HeartIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const navigationItems = [
    {
        name: 'Accueil',
        href: '/dashboard',
        icon: HomeIcon,
        color: 'text-blue-600',
        gradient: 'from-blue-500 to-blue-600'
    },
    {
        name: 'Agenda',
        href: '/calendar',
        icon: CalendarIcon,
        color: 'text-purple-600',
        gradient: 'from-purple-500 to-purple-600',
        badge: '3',
        badgeColor: 'bg-purple-100 text-purple-800'
    },
    {
        name: 'Comités',
        href: '/committees',
        icon: UserGroupIcon,
        color: 'text-emerald-600',
        gradient: 'from-emerald-500 to-emerald-600'
    },
    {
        name: 'Réunions',
        href: '/meetings',
        icon: UsersIcon,
        color: 'text-orange-600',
        gradient: 'from-orange-500 to-orange-600',
        badge: 'Nouveau',
        badgeColor: 'bg-orange-100 text-orange-800'
    },
    {
        name: 'Documents',
        href: '/documents',
        icon: DocumentIcon,
        color: 'text-rose-600',
        gradient: 'from-rose-500 to-rose-600'
    },
    {
        name: 'Paramètres',
        href: '/settings',
        icon: Cog6ToothIcon,
        color: 'text-slate-600',
        gradient: 'from-slate-500 to-slate-600'
    }
];

const quickActions = [
    {
        name: 'Nouvelle réunion',
        icon: UsersIcon,
        color: 'from-blue-500 to-indigo-600',
        description: 'Créer une nouvelle réunion'
    },
    {
        name: 'Ajouter un comité',
        icon: UserGroupIcon,
        color: 'from-emerald-500 to-green-600',
        description: 'Créer un nouveau comité local'
    },
    {
        name: 'Importer des données',
        icon: DocumentIcon,
        color: 'from-purple-500 to-pink-600',
        description: 'Importer des données en masse'
    }
];
</script>

<template>
    <Head title="Exemple Coloré" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Exemple d'Interface Colorée
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-10xl sm:px-6 lg:px-8">
                <!-- En-tête coloré -->
                <div class="mb-8 text-center">
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                        🎨 Interface Colorée et Moderne
                    </h1>
                    <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                        Découvrez les nouveaux composants colorés avec des icônes vibrantes, 
                        des gradients modernes et des animations fluides pour une expérience utilisateur exceptionnelle.
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Sidebar colorée -->
                    <div class="lg:col-span-1">
                        <ColorfulSidebar
                            title="Navigation"
                            subtitle="Menu principal"
                            :items="navigationItems"
                            current-route="/dashboard"
                            variant="gradient"
                            size="md"
                        />
                    </div>

                    <!-- Contenu principal -->
                    <div class="lg:col-span-3 space-y-8">
                        <!-- Cartes d'action rapide -->
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-6 flex items-center">
                                <SparklesIcon class="h-6 w-6 text-purple-600 mr-3" />
                                Actions Rapides
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <template v-for="action in quickActions" :key="action.name">
                                    <ModernCard
                                        :title="action.name"
                                        :subtitle="action.description"
                                        variant="gradient"
                                        color="blue"
                                        hover
                                        clickable
                                    >
                                        <div class="flex items-center justify-between">
                                            <component 
                                                :is="action.icon" 
                                                class="h-8 w-8 text-white/90" 
                                            />
                                            <div class="text-right">
                                                <div class="text-2xl font-bold text-white">
                                                    <StarIcon class="h-6 w-6 inline" />
                                                </div>
                                            </div>
                                        </div>
                                    </ModernCard>
                                </template>
                            </div>
                        </div>

                        <!-- Démonstration des boutons colorés -->
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-6 flex items-center">
                                <HeartIcon class="h-6 w-6 text-rose-600 mr-3" />
                                Boutons Colorés
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <ModernButton variant="primary" size="md">
                                    <HomeIcon class="h-4 w-4 mr-2" />
                                    Principal
                                </ModernButton>
                                
                                <ModernButton variant="success" size="md">
                                    <UserGroupIcon class="h-4 w-4 mr-2" />
                                    Succès
                                </ModernButton>
                                
                                <ModernButton variant="warning" size="md">
                                    <CalendarIcon class="h-4 w-4 mr-2" />
                                    Attention
                                </ModernButton>
                                
                                <ModernButton variant="danger" size="md">
                                    <DocumentIcon class="h-4 w-4 mr-2" />
                                    Danger
                                </ModernButton>
                            </div>
                        </div>

                        <!-- Démonstration des éléments de navigation -->
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-6 flex items-center">
                                <SparklesIcon class="h-6 w-6 text-cyan-600 mr-3" />
                                Éléments de Navigation
                            </h3>
                            <div class="bg-white rounded-2xl p-6 shadow-lg border border-slate-200/50">
                                <div class="space-y-3">
                                    <template v-for="item in navigationItems.slice(0, 3)" :key="item.name">
                                        <ColorfulNavItem
                                            :href="item.href"
                                            :icon="item.icon"
                                            :color="item.color"
                                            :gradient="item.gradient"
                                            :is-active="false"
                                            size="md"
                                        >
                                            {{ item.name }}
                                        </ColorfulNavItem>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques colorées -->
                        <div>
                            <h3 class="text-2xl font-bold text-slate-800 mb-6 flex items-center">
                                <StarIcon class="h-6 w-6 text-amber-600 mr-3" />
                                Statistiques Colorées
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-blue-100 text-sm font-medium">Total Réunions</p>
                                            <p class="text-3xl font-bold">156</p>
                                        </div>
                                        <UsersIcon class="h-12 w-12 text-blue-200" />
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-emerald-100 text-sm font-medium">Comités Actifs</p>
                                            <p class="text-3xl font-bold">23</p>
                                        </div>
                                        <UserGroupIcon class="h-12 w-12 text-emerald-200" />
                                    </div>
                                </div>
                                
                                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-purple-100 text-sm font-medium">Documents</p>
                                            <p class="text-3xl font-bold">89</p>
                                        </div>
                                        <DocumentIcon class="h-12 w-12 text-purple-200" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animations personnalisées */
@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Effet de brillance sur les titres */
h1, h2, h3 {
    position: relative;
    overflow: hidden;
}

h1::before,
h2::before,
h3::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.8s;
}

h1:hover::before,
h2:hover::before,
h3:hover::before {
    left: 100%;
}
</style> 