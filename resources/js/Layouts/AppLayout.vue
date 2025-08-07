<script setup lang="ts">
import {
    Bars3Icon,
    CalendarIcon,
    Cog6ToothIcon,
    DocumentIcon,
    HomeIcon,
    UserGroupIcon,
    UsersIcon,
    XMarkIcon,
    UserIcon,
    ArrowUpTrayIcon
} from '@heroicons/vue/24/outline';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ResponsiveImage from '@/Components/ResponsiveImage.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { useToast } from '@/Composables/useToast';
import Toast from '@/Components/Toast.vue';

interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    pivot: {
        model_type: string;
        model_id: number;
        role_id: number;
    };
}

interface PageProps {
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
            roles: Role[];
            locality_id: number;
        };
    };
    flash?: {
        message?: string;
        type?: string;
    };
}

interface NavigationItem {
    name: string;
    href: string;
    icon: any;
    adminOnly?: boolean;
    roles?: string[];
}

interface CustomUser {
    id: number;
    name: string;
    email: string;
    roles: Role[];
    locality_id: number;
}

const navigation: NavigationItem[] = [
  { name: 'Tableau de bord', href: route('dashboard'), icon: HomeIcon },
  { name: 'Agenda', href: route('calendar.index'), icon: CalendarIcon },
  { 
    name: 'Comités Locaux', 
    href: route('local-committees.index'), 
    icon: UserGroupIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'admin', 'gestionnaire']
  },
  { 
    name: 'Représentants', 
    href: route('representatives.index'), 
    icon: UserIcon,
    roles: ['sous-prefet', 'secretaire']
  },
  { 
    name: 'Réunions', 
    href: route('meetings.index'), 
    icon: UsersIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'gestionnaire']
  },
  { 
    name: 'Historique des imports', 
    href: route('bulk-imports.index'), 
    icon: ArrowUpTrayIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'gestionnaire']
  },
  { 
    name: 'Gestion des Paiements', 
    href: route('meeting-payments.lists.index'), 
    icon: DocumentIcon,
    roles: ['sous-prefet', 'secretaire', 'gestionnaire']
  },
  {
    name: 'Paiements des Comités',
    href: route('executive-payments.index'),
    icon: DocumentIcon,
    roles: ['gestionnaire', 'admin']
  },
  { 
    name: 'Gestion des APKs', 
    href: route('admin.app_versions.index'), 
    icon: DocumentIcon, 
    roles: ['admin']
  },
  {
    name: 'Paramétrage',
    href: '/admin',
    icon: Cog6ToothIcon,
    roles: ['admin']
  }
]


const page = usePage()
const showingNavigationDropdown = ref(false);

const user = computed(() => page.props.auth?.user as CustomUser | undefined)

const filteredNavigation = computed(() => {
    const userRoles = user.value?.roles || [];
    console.log('User roles objects:', userRoles);

    const userRoleNames = userRoles.map(role => role?.name?.toLowerCase() || '').filter(name => name);
    console.log('User role names (lowercase):', userRoleNames);
    
    return navigation.filter((item: NavigationItem) => {
        // Si l'élément a des rôles spécifiques, vérifier si l'utilisateur a l'un de ces rôles
        if (item.roles) {
            const itemRoles = item.roles.map(role => role.toLowerCase());
            const hasRequiredRole = itemRoles.some(role => userRoleNames.includes(role));
            console.log(`Menu item ${item.name} - Required roles:`, itemRoles, 'Has access:', hasRequiredRole);
            return hasRequiredRole;
        }

        return true;
    });
})

const flash = computed(() => page.props.flash as { message?: string; type?: string } | undefined)

defineProps<{
    title?: string;
}>();

// Import et initialisation du composable useToast
const { toasts } = useToast();
</script>

