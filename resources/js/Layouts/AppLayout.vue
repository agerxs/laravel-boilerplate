<script setup lang="ts">
import {
    Bars3Icon,
    CalendarIcon,
    DocumentIcon,
    HomeIcon,
    UserGroupIcon,
    UsersIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const showingNavigationDropdown = ref(false);

const user = computed(() => page.props.auth.user);

const navigation = [
    { name: 'Tableau de bord', href: route('dashboard'), icon: HomeIcon },
    { name: 'Réunions', href: route('meetings.index'), icon: UsersIcon },
    { name: 'Comités Locaux', href: route('local-committees.index'), icon: UserGroupIcon },
    { name: 'Agenda', href: route('calendar.index'), icon: CalendarIcon },
    { name: 'Plaintes & Réclamations', href: '#', icon: DocumentIcon },
    { name: 'Paiement', href: '#', icon: DocumentIcon },
];

defineProps<{
    title?: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar Mobile -->
        <div class="lg:hidden">
            <!-- Overlay -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20"
                @click="showingNavigationDropdown = false"
            ></div>

            <!-- Menu mobile -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-y-0 left-0 flex flex-col w-64 bg-white border-r border-gray-200 z-30"
            >
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                    <div class="flex items-center flex-shrink-0 px-4 text-2xl font-bold">
                        Colocs
                    </div>
                    <button
                        @click="showingNavigationDropdown = false"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        <span class="sr-only">Fermer le menu</span>
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Navigation mobile -->
                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                    <Link
                        v-for="item in navigation"
                        :key="item.name"
                        :href="item.href"
                        :class="[
                            route().current(item.href)
                                ? 'bg-gray-100 text-gray-900'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                            'group flex items-center px-2 py-2 text-sm font-medium rounded-md'
                        ]"
                        @click="showingNavigationDropdown = false"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                route().current(item.href)
                                    ? 'text-gray-500'
                                    : 'text-gray-400 group-hover:text-gray-500',
                                'mr-3 flex-shrink-0 h-6 w-6'
                            ]"
                        />
                        {{ item.name }}
                    </Link>
                </nav>

                <!-- Footer mobile -->
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">{{ user.name }}</p>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton menu mobile -->
        <div class="lg:hidden">
            <div class="fixed top-0 left-0 right-0 flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 z-10">
                <button
                    @click="showingNavigationDropdown = true"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <span class="sr-only">Ouvrir le menu</span>
                    <Bars3Icon class="h-6 w-6" />
                </button>
                <div class="text-lg font-semibold text-gray-900">
                    {{ title }}
                </div>
                <div class="w-6"><!-- Spacer --></div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
            <div class="flex-1 flex flex-col min-h-0 bg-white border-r border-gray-200">
                <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                    <div class="flex items-center flex-shrink-0 px-4 text-2xl font-bold">
                        Colocs
                    </div>
                    <nav class="mt-5 flex-1 px-2 space-y-1">
                        <Link
                            v-for="item in navigation"
                            :key="item.name"
                            :href="item.href"
                            :class="[
                                route().current(item.href)
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-2 py-2 text-sm font-medium rounded-md'
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    route().current(item.href) ? 'text-gray-500' : 'text-gray-400 group-hover:text-gray-500',
                                    'mr-3 flex-shrink-0 h-6 w-6'
                                ]"
                                aria-hidden="true"
                            />
                            {{ item.name }}
                        </Link>
                    </nav>
                </div>
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div>
                                <img class="inline-block h-9 w-9 rounded-full" :src="`https://ui-avatars.com/api/?name=${user.name}`" :alt="user.name">
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                                    {{ user.name }}
                                </p>
                                <Link
                                    :href="route('profile.edit')"
                                    class="text-xs font-medium text-gray-500 group-hover:text-gray-700"
                                >
                                    Voir le profil
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64 flex flex-col flex-1">
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white border-b border-gray-200">
                <button
                    @click="showingNavigationDropdown = true"
                    class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 lg:hidden"
                >
                    <span class="sr-only">Ouvrir le menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Header content -->
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex">
                        <h1 class="text-2xl font-semibold text-gray-900 my-auto">
                            {{ title }}
                        </h1>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Profile dropdown -->
                        <div class="ml-3 relative">
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-gray-500 hover:text-gray-700"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<style>
/* Ajustement pour le contenu sous le header mobile */
@media (max-width: 1024px) {
    .py-12 {
        padding-top: 5rem;
    }
}
</style>
