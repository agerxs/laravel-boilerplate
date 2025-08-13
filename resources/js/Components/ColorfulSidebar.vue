<script setup lang="ts">
import { computed } from 'vue';
import ColorfulNavItem from './ColorfulNavItem.vue';

interface NavItem {
    name: string;
    href: string;
    icon: any;
    color: string;
    gradient: string;
    badge?: string;
    badgeColor?: string;
}

interface Props {
    title?: string;
    subtitle?: string;
    items: NavItem[];
    currentRoute?: string;
    variant?: 'default' | 'gradient' | 'minimal';
    size?: 'sm' | 'md' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Navigation',
    subtitle: '',
    currentRoute: '',
    variant: 'default',
    size: 'md'
});

const sidebarClasses = computed(() => {
    const baseClasses = 'flex flex-col h-full';
    
    const variantClasses = {
        default: 'bg-white/95 backdrop-blur-xl border-r border-slate-200/50 shadow-xl',
        gradient: 'bg-gradient-to-b from-blue-50/95 to-indigo-50/95 backdrop-blur-xl border-r border-blue-200/50 shadow-xl',
        minimal: 'bg-transparent'
    };
    
    return [baseClasses, variantClasses[props.variant]].join(' ');
});

const headerClasses = computed(() => {
    const baseClasses = 'flex-shrink-0 px-6 py-4 border-b border-slate-200/50';
    
    const variantClasses = {
        default: 'bg-white/50',
        gradient: 'bg-gradient-to-r from-blue-600/10 to-indigo-600/10',
        minimal: 'bg-transparent'
    };
    
    return [baseClasses, variantClasses[props.variant]].join(' ');
});

const isActiveRoute = (href: string) => {
    return props.currentRoute === href || props.currentRoute.startsWith(href);
};
</script>

<template>
    <div :class="sidebarClasses">
        <!-- En-tête de la sidebar -->
        <div :class="headerClasses">
            <div class="flex items-center">
                <!-- Logo/icône -->
                <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl mr-3 flex items-center justify-center shadow-lg">
                    <span class="text-white font-bold text-xl">C</span>
                </div>
                
                <!-- Titre et sous-titre -->
                <div class="flex-1">
                    <h2 class="text-lg font-bold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                        {{ title }}
                    </h2>
                    <p v-if="subtitle" class="text-sm text-slate-500">
                        {{ subtitle }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto scrollbar-thin">
            <template v-for="item in items" :key="item.name">
                <div class="relative group">
                    <!-- Élément de navigation -->
                    <ColorfulNavItem
                        :href="item.href"
                        :icon="item.icon"
                        :color="item.color"
                        :gradient="item.gradient"
                        :is-active="isActiveRoute(item.href)"
                        :size="size"
                    >
                        {{ item.name }}
                    </ColorfulNavItem>
                    
                    <!-- Badge (optionnel) -->
                    <div 
                        v-if="item.badge"
                        class="absolute top-2 right-2"
                    >
                        <span 
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                            :class="item.badgeColor || 'bg-red-100 text-red-800'"
                        >
                            {{ item.badge }}
                        </span>
                    </div>
                    
                    <!-- Effet de particules au survol -->
                    <div 
                        class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
                    >
                        <div 
                            class="absolute w-1 h-1 bg-blue-400/60 rounded-full animate-pulse"
                            style="top: 20%; left: 10%; animation-delay: 0s;"
                        ></div>
                        <div 
                            class="absolute w-1 h-1 bg-indigo-400/60 rounded-full animate-pulse"
                            style="top: 60%; right: 20%; animation-delay: 0.5s;"
                        ></div>
                        <div 
                            class="absolute w-1 h-1 bg-purple-400/60 rounded-full animate-pulse"
                            style="top: 40%; left: 50%; animation-delay: 1s;"
                        ></div>
                    </div>
                </div>
            </template>
        </nav>

        <!-- Footer de la sidebar -->
        <div class="flex-shrink-0 p-4 border-t border-slate-200/50 bg-slate-50/30">
            <div class="text-center">
                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg mx-auto mb-2 flex items-center justify-center shadow-md">
                    <span class="text-white font-bold text-sm">C</span>
                </div>
                <p class="text-xs text-slate-500 font-medium">
                    Colocs v2.0
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar personnalisée */
.scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.5);
    border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.7);
}

/* Animation des particules */
@keyframes particle-float {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: translateY(-15px) scale(0.8);
        opacity: 0;
    }
}

.animate-pulse {
    animation: particle-float 3s infinite ease-in-out;
}

/* Effet de brillance sur le logo */
.w-10.h-10 {
    position: relative;
    overflow: hidden;
}

.w-10.h-10::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s;
}

.w-10.h-10:hover::before {
    left: 100%;
}

/* Transition fluide pour tous les éléments */
* {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style> 