<template>
    <div v-if="flash && flash.message" class="mb-8">
        <div class="p-2 bg-green-500 items-center text-green-100 leading-none lg:rounded-full flex lg:inline-flex">
            <span class="flex rounded-full bg-green-200 uppercase px-2 py-1 text-xs font-bold mr-3 text-green-500">Success</span>
            <span class="font-semibold mr-2 text-left flex-auto">{{ flash.message }}</span>
        </div>
    </div>

    <div class="min-h-screen bg-gray-100">
        <!-- Sidebar Mobile -->
        <div class="lg:hidden">
            <!-- Overlay -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 bg-gray-600 bg-opacity-75 z-20"
                @click="showingNavigationDropdown = false"
                role="presentation"
                aria-hidden="true"
            ></div>

            <!-- Menu mobile -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-y-0 left-0 flex flex-col w-64 bg-white border-r border-gray-200 z-30"
                role="dialog"
                aria-modal="true"
                :aria-label="'Menu de navigation'"
            >
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200">
                    <div class="flex items-center flex-shrink-0 px-4 text-2xl font-bold">
                        Colocs
                    </div>
                    <button
                        @click="showingNavigationDropdown = false"
                        class="text-gray-500 hover:text-gray-700"
                        aria-label="Fermer le menu"
                    >
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Navigation mobile -->
                <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto" role="navigation" aria-label="Navigation principale">
                    <template v-for="item in filteredNavigation" :key="item.name">
                        <a v-if="item.href.startsWith('/admin')"
                           :href="item.href"
                           :class="[
                               'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                               'group flex items-center px-2 py-2 text-sm font-medium rounded-md nav-link'
                           ]"
                           @click="showingNavigationDropdown = false"
                        >
                            <component :is="item.icon" class="mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                            {{ item.name }}
                        </a>
                        <Link v-else
                            :href="item.href"
                            :class="[
                                route().current(item.href)
                                    ? 'bg-gray-100 text-gray-900'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                'group flex items-center px-2 py-2 text-sm font-medium rounded-md nav-link'
                            ]"
                            @click="showingNavigationDropdown = false"
                            :aria-current="route().current(item.href) ? 'page' : undefined"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    route().current(item.href)
                                        ? 'text-gray-500'
                                        : 'text-gray-400 group-hover:text-gray-500',
                                    'mr-3 flex-shrink-0 h-6 w-6'
                                ]"
                                aria-hidden="true"
                            />
                            {{ item.name }}
                        </Link>
                    </template>
                </nav>

                <!-- Footer mobile -->
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-700">{{ user?.name }}</p>
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
                    <nav class="flex-1 px-2 py-4 space-y-1 overflow-y-auto" role="navigation" aria-label="Navigation principale">
                        <template v-for="item in filteredNavigation" :key="item.name">
                            <a v-if="item.href.startsWith('/admin')"
                               :href="item.href"
                               :class="[
                                   'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                   'group flex items-center px-2 py-2 text-base font-medium rounded-md'
                               ]"
                            >
                                <component :is="item.icon" class="mr-4 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                                {{ item.name }}
                            </a>
                            <Link v-else
                                :href="item.href"
                                :class="[
                                    route().current(item.href)
                                        ? 'bg-gray-100 text-gray-900'
                                        : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                                    'group flex items-center px-2 py-2 text-base font-medium rounded-md'
                                ]"
                                :aria-current="route().current(item.href) ? 'page' : undefined"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        route().current(item.href)
                                            ? 'text-gray-500'
                                            : 'text-gray-400 group-hover:text-gray-500',
                                        'mr-4 flex-shrink-0 h-6 w-6'
                                    ]"
                                    aria-hidden="true"
                                />
                                {{ item.name }}
                            </Link>
                        </template>
                    </nav>
                </div>
                <div class="flex-shrink-0 flex border-t border-gray-200 p-4">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div>
                                <ResponsiveImage
                                    :src="`https://ui-avatars.com/api/?name=${user?.name || 'User'}`"
                                    :alt="user?.name || 'User'"
                                    :width="36"
                                    :height="36"
                                    class="rounded-full"
                                />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                                    {{ user?.name }}
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
                    <div class="max-w-10xl mx-auto px-4 sm:px-6 md:px-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Ajout du composant Toast pour afficher les notifications -->
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
        <div v-for="toast in toasts" :key="toast.id">
            <Toast :message="toast.message" :type="toast.type" />
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
