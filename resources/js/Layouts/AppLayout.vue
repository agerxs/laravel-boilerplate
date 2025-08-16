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
    color: string;
    gradient: string;
}

interface CustomUser {
    id: number;
    name: string;
    email: string;
    roles: Role[];
    locality_id: number;
}

const navigation: NavigationItem[] = [
  { 
    name: 'Tableau de bord', 
    href: route('dashboard'), 
    icon: HomeIcon,
    color: 'text-blue-600',
    gradient: 'from-blue-500 to-blue-600'
  },
  { 
    name: 'Agenda', 
    href: route('calendar.index'), 
    icon: CalendarIcon,
    color: 'text-purple-600',
    gradient: 'from-purple-500 to-purple-600'
  },
  { 
    name: 'Comités Locaux', 
    href: route('local-committees.index'), 
    icon: UserGroupIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'admin', 'gestionnaire'],
    color: 'text-emerald-600',
    gradient: 'from-emerald-500 to-emerald-600'
  },
  { 
    name: 'Représentants', 
    href: route('representatives.index'), 
    icon: UserIcon,
    roles: ['sous-prefet', 'secretaire'],
    color: 'text-indigo-600',
    gradient: 'from-indigo-500 to-indigo-600'
  },
  { 
    name: 'Réunions', 
    href: route('meetings.index'), 
    icon: UsersIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'gestionnaire'],
    color: 'text-orange-600',
    gradient: 'from-orange-500 to-orange-600'
  },
  { 
    name: 'Historique des imports', 
    href: route('bulk-imports.index'), 
    icon: ArrowUpTrayIcon,
    roles: ['sous-prefet', 'secretaire', 'prefet', 'gestionnaire'],
    color: 'text-cyan-600',
    gradient: 'from-cyan-500 to-cyan-600'
  },
  { 
    name: 'Gestion des Paiements', 
    href: route('meeting-payments.lists.index'), 
    icon: DocumentIcon,
    roles: ['sous-prefet', 'secretaire', 'gestionnaire'],
    color: 'text-rose-600',
    gradient: 'from-rose-500 to-rose-600'
  },
//   {
//     name: 'Paiements des Comités',
//     href: route('executive-payments.index'),
//     icon: DocumentIcon,
//     roles: ['gestionnaire', 'admin'],
//     color: 'text-violet-600',
//     gradient: 'from-violet-500 to-violet-600'
//   },
  { 
    name: 'Gestion des APKs', 
    href: route('admin.app_versions.index'), 
    icon: DocumentIcon, 
    roles: ['admin'],
    color: 'text-slate-600',
    gradient: 'from-slate-500 to-slate-600'
  },
  {
    name: 'Paramétrage',
    href: '/admin',
    icon: Cog6ToothIcon,
    roles: ['admin'],
    color: 'text-amber-600',
    gradient: 'from-amber-500 to-amber-600'
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
        <div class="p-4 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-xl shadow-lg border border-emerald-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-white">{{ flash.message }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <!-- Sidebar Mobile -->
        <div class="lg:hidden">
            <!-- Overlay -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-20"
                @click="showingNavigationDropdown = false"
                role="presentation"
                aria-hidden="true"
            ></div>

            <!-- Menu mobile -->
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-y-0 left-0 flex flex-col w-72 bg-white/95 backdrop-blur-xl border-r border-slate-200/50 shadow-2xl z-30"
                role="dialog"
                aria-modal="true"
                :aria-label="'Menu de navigation'"
            >
                <div class="flex items-center justify-between h-20 px-6 border-b border-slate-200/50 bg-gradient-to-r from-blue-600 to-indigo-600">
                    <div class="flex items-center flex-shrink-0 px-4 text-2xl font-bold text-white">
                        <div class="w-8 h-8 bg-white/20 rounded-lg mr-3 flex items-center justify-center">
                            <span class="text-white font-bold text-lg">C</span>
                        </div>
                        Colocs
                    </div>
                    <button
                        @click="showingNavigationDropdown = false"
                        class="text-white/80 hover:text-white transition-colors duration-200"
                        aria-label="Fermer le menu"
                    >
                        <XMarkIcon class="h-6 w-6" />
                    </button>
                </div>

                <!-- Navigation mobile -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto scrollbar-thin" role="navigation" aria-label="Navigation principale">
                    <template v-for="item in filteredNavigation" :key="item.name">
                        <a v-if="item.href.startsWith('/admin')"
                           :href="item.href"
                           :class="[
                               'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:shadow-md',
                               'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 nav-link'
                           ]"
                           @click="showingNavigationDropdown = false"
                        >
                            <component :is="item.icon" class="mr-3 flex-shrink-0 h-5 w-5 transition-all duration-200" :class="item.color" aria-hidden="true" />
                            {{ item.name }}
                        </a>
                        <Link v-else
                            :href="item.href"
                            :class="[
                                route().current(item.href)
                                    ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 border border-blue-200 shadow-md'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:shadow-md',
                                'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 nav-link'
                            ]"
                            @click="showingNavigationDropdown = false"
                            :aria-current="route().current(item.href) ? 'page' : undefined"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    route().current(item.href)
                                        ? 'text-blue-500'
                                        : item.color,
                                    'mr-3 flex-shrink-0 h-5 w-5 transition-all duration-200'
                                ]"
                                aria-hidden="true"
                            />
                            {{ item.name }}
                        </Link>
                    </template>
                </nav>

                <!-- Footer mobile -->
                <div class="flex-shrink-0 flex border-t border-slate-200/50 p-6 bg-slate-50/50">
                    <div class="flex items-center w-full">
                        <div class="flex-shrink-0">
                            <ResponsiveImage
                                :src="`https://ui-avatars.com/api/?name=${user?.name || 'User'}&background=3b82f6&color=fff`"
                                :alt="user?.name || 'User'"
                                :width="40"
                                :height="40"
                                class="rounded-xl ring-2 ring-white shadow-md"
                            />
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-slate-700">{{ user?.name }}</p>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="text-xs text-slate-500 hover:text-slate-700 transition-colors duration-200"
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
            <div class="fixed top-0 left-0 right-0 flex items-center justify-between h-20 px-6 bg-white/95 backdrop-blur-xl border-b border-slate-200/50 shadow-lg z-10">
                <button
                    @click="showingNavigationDropdown = true"
                    class="text-slate-600 hover:text-slate-800 transition-colors duration-200"
                >
                    <span class="sr-only">Ouvrir le menu</span>
                    <Bars3Icon class="h-6 w-6" />
                </button>
                <div class="text-lg font-semibold text-slate-900">
                    {{ title }}
                </div>
                <div class="w-6"><!-- Spacer --></div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0">
            <div class="flex-1 flex flex-col min-h-0 bg-white/95 backdrop-blur-xl border-r border-slate-200/50 shadow-xl">
                <div class="flex-1 flex flex-col pt-6 pb-4 overflow-y-auto scrollbar-thin">
                    <div class="flex items-center flex-shrink-0 px-6 py-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl mr-3 flex items-center justify-center shadow-lg">
                            <span class="text-white font-bold text-xl">C</span>
                        </div>
                        <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            Colocs
                        </div>
                    </div>
                    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto" role="navigation" aria-label="Navigation principale">
                        <template v-for="item in filteredNavigation" :key="item.name">
                            <a v-if="item.href.startsWith('/admin')"
                               :href="item.href"
                               :class="[
                                   'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:shadow-md',
                                   'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200'
                               ]"
                            >
                                <component :is="item.icon" class="mr-4 flex-shrink-0 h-5 w-5 transition-all duration-200" :class="item.color" aria-hidden="true" />
                                {{ item.name }}
                            </a>
                            <Link v-else
                                :href="item.href"
                                :class="[
                                    route().current(item.href)
                                        ? 'bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 border border-blue-200 shadow-md'
                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 hover:shadow-md',
                                    'group flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200'
                                ]"
                                :aria-current="route().current(item.href) ? 'page' : undefined"
                            >
                                <component
                                    :is="item.icon"
                                    :class="[
                                        route().current(item.href)
                                            ? 'text-blue-500'
                                            : item.color,
                                        'mr-4 flex-shrink-0 h-5 w-5 transition-all duration-200'
                                    ]"
                                    aria-hidden="true"
                                />
                                {{ item.name }}
                            </Link>
                        </template>
                    </nav>
                </div>
                <div class="flex-shrink-0 flex border-t border-slate-200/50 p-6 bg-slate-50/50">
                    <div class="flex-shrink-0 w-full group block">
                        <div class="flex items-center">
                            <div>
                                <ResponsiveImage
                                    :src="`https://ui-avatars.com/api/?name=${user?.name || 'User'}&background=3b82f6&color=fff`"
                                    :alt="user?.name || 'User'"
                                    :width="40"
                                    :height="40"
                                    class="rounded-xl ring-2 ring-white shadow-md"
                                />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-slate-700 group-hover:text-slate-900 transition-colors duration-200">
                                    {{ user?.name }}
                                </p>
                                <Link
                                    :href="route('profile.edit')"
                                    class="text-xs font-medium text-slate-500 group-hover:text-slate-700 transition-colors duration-200"
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
        <div class="lg:pl-72 flex flex-col flex-1">
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-20 bg-white/95 backdrop-blur-xl border-b border-slate-200/50 shadow-lg">
                <button
                    @click="showingNavigationDropdown = true"
                    class="px-6 border-r border-slate-200/50 text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 lg:hidden transition-colors duration-200"
                >
                    <span class="sr-only">Ouvrir le menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Header content -->
                <div class="flex-1 px-6 flex justify-between">
                    <div class="flex-1 flex">
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-slate-700 to-slate-900 bg-clip-text text-transparent my-auto">
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
                                class="text-slate-500 hover:text-slate-700 transition-colors duration-200 hover:bg-slate-100 px-3 py-2 rounded-lg"
                            >
                                Déconnexion
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1">
                <div class="py-8">
                    <div class="max-w-10xl mx-auto px-6 sm:px-8 md:px-10">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Ajout du composant Toast pour afficher les notifications -->
    <div class="fixed bottom-6 right-6 z-50 space-y-3">
        <div v-for="toast in toasts" :key="toast.id">
            <Toast :message="toast.message" :type="toast.type" />
        </div>
    </div>
</template>

<style>
/* Ajustement pour le contenu sous le header mobile */
@media (max-width: 1024px) {
    .py-12 {
        padding-top: 6rem;
    }
}

/* Animations et transitions personnalisées */
.nav-link {
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s;
}

.nav-link:hover::before {
    left: 100%;
}

/* Scrollbar personnalisée */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}

/* Animation des éléments de navigation */
.nav-link {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-link:hover {
    transform: translateX(4px);
}

/* Effet de particules sur le logo */
.logo-container {
    position: relative;
    overflow: hidden;
}

.logo-container::before,
.logo-container::after {
    content: '';
    position: absolute;
    width: 4px;
    height: 4px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: 50%;
    animation: logo-particles 4s infinite linear;
}

.logo-container::before {
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.logo-container::after {
    top: 60%;
    right: 20%;
    animation-delay: 2s;
}

@keyframes logo-particles {
    0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-20px) rotate(360deg);
        opacity: 0;
    }
}
</style>
